<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Models\Referral;
use App\Models\Subscription;
use App\Models\SubscriptionActivationLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminSubscriptionController extends Controller
{
    /**
     * Lista todas as assinaturas separadas por status
     */
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'all'); // all, active, pending

        // Assinaturas Ativas
        $activeSubscriptionsQuery = Subscription::with(['user', 'creator', 'plan', 'activatedByAdmin'])
            ->where('is_active', true)
            ->where('end_date', '>=', now()->toDateString())
            ->orderBy('created_at', 'desc');

        // Assinaturas Pendentes (não ativas ou expiradas)
        $pendingSubscriptionsQuery = Subscription::with(['user', 'creator', 'plan'])
            ->where(function ($query) {
                $query->where('is_active', false)
                      ->orWhere('end_date', '<', now()->toDateString());
            })
            ->orderBy('created_at', 'desc');

        // Aplica filtro
        if ($filter === 'active') {
            $activeSubscriptions = $activeSubscriptionsQuery->paginate(20)->appends($request->query());
            $pendingSubscriptions = null;
        } elseif ($filter === 'pending') {
            $activeSubscriptions = null;
            $pendingSubscriptions = $pendingSubscriptionsQuery->paginate(20)->appends($request->query());
        } else {
            $activeSubscriptions = $activeSubscriptionsQuery->paginate(20)->appends($request->query());
            $pendingSubscriptions = $pendingSubscriptionsQuery->paginate(20)->appends($request->query());
        }

        // Contadores
        $activeCount = Subscription::where('is_active', true)
            ->where('end_date', '>=', now()->toDateString())
            ->count();
        
        $pendingCount = Subscription::where(function ($query) {
                $query->where('is_active', false)
                      ->orWhere('end_date', '<', now()->toDateString());
            })
            ->count();

        return view('admin.subscriptions.index', compact(
            'activeSubscriptions',
            'pendingSubscriptions',
            'filter',
            'activeCount',
            'pendingCount'
        ));
    }

    /**
     * Mostra os detalhes de uma assinatura
     */
    public function show($id)
    {
        $subscription = Subscription::with([
            'user',
            'creator',
            'plan',
            'activatedByAdmin',
            'activationLogs.adminUser'
        ])->findOrFail($id);

        return view('admin.subscriptions.show', compact('subscription'));
    }

    /**
     * Ativa manualmente uma assinatura pendente
     */
    public function activate(Request $request, $id)
    {
        $subscription = Subscription::with(['user', 'creator', 'plan'])->findOrFail($id);

        // Verifica se já está ativa
        if ($subscription->is_active && $subscription->end_date >= now()->toDateString()) {
            return response()->json([
                'success' => false,
                'message' => 'Esta assinatura já está ativa.',
            ], 400);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            // Verifica se já existe assinatura ativa para este usuário e criador
            $existingActive = Subscription::where('user_id', $subscription->user_id)
                ->where('creator_id', $subscription->creator_id)
                ->where('is_active', true)
                ->where('end_date', '>=', now()->toDateString())
                ->where('id', '!=', $subscription->id)
                ->first();

            if ($existingActive) {
                // Desativa a assinatura existente
                $existingActive->update(['is_active' => false]);
            }

            // Carrega o plano se não estiver carregado
            if (!$subscription->relationLoaded('plan')) {
                $subscription->load('plan');
            }

            // Se a assinatura não tem valores calculados (comissões), calcula agora
            // Só recalcula se ambos os valores de comissão forem 0 ou null
            // Isso evita recalcular comissões que já foram processadas
            $hasReferrerCommission = $subscription->referrer_amount > 0;
            $hasCreatorAffiliateCommission = $subscription->creator_affiliate_amount > 0;
            
            // Se não tem nenhuma comissão calculada, calcula agora
            if (!$hasReferrerCommission && !$hasCreatorAffiliateCommission) {
                $this->calculateCommissions($subscription);
                $subscription->refresh(); // Recarrega para pegar os valores atualizados
            }

            // Calcula novas datas se necessário
            $newStartDate = $subscription->start_date < now()->toDateString() 
                ? now()->toDateString() 
                : $subscription->start_date;
            
            $newEndDate = $subscription->end_date < now()->toDateString() && $subscription->plan
                ? now()->addDays($subscription->plan->duration_days)->toDateString()
                : $subscription->end_date;

            // Ativa a assinatura
            $subscription->update([
                'is_active' => true,
                'activated_manually' => true,
                'activated_by_admin_id' => Auth::id(),
                'activated_at' => now(),
                'start_date' => $newStartDate,
                'end_date' => $newEndDate,
            ]);

            // Cria log de ativação
            SubscriptionActivationLog::create([
                'subscription_id' => $subscription->id,
                'admin_user_id' => Auth::id(),
                'reason' => $validated['reason'] ?? null,
            ]);

            DB::commit();

            Log::info('ADMIN SUBSCRIPTION ACTIVATED', [
                'admin_user_id' => Auth::id(),
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'creator_id' => $subscription->creator_id,
                'reason' => $validated['reason'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Assinatura ativada com sucesso!',
                'subscription' => $subscription->fresh(['user', 'creator', 'plan', 'activatedByAdmin']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('ADMIN SUBSCRIPTION ACTIVATE ERROR', [
                'admin_user_id' => Auth::id(),
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao ativar assinatura: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calcula comissões para uma assinatura (reutiliza lógica do CheckoutController)
     */
    private function calculateCommissions(Subscription $subscription)
    {
        $user = $subscription->user;
        $plan = $subscription->plan;
        $creator = $subscription->creator;

        // Obtém porcentagem da plataforma
        $platformPercentage = PlatformSetting::getPlatformPercentage();

        // Calcula valores base
        $totalAmount = $subscription->total_amount;
        $platformAmount = ($totalAmount * $platformPercentage) / 100;
        $creatorAmount = $totalAmount - $platformAmount;

        // Verifica se há indicação válida para este usuário
        $referral = Referral::where('referred_user_id', $user->id)->first();
        $referrerAmount = 0;

        // Se há indicação válida, verifica limite e calcula comissão do indicador
        if ($referral) {
            $affiliateCommissionLimit = PlatformSetting::getAffiliateCommissionLimit();

            $canReceiveCommission = true;
            if ($affiliateCommissionLimit > 0) {
                $existingCommissionsCount = Subscription::where('user_id', $user->id)
                    ->where('referrer_amount', '>', 0)
                    ->where('id', '!=', $subscription->id) // Exclui a própria assinatura
                    ->count();

                if ($existingCommissionsCount >= $affiliateCommissionLimit) {
                    $canReceiveCommission = false;
                }
            }

            if ($canReceiveCommission) {
                $affiliateCommissionPercentage = PlatformSetting::getAffiliateCommissionPercentage();
                $referrerAmount = ($totalAmount * $affiliateCommissionPercentage) / 100;
                $platformAmount = $platformAmount - $referrerAmount;
            }
        }

        // Verifica se o criador foi indicado por um afiliado e calcula comissão sobre a venda
        $creatorAffiliateAmount = 0;
        $creatorAffiliateUserId = null;
        
        $creatorReferral = Referral::where('referred_user_id', $creator->id)->first();
        if ($creatorReferral) {
            // Verifica se o afiliado ainda existe e está ativo
            $affiliate = User::find($creatorReferral->referrer_user_id);
            if ($affiliate) {
                // Calcula comissão do afiliado sobre a venda do criador
                $affiliateCommissionPercentage = PlatformSetting::getAffiliateCommissionPercentage();
                $creatorAffiliateAmount = ($totalAmount * $affiliateCommissionPercentage) / 100;
                $creatorAffiliateUserId = $affiliate->id;
                
                // Ajusta o valor da plataforma (desconta a comissão do afiliado sobre a venda do criador)
                $platformAmount = $platformAmount - $creatorAffiliateAmount;
            }
        }

        // Atualiza a assinatura com os valores calculados
        $subscription->update([
            'platform_percentage' => $platformPercentage,
            'platform_amount' => $platformAmount,
            'referrer_amount' => $referrerAmount,
            'creator_affiliate_amount' => $creatorAffiliateAmount,
            'creator_affiliate_user_id' => $creatorAffiliateUserId,
            'creator_amount' => $creatorAmount,
        ]);
    }
}
