<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WalletController extends Controller
{
    /**
     * Verifica se o usuário tem dados completos antes de adicionar saldo
     */
    private function checkUserIdentification()
    {
        $user = Auth::user();
        
        if (!$user->hasCompleteIdentification()) {
            // Armazena o valor na sessão para usar após preencher os dados
            $amount = request('amount');
            if ($amount) {
                session(['wallet_deposit_amount' => $amount]);
            }
            
            return redirect()->route('user-identification.create', [
                'wallet' => true,
            ])->with('error', 'Por favor, complete seus dados de identificação antes de adicionar saldo.');
        }
        
        return null;
    }

    /**
     * Exibe a tela de carteira com saldo e extrato
     */
    public function index()
    {
        $user = Auth::user();
        
        // Obtém ou cria a carteira do usuário
        $wallet = $user->getOrCreateWallet();
        
        // Busca transações da carteira (entradas e saídas)
        $transactions = $wallet->transactions()
            ->with('adminUser')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('wallet.index', [
            'user' => $user,
            'wallet' => $wallet,
            'transactions' => $transactions,
        ]);
    }

    /**
     * Mostra a página de checkout para adicionar saldo
     */
    public function showAddBalance(Request $request, $method)
    {
        $user = Auth::user();
        
        // Verifica dados de identificação
        $redirect = $this->checkUserIdentification();
        if ($redirect) {
            return $redirect;
        }

        // Valida o método de pagamento
        if (!in_array($method, ['card', 'pix'])) {
            return redirect()->route('wallet.index')
                ->with('error', 'Método de pagamento inválido.');
        }

        // Valida o valor (pode vir da query string ou da sessão)
        $amount = $request->input('amount') ?? $request->session()->get('amount');
        if (!$amount || $amount <= 0) {
            return redirect()->route('wallet.index')
                ->with('error', 'Valor inválido. Por favor, informe um valor válido.');
        }

        $amount = (float) $amount;
        
        // Valida valor mínimo
        if ($amount < 0.01) {
            return redirect()->route('wallet.index')
                ->with('error', 'O valor mínimo é R$ 0,01.');
        }

        // Se for PIX, verifica se já existe transação pendente ou gera uma nova
        $transaction = null;
        if ($method === 'pix') {
            // Verifica se já existe transação pendente válida para wallet
            $transaction = PaymentTransaction::where('user_id', $user->id)
                ->whereNull('subscription_plan_id') // Transações de wallet não têm plan_id
                ->where('type', 'pix')
                ->where('status', 'pending')
                ->where('amount', $amount)
                ->where('created_at', '>', now()->subHours(24)) // Válido por 24 horas
                ->first();

            // Se não existe transação válida, gera uma nova automaticamente
            if (!$transaction) {
                try {
                    $transaction = $this->generateWalletPixTransaction($user, $amount);
                } catch (\Exception $e) {
                    Log::error('Erro ao gerar transação PIX para wallet: ' . $e->getMessage());
                    return redirect()->route('wallet.index')
                        ->with('error', 'Erro ao gerar QR Code PIX. Tente novamente.');
                }
            }
        }

        return view('wallet.checkout', [
            'method' => $method,
            'amount' => $amount,
            'transaction' => $transaction,
        ]);
    }

    /**
     * Processa o pagamento para adicionar saldo
     */
    public function processAddBalance(Request $request, $method)
    {
        $user = Auth::user();
        
        // Verifica dados de identificação
        $redirect = $this->checkUserIdentification();
        if ($redirect) {
            // Armazena o valor na sessão antes de redirecionar
            $amount = $request->input('amount');
            if ($amount) {
                session(['wallet_deposit_amount' => $amount]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Por favor, complete seus dados de identificação antes de prosseguir.',
                'redirect' => route('user-identification.create', [
                    'wallet' => true,
                ]),
            ], 400);
        }

        // Valida o valor
        $amount = $request->input('amount');
        if (!$amount || $amount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Valor inválido.',
            ], 400);
        }

        $amount = (float) $amount;
        
        // Valida o método de pagamento
        if (!in_array($method, ['card', 'pix'])) {
            return response()->json([
                'success' => false,
                'message' => 'Método de pagamento inválido.',
            ], 400);
        }

        // Se for PIX, processa via SuitPay
        if ($method === 'pix') {
            return $this->processWalletPixPayment($user, $amount);
        }

        // Se for CARTÃO, processa via SuitPay
        if ($method === 'card') {
            return $this->processWalletCardPayment($user, $amount, $request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Método de pagamento não suportado.',
        ], 400);
    }

    /**
     * Gera transação PIX para adicionar saldo na carteira
     */
    private function generateWalletPixTransaction(User $user, float $amount)
    {
        // Obtém dados de identificação do usuário
        $identification = $user->identification;
        if (!$identification || !$identification->isComplete()) {
            throw new \Exception('Dados de identificação incompletos.');
        }

        // Prepara dados do cliente no formato SuitPay
        $clientData = [
            'name' => $identification->name,
            'document' => $identification->document,
            'phoneNumber' => $identification->phone_number,
            'email' => $user->email,
            'address' => [
                'codIbge' => $identification->cod_ibge,
                'street' => $identification->street,
                'number' => $identification->number,
                'complement' => $identification->complement ?? '',
                'zipCode' => $identification->zip_code,
                'neighborhood' => $identification->neighborhood,
                'city' => $identification->city,
                'state' => $identification->state,
            ],
        ];

        // Gera UUID único para a requisição
        $requestNumber = (string) Str::uuid();

        // URL do webhook
        $webhookUrl = route('api.webhook.suitpay');

        // Chama SuitPay
        $suitPayController = new \App\Http\Controllers\SuitPayController();
        $suitPayResponse = $suitPayController->pix(
            $amount,
            $requestNumber,
            $clientData,
            "Adicionar saldo na carteira - R$ " . number_format($amount, 2, ',', '.'),
            $webhookUrl
        );

        // Verifica se a requisição foi bem-sucedida
        if (!$suitPayResponse['success'] || $suitPayResponse['status'] !== 200) {
            throw new \Exception($suitPayResponse['data']['msg'] ?? 'Erro ao gerar QR Code PIX.');
        }

        $suitPayData = $suitPayResponse['data'];
        
        // Monta nota com informações da resposta
        $note = 'QR Code PIX gerado para adicionar saldo na carteira. Aguardando pagamento.';

        // Cria transação de pagamento (sem subscription_plan_id e creator_id para indicar que é wallet)
        $transaction = PaymentTransaction::create([
            'user_id' => $user->id,
            'subscription_plan_id' => null, // Wallet não tem plano
            'creator_id' => null, // Wallet não tem criador
            'request_number' => $requestNumber,
            'transaction_id' => $suitPayData['idTransaction'] ?? null,
            'type' => 'pix',
            'status' => 'pending',
            'amount' => $amount,
            'payment_code' => $suitPayData['paymentCode'] ?? null,
            'payment_code_base64' => $suitPayData['paymentCodeBase64'] ?? null,
            'response_data' => $suitPayData,
            'note' => $note,
        ]);

        return $transaction;
    }

    /**
     * Processa pagamento PIX para adicionar saldo na carteira
     */
    private function processWalletPixPayment(User $user, float $amount)
    {
        // Verifica se já existe transação pendente válida
        $transaction = PaymentTransaction::where('user_id', $user->id)
            ->whereNull('subscription_plan_id')
            ->where('type', 'pix')
            ->where('status', 'pending')
            ->where('amount', $amount)
            ->where('created_at', '>', now()->subHours(24))
            ->first();

        if ($transaction) {
            // Retorna dados da transação existente
            return response()->json([
                'success' => true,
                'message' => 'QR Code gerado com sucesso.',
                'payment_code' => $transaction->payment_code,
                'payment_code_base64' => $transaction->payment_code_base64,
                'transaction_id' => $transaction->id,
            ]);
        }

        // Gera nova transação
        try {
            $transaction = $this->generateWalletPixTransaction($user, $amount);
            
            return response()->json([
                'success' => true,
                'message' => 'QR Code gerado com sucesso.',
                'payment_code' => $transaction->payment_code,
                'payment_code_base64' => $transaction->payment_code_base64,
                'transaction_id' => $transaction->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao processar pagamento PIX para wallet: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar QR Code PIX. Tente novamente.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Processa pagamento com cartão para adicionar saldo na carteira
     */
    private function processWalletCardPayment(User $user, float $amount, Request $request)
    {
        // Valida dados do cartão
        $request->validate([
            'card_number' => 'required|string',
            'card_expiry' => 'required|string',
            'card_cvv' => 'required|string',
            'card_name' => 'required|string',
        ]);

        // Obtém dados de identificação do usuário
        $identification = $user->identification;
        if (!$identification || !$identification->isComplete()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados de identificação incompletos.',
            ], 400);
        }

        // Prepara dados do cliente no formato SuitPay
        $clientData = [
            'name' => $identification->name,
            'document' => $identification->document,
            'phoneNumber' => $identification->phone_number,
            'email' => $user->email,
            'address' => [
                'codIbge' => $identification->cod_ibge,
                'street' => $identification->street,
                'number' => $identification->number,
                'complement' => $identification->complement ?? '',
                'zipCode' => $identification->zip_code,
                'neighborhood' => $identification->neighborhood,
                'city' => $identification->city,
                'state' => $identification->state,
            ],
        ];

        // Prepara dados do cartão
        $cardExpiry = explode('/', $request->card_expiry);
        $cardData = [
            'cardNumber' => preg_replace('/\s+/', '', $request->card_number),
            'cardExpiryMonth' => str_pad($cardExpiry[0] ?? '', 2, '0', STR_PAD_LEFT),
            'cardExpiryYear' => '20' . ($cardExpiry[1] ?? ''),
            'cardCvv' => $request->card_cvv,
            'cardName' => $request->card_name,
        ];

        // Gera UUID único para a requisição
        $requestNumber = (string) Str::uuid();

        // URL do webhook
        $webhookUrl = route('api.webhook.suitpay');

        // Chama SuitPay
        $suitPayController = new \App\Http\Controllers\SuitPayController();
        $suitPayResponse = $suitPayController->card(
            $amount,
            $requestNumber,
            $cardData,
            $clientData,
            "Adicionar saldo na carteira - R$ " . number_format($amount, 2, ',', '.'),
            $webhookUrl
        );

        Log::info('SuitPay Card Response para Wallet', [
            'request_number' => $requestNumber,
            'amount' => $amount,
            'response' => $suitPayResponse,
        ]);

        // Mapeia status do SuitPay para status interno
        $statusTransaction = $suitPayResponse['data']['statusTransaction'] ?? null;
        $newStatus = 'pending';
        
        if ($statusTransaction === 'PAID_OUT') {
            $newStatus = 'paid_out';
        } elseif ($statusTransaction === 'UNPAID') {
            $newStatus = 'unpaid';
        } elseif ($statusTransaction === 'CANCELED') {
            $newStatus = 'canceled';
        } elseif ($statusTransaction === 'WAITING_FOR_APPROVAL') {
            $newStatus = 'waiting_for_approval';
        }

        // Monta nota detalhada
        $note = $this->buildTransactionNote(
            $suitPayResponse['data'] ?? [],
            $suitPayResponse['data']['response'] ?? null,
            $statusTransaction
        );

        // Cria transação de pagamento (mesmo em caso de erro, para registro)
        $transaction = PaymentTransaction::create([
            'user_id' => $user->id,
            'subscription_plan_id' => null,
            'creator_id' => null,
            'request_number' => $requestNumber,
            'transaction_id' => $suitPayResponse['data']['idTransaction'] ?? null,
            'type' => 'card',
            'status' => $newStatus,
            'amount' => $amount,
            'response_data' => $suitPayResponse['data'] ?? [],
            'note' => $note,
        ]);

        // Se o pagamento foi aprovado, credita na carteira
        if ($newStatus === 'paid_out') {
            $this->creditWalletBalance($user, $amount, $transaction);
            
            return response()->json([
                'success' => true,
                'message' => 'Saldo adicionado com sucesso!',
                'redirect' => route('wallet.index'),
            ]);
        }

        // Se está aguardando aprovação
        if ($newStatus === 'waiting_for_approval') {
            return response()->json([
                'success' => true,
                'message' => 'Pagamento em análise. Você será notificado quando for aprovado.',
                'status' => 'waiting_for_approval',
                'transaction_id' => $transaction->id,
            ]);
        }

        // Erro no pagamento
        $userMessage = $this->getUserFriendlyMessage(
            $statusTransaction,
            $suitPayResponse['data']['response'] ?? null,
            $suitPayResponse['data']['msg'] ?? null
        );

        return response()->json([
            'success' => false,
            'message' => $userMessage,
            'error' => 'Erro ao processar pagamento por cartão de crédito.',
        ], 400);
    }

    /**
     * Credita saldo na carteira do usuário
     */
    private function creditWalletBalance(User $user, float $amount, PaymentTransaction $transaction)
    {
        try {
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
            
            Log::info('Saldo creditado na carteira', [
                'user_id' => $user->id,
                'amount' => $amount,
                'transaction_id' => $transaction->id,
                'wallet_transaction_id' => $walletTransaction->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao creditar saldo na carteira', [
                'user_id' => $user->id,
                'amount' => $amount,
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Constrói nota detalhada da transação (reutilizado do CheckoutController)
     */
    private function buildTransactionNote(array $suitPayData, ?string $responseCode, ?string $statusTransaction): string
    {
        $parts = [];
        
        if ($statusTransaction) {
            $parts[] = "Status: {$statusTransaction}";
        }
        
        if ($responseCode) {
            $parts[] = "Código: {$responseCode}";
        }
        
        if (isset($suitPayData['msg']) && !empty($suitPayData['msg'])) {
            $parts[] = "Mensagem: {$suitPayData['msg']}";
        }
        
        return implode(' | ', $parts);
    }

    /**
     * Retorna mensagem amigável para o usuário (reutilizado do CheckoutController)
     */
    private function getUserFriendlyMessage(?string $statusTransaction, ?string $responseCode, ?string $msg): string
    {
        if ($statusTransaction === 'UNPAID') {
            if ($responseCode === '05') {
                return 'Cartão negado. Verifique os dados ou entre em contato com seu banco.';
            }
            if ($responseCode === '57') {
                return 'Transação não permitida para este cartão.';
            }
            if ($responseCode === '78') {
                return 'Cartão bloqueado. Entre em contato com seu banco.';
            }
            return $msg ?? 'Pagamento não aprovado. Verifique os dados do cartão.';
        }
        
        if ($statusTransaction === 'CANCELED') {
            return 'Pagamento cancelado.';
        }
        
        return $msg ?? 'Erro ao processar pagamento. Tente novamente.';
    }

    /**
     * Verifica o status de uma transação de wallet
     */
    public function checkTransactionStatus($transactionId)
    {
        $user = Auth::user();
        
        $transaction = PaymentTransaction::where('id', $transactionId)
            ->where('user_id', $user->id)
            ->whereNull('subscription_plan_id') // Apenas transações de wallet
            ->first();
        
        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transação não encontrada.',
            ], 404);
        }

        // Se o pagamento foi confirmado, credita na carteira
        if ($transaction->status === 'paid_out') {
            // Verifica se já foi creditado (evita duplicação)
            // TODO: Implementar verificação quando tiver tabela de wallet
            
            // Credita na carteira
            $this->creditWalletBalance($user, $transaction->amount, $transaction);
        }

        return response()->json([
            'success' => true,
            'status' => $transaction->status,
            'transaction_id' => $transaction->id,
        ]);
    }
}






