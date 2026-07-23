<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - {{ config('app.name', 'Laravel') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="/css/app.css">
    <script src="/js/app.js"></script>
</head>

<body class="bg-[#FDFDFC] min-h-screen flex flex-col">

    <!-- ═══════════════════════════════════════════════
         HEADER
    ═══════════════════════════════════════════════ -->
    <header class="bg-[#01313B] w-full py-3 px-6">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <!-- Logo -->
            <img src="/img/logo.png" alt="PierFans" class="h-8">

            <!-- Redes sociais + badge +18 -->
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-3">
                    <a href="https://www.instagram.com/pier.fans.oficial" target="_blank" rel="noopener"
                        class="text-white/60 hover:text-white transition-colors"
                        title="Instagram">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                    </a>
                    <a href="https://www.tiktok.com/@pierfansoficial" target="_blank" rel="noopener"
                        class="text-white/60 hover:text-white transition-colors"
                        title="TikTok">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.69a8.17 8.17 0 004.79 1.52V6.77a4.85 4.85 0 01-1.02-.08z"/>
                        </svg>
                    </a>
                    <a href="https://x.com/PierFans" target="_blank" rel="noopener"
                        class="text-white/60 hover:text-white transition-colors"
                        title="Twitter/X">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                        </svg>
                    </a>
                    <a href="https://t.me/+Un0sfn6L0kMxZGYx" target="_blank" rel="noopener"
                        class="text-white/60 hover:text-white transition-colors"
                        title="Telegram">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                        </svg>
                    </a>
                </div>
                <span class="bg-[#FC9E2B] text-[#01313B] text-xs font-semibold px-2 py-1 rounded">+18</span>
            </div>
        </div>
    </header>

    <!-- ═══════════════════════════════════════════════
         HERO: FORMULÁRIO + BANNER
    ═══════════════════════════════════════════════ -->
    <div class="flex flex-col lg:flex-row" style="min-height: 480px;">

        <!-- Coluna Esquerda: Formulário -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-6 md:p-12 bg-white">
            <div class="w-full max-w-md">

                <!-- Stats de credibilidade -->
                <div class="flex gap-6 mb-6">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-[#14d1bc]">30+</div>
                        <div class="text-xs text-gray-500">Criadoras</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-[#14d1bc]">100%</div>
                        <div class="text-xs text-gray-500">Brasileiras</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-[#FC9E2B]">Exclusivo</div>
                        <div class="text-xs text-gray-500">Conteúdo</div>
                    </div>
                </div>

                <h1 class="text-3xl font-bold mb-1 text-gray-900">Apoie seu criador favorito!</h1>
                <p class="text-sm text-gray-500 mb-6">Acesse conteúdo exclusivo das melhores criadoras brasileiras.</p>

                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <ul class="text-sm text-red-600">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('status'))
                    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-sm text-green-800">{{ session('status') }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf
                    <div>
                        <input type="text" id="email" name="email" value="{{ old('email') }}" required autofocus
                            class="w-full px-4 py-3 bg-gray-100 border-0 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#14d1bc] transition-colors"
                            placeholder="Usuário ou E-mail">
                    </div>
                    <div class="relative">
                        <input type="password" id="password" name="password" required
                            class="w-full px-4 py-3 bg-gray-100 border-0 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#14d1bc] transition-colors pr-12"
                            placeholder="Senha">
                        <button type="button" onclick="togglePasswordVisibility('password')"
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                            <svg id="password-eye" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg id="password-eye-off" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.736m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                    <div class="flex items-center justify-end">
                        <a href="{{ route('password.request') }}"
                            class="text-sm text-[#14d1bc] hover:text-[#0e9486] hover:underline">
                            Esqueceu sua senha?
                        </a>
                    </div>
                    <button type="submit"
                        class="w-full px-5 py-3 bg-[#14d1bc] hover:bg-[#0e9486] text-[#01323a] hover:text-white font-semibold rounded-lg transition-colors">
                        Entrar
                    </button>
                </form>

                <div class="mt-4 text-center">
                    <p class="text-sm text-gray-600">
                        Ainda não tem uma conta?
                        <a href="{{ route('register') }}" class="text-[#14d1bc] hover:text-[#0e9486] font-medium hover:underline">
                            Cadastrar-se
                        </a>
                    </p>
                </div>

                <!-- CTA Criadora -->
                <div class="mt-5 p-4 bg-[#01313B] rounded-xl text-center">
                    <p class="text-white/70 text-xs mb-2">Quer monetizar seu conteúdo?</p>
                    <a href="{{ route('register') }}?type=creator"
                        class="inline-flex items-center justify-center gap-2 w-full text-[#14d1bc] font-semibold text-sm hover:text-white transition-colors">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                        </svg>
                        Seja um de nossos criadores
                        <span class="flex-shrink-0">→</span>
                    </a>
                </div>

            </div>
        </div>

        <!-- Coluna Direita: Banner Destaque da Semana (colab Juju) - responsivo, aparece no mobile tambem -->
        <div class="w-full lg:w-1/2 h-[380px] sm:h-[420px] lg:h-auto bg-[#01313B] relative overflow-hidden flex">
            <!-- Foto ocupa ~70% da largura; a mascara dissolve a borda esquerda no verde (sem linha dura) -->
            <img src="/img/destaque-juju.jpg" alt="Destaque da semana"
                class="absolute right-0 top-0 h-full w-1/2 lg:w-[70%] object-cover object-top z-[1]"
                style="-webkit-mask-image: linear-gradient(to right, transparent 0%, #000 45%); mask-image: linear-gradient(to right, transparent 0%, #000 45%);">
            <!-- Overlay leve so pra dar contraste ao texto na esquerda (a transicao quem faz e a mascara) -->
            <div class="absolute inset-0 z-[2]"
                style="background: linear-gradient(90deg, rgba(1,49,59,0.55) 0%, rgba(1,49,59,0.15) 32%, rgba(1,49,59,0) 46%);"></div>
            <!-- Logo -->
            <img src="/img/logo.png" alt="PierFans" class="absolute top-5 left-6 lg:top-6 lg:left-8 w-32 lg:w-40 z-[3]">
            <!-- Texto -->
            <div class="absolute z-[3] left-6 lg:left-8 top-0 bottom-0 flex flex-col justify-center gap-2 lg:gap-3 max-w-[62%]">
                <span class="self-start inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#FC9E2B] text-white text-[10px] lg:text-xs font-extrabold rounded-full uppercase tracking-wide">
                    🔥 Destaque da semana
                </span>
                <p class="text-white font-black leading-none text-3xl sm:text-4xl lg:text-5xl m-0">EM<br>BREVE</p>
                <p class="text-white/80 text-xs lg:text-sm m-0">colab <b class="text-[#14d1bc]">@juju</b> chegando</p>
                <a href="{{ route('register') }}"
                    class="self-start mt-1 bg-[#14d1bc] hover:bg-[#0e9486] text-[#01323a] hover:text-white font-semibold text-xs lg:text-sm px-4 py-2 lg:py-2.5 rounded-lg transition-colors">
                    quero ver →
                </a>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════
         CONTEÚDOS EM DESTAQUE
    ═══════════════════════════════════════════════ -->
    @if (isset($featuredPosts) && $featuredPosts->count() > 0)
        <div class="bg-gray-50 py-10 px-6">
            <div class="max-w-5xl mx-auto">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">
                    Conteúdos em destaque
                    <span class="text-[#FC9E2B]">🔥</span>
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($featuredPosts as $post)
                        <x-featured-post-card :post="$post" />
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- ═══════════════════════════════════════════════
         FOOTER
    ═══════════════════════════════════════════════ -->
    <footer class="bg-[#01313B] py-6 px-6 mt-auto">
        <div class="max-w-5xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
            <img src="/img/logo.png" alt="PierFans" class="h-8">
            <div class="flex items-center gap-4">
                <a href="https://www.instagram.com/pier.fans.oficial" target="_blank" rel="noopener"
                    class="text-white/50 hover:text-white transition-colors" title="Instagram">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                    </svg>
                </a>
                <a href="https://www.tiktok.com/@pierfansoficial" target="_blank" rel="noopener"
                    class="text-white/50 hover:text-white transition-colors" title="TikTok">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.69a8.17 8.17 0 004.79 1.52V6.77a4.85 4.85 0 01-1.02-.08z"/>
                    </svg>
                </a>
                <a href="https://x.com/PierFans" target="_blank" rel="noopener"
                    class="text-white/50 hover:text-white transition-colors" title="Twitter/X">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                    </svg>
                </a>
                <a href="https://t.me/+Un0sfn6L0kMxZGYx" target="_blank" rel="noopener"
                    class="text-white/50 hover:text-white transition-colors" title="Telegram">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                    </svg>
                </a>
            </div>
            <p class="text-white/30 text-xs">+18 · Conteúdo exclusivo para adultos · PierFans © {{ date('Y') }}</p>
        </div>
    </footer>

    <script>
        function togglePasswordVisibility(inputId) {
            const input = document.getElementById(inputId);
            const eye = document.getElementById(inputId + '-eye');
            const eyeOff = document.getElementById(inputId + '-eye-off');
            if (input.type === 'password') {
                input.type = 'text';
                eye.classList.add('hidden');
                eyeOff.classList.remove('hidden');
            } else {
                input.type = 'password';
                eye.classList.remove('hidden');
                eyeOff.classList.add('hidden');
            }
        }
    </script>
</body>
</html>