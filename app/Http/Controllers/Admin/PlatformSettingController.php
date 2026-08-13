<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;

class PlatformSettingController extends Controller
{
    /**
     * Mostra a página de configurações da plataforma
     */
    public function index()
    {
        $platformPercentage = PlatformSetting::getPlatformPercentage();
        $dailyWithdrawLimit = PlatformSetting::getDailyWithdrawLimit();
        $minWithdrawAmount = PlatformSetting::getMinWithdrawAmount();
        $pixReleaseDays = PlatformSetting::getPixReleaseDays();
        $cardReleaseDays = PlatformSetting::getCardReleaseDays();
        $affiliateCommissionPercentage = PlatformSetting::getAffiliateCommissionPercentage();
        $affiliateCommissionLimit = PlatformSetting::getAffiliateCommissionLimit();
        $emailVerificationRequired = PlatformSetting::isEmailVerificationRequired();
        $useR2Upload = PlatformSetting::isUseR2Upload();
        $liveUrl = PlatformSetting::getValue('live_url');

        return view('admin.platform-settings.index', [
            'live_url' => $liveUrl,
            'live_stream_url' => PlatformSetting::getValue('live_stream_url'),
            'platform_percentage' => $platformPercentage,
            'daily_withdraw_limit' => $dailyWithdrawLimit,
            'min_withdraw_amount' => $minWithdrawAmount,
            'pix_release_days' => $pixReleaseDays,
            'card_release_days' => $cardReleaseDays,
            'affiliate_commission_percentage' => $affiliateCommissionPercentage,
            'affiliate_commission_limit' => $affiliateCommissionLimit,
            'email_verification_required' => $emailVerificationRequired,
            'use_r2_upload' => $useR2Upload,
        ]);
    }

    /**
     * Atualiza as configurações da plataforma
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'platform_percentage' => 'required|numeric|min:0|max:100',
            'daily_withdraw_limit' => 'required|integer|min:1|max:100',
            'min_withdraw_amount' => 'required|numeric|min:1',
            'pix_release_days' => 'nullable|integer|min:0',
            'card_release_days' => 'nullable|integer|min:0',
            'affiliate_commission_percentage' => 'required|numeric|min:0|max:100',
            'affiliate_commission_limit' => 'required|integer|min:0',
            'email_verification_required' => 'nullable|boolean',
            'use_r2_upload' => 'nullable|boolean',
            // url:http,https e nao so 'url': o valor vai direto pro src de um iframe,
            // e o validador padrao do PHP aceita 'javascript:'.
            'live_url' => 'nullable|url:http,https|max:500',
            // 2000 e nao 500: o link do .m3u8 carrega o token da sessao de playback
            // e passa de mil caracteres. A coluna e text(), entao o banco aguenta.
            'live_stream_url' => 'nullable|url:http,https|max:2000',
        ]);

        PlatformSetting::setValue(
            'platform_percentage',
            (string) $validated['platform_percentage'],
            'Porcentagem que a plataforma recebe de cada assinatura'
        );

        PlatformSetting::setDailyWithdrawLimit($validated['daily_withdraw_limit']);
        PlatformSetting::setMinWithdrawAmount($validated['min_withdraw_amount']);
        PlatformSetting::setPixReleaseDays($validated['pix_release_days'] ?? 0);
        PlatformSetting::setCardReleaseDays($validated['card_release_days'] ?? 0);
        PlatformSetting::setAffiliateCommissionPercentage($validated['affiliate_commission_percentage']);
        PlatformSetting::setAffiliateCommissionLimit($validated['affiliate_commission_limit']);
        PlatformSetting::setEmailVerificationRequired($validated['email_verification_required'] ?? false);
        PlatformSetting::setUseR2Upload($request->boolean('use_r2_upload'));

        PlatformSetting::setValue(
            'live_url',
            (string) ($validated['live_url'] ?? ''),
            'Link da transmissão ao vivo exibida em /live. Vazio = página mostra "em breve"'
        );

        PlatformSetting::setValue(
            'live_stream_url',
            (string) ($validated['live_stream_url'] ?? ''),
            'Link .m3u8 do stream, tocado no player da própria /live. Tem preferência sobre live_url'
        );

        return redirect()->back()->with('success', 'Configurações atualizadas com sucesso!');
    }
}
