@extends('layouts.admin')

@section('title', 'Detalhes da Postagem')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <a href="{{ route('admin.posts.index') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
                ← Voltar
            </a>
            <h1 class="text-3xl font-bold text-gray-900">Detalhes da Postagem</h1>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 space-y-6">
            <!-- Informações do Criador -->
            <div class="border-b border-gray-200 pb-4">
                <h2 class="text-lg font-semibold text-gray-900 mb-2">Criador</h2>
                <p class="text-gray-700">{{ $post->user->name }}</p>
                <p class="text-sm text-gray-500">{{ $post->user->email }}</p>
            </div>

            <!-- Descrição -->
            <div>
                <h2 class="text-lg font-semibold text-gray-900 mb-2">Descrição</h2>
                <p class="text-gray-700 whitespace-pre-wrap">{{ $post->description ?? 'Sem descrição' }}</p>
            </div>

            <!-- Visibilidade -->
            <div>
                <h2 class="text-lg font-semibold text-gray-900 mb-2">Visibilidade</h2>
                @if($post->visibility === 'free')
                    <span class="px-3 py-1 text-sm font-medium rounded-full bg-green-100 text-green-800">Gratuito</span>
                @elseif($post->visibility === 'paid')
                    <span class="px-3 py-1 text-sm font-medium rounded-full bg-purple-100 text-purple-800">Conteúdo Único</span>
                @else
                    <span class="px-3 py-1 text-sm font-medium rounded-full bg-blue-100 text-blue-800">Somente Assinantes</span>
                @endif
            </div>

            <!-- Mídias -->
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Mídias</h2>
                    <button onclick="deleteAllMedia({{ $post->id }})" 
                            class="text-sm text-red-600 hover:text-red-800">
                        Deletar Todas as Mídias
                    </button>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach($post->media as $media)
                        <div class="relative group">
                            @if($media->file_type === 'video')
                                <video class="w-full h-48 object-cover rounded-lg border border-gray-300" controls>
                                    <source src="{{ $media->url }}" type="video/mp4">
                                </video>
                            @else
                                <img src="{{ $media->url }}" 
                                     alt="Mídia" 
                                     class="w-full h-48 object-cover rounded-lg border border-gray-300">
                            @endif
                            <button onclick="deleteMedia({{ $post->id }}, {{ $media->id }})" 
                                    class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-8 h-8 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Ações -->
            <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                <a href="{{ route('admin.posts.edit', $post->id) }}" 
                   class="px-6 py-2 border border-blue-300 text-blue-600 rounded-lg hover:bg-blue-50 transition-colors">
                    Editar
                </a>
                <button onclick="deletePost({{ $post->id }})" 
                        class="px-6 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                    Deletar Postagem
                </button>
            </div>
        </div>
    </div>

    <script>
        function deleteMedia(postId, mediaId) {
            if (!confirm('Tem certeza que deseja deletar esta mídia?')) {
                return;
            }

            $.ajax({
                url: `/admin/posts/${postId}/media/${mediaId}`,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        alert('Mídia deletada com sucesso!');
                        location.reload();
                    }
                },
                error: function(xhr) {
                    alert(xhr.responseJSON?.message || 'Erro ao deletar mídia');
                }
            });
        }

        function deleteAllMedia(postId) {
            if (!confirm('Tem certeza que deseja deletar TODAS as mídias desta postagem?')) {
                return;
            }

            $.ajax({
                url: `/admin/posts/${postId}/media`,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        alert('Todas as mídias foram deletadas!');
                        location.reload();
                    }
                },
                error: function(xhr) {
                    alert(xhr.responseJSON?.message || 'Erro ao deletar mídias');
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
                        window.location.href = '/admin/posts';
                    }
                },
                error: function(xhr) {
                    alert(xhr.responseJSON?.message || 'Erro ao deletar postagem');
                }
            });
        }
    </script>
@endsection

