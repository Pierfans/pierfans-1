<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Buscar Criadores - {{ config('app.name', 'Laravel') }}</title>

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
        body {
            background-color: #FDFDFC;
        }
    </style>
</head>
<body class="bg-[#FDFDFC] text-[#1b1b18] min-h-screen">
    <!-- Top Navigation (Desktop) -->
    <x-topnav />

    <!-- Bottom Navigation (Mobile) -->
    <x-bottomnav />

    <!-- Profile Overlay (apenas se autenticado) -->
    @auth
        <x-profile-overlay />
    @endauth

    <!-- Main Content -->
    <div class="pt-0 md:pt-16 pb-16 md:pb-0">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Barra de Pesquisa -->
            <div class="max-w-2xl mx-auto mb-6">
                <form method="GET" action="{{ route('creator.search') }}" class="relative">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ request('search') }}"
                            placeholder="Pesquisar" 
                            class="block w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg bg-[#F5F5F3] focus:ring-2 focus:ring-pink-500 focus:border-pink-500 text-gray-900 placeholder-gray-500"
                            autocomplete="off"
                        >
                    </div>
                </form>
            </div>


            <!-- Grid de Criadores -->
            <div class="max-w-4xl mx-auto" id="creators-grid">
                @include('creator-search.partials.creators-grid', ['creators' => $creators])
            </div>
        </div>
    </div>

    <script>
        // Busca via AJAX (sem recarregar a página)
        let searchTimeout;
        const $searchInput = $('input[name="search"]');
        const $creatorsGrid = $('#creators-grid');
        
        $searchInput.on('input', function() {
            clearTimeout(searchTimeout);
            const searchValue = $(this).val().trim();
            
            searchTimeout = setTimeout(function() {
                performSearch(searchValue);
            }, 500);
        });

        // Previne submit do formulário
        $('form').on('submit', function(e) {
            e.preventDefault();
            const searchValue = $searchInput.val().trim();
            performSearch(searchValue);
        });

        function performSearch(searchValue) {
            // Mostra loading
            $creatorsGrid.html('<div class="text-center py-12"><div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-pink-500"></div></div>');
            
            // Atualiza URL sem recarregar
            const url = new URL(window.location.href);
            if (searchValue === '') {
                url.searchParams.delete('search');
            } else {
                url.searchParams.set('search', searchValue);
            }
            window.history.pushState({}, '', url.toString());
            
            // Faz requisição AJAX
            $.ajax({
                url: '{{ route("creator.search") }}',
                method: 'GET',
                data: {
                    search: searchValue
                },
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    $creatorsGrid.html(response);
                },
                error: function(xhr) {
                    $creatorsGrid.html('<div class="text-center py-12"><p class="text-red-500">Erro ao buscar criadores. Tente novamente.</p></div>');
                }
            });
        }

        // Suporte para navegação do browser (voltar/avançar)
        window.addEventListener('popstate', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const searchValue = urlParams.get('search') || '';
            $searchInput.val(searchValue);
            performSearch(searchValue);
        });
    </script>
</body>
</html>

