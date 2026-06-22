<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualCredit extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'admin_user_id',
        'amount',
        'reason',
        'admin_notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Relacionamento com User (usuário que recebeu o crédito)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento com User (admin que adicionou o crédito)
     */
    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
