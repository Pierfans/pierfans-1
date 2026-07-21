<?php

namespace App\Services;

use App\Models\LedgerEntry;
use App\Models\PaymentTransaction;
use App\Models\PlatformSetting;
use App\Models\Referral;
use App\Models\Subscription;
use App\Models\User;

/**
 * Transforma uma transação PAGA em assinatura ativa: calcula o split (criador, afiliado do
 * assinante, afiliado do criador, plataforma), cria a Subscription e registra no ledger.
 *
 * Extraído do SuitPayWebhookController porque agora tem DOIS caminhos de pagamento — webhook
 * do SuitPay e saldo de carteira. Duplicar o split seria garantir que as duas versões
 * divergissem na primeira mudança de regra de comissão.
 *
 * NÃO engole exceção (o webhook engolia): quem paga com saldo precisa que a falha estoure
 * pra transação rolar de volta, senão o saldo é debitado e a assinatura não nasce.
 */
class SubscriptionActivation
{
    /**
     * Devolve a assinatura ATIVA do par usuário/criador — a que acabou de criar, ou a que já
     * existia. Use `wasRecentlyCreated` pra saber qual foi (webhook reenviado, duplo clique).
     */
    public static function fromTransaction(PaymentTransaction $transaction): Subscription
    {
        $user    = $transaction->user;
        $plan    = $transaction->plan;
        $creator = $transaction->creator;

        // Proteção contra dupla ativação: não cria a segunda, devolve a que está valendo.
        if ($user->hasActiveSubscription($creator->id)) {
            return $user->getActiveSubscription($creator->id);
        }

        $platformPercentage = PlatformSetting::getPlatformPercentage();

        $totalAmount    = $transaction->amount;
        $platformAmount = ($totalAmount * $platformPercentage) / 100;
        $creatorAmount  = $totalAmount - $platformAmount;

        // Comissão do afiliado que indicou o ASSINANTE (respeita o limite por assinante).
        $referrerAmount = 0;
        $referral = Referral::where('referred_user_id', $user->id)->first();
        if ($referral) {
            $limit = PlatformSetting::getAffiliateCommissionLimit();
            $canReceiveCommission = true;
            if ($limit > 0) {
                $already = Subscription::where('user_id', $user->id)->where('referrer_amount', '>', 0)->count();
                if ($already >= $limit) {
                    $canReceiveCommission = false;
                }
            }
            if ($canReceiveCommission) {
                $referrerAmount = ($totalAmount * PlatformSetting::getAffiliateCommissionPercentage()) / 100;
                $platformAmount = $platformAmount - $referrerAmount;
            }
        }

        // Comissão do afiliado que indicou o CRIADOR (incide em toda venda dele).
        $creatorAffiliateAmount = 0;
        $creatorAffiliateUserId = null;
        $creatorReferral = Referral::where('referred_user_id', $creator->id)->first();
        if ($creatorReferral) {
            $affiliate = User::find($creatorReferral->referrer_user_id);
            if ($affiliate) {
                $creatorAffiliateAmount = ($totalAmount * PlatformSetting::getAffiliateCommissionPercentage()) / 100;
                $creatorAffiliateUserId = $affiliate->id;
                $platformAmount = $platformAmount - $creatorAffiliateAmount;
            }
        }

        // Limpeza: desativa assinatura anterior já vencida deste par usuário/criador.
        Subscription::where('user_id', $user->id)
            ->where('creator_id', $creator->id)
            ->where('is_active', true)
            ->where('end_date', '<', now()->toDateString())
            ->update(['is_active' => false]);

        $subscription = Subscription::create([
            'user_id'                   => $user->id,
            'creator_id'                => $creator->id,
            'subscription_plan_id'      => $plan->id,
            'total_amount'              => $totalAmount,
            'platform_percentage'       => $platformPercentage,
            'platform_amount'           => $platformAmount,
            'referrer_amount'           => $referrerAmount,
            'creator_affiliate_amount'  => $creatorAffiliateAmount,
            'creator_affiliate_user_id' => $creatorAffiliateUserId,
            'creator_amount'            => $creatorAmount,
            'start_date'                => now()->toDateString(),
            'end_date'                  => now()->addDays($plan->duration_days)->toDateString(),
            'is_active'                 => true,
            'payment_method'            => $transaction->type, // 'pix', 'card' ou 'wallet'
        ]);

        $transaction->update(['subscription_id' => $subscription->id]);

        LedgerEntry::record([
            'entry_type'             => 'subscription_sale',
            'paid_with'              => LedgerEntry::paidWith($transaction),
            'payment_transaction_id' => $transaction->id,
            'gross_amount'           => $totalAmount,
            'suitpay_fee'            => LedgerEntry::saleFee($transaction),
            'creator_amount'         => $creatorAmount,
            'affiliate_amount'       => round($referrerAmount + $creatorAffiliateAmount, 2),
            'occurred_at'            => now(),
        ]);

        return $subscription;
    }
}
