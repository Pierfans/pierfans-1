<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 - Página não encontrada - {{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- TailwindCSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- jQuery via CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Estilos e scripts customizados -->
    <link rel="stylesheet" href="/css/app.css">
    <script src="/js/app.js"></script>
</head>

<body
    class="bg-[#FDFDFC] text-[#1b1b18] min-h-screen flex items-center justify-center">
    <div class="max-w-2xl mx-auto px-6 text-center">
        <!-- Logo -->
        <div class="mb-8">
            <a href="{{ route('dashboard') }}" class="inline-block">
                <img class="w-40" src="/img/logo.svg" />
            </a>
        </div>

        <!-- Conteúdo 404 -->
        <div class="bg-white rounded-lg shadow-sm p-8 md:p-12">
            <div class="mb-6">
                <h1 class="text-6xl md:text-8xl font-bold text-gray-900 mb-4">404</h1>
                <h2 class="text-2xl md:text-3xl font-semibold text-gray-800 mb-4">
                    Página não encontrada
                </h2>
                <p class="text-gray-600 text-lg mb-8">
                    A página que você está procurando não existe ou foi movida.
                </p>
            </div>

            <!-- Botões de ação -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('dashboard') }}"
                    class="inline-block px-6 py-3 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors font-medium">
                    Voltar ao Pierfans
                </a>
                <a href="{{ route('creator.search') }}"
                    class="inline-block px-6 py-3 border border-[#19140035] text-[#1b1b18] rounded-lg hover:border-[#1915014a] transition-colors font-medium">
                    Buscar Criadores
                </a>
            </div>
        </div>

        <!-- Informações adicionais -->
        <div class="mt-8 text-sm text-gray-500">
            <p>Se você acredita que isso é um erro, entre em contato com o suporte.</p>
        </div>
    </div>
</body>

</html>

