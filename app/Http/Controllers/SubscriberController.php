<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriberController extends Controller
{
    /**
     * Lista os assinantes do criador
     */
    public function index()
    {
        // Verifica se é criador aprovado
        if (Auth::user()->creator_status !== 'approved') {
            return redirect()->route('dashboard')->with('error', 'Apenas criadores aprovados podem acessar esta página.');
        }

        $creator = Auth::user();
        
        // Desativa assinaturas expiradas
        Subscription::deactivateExpired();
        
        // Busca assinaturas do criador
        $subscriptions = Subscription::with(['user', 'plan'])
            ->where('creator_id', $creator->id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Conta assinantes ativos e inativos
        $activeCount = $subscriptions->where('is_active', true)
            ->where('end_date', '>=', now()->toDateString())
            ->count();
        
        $inactiveCount = $subscriptions->where(function($sub) {
            return $sub->is_active === false || $sub->end_date < now()->toDateString();
        })->count();

        return view('subscribers.index', [
            'active_count' => $activeCount,
            'inactive_count' => $inactiveCount,
            'subscriptions' => $subscriptions,
        ]);
    }
}
