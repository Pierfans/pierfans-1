<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Chat - {{ config('app.name', 'Laravel') }}</title>

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

        .conversations-sidebar {
            width: 350px;
            background: #FDFDFC;
            border-right: 1px solid #E2E8F0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .conversations-header {
            padding: 20px;
            border-bottom: 1px solid #E2E8F0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .conversations-search {
            padding: 12px 16px;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            width: 100%;
            font-size: 14px;
            margin-bottom: 12px;
        }

        .conversations-list {
            flex: 1;
            overflow-y: auto;
            padding: 8px;
        }

        .conversation-item {
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            margin-bottom: 4px;
            transition: background 0.2s;
        }

        .conversation-item:hover {
            background: #F7FAFC;
        }

        .conversation-item.active {
            background: #EFF6FF;
        }

        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: white;
        }

        .chat-empty {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #706f6c;
            font-size: 16px;
        }

        .chat-header {
            padding: 16px 20px;
            border-bottom: 1px solid #E2E8F0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #F9FAFB;
        }

        .message {
            margin-bottom: 16px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .message.own {
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .message-content {
            max-width: 70%;
            background: white;
            padding: 10px 14px;
            border-radius: 12px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .message.own .message-content {
            background: #FF6B35;
            color: white;
        }

        .message-image {
            max-width: 100%;
            border-radius: 8px;
            cursor: pointer;
        }

        .message-time {
            font-size: 11px;
            color: #706f6c;
            margin-top: 4px;
        }

        .message.own .message-time {
            color: rgba(255, 255, 255, 0.8);
        }

        .chat-input-area {
            padding: 16px 20px;
            border-top: 1px solid #E2E8F0;
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .chat-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #E2E8F0;
            border-radius: 24px;
            font-size: 14px;
            outline: none;
        }

        .chat-input:focus {
            border-color: #FF6B35;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }

        .unread-badge {
            background: #FF6B35;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 600;
        }

        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.8);
            z-index: 50;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .modal-overlay.active {
            display: flex;
        }

        .image-modal {
            max-width: 90vw;
            max-height: 90vh;
            border-radius: 8px;
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
    <div class="chat-container lg:pt-14">
        <!-- Sidebar de Conversas -->
        <div class="conversations-sidebar">
            <div class="conversations-header">
                <h2 class="text-xl font-bold text-[#1b1b18]">Conversas</h2>
                <a href="{{ route('chat.new') }}" class="text-[#FF6B35] hover:text-[#E55A2B]">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </a>
            </div>

            <div style="padding: 0 20px 12px 20px;">
                <input
                    type="text"
                    id="conversationSearch"
                    class="conversations-search"
                    placeholder="Pesquisar em minhas conversas"
                >
                <div class="flex items-center gap-2">
                    <label class="text-sm text-[#706f6c]">Exibir apenas usuários online</label>
                    <input type="checkbox" id="onlineOnlyToggle" class="w-4 h-4">
                </div>
            </div>

            <div class="conversations-list" id="conversationsList">
                @foreach($conversations as $conversation)
                    <div class="conversation-item" data-conversation-id="{{ $conversation['id'] }}" onclick="openConversation({{ $conversation['id'] }})">
                        <div class="flex items-center gap-3">
                            <div class="relative">
                                @if($conversation['other_participant']['profile_photo'])
                                    @php
                                        $otherUser = \App\Models\User::find($conversation['other_participant']['id']);
                                    @endphp
                                    <img src="{{ $otherUser->profile_photo_url }}"
                                         alt="{{ $conversation['other_participant']['name'] }}"
                                         class="w-12 h-12 rounded-full object-cover">
                                @else
                                    <div class="w-12 h-12 rounded-full bg-gray-300 flex items-center justify-center">
                                        <span class="text-gray-600 font-semibold">
                                            {{ strtoupper(substr($conversation['other_participant']['name'], 0, 2)) }}
                                        </span>
                                    </div>
                                @endif
                                @if($conversation['unread_count'] > 0)
                                    <span class="unread-badge absolute -top-1 -right-1">
                                        {{ $conversation['unread_count'] }}
                                    </span>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <h3 class="font-semibold text-[#1b1b18] truncate">
                                        {{ strtolower($conversation['other_participant']['name']) }}
                                    </h3>
                                    @if($conversation['other_participant']['creator_status'] === 'approved')
                                        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                    @endif
                                </div>
                                @if($conversation['last_message'])
                                    <p class="text-sm text-[#706f6c] truncate">
                                        @if($conversation['last_message']['message_type'] === 'image')
                                            [Imagem]
                                        @else
                                            {{ $conversation['last_message']['content'] }}
                                        @endif
                                    </p>
                                    <p class="text-xs text-[#706f6c] mt-1">
                                        {{ $conversation['last_message']['created_at']->format('H:i') }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Área Principal do Chat -->
        <div class="chat-main" id="chatMain">
            <div class="chat-empty">
                Selecione uma conversa para começar
            </div>
        </div>
    </div>

    <!-- Modal para visualizar imagem ampliada -->
    <div id="imageModal" class="modal-overlay" onclick="closeImageModal()">
        <img id="modalImage" class="image-modal" src="" alt="Imagem ampliada" onclick="event.stopPropagation()">
    </div>

    <!-- Profile Overlay -->
    @auth
        <x-profile-overlay />
    @endauth

    <script>
        let currentConversationId = null;
        let pollingInterval = null;
        let lastMessageId = 0;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        function openConversation(conversationId) {
            currentConversationId = conversationId;
            lastMessageId = 0;

            // Atualiza UI
            document.querySelectorAll('.conversation-item').forEach(item => {
                item.classList.remove('active');
            });
            document.querySelector(`[data-conversation-id="${conversationId}"]`)?.classList.add('active');

            // Carrega a conversa
            window.location.href = `/chat/${conversationId}`;
        }

        function closeImageModal() {
            document.getElementById('imageModal').classList.remove('active');
        }

        function openImageModal(imageSrc) {
            document.getElementById('modalImage').src = imageSrc;
            document.getElementById('imageModal').classList.add('active');
        }

        // Fechar modal com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeImageModal();
            }
        });
    </script>
</body>
</html>

