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
        'fee',
        'status',
        'admin_notes',
        'processed_at',
        'suitpay_transaction_id',
        'suitpay_external_id',
        'suitpay_response_data',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'processed_at' => 'datetime',
        'suitpay_response_data' => 'array',
    ];

    /**
     * Regra de saque estilo Privacy: 1 saque grátis por dia (por tipo criador/afiliado),
     * os demais custam uma taxa fixa; teto de N/dia. Conta só saques válidos
     * (pending/transferred) para que um saque recusado não queime a franquia nem o teto.
     *
     * ponytail: global — chame DENTRO de uma transação. O lockForUpdate serializa os
     * saques já existentes do dia; o caso raro do 1º-do-dia em duplo-clique não é coberto
     * (upgrade: índice único (user_id, type, dia) ou lock por usuário se virar problema).
     *
     * @return array{count:int, fee:float, allowed:bool, limit:int}
     */
    public static function assessDailyFee(int $userId, string $type, float $amount = 0): array
    {
        $todayCount = self::where('user_id', $userId)
            ->where('type', $type)
            ->whereDate('created_at', now()->toDateString())
            ->whereIn('status', ['pending', 'transferred'])
            ->lockForUpdate()
            ->count();

        $limit = PlatformSetting::getDailyWithdrawLimit();

        return [
            'count'   => $todayCount,
            // 1º saque do dia grátis (plataforma absorve o custo SuitPay); nos extras, quem saca
            // paga a taxa real de saída da SuitPay (3,5%) — repassa o custo, plataforma fica zero a zero.
            'fee'     => $todayCount >= 1 ? PlatformSetting::suitpayFeeOut($amount) : 0.0,
            'allowed' => $todayCount < $limit,
            'limit'   => $limit,
        ];
    }

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
