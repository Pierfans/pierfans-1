<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionActivationLog extends Model
{
    protected $fillable = [
        'subscription_id',
        'admin_user_id',
        'reason',
    ];

    /**
     * Relacionamento com Subscription
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Relacionamento com User (admin)
     */
    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
