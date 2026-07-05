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
                    <label class="block text-xs text-gray-500 mb-1">Criador</label>
                    <select name="creator" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="">Todos</option>
                        @foreach($allCreators as $c)
                            <option value="{{ $c->id }}" @selected($creator === $c->id)>{{ $c->name }} ({{ '@' . $c->username }})</option>
                        @endforeach
                    </select>
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
        </div>
    </div>
@endsection
