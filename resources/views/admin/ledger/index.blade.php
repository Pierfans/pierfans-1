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

        <!-- Saldo real (SuitPay) + reconciliação contra o extrato -->
        <div class="mb-6">
            <div class="flex flex-wrap items-baseline justify-between gap-2 mb-2">
                <div class="flex items-baseline gap-2">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Saldo real (SuitPay)</h2>
                    <span class="text-xs text-gray-400">do extrato importado — a verdade da conta</span>
                </div>
                <form method="POST" action="{{ route('admin.fluxo-caixa.importar-extrato') }}" enctype="multipart/form-data" class="flex items-center gap-2">
                    @csrf
                    <input type="file" name="extrato[]" multiple accept=".csv,text/csv" required
                           class="text-xs text-gray-600 file:mr-2 file:px-3 file:py-1.5 file:rounded-lg file:border-0 file:bg-gray-100 file:text-gray-700 file:cursor-pointer">
                    <button type="submit" class="px-3 py-1.5 bg-gray-900 text-white rounded-lg hover:bg-gray-700 text-xs font-medium whitespace-nowrap">Importar extrato</button>
                </form>
            </div>

            @if($recon)
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-teal-500">
                        <p class="text-sm text-gray-500">Saldo real na conta</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">R$ {{ number_format($recon['realBalance'], 2, ',', '.') }}</p>
                        <p class="text-xs text-gray-400 mt-1">Extrato até {{ \Illuminate\Support\Carbon::parse($recon['realBalanceAt'])->format('d/m/Y H:i') }} · cobre {{ \Illuminate\Support\Carbon::parse($recon['stmtMin'])->format('d/m/Y') }} a {{ \Illuminate\Support\Carbon::parse($recon['stmtMax'])->format('d/m/Y') }}</p>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-rose-500">
                        <p class="text-sm text-gray-500">Retiradas manuais</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">R$ {{ number_format(abs($recon['manualTotal']), 2, ',', '.') }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ $recon['manual']->count() }} saída(s) fora do gateway — não aparecem no ledger interno.</p>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-indigo-500">
                        <p class="text-sm text-gray-500">Taxa real vs estimada</p>
                        @php
                            $diffIn = round($recon['realFeeIn'] - $recon['ledgerFeeIn'], 2);
                            $diffOut = round($recon['realFeeOut'] - $recon['ledgerFeeOut'], 2);
                        @endphp
                        <p class="text-sm mt-1">Entrada: real <b>R$ {{ number_format($recon['realFeeIn'], 2, ',', '.') }}</b> · nossa R$ {{ number_format($recon['ledgerFeeIn'], 2, ',', '.') }}
                            <span class="{{ abs($diffIn) < 1 ? 'text-green-600' : 'text-amber-600' }}">({{ $diffIn >= 0 ? '+' : '' }}{{ number_format($diffIn, 2, ',', '.') }})</span></p>
                        <p class="text-sm">Saída: real <b>R$ {{ number_format($recon['realFeeOut'], 2, ',', '.') }}</b> · nossa R$ {{ number_format($recon['ledgerFeeOut'], 2, ',', '.') }}
                            <span class="{{ abs($diffOut) < 1 ? 'text-green-600' : 'text-amber-600' }}">({{ $diffOut >= 0 ? '+' : '' }}{{ number_format($diffOut, 2, ',', '.') }})</span></p>
                        <p class="text-xs text-gray-400 mt-1">Na janela {{ \Illuminate\Support\Carbon::parse($recon['winFrom'])->format('d/m/Y') }}–{{ \Illuminate\Support\Carbon::parse($recon['winTo'])->format('d/m/Y') }}. Perto de zero = fórmula boa.</p>
                    </div>
                </div>

                @if($recon['manual']->count() > 0)
                    <div class="bg-white rounded-lg shadow-sm p-5 mt-4">
                        <p class="text-sm font-semibold text-gray-700 mb-2">Retiradas manuais (só no extrato do SuitPay)</p>
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
            @else
                <div class="bg-white rounded-lg shadow-sm p-5 text-sm text-gray-500 border-l-4 border-gray-300">
                    Nenhum extrato importado ainda. Suba o CSV do painel SuitPay (Exportar) pra ver o <b>saldo real</b>, as retiradas manuais e conferir a taxa estimada contra a real.
                </div>
            @endif
        </div>

        <!-- Resumo registrado (all-time — NÃO muda com o filtro abaixo) -->
        <div class="mb-6">
            <div class="flex items-baseline gap-2 mb-2">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Resumo registrado</h2>
                <span class="text-xs text-gray-400">acumulado — não muda com o filtro abaixo</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-emerald-500">
                    <p class="text-sm text-gray-500">Líquido movimentado</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">R$ {{ number_format($accountBalance, 2, ',', '.') }}</p>
                    <p class="text-xs text-gray-400 mt-1">Vendas − saques, já fora as taxas, desde o início do registro ({{ $ledgerStart ? \Illuminate\Support\Carbon::parse($ledgerStart)->format('d/m/Y') : '—' }}). Inclui R$ {{ number_format($owedToCreators, 2, ',', '.') }} que ainda são dos criadores (ganharam, não sacaram).</p>
                    <p class="text-xs text-amber-600 mt-1">Não é o saldo real do SuitPay — ignora a abertura da conta e retiradas manuais. Saldo real: reconciliação por extrato (em breve).</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-orange-500">
                    <p class="text-sm text-gray-500">Caixa da plataforma</p>
                    <p class="text-3xl font-bold {{ $platformCash >= 0 ? 'text-gray-900' : 'text-red-600' }} mt-1">R$ {{ number_format($platformCash, 2, ',', '.') }}</p>
                    <p class="text-xs text-gray-400 mt-1">O que é de fato seu — receita líquida acumulada, já descontado criadores, afiliados e taxas.</p>
                </div>
            </div>
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
                    </table>
                </div>

                @if($entries->hasPages())
                    <div class="mt-4">{{ $entries->links() }}</div>
                @endif
            @else
                <div class="text-center py-12 text-gray-500">
                    <p>Nenhum movimento no período selecionado.</p>
                </div>
            @endif
        </div>
    </div>
@endsection
