<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PostPurchase;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Relatório de vendas por influenciador (criador).
 * Fonte = registros de domínio (subscriptions + post_purchases), não o ledger,
 * porque o ledger só existe a partir de 29/06 e aqui queremos o histórico completo.
 * Assinaturas de teste (total_amount = 0) ficam de fora — não são venda.
 */
class AdminSalesController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to] = $this->period($request);

        $subs = Subscription::where('total_amount', '>', 0)
            ->whereBetween('created_at', ["$from 00:00:00", "$to 23:59:59"])
            ->selectRaw('creator_id, count(*) as qtd, sum(total_amount) as bruto, sum(creator_amount) as criador')
            ->groupBy('creator_id')->get()->keyBy('creator_id');

        $ppv = PostPurchase::whereBetween('purchased_at', ["$from 00:00:00", "$to 23:59:59"])
            ->selectRaw('creator_id, count(*) as qtd, sum(amount_paid) as bruto, sum(creator_amount) as criador')
            ->groupBy('creator_id')->get()->keyBy('creator_id');

        $ids = $subs->keys()->merge($ppv->keys())->unique()->values();
        $creators = User::whereIn('id', $ids)->get()->keyBy('id');

        $rows = $ids->map(function ($cid) use ($subs, $ppv, $creators) {
            $s = $subs->get($cid);
            $p = $ppv->get($cid);
            $c = $creators->get($cid);
            return [
                'creator_id'     => $cid,
                'name'           => $c->name ?? '—',
                'username'       => $c->username ?? '',
                'subs_qtd'       => (int) ($s->qtd ?? 0),
                'ppv_qtd'        => (int) ($p->qtd ?? 0),
                'gross'          => round((float) ($s->bruto ?? 0) + (float) ($p->bruto ?? 0), 2),
                'creator_amount' => round((float) ($s->criador ?? 0) + (float) ($p->criador ?? 0), 2),
            ];
        })->sortByDesc('gross')->values();

        if ($request->get('export') === 'csv') {
            return $this->exportRankingCsv($rows, $from, $to);
        }

        $totGross = $rows->sum('gross');
        $totSubs  = $rows->sum('subs_qtd');
        $totPpv   = $rows->sum('ppv_qtd');

        return view('admin.sales.index', compact('rows', 'from', 'to', 'totGross', 'totSubs', 'totPpv'));
    }

    public function show(Request $request, int $creatorId)
    {
        [$from, $to] = $this->period($request);
        $creator = User::findOrFail($creatorId);

        $subs = Subscription::where('creator_id', $creatorId)
            ->where('total_amount', '>', 0)
            ->whereBetween('created_at', ["$from 00:00:00", "$to 23:59:59"])
            ->get()->map(fn ($s) => [
                'date'           => $s->created_at,
                'buyer_id'       => $s->user_id,
                'tipo'           => 'Assinatura',
                'conteudo'       => 'Plano #' . $s->subscription_plan_id,
                'gross'          => (float) $s->total_amount,
                'creator_amount' => (float) $s->creator_amount,
            ]);

        $ppv = PostPurchase::where('creator_id', $creatorId)
            ->whereBetween('purchased_at', ["$from 00:00:00", "$to 23:59:59"])
            ->get()->map(fn ($p) => [
                'date'           => $p->purchased_at,
                'buyer_id'       => $p->user_id,
                'tipo'           => 'Conteúdo Único',
                'conteudo'       => 'Post #' . $p->post_id,
                'gross'          => (float) $p->amount_paid,
                'creator_amount' => (float) $p->creator_amount,
            ]);

        $sales  = $subs->concat($ppv)->sortByDesc('date')->values();
        $buyers = User::whereIn('id', $sales->pluck('buyer_id')->unique())->get()->keyBy('id');

        if ($request->get('export') === 'csv') {
            return $this->exportBuyersCsv($sales, $buyers, $creator, $from, $to);
        }

        return view('admin.sales.show', compact('creator', 'sales', 'buyers', 'from', 'to'));
    }

    private function period(Request $request): array
    {
        $from = $request->get('from') ?: now()->startOfMonth()->toDateString();
        $to   = $request->get('to') ?: now()->toDateString();
        return [$from, $to];
    }

    private function exportRankingCsv($rows, string $from, string $to)
    {
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Criador', 'Username', 'Assinaturas', 'Conteudo Unico (pacotes)', 'Bruto vendido', 'Valor do criador']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['name'],
                    $r['username'],
                    $r['subs_qtd'],
                    $r['ppv_qtd'],
                    number_format($r['gross'], 2, ',', '.'),
                    number_format($r['creator_amount'], 2, ',', '.'),
                ]);
            }
            fclose($out);
        }, "vendas_por_criador_{$from}_a_{$to}.csv");
    }

    private function exportBuyersCsv($sales, $buyers, $creator, string $from, string $to)
    {
        return response()->streamDownload(function () use ($sales, $buyers) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Data', 'Comprador', 'Username', 'Tipo', 'Conteudo', 'Bruto', 'Valor do criador']);
            foreach ($sales as $s) {
                $b = $buyers->get($s['buyer_id']);
                fputcsv($out, [
                    $s['date']->format('d/m/Y H:i'),
                    $b->name ?? '—',
                    $b->username ?? '',
                    $s['tipo'],
                    $s['conteudo'],
                    number_format($s['gross'], 2, ',', '.'),
                    number_format($s['creator_amount'], 2, ',', '.'),
                ]);
            }
            fclose($out);
        }, "compradores_{$creator->username}_{$from}_a_{$to}.csv");
    }
}
