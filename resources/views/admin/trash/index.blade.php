@extends('layouts.admin')

@section('title', 'Lixeira')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Lixeira</h1>
            <p class="text-gray-600 mt-2">Postagens deletadas pelos usuários</p>
        </div>

        @if($posts->count() > 0)
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Criador</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Visibilidade</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mídias</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deletado em</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($posts as $post)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $post->user->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $post->user->email }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 max-w-xs truncate">
                                            {{ Str::limit($post->description ?? 'Sem descrição', 50) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $post->visibility === 'free' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                            {{ $post->visibility === 'free' ? 'Gratuito' : 'Assinantes' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $post->media->count() }} arquivo(s)</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500">
                                            {{ $post->deleted_by_user_at->format('d/m/Y H:i') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="{{ route('admin.trash.show', $post->id) }}" 
                                               class="text-blue-600 hover:text-blue-900">Ver</a>
                                            <button onclick="restorePost({{ $post->id }})" 
                                                    class="text-green-600 hover:text-green-900">Restaurar</button>
                                            <button onclick="deletePost({{ $post->id }})" 
                                                    class="text-red-600 hover:text-red-900">Deletar</button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                @if($posts->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Mostrando {{ $posts->firstItem() }} a {{ $posts->lastItem() }} de {{ $posts->total() }} resultados
                            </div>
                            <div class="flex space-x-2">
                                @if($posts->onFirstPage())
                                    <span class="px-3 py-1 text-gray-400 cursor-not-allowed">Anterior</span>
                                @else
                                    <a href="{{ $posts->previousPageUrl() }}" class="px-3 py-1 text-blue-600 hover:text-blue-800">Anterior</a>
                                @endif
                                
                                @if($posts->hasMorePages())
                                    <a href="{{ $posts->nextPageUrl() }}" class="px-3 py-1 text-blue-600 hover:text-blue-800">Próximo</a>
                                @else
                                    <span class="px-3 py-1 text-gray-400 cursor-not-allowed">Próximo</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @else
            <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <p class="text-gray-600 text-lg font-medium">Lixeira vazia</p>
                <p class="text-gray-500 mt-2">Nenhuma postagem foi deletada pelos usuários</p>
            </div>
        @endif
    </div>

    <script>
        function restorePost(id) {
            if (!confirm('Tem certeza que deseja restaurar esta postagem?')) {
                return;
            }

            $.ajax({
                url: `/admin/trash/${id}/restore`,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        alert('Postagem restaurada com sucesso!');
                        location.reload();
                    }
                },
                error: function(xhr) {
                    alert(xhr.responseJSON?.message || 'Erro ao restaurar postagem');
                }
            });
        }

        function deletePost(id) {
            if (!confirm('Tem certeza que deseja deletar PERMANENTEMENTE esta postagem? Esta ação NÃO pode ser desfeita!')) {
                return;
            }

            $.ajax({
                url: `/admin/trash/${id}`,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        alert('Postagem deletada permanentemente!');
                        location.reload();
                    }
                },
                error: function(xhr) {
                    alert(xhr.responseJSON?.message || 'Erro ao deletar postagem');
                }
            });
        }
    </script>
@endsection
