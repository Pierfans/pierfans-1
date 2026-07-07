<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    /**
     * Obtém o valor de uma configuração
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Define o valor de uma configuração
     */
    public static function setValue(string $key, string $value, string $description = null)
    {
        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'description' => $description,
            ]
        );
    }

    /**
     * Obtém a porcentagem da plataforma
     */
    public static function getPlatformPercentage(): float
    {
        return (float) self::getValue('platform_percentage', 20);
    }

    /**
     * Obtém o limite diário de saques
     */
    public static function getDailyWithdrawLimit(): int
    {
        return (int) self::getValue('daily_withdraw_limit', 5);
    }

    /**
     * Define o limite diário de saques
     */
    public static function setDailyWithdrawLimit(int $limit): void
    {
        self::setValue(
            'daily_withdraw_limit',
            (string) $limit,
            'Número máximo de saques que um criador pode fazer por dia'
        );
    }

    /**
     * Obtém o valor mínimo de saque
     */
    public static function getMinWithdrawAmount(): float
    {
        return (float) self::getValue('min_withdraw_amount', 50.00);
    }

    /**
     * Define o valor mínimo de saque
     */
    public static function setMinWithdrawAmount(float $amount): void
    {
        self::setValue(
            'min_withdraw_amount',
            (string) $amount,
            'Valor mínimo que um criador pode solicitar para saque'
        );
    }

    /**
     * Obtém os dias de bloqueio para pagamentos via PIX
     */
    public static function getPixReleaseDays(): int
    {
        $value = self::getValue('pix_release_days', 0);
        return $value === null || $value === '' ? 0 : (int) $value;
    }

    /**
     * Define os dias de bloqueio para pagamentos via PIX
     */
    public static function setPixReleaseDays(int $days): void
    {
        self::setValue(
            'pix_release_days',
            (string) $days,
            'Número de dias que pagamentos via PIX ficam bloqueados antes de liberar para saque. 0 = liberação imediata'
        );
    }

    /**
     * Obtém os dias de bloqueio para pagamentos via Cartão
     */
    public static function getCardReleaseDays(): int
    {
        $value = self::getValue('card_release_days', 0);
        return $value === null || $value === '' ? 0 : (int) $value;
    }

    /**
     * Define os dias de bloqueio para pagamentos via Cartão
     */
    public static function setCardReleaseDays(int $days): void
    {
        self::setValue(
            'card_release_days',
            (string) $days,
            'Número de dias que pagamentos via Cartão ficam bloqueados antes de liberar para saque. 0 = liberação imediata'
        );
    }

    /**
     * Obtém a porcentagem de comissão de afiliado
     */
    public static function getAffiliateCommissionPercentage(): float
    {
        return (float) self::getValue('affiliate_commission_percentage', 5);
    }

    /**
     * Define a porcentagem de comissão de afiliado
     */
    public static function setAffiliateCommissionPercentage(float $percentage): void
    {
        self::setValue(
            'affiliate_commission_percentage',
            (string) $percentage,
            'Porcentagem de comissão que o afiliado recebe por cada indicação que assinar'
        );
    }

    /**
     * Obtém o limite de recebimento por indicação
     * 0 = sem limite, qualquer outro número = limite máximo de comissões por pessoa indicada
     */
    public static function getAffiliateCommissionLimit(): int
    {
        $value = self::getValue('affiliate_commission_limit', 0);
        return $value === null || $value === '' ? 0 : (int) $value;
    }

    /**
     * Define o limite de recebimento por indicação
     * 0 = sem limite, qualquer outro número = limite máximo de comissões por pessoa indicada
     */
    public static function setAffiliateCommissionLimit(int $limit): void
    {
        self::setValue(
            'affiliate_commission_limit',
            (string) $limit,
            'Limite de recebimento por indicação. Define quantas vezes um afiliado pode receber comissão pela mesma pessoa indicada. 0 = sem limite'
        );
    }

    /**
     * Verifica se a confirmação de e-mail é obrigatória
     */
    public static function isEmailVerificationRequired(): bool
    {
        $value = self::getValue('email_verification_required', '0');
        return $value === '1' || $value === 1 || $value === true;
    }

    /**
     * Define se a confirmação de e-mail é obrigatória
     */
    public static function setEmailVerificationRequired(bool $required): void
    {
        self::setValue(
            'email_verification_required',
            $required ? '1' : '0',
            'Exigir confirmação de e-mail para novos usuários. 1 = ativado, 0 = desativado'
        );
    }

    /**
     * Verifica se o upload de mídia deve ser feito no R2 (true) ou localmente (false)
     */
    public static function isUseR2Upload(): bool
    {
        $value = self::getValue('use_r2_upload', '1');
        return $value === '1' || $value === 1 || $value === true;
    }

    /**
     * Define se o upload de mídia será no R2 ou local
     */
    public static function setUseR2Upload(bool $use): void
    {
        self::setValue(
            'use_r2_upload',
            $use ? '1' : '0',
            'Quando ativado, upload de mídia de posts é feito no R2. Quando desativado, é feito localmente.'
        );
    }

    /**
     * Taxa do SuitPay numa entrada PIX (recebimento): max(3,5% do valor, R$0,99).
     * O SuitPay NÃO envia a taxa de PIX recebido no webhook, então estimamos por fórmula.
     * CRAVADO com o extrato de jun/2026 que traz a taxa de CADA linha: +29,90→1,05 (3,5%),
     * +50,00→1,75 (3,5%), +19,90→0,99, +1,00→0,99 (piso R$0,99, morde abaixo de ~R$28,28).
     * Modelo 3,5%+0,99 fecha o mês em R$0,14 (R$76,96 vs R$77,10 real); o antigo 3,75%+0,50
     * errava +R$1,70. O "1%" anunciado na tela do SuitPay é falso.
     * ponytail: estimativa proposital; fonte EXATA = extrato/Exportar Excel do SuitPay.
     */
    public static function suitpayFeeIn(float $amount): float
    {
        $pct = (float) self::getValue('suitpay_fee_pix_in_percent', 3.5);
        $min = (float) self::getValue('suitpay_fee_pix_in_min', 0.99);
        return round(max($amount * $pct / 100, $min), 2);
    }

    /**
     * Taxa do SuitPay numa saída PIX (saque/cashout): 3,5% do valor.
     */
    public static function suitpayFeeOut(float $amount): float
    {
        $pct = (float) self::getValue('suitpay_fee_pix_out_percent', 3.5);
        return round($amount * $pct / 100, 2);
    }
}
