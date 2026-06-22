<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'duration_days',
        'price',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Relacionamento com User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
