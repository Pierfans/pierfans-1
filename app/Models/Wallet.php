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

        $movimento = WalletTransaction::create([
            'wallet_id' => $this->id,
            'admin_user_id' => $adminUserId,
            'payment_transaction_id' => $paymentTransactionId,
            'amount' => $amount,
            'type' => 'credit',
            'description' => $description,
            'admin_notes' => $adminNotes,
        ]);

        // Recarga paga de verdade entra no ledger: é dinheiro que caiu na conta do SuitPay.
        // Sem isso o passivo da carteira sobe na hora e o saldo real só descobre no próximo
        // import de extrato — o caixa da plataforma fica menor do que é nesse intervalo.
        // NÃO é receita: entry_type próprio, então os cards de venda ignoram sozinhos.
        // Crédito manual do admin (sem transação) não entra: ali não entrou dinheiro nenhum.
        // Fica aqui, e não nos controllers, porque há dois caminhos de crédito (app e webhook).
        if ($paymentTransactionId && ($tx = PaymentTransaction::find($paymentTransactionId))) {
            LedgerEntry::record([
                'entry_type'             => 'wallet_deposit',
                'paid_with'              => 'suitpay',
                'payment_transaction_id' => $tx->id,
                'gross_amount'           => $amount,
                'suitpay_fee'            => LedgerEntry::saleFee($tx),
                'creator_amount'         => 0,
                'affiliate_amount'       => 0,
                'occurred_at'            => now(),
            ]);
        }

        return $movimento;
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
