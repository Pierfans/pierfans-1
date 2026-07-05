@extends('layouts.admin')

@section('title', 'Fluxo de Caixa')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Fluxo de Caixa</h1>
            <p class="text-gray-600 mt-2">Entradas, taxas do SuitPay e repasses — receita líquida real da plataforma</p>
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
                    <label class="block text-xs text-gray-500 mb-1">Pessoa</label>
                    <div id="pessoaChips" class="flex flex-wrap items-center gap-1 min-w-[220px] px-2 py-1 border border-gray-300 rounded-lg text-sm bg-white cursor-text">
                        <input type="text" id="pessoaInput" list="pessoas-list" autocomplete="off" placeholder="Nome ou @usuário"
                               class="flex-1 min-w-[120px] border-0 outline-none focus:ring-0 py-1 text-sm">
                    </div>
                    <datalist id="pessoas-list">
                        @foreach($allCreators as $c)
                            <option value="{{ $c->name }}"></option>
                            @if($c->username)<option value="{{ $c->username }}"></option>@endif
                        @endforeach
                    </datalist>
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
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pessoa</th>
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

                                    // Pessoa da linha: solicitante no saque, criador na venda (+ comprador como contexto).
                                    if ($e->entry_type === 'cashout') {
                                        $person  = $e->withdrawal?->user;
                                        $context = $e->withdrawal?->type === 'affiliate' ? 'afiliado' : 'criador';
                                    } else {
                                        $person  = $e->paymentTransaction?->creator;
                                        $buyer   = $e->paymentTransaction?->user;
                                        $context = $buyer ? 'comprado por ' . $buyer->name : null;
                                    }
                                    $palette = ['bg-indigo-500', 'bg-pink-500', 'bg-emerald-500', 'bg-amber-500', 'bg-sky-500', 'bg-purple-500', 'bg-rose-500', 'bg-teal-500'];
                                    $avatarColor = $person ? $palette[crc32($person->name) % count($palette)] : 'bg-gray-300';
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">{{ $e->occurred_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                                            {{ $e->entry_type === 'cashout' ? 'bg-gray-100 text-gray-700' : 'bg-green-100 text-green-700' }}">
                                            {{ $label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">
                                        @if($person)
                                            <div class="flex items-center gap-2">
                                                <div class="w-8 h-8 shrink-0 rounded-full flex items-center justify-center text-xs font-semibold text-white {{ $avatarColor }}">
                                                    {{ mb_strtoupper(mb_substr($person->name, 0, 1)) }}
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="text-sm font-medium text-gray-900 truncate">{{ $person->name }}</div>
                                                    <div class="text-xs text-gray-400 truncate">
                                                        {{ '@' . $person->username }}@if($context) · {{ $context }}@endif
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-sm text-gray-300">—</span>
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

    <script>
    (function () {
        var box = document.getElementById('pessoaChips');
        var input = document.getElementById('pessoaInput');
        var form = input.closest('form');
        var options = new Set(
            Array.prototype.map.call(document.querySelectorAll('#pessoas-list option'), function (o) {
                return o.value.toLowerCase();
            })
        );

        function addChip(value) {
            value = value.trim();
            if (!value) return;
            var exists = Array.prototype.some.call(box.querySelectorAll('input[name="pessoa[]"]'), function (h) {
                return h.value.toLowerCase() === value.toLowerCase();
            });
            if (exists) { input.value = ''; return; }
            var chip = document.createElement('span');
            chip.className = 'inline-flex items-center gap-1 bg-indigo-100 text-indigo-800 rounded-full px-2 py-0.5 text-xs font-medium';
            var label = document.createElement('span');
            label.textContent = value;
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'text-indigo-500 hover:text-indigo-800';
            btn.innerHTML = '&times;';
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'pessoa[]';
            hidden.value = value;
            btn.addEventListener('click', function () { chip.remove(); });
            chip.appendChild(label);
            chip.appendChild(btn);
            chip.appendChild(hidden);
            box.insertBefore(chip, input);
            input.value = '';
        }

        box.addEventListener('click', function () { input.focus(); });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                addChip(input.value);
            } else if (e.key === 'Backspace' && input.value === '') {
                var chips = box.querySelectorAll(':scope > span');
                if (chips.length) chips[chips.length - 1].remove();
            }
        });

        input.addEventListener('input', function () {
            if (options.has(input.value.trim().toLowerCase())) addChip(input.value);
        });

        form.addEventListener('submit', function () {
            if (input.value.trim()) addChip(input.value);
        });

        (@json($personTerms) || []).forEach(addChip);
    })();
    </script>
@endsection
