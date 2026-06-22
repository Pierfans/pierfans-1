<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateCommission extends Model
{
    protected $fillable = [
        'affiliate_user_id',
        'referred_user_id',
        'subscription_id',
        'creator_id',
        'subscription_amount',
        'commission_percentage',
        'commission_amount',
        'status',
        'released_at',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'subscription_amount' => 'decimal:2',
        'commission_percentage' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'released_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    /**
     * Usuário afiliado que recebe a comissão
     */
    public function affiliateUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'affiliate_user_id');
    }

    /**
     * Usuário indicado que realizou a assinatura
     */
    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    /**
     * Assinatura que gerou a comissão
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Criador do conteúdo assinado
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Verifica se está pendente
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Verifica se está liberado
     */
    public function isReleased(): bool
    {
        return $this->status === 'released';
    }

    /**
     * Verifica se foi pago
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Verifica se foi cancelado
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}
