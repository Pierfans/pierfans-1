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
     * Percentual da plataforma quando a venda e no CARTAO (bento 21/09: "na parte do
     * cartao, ideal seria diminuir a porcentagem da criadora... se for no cartao ela
     * recebe so 76% e o restante fica pra plataforma"). A ideia e a plataforma nao
     * absorver sozinha a taxa do cartao, que come uns 8%.
     *
     * Venda paga com saldo da carteira NAO usa isto: a taxa ja foi paga la atras, na
     * recarga. Vale so pra assinatura e conteudo avulso pagos com cartao.
     */
    public static function getPlatformPercentageCard(): float
    {
        $value = self::getValue('platform_percentage_card', null);
        // Sem valor gravado, cai no percentual normal: nunca cobra a mais por engano.
        return $value === null || $value === '' ? self::getPlatformPercentage() : (float) $value;
    }

    /**
     * Define o percentual da plataforma no cartao
     */
    public static function setPlatformPercentageCard(float $percentage): void
    {
        self::setValue(
            'platform_percentage_card',
            (string) $percentage,
            'Porcentagem da plataforma quando a venda é no cartão. Vazio = usa a porcentagem normal'
        );
    }

    /**
     * Dias que a venda de mensagem do chat fica presa antes de liberar pro saque
     * (bento 21/09: "deve ficar presa por 7 dias, seja pix ou cartao"). Independe da
     * forma de pagamento, por isso nao reusa pix_release_days nem card_release_days.
     */
    public static function getChatReleaseDays(): int
    {
        $value = self::getValue('chat_release_days', 7);
        return $value === null || $value === '' ? 7 : (int) $value;
    }

    /**
     * Define os dias de bloqueio da venda de mensagem do chat
     */
    public static function setChatReleaseDays(int $days): void
    {
        self::setValue(
            'chat_release_days',
            (string) $days,
            'Número de dias que a venda de mensagem no chat fica bloqueada antes de liberar para saque'
        );
    }


    /**
     * Comissão do afiliado da CRIADORA numa venda dela (bento 21/09: "o afiliado só ganha
     * 5% em cima dos criadores... tudo o que a criadora vender ali, 5% é do afiliado").
     *
     * Sai da parte da plataforma, nunca da criadora.
     *
     * @return array{0: ?int, 1: float} id do afiliado (ou null) e o valor da comissão
     */
    public static function comissaoDoAfiliado(int $creatorId, float $valorDaVenda): array
    {
        $indicacao = \App\Models\Referral::where('referred_user_id', $creatorId)->first();

        if (!$indicacao) {
            return [null, 0.0];
        }

        // withoutGlobalScope: afiliado desativado nao some e continua recebendo o que e dele
        $afiliado = \App\Models\User::withoutGlobalScope('active')->find($indicacao->referrer_user_id);

        if (!$afiliado) {
            return [null, 0.0];
        }

        $percentual = \App\Models\PlatformSetting::getAffiliateCommissionPercentage();

        return [$afiliado->id, round($valorDaVenda * $percentual / 100, 2)];
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
     * Teto do saque automatico: saque de criador ate esse valor vai direto pro
     * SuitPay, sem passar pela aprovacao do admin. 0 = desligado, tudo manual.
     * (bento 21/09: "coloca 100 no automatico... o restante no manual... mais pra
     * frente vamos aumentando esse automatico")
     */
    public static function getAutoWithdrawLimit(): float
    {
        $value = self::getValue('auto_withdraw_limit', 0);
        return $value === null || $value === '' ? 0.0 : (float) $value;
    }

    /**
     * Define o teto do saque automatico
     */
    public static function setAutoWithdrawLimit(float $amount): void
    {
        self::setValue(
            'auto_withdraw_limit',
            (string) $amount,
            'Saques de criador até este valor vão direto para o SuitPay, sem aprovação manual. 0 = desligado'
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
     * Taxa do SuitPay numa saída PIX (saque/cashout): 3,5% do valor, com piso.
     * O piso é real e foi conferido no extrato: saque de R$8,00 e de R$1,00 pagaram R$0,99
     * cada (3,5% dariam R$0,28 e R$0,04). Sem ele, saque pequeno registra taxa menor que a
     * cobrada e o caixa da plataforma parece maior do que é.
     */
    public static function suitpayFeeOut(float $amount): float
    {
        $min = (float) self::getValue('suitpay_fee_pix_out_min', 0.99);
        return round(max($amount * self::suitpayFeeOutPercent() / 100, $min), 2);
    }

    /** Percentual da taxa de saída (para calcular o teto de um saque: valor + taxa <= saldo). */
    public static function suitpayFeeOutPercent(): float
    {
        return (float) self::getValue('suitpay_fee_pix_out_percent', 3.5);
    }

    /**
     * Banner da collab (login + dashboard): foto, frase e @ da criadora, trocados pelo admin
     * sem deploy (o Bento troca a foto todo dia, pedido de 28/08). Campo vazio cai no que
     * estava chapado no Blade naquele dia, por isso `?:` e não `??`: setValue grava '' e não null.
     */
    public static function collabBanner(string $place = 'login'): array
    {
        $v = self::where('key', 'like', 'banner_%')->pluck('value', 'key');
        // dashboard: campo a campo, vazio cai no banner geral (pedido do Pedro 28/08)
        $get = fn ($f) => ($place === 'dashboard' ? ($v["banner_dash_$f"] ?? '') : '') ?: ($v["banner_$f"] ?? '');

        return [
            'image'    => $get('image') ?: '/img/banner-collab-tayna-juju.jpg',
            'text'     => $get('text') ?: 'O grande lançamento da collab Tayná e Juju, direto da Mansão da Juju.',
            'username' => $get('username') ?: 'Taynaandrade',
        ];
    }

    /**
     * Cartao de credito ligado na plataforma inteira. Desligado por padrao (bento 24/09, audio 19:
     * "o SuitPay nao liberou por enquanto, pode tirar a parte do cartao e deixar desativado").
     * Quem le e o User::acceptsMethod('card'), por onde passam assinatura, avulso e a tela de PIX.
     */
    public static function isCardEnabled(): bool
    {
        return (string) self::getValue('card_enabled', '0') === '1';
    }
}
