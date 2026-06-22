<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Conteúdo Liberado!</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <x-topnav />

    <div class="pt-16 pb-16">
        <div class="max-w-lg mx-auto px-4 py-8 text-center">
            <div class="bg-white rounded-xl shadow-sm p-8">

                <!-- Ícone de sucesso -->
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>

                <h1 class="text-2xl font-bold text-gray-900 mb-2">Conteúdo Liberado!</h1>
                <p class="text-gray-500 mb-1">
                    Você comprou este conteúdo por
                    <strong class="text-gray-800">R$ {{ number_format($purchase->amount_paid, 2, ',', '.') }}</strong>
                </p>
                <p class="text-gray-400 text-sm mb-8">O acesso é permanente.</p>

                <a href="{{ $post->user->username ? '/' . $post->user->username : route('dashboard') }}"
                   class="inline-block bg-green-500 hover:bg-green-600 text-white font-semibold px-8 py-3 rounded-full transition-colors">
                    Ver perfil de {{ $creator->name }}
                </a>

                <div class="mt-4">
                    <a href="{{ route('dashboard') }}" class="text-gray-400 text-sm hover:text-gray-600">
                        Voltar ao início
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
