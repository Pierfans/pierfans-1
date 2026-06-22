@extends('layouts.admin')

@section('title', 'Editar Postagem')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <a href="{{ route('admin.posts.index') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
                ← Voltar
            </a>
            <h1 class="text-3xl font-bold text-gray-900">Editar Postagem</h1>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <form id="editPostForm" class="space-y-6">
                @csrf

                <!-- Descrição -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Descrição
                    </label>
                    <textarea 
                        id="description" 
                        name="description" 
                        rows="6"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent resize-y"
                    >{{ $post->description }}</textarea>
                    <div id="description-error" class="text-red-500 text-sm mt-1 hidden"></div>
                </div>

                <!-- Visibilidade -->
                <div>
                    <label for="visibility" class="block text-sm font-medium text-gray-700 mb-2">
                        Visibilidade
                    </label>
                    <select 
                        id="visibility" 
                        name="visibility"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                    >
                        <option value="free" {{ $post->visibility === 'free' ? 'selected' : '' }}>Gratuito</option>
                        <option value="subscriber" {{ $post->visibility === 'subscriber' ? 'selected' : '' }}>Somente Assinantes</option>
                    </select>
                    <div id="visibility-error" class="text-red-500 text-sm mt-1 hidden"></div>
                </div>

                <!-- Mídias Existentes -->
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <label class="block text-sm font-medium text-gray-700">
                            Mídias Existentes
                        </label>
                        <button type="button" onclick="deleteAllMedia({{ $post->id }})" 
                                class="text-sm text-red-600 hover:text-red-800">
                            Deletar Todas
                        </button>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        @foreach($post->media as $media)
                            <div class="relative group">
                                @if($media->file_type === 'video')
                                    <video class="w-full h-32 object-cover rounded-lg border border-gray-300" controls>
                                        <source src="{{ $media->url }}" type="video/mp4">
                                    </video>
                                @else
                                    <img src="{{ $media->url }}" 
                                         alt="Mídia" 
                                         class="w-full h-32 object-cover rounded-lg border border-gray-300">
                                @endif
                                <button type="button" onclick="deleteMedia({{ $post->id }}, {{ $media->id }})" 
                                        class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Botões -->
                <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                    <a href="{{ route('admin.posts.index') }}" 
                       class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancelar
                    </a>
                    <button 
                        type="submit"
                        id="submitBtn"
                        class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
                    >
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        $('#editPostForm').on('submit', function(e) {
            e.preventDefault();

            const formData = new FormData();
            formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
            formData.append('_method', 'PUT');
            formData.append('description', $('#description').val());
            formData.append('visibility', $('#visibility').val());

            $('#submitBtn').prop('disabled', true).text('Salvando...');

            $.ajax({
                url: `/admin/posts/{{ $post->id }}`,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        alert('Postagem atualizada com sucesso!');
                        window.location.href = '/admin/posts';
                    }
                },
                error: function(xhr) {
                    $('#submitBtn').prop('disabled', false).text('Salvar Alterações');
                    
                    const errors = xhr.responseJSON?.errors || {};
                    Object.keys(errors).forEach(function(field) {
                        $(`#${field}-error`).removeClass('hidden').text(errors[field][0]);
                    });

                    if (xhr.responseJSON?.message) {
                        alert(xhr.responseJSON.message);
                    }
                }
            });
        });

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
    </script>
@endsection

