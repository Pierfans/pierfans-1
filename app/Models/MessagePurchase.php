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
        'affiliate_user_id',
        'payment_transaction_id',
        'amount_paid',
        'platform_percentage',
        'platform_amount',
        'affiliate_amount',
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
     * Prazo proprio, de 7 dias por padrao (bento 21/09: "essa pra evitar problemas, deve
     * ficar presa por 7 dias, seja pix ou cartao"). Nao usa o prazo do PIX nem o do
     * cartao porque a venda de mensagem e sempre paga com saldo: a forma de pagamento
     * original ficou la atras, na recarga da carteira.
     */
    public static function creatorAmount(int $creatorId, bool $released): float
    {
        $days = PlatformSetting::getChatReleaseDays();
        $date = $days == 0 ? now() : now()->subDays($days)->endOfDay();

        return (float) self::where('creator_id', $creatorId)
            ->where('purchased_at', $released ? '<=' : '>', $date)
            ->sum('creator_amount');
    }

    /**
     * Quanto o afiliado tem de mensagem vendida pelas criadoras que ele trouxe.
     * Mesmo prazo da venda do chat.
     */
    public static function affiliateAmount(int $affiliateId, bool $released): float
    {
        $days = PlatformSetting::getChatReleaseDays();
        $date = $days == 0 ? now() : now()->subDays($days)->endOfDay();

        return (float) self::where('affiliate_user_id', $affiliateId)
            ->where('purchased_at', $released ? '<=' : '>', $date)
            ->sum('affiliate_amount');
    }
}
