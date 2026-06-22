<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Criar Nova Postagem - {{ config('app.name', 'Laravel') }}</title>

    <!-- TailwindCSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- jQuery via CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- heic2any - Conversão de HEIC para visualização -->
    <script src="https://cdn.jsdelivr.net/npm/heic2any@0.0.4/dist/heic2any.min.js"></script>

    <!-- Resumable.js - Upload por chunks -->
    <script src="https://cdn.jsdelivr.net/npm/resumablejs@1.1.0/resumable.min.js"></script>

    <!-- Estilos e scripts customizados -->
    <link rel="stylesheet" href="/css/app.css">
    <script src="/js/app.js"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Top Navigation (Desktop) -->
    <x-topnav />

    <!-- Bottom Navigation (Mobile) -->
    <x-bottomnav />

    <!-- Main Content -->
    <div class="pt-16 md:pt-16 pb-16 md:pb-0">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white rounded-lg shadow-sm p-6 md:p-8">
                <h1 class="text-2xl font-bold text-gray-900 mb-6">Criar Nova Postagem</h1>

                <form id="createPostForm" class="space-y-6">
                    @csrf

                    <!-- Descrição -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Descrição
                        </label>
                        <textarea
                            id="description"
                            name="description"
                            rows="6"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent resize-y"
                            placeholder="Descreva sua postagem..."
                        ></textarea>
                        <div id="description-error" class="text-red-500 text-sm mt-1 hidden"></div>
                    </div>

                    <!-- Visibilidade -->
                    <div>
                        <label for="visibility" class="block text-sm font-medium text-gray-700 mb-2">
                            Visibilidade
                        </label>
                        <select
                            id="visibility"
                            name="visibility"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                        >
                            <option value="free">Gratuito</option>
                            <option value="subscriber">Somente Assinantes</option>
                            <option value="paid">Conteúdo Único (pago)</option>
                        </select>
                        <div id="visibility-error" class="text-red-500 text-sm mt-1 hidden"></div>

                        <!-- Campo de preço — visível apenas quando "Conteúdo Único" é selecionado -->
                        <div id="price-field" class="mt-4 hidden">
                            <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
                                Preço do conteúdo (R$)
                            </label>
                            <input
                                type="text"
                                id="price"
                                name="price"
                                inputmode="decimal"
                                placeholder="Ex: 29.90"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                            >
                            <div id="price-error" class="text-red-500 text-sm mt-1 hidden"></div>
                        </div>
                    </div>

                    <!-- Mídias -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Mídias (Imagens ou Vídeos)
                        </label>
                        <div
                            id="dropZone"
                            class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-pink-400 transition-colors cursor-pointer"
                            onclick="document.getElementById('mediaFiles').click()"
                        >
                            <input
                                type="file"
                                id="mediaFiles"
                                name="media[]"
                                multiple
                                accept="image/jpeg,image/jpg,image/png,image/webp,video/mp4,video/webm,.jpg,.jpeg,.png,.webp,.mp4,.mov,.webm"
                                class="hidden"
                            >
                            <div id="dropZoneContent">
                                <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <p class="text-gray-600 mb-2">
                                    <span class="font-medium">Escolher arquivos</span>
                                    <span id="fileCount" class="text-gray-400"> Nenhum arquivo escolhido</span>
                                </p>
                                <p class="text-sm text-gray-500">Clique para selecionar ou arraste arquivos aqui</p>
                                <p class="text-xs text-gray-400 mt-2">Você pode adicionar arquivos um por um ou vários de uma vez</p>
                            </div>
                        </div>

                        <!-- Preview das mídias selecionadas -->
                        <div id="mediaPreview" class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4"></div>
                        <div id="uploadStatus" class="hidden mt-3 p-3 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg text-sm"></div>
                        <div id="media-error" class="text-red-500 text-sm mt-1 hidden"></div>
                    </div>

                    <!-- Botões -->
                    <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                        <a href="{{ route('dashboard') }}"
                           class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            Cancelar
                        </a>
                        <button
                            type="submit"
                            id="submitBtn"
                            class="px-6 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Selecione um arquivo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('posts.partials.no-plans-modal')

    <!-- Modal de Arquivo Duplicado -->
    <div id="duplicateModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Arquivo Duplicado</h3>
                <button onclick="closeDuplicateModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="mb-4">
                <p class="text-gray-700" id="duplicateMessage"></p>
            </div>
            <div class="flex justify-end">
                <button onclick="closeDuplicateModal()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                    Entendi
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Sucesso -->
    <div id="successModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 animate-fade-in">
            <div class="text-center">
                <!-- Ícone de Sucesso -->
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                    <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                
                <h3 class="text-xl font-bold text-gray-900 mb-2">Postagem Criada!</h3>
                <p class="text-gray-600 mb-6">Sua postagem foi publicada com sucesso e já está disponível no seu perfil.</p>
                
                <button 
                    id="successModalBtn"
                    class="w-full px-6 py-3 bg-orange-500 text-white font-semibold rounded-lg hover:bg-orange-600 transition-colors focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2"
                >
                    Ver Meu Perfil
                </button>
            </div>
        </div>
    </div>

    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        .animate-fade-in {
            animation: fadeIn 0.3s ease-out;
        }
    </style>

    <script>
        window.USE_R2_UPLOAD = {{ $useR2Upload ? 'true' : 'false' }};
        {{-- true = pelo menos um plano ativo (permite "Somente assinantes") --}}
        window.HAS_SUBSCRIPTION_PLANS = {{ $hasSubscriptionPlans ? 'true' : 'false' }};
    </script>
    <script src="/js/post-subscriber-visibility-guard.js"></script>
    <script src="/js/post-create.js"></script>
    <script>
        document.getElementById('visibility').addEventListener('change', function () {
            const priceField = document.getElementById('price-field');
            const priceInput = document.getElementById('price');

            if (this.value === 'paid') {
                priceField.classList.remove('hidden');
                priceInput.required = true;
            } else {
                priceField.classList.add('hidden');
                priceInput.required = false;
                priceInput.value = '';
            }
        });
    </script>
</body>
</html>

