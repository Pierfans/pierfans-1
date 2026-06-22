@extends('layouts.admin')

@section('title', 'Detalhes da Postagem (Lixeira)')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <a href="{{ route('admin.trash.index') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
                ← Voltar para Lixeira
            </a>
            <h1 class="text-3xl font-bold text-gray-900">Detalhes da Postagem (Lixeira)</h1>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 space-y-6">
            <!-- Status de Deleção -->
            <div class="bg-orange-50 border-l-4 border-orange-500 p-4">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-orange-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <p class="font-semibold text-orange-800">Postagem na Lixeira</p>
                        <p class="text-sm text-orange-700 mt-1">
                            Deletada pelo usuário em {{ $post->deleted_by_user_at->format('d/m/Y \à\s H:i') }}
                        </p>
                    </div>
                </div>
            </div>

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
                <span class="px-3 py-1 text-sm font-medium rounded-full {{ $post->visibility === 'free' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                    {{ $post->visibility === 'free' ? 'Gratuito' : 'Somente Assinantes' }}
                </span>
            </div>

            <!-- Data de Criação -->
            <div>
                <h2 class="text-lg font-semibold text-gray-900 mb-2">Data de Criação</h2>
                <p class="text-gray-700">{{ $post->created_at->format('d/m/Y H:i') }}</p>
            </div>

            <!-- Mídias -->
            <div>
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Mídias ({{ $post->media->count() }})</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach($post->media as $media)
                        <div class="relative">
                            @if($media->file_type === 'video')
                                <video class="w-full h-48 object-cover rounded-lg border border-gray-300" controls>
                                    <source src="{{ $media->url }}" type="video/mp4">
                                </video>
                            @else
                                <img src="{{ $media->url }}" 
                                     alt="Mídia" 
                                     class="w-full h-48 object-cover rounded-lg border border-gray-300">
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Ações -->
            <div class="flex justify-between pt-6 border-t border-gray-200">
                <button onclick="restorePost({{ $post->id }})" 
                        class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Restaurar Postagem
                </button>
                <button onclick="deletePost({{ $post->id }})" 
                        class="px-6 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Deletar Permanentemente
                </button>
            </div>
        </div>
    </div>

    <script>
        function restorePost(id) {
            if (!confirm('Tem certeza que deseja restaurar esta postagem? Ela voltará a ficar visível para todos.')) {
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
                        window.location.href = '/admin/trash';
                    }
                },
                error: function(xhr) {
                    alert(xhr.responseJSON?.message || 'Erro ao restaurar postagem');
                }
            });
        }

        function deletePost(id) {
            if (!confirm('⚠️ ATENÇÃO: Você está prestes a deletar esta postagem PERMANENTEMENTE!\n\nEsta ação irá:\n- Deletar todos os arquivos de mídia do servidor\n- Remover completamente a postagem do banco de dados\n- Esta ação NÃO pode ser desfeita!\n\nTem certeza que deseja continuar?')) {
                return;
            }

            // Segunda confirmação para ações críticas
            if (!confirm('Confirme novamente: Deletar permanentemente esta postagem?')) {
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
                        window.location.href = '/admin/trash';
                    }
                },
                error: function(xhr) {
                    alert(xhr.responseJSON?.message || 'Erro ao deletar postagem');
                }
            });
        }
    </script>
@endsection
