<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Ledger de conciliação financeira — registro imutável do split + taxa SuitPay
 * por evento de dinheiro (venda de assinatura, venda PPV, saque).
 *
 * O split (creator/affiliate) NÃO é recalculado aqui — é capturado do que o
 * fluxo de pagamento já computou. A plataforma é derivada no relatório:
 *   platform = gross - creator - affiliate - suitpay_fee  (vendas)
 */
class LedgerEntry extends Model
{
    protected $fillable = [
        'entry_type',
        'payment_transaction_id',
        'withdrawal_id',
        'gross_amount',
        'suitpay_fee',
        'creator_amount',
        'affiliate_amount',
        'occurred_at',
    ];

    protected $casts = [
        'gross_amount'     => 'decimal:2',
        'suitpay_fee'      => 'decimal:2',
        'creator_amount'   => 'decimal:2',
        'affiliate_amount' => 'decimal:2',
        'occurred_at'      => 'datetime',
    ];

    /**
     * Grava uma entrada de forma idempotente (chaveada pela origem) e resiliente:
     * falha de ledger NUNCA quebra o fluxo de pagamento — só loga.
     * ponytail: side-effect de auditoria; engolir+logar é o comportamento certo aqui.
     */
    public static function record(array $attrs): ?self
    {
        try {
            $key = isset($attrs['payment_transaction_id'])
                ? ['payment_transaction_id' => $attrs['payment_transaction_id']]
                : ['withdrawal_id' => $attrs['withdrawal_id']];

            return self::firstOrCreate($key, $attrs);
        } catch (\Throwable $e) {
            Log::error('LEDGER: falha ao gravar entrada', [
                'attrs' => $attrs,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Taxa do SuitPay numa venda (entrada).
     * Cartão: o webhook traz netAmount → usa a taxa REAL (value - netAmount).
     * PIX: webhook não traz taxa → fórmula (max 1%, R$0,50) via PlatformSetting.
     */
    public static function saleFee(PaymentTransaction $tx): float
    {
        $net = $tx->webhook_data['netAmount'] ?? null;
        if ($net !== null) {
            return round((float) $tx->amount - (float) $net, 2);
        }
        return PlatformSetting::suitpayFeeIn((float) $tx->amount);
    }
}
