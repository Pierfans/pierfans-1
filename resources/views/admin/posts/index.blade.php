@extends('layouts.admin')

@section('title', 'Gerenciar Postagens')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Gerenciar Postagens</h1>
            <p class="text-gray-600 mt-2">Todas as postagens da plataforma</p>
        </div>

        <!-- Filtros -->
        <form method="GET" action="{{ route('admin.posts.index') }}" class="bg-white rounded-lg shadow-sm p-4 mb-6 flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Criador</label>
                <select name="creator_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Todos</option>
                    @foreach($creators as $creator)
                        <option value="{{ $creator->id }}" @selected(request('creator_id') == $creator->id)>{{ $creator->name }} ({{ '@'.$creator->username }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Status</label>
                <select name="visibility" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Todos</option>
                    <option value="free" @selected(request('visibility') === 'free')>Gratuito</option>
                    <option value="subscriber" @selected(request('visibility') === 'subscriber')>Assinantes</option>
                    <option value="paid" @selected(request('visibility') === 'paid')>Conteúdo Único</option>
                </select>
            </div>
            <button type="submit" class="bg-pink-600 hover:bg-pink-700 text-white text-sm font-medium px-4 py-2 rounded-lg">Filtrar</button>
            @if(request('creator_id') || request('visibility'))
                <a href="{{ route('admin.posts.index') }}" class="text-sm text-gray-500 hover:text-gray-700 px-2 py-2">Limpar</a>
            @endif
        </form>

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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
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
                                        @if($post->visibility === 'free')
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Gratuito</span>
                                        @elseif($post->visibility === 'paid')
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">Conteúdo Único</span>
                                        @else
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">Assinantes</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $post->media->count() }} arquivo(s)</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500">
                                            {{ $post->created_at->format('d/m/Y H:i') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="{{ route('admin.posts.show', $post->id) }}" 
                                               class="text-blue-600 hover:text-blue-900">Ver</a>
                                            <a href="{{ route('admin.posts.edit', $post->id) }}"
                                               class="text-green-600 hover:text-green-900">Editar</a>
                                            <button onclick="disablePost({{ $post->id }})"
                                                    class="text-orange-600 hover:text-orange-900">Desabilitar</button>
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
                <p class="text-gray-600">Nenhuma postagem encontrada.</p>
            </div>
        @endif
    </div>

    <script>
        function disablePost(id) {
            if (!confirm('Desabilitar esta postagem? Ela some do site e vai para a Lixeira, mas pode ser restaurada.')) {
                return;
            }

            $.ajax({
                url: `/admin/posts/${id}/disable`,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        location.reload();
                    }
                },
                error: function(xhr) {
                    alert(xhr.responseJSON?.message || 'Erro ao desabilitar postagem');
                }
            });
        }

        function deletePost(id) {
            if (!confirm('Tem certeza que deseja deletar esta postagem? Esta ação não pode ser desfeita.')) {
                return;
            }

            $.ajax({
                url: `/admin/posts/${id}`,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        alert('Postagem deletada com sucesso!');
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

