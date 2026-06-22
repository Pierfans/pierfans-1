// Media Player Controller - OnlyFans Style with Plyr.js
(function() {
    let players = {};
    let controlsTimeout = null;
    const CONTROLS_HIDE_DELAY = 3000; // 3 segundos

    // Inicialização quando o DOM estiver pronto
    $(document).ready(function() {
        initializePlyrPlayers();
        initializeImageViewers();
    });

    // Inicializa todos os players Plyr
    function initializePlyrPlayers() {
        $('.plyr-video-container:not(.plyr-initialized)').each(function() {
            const container = $(this);
            const video = container.find('video')[0];
            
            // Não inicializa se estiver bloqueado
            const isLocked = container.hasClass('locked') || 
                           container.data('locked') === true || 
                           container.data('locked') === 'true' ||
                           container.find('.media-paywall-overlay').length > 0;
            
            if (isLocked) {
                return;
            }
            
            // Não inicializa se não houver vídeo
            if (!video) {
                return;
            }

            const playerId = container.data('player-id');
            
            // Verifica se já foi inicializado
            if (players[playerId]) {
                return;
            }

            // Inicializa o Plyr
            const player = new Plyr(video, {
                controls: [
                    'play-large',
                    'play',
                    'progress',
                    'current-time',
                    'mute',
                    'volume',
                    'fullscreen'
                ],
                settings: [],
                keyboard: {
                    focused: true,
                    global: false
                },
                tooltips: {
                    controls: true,
                    seek: true
                },
                clickToPlay: true,
                hideControls: true,
                resetOnEnd: false,
                ratio: null, // Mantém aspect ratio original
                autoplay: false,
                muted: false,
                volume: 1,
                captions: {
                    active: false,
                    language: 'auto',
                    update: false
                },
                fullscreen: {
                    enabled: true,
                    fallback: true,
                    iosNative: false
                },
                previewThumbnails: {
                    enabled: false
                }
            });

            // Armazena a instância do player
            players[playerId] = player;
            container.addClass('plyr-initialized');

            // Eventos do player
            player.on('ready', function() {
                setupPlayerEvents(player, container);
            });

            player.on('play', function() {
                // O botão play grande já some automaticamente com CSS
                // Ocultamos os controles após delay
                setTimeout(function() {
                    hideControlsAfterDelay(container);
                }, 500);
            });

            player.on('pause', function() {
                showControls(container);
            });

            player.on('ended', function() {
                showControls(container);
            });

            player.on('seeked', function() {
                showControls(container);
                if (!player.paused) {
                    hideControlsAfterDelay(container);
                }
            });
        });
    }

    // Configura eventos adicionais do player
    function setupPlayerEvents(player, container) {
        const video = player.media;

        // Mostra controles ao interagir
        container.on('mousemove touchstart', function() {
            showControls(container);
            if (!video.paused) {
                hideControlsAfterDelay(container);
            }
        });

        // Oculta controles quando o mouse sai (apenas se estiver tocando)
        container.on('mouseleave', function() {
            if (!video.paused) {
                hideControlsAfterDelay(container);
            }
        });

        // Suporte a teclado
        $(document).on('keydown', function(e) {
            // Só processa se o player estiver visível
            if (!container.is(':visible')) return;

            const isFocused = container.find(':focus').length > 0 || 
                            document.activeElement === video;

            switch(e.key) {
                case ' ':
                case 'k':
                    if (isFocused || container.is(':hover')) {
                        e.preventDefault();
                        player.togglePlay();
                    }
                    break;
                case 'f':
                    if (isFocused || container.is(':hover')) {
                        e.preventDefault();
                        player.fullscreen.toggle();
                    }
                    break;
                case 'm':
                    if (isFocused || container.is(':hover')) {
                        e.preventDefault();
                        player.muted = !player.muted;
                    }
                    break;
            }
        });
    }

    // Mostra controles
    function showControls(container) {
        container.removeClass('plyr--hide-controls');
        clearTimeout(controlsTimeout);
    }

    // Oculta controles após delay
    function hideControlsAfterDelay(container) {
        clearTimeout(controlsTimeout);
        controlsTimeout = setTimeout(function() {
            const player = getPlayerFromContainer(container);
            if (player && !player.paused && !container.is(':hover')) {
                container.addClass('plyr--hide-controls');
            }
        }, CONTROLS_HIDE_DELAY);
    }

    // Obtém instância do player a partir do container
    function getPlayerFromContainer(container) {
        const playerId = container.data('player-id');
        return players[playerId] || null;
    }

    // Inicializa visualizadores de imagem
    function initializeImageViewers() {
        $('.plyr-image').on('load', function() {
            // Imagem carregada
        });
    }

    // Função para desbloquear conteúdo (paywall)
    window.unlockContent = function(postId) {
        // Implementar lógica de desbloqueio
        // Por exemplo, redirecionar para página de assinatura
        window.location.href = '/subscribe?post=' + postId;
    };

    // Reinicializa players quando novos elementos são adicionados
    if (typeof MutationObserver !== 'undefined') {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) { // Element node
                            const $node = $(node);
                            if ($node.find('.plyr-video-container').length || 
                                $node.hasClass('plyr-video-container')) {
                                initializePlyrPlayers();
                            }
                        }
                    });
                }
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    // Limpa players quando elementos são removidos
    $(document).on('DOMNodeRemoved', function(e) {
        const container = $(e.target).find('.plyr-video-container');
        if (container.length) {
            const playerId = container.data('player-id');
            if (players[playerId]) {
                players[playerId].destroy();
                delete players[playerId];
            }
        }
    });

    // Exporta função para reinicializar (útil para paginação)
    window.reinitializePlyrPlayers = function() {
        initializePlyrPlayers();
    };
})();
