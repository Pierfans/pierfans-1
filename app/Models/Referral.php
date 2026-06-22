<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    protected $fillable = [
        'referred_user_id',
        'referrer_user_id',
        'creator_id', // Opcional - indicação vale para qualquer assinatura
        'referred_at',
    ];

    protected $casts = [
        'referred_at' => 'datetime',
    ];

    /**
     * Usuário que foi indicado
     */
    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    /**
     * Usuário que indicou
     */
    public function referrerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    /**
     * Criador que foi acessado (opcional - pode ser null)
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
