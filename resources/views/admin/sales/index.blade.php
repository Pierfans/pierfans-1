@extends('layouts.admin')

@section('title', 'Vendas por Criador')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Vendas por Criador</h1>
            <p class="text-gray-600 mt-2">Desempenho de cada influenciador — valor gerado, nº de vendas e pacotes vendidos</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
            <form method="GET" action="{{ route('admin.vendas.index') }}" class="flex flex-wrap items-end gap-3">
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
                        <option value="sub" @selected($tipo === 'sub')>Assinatura</option>
                        <option value="ppv" @selected($tipo === 'ppv')>Conteúdo Único</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Agrupar por</label>
                    <select name="grupo" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="criador" @selected($grupo === 'criador')>Criador</option>
                        <option value="cliente" @selected($grupo === 'cliente')>Cliente</option>
                        <option value="afiliado" @selected($grupo === 'afiliado')>Afiliado</option>
                        <option value="dia" @selected($grupo === 'dia')>Dia</option>
                        <option value="mes" @selected($grupo === 'mes')>Mês</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Criador</label>
                    <div id="creatorChips" class="flex flex-wrap items-center gap-1 min-w-[220px] px-2 py-1 border border-gray-300 rounded-lg text-sm bg-white cursor-text">
                        <input type="text" id="creatorInput" list="creators-list" autocomplete="off" placeholder="Nome ou @usuário"
                               class="flex-1 min-w-[120px] border-0 outline-none focus:ring-0 py-1 text-sm">
                    </div>
                    <datalist id="creators-list">
                        @foreach($allCreators as $c)
                            <option value="{{ $c->name }}"></option>
                            @if($c->username)<option value="{{ $c->username }}"></option>@endif
                        @endforeach
                    </datalist>
                </div>
                <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-700 text-sm font-medium">Filtrar</button>
                <a href="{{ route('admin.vendas.index', array_merge(request()->query(), ['export' => 'csv'])) }}"
                   class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium">Exportar CSV</a>
            </form>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-green-500">
                <p class="text-sm text-gray-500">Bruto vendido</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">R$ {{ number_format($totGross, 2, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-blue-500">
                <p class="text-sm text-gray-500">Assinaturas</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $totSubs }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-purple-500">
                <p class="text-sm text-gray-500">Conteúdo Único (pacotes)</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $totPpv }}</p>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            @if($grupo === 'criador')
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-900">Ranking</h2>
                    <span class="px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-sm font-semibold">{{ $rows->count() }} criador(es)</span>
                </div>

                @if($rows->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Criador</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assinaturas</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Conteúdo Único</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bruto vendido</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor do criador</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($rows as $r)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $r['name'] }}</div>
                                            <div class="text-xs text-gray-500">{{ '@' . $r['username'] }}</div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">{{ $r['subs_qtd'] }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">{{ $r['ppv_qtd'] }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">R$ {{ number_format($r['gross'], 2, ',', '.') }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-blue-600">R$ {{ number_format($r['creator_amount'], 2, ',', '.') }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm">
                                            <a href="{{ route('admin.vendas.show', array_merge(['creatorId' => $r['creator_id']], request()->only('from', 'to'))) }}"
                                               class="text-indigo-600 hover:text-indigo-900 font-medium">Ver compradores</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-12 text-gray-500"><p>Nenhuma venda no período selecionado.</p></div>
                @endif
            @elseif($grupo === 'cliente')
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-900">Vendas por cliente</h2>
                    <span class="px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-sm font-semibold">{{ $clienteRows->count() }} cliente(s)</span>
                </div>

                @if($clienteRows->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assinaturas</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Conteúdo Único</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total gasto</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($clienteRows as $r)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $r['name'] }}</div>
                                            <div class="text-xs text-gray-500">{{ '@' . $r['username'] }}</div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">{{ $r['subs_qtd'] }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">{{ $r['ppv_qtd'] }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">R$ {{ number_format($r['gross'], 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                                <tr class="font-semibold text-gray-900">
                                    <td class="px-4 py-3 text-sm">Total</td>
                                    <td class="px-4 py-3 text-sm">{{ $clienteRows->sum('subs_qtd') }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $clienteRows->sum('ppv_qtd') }}</td>
                                    <td class="px-4 py-3 text-sm">R$ {{ number_format($clienteRows->sum('gross'), 2, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="text-center py-12 text-gray-500"><p>Nenhuma venda no período selecionado.</p></div>
                @endif
            @elseif($grupo === 'afiliado')
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-900">Vendas por afiliado</h2>
                    <span class="px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-sm font-semibold">{{ $afiliadoRows->count() }} afiliado(s)</span>
                </div>

                @if($afiliadoRows->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Afiliado</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendas atribuídas</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bruto gerado</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comissão do afiliado</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($afiliadoRows as $r)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $r['name'] }}</div>
                                            <div class="text-xs text-gray-500">{{ '@' . $r['username'] }}</div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">{{ $r['qtd'] }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">R$ {{ number_format($r['gross'], 2, ',', '.') }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-purple-600">R$ {{ number_format($r['comissao'], 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                                <tr class="font-semibold text-gray-900">
                                    <td class="px-4 py-3 text-sm">Total</td>
                                    <td class="px-4 py-3 text-sm">{{ $afiliadoRows->sum('qtd') }}</td>
                                    <td class="px-4 py-3 text-sm">R$ {{ number_format($afiliadoRows->sum('gross'), 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-sm text-purple-600">R$ {{ number_format($afiliadoRows->sum('comissao'), 2, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="text-center py-12 text-gray-500"><p>Nenhuma venda com afiliado no período. (Só assinaturas de criadores indicados por afiliado geram atribuição.)</p></div>
                @endif
            @else
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-900">{{ $grupo === 'mes' ? 'Vendas por mês' : 'Vendas por dia' }}</h2>
                    <span class="px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-sm font-semibold">{{ $timeRows->count() }} {{ $grupo === 'mes' ? 'mês(es)' : 'dia(s)' }}</span>
                </div>

                @if($timeRows->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $grupo === 'mes' ? 'Mês' : 'Dia' }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assinaturas</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Conteúdo Único</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bruto vendido</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor dos criadores</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($timeRows as $r)
                                    @php
                                        $rotulo = $grupo === 'mes'
                                            ? substr($r['periodo'], 5, 2) . '/' . substr($r['periodo'], 0, 4)
                                            : \Illuminate\Support\Carbon::parse($r['periodo'])->format('d/m/Y');
                                    @endphp
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $rotulo }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">{{ $r['subs_qtd'] }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">{{ $r['ppv_qtd'] }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">R$ {{ number_format($r['gross'], 2, ',', '.') }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-blue-600">R$ {{ number_format($r['creator_amount'], 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                                <tr class="font-semibold text-gray-900">
                                    <td class="px-4 py-3 text-sm">Total</td>
                                    <td class="px-4 py-3 text-sm">{{ $timeRows->sum('subs_qtd') }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $timeRows->sum('ppv_qtd') }}</td>
                                    <td class="px-4 py-3 text-sm">R$ {{ number_format($timeRows->sum('gross'), 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-sm text-blue-600">R$ {{ number_format($timeRows->sum('creator_amount'), 2, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="text-center py-12 text-gray-500"><p>Nenhuma venda no período selecionado.</p></div>
                @endif
            @endif
        </div>
    </div>

    <script>
    (function () {
        var box = document.getElementById('creatorChips');
        var input = document.getElementById('creatorInput');
        var form = input.closest('form');
        var options = new Set(
            Array.prototype.map.call(document.querySelectorAll('#creators-list option'), function (o) {
                return o.value.toLowerCase();
            })
        );

        function addChip(value) {
            value = value.trim();
            if (!value) return;
            var exists = Array.prototype.some.call(box.querySelectorAll('input[name="creator[]"]'), function (h) {
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
            hidden.name = 'creator[]';
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

        // Selecionou uma sugestao do datalist -> vira chip na hora
        input.addEventListener('input', function () {
            if (options.has(input.value.trim().toLowerCase())) addChip(input.value);
        });

        // Nao perde um termo digitado sem Enter ao filtrar
        form.addEventListener('submit', function () {
            if (input.value.trim()) addChip(input.value);
        });

        // Recria os chips dos termos ja aplicados
        (@json($creatorTerms) || []).forEach(addChip);
    })();
    </script>
@endsection
