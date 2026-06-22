<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    protected $fillable = [
        'wallet_id',
        'admin_user_id',
        'payment_transaction_id',
        'amount',
        'type',
        'description',
        'admin_notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Relacionamento com Wallet
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Relacionamento com User (admin que adicionou o saldo)
     */
    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    /**
     * Relacionamento com PaymentTransaction
     */
    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(\App\Models\PaymentTransaction::class, 'payment_transaction_id');
    }
}
