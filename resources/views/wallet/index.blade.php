<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Carteira - {{ config('app.name', 'Laravel') }}</title>

    <!-- TailwindCSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- jQuery via CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Estilos e scripts customizados -->
    <link rel="stylesheet" href="/css/app.css">
    <script src="/js/app.js"></script>
</head>

<body class="bg-gray-50 min-h-screen">
    <!-- Top Navigation (Desktop) -->
    <x-topnav />

    <!-- Bottom Navigation (Mobile) -->
    <x-bottomnav />

    <!-- Main Content -->
    <div class="pt-16 md:pt-16 pb-16 md:pb-0">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Minha Carteira</h1>
            <p class="text-gray-600 mt-2">Gerencie seu saldo e visualize suas movimentações</p>
        </div>

        <!-- Mensagens de Sucesso/Erro -->
        @if(session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        @if(session('info'))
            <div class="mb-4 bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('info') }}</span>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Coluna Principal -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Card de Saldo -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-700 mb-1">Saldo Disponível</h2>
                            <div class="text-4xl font-bold text-pink-600">
                                R$ {{ number_format($wallet->balance, 2, ',', '.') }}
                            </div>
                        </div>
                        {{-- Depósito desativado até existir forma de gastar o saldo (assinatura/PPV). --}}
                        <button
                            disabled
                            title="Depósitos temporariamente desativados"
                            class="px-6 py-3 bg-gray-200 text-gray-400 rounded-lg font-semibold cursor-not-allowed"
                        >
                            Depositar
                        </button>
                    </div>
                    <p class="text-sm text-gray-500">Depósitos estão temporariamente desativados. Se você já tem saldo, ele continua guardado.</p>
                </div>

                <!-- Extrato de Movimentações -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Extrato de Movimentações</h2>
                    @if($transactions->count() > 0)
                        <div class="space-y-4">
                            @foreach($transactions as $transaction)
                                <div class="border-b border-gray-200 pb-4 last:border-b-0">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-1">
                                                @if($transaction->type === 'credit')
                                                    <span class="text-sm font-semibold text-green-600">
                                                        + R$ {{ number_format($transaction->amount, 2, ',', '.') }}
                                                    </span>
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                        Entrada
                                                    </span>
                                                @else
                                                    <span class="text-sm font-semibold text-red-600">
                                                        - R$ {{ number_format($transaction->amount, 2, ',', '.') }}
                                                    </span>
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                                        Saída
                                                    </span>
                                                @endif
                                            </div>
                                            @if($transaction->description)
                                                <p class="text-sm text-gray-600 mb-1">{{ $transaction->description }}</p>
                                            @endif
                                            <div class="flex items-center gap-4 text-xs text-gray-500">
                                                <span>{{ $transaction->created_at->emBrasilia()->format('d/m/Y H:i') }}</span>
                                                @if($transaction->adminUser)
                                                    <span>Adicionado por: {{ $transaction->adminUser->name }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <!-- Paginação -->
                        <div class="mt-6">
                            {{ $transactions->links() }}
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-8">Nenhuma movimentação encontrada.</p>
                    @endif
                </div>
            </div>

            <!-- Coluna Lateral -->
            <div class="space-y-6">
                <!-- Informações -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Sobre a Carteira</h3>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li>• Consulte seu extrato completo</li>
                        <li>• Novos depósitos estão desativados no momento</li>
                        <li>• Saldo já existente continua guardado</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Depósito -->
    <div id="depositModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-900">Depositar na Carteira</h3>
                <button onclick="closeDepositModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <form id="depositForm">
                <div class="mb-4">
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                        Valor (R$)
                    </label>
                    <input 
                        type="number" 
                        id="amount" 
                        name="amount" 
                        step="0.01" 
                        min="0.01" 
                        max="999999.99"
                        placeholder="0,00" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                        required
                    >
                    <p class="text-xs text-gray-500 mt-1">Valor mínimo: R$ 0,01</p>
                </div>
                
                <div class="flex gap-3">
                    <button 
                        type="button"
                        onclick="closeDepositModal()"
                        class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-semibold"
                    >
                        Cancelar
                    </button>
                    <button 
                        type="submit"
                        id="depositPixButton"
                        class="flex-1 px-4 py-2 bg-pink-500 text-white rounded-lg hover:bg-pink-600 transition-colors font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
                        disabled
                    >
                        Depositar via Pix
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de QR Code PIX -->
    <div id="qrcodeModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-900">Pagamento PIX</h3>
                <button onclick="closeQrcodeModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <div class="text-center">
                <p class="text-gray-600 mb-4">Escaneie o QR Code com o app do seu banco para concluir o pagamento</p>
                <div id="qrcodeContainer" class="flex justify-center mb-4">
                    <!-- QR Code será inserido aqui -->
                </div>
                <p class="text-sm text-gray-500 mb-4">Ou copie o código PIX abaixo:</p>
                <div class="bg-gray-100 p-3 rounded-lg mb-4">
                    <p id="pixCode" class="text-xs font-mono break-all text-gray-700"></p>
                </div>
                <button 
                    onclick="copyPixCode()"
                    class="w-full px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-semibold mb-4"
                >
                    Copiar Código PIX
                </button>
                <p class="text-xs text-gray-500">Após o pagamento, o saldo será creditado automaticamente em sua carteira.</p>
            </div>
        </div>
    </div>

    <script>
        let currentTransactionId = null;
        let checkStatusInterval = null;

        // Verifica se há valor de depósito na sessão e abre o modal automaticamente
        @if(session('deposit_amount'))
            $(document).ready(function() {
                const depositAmount = {{ session('deposit_amount') }};
                $('#amount').val(depositAmount.toFixed(2));
                $('#depositPixButton').prop('disabled', false);
                openDepositModal();
            });
        @endif

        function openDepositModal() {
            document.getElementById('depositModal').classList.remove('hidden');
            document.getElementById('depositModal').classList.add('flex');
        }

        function closeDepositModal() {
            document.getElementById('depositModal').classList.add('hidden');
            document.getElementById('depositModal').classList.remove('flex');
            document.getElementById('depositForm').reset();
            document.getElementById('depositPixButton').disabled = true;
        }

        function openQrcodeModal() {
            document.getElementById('qrcodeModal').classList.remove('hidden');
            document.getElementById('qrcodeModal').classList.add('flex');
        }

        function closeQrcodeModal() {
            document.getElementById('qrcodeModal').classList.add('hidden');
            document.getElementById('qrcodeModal').classList.remove('flex');
            if (checkStatusInterval) {
                clearInterval(checkStatusInterval);
                checkStatusInterval = null;
            }
        }

        // Habilita botão quando valor for preenchido
        $('#amount').on('input', function() {
            const amount = parseFloat($(this).val());
            const button = $('#depositPixButton');
            if (amount && amount >= 0.01) {
                button.prop('disabled', false);
            } else {
                button.prop('disabled', true);
            }
        });

        // Submete formulário de depósito
        $('#depositForm').on('submit', function(e) {
            e.preventDefault();
            
            const amount = parseFloat($('#amount').val());
            if (!amount || amount < 0.01) {
                alert('Por favor, informe um valor válido (mínimo R$ 0,01).');
                return;
            }

            const submitBtn = $('#depositPixButton');
            const originalText = submitBtn.text();
            submitBtn.prop('disabled', true).text('Gerando QR Code...');

            $.ajax({
                url: '{{ route("wallet.process-add-balance", "pix") }}',
                method: 'POST',
                data: {
                    amount: amount,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        // Fecha modal de depósito
                        closeDepositModal();
                        
                        // Exibe QR Code
                        currentTransactionId = response.transaction_id;
                        displayQrcode(response.payment_code_base64, response.payment_code);
                        
                        // Inicia verificação de status
                        startStatusCheck();
                    } else {
                        // Se há redirect, redireciona para o formulário de identificação
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        } else {
                            alert('Erro: ' + (response.message || 'Erro ao gerar QR Code.'));
                            submitBtn.prop('disabled', false).text(originalText);
                        }
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    // Se há redirect na resposta de erro, redireciona
                    if (response && response.redirect) {
                        window.location.href = response.redirect;
                    } else {
                        const errorMessage = response?.message || 'Erro ao gerar QR Code. Tente novamente.';
                        alert('Erro: ' + errorMessage);
                        submitBtn.prop('disabled', false).text(originalText);
                    }
                }
            });
        });

        function displayQrcode(qrcodeBase64, pixCode) {
            const container = $('#qrcodeContainer');
            container.html(`<img src="data:image/png;base64,${qrcodeBase64}" alt="QR Code PIX" class="max-w-full h-auto">`);
            $('#pixCode').text(pixCode);
            openQrcodeModal();
        }

        function copyPixCode() {
            const pixCode = $('#pixCode').text();
            navigator.clipboard.writeText(pixCode).then(function() {
                alert('Código PIX copiado!');
            });
        }

        function startStatusCheck() {
            if (!currentTransactionId) return;
            
            checkStatusInterval = setInterval(function() {
                $.ajax({
                    url: `/wallet/transaction/${currentTransactionId}/status`,
                    method: 'GET',
                    success: function(response) {
                        if (response.success && response.status === 'paid_out') {
                            clearInterval(checkStatusInterval);
                            alert('Pagamento confirmado! Saldo adicionado à sua carteira.');
                            closeQrcodeModal();
                            location.reload();
                        }
                    }
                });
            }, 5000); // Verifica a cada 5 segundos
        }
    </script>
</body>

</html>
