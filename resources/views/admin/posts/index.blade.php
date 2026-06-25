@extends('layouts.admin')

@section('title', 'Gerenciar Postagens')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Gerenciar Postagens</h1>
            <p class="text-gray-600 mt-2">Todas as postagens da plataforma</p>
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

