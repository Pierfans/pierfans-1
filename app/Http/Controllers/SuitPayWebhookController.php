<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Models\PlatformSetting;
use App\Models\Subscription;
use App\Models\Withdrawal;
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

                    // Se foi pago, verifica se é transação de wallet ou assinatura
                    if ($newStatus === 'paid_out') {
                        // Se não tem subscription_plan_id nem creator_id, é transação de wallet
                        if (!$transaction->subscription_plan_id && !$transaction->creator_id) {
                            $this->creditWalletFromTransaction($transaction);
                        } else {
                            // É transação de assinatura
                            if (!$transaction->subscription_id) {
                                $this->createSubscriptionFromTransaction($transaction);
                                $transaction->refresh(); // Atualiza para pegar o subscription_id
                            }
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

                    // Se foi pago, verifica se é transação de wallet ou assinatura
                    if ($newStatus === 'paid_out') {
                        // Se não tem subscription_plan_id nem creator_id, é transação de wallet
                        if (!$transaction->subscription_plan_id && !$transaction->creator_id) {
                            $this->creditWalletFromTransaction($transaction);
                        } else {
                            // É transação de assinatura
                            if (!$transaction->subscription_id) {
                                $this->createSubscriptionFromTransaction($transaction);
                                $transaction->refresh(); // Atualiza para pegar o subscription_id
                            }
                        }
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
    private function createSubscriptionFromTransaction(PaymentTransaction $transaction)
    {
        try {
            $user = $transaction->user;
            $plan = $transaction->plan;
            $creator = $transaction->creator;

            // Verifica se já existe assinatura ativa (proteção)
            if ($user->hasActiveSubscription($creator->id)) {
                Log::warning('ASSINATURA JÁ EXISTE PARA ESTA TRANSAÇÃO', [
                    'transactionId' => $transaction->id,
                    'userId' => $user->id,
                    'creatorId' => $creator->id,
                ]);
                return;
            }

            // Obtém porcentagem da plataforma
            $platformPercentage = PlatformSetting::getPlatformPercentage();

            // Calcula valores base
            $totalAmount = $transaction->amount;
            $platformAmount = ($totalAmount * $platformPercentage) / 100;
            $creatorAmount = $totalAmount - $platformAmount;

            // Verifica se há indicação válida para este usuário
            $referral = \App\Models\Referral::where('referred_user_id', $user->id)->first();
            $referrerAmount = 0;

            // Se há indicação válida, verifica limite e calcula comissão do indicador
            if ($referral) {
                $affiliateCommissionLimit = PlatformSetting::getAffiliateCommissionLimit();

                $canReceiveCommission = true;
                if ($affiliateCommissionLimit > 0) {
                    $existingCommissionsCount = Subscription::where('user_id', $user->id)
                        ->where('referrer_amount', '>', 0)
                        ->count();

                    if ($existingCommissionsCount >= $affiliateCommissionLimit) {
                        $canReceiveCommission = false;
                    }
                }

                if ($canReceiveCommission) {
                    $affiliateCommissionPercentage = PlatformSetting::getAffiliateCommissionPercentage();
                    $referrerAmount = ($totalAmount * $affiliateCommissionPercentage) / 100;
                    $platformAmount = $platformAmount - $referrerAmount;
                }
            }

            // Verifica se o criador foi indicado por um afiliado e calcula comissão sobre a venda
            $creatorAffiliateAmount = 0;
            $creatorAffiliateUserId = null;
            
            $creatorReferral = \App\Models\Referral::where('referred_user_id', $creator->id)->first();
            if ($creatorReferral) {
                // Verifica se o afiliado ainda existe e está ativo
                $affiliate = \App\Models\User::find($creatorReferral->referrer_user_id);
                if ($affiliate) {
                    // Calcula comissão do afiliado sobre a venda do criador
                    $affiliateCommissionPercentage = PlatformSetting::getAffiliateCommissionPercentage();
                    $creatorAffiliateAmount = ($totalAmount * $affiliateCommissionPercentage) / 100;
                    $creatorAffiliateUserId = $affiliate->id;
                    
                    // Ajusta o valor da plataforma (desconta a comissão do afiliado sobre a venda do criador)
                    $platformAmount = $platformAmount - $creatorAffiliateAmount;
                }
            }

            // Calcula datas
            $startDate = now()->toDateString();
            $endDate = now()->addDays($plan->duration_days)->toDateString();

            // Desativa qualquer assinatura anterior expirada (limpeza)
            Subscription::where('user_id', $user->id)
                ->where('creator_id', $creator->id)
                ->where('is_active', true)
                ->where('end_date', '<', now()->toDateString())
                ->update(['is_active' => false]);

            // Cria a assinatura
            $subscription = Subscription::create([
                'user_id' => $user->id,
                'creator_id' => $creator->id,
                'subscription_plan_id' => $plan->id,
                'total_amount' => $totalAmount,
                'platform_percentage' => $platformPercentage,
                'platform_amount' => $platformAmount,
                'referrer_amount' => $referrerAmount,
                'creator_affiliate_amount' => $creatorAffiliateAmount,
                'creator_affiliate_user_id' => $creatorAffiliateUserId,
                'creator_amount' => $creatorAmount,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_active' => true,
                'payment_method' => $transaction->type, // 'pix' ou 'card'
            ]);

            // Atualiza a transação com o ID da assinatura
            $transaction->update([
                'subscription_id' => $subscription->id,
            ]);

            Log::info('ASSINATURA CRIADA VIA WEBHOOK PIX', [
                'transactionId' => $transaction->id,
                'subscriptionId' => $subscription->id,
                'userId' => $user->id,
                'creatorId' => $creator->id,
            ]);

        } catch (\Exception $e) {
            Log::error('ERRO AO CRIAR ASSINATURA VIA WEBHOOK PIX', [
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
