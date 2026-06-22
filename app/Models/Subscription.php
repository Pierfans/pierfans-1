<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'creator_id',
        'subscription_plan_id',
        'total_amount',
        'platform_percentage',
        'platform_amount',
        'referrer_amount',
        'creator_affiliate_amount',
        'creator_affiliate_user_id',
        'creator_amount',
        'start_date',
        'end_date',
        'is_active',
        'payment_method',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'platform_percentage' => 'decimal:2',
        'platform_amount' => 'decimal:2',
        'referrer_amount' => 'decimal:2',
        'creator_affiliate_amount' => 'decimal:2',
        'creator_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Relacionamento com User (assinante)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relacionamento com Creator (criador)
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Relacionamento com SubscriptionPlan
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    /**
     * Verifica se a assinatura está ativa
     */
    public function isActive(): bool
    {
        return $this->is_active && $this->end_date >= now()->toDateString();
    }

    /**
     * Desativa assinaturas expiradas
     */
    public static function deactivateExpired()
    {
        self::where('is_active', true)
            ->where('end_date', '<', now()->toDateString())
            ->update(['is_active' => false]);
    }
}
