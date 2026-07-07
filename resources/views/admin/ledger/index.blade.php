@extends('layouts.admin')

@section('title', 'Fluxo de Caixa')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Fluxo de Caixa</h1>
            <p class="text-gray-600 mt-2">Entradas, taxas do SuitPay e repasses — receita líquida real da plataforma</p>
        </div>

        @if(session('status'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">{{ $errors->first() }}</div>
        @endif

        <!-- Caixa: saldo real (extrato) + caixa da plataforma. Reconciliação em "ver detalhes". -->
        <div class="mb-6">
            <div class="flex flex-wrap items-baseline justify-between gap-2 mb-2">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Caixa</h2>
                <form method="POST" action="{{ route('admin.fluxo-caixa.importar-extrato') }}" enctype="multipart/form-data" class="flex items-center gap-2">
                    @csrf
                    <input type="file" name="extrato[]" multiple accept=".csv,text/csv" required
                           class="text-xs text-gray-600 file:mr-2 file:px-3 file:py-1.5 file:rounded-lg file:border-0 file:bg-gray-100 file:text-gray-700 file:cursor-pointer">
                    <button type="submit" class="px-3 py-1.5 bg-gray-900 text-white rounded-lg hover:bg-gray-700 text-xs font-medium whitespace-nowrap">Importar extrato</button>
                </form>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if($recon)
                    <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-teal-500">
                        <p class="text-sm text-gray-500">Saldo real (SuitPay)</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">R$ {{ number_format($recon['realBalance'], 2, ',', '.') }}</p>
                        <p class="text-xs text-gray-400 mt-1">Extrato até {{ \Illuminate\Support\Carbon::parse($recon['realBalanceAt'])->format('d/m/Y H:i') }} · cobre {{ \Illuminate\Support\Carbon::parse($recon['stmtMin'])->format('d/m/Y') }} a {{ \Illuminate\Support\Carbon::parse($recon['stmtMax'])->format('d/m/Y') }}</p>
                    </div>
                @else
                    <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-gray-300 text-sm text-gray-500">
                        <p class="font-semibold text-gray-700 mb-1">Saldo real (SuitPay)</p>
                        Importe o extrato (CSV do painel) pra ver o saldo real da conta e reconciliar.
                    </div>
                @endif
                <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-orange-500">
                    <p class="text-sm text-gray-500">Caixa da plataforma</p>
                    <p class="text-3xl font-bold {{ $platformCash >= 0 ? 'text-gray-900' : 'text-red-600' }} mt-1">R$ {{ number_format($platformCash, 2, ',', '.') }}</p>
                    <p class="text-xs text-gray-400 mt-1">O que é de fato seu — receita líquida acumulada, já descontado criadores, afiliados e taxas.</p>
                </div>
            </div>

            @if($recon)
                @php
                    $diffIn = round($recon['realFeeIn'] - $recon['ledgerFeeIn'], 2);
                    $diffOut = round($recon['realFeeOut'] - $recon['ledgerFeeOut'], 2);
                @endphp
                @php $feeInOk = abs($diffIn) < 1; $manualTotal = abs($recon['manualTotal']); @endphp
                <details class="mt-3 bg-white rounded-lg shadow-sm">
                    <summary class="cursor-pointer select-none px-4 py-3 text-sm flex flex-wrap items-center gap-x-2 gap-y-1">
                        <span class="{{ $feeInOk ? 'text-green-600' : 'text-amber-600' }} font-semibold">{{ $feeInOk ? '✓ As contas batem com o SuitPay' : '⚠ Diferença a conferir' }}</span>
                        @if($manualTotal > 0)
                            <span class="text-gray-500">· R$ {{ number_format($manualTotal, 2, ',', '.') }} saíram da conta por fora do app</span>
                        @endif
                        <span class="text-gray-400 ml-auto text-xs">ver conferência</span>
                    </summary>
                    <div class="border-t border-gray-100 px-4 py-4 space-y-5 text-sm">
                        @if($recon['manual']->count() > 0)
                            <div>
                                <p class="font-semibold text-gray-800">Dinheiro que saiu por fora do app</p>
                                <p class="text-gray-500 text-xs mt-0.5 mb-2">Retiradas feitas direto no painel do SuitPay — não são saques de criador nem afiliado, então o sistema não registra. É por isso que o saldo real fica menor que o total movimentado.</p>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach($recon['manual'] as $m)
                                                <tr>
                                                    <td class="py-2 pr-4 text-gray-900 whitespace-nowrap">{{ $m->occurred_at->format('d/m/Y H:i') }}</td>
                                                    <td class="py-2 pr-4 text-gray-500">{{ $m->beneficiario ?: '—' }}</td>
                                                    <td class="py-2 text-right font-medium text-rose-600 whitespace-nowrap">R$ {{ number_format(abs($m->valor), 2, ',', '.') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                        <div>
                            <p class="font-semibold text-gray-800">As taxas do SuitPay batem com a nossa estimativa?</p>
                            <p class="text-gray-600 mt-1"><b>Entrada:</b>
                                @if($feeInOk)
                                    <span class="text-green-600 font-medium">batem ✓</span> — o SuitPay cobrou R$ {{ number_format($recon['realFeeIn'], 2, ',', '.') }} e a gente estimou R$ {{ number_format($recon['ledgerFeeIn'], 2, ',', '.') }} (diferença de só R$ {{ number_format(abs($diffIn), 2, ',', '.') }}).
                                @else
                                    <span class="text-amber-600 font-medium">diferença de R$ {{ number_format(abs($diffIn), 2, ',', '.') }}</span> — o SuitPay cobrou R$ {{ number_format($recon['realFeeIn'], 2, ',', '.') }} e a gente estimou R$ {{ number_format($recon['ledgerFeeIn'], 2, ',', '.') }}. Vale conferir a fórmula da taxa de entrada.
                                @endif
                            </p>
                            <p class="text-gray-600 mt-1"><b>Saída:</b> o SuitPay cobrou R$ {{ number_format($recon['realFeeOut'], 2, ',', '.') }} e a gente estimou R$ {{ number_format($recon['ledgerFeeOut'], 2, ',', '.') }}.
                                @if($manualTotal > 0)
                                    A diferença de R$ {{ number_format(abs($diffOut), 2, ',', '.') }} é a taxa daquelas retiradas por fora do app (que o sistema não vê).
                                @endif
                            </p>
                            <p class="text-xs text-gray-400 mt-2">Período conferido: {{ \Illuminate\Support\Carbon::parse($recon['winFrom'])->format('d/m/Y') }} a {{ \Illuminate\Support\Carbon::parse($recon['winTo'])->format('d/m/Y') }}. Se um dia a diferença não for explicada por retirada manual, aí sim é sinal de taxa errada ou venda/saque não registrado.</p>
                        </div>
                    </div>
                </details>
            @endif
        </div>

        <!-- Filtro de período + export -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
            <form method="GET" action="{{ route('admin.fluxo-caixa.index') }}" class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">De</label>
                    <input type="date" name="from" value="{{ $from }}" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Até</label>
                    <input type="date" name="to" value="{{ $to }}" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Tipo</label>
                    <select name="tipo" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="todos" @selected($tipo === 'todos')>Todos</option>
                        <option value="subscription_sale" @selected($tipo === 'subscription_sale')>Assinatura</option>
                        <option value="ppv_sale" @selected($tipo === 'ppv_sale')>Conteúdo Único</option>
                        <option value="cashout" @selected($tipo === 'cashout')>Saque</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Dono do saque</label>
                    <select name="dono" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="todos" @selected($dono === 'todos')>Todos</option>
                        <option value="creator" @selected($dono === 'creator')>Criador</option>
                        <option value="affiliate" @selected($dono === 'affiliate')>Afiliado</option>
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-700 text-sm font-medium">
                    Filtrar
                </button>
                <a href="{{ route('admin.fluxo-caixa.index', array_merge(request()->query(), ['export' => 'csv'])) }}"
                   class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium">
                    Exportar CSV
                </a>
            </form>
        </div>

        <!-- Cards de totais -->
        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-2">Movimento do período</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-green-500">
                <p class="text-sm text-gray-500">Bruto recebido (vendas)</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">R$ {{ number_format($grossSales, 2, ',', '.') }}</p>
                <p class="text-xs text-gray-400 mt-1">Assinatura R$ {{ number_format($subTotal, 2, ',', '.') }} · PPV R$ {{ number_format($ppvTotal, 2, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-red-500">
                <p class="text-sm text-gray-500">Taxas SuitPay</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">R$ {{ number_format($feeIn + $feeOut, 2, ',', '.') }}</p>
                <p class="text-xs text-gray-400 mt-1">Entrada R$ {{ number_format($feeIn, 2, ',', '.') }} · Saída R$ {{ number_format($feeOut, 2, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-blue-500">
                <p class="text-sm text-gray-500">Pago aos criadores</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">R$ {{ number_format($creatorPaid, 2, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-purple-500">
                <p class="text-sm text-gray-500">Pago a afiliados</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">R$ {{ number_format($affiliatePaid, 2, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-gray-400">
                <p class="text-sm text-gray-500">Total sacado (saídas)</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">R$ {{ number_format($cashoutTotal, 2, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-orange-500">
                <p class="text-sm text-gray-500">Receita líquida da plataforma</p>
                <p class="text-2xl font-bold {{ $platformNet >= 0 ? 'text-gray-900' : 'text-red-600' }} mt-1">R$ {{ number_format($platformNet, 2, ',', '.') }}</p>
                <p class="text-xs text-gray-400 mt-1">Plataforma menos taxas SuitPay (entrada + saída)</p>
            </div>
        </div>

        <!-- Tabela -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-900">Movimentos</h2>
                <span class="px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-sm font-semibold">
                    {{ $entries->total() }} registro(s)
                </span>
            </div>

            @if($entries->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bruto</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Taxa SuitPay</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Criador</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Afiliado</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plataforma (líq)</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($entries as $e)
                                @php
                                    $label = ['subscription_sale' => 'Assinatura', 'ppv_sale' => 'PPV', 'cashout' => 'Saque'][$e->entry_type] ?? $e->entry_type;
                                    $platform = $e->entry_type === 'cashout'
                                        ? $e->withdraw_fee - $e->suitpay_fee
                                        : $e->gross_amount - $e->creator_amount - $e->affiliate_amount - $e->suitpay_fee;
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">{{ $e->occurred_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                                            {{ $e->entry_type === 'cashout' ? 'bg-gray-100 text-gray-700' : 'bg-green-100 text-green-700' }}">
                                            {{ $label }}
                                        </span>
                                        @if($e->entry_type === 'cashout' && $e->withdrawal?->user)
                                            <div class="text-xs text-gray-500 mt-1">
                                                {{ $e->withdrawal->user->name }}
                                                <span class="text-gray-400">{{ '@' . $e->withdrawal->user->username }}</span>
                                                · {{ $e->withdrawal->type === 'affiliate' ? 'afiliado' : 'criador' }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">R$ {{ number_format($e->gross_amount, 2, ',', '.') }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-red-600">R$ {{ number_format($e->suitpay_fee, 2, ',', '.') }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-blue-600">R$ {{ number_format($e->creator_amount, 2, ',', '.') }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-purple-600">R$ {{ number_format($e->affiliate_amount, 2, ',', '.') }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm {{ $platform >= 0 ? 'text-orange-600' : 'text-red-600' }} font-medium">R$ {{ number_format(round($platform, 2), 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                            <tr class="font-semibold text-gray-900">
                                <td class="px-4 py-3 text-sm">Total do filtro</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $filtered['count'] }} registro(s)</td>
                                <td class="px-4 py-3 text-sm">R$ {{ number_format($filtered['gross'], 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm text-red-600">R$ {{ number_format($filtered['fee'], 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm text-blue-600">R$ {{ number_format($filtered['creator'], 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm text-purple-600">R$ {{ number_format($filtered['affiliate'], 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm {{ $filtered['platform'] >= 0 ? 'text-orange-600' : 'text-red-600' }}">R$ {{ number_format(round($filtered['platform'], 2), 2, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if($entries->hasPages())
                    <div class="mt-4 text-xs text-gray-400">O "Total do filtro" acima soma todos os {{ $filtered['count'] }} registros filtrados, não só esta página.</div>
                    <div class="mt-2">{{ $entries->links() }}</div>
                @endif
            @else
                <div class="text-center py-12 text-gray-500">
                    <p>Nenhum movimento no período selecionado.</p>
                </div>
            @endif
        </div>
    </div>
@endsection
