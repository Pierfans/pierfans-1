<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\PostPurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserSubscriptionController extends Controller
{
    /**
     * Lista as assinaturas do usuário
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Desativa assinaturas expiradas
        Subscription::deactivateExpired();
        
        // Filtro: active, expired, ou ppv
        $filter = $request->get('filter', 'active'); // 'active', 'expired' ou 'ppv'
        
        $query = Subscription::with(['creator', 'plan'])
            ->where('user_id', $user->id);
        
        if ($filter === 'active') {
            // Assinaturas ativas
            $subscriptions = $query->where('is_active', true)
                ->where('end_date', '>=', now()->toDateString())
                ->orderBy('end_date', 'asc')
                ->get();
        } else {
            // Assinaturas expiradas
            $subscriptions = $query->where(function($q) {
                $q->where('is_active', false)
                  ->orWhere('end_date', '<', now()->toDateString());
            })
            ->orderBy('end_date', 'desc')
            ->get();
        }
        
        // Conta total de ativas e expiradas
        $activeCount = Subscription::where('user_id', $user->id)
            ->where('is_active', true)
            ->where('end_date', '>=', now()->toDateString())
            ->count();

        $expiredCount = Subscription::where('user_id', $user->id)
            ->where(function($q) {
                $q->where('is_active', false)
                  ->orWhere('end_date', '<', now()->toDateString());
            })
            ->count();

        // Compras de Conteúdo Único (PPV)
        $ppvPurchases = PostPurchase::where('user_id', $user->id)
            ->with(['post', 'creator'])
            ->orderBy('purchased_at', 'desc')
            ->get();

        $ppvCount = $ppvPurchases->count();

        return view('user-subscriptions.index', [
            'subscriptions'  => $subscriptions,
            'filter'         => $filter,
            'active_count'   => $activeCount,
            'expired_count'  => $expiredCount,
            'ppv_purchases'  => $ppvPurchases,
            'ppv_count'      => $ppvCount,
        ]);
    }
}
