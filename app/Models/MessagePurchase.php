<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Compra de mensagem trancada no chat. Espelha o PostPurchase do conteudo avulso.
 */
class MessagePurchase extends Model
{
    protected $fillable = [
        'user_id',
        'message_id',
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

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Quanto a criadora tem de mensagem vendida, liberado ou ainda preso no prazo.
     *
     * Mensagem trancada e SEMPRE paga com saldo da carteira (PIX e cartao so recarregam
     * a carteira), entao vale o prazo do PIX, a mesma regra que o getAvailableBalance ja
     * aplica pra compra feita com saldo.
     */
    public static function creatorAmount(int $creatorId, bool $released): float
    {
        $days = PlatformSetting::getPixReleaseDays();
        $date = $days == 0 ? now() : now()->subDays($days)->endOfDay();

        return (float) self::where('creator_id', $creatorId)
            ->where('purchased_at', $released ? '<=' : '>', $date)
            ->sum('creator_amount');
    }
}
