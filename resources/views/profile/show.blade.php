<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $user->name }} - {{ config('app.name', 'Laravel') }}</title>

    <!-- TailwindCSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- jQuery via CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Video.js CSS -->
    <link href="https://vjs.zencdn.net/8.10.0/video-js.css" rel="stylesheet" />

    <!-- Cropper.js CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" rel="stylesheet" />

    <!-- Estilos e scripts customizados -->
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/profile-overlay.css">
    <link rel="stylesheet" href="/css/video-player-premium.css">
    <script src="/js/app.js"></script>
    <script src="/js/post-interactions.js"></script>
    <script src="/js/profile-overlay.js"></script>

    <!-- Video.js JS -->
    <script src="https://vjs.zencdn.net/8.10.0/video.min.js"></script>

    <!-- Cropper.js JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>

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
                            if (node.nodeType === 1 && node.classList.contains('post-media-item')) {
                                const video = node.querySelector('video.video-js');
                                if (video && !video.classList.contains('vjs-initialized')) {
                                    initializeVideoPlayer(video);
                                }
                            }
                        });
                    });
                });

                observer.observe(container, { childList: true, subtree: true });
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
            const sourceEl = videoElement.querySelector('source');
            const videoUrl = sourceEl?.src;
            if (!videoUrl) {
                return;
            }
            // Se for rota de stream, busca URL presigned antes de inicializar
            if (videoUrl.includes('/post-media/') && videoUrl.includes('/stream')) {
                fetch(videoUrl, {
                    headers: { 'Accept': 'application/json' }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.url) {
                        sourceEl.src = data.url;
                        videoElement.load();
                        initVideoJS(videoElement, playerId);
                    }
                })
                .catch(() => initVideoJS(videoElement, playerId));
                return;
            }

            initVideoJS(videoElement, playerId);
        }
        function initVideoJS(videoElement, playerId) {
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

                hiddenObserver.observe(mediaItem, { attributes: true, attributeFilter: ['class'] });
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
                    if (target.classList.contains('post-media-item') && !target.classList.contains('hidden')) {
                        const video = target.querySelector('video.video-js:not(.vjs-initialized)');
                        if (video) {
                            initializeVideoPlayer(video);
                        }
                    }
                }
            });
        });

        document.querySelectorAll('.post-media-item').forEach(function(item) {
            hiddenObserver.observe(item, { attributes: true, attributeFilter: ['class'] });
        });
    </script>

    <style>
        /* .profile-card {
            background: white;
            border-radius: 0;
            overflow: hidden;
            box-shadow: none;
            margin-bottom: 0;
            padding: 0px;
        }

        @media (min-width: 768px) {
            .profile-card {
                border-radius: 16px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                margin-bottom: 24px;
            }
        } */

        .cover-image-container {
            position: relative;
            width: 100%;
            height: 110px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            overflow: hidden;
            border-radius: 20px 20px 0 0;
        }

        .cover-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .cover-controls {
            position: absolute;
            top: 16px;
            left: 16px;
            display: flex;
            gap: 8px;
        }

        .cover-btn {
            background: rgba(0, 0, 0, 0.6);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: background 0.2s;
        }

        .cover-btn:hover {
            background: rgba(0, 0, 0, 0.8);
        }

        .profile-picture-container {
            position: relative;
            margin-top: -75px;
            margin-left: 32px;
            width: 130px;
            height: 130px;
            z-index: 10;
        }

        .profile-picture {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            border: 3px solid white;
            object-fit: cover;
            background: #E5E5E5;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .profile-picture-btn {
            position: absolute;
            bottom: 2px;
            right: 9px;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: #FF6B35;
            border: 2px solid white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: transform 0.2s;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .profile-picture-btn:hover {
            transform: scale(1.1);
            background: #e55a2b;
        }

        .profile-info {
            padding: 24px 32px;
            padding-top: 20px;
        }

        .profile-name {
            font-size: 20px;
            font-weight: 500;
            color: #1b1b18;
            margin-bottom: 4px;
        }

        .profile-username {
            font-size: 16px;
            color: #706f6c;
            margin-bottom: 5px;
            font-weight: 400;
        }

        .profile-description {
            font-size: 16px;
            color: #706f6c;
            margin-bottom: 16px;
            line-height: 1.6;
        }

        .about-section {
            margin-bottom: 5px;
        }

        .about-title {
            font-size: 14px;
            font-weight: 600;
            color: #1b1b18;
            margin-bottom: 8px;
        }

        .profile-actions {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
        }

        .btn-edit {
            background: #FF6B35;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.2s;
        }

        .btn-edit:hover {
            background: #e55a2b;
        }

        .btn-share {
            background: white;
            color: #1b1b18;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            border: 1px solid #E5E5E5;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .btn-share:hover {
            background: #F5F5F5;
        }

        .social-links {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        .social-link {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #706f6c;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.2s;
        }

        .social-link:hover {
            color: #FF6B35;
        }

        .plans-section {
            background: white;
            border-radius: 0;
            padding: 24px 32px;
            box-shadow: none;
        }

        @media (min-width: 768px) {
            .plans-section {
                border-radius: 16px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                margin-top: 24px;
            }
        }

        .plans-title {
            font-size: 20px;
            font-weight: 600;
            color: #1b1b18;
            margin-bottom: 16px;
        }

        .plan-item {
            background: #FF6B35;
            color: white;
            padding: 16px 20px;
            border-radius: 100px;
            margin-bottom: 12px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            border: none;
            width: 100%;
            text-align: left;
        }

        .plan-item:hover {
            background: #e55a2b;
            transform: translateY(-2px);
        }

        .plan-item:last-child {
            margin-bottom: 0;
        }

        .hidden-file-input {
            display: none;
        }

        /* Modal de Assinatura */
        .subscription-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .subscription-modal.active {
            display: flex;
        }

        .subscription-modal-content {
            background: white;
            border-radius: 24px;
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .subscription-modal-header {
            position: relative;
            padding: 24px 24px 16px;
            text-align: center;
        }

        .subscription-modal-close {
            position: absolute;
            top: 16px;
            right: 16px;
            background: rgba(0, 0, 0, 0.1);
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }

        .subscription-modal-close:hover {
            background: rgba(0, 0, 0, 0.2);
        }

        .subscription-modal-profile {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin: 0 auto 16px;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .subscription-modal-title {
            font-size: 24px;
            font-weight: 700;
            color: #1b1b18;
            margin-bottom: 4px;
        }

        .subscription-modal-subtitle {
            font-size: 16px;
            color: #706f6c;
            margin-bottom: 24px;
        }

        .subscription-modal-body {
            padding: 0 24px 24px;
        }

        .subscription-plan-box {
            background: #F5F5F5;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }

        .subscription-plan-name {
            font-size: 16px;
            color: #706f6c;
            margin-bottom: 8px;
        }

        .subscription-plan-price {
            font-size: 32px;
            font-weight: 700;
            color: #1b1b18;
            margin-bottom: 4px;
        }

        .subscription-plan-price .currency {
            color: #FF6B35;
            font-size: 24px;
        }

        .subscription-plan-billing {
            font-size: 14px;
            color: #706f6c;
        }

        .subscription-benefits {
            list-style: none;
            padding: 0;
            margin: 0 0 24px 0;
        }

        .subscription-benefits li {
            padding: 12px 0;
            font-size: 15px;
            color: #1b1b18;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .subscription-benefits li:before {
            content: "✓";
            color: #FF6B35;
            font-weight: 700;
            font-size: 18px;
            flex-shrink: 0;
        }

        .subscription-payment-buttons {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .subscription-payment-btn {
            padding: 16px 20px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            transition: all 0.2s;
        }

        .subscription-payment-btn-pix {
            background: #32BCAD;
            color: white;
        }

        .subscription-payment-btn-pix:hover {
            background: #2aa89a;
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="bg-[#F5F5F5] text-[#1b1b18] min-h-screen">
    <!-- Top Navigation -->
    <x-topnav />

    <!-- Bottom Navigation -->
    <x-bottomnav />

    <!-- Profile Drawer -->
    <x-profile-drawer />

    <!-- Main Content -->
    <div class="pt-0 md:pt-16 md:pb-0 px-3 mt-6">
        <div class="max-w-3xl mx-auto px-0  lg:px-8 py-0">
            <!-- Profile Card -->
            <div class="profile-card-header">
                <!-- Cover Image -->
                <div class="cover-image-container">
                    @if($user->cover_photo)
                        <img src="{{ $user->cover_photo_url }}" alt="Cover" class="cover-image">
                    @endif

                    @if($isOwner)
                        <div class="cover-controls">
                            <label for="coverPhotoInput" class="cover-btn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                                    <circle cx="12" cy="13" r="4"></circle>
                                </svg>
                                Alterar Capa
                            </label>
                            <input type="file" id="coverPhotoInput" class="hidden-file-input" accept="image/*">

                            @if($user->cover_photo)
                                <button class="cover-btn" onclick="deleteCoverPhoto()">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                    Excluir Capa
                                </button>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Profile Picture -->
                <div class="profile-picture-container" style="margin-top: -50px; margin-left: 20px; position: relative; z-index: 10;">
                    <div style="position: relative; display: inline-block;">
                        @if($user->profile_photo)
                            <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}" style="width: 100px; height: 100px; border-radius: 50%; border: 4px solid white; object-fit: cover; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        @else
                            <div style="width: 100px; height: 100px; border-radius: 50%; border: 4px solid white; display: flex; align-items: center; justify-content: center; font-size: 36px; color: #706f6c; font-weight: 600; background: #E5E5E5; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                {{ strtoupper(substr($user->name, 0, 2)) }}
                            </div>
                        @endif
                        <!-- Indicador Online -->
                        <div style="position: absolute; bottom: 4px; right: 4px; width: 16px; height: 16px; background: #10B981; border: 3px solid white; border-radius: 50%;"></div>
                    </div>

                    @if($isOwner)
                        <label for="profilePhotoInput" style="position: absolute; top: 2px; bottom: 0; left: 3px; background: rgba(0,0,0,0.6); border-radius: 50%; width: 90px; height: 90px; display: flex; align-items: center; justify-content: center; cursor: pointer; opacity: 0; transition: opacity 0.2s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0'">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                                <circle cx="12" cy="13" r="4"></circle>
                            </svg>
                        </label>
                        <input type="file" id="profilePhotoInput" class="hidden-file-input" accept="image/*">
                    @endif
                </div>

                <!-- Profile Info -->
                <div class="profile-info" style="padding: 0px 20px; margin-top: -15px;">
                    <!-- Nome e Username -->
                    <div style="margin-bottom: 12px;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                            <h1 style="font-size: 24px; font-weight: 700; color: #1b1b18; margin: 0;">{{ strtolower($user->name) }}</h1>
                            @if($user->creator_status === 'approved')
                                   <svg class="w-6 h-6 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                            @endif
                            <button onclick="shareProfile()" style="background: none; border: none; cursor: pointer; padding: 4px; display: flex; align-items: center; margin-left: auto;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1b1b18" stroke-width="2">
                                    <circle cx="18" cy="5" r="3"></circle>
                                    <circle cx="6" cy="12" r="3"></circle>
                                    <circle cx="18" cy="19" r="3"></circle>
                                    <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                                    <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
                                </svg>
                            </button>
                        </div>
                        @if($user->username)
                            <p style="font-size: 14px; color: #706f6c; margin: 0;"><span>@</span>{{ $user->username }}</p>
                        @endif
                    </div>

                    <!-- Bio com Ler mais/Ler menos -->
                    @if($user->description)
                        <div style="margin-bottom: 12px;">
                            <p id="bio-text" style="font-size: 14px; color: #1b1b18; line-height: 1.5; margin: 0; white-space: pre-wrap; display: none;">{{ $user->description }}</p>
                            <p id="bio-text-short" style="font-size: 14px; color: #1b1b18; line-height: 1.5; margin: 0; white-space: pre-wrap;">{{ Str::limit($user->description, 150) }}</p>
                            @if(strlen($user->description) > 150)
                                <button id="read-more-btn" onclick="toggleBio()" style="background: none; border: none; color: #FF6B35; font-size: 14px; cursor: pointer; padding: 0; margin-top: 4px; font-weight: 500;">Ler mais</button>
                            @endif
                        </div>
                    @endif

                    <!-- Instagram -->
                    @if($instagramUrl)
                        <div style="margin-bottom: 16px;">
                            <a href="{{ $instagramUrl }}" target="_blank" style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                                    <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" fill="white"></path>
                                    <line x1="17.5" y1="6.5" x2="17.51" y2="6.5" stroke="white" stroke-width="2"></line>
                                </svg>
                            </a>
                        </div>
                    @endif

                    <!-- Botões Mimo e Chat (apenas para assinantes) -->

                    @if(!$isOwner && $hasActiveSubscription)
                        <div style="display: flex; gap: 12px; margin-top: 16px; margin-bottom: 16px;">
                          <!--  <button style="flex: 1; padding: 12px 20px; background: #FF6B35; color: white; border: none; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="1" x2="12" y2="23"></line>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                </svg>
                                Mimo
                            </button>-->
                            <a href="{{ route('chat.start', $user->id) }}" style="flex: 1; padding: 12px 20px; background: #FF6B35; color: white; border: none; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                </svg>
                                Chat
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Plans Section - Apenas se NÃO for o próprio perfil e NÃO tiver assinatura ativa -->
            @if(!$isOwner && $plans->count() > 0 && !$hasActiveSubscription)
                <div class="plans-section">
                    <h2 class="plans-title">Planos de Assinatura</h2>
                    @foreach($plans as $plan)
                        <button class="plan-item" onclick="openSubscriptionModal({{ $plan->id }}, {{ json_encode($plan->name) }}, {{ $plan->price }}, {{ $plan->duration_days }})">
                            {{ $plan->name }} - R$ {{ number_format($plan->price, 2, ',', '.') }}
                        </button>
                    @endforeach
                </div>
            @endif

            <!-- Mensagem se já tiver assinatura ativa -->
            {{-- @if(!$isOwner && $hasActiveSubscription)
                <div class="plans-section" style="background: #10B981; color: white; padding: 20px; border-radius: 12px; text-align: center;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin: 0 auto 12px;">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">Você já possui uma assinatura ativa!</h3>
                    <p style="font-size: 14px; opacity: 0.9;">Aproveite o acesso exclusivo ao conteúdo de {{ $user->name }}.</p>
                </div>
            @endif --}}
        </div>
    </div>

    <!-- Seção de Postagens -->
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="max-w-4xl mx-auto">
            <h2 class="text-2xl font-bold text-[#1b1b18] mb-6">Postagens</h2>

            @guest
                <!-- Postagem fictícia para usuários não autenticados -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                    <!-- Header do Post -->
                    <div class="flex items-center justify-between p-4 border-b border-gray-200">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center overflow-hidden">
                                @if($user->profile_photo)
                                    <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}" class="w-full h-full object-cover">
                                @else
                                    <span class="text-gray-600 font-medium text-sm">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </span>
                                @endif
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <span class="font-semibold text-gray-900">{{ $user->name }}</span>
                                    @if($user->creator_status === 'approved')
                                        <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                    @endif
                                </div>
                                <span class="text-xs text-gray-500">{{ now()->format('d M') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Conteúdo bloqueado -->
                    <div class="bg-gray-300 flex flex-col items-center justify-center py-16 px-4 min-h-[400px]">
                        <svg class="w-16 h-16 text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <p class="text-gray-700 text-lg font-semibold mb-2 text-center">Conteúdo exclusivo</p>
                        <p class="text-gray-600 text-sm mb-6 text-center px-4">Para ver o conteúdo de {{ $user->name }}, você precisa fazer login ou criar uma conta.</p>
                        <a href="{{ route('login') }}" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-6 rounded-lg transition-colors">
                            Entrar
                        </a>
                    </div>

                    <!-- Ações do Post (desabilitadas) -->
                    <div class="p-4 space-y-3">
                        <div class="flex items-center space-x-4">
                            <button class="text-gray-400 cursor-not-allowed" disabled>
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                </svg>
                            </button>
                            <button class="text-gray-400 cursor-not-allowed" disabled>
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            @else
                @if(isset($posts) && $posts->count() > 0)
                    <div class="space-y-6">
                        @foreach($posts as $post)
                            <x-post-card :post="$post" />
                        @endforeach
                    </div>

                    <!-- Paginação -->
                    <div class="mt-6">
                        {{ $posts->links() }}
                    </div>
                @else
                    <div class="bg-white shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] rounded-lg p-8 text-center">
                        <p class="text-[#706f6c]">
                            @if($isOwner)
                                Você ainda não criou nenhuma postagem.
                            @else
                                {{ $user->name }} ainda não criou nenhuma postagem.
                            @endif
                        </p>
                    </div>
                @endif
            @endguest
        </div>
    </div>

    <!-- Comments Drawer -->
    <x-comments-drawer />

    <!-- Profile Overlay -->
    @auth
        <x-profile-overlay />
    @endauth

    <!-- Modal de Assinatura -->
    <div id="subscriptionModal" class="subscription-modal">
        <div class="subscription-modal-content">
            <div class="subscription-modal-header">
                <button class="subscription-modal-close" onclick="closeSubscriptionModal()" aria-label="Fechar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>

                @if($user->profile_photo)
                    <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}" class="subscription-modal-profile">
                @else
                    <div class="subscription-modal-profile" style="background: #E5E5E5; display: flex; align-items: center; justify-content: center; font-size: 36px; color: #706f6c; font-weight: 600;">
                        {{ strtoupper(substr($user->name, 0, 2)) }}
                    </div>
                @endif

                <h2 class="subscription-modal-title">Assine {{ $user->name }}</h2>
                <p class="subscription-modal-subtitle">e tenha acesso exclusivo</p>
            </div>

            <div class="subscription-modal-body">
                <div class="subscription-plan-box" id="subscriptionPlanBox">
                    <div class="subscription-plan-name" id="subscriptionPlanName"></div>
                    <div class="subscription-plan-price">
                        <span class="currency">R$</span> <span id="subscriptionPlanPrice"></span>
                    </div>
                    <div class="subscription-plan-billing" id="subscriptionPlanBilling"></div>
                </div>

                <ul class="subscription-benefits">
                    <li>Você terá acesso a área de assinantes</li>
                    <li>Fácil cancelar a qualquer momento</li>
                    <li>Novos vídeos e packs recorrentes</li>
                    <li>Chat mensagens habilitado somente para assinantes!</li>
                </ul>

                <div class="subscription-payment-buttons">
                    <button class="subscription-payment-btn subscription-payment-btn-pix" id="btnPixPayment" onclick="handlePixPayment()">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                        Pagar com PIX
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Compartilhar Perfil -->
    <div id="shareModal" class="subscription-modal">
        <div class="subscription-modal-content" onclick="event.stopPropagation()">
            <div class="subscription-modal-header">
                <button class="subscription-modal-close" onclick="closeShareModal()" aria-label="Fechar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>

                <h2 class="subscription-modal-title">Compartilhar Perfil</h2>
            </div>

            <div class="subscription-modal-body">
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #1b1b18; margin-bottom: 8px;">
                        Link de Compartilhamento
                    </label>
                    <div style="display: flex; gap: 8px;">
                        <input
                            type="text"
                            id="shareLinkInput"
                            value="{{ $shareLink ?? '' }}"
                            readonly
                            style="flex: 1; padding: 12px 16px; border: 1px solid #E2E8F0; border-radius: 8px; font-size: 14px; background: #F7FAFC;"
                        >
                        <button
                            onclick="copyShareLink()"
                            style="padding: 12px 24px; background: #FF6B35; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px;"
                        >
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                            </svg>
                            Copiar
                        </button>
                    </div>
                </div>

                <button
                    onclick="shareOnWhatsApp()"
                    style="width: 100%; padding: 16px; background: #25D366; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 16px;"
                >
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                    </svg>
                    Enviar no WhatsApp
                </button>

                <button
                    onclick="closeShareModal()"
                    style="width: 100%; padding: 12px; background: white; color: #1b1b18; border: 2px solid #E2E8F0; border-radius: 8px; font-weight: 600; cursor: pointer;"
                >
                    Fechar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Crop da Foto de Capa -->
    <div id="cropCoverModal" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.8); z-index: 9999; padding: 20px; overflow: auto;">
        <div style="max-width: 900px; margin: 0 auto; background: white; border-radius: 16px; padding: 24px;">
            <h2 style="font-size: 20px; font-weight: 600; color: #1b1b18; margin-bottom: 20px;">Ajustar Foto de Capa</h2>
            
            <div style="max-height: 60vh; overflow: hidden; margin-bottom: 20px; background: #f5f5f5; border-radius: 8px;">
                <img id="coverImageToCrop" style="max-width: 100%; display: block;">
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button
                    onclick="closeCropModal()"
                    style="padding: 12px 24px; background: white; color: #1b1b18; border: 2px solid #E5E5E5; border-radius: 8px; font-weight: 600; cursor: pointer;"
                >
                    Cancelar
                </button>
                <button
                    onclick="applyCrop()"
                    style="padding: 12px 24px; background: #FF6B35; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;"
                >
                    Aplicar e Salvar
                </button>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const isOwner = {{ $isOwner ? 'true' : 'false' }};
        let cropperInstance = null;
        let originalCoverFile = null;

        // Upload de foto de capa
        if (isOwner) {
            document.getElementById('coverPhotoInput')?.addEventListener('change', function(e) {
                if (e.target.files.length > 0) {
                    openCropModal(e.target.files[0]);
                }
                // Limpa o input para permitir selecionar o mesmo arquivo novamente
                e.target.value = '';
            });
        }

        // Upload de foto de perfil
        if (isOwner) {
            document.getElementById('profilePhotoInput')?.addEventListener('change', function(e) {
                if (e.target.files.length > 0) {
                    uploadProfilePhoto(e.target.files[0]);
                }
            });
        }

        function openCropModal(file) {
            originalCoverFile = file;
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const image = document.getElementById('coverImageToCrop');
                image.src = e.target.result;
                
                // Mostra o modal
                document.getElementById('cropCoverModal').style.display = 'block';
                document.body.style.overflow = 'hidden';
                
                // Inicializa o Cropper.js
                if (cropperInstance) {
                    cropperInstance.destroy();
                }
                
                cropperInstance = new Cropper(image, {
                    aspectRatio: 16 / 6, // Proporção típica de capa (mais largo)
                    viewMode: 1,
                    dragMode: 'move',
                    autoCropArea: 1,
                    restore: false,
                    guides: true,
                    center: true,
                    highlight: false,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    toggleDragModeOnDblclick: false,
                    minContainerWidth: 200,
                    minContainerHeight: 200,
                    responsive: true,
                });
            };
            
            reader.readAsDataURL(file);
        }

        function closeCropModal() {
            document.getElementById('cropCoverModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            
            if (cropperInstance) {
                cropperInstance.destroy();
                cropperInstance = null;
            }
            
            originalCoverFile = null;
        }

        function applyCrop() {
            if (!cropperInstance) {
                return;
            }

            // Obtém o canvas com a imagem cropada
            const canvas = cropperInstance.getCroppedCanvas({
                width: 1600, // Largura ideal para foto de capa
                height: 600,  // Altura proporcional
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
            });

            // Converte o canvas para blob
            canvas.toBlob(function(blob) {
                // Cria um arquivo a partir do blob
                const croppedFile = new File(
                    [blob], 
                    originalCoverFile.name, 
                    { type: 'image/jpeg', lastModified: Date.now() }
                );

                // Fecha o modal
                closeCropModal();

                // Faz o upload da imagem cropada
                uploadCoverPhoto(croppedFile);
            }, 'image/jpeg', 0.95); // Qualidade de 95%
        }

        function uploadCoverPhoto(file) {
            const formData = new FormData();
            formData.append('cover_photo', file);
            formData.append('_token', csrfToken);

            // Mostra loading
            const loadingDiv = document.createElement('div');
            loadingDiv.innerHTML = '<div style="position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 10000; display: flex; align-items: center; justify-content: center;"><div style="background: white; padding: 24px; border-radius: 12px; text-align: center;"><div style="margin-bottom: 12px;">Enviando foto de capa...</div><div style="width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #FF6B35; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto;"></div></div></div><style>@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>';
            document.body.appendChild(loadingDiv);

            fetch('{{ route("profile.update-cover-photo") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    document.body.removeChild(loadingDiv);
                    alert('Erro ao fazer upload da foto de capa.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.body.removeChild(loadingDiv);
                alert('Erro ao fazer upload da foto de capa.');
            });
        }

        function uploadProfilePhoto(file) {
            const formData = new FormData();
            formData.append('profile_photo', file);
            formData.append('_token', csrfToken);

            fetch('{{ route("profile.update-profile-photo") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erro ao fazer upload da foto de perfil.');
            });
        }

        function deleteCoverPhoto() {
            if (!confirm('Tem certeza que deseja excluir a foto de capa?')) {
                return;
            }

            fetch('{{ route("profile.delete-cover-photo") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erro ao excluir foto de capa.');
            });
        }

        function shareProfile() {
            openShareModal();
        }

        function openShareModal() {
            const modal = document.getElementById('shareModal');
            if (modal) {
                // Atualiza o link de compartilhamento dinamicamente
                const shareLinkInput = document.getElementById('shareLinkInput');
                if (shareLinkInput) {
                    // Se o usuário está autenticado e não é o dono do perfil, gera link com dois slugs
                    // Isso permite redirecionar para o criador após cadastro, mas indicação vale para qualquer assinatura
                    const isOwner = {{ $isOwner ? 'true' : 'false' }};
                    const creatorSlug = '{{ $user->slug }}';
                    const baseUrl = window.location.origin;

                    if (!isOwner && {{ Auth::check() ? 'true' : 'false' }}) {
                        // Link de indicação com dois slugs e prefixo /a/: /a/referrerSlug/creatorSlug
                        // Após cadastro, redireciona para o criador, mas indicação vale para qualquer assinatura
                        const userSlug = '{{ Auth::check() ? Auth::user()->slug : "" }}';
                        shareLinkInput.value = baseUrl + '/a/' + userSlug + '/' + creatorSlug;
                    } else if (isOwner) {
                        // Link direto do perfil do criador
                        shareLinkInput.value = baseUrl + '/' + creatorSlug;
                    } else {
                        // Mantém o link original se não autenticado (link direto do perfil)
                        shareLinkInput.value = '{{ $shareLink ?? "" }}';
                    }
                }

                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeShareModal() {
            const modal = document.getElementById('shareModal');
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        }

        function copyShareLink() {
            const input = document.getElementById('shareLinkInput');
            if (input) {
                input.select();
                input.setSelectionRange(0, 99999); // Para mobile
                document.execCommand('copy');

                // Feedback visual
                const button = event.target.closest('button');
                const originalText = button.innerHTML;
                button.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg> Copiado!';
                button.style.background = '#10B981';

                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.background = '#FF6B35';
                }, 2000);
            }
        }

        function shareOnWhatsApp() {
            const shareLink = document.getElementById('shareLinkInput').value;
            const message = encodeURIComponent('Confira este perfil: ' + shareLink);
            window.open('https://wa.me/?text=' + message, '_blank');
        }

        // Função para expandir/recolher bio
        function toggleBio() {
            const bioText = document.getElementById('bio-text');
            const bioTextShort = document.getElementById('bio-text-short');
            const readMoreBtn = document.getElementById('read-more-btn');

            if (!bioText || !bioTextShort || !readMoreBtn) return;

            if (bioText.style.display === 'none' || bioText.style.display === '') {
                bioText.style.display = 'block';
                bioTextShort.style.display = 'none';
                readMoreBtn.textContent = 'Ler menos';
            } else {
                bioText.style.display = 'none';
                bioTextShort.style.display = 'block';
                readMoreBtn.textContent = 'Ler mais';
            }
        }

        // Fechar modal ao clicar fora
        document.getElementById('shareModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeShareModal();
            }
        });

        // Fechar modal com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeShareModal();
            }
        });

        // Funções do Modal de Assinatura
        let currentPlan = null;

        function openSubscriptionModal(planId, planName, planPrice, durationDays) {
            // Verifica se o usuário está autenticado
            const isAuthenticated = {{ Auth::check() ? 'true' : 'false' }};
            if (!isAuthenticated) {
                // Redireciona para a página de cadastro
                window.location.href = '{{ route("register") }}';
                return;
            }

            // Valida os parâmetros
            if (!planId || !planName || planPrice === undefined || !durationDays) {
                console.error('Parâmetros inválidos para abrir modal:', { planId, planName, planPrice, durationDays });
                alert('Erro ao abrir modal. Por favor, tente novamente.');
                return;
            }

            currentPlan = {
                id: parseInt(planId),
                name: planName,
                price: parseFloat(planPrice),
                durationDays: parseInt(durationDays)
            };

            // Atualiza o conteúdo do modal
            document.getElementById('subscriptionPlanName').textContent = planName;
            document.getElementById('subscriptionPlanPrice').textContent = currentPlan.price.toFixed(2).replace('.', ',');

            // Define o texto de cobrança baseado na duração
            let billingText = '';
            if (currentPlan.durationDays === 30) {
                billingText = 'Cobrança realizada a cada mês';
            } else if (currentPlan.durationDays === 90) {
                billingText = 'Cobrança realizada a cada 3 meses';
            } else if (currentPlan.durationDays === 180) {
                billingText = 'Cobrança realizada a cada 6 meses';
            } else if (currentPlan.durationDays === 365) {
                billingText = 'Cobrança realizada anualmente';
            } else {
                billingText = `Cobrança realizada a cada ${currentPlan.durationDays} dias`;
            }
            document.getElementById('subscriptionPlanBilling').textContent = billingText;

            // Armazena o planId no botão como data-attribute (backup)
            document.getElementById('btnPixPayment').setAttribute('data-plan-id', currentPlan.id);

            // Abre o modal
            document.getElementById('subscriptionModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeSubscriptionModal() {
            document.getElementById('subscriptionModal').classList.remove('active');
            document.body.style.overflow = '';

            // Limpa o data-attribute do botão
            const btnPix = document.getElementById('btnPixPayment');
            if (btnPix) btnPix.removeAttribute('data-plan-id');

            currentPlan = null;
        }

        // Fecha o modal ao clicar fora dele
        document.getElementById('subscriptionModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeSubscriptionModal();
            }
        });

        // Fecha o modal com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('subscriptionModal').classList.contains('active')) {
                closeSubscriptionModal();
            }
        });

        function handlePixPayment() {
            // Tenta obter o planId de múltiplas fontes
            let planId = null;

            if (currentPlan && currentPlan.id) {
                planId = currentPlan.id;
            } else {
                // Fallback: tenta obter do data-attribute do botão
                const btn = document.getElementById('btnPixPayment');
                if (btn) {
                    planId = btn.getAttribute('data-plan-id');
                }
            }

            if (!planId) {
                console.error('Plano não selecionado');
                alert('Erro: Plano não encontrado. Por favor, selecione um plano novamente.');
                return;
            }

            // Fecha o modal
            closeSubscriptionModal();

            // Redireciona para o checkout de PIX
            // O CheckoutController verificará se o usuário tem dados completos
            window.location.href = `/checkout/${planId}/pix`;
        }
    </script>
</body>
</html>

