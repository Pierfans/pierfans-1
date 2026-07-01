@extends('layouts.admin')

@section('title', 'Compradores — ' . $creator->name)

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <a href="{{ route('admin.vendas.index', request()->only('from', 'to')) }}" class="text-sm text-indigo-600 hover:text-indigo-900">&larr; Voltar ao ranking</a>
                <h1 class="text-3xl font-bold text-gray-900 mt-1">{{ $creator->name }}</h1>
                <p class="text-gray-600">{{ '@' . $creator->username }} — quem comprou o conteúdo</p>
            </div>
            <a href="{{ route('admin.vendas.show', array_merge(['creatorId' => $creator->id], request()->only('from', 'to'), ['export' => 'csv'])) }}"
               class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium">Exportar CSV</a>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-900">Compras</h2>
                <span class="px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-sm font-semibold">{{ $sales->count() }} compra(s)</span>
            </div>

            @if($sales->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comprador</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Conteúdo</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bruto</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor do criador</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($sales as $s)
                                @php $b = $buyers->get($s['buyer_id']); @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">{{ $s['date']->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $b->name ?? '—' }}</div>
                                        <div class="text-xs text-gray-500">{{ $b ? '@' . $b->username : '' }}</div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $s['tipo'] === 'Assinatura' ? 'bg-green-100 text-green-700' : 'bg-purple-100 text-purple-700' }}">{{ $s['tipo'] }}</span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700">{{ $s['conteudo'] }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">R$ {{ number_format($s['gross'], 2, ',', '.') }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-blue-600">R$ {{ number_format($s['creator_amount'], 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12 text-gray-500"><p>Nenhuma compra no período selecionado.</p></div>
            @endif
        </div>
    </div>
@endsection
