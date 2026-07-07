<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LedgerEntry;
use Illuminate\Http\Request;

class AdminLedgerController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to] = $this->period($request);
        $tipo = in_array($request->get('tipo'), ['subscription_sale', 'ppv_sale', 'cashout']) ? $request->get('tipo') : 'todos';

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

        // Caixa "agora" = all-time, NÃO sofre filtro de período nem de tipo (é o estado atual do caixa).
        // Saldo em conta = o que de fato entra/sai da conta SuitPay: venda cai (bruto - taxa entrada),
        // saque sai (bruto + taxa saída). Espelha o saldo real do painel (a menos do erro da taxa estimada de PIX).
        $allSales = LedgerEntry::whereIn('entry_type', ['subscription_sale', 'ppv_sale']);
        $allCash  = LedgerEntry::where('entry_type', 'cashout');
        $accountBalance = ((float) (clone $allSales)->sum('gross_amount') - (float) (clone $allSales)->sum('suitpay_fee'))
                        - ((float) (clone $allCash)->sum('gross_amount') + (float) (clone $allCash)->sum('suitpay_fee'));
        // Caixa da plataforma = receita líquida acumulada (mesma fórmula do card, sem filtro).
        $platformCash = (float) (clone $allSales)->sum('gross_amount')
                      - (float) (clone $allSales)->sum('creator_amount')
                      - (float) (clone $allSales)->sum('affiliate_amount')
                      - (float) (clone $allSales)->sum('suitpay_fee')
                      - (float) (clone $allCash)->sum('suitpay_fee')
                      + (float) (clone $allCash)->sum('withdraw_fee');
        // Ponte: a diferença é o que ainda é dos criadores/afiliados (ganharam mas não sacaram).
        $owedToCreators = $accountBalance - $platformCash;
        // Desde quando o fluxo é registrado (o "saldo" abaixo é fluxo desde essa data, NÃO o saldo real do SuitPay:
        // ignora a abertura da conta e retiradas manuais que não passam pelo gateway).
        $ledgerStart = LedgerEntry::min('occurred_at');

        // Cards = visão geral do período; o filtro de tipo afeta só a lista de movimentos.
        $listing = (clone $base);
        if ($tipo !== 'todos') {
            $listing->where('entry_type', $tipo);
        }

        if ($request->get('export') === 'csv') {
            return $this->exportCsv((clone $listing), $from, $to);
        }

        $entries = $listing->with('withdrawal.user')->latest('occurred_at')->paginate(50)->appends($request->query());

        return view('admin.ledger.index', compact(
            'entries', 'from', 'to', 'tipo',
            'grossSales', 'creatorPaid', 'affiliatePaid', 'feeIn', 'feeOut', 'withdrawFee',
            'cashoutTotal', 'platformNet', 'subTotal', 'ppvTotal',
            'accountBalance', 'platformCash', 'owedToCreators', 'ledgerStart'
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
        $rows = $query->with(['paymentTransaction', 'withdrawal.user'])->latest('occurred_at')->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Data', 'Tipo', 'Solicitante', 'Id SuitPay', 'Bruto', 'Taxa SuitPay', 'Liquido SuitPay', 'Criador', 'Afiliado', 'Plataforma (liq)']);
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
                $solicitante = $e->entry_type === 'cashout' && $e->withdrawal?->user
                    ? $e->withdrawal->user->name . ' (@' . $e->withdrawal->user->username . ')'
                    : '';
                fputcsv($out, [
                    $e->occurred_at->format('d/m/Y H:i'),
                    $e->typeLabel(),
                    $solicitante,
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
