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

        <!-- Caixa: saldo real (base do extrato + vendas/saques do app) + caixa da plataforma. -->
        <div class="mb-6">
            <div class="mb-2">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Caixa</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                <div class="space-y-4">
                @if($recon)
                    <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-teal-500">
                        <p class="text-sm text-gray-500">Saldo real (SuitPay)</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">R$ {{ number_format($recon['liveBalance'], 2, ',', '.') }}</p>
                        <p class="text-xs text-gray-400 mt-1">
                            Base do extrato R$ {{ number_format($recon['realBalance'], 2, ',', '.') }} ({{ \Illuminate\Support\Carbon::parse($recon['realBalanceAt'])->format('d/m/Y H:i') }})
                            @if($recon['appDelta'] != 0)
                                {{ $recon['appDelta'] > 0 ? '+' : '−' }} R$ {{ number_format(abs($recon['appDelta']), 2, ',', '.') }} de vendas/saques registrados depois
                            @endif
                        </p>
                    </div>
                @else
                    <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-gray-300 text-sm text-gray-500">
                        <p class="font-semibold text-gray-700 mb-1">Saldo real (SuitPay)</p>
                        Sem base do extrato ainda. Importe um CSV do painel pelo terminal pra ancorar no saldo real; daí as vendas e saques do app somam sozinhos em cima.
                    </div>
                @endif

                    <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-amber-500">
                        <p class="text-sm text-gray-500">Devido a criadores e afiliados</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">R$ {{ number_format($owedToCreators, 2, ',', '.') }}</p>
                        <p class="text-xs text-gray-400 mt-1">Não é seu — está na conta, mas eles podem sacar a qualquer momento (inclui o que ainda está no prazo de liberação).</p>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-blue-700">
                    <p class="text-sm text-gray-500">Caixa da plataforma</p>
                    @if($platformCash !== null)
                        <p class="text-3xl font-bold {{ $platformCash >= 0 ? 'text-gray-900' : 'text-red-600' }} mt-1">R$ {{ number_format($platformCash, 2, ',', '.') }}</p>
                        <p class="text-xs text-gray-400 mt-1">O que é de fato seu, e o que dá pra sacar: saldo real menos o que é dos criadores e afiliados.</p>
                        @if($platformCash < 0)
                            <p class="text-xs text-red-600 mt-2 font-medium">Negativo: a conta não cobre o que os criadores podem sacar. Confira se falta importar extrato ou se houve retirada manual demais.</p>
                        @endif
                    @else
                        <p class="text-sm text-gray-500 mt-2">Precisa do saldo real pra calcular. Importe o extrato do SuitPay.</p>
                    @endif
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
            <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-amber-500">
                <p class="text-sm text-gray-500">Comissão dos criadores</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">R$ {{ number_format($creatorPaid, 2, ',', '.') }}</p>
                <p class="text-xs text-gray-400 mt-1">Creditada nas vendas do período (não é o que foi sacado)</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-purple-500">
                <p class="text-sm text-gray-500">Comissão dos afiliados</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">R$ {{ number_format($affiliatePaid, 2, ',', '.') }}</p>
                <p class="text-xs text-gray-400 mt-1">Creditada nas vendas do período (não é o que foi sacado)</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-gray-400">
                <p class="text-sm text-gray-500">Total sacado (saídas)</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">R$ {{ number_format($cashoutTotal, 2, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-blue-700">
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
                                        @if($e->entry_type === 'cashout')
                                            {{-- por que a plataforma fica negativa aqui: a franquia do 1º saque do dia sai do nosso bolso --}}
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold cursor-help {{ $e->withdraw_fee > 0 ? 'bg-gray-100 text-gray-500' : 'bg-red-50 text-red-600' }}"
                                                  title="{{ $e->withdraw_fee > 0
                                                        ? 'Saque extra: não foi o primeiro do dia, então a taxa de 3,5% da SuitPay foi cobrada de quem sacou.'
                                                        : 'Saque grátis porque foi o primeiro do dia: a taxa de 3,5% da SuitPay ficou por nossa conta.' }}">
                                                {{ $e->withdraw_fee > 0 ? 'extra' : 'grátis' }}
                                            </span>
                                        @endif
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
                                    <td class="px-4 py-4 whitespace-nowrap text-sm {{ $platform >= 0 ? 'text-blue-700' : 'text-red-600' }} font-medium"
                                        @if($e->entry_type === 'cashout') title="{{ $e->withdraw_fee > 0 ? 'Saque extra: a taxa de 3,5% foi cobrada de quem sacou, então a plataforma fica zero a zero.' : 'Saque grátis do dia: a taxa de 3,5% da SuitPay ficou por nossa conta.' }}" @endif>
                                        R$ {{ number_format(round($platform, 2), 2, ',', '.') }}
                                    </td>
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
                                <td class="px-4 py-3 text-sm {{ $filtered['platform'] >= 0 ? 'text-blue-700' : 'text-red-600' }}">R$ {{ number_format(round($filtered['platform'], 2), 2, ',', '.') }}</td>
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
