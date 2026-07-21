<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'paid_with',
        'payment_transaction_id',
        'withdrawal_id',
        'gross_amount',
        'suitpay_fee',
        'withdraw_fee',
        'creator_amount',
        'affiliate_amount',
        'occurred_at',
    ];

    protected $casts = [
        'gross_amount'     => 'decimal:2',
        'suitpay_fee'      => 'decimal:2',
        'withdraw_fee'     => 'decimal:2',
        'creator_amount'   => 'decimal:2',
        'affiliate_amount' => 'decimal:2',
        'occurred_at'      => 'datetime',
    ];

    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class);
    }

    public function withdrawal(): BelongsTo
    {
        return $this->belongsTo(Withdrawal::class);
    }

    /**
     * Id de Controle no SuitPay (o UUID que aparece no extrato "Exportar Excel").
     * Vendas casam pelo request_number da transação; saques pelo suitpay_external_id.
     */
    public function suitpayControlId(): ?string
    {
        return $this->paymentTransaction?->request_number
            ?? $this->withdrawal?->suitpay_external_id
            ?? null;
    }

    /** Rótulo do tipo de lançamento em PT. */
    public function typeLabel(): string
    {
        return [
            'subscription_sale' => 'Assinatura',
            'ppv_sale'          => 'Conteúdo Único',
            'cashout'           => 'Saque',
        ][$this->entry_type] ?? $this->entry_type;
    }

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
     * Origem do dinheiro da venda. 'wallet' = pago com saldo de carteira: É receita, mas NÃO é
     * entrada nova no banco (entrou lá atrás, no depósito). A reconciliação contra o extrato e o
     * saldo real filtram por isso — sem essa marca, venda com saldo inflaria os dois.
     */
    public static function paidWith(PaymentTransaction $tx): string
    {
        return $tx->type === 'wallet' ? 'wallet' : 'suitpay';
    }

    /**
     * Taxa do SuitPay numa venda (entrada).
     * Carteira: zero — o SuitPay não vê essa venda; a taxa dele já foi paga no depósito.
     * Cartão: o webhook traz netAmount → usa a taxa REAL (value - netAmount).
     * PIX: webhook não traz taxa → fórmula max(3,5%, R$0,99) via PlatformSetting.
     */
    public static function saleFee(PaymentTransaction $tx): float
    {
        if ($tx->type === 'wallet') {
            return 0.0;
        }
        $net = $tx->webhook_data['netAmount'] ?? null;
        if ($net !== null) {
            return round((float) $tx->amount - (float) $net, 2);
        }
        return PlatformSetting::suitpayFeeIn((float) $tx->amount);
    }
}
