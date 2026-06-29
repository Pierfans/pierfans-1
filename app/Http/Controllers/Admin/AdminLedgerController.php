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

        $base = LedgerEntry::whereBetween('occurred_at', [$from . ' 00:00:00', $to . ' 23:59:59']);

        $sales    = (clone $base)->whereIn('entry_type', ['subscription_sale', 'ppv_sale']);
        $cashouts = (clone $base)->where('entry_type', 'cashout');

        $grossSales    = (float) (clone $sales)->sum('gross_amount');
        $creatorPaid   = (float) (clone $sales)->sum('creator_amount');
        $affiliatePaid = (float) (clone $sales)->sum('affiliate_amount');
        $feeIn         = (float) (clone $sales)->sum('suitpay_fee');
        $feeOut        = (float) (clone $cashouts)->sum('suitpay_fee');
        $cashoutTotal  = (float) (clone $cashouts)->sum('gross_amount');

        $subTotal = (float) (clone $base)->where('entry_type', 'subscription_sale')->sum('gross_amount');
        $ppvTotal = (float) (clone $base)->where('entry_type', 'ppv_sale')->sum('gross_amount');

        // Receita líquida da plataforma = parte da plataforma nas vendas, menos as taxas do SuitPay (entrada + saída)
        $platformNet = $grossSales - $creatorPaid - $affiliatePaid - $feeIn - $feeOut;

        if ($request->get('export') === 'csv') {
            return $this->exportCsv((clone $base), $from, $to);
        }

        $entries = (clone $base)->latest('occurred_at')->paginate(50)->appends($request->query());

        return view('admin.ledger.index', compact(
            'entries', 'from', 'to',
            'grossSales', 'creatorPaid', 'affiliatePaid', 'feeIn', 'feeOut',
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
        $rows = $query->latest('occurred_at')->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Data', 'Tipo', 'Bruto', 'Taxa SuitPay', 'Criador', 'Afiliado', 'Plataforma (liq)']);
            foreach ($rows as $e) {
                $platform = $e->entry_type === 'cashout'
                    ? -$e->suitpay_fee
                    : $e->gross_amount - $e->creator_amount - $e->affiliate_amount - $e->suitpay_fee;
                fputcsv($out, [
                    $e->occurred_at->format('d/m/Y H:i'),
                    $e->entry_type,
                    number_format($e->gross_amount, 2, ',', '.'),
                    number_format($e->suitpay_fee, 2, ',', '.'),
                    number_format($e->creator_amount, 2, ',', '.'),
                    number_format($e->affiliate_amount, 2, ',', '.'),
                    number_format(round($platform, 2), 2, ',', '.'),
                ]);
            }
            fclose($out);
        }, "conciliacao_{$from}_a_{$to}.csv");
    }
}
