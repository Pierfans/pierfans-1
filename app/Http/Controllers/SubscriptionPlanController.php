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

        // Pros dois percentuais aparecerem na tela: o bento pediu que ficasse "bem claro"
        // pra criadora quanto ela recebe em cada forma de pagamento (audio 16).
        $platformPercentage = \App\Models\PlatformSetting::getPlatformPercentage();
        $platformPercentageCard = \App\Models\PlatformSetting::getPlatformPercentageCard();

        return view('subscription-plans.index', compact('plans', 'platformPercentage', 'platformPercentageCard'));
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
            'accepts_pix' => 'nullable|boolean',
            'accepts_card' => 'nullable|boolean',
            'plans' => 'required|array',
            'plans.*.id' => 'required|exists:subscription_plans,id',
            'plans.*.price' => 'required|string',
            'plans.*.is_active' => 'nullable',
        ]);

        // Formas de pagamento aceitas (bento 21/09, audio 16). Pelo menos uma tem que ficar
        // ligada, senao ela nao vende nada e nem entende por que.
        $aceitaPix = $request->boolean('accepts_pix');
        $aceitaCartao = $request->boolean('accepts_card');

        if (!$aceitaPix && !$aceitaCartao) {
            return response()->json([
                'success' => false,
                'message' => 'Você precisa aceitar pelo menos uma forma de pagamento.',
            ], 400);
        }

        Auth::user()->update([
            'accepts_pix' => $aceitaPix,
            'accepts_card' => $aceitaCartao,
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
