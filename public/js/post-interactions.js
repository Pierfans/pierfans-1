// Post Interactions Handler
(function() {
    let currentPostId = null;
    let replyingTo = null;

    $(document).ready(function() {
        // Toggle Like
        $(document).on('click', '.post-like-btn', function() {
            const postId = $(this).data('post-id');
            togglePostLike(postId, $(this));
        });

        // Abrir drawer de comentários
        $(document).on('click', '.post-comment-btn, .post-comments-toggle', function() {
            const postId = $(this).data('post-id');
            openCommentsDrawer(postId);
        });

        // Enviar comentário
        $('#sendCommentBtn').on('click', function() {
            sendComment();
        });

        // Enter para enviar comentário
        $('#commentInput').on('keypress', function(e) {
            if (e.which === 13) {
                sendComment();
            }
        });

        // Habilitar botão de enviar quando houver texto
        $('#commentInput').on('input', function() {
            const hasText = $(this).val().trim().length > 0;
            $('#sendCommentBtn').prop('disabled', !hasText);
        });

        // Navegação de mídia
        $(document).on('click', '.post-media-next', function() {
            const container = $(this).closest('.post-media-container');
            navigateMedia(container, 'next');
        });

        $(document).on('click', '.post-media-prev', function() {
            const container = $(this).closest('.post-media-container');
            navigateMedia(container, 'prev');
        });

        // Inicializa navegação de mídia para todos os posts
        $('.post-media-container').each(function() {
            const container = $(this);
            const items = container.find('.post-media-item');
            const totalItems = items.length;
            if (totalItems > 1) {
                updateMediaNavigation(container, 0, totalItems);
            }
        });
    });

    function togglePostLike(postId, button) {
        $.ajax({
            url: `/posts/${postId}/like`,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    // Atualiza o botão
                    if (response.liked) {
                        button.addClass('text-red-500').find('svg').addClass('fill-current');
                    } else {
                        button.removeClass('text-red-500').find('svg').removeClass('fill-current');
                    }
                    button.data('liked', response.liked);

                    // Atualiza contador
                    const postCard = button.closest('[data-post-id]');
                    postCard.find('.post-likes-count').text(
                        response.likes_count + ' ' + (response.likes_count === 1 ? 'curtida' : 'curtidas')
                    );
                }
            },
            error: function(xhr) {
                console.error('Erro ao curtir postagem:', xhr);
            }
        });
    }

    function openCommentsDrawer(postId) {
        currentPostId = postId;
        replyingTo = null;
        $('#commentsDrawer').removeClass('hidden');
        $('body').css('overflow', 'hidden');
        loadComments(postId);
    }

    window.closeCommentsDrawer = function() {
        $('#commentsDrawer').addClass('hidden');
        $('body').css('overflow', '');
        currentPostId = null;
        replyingTo = null;
        $('#commentInput').val('');
        $('#sendCommentBtn').prop('disabled', true);
    };

    function loadComments(postId) {
        $('#commentsList').html(`
            <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 dark:border-gray-100 mx-auto"></div>
                <p class="mt-2">Carregando comentários...</p>
            </div>
        `);

        $.ajax({
            url: `/posts/${postId}/comments`,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    renderComments(response.comments);
                }
            },
            error: function(xhr) {
                $('#commentsList').html(`
                    <div class="text-center text-red-500 py-8">
                        <p>Erro ao carregar comentários.</p>
                    </div>
                `);
            }
        });
    }

    function renderComments(comments) {
        if (comments.length === 0) {
            $('#commentsList').html(`
                <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                    <p>Nenhum comentário ainda. Seja o primeiro a comentar!</p>
                </div>
            `);
            return;
        }

        let html = '';
        comments.forEach(function(comment) {
            html += renderComment(comment, false);
        });

        $('#commentsList').html(html);
    }

    function renderComment(comment, isReply = false) {
        const userInitials = comment.user.name.substring(0, 2).toUpperCase();
        
        // Se o usuário tem foto de perfil, usa a imagem, senão usa as iniciais
        let avatarHtml;
        if (comment.user.profile_photo) {
            avatarHtml = `<img src="${comment.user.profile_photo}" alt="${comment.user.name}" class="w-full h-full object-cover">`;
        } else {
            avatarHtml = `<span class="text-gray-600 dark:text-gray-300 font-medium text-xs">${userInitials}</span>`;
        }

        let html = `
            <div class="comment-item ${isReply ? 'ml-8 mt-2' : ''}" data-comment-id="${comment.id}">
                <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 rounded-full bg-gray-300 dark:bg-gray-700 flex items-center justify-center overflow-hidden flex-shrink-0">
                        ${avatarHtml}
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center space-x-2 mb-1">
                            <span class="font-semibold text-sm text-gray-900 dark:text-gray-100">${comment.user.name}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">${comment.created_at}</span>
                        </div>
                        <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">${escapeHtml(comment.content)}</p>
                        <div class="flex items-center space-x-4">
                            <button 
                                class="comment-like-btn flex items-center space-x-1 ${comment.is_liked ? 'text-red-500' : 'text-gray-500 dark:text-gray-400'}"
                                data-comment-id="${comment.id}"
                                data-liked="${comment.is_liked}"
                            >
                                <svg class="w-4 h-4 ${comment.is_liked ? 'fill-current' : ''}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                                <span class="comment-likes-count text-xs">${comment.likes_count}</span>
                            </button>
                            ${!isReply ? `
                                <button 
                                    class="comment-reply-btn text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200"
                                    data-comment-id="${comment.id}"
                                >
                                    Responder
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
                ${comment.replies && comment.replies.length > 0 ? `
                    <div class="mt-3 space-y-2">
                        ${comment.replies.map(reply => renderComment(reply, true)).join('')}
                    </div>
                ` : ''}
            </div>
        `;

        return html;
    }

    function sendComment() {
        const content = $('#commentInput').val().trim();
        if (!content || !currentPostId) return;

        const data = {
            content: content,
            _token: $('meta[name="csrf-token"]').attr('content')
        };

        if (replyingTo) {
            data.parent_id = replyingTo;
        }

        $('#sendCommentBtn').prop('disabled', true);

        $.ajax({
            url: `/posts/${currentPostId}/comments`,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    $('#commentInput').val('');
                    $('#sendCommentBtn').prop('disabled', true);
                    replyingTo = null;
                    loadComments(currentPostId);
                }
            },
            error: function(xhr) {
                $('#sendCommentBtn').prop('disabled', false);
                alert('Erro ao enviar comentário. Tente novamente.');
            }
        });
    }

    $(document).on('click', '.comment-like-btn', function() {
        const commentId = $(this).data('comment-id');
        const button = $(this);
        toggleCommentLike(commentId, button);
    });

    $(document).on('click', '.comment-reply-btn', function() {
        const commentId = $(this).data('comment-id');
        replyingTo = commentId;
        const commentItem = $(this).closest('.comment-item');
        const userName = commentItem.find('.font-semibold').first().text();
        $('#commentInput').attr('placeholder', `Respondendo a ${userName}...`).focus();
    });

    function toggleCommentLike(commentId, button) {
        $.ajax({
            url: `/comments/${commentId}/like`,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    if (response.liked) {
                        button.addClass('text-red-500').find('svg').addClass('fill-current');
                    } else {
                        button.removeClass('text-red-500').find('svg').removeClass('fill-current');
                    }
                    button.data('liked', response.liked);
                    button.find('.comment-likes-count').text(response.likes_count);
                }
            },
            error: function(xhr) {
                console.error('Erro ao curtir comentário:', xhr);
            }
        });
    }

    function navigateMedia(container, direction) {
        const items = container.find('.post-media-item');
        const currentIndex = items.filter('.active').data('index');
        const totalItems = items.length;

        let newIndex;
        if (direction === 'next') {
            newIndex = (currentIndex + 1) % totalItems;
        } else {
            newIndex = (currentIndex - 1 + totalItems) % totalItems;
        }

        items.removeClass('active hidden').addClass('hidden');
        items.eq(newIndex).removeClass('hidden').addClass('active');

        // Atualiza contador
        container.find('.current-media-index').text(newIndex + 1);

        // Atualiza botões de navegação
        updateMediaNavigation(container, newIndex, totalItems);
    }

    function updateMediaNavigation(container, currentIndex, totalItems) {
        const prevBtn = container.find('.post-media-prev');
        const nextBtn = container.find('.post-media-next');

        if (currentIndex === 0) {
            prevBtn.addClass('hidden');
        } else {
            prevBtn.removeClass('hidden');
        }

        if (currentIndex === totalItems - 1) {
            nextBtn.addClass('hidden');
        } else {
            nextBtn.removeClass('hidden');
        }
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
})();

