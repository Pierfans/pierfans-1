<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Chat - {{ $otherParticipant->name }} - {{ config('app.name', 'Laravel') }}</title>

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
            object-fit: cover;
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
            max-height: 400px;
            border-radius: 8px;
            cursor: pointer;
            display: block;
        }

        .message-audio {
            display: block;
            width: 240px;
            max-width: 100%;
            margin-top: 6px;
        }

        .paid-lock {
            margin-top: 6px;
            padding: 16px;
            border: 1px dashed rgba(0, 0, 0, 0.18);
            border-radius: 10px;
            background: rgba(0, 0, 0, 0.03);
            text-align: center;
        }

        .message.own .paid-lock {
            border-color: rgba(255, 255, 255, 0.45);
            background: rgba(255, 255, 255, 0.12);
        }

        .paid-lock-icon {
            font-size: 22px;
            line-height: 1;
        }

        .paid-lock-label {
            font-size: 13px;
            font-weight: 600;
            margin: 6px 0 10px;
        }

        .paid-lock-button {
            width: 100%;
            padding: 8px 12px;
            border: 0;
            border-radius: 8px;
            background: #FF6B35;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
        }

        .message.own .paid-lock-button {
            background: #fff;
            color: #FF6B35;
        }

        .paid-lock-button[disabled] {
            opacity: .6;
            cursor: default;
        }

        .paid-lock-erro {
            margin-top: 8px;
            font-size: 12px;
            color: #b91c1c;
        }

        .price-field {
            width: 92px;
            padding: 10px;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            font-size: 14px;
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

        .conversation-started {
            text-align: center;
            color: #706f6c;
            font-size: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #E2E8F0;
            margin-bottom: 16px;
        }
    </style>
    @include('partials.tracking')
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
                <!-- Será carregado via AJAX ou pode ser incluído aqui -->
            </div>
        </div>

        <!-- Área Principal do Chat -->
        <div class="chat-main">
            <!-- Header do Chat -->
            <div class="chat-header">
                <a href="{{ route('chat.index') }}" class="text-[#706f6c] hover:text-[#1b1b18]">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div class="relative">
                    @if($otherParticipant->profile_photo)
                        <img src="{{ $otherParticipant->profile_photo_url }}"
                             alt="{{ $otherParticipant->name }}"
                             class="w-10 h-10 rounded-full object-cover">
                    @else
                        <div class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center">
                            <span class="text-gray-600 font-semibold text-sm">
                                {{ strtoupper(substr($otherParticipant->name, 0, 2)) }}
                            </span>
                        </div>
                    @endif
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <h3 class="font-semibold text-[#1b1b18]">
                            {{ strtolower($otherParticipant->name) }}
                        </h3>
                        @if($otherParticipant->creator_status === 'approved')
                            <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        @endif
                    </div>
                    <p class="text-sm text-green-600">• Está online</p>
                </div>
                @if($podePedirChamada)
                    <button type="button" onclick="abrirChamadaModal()"
                            class="px-3 py-2 rounded-full text-sm font-semibold text-white bg-pink-500 hover:bg-pink-600 transition-colors"
                            title="Chamada de vídeo de {{ $criadoraDaConversa->video_call_minutes }} min">
                        📹 R$ {{ number_format($criadoraDaConversa->video_call_price, 2, ',', '.') }}
                    </button>
                @endif
                <button class="text-[#706f6c] hover:text-[#1b1b18]">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                    </svg>
                </button>
            </div>

            <!-- Área de Mensagens -->
            <div class="chat-messages" id="chatMessages">
                <div class="conversation-started">
                    Conversa iniciada em: {{ $conversation->created_at->format('d/m/Y') }}
                </div>

                @foreach($messages as $message)
                    <div class="message {{ $message->user_id === Auth::id() ? 'own' : '' }}" data-message-id="{{ $message->id }}">
                        @if($message->user_id !== Auth::id())
                            @if($message->user->profile_photo)
                                <img src="{{ $message->user->profile_photo_url }}"
                                     alt="{{ $message->user->name }}"
                                     class="message-avatar">
                            @else
                                <div class="message-avatar bg-gray-300 flex items-center justify-center">
                                    <span class="text-gray-600 font-semibold text-xs">
                                        {{ strtoupper(substr($message->user->name, 0, 2)) }}
                                    </span>
                                </div>
                            @endif
                        @endif
                        <div class="message-content">
                            @if($message->message_type === 'video_call' && $message->videoCall)
                                {{-- O JS desenha a partir do payload, o mesmo do polling: um renderizador só --}}
                                <div class="chamada-card" data-chamada-id="{{ $message->videoCall->id }}" data-message-id="{{ $message->id }}"
                                     data-payload="{{ json_encode($message->toChatPayload(Auth::user())) }}"></div>
                            @elseif($message->content)
                                <p>{{ $message->content }}</p>
                            @endif

                            @if($message->isPaid() && ! $message->isUnlockedFor(Auth::user()))
                                <div class="paid-lock" id="lock-{{ $message->id }}">
                                    <div class="paid-lock-icon">&#128274;</div>
                                    <div class="paid-lock-label">
                                        {{ $message->message_type === 'audio' ? 'Áudio' : 'Foto' }}
                                        de R$ {{ number_format($message->price, 2, ',', '.') }}
                                    </div>
                                    <button type="button" class="paid-lock-button" onclick="desbloquear({{ $message->id }}, this)">
                                        Desbloquear
                                    </button>
                                    <div class="paid-lock-erro" style="display:none"></div>
                                </div>
                            @elseif($message->message_type === 'audio' && $message->file_path)
                                <audio class="message-audio" controls preload="none" src="{{ route('chat.media', $message->id) }}"></audio>
                            @elseif($message->message_type === 'image' && $message->file_path)
                                <img src="{{ route('chat.media', $message->id) }}"
                                     alt="Imagem"
                                     class="message-image"
                                     onclick="openImageModal('{{ route('chat.media', $message->id) }}')">
                            @endif

                            @if($message->isPaid() && $message->user_id === Auth::id())
                                <p class="message-time">
                                    Cobrado R$ {{ number_format($message->price, 2, ',', '.') }}
                                    &middot; {{ $message->purchases()->count() > 0 ? 'liberado' : 'aguardando pagamento' }}
                                </p>
                            @endif

                            <p class="message-time">{{ $message->created_at->format('H:i') }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Área de Input -->
            <div class="chat-input-area">
                <input type="file" id="imageInput" accept="image/*" style="display: none;" onchange="handleImageSelect(event)">
                <input type="file" id="audioInput" accept="audio/*" style="display: none;" onchange="handleAudioSelect(event)">

                <button type="button" onclick="document.getElementById('audioInput').click()"
                        title="Enviar áudio"
                        class="px-3 py-3 text-xl leading-none">&#127908;</button>

                @if($conversation->creator_id === Auth::id())
                    <input type="number" id="priceInput" class="price-field" min="1" step="0.01"
                           placeholder="R$" title="Preço para desbloquear. Deixe vazio para enviar de graça.">
                @endif
                <input
                    type="text"
                    id="messageInput"
                    class="chat-input"
                    placeholder="Digite uma mensagem"
                    onkeypress="handleKeyPress(event)"
                >
                <button
                    id="sendButton"
                    onclick="sendMessage()"
                    class="px-6 py-3 bg-[#FF6B35] text-white font-semibold rounded-lg hover:bg-[#E55A2B] transition-colors"
                >
                    ENVIAR
                </button>
            </div>
        </div>
    </div>

    <!-- Modal para visualizar imagem ampliada -->
    @if($podePedirChamada)
    {{-- Chamada de vídeo (spec 24/09): o fã pede e paga com saldo; o valor fica reservado --}}
    <div id="chamadaModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:60;align-items:center;justify-content:center" onclick="if(event.target===this)fecharChamadaModal()">
        <div style="background:#fff;border-radius:16px;padding:24px;max-width:380px;width:92%">
            <h3 class="font-semibold text-lg mb-2">Chamada de vídeo com {{ strtolower($otherParticipant->name) }}</h3>
            <p class="text-sm text-gray-600 mb-3">
                {{ $criadoraDaConversa->video_call_minutes }} minutos por
                <strong>R$ {{ number_format($criadoraDaConversa->video_call_price, 2, ',', '.') }}</strong>, pagos com o seu saldo.
            </p>
            <label class="text-sm text-gray-700">Sugerir um horário (opcional)</label>
            <input type="datetime-local" id="chamadaSugestao" class="w-full border rounded-lg px-3 py-2 mb-3">
            <p class="text-xs text-gray-500 mb-4">
                O valor fica reservado e só vai pra ela quando a chamada acontecer. Se ela recusar ou não
                aparecer, volta pra sua carteira.
            </p>
            <div class="flex gap-2">
                <button type="button" onclick="fecharChamadaModal()" class="flex-1 px-4 py-2 rounded-lg border">Cancelar</button>
                <button type="button" id="chamadaPagar" onclick="pedirChamada()" class="flex-1 px-4 py-2 rounded-lg bg-pink-500 text-white font-semibold">Pagar com saldo</button>
            </div>
            <p id="chamadaErro" class="text-sm text-red-600 mt-2" style="display:none"></p>
        </div>
    </div>
    @endif

    <div id="imageModal" class="modal-overlay" onclick="closeImageModal()">
        <img id="modalImage" class="image-modal" src="" alt="Imagem ampliada" onclick="event.stopPropagation()">
    </div>

    <!-- Profile Overlay -->
    @auth
        <x-profile-overlay />
    @endauth

    <script>
        const conversationId = {{ $conversation->id }};
        const currentUserId = {{ Auth::id() }};
        let lastMessageId = {{ $messages->count() > 0 ? $messages->last()->id : 0 }};
        let pollingInterval = null;
        let isUserTyping = false;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const messagesContainer = document.getElementById('chatMessages');
        const messageInput = document.getElementById('messageInput');

        // Função auxiliar para gerar URL de foto de perfil
        function getProfilePhotoUrl(profilePhoto) {
            if (!profilePhoto) return '';

            // Sempre usa a API externa
            // O profilePhoto deve conter o new_name retornado pela API
            const apiUrl = '{{ config('services.media_api.url') }}';
            const imageEndpoint = '{{ config('services.media_api.image_endpoint') }}';
            return `${apiUrl}${imageEndpoint}/${profilePhoto}`;
        }

        // Inicia polling para buscar novas mensagens
        function startPolling() {
            // Polling a cada 4 segundos
            pollingInterval = setInterval(function() {
                // Só busca novas mensagens se o usuário não estiver digitando
                if (!isUserTyping && document.activeElement !== messageInput) {
                    fetchNewMessages();
                }
            }, 4000);
        }

        // Busca novas mensagens via AJAX
        function fetchNewMessages() {
            fetch(`{{ route('chat.get-messages', $conversation->id) }}?last_message_id=${lastMessageId}`, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.messages && data.messages.length > 0) {
                    // Adiciona apenas as novas mensagens
                    data.messages.forEach(message => {
                        addMessageToDOM(message);
                        lastMessageId = Math.max(lastMessageId, message.id);
                    });

                    // Scroll para a última mensagem
                    scrollToBottom();
                }
            })
            .catch(error => {
                console.error('Erro ao buscar mensagens:', error);
            });
        }

        // ---- Chamada de vídeo (spec 24/09) ----
        function chamadaCardHtml(message) {
            const c = message.video_call;
            const atual = c.last_message_id === message.id;
            let html = `<p>${escapeHtml(message.content || '')}</p>`;
            html += `<p class="message-time">${escapeHtml(c.status_label)}</p>`;
            if (!atual) return html;

            let acoes = '';
            if (c.pode_marcar) {
                const valor = c.scheduled_local || c.suggested_local || '';
                acoes += `<input type="datetime-local" id="chamadaHorario-${c.id}" value="${valor}" class="border rounded-lg px-2 py-1 text-sm w-full mb-2">
                    <button type="button" class="paid-lock-button" onclick="marcarChamada(${c.id}, this)">${c.status === 'scheduled' ? 'Remarcar' : 'Marcar'}</button>
                    <button type="button" class="paid-lock-button" style="background:#e5e7eb;color:#111" onclick="recusarChamada(${c.id}, this)">Recusar</button>`;
            }
            if (c.pode_entrar) {
                // Primeira entrega: sem sala ainda. A segunda troca este botão pelo link da chamada.
                acoes += `<button type="button" class="paid-lock-button" disabled title="Em breve">Entrar na chamada (em breve)</button>`;
            }
            if (acoes) html += `<div class="chamada-acoes mt-2">${acoes}<div class="paid-lock-erro" style="display:none"></div></div>`;
            return html;
        }

        function chamadaPost(url, body, botao, erroEl) {
            if (botao) botao.disabled = true;
            if (erroEl) erroEl.style.display = 'none';
            return fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify(body),
            })
            .then(r => r.json().then(data => ({ ok: r.ok, data })))
            .then(({ ok, data }) => {
                if (data.recarregar && data.redirect) { window.location.href = data.redirect; return null; }
                if (!ok || !data.success) throw new Error(data.message || 'Não deu certo. Tente de novo.');
                if (data.chat_message) {
                    addMessageToDOM(data.chat_message);
                    lastMessageId = Math.max(lastMessageId, data.chat_message.id);
                    scrollToBottom();
                }
                return data;
            })
            .catch(e => {
                if (erroEl) { erroEl.textContent = e.message; erroEl.style.display = 'block'; } else { alert(e.message); }
                if (botao) botao.disabled = false;
                return null;
            });
        }

        function abrirChamadaModal() { const m = document.getElementById('chamadaModal'); if (m) m.style.display = 'flex'; }
        function fecharChamadaModal() { const m = document.getElementById('chamadaModal'); if (m) m.style.display = 'none'; }

        function pedirChamada() {
            const botao = document.getElementById('chamadaPagar');
            const erro = document.getElementById('chamadaErro');
            chamadaPost(`/chat/${conversationId}/chamada`, { suggested_at: document.getElementById('chamadaSugestao').value || null }, botao, erro)
                .then(data => { if (data) { fecharChamadaModal(); const b = document.querySelector('[onclick="abrirChamadaModal()"]'); if (b) b.remove(); } });
        }

        function marcarChamada(id, botao) {
            const card = botao.closest('.chamada-card');
            chamadaPost(`/chat/chamada/${id}/marcar`, { scheduled_at: document.getElementById('chamadaHorario-' + id).value || null }, botao, card.querySelector('.paid-lock-erro'));
        }

        function recusarChamada(id, botao) {
            if (!confirm('Recusar a chamada? O valor volta pra carteira do fã agora.')) return;
            const card = botao.closest('.chamada-card');
            chamadaPost(`/chat/chamada/${id}/recusar`, {}, botao, card.querySelector('.paid-lock-erro'));
        }

        // Cards que vieram do servidor: desenha com o mesmo renderizador do polling
        document.querySelectorAll('.chamada-card[data-payload]').forEach(el => {
            const message = JSON.parse(el.getAttribute('data-payload'));
            el.innerHTML = chamadaCardHtml(message);
            el.removeAttribute('data-payload');
        });

        // Adiciona uma mensagem ao DOM
        function addMessageToDOM(message) {
            const isOwn = message.user_id === currentUserId;
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${isOwn ? 'own' : ''}`;
            messageDiv.setAttribute('data-message-id', message.id);

            let avatarHtml = '';
            if (!isOwn) {
                const profilePhoto = message.user?.profile_photo
                    ? getProfilePhotoUrl(message.user.profile_photo)
                    : '';
                const initials = message.user?.name ? message.user.name.substring(0, 2).toUpperCase() : '';

                if (profilePhoto) {
                    avatarHtml = `<img src="${profilePhoto}" alt="${message.user.name}" class="message-avatar">`;
                } else {
                    avatarHtml = `<div class="message-avatar bg-gray-300 flex items-center justify-center">
                        <span class="text-gray-600 font-semibold text-xs">${initials}</span>
                    </div>`;
                }
            }

            let contentHtml = '';

            if (message.message_type === 'video_call' && message.video_call) {
                // Card novo desta chamada: os anteriores perdem os botões (só o último age)
                document.querySelectorAll(`.chamada-card[data-chamada-id="${message.video_call.id}"] .chamada-acoes`).forEach(e => e.remove());
                contentHtml += `<div class="chamada-card" data-chamada-id="${message.video_call.id}" data-message-id="${message.id}">${chamadaCardHtml(message)}</div>`;
            } else if (message.content) {
                contentHtml += `<p>${escapeHtml(message.content)}</p>`;
            }

            if (message.is_paid && !message.unlocked) {
                const tipo = message.message_type === 'audio' ? 'Áudio' : 'Foto';
                const valor = Number(message.price || 0).toFixed(2).replace('.', ',');
                contentHtml += `
                    <div class="paid-lock" id="lock-${message.id}">
                        <div class="paid-lock-icon">&#128274;</div>
                        <div class="paid-lock-label">${tipo} de R$ ${valor}</div>
                        <button type="button" class="paid-lock-button" onclick="desbloquear(${message.id}, this)">Desbloquear</button>
                        <div class="paid-lock-erro" style="display:none"></div>
                    </div>`;
            } else if (message.message_type === 'audio' && message.media_url) {
                contentHtml += `<audio class="message-audio" controls preload="none" src="${message.media_url}"></audio>`;
            } else if (message.message_type === 'image' && message.media_url) {
                contentHtml += `<img src="${message.media_url}" alt="Imagem" class="message-image" onclick="openImageModal('${message.media_url}')">`;
            }

            if (message.is_paid && message.user_id === currentUserId) {
                const valor = Number(message.price || 0).toFixed(2).replace('.', ',');
                contentHtml += `<p class="message-time">Cobrado R$ ${valor}</p>`;
            }

            const time = new Date(message.created_at).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });

            messageDiv.innerHTML = `
                ${avatarHtml}
                <div class="message-content">
                    ${contentHtml}
                    <p class="message-time">${time}</p>
                </div>
            `;

            messagesContainer.appendChild(messageDiv);
        }

        // Escolha de áudio: reaproveita o mesmo aviso da foto.
        function handleAudioSelect(event) {
            const file = event.target.files[0];
            if (file) {
                messageInput.placeholder = 'Áudio selecionado: ' + file.name;
            }
        }

        // Desbloqueia uma mensagem paga com saldo da carteira. Sem saldo, o servidor
        // devolve pra onde recarregar levando a mensagem junto, e o pagamento abre sozinho.
        function desbloquear(messageId, botao) {
            const caixa = document.getElementById('lock-' + messageId);
            const erro = caixa ? caixa.querySelector('.paid-lock-erro') : null;

            botao.disabled = true;
            botao.textContent = 'Liberando...';
            if (erro) { erro.style.display = 'none'; }

            fetch(`/chat/message/${messageId}/unlock`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    location.reload();
                    return;
                }

                if (d.recarregar && d.redirect) {
                    window.location.href = d.redirect;
                    return;
                }

                if (erro) {
                    erro.textContent = d.message || 'Não foi possível liberar.';
                    erro.style.display = 'block';
                }
                botao.disabled = false;
                botao.textContent = 'Desbloquear';
            })
            .catch(() => {
                if (erro) {
                    erro.textContent = 'Erro de conexão. Tente de novo.';
                    erro.style.display = 'block';
                }
                botao.disabled = false;
                botao.textContent = 'Desbloquear';
            });
        }

        // Função para escapar HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Envia uma mensagem
        function sendMessage() {
            const content = messageInput.value.trim();
            const imageFile = document.getElementById('imageInput').files[0];
            const audioFile = document.getElementById('audioInput').files[0];
            const priceEl = document.getElementById('priceInput');
            const price = priceEl ? priceEl.value.trim() : '';

            if (!content && !imageFile && !audioFile) {
                return;
            }

            if (price && !imageFile && !audioFile) {
                alert('Para cobrar, escolha uma foto ou um áudio.');
                return;
            }

            const formData = new FormData();
            if (content) {
                formData.append('content', content);
            }
            if (imageFile) {
                formData.append('image', imageFile);
            }
            if (audioFile) {
                formData.append('audio', audioFile);
            }
            if (price) {
                formData.append('price', price);
            }
            // Desabilita o botão durante o envio
            const sendButton = document.getElementById('sendButton');
            sendButton.disabled = true;
            sendButton.textContent = 'Enviando...';

            fetch(`{{ route('chat.send-message', $conversation->id) }}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Limpa o input
                    messageInput.value = '';
                    document.getElementById('imageInput').value = '';
                    document.getElementById('audioInput').value = '';
                    if (document.getElementById('priceInput')) {
                        document.getElementById('priceInput').value = '';
                    }

                    // Adiciona a mensagem ao DOM imediatamente
                    addMessageToDOM(data.message);
                    lastMessageId = data.message.id;

                    // Scroll para a última mensagem
                    scrollToBottom();
                } else {
                    alert(data.message || 'Erro ao enviar mensagem.');
                }
            })
            .catch(error => {
                console.error('Erro ao enviar mensagem:', error);
                alert('Erro ao enviar mensagem. Tente novamente.');
            })
            .finally(() => {
                sendButton.disabled = false;
                sendButton.textContent = 'ENVIAR';
                messageInput.focus();
            });
        }

        // Handle Enter key
        function handleKeyPress(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            }
        }

        // Handle image selection
        function handleImageSelect(event) {
            const file = event.target.files[0];
            if (file) {
                // Envia a imagem imediatamente
                sendMessage();
            }
        }

        // Scroll para o final
        function scrollToBottom() {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        // Detecta quando o usuário está digitando
        messageInput.addEventListener('focus', function() {
            isUserTyping = true;
        });

        messageInput.addEventListener('blur', function() {
            isUserTyping = false;
        });

        // Abre modal de imagem
        function openImageModal(imageSrc) {
            document.getElementById('modalImage').src = imageSrc;
            document.getElementById('imageModal').classList.add('active');
        }

        // Fecha modal de imagem
        function closeImageModal() {
            document.getElementById('imageModal').classList.remove('active');
        }

        // Fechar modal com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeImageModal();
            }
        });

        // Inicia polling quando a página carrega
        $(document).ready(function() {
            scrollToBottom();
            startPolling();
        });

        // Para o polling quando a página é fechada
        window.addEventListener('beforeunload', function() {
            if (pollingInterval) {
                clearInterval(pollingInterval);
            }
        });
    </script>
</body>
</html>

