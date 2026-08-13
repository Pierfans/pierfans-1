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

            @if ($streamUrl)
                {{-- Player da casa. O .m3u8 nao e video: e uma lista com o endereco dos
                     ultimos pedacos de 2s da transmissao, reescrita o tempo todo. Chrome
                     nao le isso sozinho (so Safari/iOS), quem le e o hls.js. --}}
                <div id="live-player" class="mt-6 relative w-full aspect-video bg-black rounded-xl overflow-hidden">
                    <video id="live-video" class="w-full h-full object-contain" playsinline muted autoplay></video>

                    {{-- O navegador PROIBE video comecar com som sem alguem clicar antes.
                         Nao tem como burlar, entao em vez do iconezinho de mudo escondido
                         no canto, o botao e a primeira coisa que a pessoa ve. --}}
                    <button id="live-som" type="button"
                        class="absolute inset-0 m-auto w-56 h-14 bg-[#14d1bc] hover:bg-[#0e9486] text-[#01323a] hover:text-white font-bold rounded-lg shadow-lg transition-colors">
                        🔊 Ativar som
                    </button>
                </div>

            @elseif ($embedUrl)
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
            @else
                {{-- Banner so no estado de espera. Com a live no ar ele contradiz o proprio
                     player logo abaixo ("vem ai" x "AO VIVO"), e a arte do stream ja traz
                     MANSAO DA JUJU e a marca. aspect-[12/5] e a proporcao exata do jpg
                     (720x300): com altura fixa o cover comia o logo. --}}
                <img src="/img/banner-mansao-juju-dashboard.jpg"
                    alt="Mansao da Juju: 12 horas ao vivo com Juju Ferrari, JOBmodel e dez criadoras"
                    class="block w-full aspect-[12/5] object-cover rounded-xl">

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

            {{-- ponytail: nao da pra saber pelo servidor se o player vai abrir de verdade
                 (iframe barrado por X-Frame-Options, token do .m3u8 vencido, codec).
                 Em vez de detectar cada caso, esta saida deixa a pessoa entrar de qualquer
                 jeito. Aponta pro live_url, NUNCA pro .m3u8 - o navegador baixa aquilo
                 como arquivo em vez de tocar. --}}
            @if ($liveUrl && ($streamUrl || $embedUrl))
                <p class="mt-3 text-center text-sm text-gray-500">
                    Não está aparecendo?
                    <a href="{{ $liveUrl }}" target="_blank" rel="noopener"
                        class="text-pink-500 hover:text-pink-600 font-medium hover:underline">
                        Abrir a live em uma nova aba →
                    </a>
                </p>
            @endif

        </div>
    </div>

    @if ($streamUrl)
        <script src="https://cdn.jsdelivr.net/npm/hls.js@1/dist/hls.min.js"></script>
        <script>
            (function () {
                var src   = @json($streamUrl);
                var fonte = @json($embedUrl);
                var video = document.getElementById('live-video');
                var botao = document.getElementById('live-som');

                botao.addEventListener('click', function () {
                    video.muted = false;
                    video.volume = 1;
                    video.play();
                    botao.remove();
                });

                // O token do .m3u8 tem validade e a transmissao e longa. Se ele vencer no
                // meio, o player da casa morre - e ai a pagina cai pro iframe do fornecedor,
                // que renova o token sozinho. Perde o botao de som, mas a live continua no ar.
                function cairPraFonte() {
                    if (!fonte) return;
                    var quadro = document.createElement('iframe');
                    quadro.src = fonte;                 // setAttribute e nao string montada na mao:
                    quadro.className = 'w-full h-full'; // aspas dentro da url nao escapam do atributo
                    quadro.setAttribute('frameborder', '0');
                    quadro.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
                    quadro.setAttribute('allow', 'autoplay; fullscreen; picture-in-picture; encrypted-media');
                    quadro.setAttribute('allowfullscreen', '');
                    var caixa = document.getElementById('live-player');
                    caixa.replaceChildren(quadro);
                }

                if (window.Hls && window.Hls.isSupported()) {
                    var hls = new Hls();
                    hls.loadSource(src);
                    hls.attachMedia(video);
                    hls.on(Hls.Events.MANIFEST_PARSED, function () { video.play(); });
                    hls.on(Hls.Events.ERROR, function (e, dados) {
                        if (dados.fatal) { hls.destroy(); cairPraFonte(); }
                    });
                } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                    // Safari e iOS tocam .m3u8 de fabrica, sem biblioteca nenhuma.
                    video.src = src;
                    video.addEventListener('error', cairPraFonte);
                } else {
                    cairPraFonte();
                }
            })();
        </script>
    @endif
</body>
</html>
