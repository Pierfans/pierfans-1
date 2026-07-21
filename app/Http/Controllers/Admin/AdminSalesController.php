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
        $tipo          = in_array($request->get('tipo'), ['sub', 'ppv']) ? $request->get('tipo') : 'todos';
        $grupo         = in_array($request->get('grupo'), ['dia', 'mes', 'cliente', 'afiliado']) ? $request->get('grupo') : 'criador';
        $creatorTerms = collect((array) $request->get('creator'))
            ->map(fn ($t) => trim((string) $t))->filter()->unique()->values();

        // Busca por nome ou @usuário — qualquer termo casa (OR). Resolve os ids antes de agregar.
        $creatorIds = null;
        if ($creatorTerms->isNotEmpty()) {
            $creatorIds = User::where('creator_status', 'approved')
                ->where(function ($q) use ($creatorTerms) {
                    foreach ($creatorTerms as $t) {
                        $q->orWhere('name', 'like', "%{$t}%")
                          ->orWhere('username', 'like', "%{$t}%");
                    }
                })->pluck('id');
        }

        $subs = collect();
        if ($tipo !== 'ppv') {
            $q = Subscription::where('total_amount', '>', 0)
                ->whereBetween('created_at', ["$from 00:00:00", "$to 23:59:59"]);
            if ($creatorIds !== null) {
                $q->whereIn('creator_id', $creatorIds);
            }
            $subs = $q->selectRaw('creator_id, count(*) as qtd, sum(total_amount) as bruto, sum(creator_amount) as criador')
                ->groupBy('creator_id')->get()->keyBy('creator_id');
        }

        $ppv = collect();
        if ($tipo !== 'sub') {
            $q = PostPurchase::whereBetween('purchased_at', ["$from 00:00:00", "$to 23:59:59"]);
            if ($creatorIds !== null) {
                $q->whereIn('creator_id', $creatorIds);
            }
            $ppv = $q->selectRaw('creator_id, count(*) as qtd, sum(amount_paid) as bruto, sum(creator_amount) as criador')
                ->groupBy('creator_id')->get()->keyBy('creator_id');
        }

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

        // Agrupamento por tempo (dia/mês) — mesma fonte, mesmos filtros, só muda a chave.
        $timeRows = collect();
        if ($grupo !== 'criador') {
            $fmt = $grupo === 'mes' ? '%Y-%m' : '%Y-%m-%d'; // whitelist, não é input do usuário
            $subsT = collect();
            if ($tipo !== 'ppv') {
                $q = Subscription::where('total_amount', '>', 0)
                    ->whereBetween('created_at', ["$from 00:00:00", "$to 23:59:59"]);
                if ($creatorIds !== null) {
                    $q->whereIn('creator_id', $creatorIds);
                }
                $subsT = $q->selectRaw("DATE_FORMAT(created_at, '$fmt') as periodo, count(*) as qtd, sum(total_amount) as bruto, sum(creator_amount) as criador")
                    ->groupBy('periodo')->get()->keyBy('periodo');
            }
            $ppvT = collect();
            if ($tipo !== 'sub') {
                $q = PostPurchase::whereBetween('purchased_at', ["$from 00:00:00", "$to 23:59:59"]);
                if ($creatorIds !== null) {
                    $q->whereIn('creator_id', $creatorIds);
                }
                $ppvT = $q->selectRaw("DATE_FORMAT(purchased_at, '$fmt') as periodo, count(*) as qtd, sum(amount_paid) as bruto, sum(creator_amount) as criador")
                    ->groupBy('periodo')->get()->keyBy('periodo');
            }
            $timeRows = $subsT->keys()->merge($ppvT->keys())->unique()->sortDesc()->values()
                ->map(function ($p) use ($subsT, $ppvT) {
                    $s = $subsT->get($p);
                    $pp = $ppvT->get($p);
                    return [
                        'periodo'        => $p,
                        'subs_qtd'       => (int) ($s->qtd ?? 0),
                        'ppv_qtd'        => (int) ($pp->qtd ?? 0),
                        'gross'          => round((float) ($s->bruto ?? 0) + (float) ($pp->bruto ?? 0), 2),
                        'creator_amount' => round((float) ($s->criador ?? 0) + (float) ($pp->criador ?? 0), 2),
                    ];
                });
        }

        // Agrupamento por cliente (comprador) — subs + ppv chaveados pelo user_id do comprador.
        $clienteRows = collect();
        if ($grupo === 'cliente') {
            $subsC = collect();
            if ($tipo !== 'ppv') {
                $q = Subscription::where('total_amount', '>', 0)
                    ->whereBetween('created_at', ["$from 00:00:00", "$to 23:59:59"]);
                if ($creatorIds !== null) {
                    $q->whereIn('creator_id', $creatorIds);
                }
                $subsC = $q->selectRaw('user_id, count(*) as qtd, sum(total_amount) as bruto')
                    ->groupBy('user_id')->get()->keyBy('user_id');
            }
            $ppvC = collect();
            if ($tipo !== 'sub') {
                $q = PostPurchase::whereBetween('purchased_at', ["$from 00:00:00", "$to 23:59:59"]);
                if ($creatorIds !== null) {
                    $q->whereIn('creator_id', $creatorIds);
                }
                $ppvC = $q->selectRaw('user_id, count(*) as qtd, sum(amount_paid) as bruto')
                    ->groupBy('user_id')->get()->keyBy('user_id');
            }
            $cids = $subsC->keys()->merge($ppvC->keys())->unique()->values();
            $buyers = User::whereIn('id', $cids)->get()->keyBy('id');
            $clienteRows = $cids->map(function ($id) use ($subsC, $ppvC, $buyers) {
                $s = $subsC->get($id);
                $p = $ppvC->get($id);
                $b = $buyers->get($id);
                return [
                    'name'     => $b->name ?? '—',
                    'username' => $b->username ?? '',
                    'subs_qtd' => (int) ($s->qtd ?? 0),
                    'ppv_qtd'  => (int) ($p->qtd ?? 0),
                    'gross'    => round((float) ($s->bruto ?? 0) + (float) ($p->bruto ?? 0), 2),
                ];
            })->sortByDesc('gross')->values();
        }

        // Agrupamento por afiliado — só assinaturas com comissão de afiliado do criador (única atribuição no banco).
        $afiliadoRows = collect();
        if ($grupo === 'afiliado') {
            $q = Subscription::where('creator_affiliate_amount', '>', 0)
                ->whereBetween('created_at', ["$from 00:00:00", "$to 23:59:59"]);
            if ($creatorIds !== null) {
                $q->whereIn('creator_id', $creatorIds);
            }
            $aff = $q->selectRaw('creator_affiliate_user_id as aff_id, count(*) as qtd, sum(total_amount) as bruto, sum(creator_affiliate_amount) as comissao')
                ->groupBy('creator_affiliate_user_id')->get();
            $affUsers = User::whereIn('id', $aff->pluck('aff_id'))->get()->keyBy('id');
            $afiliadoRows = $aff->map(function ($r) use ($affUsers) {
                $u = $affUsers->get($r->aff_id);
                return [
                    'name'     => $u->name ?? '—',
                    'username' => $u->username ?? '',
                    'qtd'      => (int) $r->qtd,
                    'gross'    => round((float) $r->bruto, 2),
                    'comissao' => round((float) $r->comissao, 2),
                ];
            })->sortByDesc('gross')->values();
        }

        if ($request->get('export') === 'csv') {
            return match ($grupo) {
                'dia', 'mes' => $this->exportTimeCsv($timeRows, $grupo, $from, $to),
                'cliente'    => $this->exportClienteCsv($clienteRows, $from, $to),
                'afiliado'   => $this->exportAfiliadoCsv($afiliadoRows, $from, $to),
                default      => $this->exportRankingCsv($rows, $from, $to),
            };
        }

        $totGross = $rows->sum('gross');
        $totSubs  = $rows->sum('subs_qtd');
        $totPpv   = $rows->sum('ppv_qtd');

        $allCreators = User::where('creator_status', 'approved')
            ->orderBy('name')->get(['name', 'username']);

        return view('admin.sales.index', compact('rows', 'timeRows', 'clienteRows', 'afiliadoRows', 'grupo', 'from', 'to', 'totGross', 'totSubs', 'totPpv', 'tipo', 'creatorTerms', 'allCreators'));
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

    private function exportTimeCsv($rows, string $grupo, string $from, string $to)
    {
        $header = $grupo === 'mes' ? 'Mes' : 'Dia';
        return response()->streamDownload(function () use ($rows, $header) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [$header, 'Assinaturas', 'Conteudo Unico (pacotes)', 'Bruto vendido', 'Valor dos criadores']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['periodo'],
                    $r['subs_qtd'],
                    $r['ppv_qtd'],
                    number_format($r['gross'], 2, ',', '.'),
                    number_format($r['creator_amount'], 2, ',', '.'),
                ]);
            }
            fclose($out);
        }, "vendas_por_{$grupo}_{$from}_a_{$to}.csv");
    }

    private function exportClienteCsv($rows, string $from, string $to)
    {
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Cliente', 'Username', 'Assinaturas', 'Conteudo Unico (pacotes)', 'Total gasto']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['name'], $r['username'], $r['subs_qtd'], $r['ppv_qtd'],
                    number_format($r['gross'], 2, ',', '.'),
                ]);
            }
            fclose($out);
        }, "vendas_por_cliente_{$from}_a_{$to}.csv");
    }

    private function exportAfiliadoCsv($rows, string $from, string $to)
    {
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Afiliado', 'Username', 'Vendas atribuidas', 'Bruto gerado', 'Comissao do afiliado']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['name'], $r['username'], $r['qtd'],
                    number_format($r['gross'], 2, ',', '.'),
                    number_format($r['comissao'], 2, ',', '.'),
                ]);
            }
            fclose($out);
        }, "vendas_por_afiliado_{$from}_a_{$to}.csv");
    }

    private function exportBuyersCsv($sales, $buyers, $creator, string $from, string $to)
    {
        return response()->streamDownload(function () use ($sales, $buyers) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Data', 'Comprador', 'Username', 'Tipo', 'Conteudo', 'Bruto', 'Valor do criador']);
            foreach ($sales as $s) {
                $b = $buyers->get($s['buyer_id']);
                fputcsv($out, [
                    $s['date']->emBrasilia()->format('d/m/Y H:i'),
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
