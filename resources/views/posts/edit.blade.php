<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Editar Postagem - {{ config('app.name', 'Laravel') }}</title>
    
    <!-- TailwindCSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- jQuery via CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- heic2any - Conversão de HEIC para visualização -->
    <script src="https://cdn.jsdelivr.net/npm/heic2any@0.0.4/dist/heic2any.min.js"></script>
    
    <!-- Resumable.js - Upload por chunks -->
    <script src="https://cdn.jsdelivr.net/npm/resumablejs@1.1.0/resumable.min.js"></script>
    
    <!-- Estilos e scripts customizados -->
    <link rel="stylesheet" href="/css/app.css">
    <script src="/js/app.js"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Top Navigation (Desktop) -->
    <x-topnav />

    <!-- Bottom Navigation (Mobile) -->
    <x-bottomnav />

    <!-- Main Content -->
    <div class="pt-16 md:pt-16 pb-16 md:pb-0">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white rounded-lg shadow-sm p-6 md:p-8">
                <h1 class="text-2xl font-bold text-gray-900 mb-6">Editar Postagem</h1>

                <form id="editPostForm" class="space-y-6">
                    @csrf
                    @method('PUT')

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
                            placeholder="Descreva sua postagem..."
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
                            <option value="paid" {{ $post->visibility === 'paid' ? 'selected' : '' }}>Conteúdo Único (pago)</option>
                        </select>
                        <div id="visibility-error" class="text-red-500 text-sm mt-1 hidden"></div>

                        <!-- Campo de preço — visível apenas quando "Conteúdo Único" é selecionado -->
                        <div id="price-field" class="mt-4 {{ $post->visibility === 'paid' ? '' : 'hidden' }}">
                            <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
                                Preço do conteúdo (R$)
                            </label>
                            <input
                                type="text"
                                id="price"
                                name="price"
                                inputmode="decimal"
                                value="{{ old('price', $post->price) }}"
                                placeholder="Ex: 29.90"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                            >
                            <div id="price-error" class="text-red-500 text-sm mt-1 hidden"></div>
                        </div>
                    </div>

                    <!-- Mídias Existentes -->
                    @if($post->media->count() > 0)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Mídias Existentes
                            </label>
                            <div id="existingMedia" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                @foreach($post->media as $media)
                                    <div class="relative group existing-media-item" data-media-id="{{ $media->id }}">
                                        @if($media->file_type === 'image')
                                            <img src="{{ $media->url }}" alt="Mídia" class="w-full h-32 object-cover rounded-lg border border-gray-300">
                                        @else
                                            <video src="{{ $media->url }}" class="w-full h-32 object-cover rounded-lg border border-gray-300" controls></video>
                                        @endif
                                        <button 
                                            type="button"
                                            onclick="removeExistingMedia({{ $media->id }})"
                                            class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Adicionar Novas Mídias -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Adicionar Novas Mídias (Opcional)
                        </label>
                        <div 
                            id="dropZone" 
                            class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-pink-400 transition-colors cursor-pointer"
                            onclick="document.getElementById('mediaFiles').click()"
                        >
                            <input 
                                type="file" 
                                id="mediaFiles" 
                                name="media[]" 
                                multiple 
                                accept="image/jpeg,image/jpg,image/png,image/heic,image/heif,.heic,.heif,video/mp4,video/quicktime,video/x-msvideo,.mp4,.mov,.avi"
                                class="hidden"
                                onchange="handleFiles(this.files)"
                            >
                            <div id="dropZoneContent">
                                <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <p class="text-gray-600 mb-2">
                                    <span class="font-medium">Escolher arquivos</span>
                                    <span id="fileCount" class="text-gray-400"> Nenhum arquivo escolhido</span>
                                </p>
                                <p class="text-sm text-gray-500">Clique para selecionar ou arraste arquivos aqui</p>
                                <p class="text-xs text-gray-400 mt-2">Você pode adicionar arquivos um por um ou vários de uma vez</p>
                            </div>
                        </div>
                        
                        <!-- Preview das novas mídias selecionadas -->
                        <div id="mediaPreview" class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4"></div>
                        <div id="media-error" class="text-red-500 text-sm mt-1 hidden"></div>
                    </div>

                    <!-- Botões -->
                    <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                        <a href="{{ route('dashboard') }}" 
                           class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            Cancelar
                        </a>
                        <button 
                            type="submit"
                            id="submitBtn"
                            class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('posts.partials.no-plans-modal')

    <!-- Modal de Arquivo Duplicado -->
    <div id="duplicateModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Arquivo Duplicado</h3>
                <button onclick="closeDuplicateModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="mb-4">
                <p class="text-gray-700" id="duplicateMessage"></p>
            </div>
            <div class="flex justify-end">
                <button onclick="closeDuplicateModal()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                    Entendi
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Sucesso -->
    <div id="successModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 animate-fade-in">
            <div class="text-center">
                <!-- Ícone de Sucesso -->
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                    <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                
                <h3 class="text-xl font-bold text-gray-900 mb-2">Postagem Atualizada!</h3>
                <p class="text-gray-600 mb-6">Suas alterações foram salvas com sucesso e já estão disponíveis no seu perfil.</p>
                
                <button 
                    id="successModalBtn"
                    class="w-full px-6 py-3 bg-blue-500 text-white font-semibold rounded-lg hover:bg-blue-600 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
                    Ver Meu Perfil
                </button>
            </div>
        </div>
    </div>

    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        .animate-fade-in {
            animation: fadeIn 0.3s ease-out;
        }
    </style>

    <script>
        window.HAS_SUBSCRIPTION_PLANS = {{ $hasSubscriptionPlans ? 'true' : 'false' }};
    </script>
    <script src="/js/post-subscriber-visibility-guard.js"></script>

    <script>
        // Mostrar/ocultar campo de preço ao mudar visibilidade
        (function () {
            const select = document.getElementById('visibility');
            const priceField = document.getElementById('price-field');
            const priceInput = document.getElementById('price');

            select.addEventListener('change', function () {
                if (this.value === 'paid') {
                    priceField.classList.remove('hidden');
                    priceInput.required = true;
                } else {
                    priceField.classList.add('hidden');
                    priceInput.required = false;
                    priceInput.value = '';
                }
            });
        })();
    </script>

    <script>
        // Post Edit Handler with Chunked Upload
        (function() {
            let uploadedFiles = []; // Novos arquivos enviados via chunks
            let deletedMediaIds = [];

            $(document).ready(function() {
                // Inicializa Resumable.js para upload por chunks
                window.resumableInstance = new Resumable({
                    target: '/posts/upload-chunk',
                    chunkSize: 5 * 1024 * 1024, // 5MB por chunk
                    simultaneousUploads: 3,
                    testChunks: false,
                    throttleProgressCallbacks: 1,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    query: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    }
                });

                var r = window.resumableInstance;

                // Verifica suporte
                if (r.support) {
                    r.assignBrowse(document.getElementById('mediaFiles'));
                    r.assignDrop(document.getElementById('dropZone'));
                }

                // Drag and Drop
                const dropZone = document.getElementById('dropZone');
                
                dropZone.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    dropZone.classList.add('border-pink-500', 'bg-pink-50');
                });

                dropZone.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    dropZone.classList.remove('border-pink-500', 'bg-pink-50');
                });

                dropZone.addEventListener('drop', function(e) {
                    dropZone.classList.remove('border-pink-500', 'bg-pink-50');
                });

                // Quando arquivo é adicionado
                r.on('fileAdded', function(file, event) {
                    const isDuplicate = uploadedFiles.find(f => 
                        f.fileName === file.fileName && f.size === file.size
                    );

                    if (isDuplicate) {
                        showDuplicateModal(`O arquivo "${file.fileName}" já foi selecionado.`);
                        return false;
                    }

                    // Valida tipo
                    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/heic', 
                                      'image/heif', 'video/mp4', 'video/mov', 'video/avi', 
                                      'video/quicktime', 'video/x-msvideo'];
                    const fileName = file.fileName.toLowerCase();
                    const validExtensions = ['.jpg', '.jpeg', '.png', '.heic', '.heif', 
                                           '.mp4', '.mov', '.avi'];
                    const hasValidExtension = validExtensions.some(ext => fileName.endsWith(ext));
                    
                    if (!validTypes.includes(file.file.type) && !hasValidExtension) {
                        alert('Por favor, selecione apenas arquivos de imagem (JPG, PNG, HEIC) ou vídeo (MP4, MOV, AVI)');
                        return false;
                    }

                    const fileInfo = {
                        uniqueIdentifier: file.uniqueIdentifier,
                        fileName: file.fileName,
                        size: file.size,
                        file: file,
                        uploaded: false,
                        uploading: false,
                        progress: 0,
                        serverFilename: null,
                        fileType: null
                    };

                    uploadedFiles.push(fileInfo);
                    createNewFilePreview(file, uploadedFiles.length - 1);
                    updateFileCount();
                    
                    // NÃO inicia upload automaticamente
                    // Upload será iniciado ao clicar em "Salvar Alterações"
                });

                r.on('fileProgress', function(file) {
                    const index = uploadedFiles.findIndex(f => f.uniqueIdentifier === file.uniqueIdentifier);
                    if (index !== -1) {
                        uploadedFiles[index].progress = Math.floor(file.progress() * 100);
                        uploadedFiles[index].uploading = true;
                        updateNewFileProgress(index, uploadedFiles[index].progress);
                    }
                });

                r.on('fileSuccess', function(file, message) {
                    const response = JSON.parse(message);
                    const index = uploadedFiles.findIndex(f => f.uniqueIdentifier === file.uniqueIdentifier);
                    
                    if (index !== -1) {
                        uploadedFiles[index].uploaded = true;
                        uploadedFiles[index].uploading = false;
                        uploadedFiles[index].progress = 100;
                        uploadedFiles[index].serverFilename = response.filename;
                        uploadedFiles[index].fileType = response.file_type;
                        updateNewFileProgress(index, 100);
                    }
                });

                r.on('fileError', function(file, message) {
                    console.error('Erro no upload:', file.fileName, message);
                    const index = uploadedFiles.findIndex(f => f.uniqueIdentifier === file.uniqueIdentifier);
                    if (index !== -1) {
                        uploadedFiles[index].uploading = false;
                        uploadedFiles[index].error = true;
                        showNewFileError(index, 'Erro no upload');
                    }
                });

                // Form submit
                $('#editPostForm').on('submit', function(e) {
                    e.preventDefault();
                    submitPost();
                });

                // Fechar modal ao clicar no overlay
                $('#duplicateModal').on('click', function(e) {
                    if (e.target === this) {
                        closeDuplicateModal();
                    }
                });
            });

            function createNewFilePreview(file, index) {
                const previewItem = $(`
                    <div class="relative group new-file-preview" data-new-file-index="${index}">
                        <div class="preview-content w-full h-32 bg-gray-200 rounded-lg border border-gray-300 flex flex-col items-center justify-center">
                            <span class="file-name text-gray-600 text-xs text-center px-2">${file.fileName}</span>
                            <span class="file-size text-gray-500 text-xs">${formatFileSize(file.size)}</span>
                            <div class="progress-container w-full px-4 mt-2" style="display: none;">
                                <div class="progress-bar bg-blue-500 h-1 rounded transition-all" style="width: 0%"></div>
                                <span class="progress-text text-xs text-gray-600 mt-1">0%</span>
                            </div>
                        </div>
                        <button type="button" onclick="removeNewFile(${index})" 
                                class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                `);
                
                $('#mediaPreview').append(previewItem);
            }

            function updateNewFileProgress(index, progress) {
                const previewItem = $(`.new-file-preview[data-new-file-index="${index}"]`);
                
                // Mostra barra de progresso quando upload iniciar
                if (progress > 0) {
                    previewItem.find('.progress-container').show();
                }
                
                previewItem.find('.progress-bar').css('width', progress + '%');
                previewItem.find('.progress-text').text(progress + '%');
                
                if (progress === 100) {
                    setTimeout(() => {
                        previewItem.find('.progress-container').fadeOut(500);
                    }, 1000);
                }
            }

            function showNewFileError(index, message) {
                const previewItem = $(`.new-file-preview[data-new-file-index="${index}"]`);
                previewItem.find('.progress-text').text(message).addClass('text-red-500');
            }

            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
            }

            window.removeNewFile = function(index) {
                if (uploadedFiles[index].uploading) {
                    if (!confirm('O arquivo está sendo enviado. Deseja cancelar?')) {
                        return;
                    }
                }

                uploadedFiles.splice(index, 1);
                $(`.new-file-preview[data-new-file-index="${index}"]`).remove();
                
                $('.new-file-preview').each(function(newIndex) {
                    $(this).attr('data-new-file-index', newIndex);
                    $(this).find('button').attr('onclick', `removeNewFile(${newIndex})`);
                });
                
                updateFileCount();
            };

            function showDuplicateModal(message) {
                $('#duplicateMessage').text(message);
                $('#duplicateModal').removeClass('hidden');
            }

            window.closeDuplicateModal = function() {
                $('#duplicateModal').addClass('hidden');
            };

            function updateFileCount() {
                const count = uploadedFiles.length;
                const existingCount = $('.existing-media-item:visible').length;
                const countText = count > 0 ? `${count} novo(s) arquivo(s)` : 'Nenhum arquivo novo escolhido';
                $('#fileCount').text(countText);
            }

            window.removeExistingMedia = function(mediaId) {
                if (!confirm('Tem certeza que deseja remover esta mídia?')) {
                    return;
                }
                deletedMediaIds.push(mediaId);
                $(`.existing-media-item[data-media-id="${mediaId}"]`).fadeOut(300, function() {
                    $(this).remove();
                    updateFileCount();
                });
            };

            function submitPost() {
                // Validação: deve ter pelo menos uma mídia (existente ou nova)
                const existingCount = $('.existing-media-item:visible').length;
                if (existingCount === 0 && uploadedFiles.length === 0) {
                    showError('media', 'A postagem deve ter pelo menos uma mídia');
                    return;
                }

                $('.text-red-500').addClass('hidden').text('');

                // Desabilita o botão IMEDIATAMENTE
                $('#submitBtn').prop('disabled', true).text('Salvando...');

                // Verifica se há arquivos que ainda não foram enviados
                const pendingUploads = uploadedFiles.filter(f => !f.uploaded && !f.uploading);
                
                if (pendingUploads.length > 0) {
                    // Inicia o upload de todos os arquivos pendentes
                    console.log(`Iniciando upload de ${pendingUploads.length} arquivo(s)...`);
                    
                    // Marca como "uploading"
                    pendingUploads.forEach(f => f.uploading = true);
                    
                    // Inicia o upload via Resumable
                    window.resumableInstance.upload();
                    
                    // Aguarda todos os uploads terminarem
                    waitForUploadsToComplete();
                } else {
                    // Todos os arquivos já foram enviados, atualiza o post
                    updatePost();
                }
            }

            function waitForUploadsToComplete() {
                const checkInterval = setInterval(function() {
                    const stillUploading = uploadedFiles.filter(f => f.uploading);
                    const completed = uploadedFiles.filter(f => f.uploaded);
                    const errors = uploadedFiles.filter(f => f.error);
                    
                    // Atualiza texto do botão com progresso
                    if (uploadedFiles.length > 0) {
                        const total = uploadedFiles.length;
                        const done = completed.length + errors.length;
                        $('#submitBtn').text(`Salvando... (${done}/${total})`);
                    }
                    
                    // Se todos terminaram
                    if (stillUploading.length === 0) {
                        clearInterval(checkInterval);
                        
                        // Se alguns falharam
                        if (errors.length > 0 && completed.length === 0) {
                            alert('Erro: Nenhum arquivo novo foi enviado com sucesso.');
                            $('#submitBtn').prop('disabled', false).text('Salvar Alterações');
                            return;
                        }
                        
                        if (errors.length > 0) {
                            if (!confirm(`${errors.length} arquivo(s) falharam. Deseja salvar com os ${completed.length} arquivo(s) enviados?`)) {
                                $('#submitBtn').prop('disabled', false).text('Salvar Alterações');
                                return;
                            }
                        }
                        
                        // Atualiza o post
                        updatePost();
                    }
                }, 500);
            }

            function updatePost() {
                const postData = {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    _method: 'PUT',
                    description: $('#description').val(),
                    visibility: $('#visibility').val(),
                    delete_media: deletedMediaIds,
                    uploaded_files: uploadedFiles
                        .filter(f => f.uploaded && !f.error)
                        .map((f, index) => ({
                            filename: f.serverFilename,
                            file_type: f.fileType,
                            original_name: f.fileName,
                            order: index
                        }))
                };

                $.ajax({
                    url: '/posts/{{ $post->id }}',
                    type: 'POST',
                    data: postData,
                    success: function(response) {
                        if (response.success) {
                            // Mostra modal de sucesso
                            showSuccessModal(response.user_username);
                        }
                    },
                    error: function(xhr) {
                        $('#submitBtn').prop('disabled', false).text('Salvar Alterações');
                        
                        const errors = xhr.responseJSON?.errors || {};
                        Object.keys(errors).forEach(function(field) {
                            showError(field, errors[field][0]);
                        });

                        if (xhr.responseJSON?.message) {
                            alert(xhr.responseJSON.message);
                        }
                    }
                });
            }

            function showSuccessModal(username) {
                $('#successModal').removeClass('hidden');
                
                // Redireciona ao clicar no botão
                $('#successModalBtn').off('click').on('click', function() {
                    window.location.href = '/' + username;
                });

                // Redireciona ao clicar fora do modal (opcional)
                $('#successModal').off('click').on('click', function(e) {
                    if (e.target === this) {
                        window.location.href = '/' + username;
                    }
                });
            }

            function showError(field, message) {
                $(`#${field}-error`).removeClass('hidden').text(message);
                $(`#${field}`).addClass('border-red-500');
            }
        })();
    </script>
</body>
</html>

