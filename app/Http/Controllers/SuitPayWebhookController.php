<?php

namespace App\Http\Controllers;

use App\Models\LedgerEntry;
use App\Models\PaymentTransaction;
use App\Models\PlatformSetting;
use App\Models\PostPurchase;
use App\Models\Subscription;
use App\Models\Withdrawal;
use App\Services\SubscriptionActivation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SuitPayWebhookController extends Controller
{
    private const SUITPAY_IPS = ['3.132.137.46'];

    /**
     * Webhook principal da SuitPay
     *
     * REGRA DE OURO:
     * - O webhook SEMPRE define o status final da transação
     * - A resposta da API é apenas preliminar
     */
    public function handle(Request $request)
    {
        $ip = $request->header('cf-connecting-ip') ?? $request->ip();

        if (!in_array($ip, self::SUITPAY_IPS, true)) {
            Log::warning('SUITPAY WEBHOOK - IP NAO AUTORIZADO', [
                'ip'      => $ip,
                'headers' => $request->headers->all(),
                'payload' => $request->all(),
            ]);
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $payload = $request->all();

        // Log bruto (NUNCA remova isso em ambiente de estudo)
        Log::info('SUITPAY WEBHOOK RECEBIDO', [
            'ip' => $request->ip(),
            'headers' => $request->headers->all(),
            'payload' => $payload,
        ]);

        /**
         * Campos comuns
         */
        $typeTransaction   = $payload['typeTransaction'] ?? null; // PIX | CARD
        $statusTransaction = $payload['statusTransaction'] ?? null;
        $requestNumber     = $payload['requestNumber'] ?? null;
        $idTransaction     = $payload['idTransaction'] ?? null;
        $value             = $payload['value'] ?? null;

        /**
         * Mensagens adicionais (principalmente cartão)
         */
        $acquirerMessage = $payload['acquirerMessage'] ?? null;
        $responseMessage = $payload['response'] ?? null;

        /**
         * Validação mínima
         */
        // PIX_CASHOUT não tem requestNumber — é saque de criadora, não pagamento de assinatura
        $requiresRequestNumber = ($typeTransaction !== 'PIX_CASHOUT');

        if (!$typeTransaction || !$statusTransaction || ($requiresRequestNumber && !$requestNumber)) {
            Log::warning('SUITPAY WEBHOOK INVÁLIDO / INCOMPLETO', [
                'payload' => $payload,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Payload inválido',
            ], 400);
        }

        /**
         * ===========================
         * TRATAMENTO POR TIPO
         * ===========================
         */
        switch ($typeTransaction) {

            /**
             * ===========================
             * PIX
             * ===========================
             */
            case 'PIX':
                // Busca a transação pelo requestNumber
                $transaction = PaymentTransaction::where('request_number', $requestNumber)
                    ->where('type', 'pix')
                    ->first();

                if (!$transaction) {
                    Log::warning('PIX TRANSAÇÃO NÃO ENCONTRADA', [
                        'requestNumber' => $requestNumber,
                        'payload' => $payload,
                    ]);
                    break;
                }

                // Mapeia status do SuitPay para status do banco
                $newStatus = null;
                switch ($statusTransaction) {
                    case 'PAID_OUT':
                        $newStatus = 'paid_out';
                        break;
                    case 'UNPAID':
                        $newStatus = 'unpaid';
                        break;
                    case 'CANCELED':
                        $newStatus = 'canceled';
                        break;
                    default:
                        Log::info('PIX STATUS NÃO MAPEADO', [
                            'status' => $statusTransaction,
                            'payload' => $payload,
                        ]);
                        break;
                }

                // Atualiza status na base de dados
                if ($newStatus) {
                    // Monta nota com informações do webhook
                    $noteParts = [];
                    if ($statusTransaction) {
                        $noteParts[] = "Status: {$statusTransaction}";
                    }
                    if (isset($payload['msg']) && !empty($payload['msg'])) {
                        $noteParts[] = "Mensagem: {$payload['msg']}";
                    }
                    if (isset($payload['acquirerMessage']) && !empty($payload['acquirerMessage'])) {
                        $noteParts[] = "Adquirente: {$payload['acquirerMessage']}";
                    }
                    $note = !empty($noteParts) ? implode(' | ', $noteParts) : ($transaction->note ?? 'Status atualizado via webhook');
                    
                    $transaction->update([
                        'status' => $newStatus,
                        'transaction_id' => $idTransaction ?? $transaction->transaction_id,
                        'webhook_data' => $payload,
                        'note' => $note,
                    ]);

                    Log::info('PIX STATUS ATUALIZADO NO BANCO', [
                        'requestNumber' => $requestNumber,
                        'transactionId' => $transaction->id,
                        'oldStatus' => $transaction->getOriginal('status'),
                        'newStatus' => $newStatus,
                        'note' => $note,
                    ]);

                    // Se foi pago, detecta o tipo da transação
                    if ($newStatus === 'paid_out') {
                        if ($transaction->post_id) {
                            // Conteúdo Único (PPV)
                            $ppvController = new \App\Http\Controllers\PPVCheckoutController();
                            $ppvController->createPostPurchaseFromTransaction($transaction);
                        } elseif ($transaction->subscription_plan_id && $transaction->creator_id) {
                            // Assinatura
                            if (!$transaction->subscription_id) {
                                $this->createSubscriptionFromTransaction($transaction);
                                $transaction->refresh();
                            }
                        } else {
                            // Recarga de carteira
                            $this->creditWalletFromTransaction($transaction);
                        }
                    }
                }

                break;

            /**
             * ===========================
             * CARTÃO
             * ===========================
             */
            case 'CARD':
                // Busca a transação pelo requestNumber
                $transaction = PaymentTransaction::where('request_number', $requestNumber)
                    ->where('type', 'card')
                    ->first();

                if (!$transaction) {
                    Log::warning('CARTÃO TRANSAÇÃO NÃO ENCONTRADA', [
                        'requestNumber' => $requestNumber,
                        'payload' => $payload,
                    ]);
                    break;
                }

                // Mapeia status do SuitPay para status do banco
                $newStatus = null;
                switch ($statusTransaction) {
                    case 'PAID_OUT':
                        $newStatus = 'paid_out';
                        break;
                    case 'PAYMENT_ACCEPT':
                        $newStatus = 'waiting_for_approval';
                        break;
                    case 'UNPAID':
                        $newStatus = 'unpaid';
                        break;
                    case 'CANCELED':
                        $newStatus = 'canceled';
                        break;
                    case 'CHARGEBACK':
                        $newStatus = 'unpaid'; // Trata chargeback como unpaid
                        break;
                    case 'WAITING_FOR_APPROVAL':
                        $newStatus = 'waiting_for_approval';
                        break;
                    default:
                        Log::info('CARTÃO STATUS NÃO MAPEADO', [
                            'status' => $statusTransaction,
                            'payload' => $payload,
                        ]);
                        break;
                }

                // Atualiza status na base de dados
                if ($newStatus) {
                    // Monta nota com informações do webhook
                    $noteParts = [];
                    if ($statusTransaction) {
                        $noteParts[] = "Status: {$statusTransaction}";
                    }
                    if (isset($payload['msg']) && !empty($payload['msg'])) {
                        $noteParts[] = "Mensagem: {$payload['msg']}";
                    }
                    if (isset($payload['acquirerMessage']) && !empty($payload['acquirerMessage'])) {
                        $noteParts[] = "Adquirente: {$payload['acquirerMessage']}";
                    }
                    if (isset($payload['response']) && !empty($payload['response'])) {
                        $noteParts[] = "Código: {$payload['response']}";
                    }
                    $note = !empty($noteParts) ? implode(' | ', $noteParts) : ($transaction->note ?? 'Status atualizado via webhook');
                    
                    $transaction->update([
                        'status' => $newStatus,
                        'transaction_id' => $idTransaction ?? $transaction->transaction_id,
                        'webhook_data' => $payload,
                        'note' => $note,
                    ]);

                    Log::info('CARTÃO STATUS ATUALIZADO NO BANCO', [
                        'requestNumber' => $requestNumber,
                        'transactionId' => $transaction->id,
                        'oldStatus' => $transaction->getOriginal('status'),
                        'newStatus' => $newStatus,
                        'note' => $note,
                    ]);

                    // Se foi pago, detecta o tipo da transação
                    if ($newStatus === 'paid_out') {
                        if ($transaction->post_id) {
                            // Conteúdo Único (PPV)
                            $ppvController = new \App\Http\Controllers\PPVCheckoutController();
                            $ppvController->createPostPurchaseFromTransaction($transaction);
                        } elseif ($transaction->subscription_plan_id && $transaction->creator_id) {
                            // Assinatura
                            if (!$transaction->subscription_id) {
                                $this->createSubscriptionFromTransaction($transaction);
                                $transaction->refresh();
                            }
                        } else {
                            // Recarga de carteira
                            $this->creditWalletFromTransaction($transaction);
                        }
                    }

                    // Estorno: tira o dinheiro da criadora, corta o acesso da venda e
                    // bloqueia quem pediu (bento 21/09).
                    if ($statusTransaction === 'CHARGEBACK') {
                        $this->handleChargeback($transaction);
                    }
                }

                break;

            /**
             * ===========================
             * PIX_CASHOUT — Saque de criadora
             * ===========================
             */
            case 'PIX_CASHOUT':
                if (!$idTransaction) {
                    Log::warning('PIX_CASHOUT WEBHOOK SEM idTransaction', [
                        'payload' => $payload,
                    ]);
                    break;
                }

                $withdrawal = Withdrawal::where('suitpay_transaction_id', $idTransaction)->first();

                if (!$withdrawal) {
                    Log::warning('PIX_CASHOUT SAQUE NÃO ENCONTRADO NO BANCO', [
                        'idTransaction' => $idTransaction,
                        'payload' => $payload,
                    ]);
                    break;
                }

                Log::info('PIX_CASHOUT WEBHOOK CONFIRMADO', [
                    'withdrawal_id'  => $withdrawal->id,
                    'idTransaction'  => $idTransaction,
                    'status'         => $statusTransaction,
                    'value'          => $value,
                    'destinataria'   => $payload['destinationName'] ?? null,
                ]);

                // Ledger: registra a taxa de saque (3,5%) só quando o saque saiu de fato
                if ($statusTransaction === 'PAID_OUT') {
                    LedgerEntry::record([
                        'entry_type'   => 'cashout',
                        'withdrawal_id' => $withdrawal->id,
                        'gross_amount' => $withdrawal->amount,
                        'suitpay_fee'  => PlatformSetting::suitpayFeeOut((float) $withdrawal->amount),
                        'withdraw_fee' => (float) ($withdrawal->fee ?? 0), // taxa cobrada do usuário = receita da plataforma
                        'creator_amount'   => 0,
                        'affiliate_amount' => 0,
                        'occurred_at'  => now(),
                    ]);
                }

                break;

            /**
             * ===========================
             * DESCONHECIDO
             * ===========================
             */
            default:
                Log::warning('TIPO DE TRANSAÇÃO DESCONHECIDO', [
                    'typeTransaction' => $typeTransaction,
                    'payload' => $payload,
                ]);
                break;
        }

        /**
         * Resposta obrigatória
         */
        return response()->json([
            'success' => true,
            'message' => 'Webhook processado com sucesso',
        ]);
    }

    /**
     * Cria assinatura quando pagamento PIX é confirmado
     */
    /**
     * Estorno de cartao (bento 21/09): o banco devolveu o dinheiro pro cliente, entao o
     * valor sai do saldo da criadora, o acesso que a venda deu morre e quem pediu o estorno
     * e bloqueado. O debito vai como credito manual NEGATIVO, que o getAvailableBalance ja
     * soma — e por isso o saldo pode ficar negativo quando a venda ja tinha sido sacada.
     *
     * Nunca rodou em producao: nenhum cartao foi aprovado na historia da plataforma (16
     * tentativas, zero pagas, antifraude do SuitPay). Isto existe pra estar pronto ANTES de
     * o cartao ser liberado, senao o saque automatico deixa o dinheiro sair sem volta.
     *
     * ponytail: o debito bate sempre no saldo LIBERADO. Se o estorno chegar dentro dos
     * 7 dias de card_release_days, a venda ainda esta em 'a liberar' e por alguns dias a
     * tela mostra liberado a menos e a liberar a mais. A conta fecha sozinha quando o
     * prazo vence (a assinatura entra no liberado e cancela o debito), e o erro e pro lado
     * seguro: segura mais dinheiro, nao menos. Se incomodar, o proximo passo e filtrar as
     * assinaturas estornadas em getAvailableBalance E getPendingBalance e largar o
     * lancamento negativo so pra parte que ja tinha sido sacada.
     */
    private function handleChargeback(PaymentTransaction $transaction): void
    {
        // O SuitPay reenvia webhook. Sem esta trava, o mesmo estorno debitava a criadora
        // duas vezes. O marcador vai gravado no admin_notes do lancamento negativo.
        $marcador = "estorno:tx:{$transaction->id}";
        if (\App\Models\ManualCredit::where('admin_notes', 'like', "%{$marcador}%")->exists()) {
            Log::info('ESTORNO JA PROCESSADO, IGNORANDO REENVIO', ['transaction_id' => $transaction->id]);
            return;
        }

        // withoutGlobalScope: conta desativada some do Eloquent e a gente precisa dela pra
        // bloquear e pra ter o email.
        $comprador = \App\Models\User::withoutGlobalScope('active')->find($transaction->user_id);

        $debito    = 0.0;
        $criadorId = null;
        $oQueEra   = null;

        if ($transaction->post_id) {
            $compra = \App\Models\PostPurchase::where('payment_transaction_id', $transaction->id)->first();
            if ($compra) {
                $debito    = (float) $compra->creator_amount;
                $criadorId = $compra->creator_id;
                $oQueEra   = 'um conteúdo avulso';
                $compra->delete(); // o acesso ao post morre junto com a venda
            }
        } elseif ($transaction->subscription_plan_id && $transaction->creator_id) {
            $assinatura = $transaction->subscription_id
                ? \App\Models\Subscription::find($transaction->subscription_id)
                : null;
            if ($assinatura) {
                $debito    = (float) $assinatura->creator_amount;
                $criadorId = $assinatura->creator_id;
                $oQueEra   = 'uma assinatura';
                $assinatura->update(['is_active' => false]);
            }
        } else {
            // ponytail: recarga de carteira estornada nao debita sozinho. O dinheiro pode ja
            // ter virado compra e estar espalhado em varias criadoras, e desfazer isso e um
            // estorno em cascata que nunca aconteceu aqui. Grita no log pra um humano. Se
            // comecar a acontecer, o proximo passo e debitar a carteira do comprador e deixar
            // ela negativa, igual ao saldo da criadora.
            Log::error('ESTORNO DE RECARGA DE CARTEIRA - PRECISA DE HUMANO', [
                'transaction_id' => $transaction->id,
                'user_id'        => $transaction->user_id,
                'amount'         => $transaction->amount,
            ]);
        }

        if ($criadorId && $debito > 0) {
            \App\Models\ManualCredit::create([
                'user_id'       => $criadorId,
                'type'          => 'creator',
                'admin_user_id' => null,
                'amount'        => -$debito,
                'reason'        => 'Estorno de cartão: o cliente pediu estorno ao banco e o dinheiro foi devolvido a ele.',
                'admin_notes'   => "Automático. {$marcador}, {$oQueEra}, comprador #{$transaction->user_id}.",
            ]);

            $criadora = \App\Models\User::withoutGlobalScope('active')->find($criadorId);
            if ($criadora) {
                try {
                    \Illuminate\Support\Facades\Mail::to($criadora->email)->send(
                        new \App\Mail\ChargebackMail($criadora, 'creator', ['valor' => $debito, 'oQueEra' => $oQueEra])
                    );
                } catch (\Exception $e) {
                    Log::error('ESTORNO - FALHA NO EMAIL DA CRIADORA', ['error' => $e->getMessage()]);
                }
            }
        }

        // Bloqueia quem pediu o estorno. Admin nunca, pra ninguem se trancar fora do painel.
        if ($comprador && !$comprador->is_admin && !$comprador->blocked_at) {
            $comprador->update([
                'blocked_at'     => now(),
                'blocked_reason' => "Estorno de cartão na transação #{$transaction->id}",
            ]);

            try {
                \Illuminate\Support\Facades\Mail::to($comprador->email)->send(
                    new \App\Mail\ChargebackMail($comprador, 'subscriber')
                );
            } catch (\Exception $e) {
                Log::error('ESTORNO - FALHA NO EMAIL DO COMPRADOR', ['error' => $e->getMessage()]);
            }
        }

        Log::warning('ESTORNO DE CARTAO PROCESSADO', [
            'transaction_id' => $transaction->id,
            'debito'         => $debito,
            'creator_id'     => $criadorId,
            'comprador'      => $transaction->user_id,
        ]);
    }

    private function createSubscriptionFromTransaction(PaymentTransaction $transaction)
    {
        try {
            $subscription = SubscriptionActivation::fromTransaction($transaction);

            if (!$subscription->wasRecentlyCreated) {
                Log::warning('ASSINATURA JA EXISTE PARA ESTA TRANSACAO', [
                    'transactionId' => $transaction->id,
                    'userId'        => $transaction->user_id,
                    'creatorId'     => $transaction->creator_id,
                ]);
                return;
            }

            Log::info('ASSINATURA CRIADA VIA WEBHOOK', [
                'transactionId'  => $transaction->id,
                'subscriptionId' => $subscription->id,
                'userId'         => $transaction->user_id,
                'creatorId'      => $transaction->creator_id,
            ]);
        } catch (\Exception $e) {
            // Webhook engole e loga de proposito: o SuitPay reenvia, e estourar aqui viraria
            // retry infinito. Quem paga com saldo NAO passa por este caminho.
            Log::error('ERRO AO CRIAR ASSINATURA VIA WEBHOOK', [
                'transactionId' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Credita saldo na carteira quando pagamento de wallet é confirmado
     */
    private function creditWalletFromTransaction(PaymentTransaction $transaction)
    {
        try {
            $user = $transaction->user;
            $amount = $transaction->amount;

            // Obtém ou cria a carteira do usuário
            $wallet = $user->getOrCreateWallet();
            
            // Verifica se já foi creditado (evita duplicação)
            $existingTransaction = \App\Models\WalletTransaction::where('payment_transaction_id', $transaction->id)->first();
            if ($existingTransaction) {
                Log::info('Saldo já creditado para esta transação', [
                    'user_id' => $user->id,
                    'transaction_id' => $transaction->id,
                ]);
                return;
            }
            
            // Adiciona saldo à carteira
            $walletTransaction = $wallet->addBalance(
                $amount,
                null, // Não é admin
                "Depósito via {$transaction->type} - Transação #{$transaction->id}",
                "Transação de pagamento: {$transaction->request_number}",
                $transaction->id // payment_transaction_id
            );

            Log::info('SALDO CREDITADO NA CARTEIRA VIA WEBHOOK', [
                'transactionId' => $transaction->id,
                'userId' => $user->id,
                'amount' => $amount,
                'walletTransactionId' => $walletTransaction->id,
            ]);

        } catch (\Exception $e) {
            Log::error('ERRO AO CREDITAR CARTEIRA VIA WEBHOOK', [
                'transactionId' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
