<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionPlanController extends Controller
{
    /**
     * Mostra a página de configuração de planos
     */
    public function index()
    {
        // Verifica se é criador aprovado
        if (Auth::user()->creator_status !== 'approved') {
            return redirect()->route('dashboard')->with('error', 'Você precisa ser um criador aprovado para configurar planos.');
        }

        $plans = SubscriptionPlan::where('user_id', Auth::id())
            ->orderBy('duration_days')
            ->get();

        // Se não existir planos, cria os padrões
        if ($plans->isEmpty()) {
            $defaultPlans = [
                ['name' => '1 mês de assinatura', 'duration_days' => 30],
                ['name' => '3 meses de assinatura', 'duration_days' => 90],
                ['name' => '6 meses de assinatura', 'duration_days' => 180],
                ['name' => '1 ano de assinatura', 'duration_days' => 365],
            ];

            foreach ($defaultPlans as $plan) {
                SubscriptionPlan::create([
                    'user_id' => Auth::id(),
                    'name' => $plan['name'],
                    'duration_days' => $plan['duration_days'],
                    'price' => 0,
                    'is_active' => false,
                ]);
            }

            $plans = SubscriptionPlan::where('user_id', Auth::id())
                ->orderBy('duration_days')
                ->get();
        }

        return view('subscription-plans.index', compact('plans'));
    }

    /**
     * Salva ou atualiza os planos
     */
    public function store(Request $request)
    {
        // Verifica se é criador aprovado
        if (Auth::user()->creator_status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Você precisa ser um criador aprovado para configurar planos.',
            ], 403);
        }

        $request->validate([
            'plans' => 'required|array',
            'plans.*.id' => 'required|exists:subscription_plans,id',
            'plans.*.price' => 'required|string',
            'plans.*.is_active' => 'nullable',
        ]);

        foreach ($request->plans as $planData) {
            $plan = SubscriptionPlan::where('id', $planData['id'])
                ->where('user_id', Auth::id())
                ->firstOrFail();

            // Remove "R$" e espaços, substitui ponto por nada e vírgula por ponto
            $price = str_replace(['R$', ' '], '', $planData['price']);
            $price = str_replace('.', '', $price);
            $price = str_replace(',', '.', $price);
            $price = (float) $price;

            $plan->update([
                'price' => $price,
                'is_active' => isset($planData['is_active']) && ($planData['is_active'] == '1' || $planData['is_active'] === true),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Planos salvos com sucesso!',
        ]);
    }
}
