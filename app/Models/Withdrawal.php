<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Withdrawal extends Model
{
    protected $fillable = [
        'user_id',
        'type', // 'creator' ou 'affiliate'
        'bank_account_id',
        'amount',
        'status',
        'admin_notes',
        'processed_at',
        'suitpay_transaction_id',
        'suitpay_external_id',
        'suitpay_response_data',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'suitpay_response_data' => 'array',
    ];

    /**
     * Relacionamento com User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento com BankAccount
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    /**
     * Verifica se está pendente
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Verifica se foi transferido
     */
    public function isTransferred(): bool
    {
        return $this->status === 'transferred';
    }

    /**
     * Verifica se foi recusado
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
