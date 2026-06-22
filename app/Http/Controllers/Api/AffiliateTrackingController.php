<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AffiliateTrackingController extends Controller
{
    /**
     * Slug exclusivo permitido para consultas
     */
    const ALLOWED_AFFILIATE_SLUG = 'ZjOMZKiHDT';

    /**
     * Retorna todos os usuários associados a um afiliado específico
     * Rota exclusiva para o slug "ZjOMZKiHDT"
     */
    public function getAffiliateUsers(Request $request)
    {
        // Valida o payload
        $validator = Validator::make($request->all(), [
            'affiliate' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Payload inválido. O campo "affiliate" é obrigatório.',
                'errors' => $validator->errors(),
            ], 400);
        }

        $affiliateSlug = $request->input('affiliate');

        // Verifica se o slug é o permitido (exclusivo)
        if ($affiliateSlug !== self::ALLOWED_AFFILIATE_SLUG) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado. Esta API é exclusiva para o afiliado autorizado.',
            ], 403);
        }

        // Busca usuários cuja registration_url contém o slug do afiliado e tem tg_id
        // Procura URLs que contêm o slug do afiliado (formato: /a/{slug} ou /{slug}/) e o parâmetro tg_id
        $users = User::whereNotNull('registration_url')
            ->where(function ($query) use ($affiliateSlug) {
                // Busca por /a/{slug} ou /{slug}/ ou /{slug}? ou /{slug}& (para query params)
                $query->where('registration_url', 'like', '%/' . $affiliateSlug . '%');
            })
            ->where('registration_url', 'like', '%tg_id=%')
            ->get();

        $result = [];

        foreach ($users as $user) {
            // Extrai o tg_id da URL
            $tgId = $this->extractTgIdFromUrl($user->registration_url);

            // Se não encontrar tg_id, pula este usuário
            if (!$tgId) {
                continue;
            }

            // Conta assinaturas ativas
            // Assinatura ativa: is_active = true E end_date >= hoje
            $activeSubscriptionsCount = $user->subscriptions()
                ->where('is_active', true)
                ->where('end_date', '>=', now()->toDateString())
                ->count();

            // Conta assinaturas inativas
            // Assinatura inativa: todas as outras (is_active = false OU end_date < hoje)
            $inactiveSubscriptionsCount = $user->subscriptions()
                ->where(function ($query) {
                    $query->where('is_active', false)
                        ->orWhere('end_date', '<', now()->toDateString());
                })
                ->count();

            $result[] = [
                'tg_id' => $tgId,
                'name' => $user->name,
                'active_subscriptions_count' => $activeSubscriptionsCount,
                'inactive_subscriptions_count' => $inactiveSubscriptionsCount,
            ];
        }

        return response()->json([
            'success' => true,
            'affiliate' => $affiliateSlug,
            'total_users' => count($result),
            'users' => $result,
        ], 200);
    }

    /**
     * Extrai o valor do parâmetro tg_id de uma URL
     */
    private function extractTgIdFromUrl(string $url): ?string
    {
        // Parse da URL para obter os query parameters
        $queryString = parse_url($url, PHP_URL_QUERY);
        
        if (!$queryString) {
            return null;
        }

        // Converte query string em array
        parse_str($queryString, $params);

        // Retorna o tg_id se existir
        return $params['tg_id'] ?? null;
    }
}
