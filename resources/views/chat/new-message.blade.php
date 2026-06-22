<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Nova Mensagem - {{ config('app.name', 'Laravel') }}</title>

    <!-- TailwindCSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- jQuery via CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Estilos e scripts customizados -->
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/profile-overlay.css">
    <script src="/js/app.js"></script>
    <script src="/js/profile-overlay.js"></script>

    <style>
        .chat-container {
            display: flex;
            height: calc(100vh - 64px);
            background: #FDFDFC;
        }

        .new-message-sidebar {
            width: 400px;
            background: #FDFDFC;
            border-right: 1px solid #E2E8F0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .new-message-header {
            padding: 20px;
            border-bottom: 1px solid #E2E8F0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .filter-buttons {
            display: flex;
            gap: 8px;
            padding: 0 20px 12px 20px;
        }

        .filter-btn {
            padding: 8px 16px;
            border-radius: 20px;
            border: 1px solid #E2E8F0;
            background: white;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-btn.active {
            background: #FF6B35;
            color: white;
            border-color: #FF6B35;
        }

        .search-input {
            padding: 12px 16px;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            width: 100%;
            font-size: 14px;
            margin: 0 20px 12px 20px;
        }

        .users-list {
            flex: 1;
            overflow-y: auto;
            padding: 8px;
        }

        .user-item {
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            margin-bottom: 4px;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-item:hover {
            background: #F7FAFC;
        }

        .user-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }

        .user-info {
            flex: 1;
            min-width: 0;
        }

        .user-name {
            font-weight: 600;
            color: #1b1b18;
            margin-bottom: 2px;
        }

        .user-handle {
            font-size: 12px;
            color: #706f6c;
        }

        .user-radio {
            width: 20px;
            height: 20px;
            border: 2px solid #E2E8F0;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .user-item.selected .user-radio {
            border-color: #FF6B35;
            background: #FF6B35;
            position: relative;
        }

        .user-item.selected .user-radio::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 8px;
            height: 8px;
            background: white;
            border-radius: 50%;
        }

        .chat-empty {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #706f6c;
            font-size: 16px;
            text-align: center;
            padding: 40px;
        }
    </style>
</head>
<body class="bg-[#FDFDFC] text-[#1b1b18] min-h-screen">
    <!-- Top Navigation (Desktop) -->
    <x-topnav />

    <!-- Bottom Navigation (Mobile) -->
    <x-bottomnav />

    <!-- Profile Drawer -->
    <x-profile-drawer />

    <!-- Chat Container -->
    <div class="chat-container" style="margin-top: 64px;">
        <!-- Sidebar de Seleção -->
        <div class="new-message-sidebar">
            <div class="new-message-header">
                <a href="{{ route('chat.index') }}" class="text-[#706f6c] hover:text-[#1b1b18]">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <h2 class="text-xl font-bold text-[#1b1b18]">NOVA MENSAGEM</h2>
            </div>

            <div>
                <h3 class="px-20 pb-2 text-sm font-semibold text-[#1b1b18]">ENVIAR PARA</h3>
                <div class="filter-buttons">
                    <button class="filter-btn active" data-filter="all">TODOS</button>
                    <button class="filter-btn" data-filter="followers">0 SEGUIDORES</button>
                    <button class="filter-btn" data-filter="following">7 SEGUINDO</button>
                </div>
            </div>

            <input
                type="text"
                id="userSearch"
                class="search-input"
                placeholder="Pesquisar"
                onkeyup="searchUsers()"
            >

            <div class="users-list" id="usersList">
                @foreach($availableUsers as $user)
                    <div class="user-item" onclick="selectUser({{ $user->id }})" data-user-id="{{ $user->id }}">
                        <div class="relative">
                            @if($user->profile_photo)
                                <img src="{{ $user->profile_photo_url }}"
                                     alt="{{ $user->name }}"
                                     class="user-avatar">
                            @else
                                <div class="user-avatar bg-gray-300 flex items-center justify-center">
                                    <span class="text-gray-600 font-semibold">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </span>
                                </div>
                            @endif
                        </div>
                        <div class="user-info">
                            <div class="flex items-center gap-2">
                                <p class="user-name">{{ $user->name }}</p>
                                @if($user->creator_status === 'approved')
                                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </div>
                            <p class="user-handle"><span>@</span>{{ $user->username }}</p>
                        </div>
                        <div class="user-radio"></div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Área Vazia -->
        <div class="chat-empty">
            SELECIONAR USUÁRIO PARA ENVIAR UMA MENSAGEM
        </div>
    </div>

    <!-- Profile Overlay -->
    @auth
        <x-profile-overlay />
    @endauth

    <script>
        let selectedUserId = null;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        // Função auxiliar para gerar URL de foto de perfil
        function getProfilePhotoUrl(profilePhoto) {
            if (!profilePhoto) return '';

            // Sempre usa a API externa
            // O profilePhoto deve conter o new_name retornado pela API
            const apiUrl = '{{ config('services.media_api.url') }}';
            const imageEndpoint = '{{ config('services.media_api.image_endpoint') }}';
            return `${apiUrl}${imageEndpoint}/${profilePhoto}`;
        }

        function selectUser(userId) {
            // Remove seleção anterior
            document.querySelectorAll('.user-item').forEach(item => {
                item.classList.remove('selected');
            });

            // Marca como selecionado
            const userItem = document.querySelector(`[data-user-id="${userId}"]`);
            if (userItem) {
                userItem.classList.add('selected');
                selectedUserId = userId;

                // Inicia a conversa
                window.location.href = `/chat/start/${userId}`;
            }
        }

        function searchUsers() {
            const search = document.getElementById('userSearch').value;

            fetch(`{{ route('chat.search-users') }}?search=${encodeURIComponent(search)}`, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderUsers(data.users);
                }
            })
            .catch(error => {
                console.error('Erro ao buscar usuários:', error);
            });
        }

        function renderUsers(users) {
            const usersList = document.getElementById('usersList');
            usersList.innerHTML = '';

            if (users.length === 0) {
                usersList.innerHTML = '<p class="text-center text-[#706f6c] py-8">Nenhum usuário encontrado.</p>';
                return;
            }

            users.forEach(user => {
                const userItem = document.createElement('div');
                userItem.className = 'user-item';
                userItem.setAttribute('data-user-id', user.id);
                userItem.onclick = () => selectUser(user.id);

                const avatarHtml = user.profile_photo
                    ? `<img src="${getProfilePhotoUrl(user.profile_photo)}" alt="${user.name}" class="user-avatar">`
                    : `<div class="user-avatar bg-gray-300 flex items-center justify-center">
                        <span class="text-gray-600 font-semibold">${user.name.substring(0, 2).toUpperCase()}</span>
                    </div>`;

                const checkmarkHtml = user.creator_status === 'approved'
                    ? `<svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>`
                    : '';

                userItem.innerHTML = `
                    ${avatarHtml}
                    <div class="user-info">
                        <div class="flex items-center gap-2">
                            <p class="user-name">${user.name}</p>
                            ${checkmarkHtml}
                        </div>
                        <p class="user-handle">@${user.username}</p>
                    </div>
                    <div class="user-radio"></div>
                `;

                usersList.appendChild(userItem);
            });
        }
    </script>
</body>
</html>

