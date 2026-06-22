<nav class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-200 md:hidden">
    <div class="flex items-center justify-around h-16">
        @auth
            <a href="{{ route('dashboard') }}"
               class="flex flex-col items-center justify-center flex-1 h-full text-gray-700 hover:text-pink-500 transition-colors"
               title="Home">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
            </a>
        @endauth
        <a href="{{ route('creator.search') }}"
           class="flex flex-col items-center justify-center flex-1 h-full text-gray-700 hover:text-pink-500 transition-colors"
           title="Buscar">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </a>
        @auth
            @if(Auth::user()->creator_status === 'approved')
                <a href="{{ route('posts.create') }}"
                   class="flex flex-col items-center justify-center flex-1 h-full text-gray-700 hover:text-pink-500 transition-colors"
                   title="Criar">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </a>
            @endif
            <a href="{{ route('chat.index') }}"
               class="flex flex-col items-center justify-center flex-1 h-full text-gray-700 hover:text-pink-500 transition-colors relative"
               title="Mensagens">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
            </a>
        @endauth
        <button onclick="openProfileOverlay()"
           class="flex flex-col items-center justify-center flex-1 h-full text-gray-700 hover:text-pink-500 transition-colors relative"
           title="Perfil">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
        </button>
    </div>
</nav>

<x-whatsapp-float />
