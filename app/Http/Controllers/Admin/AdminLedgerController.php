<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LedgerEntry;
use App\Models\User;
use Illuminate\Http\Request;

class AdminLedgerController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to] = $this->period($request);
        $tipo = in_array($request->get('tipo'), ['subscription_sale', 'ppv_sale', 'cashout']) ? $request->get('tipo') : 'todos';

        // Filtro por pessoa (chips): casa criador da venda OU solicitante do saque.
        $personTerms = collect((array) $request->get('pessoa'))
            ->map(fn ($t) => trim((string) $t))->filter()->unique()->values();
        $personIds = null;
        if ($personTerms->isNotEmpty()) {
            $personIds = User::where(function ($q) use ($personTerms) {
                foreach ($personTerms as $t) {
                    $q->orWhere('name', 'like', "%{$t}%")->orWhere('username', 'like', "%{$t}%");
                }
            })->pluck('id');
        }

        $base = LedgerEntry::whereBetween('occurred_at', [$from . ' 00:00:00', $to . ' 23:59:59']);

        $sales    = (clone $base)->whereIn('entry_type', ['subscription_sale', 'ppv_sale']);
        $cashouts = (clone $base)->where('entry_type', 'cashout');

        $grossSales    = (float) (clone $sales)->sum('gross_amount');
        $creatorPaid   = (float) (clone $sales)->sum('creator_amount');
        $affiliatePaid = (float) (clone $sales)->sum('affiliate_amount');
        $feeIn         = (float) (clone $sales)->sum('suitpay_fee');
        $feeOut        = (float) (clone $cashouts)->sum('suitpay_fee');
        $withdrawFee   = (float) (clone $cashouts)->sum('withdraw_fee');
        $cashoutTotal  = (float) (clone $cashouts)->sum('gross_amount');

        $subTotal = (float) (clone $base)->where('entry_type', 'subscription_sale')->sum('gross_amount');
        $ppvTotal = (float) (clone $base)->where('entry_type', 'ppv_sale')->sum('gross_amount');

        // Receita líquida da plataforma = parte da plataforma nas vendas, menos as taxas do
        // SuitPay (entrada + saída), mais a taxa de saque cobrada do usuário (receita).
        $platformNet = $grossSales - $creatorPaid - $affiliatePaid - $feeIn - $feeOut + $withdrawFee;

        // Cards = visão geral do período; os filtros de tipo/pessoa afetam só a lista de movimentos.
        $listing = (clone $base);
        if ($tipo !== 'todos') {
            $listing->where('entry_type', $tipo);
        }
        if ($personIds !== null) {
            $listing->where(function ($q) use ($personIds) {
                $q->whereHas('paymentTransaction', fn ($t) => $t->whereIn('creator_id', $personIds))
                  ->orWhereHas('withdrawal', fn ($w) => $w->whereIn('user_id', $personIds));
            });
        }

        if ($request->get('export') === 'csv') {
            return $this->exportCsv((clone $listing), $from, $to);
        }

        $entries = $listing->with(['paymentTransaction.creator', 'paymentTransaction.user', 'withdrawal.user'])
            ->latest('occurred_at')->paginate(50)->appends($request->query());

        $allCreators = User::where('creator_status', 'approved')->orderBy('name')->get(['name', 'username']);

        return view('admin.ledger.index', compact(
            'entries', 'from', 'to', 'tipo', 'personTerms', 'allCreators',
            'grossSales', 'creatorPaid', 'affiliatePaid', 'feeIn', 'feeOut', 'withdrawFee',
            'cashoutTotal', 'platformNet', 'subTotal', 'ppvTotal'
        ));
    }

    private function period(Request $request): array
    {
        $from = $request->get('from') ?: now()->startOfMonth()->toDateString();
        $to   = $request->get('to') ?: now()->toDateString();
        return [$from, $to];
    }

    private function exportCsv($query, string $from, string $to)
    {
        $rows = $query->with(['paymentTransaction.creator', 'paymentTransaction.user', 'withdrawal.user'])->latest('occurred_at')->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Data', 'Tipo', 'Pessoa', 'Comprador', 'Id SuitPay', 'Bruto', 'Taxa SuitPay', 'Liquido SuitPay', 'Criador', 'Afiliado', 'Plataforma (liq)']);
            foreach ($rows as $e) {
                if ($e->entry_type === 'cashout') {
                    // Saque: plataforma embolsa a taxa cobrada do usuário e paga o custo do SuitPay
                    $platform = round($e->withdraw_fee - $e->suitpay_fee, 2);
                    // SuitPay debita o valor + a taxa da conta
                    $liquidoSuitpay = -round($e->gross_amount + $e->suitpay_fee, 2);
                } else {
                    $platform = $e->gross_amount - $e->creator_amount - $e->affiliate_amount - $e->suitpay_fee;
                    // Venda: o que de fato cai no saldo SuitPay é o bruto menos a taxa
                    $liquidoSuitpay = round($e->gross_amount - $e->suitpay_fee, 2);
                }
                if ($e->entry_type === 'cashout') {
                    $u = $e->withdrawal?->user;
                    $pessoa = $u ? $u->name . ' (@' . $u->username . ')' : '';
                    $comprador = '';
                } else {
                    $c = $e->paymentTransaction?->creator;
                    $pessoa = $c ? $c->name . ' (@' . $c->username . ')' : '';
                    $comprador = $e->paymentTransaction?->user?->name ?? '';
                }
                fputcsv($out, [
                    $e->occurred_at->format('d/m/Y H:i'),
                    $e->typeLabel(),
                    $pessoa,
                    $comprador,
                    $e->suitpayControlId() ?? '',
                    number_format($e->gross_amount, 2, ',', '.'),
                    number_format($e->suitpay_fee, 2, ',', '.'),
                    number_format($liquidoSuitpay, 2, ',', '.'),
                    number_format($e->creator_amount, 2, ',', '.'),
                    number_format($e->affiliate_amount, 2, ',', '.'),
                    number_format(round($platform, 2), 2, ',', '.'),
                ]);
            }
            fclose($out);
        }, "fluxo_caixa_{$from}_a_{$to}.csv");
    }
}
