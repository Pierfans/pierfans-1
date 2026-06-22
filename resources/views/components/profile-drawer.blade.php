<!-- Drawer Overlay -->
<div id="profileDrawerOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden transition-opacity duration-300" onclick="closeProfileDrawer()" style="cursor: pointer;"></div>

<!-- Drawer -->
<div id="profileDrawer" class="fixed bottom-0 left-0 right-0 bg-white rounded-t-3xl z-50 transform translate-y-full transition-transform duration-300 ease-out max-h-[90vh] overflow-y-auto">
    <!-- Drag Handle -->
    <div class="flex justify-center pt-3 pb-2">
        <div class="w-12 h-1 bg-gray-300 rounded-full"></div>
    </div>

    <!-- Menu Items -->
    <div class="px-4 pb-6">
        <div class="space-y-1">
            <a href="#" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                <svg class="w-6 h-6 mr-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span class="font-medium">Meu Perfil</span>
            </a>

            @if (Auth::user() && Auth::user()->creator_status !== 'approved')
            <a href="{{ route('creator.index') }}" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                <svg class="w-6 h-6 mr-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                </svg>
                <span class="font-medium">Tornar-se Criador</span>
            </a>
            @endif

            <a href="#" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                <svg class="w-6 h-6 mr-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
                <span class="font-medium">Painel do Afiliado</span>
            </a>

            @auth
                @if(Auth::user()->is_admin)
                <a href="{{ route('admin.dashboard') }}" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                    <svg class="w-6 h-6 mr-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <span class="font-medium">Administração</span>
                </a>
                @endif
            @endauth

            <a href="#" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                <svg class="w-6 h-6 mr-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                <span class="font-medium">Notificações</span>
            </a>

            <a href="#" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                <svg class="w-6 h-6 mr-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
                <span class="font-medium">Minha Carteira</span>
            </a>

            <a href="#" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                <svg class="w-6 h-6 mr-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span class="font-medium">Configurações</span>
            </a>
        </div>

        <!-- Separator -->
        <div class="border-t border-gray-200 my-2"></div>

        <!-- Logout -->
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex items-center w-full px-4 py-3 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span class="font-medium">Sair</span>
            </button>
        </form>
    </div>
</div>


