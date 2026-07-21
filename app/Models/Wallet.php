<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    protected $fillable = [
        'user_id',
        'balance',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    /**
     * Relacionamento com User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento com WalletTransaction
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class)->orderBy('created_at', 'desc');
    }

    /**
     * Adiciona saldo à carteira
     */
    public function addBalance(float $amount, ?int $adminUserId = null, ?string $description = null, ?string $adminNotes = null, ?int $paymentTransactionId = null): WalletTransaction
    {
        $this->balance += $amount;
        $this->save();

        return WalletTransaction::create([
            'wallet_id' => $this->id,
            'admin_user_id' => $adminUserId,
            'payment_transaction_id' => $paymentTransactionId,
            'amount' => $amount,
            'type' => 'credit',
            'description' => $description,
            'admin_notes' => $adminNotes,
        ]);
    }

    /**
     * Debita saldo da carteira.
     *
     * ATENÇÃO: chame SEMPRE dentro de uma transação, com a linha da carteira já travada
     * (`Wallet::where('user_id', $id)->lockForUpdate()->first()`). A checagem de saldo aqui é
     * lê-e-grava: sem o lock, dois cliques simultâneos leem o mesmo saldo e gastam duas vezes.
     */
    public function subtractBalance(float $amount, ?string $description = null): WalletTransaction
    {
        if ($this->balance < $amount) {
            throw new \Exception('Saldo insuficiente na carteira.');
        }

        $this->balance -= $amount;
        $this->save();

        return WalletTransaction::create([
            'wallet_id' => $this->id,
            'amount' => $amount,
            'type' => 'debit',
            'description' => $description,
        ]);
    }
}
