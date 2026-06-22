<?php

namespace App\Http\Controllers;

use App\Models\AffiliateVisit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class AffiliateTrackingController extends Controller
{
    /**
     * Rastreia o clique do afiliado e redireciona
     * Esta rota captura /a/{slug} e rastreia a visita
     */
    public function track(Request $request, $slug)
    {
        // Busca o afiliado pelo slug
        $affiliate = User::where('slug', $slug)->first();
        
        // Se não encontrar o afiliado, redireciona para dashboard
        if (!$affiliate) {
            return redirect()->route('dashboard');
        }
        
        // Configura cookie e sessão de referral para captura no registro
        // Armazena o referrer_slug na sessão e no cookie (válido por 30 dias)
        // O cookie será usado no momento do cadastro para criar o registro de Referral
        session(['referrer_slug' => $slug]);
        
        // Captura a URL completa com query params para armazenar no cadastro
        $fullUrl = $request->fullUrl();
        session(['registration_url' => $fullUrl]);
        
        // Verifica se já foi rastreado nesta sessão (evita múltiplos registros)
        $sessionKey = 'affiliate_tracked_' . $slug;
        if (!$request->session()->has($sessionKey)) {
            // Captura dados do request
            $referer = $request->header('referer');
            $ipAddress = $request->ip();
            $userAgent = $request->header('user-agent');
            
            // Determina se é origem externa
            $isExternal = false;
            if ($referer) {
                $refererHost = parse_url($referer, PHP_URL_HOST);
                $appHost = parse_url(config('app.url'), PHP_URL_HOST);
                $isExternal = $refererHost !== $appHost;
            }
            
            // Captura parâmetros UTM
            $utmSource = $request->query('utm_source');
            $utmMedium = $request->query('utm_medium');
            $utmCampaign = $request->query('utm_campaign');
            $utmContent = $request->query('utm_content');
            $utmTerm = $request->query('utm_term');
            
            // Captura IDs de rastreamento
            $gclid = $request->query('gclid');
            $fbclid = $request->query('fbclid');
            
            // Salva o registro de visita
            AffiliateVisit::create([
                'affiliate_id' => $affiliate->id,
                'slug' => $slug,
                'is_external' => $isExternal,
                'referer' => $referer,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'utm_source' => $utmSource,
                'utm_medium' => $utmMedium,
                'utm_campaign' => $utmCampaign,
                'utm_content' => $utmContent,
                'utm_term' => $utmTerm,
                'gclid' => $gclid,
                'fbclid' => $fbclid,
            ]);
            
            // Marca na sessão que já foi rastreado
            $request->session()->put($sessionKey, true);
        }
        
        // Redireciona para o destino configurado (por enquanto, dashboard)
        // TODO: Adicionar campo de destino configurável no cadastro do afiliado
        // Anexa cookies ao redirect para garantir que sejam enviados corretamente
        return redirect()
            ->route('dashboard')
            ->cookie('referrer_slug', $slug, 60 * 24 * 30) // 30 dias
            ->cookie('registration_url', $fullUrl, 60 * 24 * 30); // 30 dias
    }

    /**
     * Rastreia o clique do afiliado com redirecionamento para criador específico
     * Esta rota captura /a/{referrerSlug}/{creatorSlug} e rastreia a visita
     * Depois redireciona para o perfil do criador
     */
    public function trackWithCreator(Request $request, $referrerSlug, $creatorSlug)
    {
        // Busca o afiliado pelo slug
        $affiliate = User::where('slug', $referrerSlug)->first();
        
        // Se não encontrar o afiliado, redireciona para o perfil do criador mesmo assim
        if (!$affiliate) {
            return redirect()->route('profile.show', $creatorSlug);
        }

        // Busca o criador pelo slug
        $creator = User::where('slug', $creatorSlug)->first();
        
        // Se não encontrar o criador, redireciona para dashboard
        if (!$creator) {
            return redirect()->route('dashboard');
        }
        
        // Configura cookie e sessão de referral para captura no registro
        // Armazena o referrer_slug na sessão e no cookie (válido por 30 dias)
        session(['referrer_slug' => $referrerSlug]);
        
        // Armazena também o creator_slug para redirecionar após cadastro (se usuário não autenticado)
        $creatorSlugSession = null;
        if (!\Illuminate\Support\Facades\Auth::check()) {
            session(['creator_slug' => $creatorSlug]);
            $creatorSlugSession = $creatorSlug;
        }
        
        // Captura a URL completa com query params para armazenar no cadastro
        $fullUrl = $request->fullUrl();
        session(['registration_url' => $fullUrl]);
        
        // Verifica se já foi rastreado nesta sessão (evita múltiplos registros)
        $sessionKey = 'affiliate_tracked_' . $referrerSlug . '_' . $creatorSlug;
        if (!$request->session()->has($sessionKey)) {
            // Captura dados do request
            $referer = $request->header('referer');
            $ipAddress = $request->ip();
            $userAgent = $request->header('user-agent');
            
            // Determina se é origem externa
            $isExternal = false;
            if ($referer) {
                $refererHost = parse_url($referer, PHP_URL_HOST);
                $appHost = parse_url(config('app.url'), PHP_URL_HOST);
                $isExternal = $refererHost !== $appHost;
            }
            
            // Captura parâmetros UTM
            $utmSource = $request->query('utm_source');
            $utmMedium = $request->query('utm_medium');
            $utmCampaign = $request->query('utm_campaign');
            $utmContent = $request->query('utm_content');
            $utmTerm = $request->query('utm_term');
            
            // Captura IDs de rastreamento
            $gclid = $request->query('gclid');
            $fbclid = $request->query('fbclid');
            
            // Salva o registro de visita
            AffiliateVisit::create([
                'affiliate_id' => $affiliate->id,
                'slug' => $referrerSlug,
                'is_external' => $isExternal,
                'referer' => $referer,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'utm_source' => $utmSource,
                'utm_medium' => $utmMedium,
                'utm_campaign' => $utmCampaign,
                'utm_content' => $utmContent,
                'utm_term' => $utmTerm,
                'gclid' => $gclid,
                'fbclid' => $fbclid,
            ]);
            
            // Marca na sessão que já foi rastreado
            $request->session()->put($sessionKey, true);
        }
        
        // Redireciona para o perfil do criador
        // Anexa cookies ao redirect para garantir que sejam enviados corretamente
        $redirect = redirect()->route('profile.show', $creatorSlug)
            ->cookie('referrer_slug', $referrerSlug, 60 * 24 * 30) // 30 dias
            ->cookie('registration_url', $fullUrl, 60 * 24 * 30); // 30 dias
            
        if ($creatorSlugSession) {
            $redirect->cookie('creator_slug', $creatorSlugSession, 60 * 24 * 30); // 30 dias
        }
        
        return $redirect;
    }
}
