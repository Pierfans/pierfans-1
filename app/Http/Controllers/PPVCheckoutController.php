<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Models\PlatformSetting;
use App\Models\Post;
use App\Models\PostPurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PPVCheckoutController extends Controller
{
    /**
     * Exibe a tela de pagamento do Conteúdo Único.
     * PIX gera QR code automaticamente.
     */
    public function show(Post $post, string $method)
    {
        $this->applyGuards($post);

        if (!in_array($method, ['pix', 'card'])) {
            return redirect()->back()->with('error', 'Método de pagamento inválido.');
        }

        $user = Auth::user();
        $creator = $post->user;
        $transaction = null;

        if ($method === 'pix') {
            $transaction = PaymentTransaction::where('user_id', $user->id)
                ->where('post_id', $post->id)
                ->where('type', 'pix')
                ->where('status', 'pending')
                ->where('created_at', '>', now()->subHours(24))
                ->first();

            if (!$transaction) {
                try {
                    $transaction = $this->generatePixTransaction($user, $post, $creator);
                } catch (\Exception $e) {
                    Log::error('PPV: Erro ao gerar PIX', ['post_id' => $post->id, 'error' => $e->getMessage()]);
                }
            }
        }

        return view('ppv.show', compact('post', 'creator', 'method', 'transaction'));
    }

    /**
     * Processa o pagamento (chamada AJAX do frontend).
     */
    public function process(Post $post, string $method, Request $request)
    {
        $this->applyGuards($post);

        if (!in_array($method, ['pix', 'card'])) {
            return response()->json(['success' => false, 'message' => 'Método inválido.'], 400);
        }

        $user = Auth::user();
        $creator = $post->user;

        if ($method === 'pix') {
            return $this->processPixPayment($user, $post, $creator);
        }

        return $this->processCardPayment($user, $post, $creator, $request);
    }

    /**
     * Polling do frontend: retorna status da transação.
     * Se paid_out mas sem PostPurchase, cria (fallback caso webhook falhe).
     */
    public function checkStatus(string $transactionId)
    {
        $user = Auth::user();
        $transaction = PaymentTransaction::findOrFail($transactionId);

        if ($transaction->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($transaction->status === 'paid_out') {
            $purchase = PostPurchase::where('payment_transaction_id', $transaction->id)->first();

            if (!$purchase) {
                try {
                    $purchase = $this->createPostPurchaseFromTransaction($transaction);
                } catch (\Exception $e) {
                    Log::error('PPV: Erro ao criar compra via polling', [
                        'transaction_id' => $transaction->id,
                        'error'          => $e->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'status'      => $transaction->status,
                'purchase_id' => $purchase?->id,
            ]);
        }

        return response()->json(['status' => $transaction->status, 'purchase_id' => null]);
    }

    /**
     * Tela de sucesso após compra confirmada.
     */
    public function success(PostPurchase $purchase)
    {
        if ($purchase->user_id !== Auth::id()) {
            return redirect()->route('dashboard')->with('error', 'Acesso negado.');
        }

        $post    = $purchase->post->load('media');
        $creator = $purchase->creator;

        return view('ppv.success', compact('purchase', 'post', 'creator'));
    }

    // -------------------------------------------------------------------------
    // Guardas de segurança
    // -------------------------------------------------------------------------

    private function applyGuards(Post $post): void
    {
        abort_if($post->visibility !== 'paid', 404);
        abort_if($post->user_id === Auth::id(), 403);

        if ($post->isPurchasedBy(Auth::id())) {
            $purchase = $post->purchases()->where('user_id', Auth::id())->first();
            redirect()->route('ppv.success', $purchase)->send();
            exit;
        }
    }

    // -------------------------------------------------------------------------
    // PIX
    // -------------------------------------------------------------------------

    private function generatePixTransaction($user, Post $post, $creator): PaymentTransaction
    {
        $identification = $user->identification;
        if (!$identification || !$identification->isComplete()) {
            throw new \Exception('Dados de identificação incompletos.');
        }

        $clientData = [
            'name'        => $identification->name,
            'document'    => $identification->document,
            'phoneNumber' => $identification->phone_number,
            'email'       => $user->email,
            'address'     => [
                'codIbge'      => $identification->cod_ibge,
                'street'       => $identification->street,
                'number'       => $identification->number,
                'complement'   => $identification->complement ?? '',
                'zipCode'      => $identification->zip_code,
                'neighborhood' => $identification->neighborhood,
                'city'         => $identification->city,
                'state'        => $identification->state,
            ],
        ];

        $requestNumber = (string) Str::uuid();
        $webhookUrl    = route('api.webhook.suitpay');
        $suitPay       = new SuitPayController();

        $response = $suitPay->pix(
            $post->price,
            $requestNumber,
            $clientData,
            "Conteudo Unico: {$creator->name}",
            $webhookUrl
        );

        if (!$response['success'] || $response['status'] !== 200) {
            throw new \Exception($response['data']['msg'] ?? 'Erro ao gerar QR Code PIX.');
        }

        $data = $response['data'];

        return PaymentTransaction::create([
            'user_id'             => $user->id,
            'post_id'             => $post->id,
            'creator_id'          => $creator->id,
            'request_number'      => $requestNumber,
            'transaction_id'      => $data['idTransaction'] ?? null,
            'type'                => 'pix',
            'status'              => 'pending',
            'amount'              => $post->price,
            'payment_code'        => $data['paymentCode'] ?? null,
            'payment_code_base64' => $data['paymentCodeBase64'] ?? null,
            'response_data'       => $data,
            'note'                => 'PIX PPV gerado. Aguardando pagamento.',
        ]);
    }

    private function processPixPayment($user, Post $post, $creator)
    {
        $existing = PaymentTransaction::where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->where('type', 'pix')
            ->where('status', 'pending')
            ->where('created_at', '>', now()->subHours(24))
            ->first();

        if ($existing && $existing->payment_code_base64) {
            return response()->json([
                'success'     => true,
                'transaction' => [
                    'id'                  => $existing->id,
                    'payment_code'        => $existing->payment_code,
                    'payment_code_base64' => $existing->payment_code_base64,
                    'transaction_id'      => $existing->transaction_id,
                ],
            ]);
        }

        try {
            $transaction = $this->generatePixTransaction($user, $post, $creator);

            return response()->json([
                'success'     => true,
                'transaction' => [
                    'id'                  => $transaction->id,
                    'payment_code'        => $transaction->payment_code,
                    'payment_code_base64' => $transaction->payment_code_base64,
                    'transaction_id'      => $transaction->transaction_id,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    // -------------------------------------------------------------------------
    // Cartão
    // -------------------------------------------------------------------------

    private function processCardPayment($user, Post $post, $creator, Request $request)
    {
        $validated = $request->validate([
            'card_number' => 'required|string',
            'card_expiry' => 'required|string',
            'card_cvv'    => 'required|string',
            'card_name'   => 'required|string|max:255',
        ]);

        $cardNumber  = preg_replace('/\s+/', '', $validated['card_number']);
        $expiryParts = explode('/', $validated['card_expiry']);

        if (count($expiryParts) !== 2) {
            return response()->json(['success' => false, 'message' => 'Formato de validade inválido. Use MM/AA.'], 400);
        }

        $identification = $user->identification;
        if (!$identification || !$identification->isComplete()) {
            return response()->json(['success' => false, 'message' => 'Dados de identificação incompletos.'], 400);
        }

        $clientData = [
            'name'        => $identification->name,
            'document'    => $identification->document,
            'phoneNumber' => $identification->phone_number,
            'email'       => $user->email,
            'address'     => [
                'codIbge'      => $identification->cod_ibge,
                'street'       => $identification->street,
                'number'       => $identification->number,
                'complement'   => $identification->complement ?? '',
                'zipCode'      => $identification->zip_code,
                'neighborhood' => $identification->neighborhood,
                'city'         => $identification->city,
                'state'        => $identification->state,
            ],
        ];

        $cardData = [
            'number'          => $cardNumber,
            'expirationMonth' => str_pad($expiryParts[0], 2, '0', STR_PAD_LEFT),
            'expirationYear'  => '20' . $expiryParts[1],
            'cvv'             => $validated['card_cvv'],
            'installment'     => 1,
        ];

        $requestNumber = (string) Str::uuid();
        $webhookUrl    = route('api.webhook.suitpay');
        $suitPay       = new SuitPayController();

        try {
            $response = $suitPay->card(
                $post->price,
                $requestNumber,
                $cardData,
                $clientData,
                "Conteudo Unico: {$creator->name}",
                $webhookUrl
            );
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao processar pagamento.'], 500);
        }

        $data   = $response['data'] ?? [];
        $status = $data['statusTransaction'] ?? null;

        $dbStatus = match ($status) {
            'PAID_OUT'       => 'paid_out',
            'PAYMENT_ACCEPT' => 'waiting_for_approval',
            'UNPAID'         => 'unpaid',
            'CANCELED'       => 'canceled',
            default          => 'pending',
        };

        $transaction = PaymentTransaction::create([
            'user_id'        => $user->id,
            'post_id'        => $post->id,
            'creator_id'     => $creator->id,
            'request_number' => $requestNumber,
            'transaction_id' => $data['transactionId'] ?? $data['idTransaction'] ?? null,
            'type'           => 'card',
            'status'         => $dbStatus,
            'amount'         => $post->price,
            'response_data'  => $data,
            'note'           => "Status: {$status}",
        ]);

        if ($dbStatus === 'paid_out') {
            $purchase = $this->createPostPurchaseFromTransaction($transaction);
            return response()->json([
                'success'  => true,
                'message'  => 'Pagamento aprovado!',
                'redirect' => route('ppv.success', $purchase),
            ]);
        }

        if ($dbStatus === 'unpaid' || $dbStatus === 'canceled') {
            return response()->json(['success' => false, 'message' => $data['msg'] ?? 'Pagamento recusado.'], 402);
        }

        return response()->json([
            'success'        => true,
            'status'         => $dbStatus,
            'transaction_id' => $transaction->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // Criação da compra (chamado pelo webhook e pelo polling de fallback)
    // -------------------------------------------------------------------------

    public function createPostPurchaseFromTransaction(PaymentTransaction $transaction): PostPurchase
    {
        // Idempotência: não duplica se já existe
        $existing = PostPurchase::where('payment_transaction_id', $transaction->id)->first();
        if ($existing) {
            return $existing;
        }

        $platformPercentage = PlatformSetting::getPlatformPercentage();
        $platformAmount     = round($transaction->amount * $platformPercentage / 100, 2);
        $creatorAmount      = round($transaction->amount - $platformAmount, 2);

        $purchase = PostPurchase::create([
            'user_id'                => $transaction->user_id,
            'post_id'                => $transaction->post_id,
            'creator_id'             => $transaction->post->user_id,
            'payment_transaction_id' => $transaction->id,
            'amount_paid'            => $transaction->amount,
            'platform_percentage'    => $platformPercentage,
            'platform_amount'        => $platformAmount,
            'creator_amount'         => $creatorAmount,
            'purchased_at'           => now(),
        ]);

        Log::info('PPV: COMPRA CRIADA', [
            'purchase_id'    => $purchase->id,
            'user_id'        => $purchase->user_id,
            'post_id'        => $purchase->post_id,
            'transaction_id' => $transaction->id,
        ]);

        return $purchase;
    }
}
