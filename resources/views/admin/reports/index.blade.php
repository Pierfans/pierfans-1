@extends('layouts.admin')

@section('title', 'Denúncias')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Denúncias</h1>
            <p class="text-gray-600 mt-2">Gerenciar denúncias de postagens</p>
        </div>

        <!-- Filtros -->
        <div class="mb-6 bg-white rounded-lg shadow-sm p-4">
            <form method="GET" action="{{ route('admin.reports.index') }}" class="flex flex-col md:flex-row items-start md:items-center gap-4">
                <!-- Filtro por status -->
                <div class="flex items-center gap-2">
                    <label class="text-sm font-medium text-gray-700">Status:</label>
                    <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="all" {{ request('status') === 'all' || !request('status') ? 'selected' : '' }}>Todas</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pendentes</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Aprovadas</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejeitadas</option>
                    </select>
                </div>
                <!-- Botão Buscar -->
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Filtrar
                </button>
                @if(request('status'))
                    <a href="{{ route('admin.reports.index') }}" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Limpar
                    </a>
                @endif
            </form>
        </div>

        @if($reports->count() > 0)
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Postagem</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Denunciado por</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($reports as $report)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            Postagem #{{ $report->post_id }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            Criador: {{ $report->post->user->name }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $report->user->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $report->user->email }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $report->created_at->emBrasilia()->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($report->status === 'pending')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                Pendente
                                            </span>
                                        @elseif($report->status === 'approved')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                Aprovada
                                            </span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                Rejeitada
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="{{ route('admin.reports.show', $report->id) }}" class="text-blue-600 hover:text-blue-900">
                                            Ver detalhes
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginação -->
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $reports->links() }}
                </div>
            </div>
        @else
            <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                <p class="text-gray-600">Nenhuma denúncia encontrada.</p>
            </div>
        @endif
    </div>
@endsection

