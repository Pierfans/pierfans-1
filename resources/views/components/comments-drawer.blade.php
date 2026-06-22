<div id="commentsDrawer" class="fixed inset-0 z-50 hidden">
    <!-- Overlay -->
    <div class="absolute inset-0 bg-black bg-opacity-50" onclick="closeCommentsDrawer()"></div>
    
    <!-- Drawer -->
    <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl shadow-2xl max-h-[80vh] flex flex-col">
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Comentários</h3>
            <button onclick="closeCommentsDrawer()" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Lista de Comentários -->
        <div id="commentsList" class="flex-1 overflow-y-auto p-4 space-y-4">
            <div class="text-center text-gray-500 py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 mx-auto"></div>
                <p class="mt-2">Carregando comentários...</p>
            </div>
        </div>

        <!-- Input de Comentário -->
        @auth
        <div class="p-4 border-t border-gray-200">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center overflow-hidden flex-shrink-0">
                    @if(Auth::user() && Auth::user()->profile_photo)
                        <img src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" class="w-full h-full object-cover">
                    @else
                        <span class="text-gray-600 font-medium text-xs">
                            {{ Auth::user() ? strtoupper(substr(Auth::user()->name, 0, 2)) : 'U' }}
                        </span>
                    @endif
                </div>
                <div class="flex-1 flex items-center space-x-2">
                    <input 
                        type="text" 
                        id="commentInput" 
                        placeholder="Adicione um comentário..." 
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-pink-500 bg-white text-gray-900"
                    >
                    <button 
                        id="sendCommentBtn"
                        class="text-pink-500 hover:text-pink-600 disabled:text-gray-400 disabled:cursor-not-allowed"
                        disabled
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        @else
        <div class="p-4 border-t border-gray-200">
            <div class="text-center py-4">
                <p class="text-gray-500 text-sm mb-3">Faça login para comentar</p>
                <a href="{{ route('login') }}" class="inline-block bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-6 rounded-lg transition-colors">
                    Entrar
                </a>
            </div>
        </div>
        @endauth
    </div>
</div>

