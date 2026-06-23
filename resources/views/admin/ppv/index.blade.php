@extends('layouts.admin')

@section('title', 'Conteúdo Único (PPV)')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Conteúdo Único (PPV)</h1>
            <p class="text-gray-600 mt-2">Todas as compras de Conteúdo Único da plataforma</p>
        </div>

        <!-- Cards de totais -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-indigo-500">
                <p class="text-sm text-gray-500">Total de vendas</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $totalCount }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-green-500">
                <p class="text-sm text-gray-500">Volume total</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">R$ {{ number_format($totalRevenue, 2, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-orange-500">
                <p class="text-sm text-gray-500">Receita da plataforma</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">R$ {{ number_format($platformRevenue, 2, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-blue-500">
                <p class="text-sm text-gray-500">Pago aos criadores</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">R$ {{ number_format($creatorRevenue, 2, ',', '.') }}</p>
            </div>
        </div>

        <!-- Busca -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
            <form method="GET" action="{{ route('admin.ppv.index') }}" class="flex gap-3">
                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Buscar por comprador ou criador..."
                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                >
                <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-700 text-sm font-medium">
                    Buscar
                </button>
                @if($search)
                    <a href="{{ route('admin.ppv.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium">
                        Limpar
                    </a>
                @endif
            </form>
        </div>

        <!-- Tabela -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-900">Compras</h2>
                <span class="px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-sm font-semibold">
                    {{ $purchases->total() }} registro(s)
                </span>
            </div>

            @if($purchases->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comprador</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Criador</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Post</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor pago</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plataforma</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Criador</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($purchases as $purchase)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                        #{{ $purchase->id }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $purchase->buyer->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $purchase->buyer->email }}</div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $purchase->creator->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $purchase->creator->username }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="text-sm text-gray-700 max-w-xs truncate">
                                            {{ $purchase->post ? mb_strimwidth($purchase->post->description ?? '', 0, 50, '...') : '—' }}
                                        </div>
                                        @if($purchase->post)
                                            <div class="text-xs text-gray-400">Post #{{ $purchase->post_id }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="text-sm font-semibold text-gray-900">
                                            R$ {{ number_format($purchase->amount_paid, 2, ',', '.') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="text-sm text-orange-600 font-medium">
                                            R$ {{ number_format($purchase->platform_amount, 2, ',', '.') }}
                                        </span>
                                        <div class="text-xs text-gray-400">{{ $purchase->platform_percentage }}%</div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="text-sm text-green-600 font-medium">
                                            R$ {{ number_format($purchase->creator_amount, 2, ',', '.') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            {{ $purchase->purchased_at->format('d/m/Y') }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $purchase->purchased_at->format('H:i') }}
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($purchases->hasPages())
                    <div class="mt-4">
                        {{ $purchases->links() }}
                    </div>
                @endif
            @else
                <div class="text-center py-12 text-gray-500">
                    @if($search)
                        <p>Nenhuma compra encontrada para "<strong>{{ $search }}</strong>".</p>
                    @else
                        <p>Ainda não há compras de Conteúdo Único.</p>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endsection
