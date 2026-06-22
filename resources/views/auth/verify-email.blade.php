<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirmação de E-mail - {{ config('app.name', 'Laravel') }}</title>

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
                    <h1 class="text-3xl font-bold mb-2 text-gray-900">Confirme seu e-mail!</h1>

                    @if (session('status'))
                        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                            <p class="text-sm text-green-800">{{ session('status') }}</p>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                            <ul class="text-sm text-red-600">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mt-6 space-y-4">
                        <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex items-start">
                                <svg class="w-6 h-6 text-blue-600 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-blue-900 mb-2">Verificação de e-mail necessária</p>
                                    <p class="text-sm text-blue-700">
                                        Enviamos um e-mail de confirmação para <strong>{{ $email }}</strong>.
                                        Por favor, verifique sua caixa de entrada e clique no link de confirmação para ativar sua conta.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="flex items-start">
                                <svg class="w-6 h-6 text-yellow-600 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-yellow-900 mb-2">Não recebeu o e-mail?</p>
                                    <p class="text-sm text-yellow-700 mb-3">
                                        Verifique sua pasta de spam ou lixo eletrônico. O e-mail pode ter sido filtrado.
                                    </p>
                                    <form method="POST" action="{{ route('verification.resend') }}">
                                        @csrf
                                        <button type="submit" class="text-sm text-yellow-800 hover:text-yellow-900 font-medium underline">
                                            Reenviar e-mail de confirmação
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4">
                            <a href="{{ route('login') }}" class="block w-full px-5 py-3 bg-[#14d1bc] hover:bg-[#0e9486] text-[#01323a] hover:text-white font-semibold rounded-lg transition-colors text-center">
                                Voltar para Login
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Coluna Direita: Banner -->
            <div class="w-full md:w-1/2 bg-[#14d1bc] relative overflow-hidden hidden items-center justify-center lg:flex">
                <!-- Imagem de fundo com overlay -->
                <div class="absolute inset-0 bg-cover bg-center bg-no-repeat opacity-50" style="background-image: url('/img/bg1.jpg');">
                </div>

                <div class="absolute inset-0 bg-cover bg-center opacity-10" style="background-image: url('data:image/svg+xml,%3Csvg width=%2260%22 height=%2260%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cg fill=%22none%22 fill-rule=%22evenodd%22%3E%3Cg fill=%22%23ffffff%22 fill-opacity=%220.1%22%3E%3Cpath d=%22M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z%22/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');">
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
    <x-whatsapp-float :clear-mobile-nav="false" />
</body>
</html>

