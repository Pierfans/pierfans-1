<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostPurchase extends Model
{
    protected $fillable = [
        'user_id',
        'post_id',
        'creator_id',
        'payment_transaction_id',
        'amount_paid',
        'platform_percentage',
        'platform_amount',
        'creator_amount',
        'purchased_at',
    ];

    protected $casts = [
        'amount_paid'         => 'decimal:2',
        'platform_percentage' => 'decimal:2',
        'platform_amount'     => 'decimal:2',
        'creator_amount'      => 'decimal:2',
        'purchased_at'        => 'datetime',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }

    /**
     * Quanto de venda PPV o criador tem liberado (ou ainda bloqueado) pra saque.
     * Mesma regra das assinaturas: o prazo depende do método de pagamento, que no PPV
     * mora na transação (payment_transactions.type), não na compra.
     */
    public static function creatorAmount(int $creatorId, bool $released): float
    {
        $limits = [
            'pix'  => PlatformSetting::getPixReleaseDays(),
            'card' => PlatformSetting::getCardReleaseDays(),
        ];
        $op = $released ? '<=' : '>';

        $q = self::where('creator_id', $creatorId)->where(function ($outer) use ($limits, $op) {
            foreach ($limits as $method => $days) {
                $date = $days == 0 ? now() : now()->subDays($days)->endOfDay();
                $outer->orWhere(fn ($q) => $q
                    ->where('purchased_at', $op, $date)
                    ->whereHas('transaction', fn ($t) => $t->where('type', $method)));
            }
        });

        return (float) $q->sum('creator_amount');
    }
}
