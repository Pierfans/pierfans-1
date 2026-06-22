<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard - {{ config('app.name', 'Laravel') }}</title>

    <!-- TailwindCSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- jQuery via CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Video.js CSS -->
    <link href="https://vjs.zencdn.net/8.10.0/video-js.css" rel="stylesheet" />

    <!-- Estilos e scripts customizados -->
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/profile-overlay.css">
    <link rel="stylesheet" href="/css/video-player-premium.css">
    <script src="/js/app.js"></script>
    <script src="/js/post-interactions.js"></script>
    <script src="/js/profile-overlay.js"></script>

    <!-- Video.js JS -->
    <script src="https://vjs.zencdn.net/8.10.0/video.min.js"></script>

    <style>
        /* Estilos customizados para Video.js */
        .video-js {
            width: 100% !important;
            height: auto !important;
            max-height: 600px !important;
            background-color: #000;
        }

        .video-js .vjs-tech {
            width: 100% !important;
            height: auto !important;
            max-height: 600px !important;
            object-fit: contain;
        }

        .video-js .vjs-poster {
            width: 100% !important;
            height: auto !important;
            max-height: 600px !important;
            object-fit: contain;
        }

        .video-js .vjs-big-play-button {
            background-color: rgba(0, 0, 0, 0.8);
            border-radius: 50%;
            width: 3em;
            height: 3em;
            line-height: 3em;
            border: none;
            left: 50%;
            top: 50%;
            margin-left: -1.5em;
            margin-top: -1.5em;
        }

        .video-js .vjs-big-play-button:hover {
            background-color: rgba(0, 0, 0, 0.9);
        }

        .video-js .vjs-control-bar {
            background: linear-gradient(to top, rgba(0, 0, 0, 0.7) 0%, transparent 100%);
        }
    </style>

    <script>
        // Aguarda o Video.js estar disponível
        function waitForVideoJS(callback) {
            if (typeof videojs !== 'undefined') {
                callback();
            } else {
                setTimeout(function() {
                    waitForVideoJS(callback);
                }, 100);
            }
        }

        // Inicializa Video.js para todos os players quando a página carrega
        waitForVideoJS(function() {
            // Inicializa todos os players visíveis
            initializeVideoPlayers();

            // Observa mudanças na navegação de mídias para inicializar novos players
            const mediaContainers = document.querySelectorAll('.post-media-container');
            mediaContainers.forEach(container => {
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        mutation.addedNodes.forEach(function(node) {
                            if (node.nodeType === 1 && node.classList.contains(
                                    'post-media-item')) {
                                const video = node.querySelector('video.video-js');
                                if (video && !video.classList.contains(
                                        'vjs-initialized')) {
                                    initializeVideoPlayer(video);
                                }
                            }
                        });
                    });
                });

                observer.observe(container, {
                    childList: true,
                    subtree: true
                });
            });
        });

        function initializeVideoPlayers() {
            document.querySelectorAll('video.video-js:not(.vjs-initialized)').forEach(function(video) {
                // Só inicializa se o vídeo estiver visível
                const mediaItem = video.closest('.post-media-item');
                if (mediaItem && !mediaItem.classList.contains('hidden')) {
                    initializeVideoPlayer(video);
                }
            });
        }

        function initializeVideoPlayer(videoElement) {
            if (videoElement.classList.contains('vjs-initialized')) {
                return;
            }

            const playerId = videoElement.id;
            const videoUrl = videoElement.querySelector('source')?.src;

            if (!videoUrl) {
                return;
            }

            // Configurações do Video.js com altura fixa
            const player = videojs(playerId, {
                controls: true,
                autoplay: false,
                preload: 'auto',
                fluid: false,
                responsive: false,
                width: '100%',
                height: 'auto',
                playbackRates: [0.5, 1, 1.25, 1.5, 2],
                html5: {
                    vhs: {
                        overrideNative: true
                    },
                    nativeVideoTracks: false,
                    nativeAudioTracks: false,
                    nativeTextTracks: false
                }
            });

            // Ajusta a altura após o vídeo carregar
            player.ready(function() {
                const tech = player.tech();
                if (tech && tech.el()) {
                    const videoEl = tech.el();
                    videoEl.style.maxHeight = '600px';
                    videoEl.style.height = 'auto';
                    videoEl.style.width = '100%';
                    videoEl.style.objectFit = 'contain';
                }
            });

            // Ajusta altura quando o vídeo carrega metadados
            player.on('loadedmetadata', function() {
                const tech = player.tech();
                if (tech && tech.el()) {
                    const videoEl = tech.el();
                    const videoHeight = videoEl.videoHeight;
                    const videoWidth = videoEl.videoWidth;

                    if (videoHeight && videoWidth) {
                        const aspectRatio = videoHeight / videoWidth;
                        let calculatedHeight = videoWidth * aspectRatio;

                        if (calculatedHeight > 600) {
                            calculatedHeight = 600;
                        }

                        videoEl.style.maxHeight = '600px';
                        videoEl.style.height = 'auto';
                        videoEl.style.width = '100%';
                        videoEl.style.objectFit = 'contain';

                        // Ajusta o container do player também
                        const playerEl = player.el();
                        if (playerEl) {
                            playerEl.style.maxHeight = '600px';
                            playerEl.style.height = 'auto';
                        }
                    }
                }
            });

            // Pausa outros players quando um novo começa a tocar
            player.on('play', function() {
                document.querySelectorAll('video.video-js.vjs-initialized').forEach(function(otherVideo) {
                    if (otherVideo.id !== playerId) {
                        try {
                            const otherPlayer = videojs(otherVideo.id);
                            if (otherPlayer && !otherPlayer.paused()) {
                                otherPlayer.pause();
                            }
                        } catch (e) {
                            // Player não inicializado ainda ou erro ao acessar
                        }
                    }
                });
            });

            // Pausa o player quando a mídia fica oculta
            const mediaItem = videoElement.closest('.post-media-item');
            if (mediaItem) {
                const hiddenObserver = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                            if (mediaItem.classList.contains('hidden')) {
                                if (player && !player.paused()) {
                                    player.pause();
                                }
                            }
                        }
                    });
                });

                hiddenObserver.observe(mediaItem, {
                    attributes: true,
                    attributeFilter: ['class']
                });
            }
        }

        // Reinicializa players quando mídias são trocadas
        document.addEventListener('click', function(e) {
            if (e.target.closest('.post-media-next') || e.target.closest('.post-media-prev')) {
                // Pausa todos os players antes de trocar
                document.querySelectorAll('video.video-js.vjs-initialized').forEach(function(video) {
                    try {
                        const player = videojs(video.id);
                        if (player && !player.paused()) {
                            player.pause();
                        }
                    } catch (e) {
                        // Ignora erros
                    }
                });

                setTimeout(function() {
                    initializeVideoPlayers();
                }, 100);
            }
        });

        // Observa mudanças na classe 'hidden' para inicializar players quando ficam visíveis
        const hiddenObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const target = mutation.target;
                    if (target.classList.contains('post-media-item') && !target.classList.contains(
                            'hidden')) {
                        const video = target.querySelector('video.video-js:not(.vjs-initialized)');
                        if (video) {
                            initializeVideoPlayer(video);
                        }
                    }
                }
            });
        });

        document.querySelectorAll('.post-media-item').forEach(function(item) {
            hiddenObserver.observe(item, {
                attributes: true,
                attributeFilter: ['class']
            });
        });
    </script>
</head>

<body class="bg-[#FDFDFC] text-[#1b1b18] min-h-screen">
    <!-- Top Navigation (Desktop) -->
    <x-topnav />

    <!-- Bottom Navigation (Mobile) -->
    <x-bottomnav />

    <!-- Profile Drawer -->
    <x-profile-drawer />

    <!-- Main Content -->
    <div class="pt-0 md:pt-16 pb-16 md:pb-0">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <a href="/affiliates" class="block w-full overflow-hidden rounded-xl">
                <img src="/img/pr1.webp" alt="Programa de afiliados Pierfans"
                    class="block h-[140px] w-full object-cover md:h-[160px] lg:h-[220px]" loading="lazy">
            </a>

            <!-- Top 5 Criadores -->
            @if (isset($featuredCreators) && $featuredCreators->count() > 0)
                <div class="mb-8 mt-6">
                    <h2 class="text-lg font-bold text-[#1b1b18] mb-4 flex items-center gap-2">
                        <span>🏆</span>
                        <span>Top 5 Criadores</span>
                    </h2>
                    <div class="overflow-x-auto pb-4 -mx-4 px-4">
                        <div class="flex gap-4 min-w-max"
                            style="scroll-snap-type: x mandatory; -webkit-overflow-scrolling: touch;">
                            @foreach ($featuredCreators as $index => $creator)
                                <a href="{{ route('profile.show', $creator->username) }}"
                                    class="flex-shrink-0 w-[150px] bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow cursor-pointer"
                                    style="scroll-snap-align: start;">
                                    <div class="relative">
                                        @if ($creator->profile_photo_url)
                                            <img src="{{ $creator->profile_photo_url }}"
                                                alt="{{ $creator->name ?? $creator->creator_full_name }}"
                                                class="w-full h-[140px] object-cover">
                                        @else
                                            <div class="w-full h-[140px] bg-gray-200 flex items-center justify-center">
                                                <svg class="w-16 h-16 text-gray-400" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </div>
                                        @endif
                                        <div
                                            class="absolute top-2 right-2 bg-[#FF6B35] text-white rounded-full w-8 h-8 flex items-center justify-center font-bold text-sm">
                                            {{ $index + 1 }}
                                        </div>
                                    </div>
                                    <div class="p-4">
                                        <h3 class="font-semibold text-[#1b1b18] text-md mb-1 truncate">
                                            {{ $creator->name ?? ($creator->creator_full_name ?? 'Sem nome') }}
                                        </h3>
                                        <p class="text-sm text-[#706f6c] truncate -mt-1">
                                            <span>@</span>{{ $creator->username }}
                                        </p>
                                        <div class="mt-3">
                                            <span
                                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-[#FF6B35] text-white text-xs font-semibold rounded-full">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                Em Alta
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Feed de Postagens -->
            <div class="max-w-2xl mx-auto">
                @if (isset($posts) && $posts->count() > 0)
                    @foreach ($posts as $post)
                        <x-post-card :post="$post" />
                    @endforeach

                    <!-- Paginação -->
                    <div class="mt-6">
                        {{ $posts->links() }}
                    </div>
                @else
                    <div class="bg-white shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] rounded-lg p-8 text-center">
                        <p class="text-[#706f6c]">
                            Nenhuma postagem ainda. Seja o primeiro a criar uma!
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Comments Drawer -->
    <x-comments-drawer />

    <!-- Profile Overlay -->
    <x-profile-overlay />
</body>

</html>
