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
            'banner' => PlatformSetting::collabBanner(),
            // cru, sem fallback: vazio na tela = "usa o banner geral"
            'bannerDash' => PlatformSetting::where('key', 'like', 'banner_dash_%')->pluck('value', 'key'),
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
        // o Bento vai colar "@Taynaandrade": tira o @ antes do exists, senao recusa
        foreach (['banner_username', 'banner_dash_username'] as $k) {
            $request->merge([$k => ltrim(trim((string) $request->input($k)), '@')]);
        }

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
            // banner da collab: foto de celular passa de 2 MB facil, 5 MB de teto; o fpm aceita 512M
            'banner_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'banner_text' => 'nullable|string|max:150',
            'banner_username' => ['nullable', 'string', 'max:30', \Illuminate\Validation\Rule::exists('users', 'username')->where('creator_status', 'approved')],
            // dashboard opcional: mesmos campos, vazio usa o geral
            'banner_dash_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'banner_dash_text' => 'nullable|string|max:150',
            'banner_dash_username' => ['nullable', 'string', 'max:30', \Illuminate\Validation\Rule::exists('users', 'username')->where('creator_status', 'approved')],
            'banner_dash_image_remove' => 'nullable|boolean',
        ]);

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
