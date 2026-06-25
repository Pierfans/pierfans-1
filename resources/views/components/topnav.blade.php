@php
    $isHome    = request()->routeIs('dashboard');
    $isSearch  = request()->routeIs('creator.search');
    $isCreate  = request()->routeIs('posts.create');
    $isChat    = request()->routeIs('chat.index');
@endphp

<nav class="fixed top-0 left-0 right-0 z-50 bg-white border-b border-gray-200 hidden md:block">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">

            {{-- Logo --}}
            <div class="flex-shrink-0">
                <a href="{{ route('dashboard') }}" class="flex items-center">
                    <img class="w-20" src="/img/logo.svg" />
                </a>
            </div>

            {{-- Itens de navegação --}}
            <div class="flex items-center gap-1">

                @auth
                    {{-- Home --}}
                    <a href="{{ route('dashboard') }}"
                       class="flex items-center gap-2 px-3 py-2 rounded-lg transition-colors {{ $isHome ? 'bg-pink-50 text-pink-500' : 'text-gray-600 hover:text-pink-500 hover:bg-gray-50' }}"
                       title="Home">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span class="hidden lg:inline text-sm font-medium">Home</span>
                    </a>
                @endauth

                {{-- Buscar Criadores --}}
                <a href="{{ route('creator.search') }}"
                   class="flex items-center gap-2 px-3 py-2 rounded-lg transition-colors {{ $isSearch ? 'bg-pink-50 text-pink-500' : 'text-gray-600 hover:text-pink-500 hover:bg-gray-50' }}"
                   title="Buscar Criadores">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <span class="hidden lg:inline text-sm font-medium">Buscar Criadores</span>
                </a>

                @auth
                    {{-- Criar Conteúdo — só para creators aprovados --}}
                    @if(Auth::user()->creator_status === 'approved')
                        <a href="{{ route('posts.create') }}"
                           class="flex items-center gap-2 px-3 py-2 rounded-lg transition-colors {{ $isCreate ? 'bg-pink-50 text-pink-500' : 'text-gray-600 hover:text-pink-500 hover:bg-gray-50' }}"
                           title="Criar Conteúdo">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span class="hidden lg:inline text-sm font-medium">Criar Conteúdo</span>
                        </a>
                    @endif

                    {{-- Chat --}}
                    <a href="{{ route('chat.index') }}"
                       class="flex items-center gap-2 px-3 py-2 rounded-lg transition-colors {{ $isChat ? 'bg-pink-50 text-pink-500' : 'text-gray-600 hover:text-pink-500 hover:bg-gray-50' }}"
                       title="Chat">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        <span class="hidden lg:inline text-sm font-medium">Chat</span>
                    </a>

                    {{-- Avatar --}}
                    <button onclick="openProfileOverlay()"
                            class="ml-2 flex-shrink-0 focus:outline-none"
                            title="Perfil">
                        @if(Auth::user()->profile_photo_url)
                            <img
                                src="{{ Auth::user()->profile_photo_url }}"
                                alt="{{ Auth::user()->name }}"
                                class="w-8 h-8 rounded-full object-cover ring-2 ring-transparent hover:ring-pink-300 transition-all"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                            />
                            <span
                                style="display:none;"
                                class="w-8 h-8 rounded-full bg-pink-500 text-white text-sm font-bold items-center justify-center hover:ring-2 hover:ring-pink-300 transition-all">
                                {{ strtoupper(substr(Auth::user()->name ?? '?', 0, 1)) }}
                            </span>
                        @else
                            <span class="w-8 h-8 rounded-full bg-pink-500 text-white text-sm font-bold flex items-center justify-center hover:ring-2 hover:ring-pink-300 transition-all">
                                {{ strtoupper(substr(Auth::user()->name ?? '?', 0, 1)) }}
                            </span>
                        @endif
                    </button>

                @else
                    {{-- Visitante: botão de perfil genérico --}}
                    <button onclick="openProfileOverlay()"
                            class="p-2 text-gray-600 hover:text-pink-500 transition-colors rounded-lg hover:bg-gray-50"
                            title="Perfil">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </button>
                @endauth

            </div>
        </div>
    </div>
</nav>
