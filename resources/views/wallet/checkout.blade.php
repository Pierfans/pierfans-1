<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Adicionar Saldo - {{ config('app.name', 'Laravel') }}</title>

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
        .checkout-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 24px;
        }

        .checkout-card {
            background: white;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 24px;
        }

        .checkout-header {
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 1px solid #E5E5E5;
        }

        .checkout-title {
            font-size: 24px;
            font-weight: 600;
            color: #1b1b18;
            margin-bottom: 4px;
        }

        .checkout-subtitle {
            font-size: 16px;
            color: #706f6c;
        }

        .amount-info {
            background: #F5F5F5;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }

        .amount-label {
            font-size: 14px;
            color: #706f6c;
            margin-bottom: 8px;
        }

        .amount-value {
            font-size: 32px;
            font-weight: 700;
            color: #FF6B35;
        }

        .payment-method-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: #F5F5F5;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #1b1b18;
            margin-bottom: 24px;
        }

        .payment-method-badge.card {
            background: #1b1b18;
            color: white;
        }

        .payment-method-badge.pix {
            background: #32BCAD;
            color: white;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #1b1b18;
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #E5E5E5;
            border-radius: 8px;
            font-size: 16px;
            color: #1b1b18;
            transition: border-color 0.2s;
        }

        .form-input:focus {
            outline: none;
            border-color: #FF6B35;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        @media (max-width: 640px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        .btn-pay {
            background: #FF6B35;
            color: white;
            padding: 16px 32px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            width: 100%;
            transition: background 0.2s;
            margin-top: 24px;
        }

        .btn-pay:hover {
            background: #e55a2b;
        }

        .btn-pay:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .pix-qr-code {
            text-align: center;
            padding: 32px;
            background: #F5F5F5;
            border-radius: 12px;
            margin-bottom: 24px;
        }

        .pix-qr-code img {
            max-width: 256px;
            margin: 0 auto;
            display: block;
        }

        .pix-code {
            background: white;
            border: 1px solid #E5E5E5;
            border-radius: 8px;
            padding: 16px;
            margin-top: 16px;
            word-break: break-all;
            font-family: monospace;
            font-size: 12px;
            position: relative;
        }

        .copy-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #FF6B35;
            color: white;
            border: none;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
        }

        .error-message {
            background: #fee;
            border: 1px solid #fcc;
            color: #c33;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
        }
    </style>
</head>
<body class="bg-[#F5F5F5] text-[#1b1b18] min-h-screen">
    <!-- Top Navigation -->
    <x-topnav />

    <!-- Bottom Navigation -->
    <x-bottomnav />

    <!-- Profile Drawer -->
    <x-profile-drawer />

    <!-- Main Content -->
    <div class="pt-0 md:pt-16 md:pb-0 pb-16">
        <div class="checkout-container">
            <div class="checkout-card">
                <!-- Header -->
                <div class="checkout-header">
                    <h1 class="checkout-title">Adicionar Saldo na Carteira</h1>
                    <p class="checkout-subtitle">Complete o pagamento para adicionar saldo</p>
                </div>

                <!-- Valor -->
                <div class="amount-info">
                    <div class="amount-label">Valor a adicionar</div>
                    <div class="amount-value">R$ {{ number_format($amount, 2, ',', '.') }}</div>
                </div>

                <!-- Badge do Método de Pagamento -->
                <div class="payment-method-badge {{ $method }}">
                    @if($method === 'card')
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                        Pagamento com Cartão
                    @else
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                        Pagamento com PIX
                    @endif
                </div>

                @if($method === 'card')
                    <!-- Formulário de Cartão -->
                    <form id="cardPaymentForm" method="POST" action="{{ route('wallet.add-balance.process', ['method' => 'card']) }}">
                        @csrf
                        <input type="hidden" name="amount" value="{{ $amount }}">

                        <div class="form-group">
                            <label class="form-label">Número do Cartão</label>
                            <input type="text" name="card_number" class="form-input" placeholder="0000 0000 0000 0000" maxlength="19" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Validade</label>
                                <input type="text" name="card_expiry" class="form-input" placeholder="MM/AA" maxlength="5" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">CVV</label>
                                <input type="text" name="card_cvv" class="form-input" placeholder="123" maxlength="4" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nome no Cartão</label>
                            <input type="text" name="card_name" class="form-input" placeholder="NOME COMO ESTÁ NO CARTÃO" required>
                        </div>

                        <button type="submit" class="btn-pay" id="btnSubmitCard">
                            Adicionar Saldo
                        </button>
                    </form>

                    <div id="cardPaymentStatus" style="display: none;"></div>
                @else
                    <!-- PIX -->
                    @if($transaction && $transaction->payment_code_base64)
                        <div class="pix-qr-code">
                            <img src="data:image/png;base64,{{ $transaction->payment_code_base64 }}" alt="QR Code PIX">
                            
                            <div class="pix-code">
                                <button onclick="copyPixCode()" class="copy-btn">Copiar</button>
                                <div id="pixCodeText">{{ $transaction->payment_code }}</div>
                            </div>
                        </div>

                        <div id="paymentStatus" class="text-center">
                            <p class="text-gray-600 mb-4">Escaneie o QR Code ou copie o código PIX acima</p>
                            <p class="text-sm text-gray-500">Aguardando confirmação do pagamento...</p>
                        </div>
                    @else
                        <div class="error-message">
                            <p>Erro ao gerar QR Code PIX. Por favor, tente novamente.</p>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <script>
        // Função para copiar código PIX
        function copyPixCode() {
            const code = document.getElementById('pixCodeText').textContent;
            navigator.clipboard.writeText(code).then(function() {
                alert('Código PIX copiado!');
            });
        }

        // Processamento de pagamento com cartão
        @if($method === 'card')
        $(document).ready(function() {
            $('#cardPaymentForm').on('submit', function(e) {
                e.preventDefault();
                
                const btnSubmit = $('#btnSubmitCard');
                btnSubmit.prop('disabled', true).text('Processando...');
                
                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: $(this).serialize(),
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            if (response.redirect) {
                                window.location.href = response.redirect;
                            } else if (response.status === 'waiting_for_approval') {
                                $('#cardPaymentStatus').html(
                                    '<div class="text-center p-4 bg-yellow-50 border border-yellow-200 rounded-lg">' +
                                    '<p class="text-yellow-800 font-semibold">Pagamento em Análise</p>' +
                                    '<p class="text-yellow-600 text-sm mt-2">Seu pagamento está sendo analisado. Você será notificado quando for aprovado.</p>' +
                                    '</div>'
                                ).show();
                                btnSubmit.hide();
                                
                                // Inicia polling se tiver transaction_id
                                if (response.transaction_id) {
                                    startPaymentPolling(response.transaction_id);
                                }
                            }
                        } else {
                            alert(response.message || 'Erro ao processar pagamento.');
                            btnSubmit.prop('disabled', false).text('Adicionar Saldo');
                        }
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON;
                        alert(response?.message || 'Erro ao processar pagamento. Tente novamente.');
                        btnSubmit.prop('disabled', false).text('Adicionar Saldo');
                    }
                });
            });
        });
        @endif

        // Polling para verificar status do pagamento
        @if($method === 'pix' && $transaction)
        $(document).ready(function() {
            startPaymentPolling({{ $transaction->id }});
        });
        @endif

        function startPaymentPolling(transactionId) {
            const interval = setInterval(function() {
                $.ajax({
                    url: `/wallet/transaction/${transactionId}/status`,
                    method: 'GET',
                    success: function(response) {
                        if (response.status === 'paid_out') {
                            clearInterval(interval);
                            window.location.href = '{{ route("wallet.index") }}';
                        } else if (response.status === 'unpaid' || response.status === 'canceled') {
                            clearInterval(interval);
                            $('#paymentStatus').html(
                                '<div class="error-message">' +
                                '<p>Pagamento não confirmado. Tente novamente.</p>' +
                                '</div>'
                            );
                        }
                    },
                    error: function() {
                        // Continua tentando em caso de erro
                    }
                });
            }, 5000); // Verifica a cada 5 segundos
        }
    </script>
</body>
</html>






