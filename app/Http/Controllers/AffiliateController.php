<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\PlatformSetting;
use App\Models\Subscription;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AffiliateController extends Controller
{
    /**
     * Mostra a tela principal de afiliados
     */
    public function index()
    {
        $user = Auth::user();

        // Cards de resumo
        $activeAffiliatesCount = $user->getActiveAffiliatesCount();
        $totalReferralsCount = $user->getTotalReferralsCount();
        $availableBalance = $user->getAffiliateAvailableBalance();
        $pendingBalance = $user->getAffiliatePendingBalance();

        // Listagem de indicações (comissões geradas)
        $commissions = $this->getCommissionsList($user);

        // Configurações de saque
        $minWithdrawAmount = PlatformSetting::getMinWithdrawAmount();
        $dailyWithdrawLimit = PlatformSetting::getDailyWithdrawLimit();

        // Contas bancárias
        $bankAccounts = BankAccount::where('user_id', $user->id)
            ->orderBy('is_primary', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Extrato (últimas 5 transações) - apenas saques do afiliado
        $extract = Withdrawal::where('user_id', $user->id)
            ->where('type', 'affiliate')
            ->with('bankAccount')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Link de indicação do afiliado
        $affiliateLink = config('app.url') . '/a/' . $user->slug;
        
        // Porcentagem de comissão configurada
        $commissionPercentage = PlatformSetting::getAffiliateCommissionPercentage();

        return view('affiliates.index', compact(
            'activeAffiliatesCount',
            'totalReferralsCount',
            'availableBalance',
            'pendingBalance',
            'commissions',
            'minWithdrawAmount',
            'dailyWithdrawLimit',
            'bankAccounts',
            'extract',
            'affiliateLink',
            'commissionPercentage'
        ));
    }

    /**
     * Mostra o extrato completo do afiliado
     */
    public function extract(Request $request)
    {
        $user = Auth::user();

        // Filtros
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $status = $request->input('status');
        $commissionType = $request->input('commission_type', 'all'); // 'all', 'subscription', 'creator_sale'

        // Busca saques (withdrawals) do afiliado
        $withdrawalsQuery = Withdrawal::where('user_id', $user->id)
            ->where('type', 'affiliate')
            ->with('bankAccount');

        // Aplica filtros de data e status para saques
        if ($startDate) {
            $withdrawalsQuery->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $withdrawalsQuery->whereDate('created_at', '<=', $endDate);
        }
        if ($status && $status !== 'all') {
            $withdrawalsQuery->where('status', $status);
        }

        $withdrawals = $withdrawalsQuery->get();

        // Busca comissões do afiliado
        $commissionsQuery = $user->affiliateCommissions()
            ->with(['creator', 'plan', 'user']);

        // Aplica filtros de data para comissões
        if ($startDate) {
            $commissionsQuery->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $commissionsQuery->whereDate('created_at', '<=', $endDate);
        }

        // Filtro por tipo de comissão
        if ($commissionType === 'subscription') {
            // Apenas comissões quando indicado assina
            $commissionsQuery->where('referrer_amount', '>', 0);
        } elseif ($commissionType === 'creator_sale') {
            // Apenas comissões quando criador indicado vende
            $commissionsQuery->where('creator_affiliate_amount', '>', 0);
        }
        // Se 'all', não aplica filtro adicional

        $commissions = $commissionsQuery->orderBy('created_at', 'desc')->get();

        $pixReleaseDays = PlatformSetting::getPixReleaseDays();
        $cardReleaseDays = PlatformSetting::getCardReleaseDays();

        // Formata saques para exibição
        $withdrawalTransactions = $withdrawals->map(function ($withdrawal) {
            $statusLabels = [
                'pending' => 'Pendente',
                'transferred' => 'Completa',
                'rejected' => 'Reprovado',
            ];
            
            return [
                'id' => 'with_' . $withdrawal->id,
                'type' => 'withdrawal',
                'type_label' => 'Saque',
                'amount' => $withdrawal->amount,
                'status' => $withdrawal->status,
                'status_label' => $statusLabels[$withdrawal->status] ?? $withdrawal->status,
                'date' => $withdrawal->created_at, // Mantém como objeto Carbon para formatação na view
                'date_string' => $withdrawal->created_at->format('d M, Y, h:i A'), // String formatada para JavaScript
                'data' => $withdrawal,
            ];
        });

        // Formata comissões para exibição
        $commissionTransactions = $commissions->map(function ($subscription) use ($pixReleaseDays, $cardReleaseDays) {
            // Calcula data de liberação
            $releaseDate = $subscription->payment_method === 'pix' 
                ? ($pixReleaseDays == 0 ? now() : $subscription->created_at->copy()->addDays($pixReleaseDays))
                : ($cardReleaseDays == 0 ? now() : $subscription->created_at->copy()->addDays($cardReleaseDays));
            
            $isReleased = now() >= $releaseDate;
            $status = $isReleased ? 'Liberado' : 'Bloqueado';
            
            // Calcula valor total da comissão
            $totalCommission = (float) $subscription->referrer_amount + (float) $subscription->creator_affiliate_amount;
            
            // Determina o tipo de comissão
            $commissionType = 'Assinatura';
            if ($subscription->creator_affiliate_amount > 0) {
                $commissionType = 'Venda do Criador';
            }
            
            return [
                'id' => 'comm_' . $subscription->id,
                'type' => 'commission',
                'type_label' => 'Comissão',
                'amount' => $totalCommission,
                'status' => strtolower($status),
                'status_label' => $status,
                'date' => $subscription->created_at, // Mantém como objeto Carbon para formatação na view
                'date_string' => $subscription->created_at->format('d M, Y, h:i A'), // String formatada para JavaScript
                'data' => $subscription,
                'commission_type' => $commissionType,
                'referrer_amount' => $subscription->referrer_amount,
                'creator_affiliate_amount' => $subscription->creator_affiliate_amount,
                'plan_name' => $subscription->plan->name ?? 'N/A',
                'creator_name' => $subscription->creator->name ?? 'N/A',
            ];
        });

        // Combina e ordena todas as transações por data
        $allTransactions = $withdrawalTransactions->concat($commissionTransactions)
            ->sortByDesc('date')
            ->values();

        // Prepara dados para JavaScript (apenas saques)
        $transactionsForJs = $withdrawalTransactions->map(function ($t) {
            $data = $t['data'];
            return [
                'id' => $t['id'],
                'type' => $t['type'],
                'amount' => $data->amount,
                'status' => $data->status,
                'created_at' => $data->created_at->toISOString(),
                'processed_at' => $data->processed_at ? $data->processed_at->toISOString() : null,
                'bank_account' => $data->bankAccount ? [
                    'bank_name' => $data->bankAccount->bank_name,
                    'pix_key_type' => $data->bankAccount->pix_key_type,
                    'pix_key' => $data->bankAccount->pix_key,
                ] : null,
            ];
        });

        // Prepara dados completos para JavaScript (incluindo comissões)
        $allTransactionsForJs = [];
        foreach ($allTransactions as $t) {
            $dateValue = $t['date'];
            if (is_object($dateValue)) {
                if (method_exists($dateValue, 'toISOString')) {
                    $dateValue = $dateValue->toISOString();
                } elseif (method_exists($dateValue, 'format')) {
                    $dateValue = $dateValue->format('c');
                } else {
                    $dateValue = (string) $dateValue;
                }
            }
            
            $result = [
                'id' => $t['id'],
                'type' => $t['type'],
                'type_label' => $t['type_label'],
                'amount' => $t['amount'],
                'status' => $t['status'],
                'status_label' => $t['status_label'],
                'date' => $dateValue,
                'date_string' => $t['date_string'] ?? $dateValue,
                'commission_type' => $t['commission_type'] ?? null,
                'plan_name' => $t['plan_name'] ?? null,
                'creator_name' => $t['creator_name'] ?? null,
            ];
            
            if ($t['type'] === 'withdrawal' && isset($t['data'])) {
                $withdrawal = $t['data'];
                $result['data'] = [
                    'amount' => $withdrawal->amount,
                    'status' => $withdrawal->status,
                    'created_at' => $withdrawal->created_at->toISOString(),
                    'processed_at' => $withdrawal->processed_at ? $withdrawal->processed_at->toISOString() : null,
                    'bank_account' => $withdrawal->bankAccount ? [
                        'bank_name' => $withdrawal->bankAccount->bank_name,
                        'pix_key_type' => $withdrawal->bankAccount->pix_key_type,
                        'pix_key' => $withdrawal->bankAccount->pix_key,
                    ] : null,
                ];
            } else {
                $result['data'] = null;
            }
            
            $allTransactionsForJs[] = $result;
        }

        $pendingBalance = $user->getAffiliatePendingBalance();

        return view('affiliates.extract', [
            'transactions' => $allTransactions,
            'transactionsForJs' => $transactionsForJs,
            'allTransactionsForJs' => $allTransactionsForJs,
            'pendingBalance' => $pendingBalance,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'status' => $status ?? 'all',
            'commissionType' => $commissionType,
        ]);
    }

    /**
     * Mostra a página de saque do afiliado
     * Reutiliza a mesma lógica do criador
     */
    public function showWithdraw()
    {
        $user = Auth::user();

        // Saldos do afiliado
        $availableBalance = $user->getAffiliateAvailableBalance();
        $pendingBalance = $user->getAffiliatePendingBalance();

        // Configurações de saque
        $minWithdrawAmount = PlatformSetting::getMinWithdrawAmount();
        $dailyWithdrawLimit = PlatformSetting::getDailyWithdrawLimit();

        // Extrato (últimas 5 transações) - apenas saques do afiliado
        $extract = Withdrawal::where('user_id', $user->id)
            ->where('type', 'affiliate')
            ->with('bankAccount')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Contas bancárias
        $bankAccounts = BankAccount::where('user_id', $user->id)
            ->orderBy('is_primary', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('affiliates.withdraw', compact('availableBalance', 'pendingBalance', 'extract', 'bankAccounts', 'minWithdrawAmount', 'dailyWithdrawLimit'));
    }

    /**
     * Cria uma solicitação de saque do afiliado
     * Reutiliza a mesma lógica do criador
     */
    public function storeWithdraw(Request $request)
    {
        $user = Auth::user();

        $minWithdrawAmount = PlatformSetting::getMinWithdrawAmount();
        
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:' . $minWithdrawAmount, 'max:999999.99'],
            'bank_account_id' => 'required|exists:bank_accounts,id',
        ], [
            'amount.min' => 'O valor mínimo para saque é de R$ ' . number_format($minWithdrawAmount, 2, ',', '.'),
            'bank_account_id.required' => 'Selecione uma conta bancária',
            'bank_account_id.exists' => 'Conta bancária inválida',
        ]);

        // Verifica se a conta pertence ao usuário
        $bankAccount = BankAccount::where('id', $validated['bank_account_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Cria a solicitação de saque (teto diário + taxa avaliados dentro da transação)
        DB::beginTransaction();
        try {
            $assess = Withdrawal::assessDailyFee($user->id, 'affiliate', (float) $validated['amount']);
            if (!$assess['allowed']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "Você atingiu o limite de {$assess['limit']} saques por dia.",
                ], 400);
            }
            $fee = $assess['fee'];

            // Saldo precisa cobrir o valor + a taxa do saque
            $availableBalance = $user->getAffiliateAvailableBalance();
            if ($validated['amount'] + $fee > $availableBalance) {
                DB::rollBack();
                $msg = $fee > 0
                    ? 'Saldo insuficiente para o valor + a taxa de R$ ' . number_format($fee, 2, ',', '.') . ' deste saque.'
                    : 'Valor solicitado excede o saldo disponível para saque.';
                return response()->json(['success' => false, 'message' => $msg], 400);
            }

            $withdrawal = Withdrawal::create([
                'user_id' => $user->id,
                'type' => 'affiliate',
                'bank_account_id' => $bankAccount->id,
                'amount' => $validated['amount'],
                'fee' => $fee,
                'status' => 'pending',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Solicitação de saque criada com sucesso!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar solicitação de saque.',
            ], 500);
        }
    }

    /**
     * Obtém detalhes de uma comissão específica
     */
    public function getCommissionDetails($subscriptionId)
    {
        $user = Auth::user();

        // Busca a subscription
        $subscription = Subscription::with(['creator', 'plan', 'user.referral'])
            ->findOrFail($subscriptionId);

        // Verifica se esta subscription gerou comissão para este afiliado
        // Pode ser referrer_amount (quando indicado assina) ou creator_affiliate_amount (quando criador indicado vende)
        $hasCommission = false;
        $referral = $subscription->user->referral ?? null;
        
        // Verifica se é comissão quando indicado assina
        if ($referral && $referral->referrer_user_id === $user->id && $subscription->referrer_amount > 0) {
            $hasCommission = true;
        }
        
        // Verifica se é comissão quando criador indicado vende
        if ($subscription->creator_affiliate_user_id === $user->id && $subscription->creator_affiliate_amount > 0) {
            $hasCommission = true;
        }
        
        if (!$hasCommission) {
            return response()->json([
                'success' => false,
                'message' => 'Comissão não encontrada.',
            ], 404);
        }

        // Calcula status do valor
        $pixReleaseDays = PlatformSetting::getPixReleaseDays();
        $cardReleaseDays = PlatformSetting::getCardReleaseDays();
        
        $releaseDate = $subscription->payment_method === 'pix' 
            ? ($pixReleaseDays == 0 ? now() : $subscription->created_at->addDays($pixReleaseDays))
            : ($cardReleaseDays == 0 ? now() : $subscription->created_at->addDays($cardReleaseDays));
        
        $isReleased = now() >= $releaseDate;
        $status = $isReleased ? 'Liberado' : 'Bloqueado';
        $releaseDateFormatted = $releaseDate->format('d/m/Y');

        // Obtém a porcentagem de comissão configurada
        $commissionPercentage = PlatformSetting::getAffiliateCommissionPercentage();
        
        // Calcula valor total da comissão
        $totalCommission = (float) $subscription->referrer_amount + (float) $subscription->creator_affiliate_amount;
        
        // Determina o tipo de comissão
        $commissionType = 'Assinatura do Indicado';
        if ($subscription->creator_affiliate_amount > 0) {
            $commissionType = 'Venda do Criador Indicado';
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'subscription_date' => $subscription->created_at->format('d/m/Y H:i'),
                'plan_name' => $subscription->plan->name ?? 'N/A',
                'plan_price' => 'R$ ' . number_format($subscription->plan->price ?? 0, 2, ',', '.'),
                'creator_name' => $subscription->creator->name ?? 'N/A',
                'commission_percentage' => number_format($commissionPercentage, 2, ',', '.') . '%',
                'commission_amount' => 'R$ ' . number_format($totalCommission, 2, ',', '.'),
                'commission_type' => $commissionType,
                'referrer_amount' => $subscription->referrer_amount > 0 ? 'R$ ' . number_format($subscription->referrer_amount, 2, ',', '.') : null,
                'creator_affiliate_amount' => $subscription->creator_affiliate_amount > 0 ? 'R$ ' . number_format($subscription->creator_affiliate_amount, 2, ',', '.') : null,
                'status' => $status,
                'release_date' => $releaseDateFormatted,
                'payment_method' => strtoupper($subscription->payment_method),
            ],
        ]);
    }

    /**
     * Obtém lista de comissões do afiliado
     */
    private function getCommissionsList($user)
    {
        $commissions = $user->affiliateCommissions()
            ->with(['creator', 'plan', 'user.referral'])
            ->orderBy('created_at', 'desc')
            ->get();

        $pixReleaseDays = PlatformSetting::getPixReleaseDays();
        $cardReleaseDays = PlatformSetting::getCardReleaseDays();

        return $commissions->map(function ($subscription) use ($pixReleaseDays, $cardReleaseDays) {
            // Calcula data de liberação
            $releaseDate = $subscription->payment_method === 'pix' 
                ? ($pixReleaseDays == 0 ? now() : $subscription->created_at->addDays($pixReleaseDays))
                : ($cardReleaseDays == 0 ? now() : $subscription->created_at->addDays($cardReleaseDays));
            
            $isReleased = now() >= $releaseDate;
            
            // Verifica se foi sacado
            $withdrawals = Withdrawal::where('user_id', $subscription->user->referral->referrer_user_id ?? $subscription->creator_affiliate_user_id)
                ->whereIn('status', ['pending', 'transferred'])
                ->sum('amount');
            
            // Status simplificado para exibição
            $status = $isReleased ? 'Liberado' : 'Bloqueado';
            if ($withdrawals > 0) {
                $status = 'Pago'; // Simplificado - na prática seria mais complexo
            }

            // Calcula valor total da comissão (referrer_amount + creator_affiliate_amount)
            $totalCommission = (float) $subscription->referrer_amount + (float) $subscription->creator_affiliate_amount;
            
            // Determina o tipo de comissão para exibição
            $commissionType = 'Assinatura';
            if ($subscription->creator_affiliate_amount > 0) {
                $commissionType = 'Venda do Criador';
            }

            return [
                'id' => $subscription->id,
                'date' => $subscription->created_at->format('d/m/Y'),
                'value' => 'R$ ' . number_format($totalCommission, 2, ',', '.'),
                'plan_name' => $subscription->plan->name ?? 'N/A',
                'creator_name' => $subscription->creator->name ?? 'N/A',
                'status' => $status,
                'release_date' => $releaseDate->format('d/m/Y'),
                'is_released' => $isReleased,
                'commission_type' => $commissionType,
                'referrer_amount' => $subscription->referrer_amount,
                'creator_affiliate_amount' => $subscription->creator_affiliate_amount,
            ];
        });
    }

    /**
     * Retorna label do status
     */
    private function getStatusLabel($status): string
    {
        return match($status) {
            'pending' => 'Pendente',
            'transferred' => 'Transferido',
            'rejected' => 'Recusado',
            default => $status,
        };
    }

    /**
     * Retorna classe CSS do status
     */
    private function getStatusClass($status): string
    {
        return match($status) {
            'pending' => 'text-yellow-600',
            'transferred' => 'text-green-600',
            'rejected' => 'text-red-600',
            default => '',
        };
    }
}
