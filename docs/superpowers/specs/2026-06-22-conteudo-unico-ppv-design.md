# Design: Conteúdo Único (Pay-Per-View)

**Data:** 2026-06-22
**Solicitante:** Bento (CTO)
**Status:** Aprovado

---

## Contexto

A plataforma hoje permite dois tipos de visibilidade em posts: `free` (gratuito) e `subscriber` (somente assinantes). O CTO solicitou uma terceira opção chamada **Conteúdo Único** — o usuário paga um valor avulso para ter acesso àquele post específico, independente de ter ou não assinatura ativa do criador.

---

## Regras de Negócio

1. **Acesso independente de assinatura** — mesmo quem já assina o criador precisa pagar pelo Conteúdo Único separadamente.
2. **Preço definido pelo criador** na hora de publicar o post.
3. **Preço editável a qualquer momento** pelo criador (inclusive após vendas — para promoções).
4. **Acesso permanente** após a compra — não expira.
5. **Divisão financeira:** 80% criador / 20% plataforma (mesma regra dos demais pagamentos).
6. **Snapshot financeiro:** o valor pago, percentual e divisão são salvos no momento da compra e não mudam com edições futuras do preço.
7. Vale para fotos e vídeos — não só vídeos.

---

## Abordagem Escolhida

**Controller dedicado (Abordagem B)** — novo `PPVCheckoutController` paralelo ao `CheckoutController` existente, sem tocar no fluxo de assinaturas. Refatoração para `PaymentService` compartilhado fica para o futuro (Abordagem C).

---

## Banco de Dados

### Migration 1 — Altera `posts`

```
posts
├── visibility  ENUM('free', 'subscriber', 'paid')   ← adiciona 'paid'
└── price       DECIMAL(10,2) NULL                    ← novo campo
```

`price` é nullable porque posts `free` e `subscriber` não têm preço.

### Migration 2 — Cria `post_purchases`

```
post_purchases
├── id                        BIGINT PK
├── user_id                   FK → users        (comprador)
├── post_id                   FK → posts        (conteúdo comprado)
├── creator_id                FK → users        (criador do conteúdo)
├── payment_transaction_id    FK → payment_transactions
├── amount_paid               DECIMAL(10,2)     (snapshot do valor pago)
├── platform_percentage       DECIMAL(5,2)      (snapshot do % da plataforma)
├── platform_amount           DECIMAL(10,2)     (valor da plataforma)
├── creator_amount            DECIMAL(10,2)     (valor do criador)
├── purchased_at              TIMESTAMP
└── timestamps
```

### Migration 3 — Altera `payment_transactions`

```
payment_transactions
└── post_id    FK nullable → posts    ← novo campo discriminador
```

Discriminação no webhook: `post_id preenchido = PPV`, `subscription_plan_id preenchido = assinatura`, `ambos nulos = recarga de carteira`.

---

## Models

### `Post.php`

```php
// Relacionamento
public function purchases(): HasMany
{
    return $this->hasMany(PostPurchase::class);
}

// Conveniência
public function isPurchasedBy(int $userId): bool
{
    return $this->purchases()->where('user_id', $userId)->exists();
}
```

### `PostPurchase.php` (novo)

```php
protected $fillable = [
    'user_id', 'post_id', 'creator_id',
    'payment_transaction_id', 'amount_paid',
    'platform_percentage', 'platform_amount',
    'creator_amount', 'purchased_at',
];

// Relacionamentos: post(), buyer(), creator()
```

### `PaymentTransaction.php`

Adiciona `post_id` ao `$fillable`.

---

## Rotas

```php
Route::middleware('auth')->group(function () {
    Route::get('/ppv/{post}/{method}',                [PPVCheckoutController::class, 'show'])->name('ppv.show');
    Route::post('/ppv/{post}/{method}',               [PPVCheckoutController::class, 'process'])->name('ppv.process');
    Route::get('/ppv/transaction/{transactionId}/status', [PPVCheckoutController::class, 'checkStatus'])->name('ppv.transaction.status');
    Route::get('/ppv/success/{purchase}',             [PPVCheckoutController::class, 'success'])->name('ppv.success');
});
```

---

## PPVCheckoutController

### Guardas em todos os métodos

```php
abort_if($post->visibility !== 'paid', 404);
abort_if($post->user_id === auth()->id(), 403);  // criador não compra o próprio

if ($post->isPurchasedBy(auth()->id())) {
    return redirect()->route('ppv.success', ...);  // já tem acesso
}
```

### Métodos

| Método | Responsabilidade |
|---|---|
| `show()` | Exibe tela de pagamento. PIX gera QR code automaticamente. |
| `process()` | Chama SuitPay, cria `PaymentTransaction` com `post_id`. |
| `checkStatus()` | Polling do frontend — retorna status da transação. |
| `success()` | Tela de confirmação após compra. |

---

## SuitPayWebhookController

### Nova discriminação

```php
if ($transaction->post_id) {
    $this->createPostPurchaseFromTransaction($transaction);   // PPV ← novo
} elseif ($transaction->subscription_plan_id) {
    $this->createSubscriptionFromTransaction($transaction);   // assinatura
} else {
    // recarga de carteira
}
```

### `createPostPurchaseFromTransaction()`

- Guard de idempotência: verifica se `PostPurchase` com `payment_transaction_id` já existe antes de criar.
- Busca `platform_percentage` do `PlatformSetting`.
- Calcula `platform_amount` e `creator_amount`.
- Cria `PostPurchase` e atualiza status da `PaymentTransaction` para `paid_out`.

---

## UI

### Formulário de criar/editar post

- Select de visibilidade ganha a opção `paid` ("Conteúdo Único").
- Campo de preço (`input[type=number]`, min=1, max=9999) aparece via JS apenas quando `paid` é selecionado.
- Na edição: JS ao carregar a página mostra o campo já preenchido se `visibility === 'paid'`.
- Ao trocar para outro tipo: campo some e valor é limpo.

### post-card.blade.php

Três estados:

| Estado | Condição |
|---|---|
| Conteúdo visível | `free` / `subscriber` com assinatura / `paid` com compra |
| Cadeado azul "Assinar" | `subscriber` sem assinatura ativa |
| Cadeado verde "Comprar por R$ X" | `paid` sem compra registrada ← novo |

### Tela de sucesso

Reutiliza layout existente com texto adaptado: "Conteúdo liberado! Você comprou este conteúdo por R$ X,00."

---

## Fluxo Completo (PIX)

```
1. Usuário clica "Comprar por R$ X"
2. GET /ppv/{postId}/pix          → exibe QR code
3. POST /ppv/{postId}/pix         → cria PaymentTransaction (post_id preenchido)
4. Frontend polling /ppv/transaction/{id}/status
5. SuitPay dispara POST /api/webhook/suitpay
6. Webhook detecta post_id → createPostPurchaseFromTransaction()
7. Frontend detecta paid_out → redireciona para /ppv/success/{purchaseId}
8. Usuário vê conteúdo liberado
```

---

## O que NÃO está no escopo

- Reembolsos de Conteúdo Único
- Expiração de acesso (permanente por definição — campo `expires_at` reservado para futuro)
- Refatoração para `PaymentService` compartilhado (Abordagem C — futuro)
- Notificações por e-mail ao criador quando alguém compra
