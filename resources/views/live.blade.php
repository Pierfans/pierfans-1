<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Ao vivo - {{ config('app.name', 'Laravel') }}</title>

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
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            {{-- Banner do topo. aspect-[12/5] e a proporcao exata da arte (720x300),
                 mesma decisao do banner do dashboard: com altura fixa o cover comia
                 o logo da peca. --}}
            <img src="/img/banner-mansao-juju-dashboard.jpg"
                alt="Mansao da Juju: 12 horas ao vivo com Juju Ferrari, JOBmodel e dez criadoras"
                class="block w-full aspect-[12/5] object-cover rounded-xl">

            @if ($embedUrl)
                {{-- aspect-video (16:9) com o iframe preenchendo: player responsivo sem
                     truque de padding-bottom, que e o que o Tailwind ja resolve. --}}
                <div class="mt-6 w-full aspect-video bg-black rounded-xl overflow-hidden">
                    <iframe src="{{ $embedUrl }}"
                        class="w-full h-full"
                        frameborder="0"
                        referrerpolicy="strict-origin-when-cross-origin"
                        allow="autoplay; fullscreen; picture-in-picture; encrypted-media"
                        allowfullscreen></iframe>
                </div>

                {{-- ponytail: nao da pra saber pelo servidor se a plataforma da live deixa
                     ser embedada (instagram responde X-Frame-Options DENY, tiktok SAMEORIGIN).
                     Em vez de detectar, esta saida deixa a pessoa entrar de qualquer jeito. --}}
                <p class="mt-3 text-center text-sm text-gray-500">
                    Não está aparecendo?
                    <a href="{{ $liveUrl }}" target="_blank" rel="noopener"
                        class="text-pink-500 hover:text-pink-600 font-medium hover:underline">
                        Abrir a live em uma nova aba →
                    </a>
                </p>
            @else
                {{-- Sem link salvo ainda. Mesma altura do player, pra pagina nao pular
                     de layout quando a live entrar no ar. --}}
                <div class="mt-6 w-full aspect-video bg-[#01313B] rounded-xl flex flex-col items-center justify-center text-center px-6">
                    <p class="text-white text-lg sm:text-2xl font-bold">A transmissão ainda não começou</p>
                    <p class="text-white/70 text-sm sm:text-base mt-2">Volte aqui no horário da live para assistir.</p>
                    <a href="/jujuferrari"
                        class="mt-5 inline-flex items-center gap-1.5 bg-[#14d1bc] hover:bg-[#0e9486] text-[#01323a] hover:text-white font-semibold text-sm px-4 py-2.5 rounded-lg transition-colors">
                        Ver o perfil da Juju →
                    </a>
                </div>
            @endif

        </div>
    </div>
</body>
</html>
