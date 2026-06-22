<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
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

    protected $casts = [
        'amount' => 'decimal:2',
        'response_data' => 'array',
        'webhook_data' => 'array',
    ];

    /**
     * Relacionamento com User (assinante)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento com SubscriptionPlan
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    /**
     * Relacionamento com Creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Relacionamento com Subscription
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Relacionamento com Post (PPV)
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Verifica se a transação está paga
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid_out';
    }

    /**
     * Verifica se a transação está pendente
     */
    public function isPending(): bool
    {
        return $this->status === 'pending' || $this->status === 'waiting_for_approval';
    }
}
