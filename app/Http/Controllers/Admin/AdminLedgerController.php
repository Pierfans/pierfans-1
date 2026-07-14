<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\LedgerEntry;
use App\Models\PlatformSetting;
use App\Models\SuitpayStatementEntry;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminLedgerController extends Controller
{
    /** Conta que saca o caixa da plataforma (a que fica nos logs). */
    private const PLATFORM_EMAIL = 'admin@pierfans.com.br';

    public function index(Request $request)
    {
        [$from, $to] = $this->period($request);
        $tipo = in_array($request->get('tipo'), ['subscription_sale', 'ppv_sale', 'cashout']) ? $request->get('tipo') : 'todos';
        // Dono do saque (criador/afiliado/plataforma). Só faz sentido em cashout — filtra pelo withdrawal.type.
        $dono = in_array($request->get('dono'), ['creator', 'affiliate', 'platform']) ? $request->get('dono') : 'todos';

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

        // Desde quando o fluxo é registrado (o ledger nasceu em jun/26; o site vende desde março,
        // então ele NÃO serve pra medir saldo nem passivo — só o movimento do período).
        $ledgerStart = LedgerEntry::min('occurred_at');

        // Reconciliação contra o extrato real do SuitPay (traz o saldo real da conta).
        $recon = $this->reconciliation($ledgerStart);

        // Passivo = o que criadores e afiliados podem sacar. Vem da MESMA conta que eles veem na tela
        // de saque (não do ledger, que só enxerga de junho pra cá e ignora crédito manual).
        // Inclui o que ainda está no prazo de liberação: vai sair da conta de qualquer jeito.
        // Soma os saques PENDENTES deles: o saldo do usuário já os desconta, mas o dinheiro ainda
        // está na conta — sem isso, saque pendente de criador viraria "caixa livre" da plataforma.
        $owedToCreators = round($this->owedToUsers() + $this->reservedByPending(['creator', 'affiliate']), 2);

        // Caixa da plataforma = o que sobra na conta depois de pagar todo mundo, menos o que a própria
        // plataforma já pediu pra sacar e ainda não saiu.
        // Derivado do saldo REAL, não da soma de comissões: assim retirada manual no painel e saque
        // pelo app já vêm descontados sozinhos — não tem como sacar o mesmo dinheiro duas vezes.
        // null quando não há extrato importado (sem saldo real, a conta não existe).
        $platformCash = $recon
            ? round($recon['liveBalance'] - $owedToCreators - $this->reservedByPending(['platform']), 2)
            : null;

        // Teto do saque da plataforma: a SuitPay debita valor + 3,5% da conta, então o valor pedido
        // precisa caber COM a taxa dentro do caixa. Sacar o caixa cheio comeria o dinheiro dos criadores.
        $platformMax = $platformCash > 0 ? floor($platformCash / (1 + PlatformSetting::suitpayFeeOutPercent() / 100) * 100) / 100 : 0.0;
        $platformAccounts = BankAccount::where('user_id', $this->platformUser()?->id)
            ->orderByDesc('is_primary')->get();
        $feeOutPct = PlatformSetting::suitpayFeeOutPercent();

        // Cards = visão geral do período; os filtros de tipo/dono afetam só a lista de movimentos.
        $listing = (clone $base);
        if ($tipo !== 'todos') {
            $listing->where('entry_type', $tipo);
        }
        // Dono do saque: whereHas withdrawal.type restringe a cashouts daquele dono (vendas não têm withdrawal).
        if ($dono !== 'todos') {
            $listing->whereHas('withdrawal', fn ($q) => $q->where('type', $dono));
        }

        // Total do que está filtrado (respeita período + tipo + dono), sobre TODO o conjunto (não só a página).
        $ft = clone $listing;
        $ftGross     = (float) (clone $ft)->sum('gross_amount');
        $ftFee       = (float) (clone $ft)->sum('suitpay_fee');
        $ftCreator   = (float) (clone $ft)->sum('creator_amount');
        $ftAffiliate = (float) (clone $ft)->sum('affiliate_amount');
        $ftWithdraw  = (float) (clone $ft)->sum('withdraw_fee');
        $ftSalesGross = (float) (clone $ft)->whereIn('entry_type', ['subscription_sale', 'ppv_sale'])->sum('gross_amount');
        // Plataforma: vendas (gross - criador - afiliado - taxa) + saques (withdraw_fee - taxa).
        $ftPlatform = $ftSalesGross - $ftCreator - $ftAffiliate - $ftFee + $ftWithdraw;
        $filtered = [
            'count'     => (clone $ft)->count(),
            'gross'     => $ftGross,
            'fee'       => $ftFee,
            'creator'   => $ftCreator,
            'affiliate' => $ftAffiliate,
            'platform'  => $ftPlatform,
        ];

        if ($request->get('export') === 'csv') {
            return $this->exportCsv((clone $listing), $from, $to);
        }

        $entries = $listing->with('withdrawal.user')->latest('occurred_at')->paginate(50)->appends($request->query());

        return view('admin.ledger.index', compact(
            'entries', 'from', 'to', 'tipo', 'dono',
            'grossSales', 'creatorPaid', 'affiliatePaid', 'feeIn', 'feeOut', 'withdrawFee',
            'cashoutTotal', 'platformNet', 'subTotal', 'ppvTotal',
            'platformCash', 'platformMax', 'platformAccounts', 'feeOutPct', 'owedToCreators', 'ledgerStart', 'recon', 'filtered'
        ));
    }

    /**
     * Passivo da plataforma: tudo que criadores e afiliados podem sacar (liberado + no prazo).
     * Reusa os mesmos métodos da tela de saque de propósito — se divergir, o card mente.
     * ponytail: ~40 usuários, algumas queries cada. Cachear (60s) se a tela pesar.
     */
    private function owedToUsers(): float
    {
        $creators = User::where('creator_status', 'approved')->get()
            ->sum(fn ($u) => $u->getAvailableBalance() + $u->getPendingBalance());

        $affiliates = User::whereHas('referrals')->get()
            ->sum(fn ($u) => $u->getAffiliateAvailableBalance() + $u->getAffiliatePendingBalance());

        return round($creators + $affiliates, 2);
    }

    /** Dinheiro já prometido num saque pendente: ainda está na conta, mas não é caixa livre. */
    private function reservedByPending(array $types): float
    {
        return (float) Withdrawal::whereIn('type', $types)
            ->where('status', 'pending')
            ->sum(DB::raw('amount + COALESCE(fee, 0)'));
    }

    /**
     * A conta que saca o caixa da plataforma. Fixa no @pierfans: é a que fica nos logs e a única
     * autorizada a tirar o dinheiro da plataforma, independente de qual admin está logado.
     */
    private function platformUser(): ?User
    {
        return User::where('email', self::PLATFORM_EMAIL)->first();
    }

    /**
     * Saque do caixa da plataforma pela própria plataforma (em vez de retirada manual no painel).
     * Cai na fila normal de saques do admin: aprovar dispara o PIX, o webhook grava no ledger.
     */
    public function sacar(Request $request)
    {
        $user = $this->platformUser();
        if (!$user) {
            return back()->withErrors(['amount' => 'Conta da plataforma (' . self::PLATFORM_EMAIL . ') não encontrada.']);
        }

        $validated = $request->validate([
            'amount'          => 'required|numeric|min:1',
            'bank_account_id' => 'required|exists:bank_accounts,id',
        ], [
            'bank_account_id.required' => 'Escolha a conta que vai receber.',
        ]);

        $bankAccount = BankAccount::where('id', $validated['bank_account_id'])
            ->where('user_id', $user->id)
            ->first();
        if (!$bankAccount) {
            return back()->withErrors(['bank_account_id' => 'Essa conta não é da plataforma.']);
        }

        // Saldo checado DENTRO da transação: dois cliques simultâneos não podem sacar o mesmo caixa.
        return DB::transaction(function () use ($validated, $bankAccount, $user) {
            $amount = round((float) $validated['amount'], 2);
            $recon  = $this->reconciliation(LedgerEntry::min('occurred_at'));
            if (!$recon) {
                return back()->withErrors(['amount' => 'Sem extrato do SuitPay importado — não dá pra saber o saldo real.']);
            }

            $reserved = Withdrawal::whereIn('type', ['creator', 'affiliate', 'platform'])
                ->where('status', 'pending')
                ->lockForUpdate()
                ->sum(DB::raw('amount + COALESCE(fee, 0)'));

            $cash = round($recon['liveBalance'] - $this->owedToUsers() - (float) $reserved, 2);
            // A SuitPay debita valor + taxa da conta: o saque precisa caber com a taxa dentro do caixa.
            $custo = $amount + PlatformSetting::suitpayFeeOut($amount);

            if ($custo > $cash) {
                return back()->withErrors(['amount' => 'Não cabe no caixa: com a taxa de saída o saque custa R$ '
                    . number_format($custo, 2, ',', '.') . ' e o caixa da plataforma é R$ ' . number_format($cash, 2, ',', '.') . '.']);
            }

            Withdrawal::create([
                'user_id'         => $user->id,
                'type'            => 'platform',
                'bank_account_id' => $bankAccount->id,
                'amount'          => $amount,
                'fee'             => 0, // a plataforma não cobra taxa de si mesma; o custo do PIX ela paga de qualquer jeito
                'status'          => 'pending',
            ]);

            return redirect()->route('admin.withdrawals.index')
                ->with('status', 'Saque da plataforma de R$ ' . number_format($amount, 2, ',', '.') . ' criado. Aprove aqui pra sair o PIX.');
        });
    }

    /**
     * Importa um ou mais CSVs de extrato do SuitPay (painel → Exportar).
     * Idempotente (line_hash): re-subir o mesmo arquivo não duplica.
     */
    public function importExtrato(Request $request)
    {
        $request->validate([
            'extrato'   => 'required|array',
            'extrato.*' => 'file|max:5120', // 5MB por arquivo; o parser ignora linhas fora do formato
        ]);

        $novas = 0;
        $arquivos = 0;
        foreach ($request->file('extrato') as $file) {
            $novas += SuitpayStatementEntry::importCsv(file_get_contents($file->getRealPath()));
            $arquivos++;
        }

        return redirect()->route('admin.fluxo-caixa.index')
            ->with('status', "Extrato importado: {$arquivos} arquivo(s), {$novas} linha(s) nova(s).");
    }

    /**
     * Reconcilia o ledger interno contra o extrato real importado.
     * Retorna null se nenhum extrato foi importado ainda.
     * O extrato é o dado-verdade: saldo real + taxa real + retiradas manuais que o ledger não vê.
     */
    private function reconciliation(?string $ledgerStart): ?array
    {
        if (!SuitpayStatementEntry::exists()) {
            return null;
        }

        // Saldo real = saldo corrente da linha mais recente do extrato.
        $latest = SuitpayStatementEntry::whereNotNull('saldo')->orderByDesc('occurred_at')->orderByDesc('id')->first();
        $stmtMin = SuitpayStatementEntry::min('occurred_at');
        $stmtMax = SuitpayStatementEntry::max('occurred_at');

        // Saldo real ao vivo = base do extrato + o que o app registrou DEPOIS da última linha dele.
        // O extrato ancora (pega abertura da conta + retiradas manuais que o app não vê); vendas/saques
        // novos somam em cima, sem precisar reimportar. Venda cai (bruto - taxa), saque sai (bruto + taxa).
        // Corta no FIM do minuto: o extrato só tem precisão de minuto (15:26:00) e o ledger tem segundos
        // (15:26:29) — sem isso, o último lançamento do extrato seria somado de novo.
        // ponytail: uma venda no mesmo minuto do corte que ainda não esteja no extrato fica de fora até
        // o próximo import. Erra pra menos (nunca oferece dinheiro a mais pra sacar), que é o lado seguro.
        $after = $latest?->occurred_at?->copy()->endOfMinute();
        $newSales = LedgerEntry::whereIn('entry_type', ['subscription_sale', 'ppv_sale'])->where('occurred_at', '>', $after);
        $newCash  = LedgerEntry::where('entry_type', 'cashout')->where('occurred_at', '>', $after);
        $appDelta = $after ? round(
            ((float) (clone $newSales)->sum('gross_amount') - (float) (clone $newSales)->sum('suitpay_fee'))
            - ((float) (clone $newCash)->sum('gross_amount') + (float) (clone $newCash)->sum('suitpay_fee')),
            2
        ) : 0.0;
        $liveBalance = round((float) ($latest?->saldo ?? 0) + $appDelta, 2);

        // Retiradas manuais (não passam pelo gateway → o ledger não as tem). O buraco de conciliação.
        $manual = SuitpayStatementEntry::where('tipo', 'manual_out')->orderByDesc('occurred_at')->get();

        // Conferência de taxa: janela = interseção do alcance do ledger com o do extrato.
        $from = max($ledgerStart, $stmtMin);
        $to   = min(now()->toDateTimeString(), $stmtMax);
        $win  = fn ($q) => $q->whereBetween('occurred_at', [$from, $to]);

        $realFeeIn  = (float) $win(SuitpayStatementEntry::where('tipo', 'fee_in'))->sum('valor');
        $realFeeOut = (float) $win(SuitpayStatementEntry::where('tipo', 'fee_out'))->sum('valor');
        $ledgerFeeIn  = (float) $win(LedgerEntry::whereIn('entry_type', ['subscription_sale', 'ppv_sale']))->sum('suitpay_fee');
        $ledgerFeeOut = (float) $win(LedgerEntry::where('entry_type', 'cashout'))->sum('suitpay_fee');

        return [
            'realBalance'   => $latest?->saldo,
            'realBalanceAt' => $latest?->occurred_at,
            'liveBalance'   => $liveBalance,
            'appDelta'      => $appDelta,
            'stmtMin'       => $stmtMin,
            'stmtMax'       => $stmtMax,
            'manual'        => $manual,
            'manualTotal'   => (float) $manual->sum('valor'),
            'winFrom'       => $from,
            'winTo'         => $to,
            'realFeeIn'     => abs($realFeeIn),   // extrato guarda taxa como negativa
            'ledgerFeeIn'   => $ledgerFeeIn,
            'realFeeOut'    => abs($realFeeOut),
            'ledgerFeeOut'  => $ledgerFeeOut,
        ];
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
