# Conteúdo Único (PPV) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Adicionar uma terceira opção de visibilidade em posts ("Conteúdo Único") que permite ao criador cobrar um valor avulso por aquele conteúdo específico, independente de assinatura, com pagamento via SuitPay (PIX e cartão).

**Architecture:** Controller dedicado `PPVCheckoutController` paralelo ao `CheckoutController` existente, sem tocar no fluxo de assinaturas. Novo model `PostPurchase` registra compras. Webhook discrimina PPV via campo `post_id` em `PaymentTransaction`.

**Tech Stack:** Laravel 11, PHP, Blade, TailwindCSS, SuitPay API, MySQL

> **Nota:** `vendor/` não existe localmente — foi excluído do git (.gitignore). Todos os comandos `php artisan` devem ser executados via SSH no servidor: `ssh root@209.126.103.238 "cd /home/pierfans/web/pierfans.com/public_html && <comando>"`

---

## Mapa de Arquivos

| Ação | Arquivo |
|---|---|
| Criar | `database/migrations/2026_06_22_000001_add_price_to_posts_table.php` |
| Criar | `database/migrations/2026_06_22_000002_create_post_purchases_table.php` |
| Criar | `database/migrations/2026_06_22_000003_add_post_id_to_payment_transactions_table.php` |
| Criar | `app/Models/PostPurchase.php` |
| Modificar | `app/Models/Post.php` |
| Modificar | `app/Models/PaymentTransaction.php` |
| Modificar | `app/Http/Controllers/PostController.php` |
| Modificar | `routes/web.php` |
| Criar | `app/Http/Controllers/PPVCheckoutController.php` |
| Modificar | `app/Http/Controllers/SuitPayWebhookController.php` |
| Modificar | `resources/views/posts/create.blade.php` |
| Modificar | `resources/views/posts/edit.blade.php` |
| Modificar | `resources/views/components/post-card.blade.php` |
| Criar | `resources/views/ppv/show.blade.php` |
| Criar | `resources/views/ppv/success.blade.php` |

---

## Task 1: Migrations

**Files:**
- Create: `database/migrations/2026_06_22_000001_add_price_to_posts_table.php`
- Create: `database/migrations/2026_06_22_000002_create_post_purchases_table.php`
- Create: `database/migrations/2026_06_22_000003_add_post_id_to_payment_transactions_table.php`

- [ ] **Step 1: Criar migration — adiciona `price` e `paid` ao enum em `posts`**

```php
<?php
// database/migrations/2026_06_22_000001_add_price_to_posts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Altera o enum para incluir 'paid'
        DB::statement("ALTER TABLE posts MODIFY COLUMN visibility ENUM('free', 'subscriber', 'paid') NOT NULL DEFAULT 'free'");

        Schema::table('posts', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->nullable()->after('visibility');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('price');
        });
        DB::statement("ALTER TABLE posts MODIFY COLUMN visibility ENUM('free', 'subscriber') NOT NULL DEFAULT 'free'");
    }
};
```

- [ ] **Step 2: Criar migration — tabela `post_purchases`**

```php
<?php
// database/migrations/2026_06_22_000002_create_post_purchases_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('payment_transaction_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount_paid', 10, 2);
            $table->decimal('platform_percentage', 5, 2);
            $table->decimal('platform_amount', 10, 2);
            $table->decimal('creator_amount', 10, 2);
            $table->timestamp('purchased_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_purchases');
    }
};
```

- [ ] **Step 3: Criar migration — adiciona `post_id` em `payment_transactions`**

```php
<?php
// database/migrations/2026_06_22_000003_add_post_id_to_payment_transactions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->foreignId('post_id')->nullable()->after('creator_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropForeign(['post_id']);
            $table->dropColumn('post_id');
        });
    }
};
```

- [ ] **Step 4: Rodar as migrations no servidor**

```bash
ssh root@209.126.103.238 "cd /home/pierfans/web/pierfans.com/public_html && php artisan migrate"
```

Saída esperada:
```
  INFO  Running migrations.
  2026_06_22_000001_add_price_to_posts_table ............... 10ms DONE
  2026_06_22_000002_create_post_purchases_table ............ 15ms DONE
  2026_06_22_000003_add_post_id_to_payment_transactions .... 8ms  DONE
```

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_06_22_000001_add_price_to_posts_table.php
git add database/migrations/2026_06_22_000002_create_post_purchases_table.php
git add database/migrations/2026_06_22_000003_add_post_id_to_payment_transactions_table.php
git commit -m "feat: migrations para Conteudo Unico (PPV)"
```

---

## Task 2: Models

**Files:**
- Create: `app/Models/PostPurchase.php`
- Modify: `app/Models/Post.php`
- Modify: `app/Models/PaymentTransaction.php`

- [ ] **Step 1: Criar `PostPurchase.php`**

```php
<?php
// app/Models/PostPurchase.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostPurchase extends Model
{
    protected $fillable = [
        'user_id',
        'post_id',
        'creator_id',
        'payment_transaction_id',
        'amount_paid',
        'platform_percentage',
        'platform_amount',
        'creator_amount',
        'purchased_at',
    ];

    protected $casts = [
        'amount_paid'          => 'decimal:2',
        'platform_percentage'  => 'decimal:2',
        'platform_amount'      => 'decimal:2',
        'creator_amount'       => 'decimal:2',
        'purchased_at'         => 'datetime',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }
}
```

- [ ] **Step 2: Modificar `Post.php` — adicionar `price` ao `$fillable`, relacionamento e método `isPurchasedBy`**

Em `app/Models/Post.php`, localizar o array `$fillable` (linha 14) e adicionar `'price'`:

```php
protected $fillable = [
    'user_id',
    'description',
    'visibility',
    'price',
    'deleted_by_user_at',
    'featured_on_login',
    'featured_on_dashboard',
];
```

Adicionar o cast de `price` no array `$casts` (após `deleted_by_user_at`):

```php
protected $casts = [
    'deleted_by_user_at' => 'datetime',
    'price'              => 'decimal:2',
];
```

Adicionar o import no topo do arquivo (após os imports existentes):

```php
use Illuminate\Database\Eloquent\Relations\HasMany;
```

Adicionar os dois métodos antes do fechamento da classe (antes do `}`):

```php
public function purchases(): HasMany
{
    return $this->hasMany(PostPurchase::class);
}

public function isPurchasedBy(int $userId): bool
{
    return $this->purchases()->where('user_id', $userId)->exists();
}
```

- [ ] **Step 3: Modificar `PaymentTransaction.php` — adicionar `post_id` ao `$fillable`**

Em `app/Models/PaymentTransaction.php`, adicionar `'post_id'` ao array `$fillable` após `'creator_id'`:

```php
protected $fillable = [
    'user_id',
    'subscription_plan_id',
    'creator_id',
    'post_id',
    'request_number',
    'transaction_id',
    'type',
    'status',
    'amount',
    'payment_code',
    'payment_code_base64',
    'subscription_id',
    'response_data',
    'webhook_data',
    'note',
];
```

Adicionar o relacionamento com `Post` (após o método `subscription()`):

```php
public function post(): BelongsTo
{
    return $this->belongsTo(Post::class);
}
```

- [ ] **Step 4: Commit**

```bash
git add app/Models/PostPurchase.php app/Models/Post.php app/Models/PaymentTransaction.php
git commit -m "feat: models PostPurchase, Post e PaymentTransaction para PPV"
```

---

## Task 3: PostController — aceitar visibilidade `paid`

**Files:**
- Modify: `app/Http/Controllers/PostController.php`

- [ ] **Step 1: Atualizar validação em `store()` (linha ~75)**

Localizar a linha:
```php
'visibility' => ['required', Rule::in(['free', 'subscriber'])],
```

Substituir por:
```php
'visibility' => ['required', Rule::in(['free', 'subscriber', 'paid'])],
'price'      => 'required_if:visibility,paid|nullable|numeric|min:1|max:9999',
```

- [ ] **Step 2: Atualizar criação do post em `store()` para salvar/limpar `price`**

Localizar o bloco que cria o post em `store()` (procurar por `Post::create`). Adicionar `price` ao array de criação:

```php
$post = Post::create([
    'user_id'     => $user->id,
    'description' => $validated['description'] ?? null,
    'visibility'  => $validated['visibility'],
    'price'       => $validated['visibility'] === 'paid' ? $validated['price'] : null,
]);
```

- [ ] **Step 3: Atualizar validação em `update()` da mesma forma**

Localizar em `update()` a linha:
```php
'visibility' => ['required', Rule::in(['free', 'subscriber'])],
```

Substituir por:
```php
'visibility' => ['required', Rule::in(['free', 'subscriber', 'paid'])],
'price'      => 'required_if:visibility,paid|nullable|numeric|min:1|max:9999',
```

Localizar onde `$post->update(...)` é chamado em `update()` e adicionar `price`:

```php
$post->update([
    'description' => $validated['description'] ?? null,
    'visibility'  => $validated['visibility'],
    'price'       => $validated['visibility'] === 'paid' ? $validated['price'] : null,
]);
```

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/PostController.php
git commit -m "feat: PostController aceita visibilidade paid com price"
```

---

## Task 4: Rotas PPV

**Files:**
- Modify: `routes/web.php`

- [ ] **Step 1: Adicionar rotas PPV em `routes/web.php`**

Adicionar o bloco após o grupo `checkout` (após a linha que fecha o grupo de checkout, ~linha 95):

```php
// Rotas de Conteúdo Único (PPV)
Route::prefix('ppv')->name('ppv.')->group(function () {
    Route::get('/success/{purchase}',              [\App\Http\Controllers\PPVCheckoutController::class, 'success'])->name('success');
    Route::get('/transaction/{transactionId}/status', [\App\Http\Controllers\PPVCheckoutController::class, 'checkStatus'])->name('transaction.status');
    Route::get('/{post}/{method}',                 [\App\Http\Controllers\PPVCheckoutController::class, 'show'])->name('show');
    Route::post('/{post}/{method}',                [\App\Http\Controllers\PPVCheckoutController::class, 'process'])->name('process');
});
```

> **Atenção:** A rota `/success/{purchase}` e `/transaction/{transactionId}/status` devem vir ANTES de `/{post}/{method}` para evitar conflito de parâmetros (mesma lógica já usada em `checkout`).

- [ ] **Step 2: Commit**

```bash
git add routes/web.php
git commit -m "feat: rotas PPV /ppv/*"
```

---

## Task 5: PPVCheckoutController

**Files:**
- Create: `app/Http/Controllers/PPVCheckoutController.php`

- [ ] **Step 1: Criar o controller**

```php
<?php
// app/Http/Controllers/PPVCheckoutController.php

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

        $requestNumber  = (string) Str::uuid();
        $webhookUrl     = route('api.webhook.suitpay');
        $suitPay        = new SuitPayController();

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
            'user_id'          => $user->id,
            'post_id'          => $post->id,
            'creator_id'       => $creator->id,
            'request_number'   => $requestNumber,
            'transaction_id'   => $data['idTransaction'] ?? null,
            'type'             => 'pix',
            'status'           => 'pending',
            'amount'           => $post->price,
            'payment_code'     => $data['paymentCode'] ?? null,
            'payment_code_base64' => $data['paymentCodeBase64'] ?? null,
            'response_data'    => $data,
            'note'             => 'PIX PPV gerado. Aguardando pagamento.',
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

        $cardNumber    = preg_replace('/\s+/', '', $validated['card_number']);
        $expiryParts   = explode('/', $validated['card_expiry']);

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
            'PAID_OUT'          => 'paid_out',
            'PAYMENT_ACCEPT'    => 'waiting_for_approval',
            'UNPAID'            => 'unpaid',
            'CANCELED'          => 'canceled',
            default             => 'pending',
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
```

- [ ] **Step 2: Commit**

```bash
git add app/Http/Controllers/PPVCheckoutController.php
git commit -m "feat: PPVCheckoutController — checkout de Conteudo Unico"
```

---

## Task 6: SuitPayWebhookController — detectar PPV

**Files:**
- Modify: `app/Http/Controllers/SuitPayWebhookController.php`

- [ ] **Step 1: Adicionar import do PostPurchase no topo**

Localizar os `use` statements e adicionar:
```php
use App\Models\PostPurchase;
```

- [ ] **Step 2: Substituir o bloco `if ($newStatus === 'paid_out')` em `case 'PIX'`**

Localizar (dentro do `case 'PIX':`, após o update de status):
```php
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
```

Substituir por:
```php
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
```

- [ ] **Step 3: Substituir o mesmo bloco em `case 'CARD'`**

Localizar (dentro do `case 'CARD':`, mesmo padrão):
```php
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
```

Substituir por:
```php
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
```

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/SuitPayWebhookController.php
git commit -m "feat: webhook detecta PPV via post_id"
```

---

## Task 7: UI — Formulário criar/editar post

**Files:**
- Modify: `resources/views/posts/create.blade.php`
- Modify: `resources/views/posts/edit.blade.php`

- [ ] **Step 1: Adicionar opção e campo de preço em `create.blade.php`**

Localizar o select de visibilidade (linha ~61):
```html
<select
    id="visibility"
    name="visibility"
    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
>
    <option value="free">Gratuito</option>
    <option value="subscriber">Somente Assinantes</option>
</select>
```

Substituir por:
```html
<select
    id="visibility"
    name="visibility"
    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
>
    <option value="free">Gratuito</option>
    <option value="subscriber">Somente Assinantes</option>
    <option value="paid">Conteúdo Único (pago)</option>
</select>
<div id="visibility-error" class="text-red-500 text-sm mt-1 hidden"></div>

<!-- Campo de preço — visível apenas quando "Conteúdo Único" é selecionado -->
<div id="price-field" class="mt-4 hidden">
    <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
        Preço do conteúdo (R$)
    </label>
    <input
        type="number"
        id="price"
        name="price"
        min="1"
        max="9999"
        step="0.01"
        placeholder="Ex: 29.90"
        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
    >
    <div id="price-error" class="text-red-500 text-sm mt-1 hidden"></div>
</div>
```

Adicionar o script JS antes do fechamento do `</body>` (ou junto com os outros scripts no final do arquivo):

```html
<script>
    document.getElementById('visibility').addEventListener('change', function () {
        const priceField = document.getElementById('price-field');
        const priceInput = document.getElementById('price');

        if (this.value === 'paid') {
            priceField.classList.remove('hidden');
            priceInput.required = true;
        } else {
            priceField.classList.add('hidden');
            priceInput.required = false;
            priceInput.value = '';
        }
    });
</script>
```

- [ ] **Step 2: Adicionar opção e campo de preço em `edit.blade.php`**

Localizar o select de visibilidade no `edit.blade.php` e substituir pelo mesmo select acima.

Adicionar o campo de preço logo após o select (mesmo HTML acima).

Adicionar o script JS com inicialização ao carregar (mostra o campo se já é `paid`):

```html
<script>
    // Inicialização: mostra preço se já é Conteúdo Único
    (function () {
        const select = document.getElementById('visibility');
        const priceField = document.getElementById('price-field');
        const priceInput = document.getElementById('price');

        if (select.value === 'paid') {
            priceField.classList.remove('hidden');
        }

        select.addEventListener('change', function () {
            if (this.value === 'paid') {
                priceField.classList.remove('hidden');
                priceInput.required = true;
            } else {
                priceField.classList.add('hidden');
                priceInput.required = false;
                priceInput.value = '';
            }
        });
    })();
</script>
```

No campo de preço do `edit.blade.php`, pré-preencher o valor existente do post (diferente do create):

```html
<input
    type="number"
    id="price"
    name="price"
    min="1"
    max="9999"
    step="0.01"
    value="{{ old('price', $post->price) }}"
    placeholder="Ex: 29.90"
    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
>
```

- [ ] **Step 3: Commit**

```bash
git add resources/views/posts/create.blade.php resources/views/posts/edit.blade.php
git commit -m "feat: campo de preco no formulario de criar/editar post"
```

---

## Task 8: UI — post-card com estado PPV

**Files:**
- Modify: `resources/views/components/post-card.blade.php`

- [ ] **Step 1: Adicionar lógica de PPV no bloco `@php` do post-card**

Localizar o bloco `@php` no início do arquivo (linha ~3). Após o bloco de verificação de `subscriber` (após a linha `$isPostLocked = !$canViewPost;` do subscriber), adicionar:

```php
// Verificação para Conteúdo Único (PPV)
$isPPVPost = $post->visibility === 'paid';
$hasPurchased = false;

if ($isPPVPost) {
    if (Auth::check()) {
        if ($post->user_id === Auth::id()) {
            // Criador sempre vê o próprio conteúdo
            $canViewPost = true;
            $isPostLocked = false;
        } else {
            $hasPurchased = $post->isPurchasedBy(Auth::id());
            $canViewPost = $hasPurchased;
            $isPostLocked = !$hasPurchased;
        }
    } else {
        $canViewPost = false;
        $isPostLocked = true;
    }
}
```

- [ ] **Step 2: Adicionar o botão "Comprar" no HTML do card**

Localizar o bloco de lock existente (onde aparece o cadeado e o botão de assinar para posts `subscriber`). Após esse bloco, adicionar o estado PPV.

Procurar por algo como `@if($isPostLocked)` ou o bloco que renderiza o overlay de conteúdo bloqueado. Adicionar dentro desse bloco, após a lógica de assinatura:

```php
@if($isPostLocked && $isPPVPost)
    <div class="absolute inset-0 bg-black bg-opacity-60 flex flex-col items-center justify-center rounded-lg z-10">
        <svg class="w-12 h-12 text-green-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
        <p class="text-white font-semibold text-lg mb-1">Conteúdo Único</p>
        <p class="text-green-300 font-bold text-xl mb-4">
            R$ {{ number_format($post->price, 2, ',', '.') }}
        </p>
        @auth
            <a href="{{ route('ppv.show', [$post->id, 'pix']) }}"
               class="bg-green-500 hover:bg-green-600 text-white font-semibold px-6 py-3 rounded-full transition-colors">
                Comprar agora
            </a>
        @else
            <a href="{{ route('login') }}"
               class="bg-green-500 hover:bg-green-600 text-white font-semibold px-6 py-3 rounded-full transition-colors">
                Entrar para comprar
            </a>
        @endauth
    </div>
@endif
```

- [ ] **Step 3: Commit**

```bash
git add resources/views/components/post-card.blade.php
git commit -m "feat: post-card exibe botao de compra para Conteudo Unico"
```

---

## Task 9: Views PPV — tela de checkout e sucesso

**Files:**
- Create: `resources/views/ppv/show.blade.php`
- Create: `resources/views/ppv/success.blade.php`

- [ ] **Step 1: Criar diretório e a view `ppv/show.blade.php`**

```php
{{-- resources/views/ppv/show.blade.php --}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Comprar Conteúdo — {{ $creator->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <x-topnav />

    <div class="pt-16 pb-16 md:pb-0">
        <div class="max-w-lg mx-auto px-4 py-8">
            <div class="bg-white rounded-xl shadow-sm p-6">

                <!-- Cabeçalho -->
                <div class="flex items-center gap-4 mb-6 pb-6 border-b border-gray-100">
                    @if($creator->profile_photo)
                        <img src="{{ $creator->profile_photo }}" class="w-16 h-16 rounded-full object-cover">
                    @else
                        <div class="w-16 h-16 rounded-full bg-pink-100 flex items-center justify-center">
                            <span class="text-pink-500 text-2xl font-bold">{{ substr($creator->name, 0, 1) }}</span>
                        </div>
                    @endif
                    <div>
                        <h2 class="font-bold text-gray-900 text-lg">{{ $creator->name }}</h2>
                        <p class="text-gray-500 text-sm">Conteúdo Único</p>
                    </div>
                </div>

                <!-- Valor -->
                <div class="text-center mb-6">
                    <p class="text-gray-500 text-sm mb-1">Valor do conteúdo</p>
                    <p class="text-4xl font-bold text-green-600">
                        R$ {{ number_format($post->price, 2, ',', '.') }}
                    </p>
                    <p class="text-gray-400 text-xs mt-1">Acesso permanente após a compra</p>
                </div>

                <!-- Método: PIX -->
                @if($method === 'pix')
                    <div id="pix-section">
                        @if($transaction && $transaction->payment_code_base64)
                            <div class="text-center">
                                <img src="data:image/png;base64,{{ $transaction->payment_code_base64 }}"
                                     class="mx-auto w-48 h-48 mb-4">
                                <p class="text-sm text-gray-600 mb-2">Copie o código PIX:</p>
                                <div class="flex gap-2">
                                    <input type="text" value="{{ $transaction->payment_code }}"
                                           id="pix-code" readonly
                                           class="flex-1 text-xs border border-gray-300 rounded px-3 py-2 bg-gray-50">
                                    <button onclick="copyPix()"
                                            class="bg-green-500 text-white px-4 py-2 rounded text-sm hover:bg-green-600">
                                        Copiar
                                    </button>
                                </div>
                            </div>

                            <div id="status-msg" class="mt-4 text-center text-sm text-gray-500">
                                Aguardando pagamento...
                            </div>
                        @else
                            <p class="text-center text-red-500">Erro ao gerar QR Code. <a href="{{ url()->current() }}" class="underline">Tentar novamente</a></p>
                        @endif
                    </div>
                @endif

                <!-- Método: Cartão -->
                @if($method === 'card')
                    <form id="card-form" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Número do cartão</label>
                            <input type="text" name="card_number" placeholder="0000 0000 0000 0000"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-green-500">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Validade</label>
                                <input type="text" name="card_expiry" placeholder="MM/AA"
                                       class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-green-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">CVV</label>
                                <input type="text" name="card_cvv" placeholder="123"
                                       class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-green-500">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome no cartão</label>
                            <input type="text" name="card_name" placeholder="NOME SOBRENOME"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-green-500">
                        </div>
                        <div id="card-error" class="text-red-500 text-sm hidden"></div>
                        <button type="submit"
                                class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-3 rounded-lg transition-colors">
                            Pagar R$ {{ number_format($post->price, 2, ',', '.') }}
                        </button>
                    </form>
                @endif

                <!-- Trocar método -->
                <div class="mt-6 pt-4 border-t border-gray-100 text-center text-sm text-gray-500">
                    @if($method === 'pix')
                        Prefere cartão?
                        <a href="{{ route('ppv.show', [$post->id, 'card']) }}" class="text-green-600 font-medium">Pagar com cartão</a>
                    @else
                        Prefere PIX?
                        <a href="{{ route('ppv.show', [$post->id, 'pix']) }}" class="text-green-600 font-medium">Pagar com PIX</a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyPix() {
            const input = document.getElementById('pix-code');
            input.select();
            document.execCommand('copy');
            alert('Código copiado!');
        }

        @if($method === 'pix' && isset($transaction))
        // Polling: verifica status a cada 3 segundos
        const transactionId = {{ $transaction->id }};
        const pollInterval = setInterval(function () {
            fetch('/ppv/transaction/' + transactionId + '/status')
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'paid_out' && data.purchase_id) {
                        clearInterval(pollInterval);
                        document.getElementById('status-msg').innerHTML =
                            '<span class="text-green-600 font-semibold">✓ Pagamento confirmado! Redirecionando...</span>';
                        setTimeout(() => {
                            window.location.href = '/ppv/success/' + data.purchase_id;
                        }, 1500);
                    } else if (data.status === 'canceled' || data.status === 'unpaid') {
                        clearInterval(pollInterval);
                        document.getElementById('status-msg').innerHTML =
                            '<span class="text-red-500">Pagamento não concluído.</span>';
                    }
                });
        }, 3000);
        @endif

        @if($method === 'card')
        document.getElementById('card-form').addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = this.querySelector('button[type=submit]');
            btn.disabled = true;
            btn.textContent = 'Processando...';

            const data = new FormData(this);
            data.append('_token', document.querySelector('meta[name=csrf-token]').content);

            fetch('{{ route("ppv.process", [$post->id, "card"]) }}', {
                method: 'POST',
                body: data,
            })
            .then(r => r.json())
            .then(res => {
                if (res.redirect) {
                    window.location.href = res.redirect;
                } else if (!res.success) {
                    document.getElementById('card-error').textContent = res.message;
                    document.getElementById('card-error').classList.remove('hidden');
                    btn.disabled = false;
                    btn.textContent = 'Pagar R$ {{ number_format($post->price, 2, ",", ".") }}';
                }
            })
            .catch(() => {
                btn.disabled = false;
                btn.textContent = 'Pagar R$ {{ number_format($post->price, 2, ",", ".") }}';
            });
        });
        @endif
    </script>
</body>
</html>
```

- [ ] **Step 2: Criar `ppv/success.blade.php`**

```php
{{-- resources/views/ppv/success.blade.php --}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Conteúdo Liberado!</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <x-topnav />

    <div class="pt-16 pb-16">
        <div class="max-w-lg mx-auto px-4 py-8 text-center">
            <div class="bg-white rounded-xl shadow-sm p-8">
                <!-- Ícone de sucesso -->
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>

                <h1 class="text-2xl font-bold text-gray-900 mb-2">Conteúdo Liberado!</h1>
                <p class="text-gray-500 mb-1">
                    Você comprou este conteúdo por
                    <strong class="text-gray-800">R$ {{ number_format($purchase->amount_paid, 2, ',', '.') }}</strong>
                </p>
                <p class="text-gray-400 text-sm mb-8">O acesso é permanente.</p>

                <!-- Botão para ver o conteúdo -->
                <a href="{{ $post->user->username ? '/' . $post->user->username : route('dashboard') }}"
                   class="inline-block bg-green-500 hover:bg-green-600 text-white font-semibold px-8 py-3 rounded-full transition-colors">
                    Ver perfil de {{ $creator->name }}
                </a>

                <div class="mt-4">
                    <a href="{{ route('dashboard') }}" class="text-gray-400 text-sm hover:text-gray-600">
                        Voltar ao início
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
```

- [ ] **Step 3: Commit**

```bash
git add resources/views/ppv/
git commit -m "feat: views de checkout e sucesso do Conteudo Unico"
```

---

## Task 10: Deploy e validação final

- [ ] **Step 1: Push para o GitHub**

```bash
git push
```

- [ ] **Step 2: Deploy no servidor**

```bash
ssh root@209.126.103.238
deploy-pierfans
```

- [ ] **Step 3: Verificar que as migrations rodaram**

```bash
ssh root@209.126.103.238 "cd /home/pierfans/web/pierfans.com/public_html && php artisan migrate:status | grep -E '2026_06_22'"
```

Esperado: as 3 migrations de 2026_06_22 aparecem com `Yes` na coluna Ran.

- [ ] **Step 4: Validação manual — criar post como criador**

1. Logar como criador aprovado
2. Ir em `/posts/create`
3. Selecionar "Conteúdo Único" → campo de preço deve aparecer
4. Inserir um preço e publicar → post deve ser criado

- [ ] **Step 5: Validação manual — fluxo de compra**

1. Logar como usuário diferente do criador
2. Visitar o perfil do criador → post deve aparecer com botão verde "Comprar agora"
3. Clicar → deve ir para `/ppv/{id}/pix` com QR code
4. (Em sandbox) completar o pagamento → tela de sucesso deve aparecer
5. Voltar ao perfil → conteúdo deve estar visível
