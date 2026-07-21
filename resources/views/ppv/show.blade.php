<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Comprar Conteúdo — {{ $creator->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <x-topnav />

    <div class="pt-16 pb-16 md:pb-0">
        <div class="max-w-lg mx-auto px-4 py-8">
            <div class="bg-white rounded-xl shadow-sm p-6">

                <!-- Cabeçalho -->
                <div class="flex items-center gap-4 mb-6 pb-6 border-b border-gray-100">
                    @if($creator->profile_photo)
                        <img src="{{ $creator->profile_photo }}" class="w-16 h-16 rounded-full object-cover">
                    @else
                        <div class="w-16 h-16 rounded-full bg-pink-100 flex items-center justify-center">
                            <span class="text-pink-500 text-2xl font-bold">{{ substr($creator->name, 0, 1) }}</span>
                        </div>
                    @endif
                    <div>
                        <h2 class="font-bold text-gray-900 text-lg">{{ $creator->name }}</h2>
                        <p class="text-gray-500 text-sm">Conteúdo Único</p>
                    </div>
                </div>

                <!-- Valor -->
                <div class="text-center mb-6">
                    <p class="text-gray-500 text-sm mb-1">Valor do conteúdo</p>
                    <p class="text-4xl font-bold text-green-600">
                        R$ {{ number_format($post->price, 2, ',', '.') }}
                    </p>
                    <p class="text-gray-400 text-xs mt-1">Acesso permanente após a compra</p>
                </div>

                <!-- Método: PIX -->
                @if($method === 'pix')
                    <div id="pix-section">
                        @if($transaction && $transaction->payment_code_base64)
                            <div class="text-center">
                                <img src="data:image/png;base64,{{ $transaction->payment_code_base64 }}"
                                     class="mx-auto w-48 h-48 mb-4">
                                <p class="text-sm text-gray-600 mb-2">Copie o código PIX:</p>
                                <div class="flex gap-2">
                                    <input type="text" value="{{ $transaction->payment_code }}"
                                           id="pix-code" readonly
                                           class="flex-1 text-xs border border-gray-300 rounded px-3 py-2 bg-gray-50">
                                    <button onclick="copyPix()"
                                            class="bg-green-500 text-white px-4 py-2 rounded text-sm hover:bg-green-600">
                                        Copiar
                                    </button>
                                </div>
                            </div>

                            <div id="status-msg" class="mt-4 text-center text-sm text-gray-500">
                                Aguardando pagamento...
                            </div>
                        @else
                            <p class="text-center text-red-500">
                                Erro ao gerar QR Code.
                                <a href="{{ url()->current() }}" class="underline">Tentar novamente</a>
                            </p>
                        @endif
                    </div>
                @endif

                <!-- Método: Cartão -->
                @if($method === 'card')
                    <form id="card-form" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Número do cartão</label>
                            <input type="text" name="card_number" placeholder="0000 0000 0000 0000"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-green-500">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Validade</label>
                                <input type="text" name="card_expiry" placeholder="MM/AA"
                                       class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-green-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">CVV</label>
                                <input type="text" name="card_cvv" placeholder="123"
                                       class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-green-500">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome no cartão</label>
                            <input type="text" name="card_name" placeholder="NOME SOBRENOME"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-green-500">
                        </div>
                        <div id="card-error" class="text-red-500 text-sm hidden"></div>
                        <button type="submit"
                                class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-3 rounded-lg transition-colors">
                            Pagar R$ {{ number_format($post->price, 2, ',', '.') }}
                        </button>
                    </form>
                @endif

                {{-- Pagar com saldo: só se o saldo cobre o conteúdo inteiro (tudo ou nada). --}}
                @if($walletBalance >= $post->price)
                    <div class="mt-6 p-4 rounded-lg border border-emerald-200 bg-emerald-50">
                        <div class="font-semibold text-emerald-800">Você tem R$ {{ number_format($walletBalance, 2, ',', '.') }} de saldo</div>
                        <div class="text-sm text-emerald-700 mt-1 mb-3">Dá para liberar este conteúdo agora, sem PIX nem cartão.</div>
                        <button type="button" id="btnPagarSaldo"
                                data-url="{{ route('ppv.process', [$post->id, 'wallet']) }}"
                                class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 rounded-lg transition-colors">
                            Pagar R$ {{ number_format($post->price, 2, ',', '.') }} com meu saldo
                        </button>
                        <div id="erroSaldo" class="hidden mt-2 text-sm text-red-600"></div>
                    </div>
                @endif

                <!-- Trocar método -->
                <div class="mt-6 pt-4 border-t border-gray-100 text-center text-sm text-gray-500">
                    @if($method === 'pix')
                        Prefere cartão?
                        <a href="{{ route('ppv.show', [$post->id, 'card']) }}" class="text-green-600 font-medium">Pagar com cartão</a>
                    @else
                        Prefere PIX?
                        <a href="{{ route('ppv.show', [$post->id, 'pix']) }}" class="text-green-600 font-medium">Pagar com PIX</a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        // Pagar com saldo da carteira (o servidor trava com lock; aqui é só pra não piscar 2 pedidos)
        document.getElementById('btnPagarSaldo')?.addEventListener('click', async function () {
            const btn = this, erro = document.getElementById('erroSaldo');
            btn.disabled = true;
            btn.classList.add('opacity-60');
            btn.textContent = 'Processando...';
            erro.classList.add('hidden');
            try {
                const r = await fetch(btn.dataset.url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                });
                const data = await r.json();
                if (data.success && data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }
                erro.textContent = data.message || 'Não foi possível concluir.';
                erro.classList.remove('hidden');
            } catch (e) {
                erro.textContent = 'Falha de conexão. Tente novamente.';
                erro.classList.remove('hidden');
            }
            btn.disabled = false;
            btn.classList.remove('opacity-60');
            btn.textContent = 'Pagar com meu saldo';
        });

        function copyPix() {
            const input = document.getElementById('pix-code');
            input.select();
            document.execCommand('copy');
            alert('Código copiado!');
        }

        @if($method === 'pix' && isset($transaction))
        // Polling: verifica status a cada 3 segundos
        const transactionId = {{ $transaction->id }};
        const pollInterval = setInterval(function () {
            fetch('/ppv/transaction/' + transactionId + '/status')
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'paid_out' && data.purchase_id) {
                        clearInterval(pollInterval);
                        document.getElementById('status-msg').innerHTML =
                            '<span class="text-green-600 font-semibold">✓ Pagamento confirmado! Redirecionando...</span>';
                        setTimeout(() => {
                            window.location.href = '/ppv/success/' + data.purchase_id;
                        }, 1500);
                    } else if (data.status === 'canceled' || data.status === 'unpaid') {
                        clearInterval(pollInterval);
                        document.getElementById('status-msg').innerHTML =
                            '<span class="text-red-500">Pagamento não concluído.</span>';
                    }
                });
        }, 3000);
        @endif

        @if($method === 'card')
        document.getElementById('card-form').addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = this.querySelector('button[type=submit]');
            btn.disabled = true;
            btn.textContent = 'Processando...';

            const data = new FormData(this);

            fetch('{{ route("ppv.process", [$post->id, "card"]) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                },
                body: data,
            })
            .then(r => r.json())
            .then(res => {
                if (res.redirect) {
                    window.location.href = res.redirect;
                } else if (!res.success) {
                    document.getElementById('card-error').textContent = res.message;
                    document.getElementById('card-error').classList.remove('hidden');
                    btn.disabled = false;
                    btn.textContent = 'Pagar R$ {{ number_format($post->price, 2, ",", ".") }}';
                }
            })
            .catch(() => {
                btn.disabled = false;
                btn.textContent = 'Pagar R$ {{ number_format($post->price, 2, ",", ".") }}';
            });
        });
        @endif
    </script>
</body>
</html>
