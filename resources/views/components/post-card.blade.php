@props(['post'])

@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Facades\Route;

    $isLiked = $post->isLikedBy(Auth::id());
    $likesCount = $post->likes()->count();
    $commentsCount = $post->comments()->count();
    $mediaItems = $post->media;
    $currentMediaIndex = 0;

    // Verifica se estamos na página do perfil do criador desta postagem
    $currentRoute = Route::currentRouteName();
    $isOnCreatorProfile = false;
    if (in_array($currentRoute, ['profile.show', 'profile.show.referral'])) {
        $routeParams = Route::current()->parameters();
        $profileSlug = null;

        // Para rota de referral, o creatorSlug é o segundo parâmetro
        if ($currentRoute === 'profile.show.referral') {
            $profileSlug = $routeParams['creatorSlug'] ?? null;
        } else {
            // Para rota normal, o username é o parâmetro
            $profileSlug = $routeParams['username'] ?? null;
        }

        // Compara com o username ou slug do criador da postagem
        // (a rota pode usar username ou slug, então verificamos ambos)
        if (
            $profileSlug &&
            (($post->user->username && $post->user->username === $profileSlug) ||
                ($post->user->slug && $post->user->slug === $profileSlug))
        ) {
            $isOnCreatorProfile = true;
        }
    }

    // URL do perfil do criador (usa username)
    $creatorProfileUrl = $post->user->username ? '/' . $post->user->username : '#';

    // Verifica visibilidade da postagem
    $isPostLocked = false;
    $canViewPost = true;

    if ($post->visibility === 'subscriber') {
        // Se é postagem para assinantes, verifica se o usuário tem acesso
        if (Auth::check()) {
            // Se é o próprio criador, sempre pode ver
            if ($post->user_id === Auth::id()) {
                $canViewPost = true;
                $isPostLocked = false;
            } else {
                // Verifica se tem assinatura ativa
                $canViewPost = Auth::user()->hasActiveSubscription($post->user_id);
                $isPostLocked = !$canViewPost;
            }
        } else {
            // Usuário não autenticado: mostra a postagem mas bloqueada
            $canViewPost = false;
            $isPostLocked = true;
        }
    }

    // Busca planos do criador se a postagem estiver bloqueada
    $creatorPlans = collect([]);
    if ($isPostLocked) {
        $creatorPlans = \App\Models\SubscriptionPlan::where('user_id', $post->user_id)
            ->where('is_active', true)
            ->orderBy('duration_days')
            ->get();
    }
@endphp

<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6"
    data-post-id="{{ $post->id }}">
    <!-- Header do Post -->
    <div class="flex items-center justify-between p-4 border-b border-gray-200">
        <div class="flex items-center space-x-3">
            @if ($isOnCreatorProfile)
                <!-- Se está no perfil do criador, não é clicável -->
                <div
                    class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center overflow-hidden cursor-default">
                    @if ($post->user->profile_photo)
                        <img src="{{ $post->user->profile_photo_url }}" alt="{{ $post->user->name }}"
                            class="w-full h-full object-cover">
                    @else
                        <span class="text-gray-600 font-medium text-sm">
                            {{ strtoupper(substr($post->user->name, 0, 2)) }}
                        </span>
                    @endif
                </div>
            @else
                <!-- Se não está no perfil, é clicável -->
                <a href="{{ $creatorProfileUrl }}"
                    class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center overflow-hidden hover:opacity-80 transition-opacity">
                    @if ($post->user->profile_photo)
                        <img src="{{ $post->user->profile_photo_url }}" alt="{{ $post->user->name }}"
                            class="w-full h-full object-cover">
                    @else
                        <span class="text-gray-600 font-medium text-sm">
                            {{ strtoupper(substr($post->user->name, 0, 2)) }}
                        </span>
                    @endif
                </a>
            @endif
            <div>
                @if ($isOnCreatorProfile)
                    <!-- Se está no perfil do criador, não é clicável -->
                    <div class="flex items-center space-x-2">
                        <span class="font-semibold text-gray-900">{{ $post->user->name }}</span>
                        @if ($post->user->creator_status === 'approved')
                            <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                        @endif
                    </div>
                @else
                    <!-- Se não está no perfil, é clicável -->
                    <a href="{{ $creatorProfileUrl }}"
                        class="flex items-center space-x-2 hover:opacity-80 transition-opacity">
                        <span class="font-semibold text-gray-900">{{ $post->user->name }}</span>
                        @if ($post->user->creator_status === 'approved')
                            <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                        @endif
                    </a>
                @endif
                <span class="text-xs text-gray-500">{{ $post->created_at->format('d M') }}</span>
            </div>
        </div>
        <div class="relative">
            <button
                onclick="togglePostMenu({{ $post->id }})"
                class="text-gray-500 hover:text-gray-700 relative z-10">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path
                        d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                </svg>
            </button>

            <!-- Menu Dropdown -->
            <div id="postMenu{{ $post->id }}" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-20">
                @auth
                    @if($post->user_id === Auth::id())
                        <!-- Opções para o criador -->
                        <a href="#" onclick="editPost({{ $post->id }}); return false;" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 ">
                            Editar
                        </a>
                        <a href="#" onclick="deletePost({{ $post->id }}); return false;" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                            Deletar
                        </a>
                    @else
                        <!-- Opção para denunciar -->
                        <a href="#" onclick="reportPost({{ $post->id }}); return false;" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100 ">
                            Denunciar
                        </a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="block px-4 py-2 text-sm text-gray-700  hover:bg-gray-100 ">
                        Denunciar
                    </a>
                @endauth
            </div>
        </div>
    </div>

    <!-- Mídia do Post -->
    @if ($mediaItems->count() > 0)
        <div class="relative">
            @if ($isPostLocked)
                <!-- Div bloqueada para não assinantes -->
                <div class="bg-gray-300  flex flex-col items-center justify-center py-16 px-4 min-h-[400px]">
                    <svg class="w-16 h-16 text-gray-500  mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <p class="text-gray-700  text-lg font-semibold mb-4">Apenas para assinantes</p>
                    <button
                        onclick="handleUnlockContent({{ $post->user_id }}, {{ json_encode($post->user->name) }})"
                        class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-6 rounded-lg transition-colors">
                        Desbloquear conteúdo
                    </button>
                </div>
            @else
                <!-- Conteúdo normal da mídia -->
                <div class="post-media-container relative" data-post-id="{{ $post->id }}">
                    @foreach ($mediaItems as $index => $media)
                        <div class="post-media-item {{ $index === 0 ? 'active' : 'hidden' }}"
                            data-index="{{ $index }}">
                            @if ($media->file_type === 'video')
                                @php
                                    // Detecta o tipo MIME baseado na extensão do arquivo
                                    $fileExtension = strtolower(pathinfo($media->file_path, PATHINFO_EXTENSION));
                                    $mimeType = 'video/mp4'; // padrão
                                    if ($fileExtension === 'webm') {
                                        $mimeType = 'video/webm';
                                    } elseif ($fileExtension === 'ogg' || $fileExtension === 'ogv') {
                                        $mimeType = 'video/ogg';
                                    } elseif ($fileExtension === 'mov') {
                                        $mimeType = 'video/quicktime';
                                    } elseif ($fileExtension === 'avi') {
                                        $mimeType = 'video/x-msvideo';
                                    }
                                @endphp
                                <div class="video-wrapper video-wrapper-premium">
                                    <video id="video-player-{{ $post->id }}-{{ $index }}"
                                        oncontextmenu="return false;"
                                        class="video-js vjs-default-skin vjs-big-play-centered" controls preload="metadata"
                                        data-media-index="{{ $index }}"
                                        data-post-id="{{ $post->id }}"
                                        >
                                        <source src="{{ $media->url }}" type="{{ $mimeType }}" />
                                    </video>
                                </div>
                            @else
                                <img src="{{ $media->url }}" alt="Post media"
                                    class="w-full h-auto max-h-[600px] object-contain">
                            @endif
                        </div>
                    @endforeach

                    <!-- Navegação entre mídias -->
                    @if ($mediaItems->count() > 1)
                        <div
                            class="absolute bottom-4 left-1/2 transform -translate-x-1/2 bg-black bg-opacity-50 text-white px-3 py-1 rounded-full text-sm">
                            <span class="current-media-index">1</span> / {{ $mediaItems->count() }}
                        </div>

                        <button
                            class="post-media-prev absolute left-2 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 hover:bg-opacity-70 text-white rounded-full p-2 hidden">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>

                        <button
                            class="post-media-next absolute right-2 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 hover:bg-opacity-70 text-white rounded-full p-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    @endif
                </div>
            @endif
        </div>
    @endif

    <!-- Ações do Post -->
    <div class="p-4 space-y-3">
        <!-- Botões de ação -->
        <div class="flex items-center space-x-4">
            <button
                class="post-like-btn flex items-center space-x-2 {{ $isLiked ? 'text-red-500' : 'text-gray-700 ' }}"
                data-post-id="{{ $post->id }}" data-liked="{{ $isLiked ? 'true' : 'false' }}">
                <svg class="w-6 h-6 {{ $isLiked ? 'fill-current' : '' }}" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
            </button>

            <button class="post-comment-btn flex items-center space-x-2 text-gray-700 "
                data-post-id="{{ $post->id }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
            </button>

            {{-- <button class="flex items-center space-x-2 text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </button> --}}

            {{-- <button class="ml-auto flex items-center space-x-2 text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                </svg>
            </button> --}}
        </div>

        <!-- Contadores -->
        <div class="space-y-1">
            <div class="post-likes-count text-sm font-semibold text-gray-900 ">
                {{ $likesCount }} {{ $likesCount === 1 ? 'curtida' : 'curtidas' }}
            </div>
            @if ($post->description && !$isPostLocked)
                <div class="text-sm text-gray-900 ">
                    <span class="font-semibold">{{ $post->user->name }}</span>
                    <span>{!! nl2br(preg_replace(
                        '/(https?:\/\/[^\s]+)/',
                        '<a href="$1" target="_blank" rel="noopener noreferrer" style="color:#18DBC1;text-decoration:underline;">$1</a>',
                        e($post->description)
                    )) !!}</span>
                </div>
            @endif
            @if (!$isPostLocked)
                <button
                    class="post-comments-toggle text-sm text-gray-500  hover:text-gray-700 "
                    data-post-id="{{ $post->id }}">
                    Ver todos os <span class="post-comments-count">{{ $commentsCount }}</span> comentários
                </button>
            @endif
        </div>
    </div>
</div>

<!-- Modal de Planos do Criador -->
<div id="postPlansModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center" style="display: none;">
    <div class="bg-white  rounded-lg shadow-xl max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-900 ">Escolha um Plano</h3>
                <button onclick="closePostPlansModal()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Creator Info -->
            <div id="postPlansCreatorInfo" class="flex items-center space-x-3 mb-6">
                <div id="postPlansCreatorPhoto" class="w-12 h-12 rounded-full bg-gray-300 flex items-center justify-center overflow-hidden">
                    <!-- Photo will be inserted here -->
                </div>
                <div>
                    <h4 id="postPlansCreatorName" class="font-semibold text-gray-900 "></h4>
                    <p class="text-sm text-gray-500 ">Assine e tenha acesso exclusivo</p>
                </div>
            </div>

            <!-- Plans List -->
            <div id="postPlansList" class="space-y-3">
                <!-- Plans will be inserted here -->
            </div>

            <!-- Loading State -->
            <div id="postPlansLoading" class="text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 "></div>
                <p class="mt-2 text-gray-500 ">Carregando planos...</p>
            </div>

            <!-- Empty State -->
            <div id="postPlansEmpty" class="text-center py-8 hidden">
                <p class="text-gray-500">Nenhum plano disponível no momento.</p>
            </div>
        </div>
    </div>
</div>

<script>
    // Função para lidar com o desbloqueio de conteúdo
    function handleUnlockContent(creatorId, creatorName) {
        // Verifica se o usuário está autenticado
        @auth
        const isAuthenticated = true;
        @else
        const isAuthenticated = false;
        @endauth

        if (!isAuthenticated) {
            // Se não estiver autenticado, redireciona para cadastro
            window.location.href = '{{ route("register") }}';
            return;
        }

        // Se estiver autenticado, abre o modal de planos
        openPostPlansModal(creatorId, creatorName);
    }

    // Função para abrir modal de planos
    function openPostPlansModal(creatorId, creatorName) {

        const modal = document.getElementById('postPlansModal');
        const loading = document.getElementById('postPlansLoading');
        const plansList = document.getElementById('postPlansList');
        const emptyState = document.getElementById('postPlansEmpty');
        const creatorInfo = document.getElementById('postPlansCreatorInfo');
        const creatorNameEl = document.getElementById('postPlansCreatorName');
        const creatorPhotoEl = document.getElementById('postPlansCreatorPhoto');

        // Mostra modal e loading
        modal.style.display = 'flex';
        loading.classList.remove('hidden');
        plansList.innerHTML = '';
        emptyState.classList.add('hidden');
        document.body.style.overflow = 'hidden';

        // Busca planos via AJAX
        fetch(`/profile/creator/${creatorId}/plans`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            loading.classList.add('hidden');

            if (data.success && data.plans && data.plans.length > 0) {
                // Atualiza informações do criador
                creatorNameEl.textContent = data.creator.name;
                if (data.creator.profile_photo) {
                    creatorPhotoEl.innerHTML = `<img src="${data.creator.profile_photo}" alt="${data.creator.name}" class="w-full h-full object-cover">`;
                } else {
                    creatorPhotoEl.innerHTML = `<span class="text-gray-600 font-medium text-sm">${data.creator.name.substring(0, 2).toUpperCase()}</span>`;
                }

                // Renderiza planos
                plansList.innerHTML = data.plans.map(plan => {
                    const billingText = plan.duration_days === 30 ? 'Cobrança realizada a cada mês' :
                                      plan.duration_days === 90 ? 'Cobrança realizada a cada 3 meses' :
                                      plan.duration_days === 180 ? 'Cobrança realizada a cada 6 meses' :
                                      plan.duration_days === 365 ? 'Cobrança realizada anualmente' :
                                      `Cobrança realizada a cada ${plan.duration_days} dias`;

                    return `
                        <button
                            onclick="selectPostPlan(${plan.id}, '${plan.name.replace(/'/g, "\\'")}', ${plan.price}, ${plan.duration_days})"
                            class="w-full bg-white border-2 border-gray-200 rounded-lg p-4 text-left hover:border-red-500 transition-colors">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="font-semibold text-gray-900">${plan.name}</h4>
                                <span class="text-lg font-bold text-red-500">R$ ${parseFloat(plan.price).toFixed(2).replace('.', ',')}</span>
                            </div>
                            <p class="text-sm text-gray-500">${billingText}</p>
                        </button>
                    `;
                }).join('');
            } else {
                emptyState.classList.remove('hidden');
            }
        })
        .catch(error => {
            console.error('Erro ao buscar planos:', error);
            loading.classList.add('hidden');
            emptyState.classList.remove('hidden');
        });
    }

    // Função para fechar modal
    function closePostPlansModal() {
        const modal = document.getElementById('postPlansModal');
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    // Função para selecionar plano e redirecionar para checkout
    function selectPostPlan(planId, planName, planPrice, durationDays) {
        closePostPlansModal();

        // Redireciona para o checkout com o plano selecionado
        window.location.href = `/checkout/${planId}/card`;
    }

    // Fecha modal ao clicar fora
    document.getElementById('postPlansModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closePostPlansModal();
        }
    });

    // Fecha modal com ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('postPlansModal');
            if (modal && modal.style.display === 'flex') {
                closePostPlansModal();
            }
        }
    });

    // Função para toggle do menu de postagem
    function togglePostMenu(postId) {
        const menu = document.getElementById('postMenu' + postId);
        const allMenus = document.querySelectorAll('[id^="postMenu"]');

        // Fecha todos os outros menus
        allMenus.forEach(m => {
            if (m.id !== 'postMenu' + postId) {
                m.classList.add('hidden');
            }
        });

        // Toggle do menu atual
        menu.classList.toggle('hidden');
    }

    // Fecha menus ao clicar fora
    document.addEventListener('click', function(e) {
        if (!e.target.closest('[onclick*="togglePostMenu"]') && !e.target.closest('[id^="postMenu"]')) {
            document.querySelectorAll('[id^="postMenu"]').forEach(menu => {
                menu.classList.add('hidden');
            });
        }
    });

    // Função para denunciar postagem
    function reportPost(postId) {
        const reason = prompt('Por favor, informe o motivo da denúncia (opcional):');

        if (reason === null) {
            return; // Usuário cancelou
        }

        fetch('/reports', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                post_id: postId,
                reason: reason || null
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                // Fecha o menu
                document.getElementById('postMenu' + postId).classList.add('hidden');
            } else {
                alert(data.message || 'Erro ao denunciar postagem');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao denunciar postagem');
        });
    }

    // Função para editar postagem
    function editPost(postId) {
        window.location.href = `/posts/${postId}/edit`;
    }

    // Função para deletar postagem
    function deletePost(postId) {
        if (!confirm('Tem certeza que deseja deletar esta postagem? Esta ação não pode ser desfeita.')) {
            return;
        }

        fetch(`/posts/${postId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Postagem deletada com sucesso!');
                // Remove a postagem do DOM
                document.querySelector(`[data-post-id="${postId}"]`).remove();
            } else {
                alert(data.message || 'Erro ao deletar postagem');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao deletar postagem');
        });
    }
</script>
