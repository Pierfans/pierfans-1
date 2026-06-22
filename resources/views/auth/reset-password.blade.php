<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Redefinir Senha - {{ config('app.name', 'Laravel') }}</title>

    <!-- TailwindCSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- jQuery via CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Estilos e scripts customizados -->
    <link rel="stylesheet" href="/css/app.css">
    <script src="/js/app.js"></script>
</head>

<body class="bg-[#FDFDFC] min-h-screen">
    <div class="min-h-screen flex flex-col">
        <!-- Seção Superior: Formulário + Banner -->
        <div class="flex-1 flex flex-col md:flex-row">
            <!-- Coluna Esquerda: Formulário -->
            <div class="w-full lg:w-1/2 flex items-center justify-center p-6 md:p-12 bg-white">
                <div class="w-full max-w-md">
                    <h1 class="text-3xl font-bold mb-2 text-gray-900">Criar nova senha</h1>
                    <p class="text-sm text-gray-600 mb-6">
                        Digite sua nova senha abaixo
                    </p>

                    @if ($errors->any())
                        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                            <ul class="text-sm text-red-600">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                        @csrf

                        <input type="hidden" name="token" value="{{ $token }}">
                        <input type="hidden" name="email" value="{{ $email ?? old('email') }}">

                        <div class="relative">
                            <input type="password" id="password" name="password" required minlength="8"
                                class="w-full px-4 py-3 bg-gray-100 border-0 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#14d1bc] transition-colors pr-12"
                                placeholder="Nova Senha">
                            <button type="button" onclick="togglePasswordVisibility('password')"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                                <svg id="password-eye" class="w-5 h-5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg id="password-eye-off" class="w-5 h-5 hidden" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.736m0 0L21 21" />
                                </svg>
                            </button>
                        </div>

                        <div class="relative">
                            <input type="password" id="password_confirmation" name="password_confirmation" required
                                minlength="8"
                                class="w-full px-4 py-3 bg-gray-100 border-0 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#14d1bc] transition-colors pr-12"
                                placeholder="Confirmar Nova Senha">
                            <button type="button" onclick="togglePasswordVisibility('password_confirmation')"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                                <svg id="password_confirmation-eye" class="w-5 h-5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg id="password_confirmation-eye-off" class="w-5 h-5 hidden" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.736m0 0L21 21" />
                                </svg>
                            </button>
                        </div>

                        <button type="submit"
                            class="w-full px-5 py-3 bg-[#14d1bc] hover:bg-[#0e9486] text-[#01323a] hover:text-white font-semibold rounded-lg transition-colors">
                            Redefinir Senha
                        </button>
                    </form>

                    <div class="mt-6 text-center">
                        <a href="{{ route('login') }}"
                            class="text-sm text-[#14d1bc] hover:text-[#0e9486] hover:underline">
                            ← Voltar para o login
                        </a>
                    </div>
                </div>
            </div>

            <!-- Coluna Direita: Banner -->
            <div
                class="w-full md:w-1/2 bg-[#14d1bc] relative overflow-hidden hidden items-center justify-center lg:flex">
                <!-- Imagem de fundo com overlay -->
                <div class="absolute inset-0 bg-cover bg-center bg-no-repeat opacity-50"
                    style="background-image: url('/img/bg1.jpg');">
                </div>

                <div class="absolute inset-0 bg-cover bg-center opacity-10"
                    style="background-image: url('data:image/svg+xml,%3Csvg width=%2260%22 height=%2260%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cg fill=%22none%22 fill-rule=%22evenodd%22%3E%3Cg fill=%22%23ffffff%22 fill-opacity=%220.1%22%3E%3Cpath d=%22M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z%22/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');">
                </div>

                <!-- Logo -->
                <div class="relative z-10 text-center">
                    <h1 class="text-5xl font-bold text-white mb-2"><img class="w-60" src="/img/logo.png" /></h1>
                    <div class="flex items-center justify-center">
                    </div>
                </div>
            </div>
        </div>

    </div>

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
