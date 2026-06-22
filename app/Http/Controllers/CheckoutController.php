<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Models\PlatformSetting;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    /**
     * Verifica se o usuário tem dados completos antes de acessar o checkout
     */
    private function checkUserIdentification()
    {
        $user = Auth::user();
        
        if (!$user->hasCompleteIdentification()) {
            // Passa os parâmetros corretos para o formulário
            return redirect()->route('user-identification.create', [
                'plan_id' => request('planId') ?? request('plan_id'),
                'payment_method' => request('method'),
            ])->with('error', 'Por favor, complete seus dados de identificação antes de prosseguir.');
        }
        
        return null;
    }

    /**
     * Mostra a página de checkout
     */
    public function show(Request $request, $planId, $method)
    {
        $user = Auth::user();
        
        // Verifica dados de identificação
        $redirect = $this->checkUserIdentification();
        if ($redirect) {
            return $redirect;
        }

        $plan = SubscriptionPlan::with('user')->findOrFail($planId);
        $creator = $plan->user;
        
        // BLOQUEIO: Verifica se já tem assinatura ativa com este criador
        if ($user->hasActiveSubscription($creator->id)) {
            return redirect()->route('profile.show', $creator->username)
                ->with('info', 'Você já possui uma assinatura ativa com este criador.');
        }
        
        // Valida o método de pagamento
        if (!in_array($method, ['card', 'pix'])) {
            return redirect()->back()->with('error', 'Método de pagamento inválido.');
        }

        // Se for PIX, verifica se já existe transação pendente ou gera uma nova
        $transaction = null;
        if ($method === 'pix') {
            // Verifica se já existe transação pendente válida
            $transaction = PaymentTransaction::where('user_id', $user->id)
                ->where('subscription_plan_id', $planId)
                ->where('type', 'pix')
                ->where('status', 'pending')
                ->where('created_at', '>', now()->subHours(24)) // Válido por 24 horas
                ->first();

            // Se não existe transação válida, gera uma nova automaticamente
            if (!$transaction) {
                try {
                    $transaction = $this->generatePixTransaction($user, $plan, $creator);
                } catch (\Exception $e) {
                    // Se houver erro ao gerar, retorna a view sem transação
                    // O erro será exibido na view
                    \Log::error('Erro ao gerar transação PIX: ' . $e->getMessage());
                }
            }
        }

        return view('checkout.show', [
            'plan' => $plan,
            'creator' => $creator,
            'method' => $method,
            'transaction' => $transaction,
        ]);
    }

    /**
     * Processa o pagamento
     */
    public function process(Request $request, $planId, $method)
    {
        $user = Auth::user();
        
        // Verifica dados de identificação
        $redirect = $this->checkUserIdentification();
        if ($redirect) {
            return response()->json([
                'success' => false,
                'message' => 'Por favor, complete seus dados de identificação antes de prosseguir.',
                'redirect' => route('user-identification.create', [
                    'plan_id' => $planId,
                    'payment_method' => $method,
                ]),
            ], 400);
        }

        $plan = SubscriptionPlan::with('user')->findOrFail($planId);
        $creator = $plan->user;
        
        // BLOQUEIO: Verifica se já tem assinatura ativa com este criador
        if ($user->hasActiveSubscription($creator->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Você já possui uma assinatura ativa com este criador.',
            ], 400);
        }
        
        // Valida o método de pagamento
        if (!in_array($method, ['card', 'pix'])) {
            return response()->json([
                'success' => false,
                'message' => 'Método de pagamento inválido.',
            ], 400);
        }

        // BLOQUEIO FINAL: Verifica novamente se já tem assinatura ativa (dupla verificação)
        if ($user->hasActiveSubscription($creator->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Você já possui uma assinatura ativa com este criador.',
            ], 400);
        }

        // Se for PIX, processa via SuitPay
        if ($method === 'pix') {
            return $this->processPixPayment($user, $plan, $creator);
        }

        // Se for CARTÃO, processa via SuitPay
        if ($method === 'card') {
            return $this->processCardPayment($user, $plan, $creator, $request);
        }
        
        // Obtém porcentagem da plataforma
        $platformPercentage = PlatformSetting::getPlatformPercentage();
        
        // Calcula valores base
        $totalAmount = $plan->price;
        $platformAmount = ($totalAmount * $platformPercentage) / 100;
        $creatorAmount = $totalAmount - $platformAmount;
        
        // Verifica se há indicação válida para este usuário
        $referral = \App\Models\Referral::where('referred_user_id', $user->id)->first();
        $referrerAmount = 0;
        
        // Se há indicação válida, verifica limite e calcula comissão do indicador
        if ($referral) {
            $affiliateCommissionLimit = PlatformSetting::getAffiliateCommissionLimit();
            
            // Se há limite configurado (diferente de 0), verifica se já atingiu
            $canReceiveCommission = true;
            if ($affiliateCommissionLimit > 0) {
                // Conta quantas comissões já foram geradas para este par afiliado/indicado
                // Como o usuário foi indicado por um afiliado específico, todas as assinaturas
                // deste usuário com referrer_amount > 0 são comissões para aquele afiliado
                $existingCommissionsCount = \App\Models\Subscription::where('user_id', $user->id)
                    ->where('referrer_amount', '>', 0)
                    ->count();
                
                // Se já atingiu o limite, não gera nova comissão
                if ($existingCommissionsCount >= $affiliateCommissionLimit) {
                    $canReceiveCommission = false;
                }
            }
            
            // Se pode receber comissão, calcula usando porcentagem configurada
            if ($canReceiveCommission) {
                $affiliateCommissionPercentage = PlatformSetting::getAffiliateCommissionPercentage();
                $referrerAmount = ($totalAmount * $affiliateCommissionPercentage) / 100;
                // Ajusta o valor da plataforma (desconta a comissão do afiliado)
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
            'payment_method' => $method,
        ]);
        
        // Redireciona para tela de confirmação
        return response()->json([
            'success' => true,
            'message' => 'Pagamento processado com sucesso!',
            'redirect' => route('checkout.success', ['subscriptionId' => $subscription->id]),
        ]);
    }

    /**
     * Gera transação PIX (método reutilizável)
     */
    private function generatePixTransaction(User $user, SubscriptionPlan $plan, User $creator)
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
            $plan->price,
            $requestNumber,
            $clientData,
            "Assinatura: {$plan->name} - {$creator->name}",
            $webhookUrl
        );

        // Verifica se a requisição foi bem-sucedida
        if (!$suitPayResponse['success'] || $suitPayResponse['status'] !== 200) {
            throw new \Exception($suitPayResponse['data']['msg'] ?? 'Erro ao gerar QR Code PIX.');
        }

        $suitPayData = $suitPayResponse['data'];
        
        // Monta nota com informações da resposta
        $note = $this->buildTransactionNote($suitPayData, $suitPayData['response'] ?? null, $suitPayData['statusTransaction'] ?? null);
        if (empty($note)) {
            $note = 'QR Code PIX gerado com sucesso. Aguardando pagamento.';
        }

        // Cria transação de pagamento
        $transaction = PaymentTransaction::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'creator_id' => $creator->id,
            'request_number' => $requestNumber,
            'transaction_id' => $suitPayData['idTransaction'] ?? null,
            'type' => 'pix',
            'status' => 'pending',
            'amount' => $plan->price,
            'payment_code' => $suitPayData['paymentCode'] ?? null,
            'payment_code_base64' => $suitPayData['paymentCodeBase64'] ?? null,
            'response_data' => $suitPayData,
            'note' => $note,
        ]);

        return $transaction;
    }

    /**
     * Processa pagamento PIX via SuitPay (mantido para compatibilidade com requisições AJAX)
     */
    private function processPixPayment(User $user, SubscriptionPlan $plan, User $creator)
    {
        // Verifica se já existe transação pendente válida
        $existingTransaction = PaymentTransaction::where('user_id', $user->id)
            ->where('subscription_plan_id', $plan->id)
            ->where('type', 'pix')
            ->where('status', 'pending')
            ->where('created_at', '>', now()->subHours(24))
            ->first();

        if ($existingTransaction && $existingTransaction->payment_code_base64) {
            // Retorna transação existente
            return response()->json([
                'success' => true,
                'message' => 'QR Code PIX gerado com sucesso!',
                'transaction' => [
                    'id' => $existingTransaction->id,
                    'payment_code' => $existingTransaction->payment_code,
                    'payment_code_base64' => $existingTransaction->payment_code_base64,
                    'transaction_id' => $existingTransaction->transaction_id,
                ],
            ]);
        }

        try {
            $transaction = $this->generatePixTransaction($user, $plan, $creator);

            return response()->json([
                'success' => true,
                'message' => 'QR Code PIX gerado com sucesso!',
                'transaction' => [
                    'id' => $transaction->id,
                    'payment_code' => $transaction->payment_code,
                    'payment_code_base64' => $transaction->payment_code_base64,
                    'transaction_id' => $transaction->transaction_id,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Verifica o status de uma transação de pagamento (para polling)
     * IMPORTANTE: Se o status mudou para paid_out mas ainda não tem subscription_id,
     * tenta criar a assinatura (caso o webhook não tenha processado ainda)
     */
    public function checkTransactionStatus($transactionId)
    {
        $user = Auth::user();
        
        $transaction = PaymentTransaction::with(['plan', 'creator'])->findOrFail($transactionId);
        
        // Verifica se a transação pertence ao usuário
        if ($transaction->user_id !== $user->id) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 403);
        }
        
        // Se o status é paid_out mas não tem subscription_id, tenta criar a assinatura
        // Isso garante que mesmo se o webhook não processar, o polling cria a assinatura
        if ($transaction->status === 'paid_out' && !$transaction->subscription_id) {
            // Verifica se já existe assinatura ativa (evita duplicação)
            if (!$user->hasActiveSubscription($transaction->creator_id)) {
                try {
                    $subscription = $this->createSubscriptionFromTransaction($transaction);
                    if ($subscription) {
                        $transaction->refresh();
                    }
                } catch (\Exception $e) {
                    \Log::error('Erro ao criar assinatura via polling', [
                        'transactionId' => $transaction->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                // Se já tem assinatura ativa, busca o ID para retornar
                $activeSubscription = $user->getActiveSubscription($transaction->creator_id);
                if ($activeSubscription) {
                    $transaction->update(['subscription_id' => $activeSubscription->id]);
                    $transaction->refresh();
                }
            }
        }
        
        return response()->json([
            'status' => $transaction->status,
            'subscription_id' => $transaction->subscription_id,
        ]);
    }

    /**
     * Processa pagamento com cartão via SuitPay
     */
    private function processCardPayment(User $user, SubscriptionPlan $plan, User $creator, Request $request)
    {
        // Valida dados do cartão
        $validated = $request->validate([
            'card_number' => 'required|string',
            'card_expiry' => 'required|string',
            'card_cvv' => 'required|string',
            'card_name' => 'required|string|max:255',
        ]);

        // Remove espaços e formatações do número do cartão
        $cardNumber = preg_replace('/\s+/', '', $validated['card_number']);
        
        // Processa validade (MM/AA)
        $expiryParts = explode('/', $validated['card_expiry']);
        if (count($expiryParts) !== 2) {
            return response()->json([
                'success' => false,
                'message' => 'Formato de validade inválido. Use MM/AA.',
            ], 400);
        }
        
        $expirationMonth = str_pad($expiryParts[0], 2, '0', STR_PAD_LEFT);
        $expirationYear = '20' . $expiryParts[1]; // Assume século 21

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
        $cardData = [
            'number' => $cardNumber,
            'expirationMonth' => $expirationMonth,
            'expirationYear' => $expirationYear,
            'cvv' => $validated['card_cvv'],
            'installment' => 1,
        ];

        // Gera UUID único para a requisição
        $requestNumber = (string) Str::uuid();

        // URL do webhook
        $webhookUrl = route('api.webhook.suitpay');

        // Chama SuitPay
        $suitPayController = new \App\Http\Controllers\SuitPayController();
        
        Log::info('CHECKOUT CARD - INICIANDO PROCESSAMENTO', [
            'userId' => $user->id,
            'planId' => $plan->id,
            'creatorId' => $creator->id,
            'amount' => $plan->price,
            'requestNumber' => $requestNumber,
        ]);
        
        try {
            $suitPayResponse = $suitPayController->card(
                $plan->price,
                $requestNumber,
                $cardData,
                $clientData,
                "Assinatura: {$plan->name} - {$creator->name}",
                $webhookUrl
            );

            Log::info('CHECKOUT CARD - RESPOSTA SUITPAY', [
                'requestNumber' => $requestNumber,
                'success' => $suitPayResponse['success'],
                'http_status' => $suitPayResponse['status'],
                'response_data' => $suitPayResponse['data'],
            ]);

            // Verifica se a requisição foi bem-sucedida
            if (!$suitPayResponse['success'] || $suitPayResponse['status'] !== 200) {
                $suitPayData = $suitPayResponse['data'] ?? [];
                $errorMessage = $suitPayData['msg'] ?? 'Erro desconhecido';
                $statusTransaction = $suitPayData['statusTransaction'] ?? null;
                $responseCode = $suitPayData['response'] ?? null;
                
                // Mapeia status mesmo em caso de erro
                $dbStatus = 'pending';
                $note = $errorMessage;
                
                if ($statusTransaction) {
                    switch ($statusTransaction) {
                        case 'PAID_OUT':
                            $dbStatus = 'paid_out';
                            break;
                        case 'PAYMENT_ACCEPT':
                            $dbStatus = 'waiting_for_approval';
                            break;
                        case 'UNPAID':
                            $dbStatus = 'unpaid';
                            break;
                        case 'CANCELED':
                            $dbStatus = 'canceled';
                            break;
                    }
                }
                
                // Monta nota detalhada
                $note = $this->buildTransactionNote($suitPayData, $responseCode, $statusTransaction);
                
                // Cria transação mesmo em caso de erro para ter histórico
                try {
                    PaymentTransaction::create([
                        'user_id' => $user->id,
                        'subscription_plan_id' => $plan->id,
                        'creator_id' => $creator->id,
                        'request_number' => $requestNumber,
                        'transaction_id' => $suitPayData['idTransaction'] ?? $suitPayData['transactionId'] ?? null,
                        'type' => 'card',
                        'status' => $dbStatus,
                        'amount' => $plan->price,
                        'response_data' => $suitPayData,
                        'note' => $note,
                    ]);
                } catch (\Exception $e) {
                    Log::error('CHECKOUT CARD - ERRO AO SALVAR TRANSAÇÃO COM ERRO', [
                        'requestNumber' => $requestNumber,
                        'error' => $e->getMessage(),
                    ]);
                }
                
                Log::error('CHECKOUT CARD - ERRO NA RESPOSTA SUITPAY', [
                    'requestNumber' => $requestNumber,
                    'http_status' => $suitPayResponse['status'],
                    'error_message' => $errorMessage,
                    'statusTransaction' => $statusTransaction,
                    'responseCode' => $responseCode,
                    'full_response' => $suitPayResponse,
                ]);
                
                // Mensagem mais clara para o usuário
                $userMessage = $this->getUserFriendlyMessage($statusTransaction, $responseCode, $errorMessage);
                
                return response()->json([
                    'success' => false,
                    'message' => $userMessage,
                    'error' => $errorMessage,
                    'status' => $dbStatus,
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('CHECKOUT CARD - EXCEÇÃO AO CHAMAR SUITPAY', [
                'requestNumber' => $requestNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar pagamento. Tente novamente.',
                'error' => $e->getMessage(),
            ], 500);
        }

        $suitPayData = $suitPayResponse['data'];
        
        // Log detalhado da resposta
        Log::info('CHECKOUT CARD - DADOS PROCESSADOS', [
            'requestNumber' => $requestNumber,
            'suitPayData' => $suitPayData,
            'hasStatusTransaction' => isset($suitPayData['statusTransaction']),
            'hasTransactionId' => isset($suitPayData['transactionId']) || isset($suitPayData['idTransaction']),
        ]);
        
        $statusTransaction = $suitPayData['statusTransaction'] ?? null;
        $transactionId = $suitPayData['transactionId'] ?? $suitPayData['idTransaction'] ?? null;

        // Mapeia status do SuitPay para status do banco
        $dbStatus = 'pending';
        switch ($statusTransaction) {
            case 'PAID_OUT':
                $dbStatus = 'paid_out';
                break;
            case 'PAYMENT_ACCEPT':
                $dbStatus = 'waiting_for_approval';
                break;
            case 'UNPAID':
                $dbStatus = 'unpaid';
                break;
            case 'CANCELED':
                $dbStatus = 'canceled';
                break;
            default:
                // Se não tem statusTransaction, mantém como pending
                Log::warning('CHECKOUT CARD - STATUS NÃO MAPEADO', [
                    'requestNumber' => $requestNumber,
                    'statusTransaction' => $statusTransaction,
                    'suitPayData' => $suitPayData,
                ]);
                $dbStatus = 'pending';
                break;
        }
        
        Log::info('CHECKOUT CARD - STATUS MAPEADO', [
            'requestNumber' => $requestNumber,
            'statusTransaction' => $statusTransaction,
            'dbStatus' => $dbStatus,
        ]);

        // Monta nota com informações da resposta
        $responseCode = $suitPayData['response'] ?? null;
        $note = $this->buildTransactionNote($suitPayData, $responseCode, $statusTransaction);
        
        // Cria transação de pagamento
        try {
            $transaction = PaymentTransaction::create([
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'creator_id' => $creator->id,
                'request_number' => $requestNumber,
                'transaction_id' => $transactionId,
                'type' => 'card',
                'status' => $dbStatus,
                'amount' => $plan->price,
                'response_data' => $suitPayData,
                'note' => $note,
            ]);
            
            Log::info('CHECKOUT CARD - TRANSAÇÃO CRIADA', [
                'transactionId' => $transaction->id,
                'requestNumber' => $requestNumber,
                'status' => $dbStatus,
                'note' => $note,
            ]);
        } catch (\Exception $e) {
            Log::error('CHECKOUT CARD - ERRO AO CRIAR TRANSAÇÃO', [
                'requestNumber' => $requestNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao salvar transação. Tente novamente.',
                'error' => $e->getMessage(),
            ], 500);
        }

        // Se foi pago imediatamente (PAID_OUT), cria a assinatura
        if ($dbStatus === 'paid_out') {
            try {
                $subscription = $this->createSubscriptionFromTransaction($transaction);
                if ($subscription) {
                    $transaction->refresh();
                }
            } catch (\Exception $e) {
                \Log::error('Erro ao criar assinatura após pagamento com cartão', [
                    'transactionId' => $transaction->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Retorna resposta baseada no status
        if ($dbStatus === 'paid_out' && $transaction->subscription_id) {
            // Pagamento aprovado imediatamente
            return response()->json([
                'success' => true,
                'message' => 'Pagamento aprovado com sucesso!',
                'status' => $dbStatus,
                'redirect' => route('checkout.success', ['subscriptionId' => $transaction->subscription_id]),
            ]);
        } elseif ($dbStatus === 'waiting_for_approval') {
            // Pagamento em análise
            return response()->json([
                'success' => true,
                'message' => 'Pagamento em análise. Você será notificado quando for confirmado.',
                'status' => $dbStatus,
                'transaction_id' => $transaction->id,
            ]);
        } elseif ($dbStatus === 'unpaid') {
            // Pagamento recusado
            $userMessage = $this->getUserFriendlyMessage('UNPAID', $suitPayData['response'] ?? null, $suitPayData['msg'] ?? null);
            
            return response()->json([
                'success' => false,
                'message' => $userMessage,
                'status' => $dbStatus,
                'details' => $suitPayData['acquirerMessage'] ?? null,
            ], 402);
        } elseif ($dbStatus === 'canceled') {
            // Pagamento cancelado
            $userMessage = $this->getUserFriendlyMessage('CANCELED', $suitPayData['response'] ?? null, $suitPayData['msg'] ?? null);
            
            return response()->json([
                'success' => false,
                'message' => $userMessage,
                'status' => $dbStatus,
            ], 400);
        } else {
            // Outros status
            $userMessage = $this->getUserFriendlyMessage($statusTransaction, $suitPayData['response'] ?? null, $suitPayData['msg'] ?? null);
            
            return response()->json([
                'success' => false,
                'message' => $userMessage,
                'status' => $dbStatus,
            ], 400);
        }
    }

    /**
     * Constrói nota detalhada da transação
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
        
        if (isset($suitPayData['acquirerMessage']) && !empty($suitPayData['acquirerMessage'])) {
            $parts[] = "Adquirente: {$suitPayData['acquirerMessage']}";
        }
        
        return !empty($parts) ? implode(' | ', $parts) : 'Sem informações adicionais';
    }

    /**
     * Retorna mensagem amigável para o usuário baseada no status e código de resposta
     */
    private function getUserFriendlyMessage(?string $statusTransaction, ?string $responseCode, ?string $msg): string
    {
        // Mensagens específicas por código de resposta
        if ($responseCode === 'CARD_PREVIOUSLY_REJECTED') {
            return 'Este cartão foi rejeitado recentemente. Por favor, aguarde alguns minutos antes de tentar novamente ou utilize outro cartão.';
        }
        
        if ($responseCode === 'INSUFFICIENT_FUNDS' || $responseCode === 'INSUFFICIENT_FUNDS_ERROR') {
            return 'Saldo insuficiente no cartão. Verifique o limite disponível e tente novamente.';
        }
        
        if ($responseCode === 'INVALID_CARD' || $responseCode === 'INVALID_CARD_NUMBER') {
            return 'Número do cartão inválido. Verifique os dados e tente novamente.';
        }
        
        if ($responseCode === 'EXPIRED_CARD') {
            return 'Cartão expirado. Verifique a data de validade e utilize outro cartão.';
        }
        
        if ($responseCode === 'CARD_BLOCKED' || $responseCode === 'BLOCKED_CARD') {
            return 'Cartão bloqueado. Entre em contato com seu banco ou utilize outro cartão.';
        }
        
        // Mensagens por status
        if ($statusTransaction === 'UNPAID') {
            return $msg ?? 'Pagamento recusado. Verifique os dados do cartão e tente novamente.';
        }
        
        if ($statusTransaction === 'CANCELED') {
            return $msg ?? 'Pagamento cancelado. Tente novamente ou utilize outro método de pagamento.';
        }
        
        // Mensagem padrão com a mensagem da SuitPay se disponível
        if ($msg) {
            return $msg;
        }
        
        return 'Erro ao processar pagamento. Por favor, verifique os dados e tente novamente.';
    }

    /**
     * Cria assinatura a partir de uma transação (método reutilizável)
     */
    private function createSubscriptionFromTransaction(PaymentTransaction $transaction)
    {
        $user = $transaction->user;
        $plan = $transaction->plan;
        $creator = $transaction->creator;

        // Verifica se já existe assinatura ativa (proteção)
        if ($user->hasActiveSubscription($creator->id)) {
            $activeSubscription = $user->getActiveSubscription($creator->id);
            return $activeSubscription;
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

        \Log::info('ASSINATURA CRIADA', [
            'transactionId' => $transaction->id,
            'subscriptionId' => $subscription->id,
            'userId' => $user->id,
            'creatorId' => $creator->id,
        ]);

        return $subscription;
    }

    /**
     * Tela de confirmação de pagamento
     */
    public function success($subscriptionId)
    {
        $user = Auth::user();
        $subscription = Subscription::with(['creator', 'plan'])->findOrFail($subscriptionId);
        
        // Verifica se a assinatura pertence ao usuário
        if ($subscription->user_id !== $user->id) {
            return redirect()->route('dashboard')->with('error', 'Acesso negado.');
        }
        
        return view('checkout.success', [
            'subscription' => $subscription,
            'creator' => $subscription->creator,
        ]);
    }
}
