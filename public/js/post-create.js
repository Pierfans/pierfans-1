// Post Create Handler with Chunked Upload
(function() {
    let uploadedFiles = []; // Armazena arquivos já enviados via chunks

    $(document).ready(function() {
        // Inicializa Resumable.js para upload por chunks
        window.resumableInstance = new Resumable({
            target: '/posts/upload-chunk',
            chunkSize: 20 * 1024 * 1024, // 20MB por chunk
            simultaneousUploads: 4, // 4 uploads simultâneos
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

        // Verifica se o navegador suporta Resumable
        if (!r.support) {
            alert('Seu navegador não suporta upload de arquivos grandes. Por favor, atualize seu navegador.');
            return;
        }

        // Associa o Resumable ao input de arquivos
        r.assignBrowse(document.getElementById('mediaFiles'));

        // Drag and Drop
        const dropZone = document.getElementById('dropZone');
        
        r.assignDrop(dropZone);

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
            // Verifica duplicados
            const isDuplicate = uploadedFiles.find(f => 
                f.fileName === file.fileName && f.size === file.size
            );

            if (isDuplicate) {
                showDuplicateModal(`O arquivo "${file.fileName}" já foi selecionado.`);
                return false; // Cancela adição
            }

            // Valida tipo de arquivo
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

            // Adiciona à lista temporária
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
            // Cria preview imediatamente
            createPreview(file, uploadedFiles.length - 1);
            updateFileCount();
            // Upload será iniciado ao clicar em Publicar
            $('#uploadStatus').removeClass('hidden').html('📎 <strong>Arquivo selecionado!</strong> Clique em Publicar — o envio acontece automaticamente.');
            $('#submitBtn').prop('disabled', false).text('Publicar');
            // Mostra barra de progresso imediatamente
            $(`.relative.group[data-file-index="${uploadedFiles.length - 1}"]`).find('.progress-container').removeClass('hidden');
        });

        // Progresso do upload de um arquivo
        r.on('fileProgress', function(file) {
            const index = uploadedFiles.findIndex(f => f.uniqueIdentifier === file.uniqueIdentifier);
            if (index !== -1) {
                uploadedFiles[index].progress = Math.floor(file.progress() * 100);
                uploadedFiles[index].uploading = true;
                updateProgressBar(index, uploadedFiles[index].progress);
            }
        });

        // Arquivo enviado com sucesso
        r.on('fileSuccess', function(file, message) {
            const response = JSON.parse(message);
            const index = uploadedFiles.findIndex(f => f.uniqueIdentifier === file.uniqueIdentifier);
            
            if (index !== -1) {
                uploadedFiles[index].uploaded = true;
                uploadedFiles[index].uploading = false;
                uploadedFiles[index].progress = 100;
                uploadedFiles[index].serverFilename = response.filename;
                uploadedFiles[index].fileType = response.file_type;
                
                updateProgressBar(index, 100);
                updatePublishButton();
                console.log('Upload completo:', file.fileName, 'Tamanho:', formatFileSize(file.size));
            }
        });

        // Erro no upload
        r.on('fileError', function(file, message) {
            console.error('Erro no upload:', file.fileName, message);
            const index = uploadedFiles.findIndex(f => f.uniqueIdentifier === file.uniqueIdentifier);
            
            if (index !== -1) {
                uploadedFiles[index].uploading = false;
                uploadedFiles[index].error = true;
                showProgressError(index, 'Erro no upload');
            }
        });

        // Form submit
        $('#createPostForm').on('submit', function(e) {
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

    function createPreview(file, index) {
        const isVideo = file.file.type.startsWith('video/');
        const isHeic = file.fileName.toLowerCase().endsWith('.heic') || 
                      file.fileName.toLowerCase().endsWith('.heif');
        
        const previewItem = $(`
            <div class="relative group" data-file-index="${index}">
                <div class="preview-content w-full h-32 bg-gray-200 rounded-lg border border-gray-300 flex flex-col items-center justify-center">
                    <div class="upload-icon">
                        <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                    </div>
                    <span class="file-name text-gray-600 text-xs text-center px-2">${file.fileName}</span>
                    <span class="file-size text-gray-500 text-xs">${formatFileSize(file.size)}</span>
                    <div class="progress-container w-full px-4 mt-2 hidden">
                        <div class="progress-bar bg-blue-500 h-1 rounded transition-all" style="width: 0%"></div>
                        <span class="progress-text text-xs text-gray-600 mt-1">0%</span>
                    </div>
                </div>
                <button type="button" onclick="removeFile(${index})" 
                        class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        `);
        
        $('#mediaPreview').append(previewItem);
        // Mostra aviso de carregamento
        $('#uploadStatus').removeClass('hidden').html('⏳ <strong>Aguarde...</strong> Seu arquivo está sendo carregado. O botão será liberado quando terminar.');
        updatePublishButton();
        // Tenta criar preview visual
        if (isVideo) {
            const video = $('<video class="w-full h-32 object-cover rounded-lg border border-gray-300"></video>');
            video.attr('src', URL.createObjectURL(file.file));
            previewItem.find('.preview-content').empty().append(video);
        } else if (!isHeic) {
            const img = $('<img class="w-full h-32 object-cover rounded-lg border border-gray-300">');
            img.attr('src', URL.createObjectURL(file.file));
            previewItem.find('.preview-content').empty().append(img);
        } else if (isHeic && typeof heic2any !== 'undefined') {
            // Converte HEIC para preview
            heic2any({
                blob: file.file,
                toType: 'image/jpeg',
                quality: 0.8
            }).then(function(convertedBlob) {
                const img = $('<img class="w-full h-32 object-cover rounded-lg border border-gray-300">');
                img.attr('src', URL.createObjectURL(convertedBlob));
                previewItem.find('.preview-content').empty().append(img);

                const badge = $('<span class="absolute top-2 left-2 bg-blue-500 text-white text-xs px-2 py-1 rounded">HEIC</span>');
                previewItem.append(badge);
            }).catch(function(error) {
                console.error('Erro ao converter HEIC:', error);
            });
        }

        // Adiciona badge de status após preview
        const statusBadge = $('<span class="status-badge absolute bottom-2 left-2 text-white text-xs px-2 py-1 rounded font-bold" style="background:#f59e0b;">⏳ Aguardando...</span>');
        previewItem.append(statusBadge);
    }

    function updateProgressBar(index, progress) {
        const previewItem = $(`.relative.group[data-file-index="${index}"]`);
        
        // Mostra barra de progresso quando upload iniciar
        if (progress > 0) {
            previewItem.find('.progress-container').removeClass('hidden');
        }
        
        previewItem.find('.progress-bar').css('width', progress + '%');
        previewItem.find('.progress-text').text(progress + '%');
        
        if (progress === 100) {
            setTimeout(() => {
                previewItem.find('.progress-container').fadeOut(500);
            }, 1000);
        }
    }

    function showProgressError(index, message) {
        const previewItem = $(`.relative.group[data-file-index="${index}"]`);
        previewItem.find('.progress-text').text(message).addClass('text-red-500');
    }

    function updateFileCount() {
        const count = uploadedFiles.length;
        const countText = count > 0 ? `${count} arquivo(s) escolhido(s)` : 'Nenhum arquivo escolhido';
        $('#fileCount').text(countText);
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    window.removeFile = function(index) {
        if (uploadedFiles[index].uploading) {
            if (!confirm('O arquivo está sendo enviado. Deseja cancelar?')) {
                return;
            }
        }

        // Remove da lista
        uploadedFiles.splice(index, 1);
        
        // Remove do preview
        $(`.relative.group[data-file-index="${index}"]`).remove();
        
        // Atualiza índices
        $('.relative.group').each(function(newIndex) {
            $(this).attr('data-file-index', newIndex);
            $(this).find('button').attr('onclick', `removeFile(${newIndex})`);
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

    function submitPost() {
        // Validação: verifica se há arquivos
        if (uploadedFiles.length === 0) {
            showError('media', 'Selecione pelo menos uma mídia');
            return;
        }

        // Limpa erros anteriores
        $('.text-red-500').addClass('hidden').text('');

        // Desabilita o botão IMEDIATAMENTE
        $('#submitBtn').prop('disabled', true).text('Publicando...');

        // Verifica se há arquivos que ainda não foram enviados
        const pendingUploads = uploadedFiles.filter(f => !f.uploaded && !f.uploading);
        
        if (pendingUploads.length > 0) {
            // Inicia o upload de todos os arquivos pendentes
            console.log(`Iniciando upload de ${pendingUploads.length} arquivo(s)...`);
            
            // Marca como "uploading" para mostrar progresso
            pendingUploads.forEach(f => f.uploading = true);
            
            // Inicia o upload via Resumable
            window.resumableInstance.upload();
            
            // Aguarda todos os uploads terminarem
            waitForUploadsToComplete();
        } else {
            // Todos os arquivos já foram enviados, cria o post
            createPost();
        }
    }

    function waitForUploadsToComplete() {
        // Verifica a cada 500ms se todos os uploads terminaram
        const checkInterval = setInterval(function() {
            const stillUploading = uploadedFiles.filter(f => f.uploading);
            const completed = uploadedFiles.filter(f => f.uploaded);
            const errors = uploadedFiles.filter(f => f.error);
            
            // Atualiza texto do botão com progresso
            const total = uploadedFiles.length;
            const done = completed.length + errors.length;
            $('#submitBtn').text(`Publicando... (${done}/${total})`);
            
            // Se todos terminaram (sucesso ou erro)
            if (stillUploading.length === 0) {
                clearInterval(checkInterval);
                
                // Se todos falharam
                if (completed.length === 0) {
                    alert('Erro: Nenhum arquivo foi enviado com sucesso.');
                    $('#submitBtn').prop('disabled', false).text('Publicar');
                    return;
                }
                
                // Se alguns falharam
                if (errors.length > 0) {
                    if (!confirm(`${errors.length} arquivo(s) falharam no upload. Deseja publicar com os ${completed.length} arquivo(s) restantes?`)) {
                        $('#submitBtn').prop('disabled', false).text('Publicar');
                        return;
                    }
                }
                
                // Cria o post
                createPost();
            }
        }, 500);
    }

    function createPost() {
        // Prepara dados para envio
        const postData = {
            _token: $('meta[name="csrf-token"]').attr('content'),
            description: $('#description').val(),
            visibility: $('#visibility').val(),
            price: $('#price').val() || null,
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
            url: '/posts',
            type: 'POST',
            data: postData,
            success: function(response) {
                if (response.success) {
                    // Mostra modal de sucesso
                    showSuccessModal(response.user_username);
                }
            },
            error: function(xhr) {
                $('#submitBtn').prop('disabled', false).text('Publicar');
                
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

    function updatePublishButton() {
        // Botão sempre habilitado após selecionar arquivo
        // O controle de upload acontece no submitPost()
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
