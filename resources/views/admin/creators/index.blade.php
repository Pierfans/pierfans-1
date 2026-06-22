@extends('layouts.admin')

@section('title', 'Gerenciar Criadores')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Gerenciar Criadores</h1>
            <p class="text-gray-600 mt-2">Todos os criadores do sistema</p>
        </div>

        <!-- Filtros e Busca -->
        <div class="mb-6 bg-white rounded-lg shadow-sm p-4">
            <form method="GET" action="{{ route('admin.creators.index') }}" class="flex flex-col md:flex-row items-start md:items-center gap-4">
                <!-- Busca -->
                <div class="flex-1 w-full md:w-auto">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}"
                        placeholder="Buscar por nome ou email..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                </div>
                <!-- Filtro por status -->
                <div class="flex items-center gap-2">
                    <label class="text-sm font-medium text-gray-700">Status:</label>
                    <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="all" {{ request('status') === 'all' || !request('status') ? 'selected' : '' }}>Todos</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pendentes</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Aprovados</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejeitados</option>
                    </select>
                </div>
                <!-- Botão Buscar -->
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Buscar
                </button>
                @if(request('search') || request('status'))
                    <a href="{{ route('admin.creators.index') }}" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Limpar
                    </a>
                @endif
            </form>
        </div>

        @if($creators->count() > 0)
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CPF</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data de Envio</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($creators as $creator)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $creator->creator_full_name ?? $creator->name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500">{{ $creator->email }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500">{{ $creator->creator_cpf ?? 'N/A' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($creator->creator_status === 'approved')
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Aprovado</span>
                                        @elseif($creator->creator_status === 'pending')
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pendente</span>
                                        @elseif($creator->creator_status === 'rejected')
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Rejeitado</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500">
                                            {{ $creator->creator_submitted_at ? $creator->creator_submitted_at->format('d/m/Y H:i') : 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="{{ route('admin.creators.show', $creator->id) }}" 
                                               class="text-blue-600 hover:text-blue-900">Ver Detalhes</a>
                                            @if($creator->creator_status === 'pending')
                                                <button onclick="approveCreator({{ $creator->id }})" 
                                                        class="text-green-600 hover:text-green-900">Aprovar</button>
                                                <button onclick="rejectCreator({{ $creator->id }})" 
                                                        class="text-red-600 hover:text-red-900">Reprovar</button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Paginação -->
            <div class="mt-6">
                {{ $creators->links() }}
            </div>
        @else
            <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                <p class="text-gray-600">Nenhum criador encontrado.</p>
            </div>
        @endif
    </div>

    <script>
        function approveCreator(id) {
            if (!confirm('Tem certeza que deseja aprovar este criador?')) {
                return;
            }

            $.ajax({
                url: `/admin/creators/${id}/approve`,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        alert('Criador aprovado com sucesso!');
                        location.reload();
                    }
                },
                error: function(xhr) {
                    alert(xhr.responseJSON?.message || 'Erro ao aprovar criador');
                }
            });
        }

        function rejectCreator(id) {
            if (!confirm('Tem certeza que deseja reprovar este criador? Ele poderá reenviar os documentos.')) {
                return;
            }

            $.ajax({
                url: `/admin/creators/${id}/reject`,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        alert('Criador reprovado. O usuário poderá reenviar os documentos.');
                        location.reload();
                    }
                },
                error: function(xhr) {
                    alert(xhr.responseJSON?.message || 'Erro ao reprovar criador');
                }
            });
        }
    </script>
@endsection

