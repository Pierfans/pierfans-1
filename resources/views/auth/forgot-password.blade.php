<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar Senha - {{ config('app.name', 'Laravel') }}</title>

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
                    <h1 class="text-3xl font-bold mb-2 text-gray-900">Recuperar senha</h1>
                    <p class="text-sm text-gray-600 mb-6">
                        Digite seu e-mail para receber um link de redefinição de senha
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

                    @if (session('status'))
                        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                            <p class="text-sm text-green-800">{{ session('status') }}</p>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                        @csrf

                        <div>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                class="w-full px-4 py-3 bg-gray-100 border-0 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#14d1bc] transition-colors"
                                placeholder="E-mail"
                            >
                        </div>

                        <button
                            type="submit"
                            class="w-full px-5 py-3 bg-[#14d1bc] hover:bg-[#0e9486] text-[#01323a] hover:text-white font-semibold rounded-lg transition-colors"
                        >
                            Enviar link de redefinição
                        </button>
                    </form>

                    <div class="mt-6 text-center">
                        <p class="text-sm text-gray-600">
                            Ou, voltar para
                            <a href="{{ route('login') }}" class="text-[#14d1bc] hover:text-[#0e9486] font-medium hover:underline">
                                entrar
                            </a>
                        </p>
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
</body>
</html>
