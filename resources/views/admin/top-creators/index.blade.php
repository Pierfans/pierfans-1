@extends('layouts.admin')

@section('title', 'TOP Criadores')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">TOP Criadores</h1>
            <p class="text-gray-600 mt-2">Gerencie quais criadores aparecem na seção "Top 5 Criadores" do dashboard</p>
            @if($topCreatorsCount > 0)
                <p class="text-sm text-gray-500 mt-1">
                    <span class="font-semibold">{{ $topCreatorsCount }}</span> criador(es) atualmente no TOP
                </p>
            @endif
        </div>

        <!-- Busca -->
        <div class="mb-6 bg-white rounded-lg shadow-sm p-4">
            <form method="GET" action="{{ route('admin.top-creators.index') }}" class="flex flex-col md:flex-row items-start md:items-center gap-4">
                <div class="flex-1 w-full md:w-auto">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}"
                        placeholder="Buscar por nome, email ou username..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                </div>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Buscar
                </button>
                @if(request('search'))
                    <a href="{{ route('admin.top-creators.index') }}" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Criador</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status TOP</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($creators as $creator)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            @if($creator->profile_photo_url)
                                                <img src="{{ $creator->profile_photo_url }}" 
                                                     alt="{{ $creator->name ?? $creator->creator_full_name }}" 
                                                     class="w-10 h-10 rounded-full object-cover mr-3">
                                            @else
                                                <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center mr-3">
                                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                    </svg>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $creator->name ?? $creator->creator_full_name ?? 'Sem nome' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $creator->username }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500">{{ $creator->email }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        @if($creator->featured_in_top_creators)
                                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-[#FF6B35] text-white">
                                                No TOP
                                            </span>
                                        @else
                                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-gray-200 text-gray-700">
                                                Fora do TOP
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input 
                                                type="checkbox" 
                                                class="sr-only peer"
                                                {{ $creator->featured_in_top_creators ? 'checked' : '' }}
                                                onchange="toggleTopCreator({{ $creator->id }}, this.checked)"
                                                id="creator-{{ $creator->id }}"
                                            >
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#FF6B35]"></div>
                                        </label>
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
                <p class="text-gray-600">Nenhum criador aprovado encontrado.</p>
            </div>
        @endif
    </div>

    <script>
        function toggleTopCreator(creatorId, isFeatured) {
            $.ajax({
                url: `/admin/top-creators/${creatorId}/toggle`,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                data: JSON.stringify({
                    featured_in_top_creators: isFeatured
                }),
                success: function(response) {
                    if (response.success) {
                        // Atualiza o status visual na tabela
                        const row = document.querySelector(`#creator-${creatorId}`).closest('tr');
                        const statusCell = row.querySelector('td:nth-child(4)');
                        
                        if (response.featured_in_top_creators) {
                            statusCell.innerHTML = '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-[#FF6B35] text-white">No TOP</span>';
                        } else {
                            statusCell.innerHTML = '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-gray-200 text-gray-700">Fora do TOP</span>';
                        }
                        
                        // Recarrega a página para atualizar o contador
                        setTimeout(() => {
                            location.reload();
                        }, 500);
                    }
                },
                error: function(xhr) {
                    // Reverte o checkbox em caso de erro
                    const checkbox = document.getElementById(`creator-${creatorId}`);
                    checkbox.checked = !isFeatured;
                    
                    alert(xhr.responseJSON?.message || 'Erro ao atualizar status do criador no TOP');
                }
            });
        }
    </script>
@endsection

