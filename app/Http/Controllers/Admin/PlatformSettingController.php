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
        $autoWithdrawLimit = PlatformSetting::getAutoWithdrawLimit();
        $platformPercentageCard = PlatformSetting::getPlatformPercentageCard();
        $chatReleaseDays = PlatformSetting::getChatReleaseDays();
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
            'tracking_head' => PlatformSetting::getValue('tracking_head'),
            'banner' => PlatformSetting::collabBanner(),
            // cru, sem fallback: vazio na tela = "usa o banner geral"
            'bannerDash' => PlatformSetting::where('key', 'like', 'banner_dash_%')->pluck('value', 'key'),
            'platform_percentage' => $platformPercentage,
            'daily_withdraw_limit' => $dailyWithdrawLimit,
            'min_withdraw_amount' => $minWithdrawAmount,
            'auto_withdraw_limit' => $autoWithdrawLimit,
            'platform_percentage_card' => $platformPercentageCard,
            'chat_release_days' => $chatReleaseDays,
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
        // o Bento vai colar "@Taynaandrade": tira o @ antes do exists, senao recusa
        foreach (['banner_username', 'banner_dash_username'] as $k) {
            $request->merge([$k => ltrim(trim((string) $request->input($k)), '@')]);
        }

        $validated = $request->validate([
            'platform_percentage' => 'required|numeric|min:0|max:100',
            'daily_withdraw_limit' => 'required|integer|min:1|max:100',
            'min_withdraw_amount' => 'required|numeric|min:1',
            'auto_withdraw_limit' => 'nullable|numeric|min:0',
            'platform_percentage_card' => 'nullable|numeric|min:0|max:100',
            'chat_release_days' => 'nullable|integer|min:0',
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
            // banner da collab: foto de celular passa de 2 MB facil, 5 MB de teto; o fpm aceita 512M
            'banner_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'banner_text' => 'nullable|string|max:150',
            'banner_username' => ['nullable', 'string', 'max:30', \Illuminate\Validation\Rule::exists('users', 'username')->where('creator_status', 'approved')],
            // dashboard opcional: mesmos campos, vazio usa o geral
            'banner_dash_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'banner_dash_text' => 'nullable|string|max:150',
            'banner_dash_username' => ['nullable', 'string', 'max:30', \Illuminate\Validation\Rule::exists('users', 'username')->where('creator_status', 'approved')],
            'banner_dash_image_remove' => 'nullable|boolean',
            // pixel do trafego pago: html cru colado pelo admin, vai no <head> de toda pagina.
            // 10000 e nao 5000: tag manager + pixel de conversao juntos passam de 3 mil facil.
            'tracking_head' => 'nullable|string|max:10000',
        ]);

        // Bloco restaurado em 15/09: o commit do banner de 28/08 (ab4b3b2) apagou isto sem querer
        // e desde entao a tela so gravava o banner. Tudo abaixo validava e caia no chao.
        PlatformSetting::setValue(
            'platform_percentage',
            (string) $validated['platform_percentage'],
            'Porcentagem que a plataforma recebe de cada assinatura'
        );
        PlatformSetting::setDailyWithdrawLimit($validated['daily_withdraw_limit']);
        PlatformSetting::setMinWithdrawAmount($validated['min_withdraw_amount']);
        PlatformSetting::setAutoWithdrawLimit((float) ($validated['auto_withdraw_limit'] ?? 0));
        PlatformSetting::setPlatformPercentageCard((float) ($validated['platform_percentage_card'] ?? $validated['platform_percentage']));
        PlatformSetting::setChatReleaseDays((int) ($validated['chat_release_days'] ?? 7));
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
        PlatformSetting::setValue(
            'tracking_head',
            trim((string) ($validated['tracking_head'] ?? '')),
            'Código de rastreamento (pixel) colado no <head> de todas as páginas'
        );

        foreach (['banner' => 'geral (login e dashboard)', 'banner_dash' => 'só do dashboard'] as $k => $desc) {
            if ($file = $request->file("{$k}_image")) {
                // nome datado e nao fixo: o Cloudflare cacheia /img e a foto velha ficaria no ar
                $name = 'collab-' . now()->format('Ymd-His') . '-' . $k . '.' . $file->extension();
                $file->move(public_path('img/banners'), $name);
                $this->unlinkBanner(PlatformSetting::getValue("{$k}_image"));
                PlatformSetting::setValue("{$k}_image", '/img/banners/' . $name, "Foto do banner $desc");
            }
            PlatformSetting::setValue("{$k}_text", (string) ($validated["{$k}_text"] ?? ''), "Frase do banner $desc");
            PlatformSetting::setValue("{$k}_username", (string) ($validated["{$k}_username"] ?? ''), "@ da criadora pra onde o banner $desc leva");
        }
        if ($request->boolean('banner_dash_image_remove')) {
            $this->unlinkBanner(PlatformSetting::getValue('banner_dash_image'));
            PlatformSetting::setValue('banner_dash_image', '', 'Foto do banner só do dashboard');
        }

        return redirect()->back()->with('success', 'Configurações atualizadas com sucesso!');
    }

    private function unlinkBanner(?string $path): void
    {
        if ($path && str_starts_with($path, '/img/banners/')) {
            @unlink(public_path($path));
        }
    }
}
