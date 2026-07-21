<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Checkout - {{ $creator->name }} - {{ config('app.name', 'Laravel') }}</title>

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
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 1px solid #E5E5E5;
        }

        .creator-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #FF6B35;
        }

        .creator-avatar-placeholder {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #E5E5E5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: #706f6c;
            font-weight: 600;
            border: 3px solid #FF6B35;
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

        .plan-info {
            background: #F5F5F5;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }

        .plan-name {
            font-size: 18px;
            font-weight: 600;
            color: #1b1b18;
            margin-bottom: 8px;
        }

        .plan-price {
            font-size: 32px;
            font-weight: 700;
            color: #FF6B35;
            margin-bottom: 4px;
        }

        .plan-billing {
            font-size: 14px;
            color: #706f6c;
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

        .pix-qr-code-placeholder {
            width: 256px;
            height: 256px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px dashed #E5E5E5;
            color: #706f6c;
            font-size: 14px;
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
                <!-- Header com foto e nome do criador -->
                <div class="checkout-header">
                    @if($creator->profile_photo)
                        <img src="{{ $creator->profile_photo_url }}" alt="{{ $creator->name }}" class="creator-avatar">
                    @else
                        <div class="creator-avatar-placeholder">
                            {{ strtoupper(substr($creator->name, 0, 2)) }}
                        </div>
                    @endif
                    <div>
                        <h1 class="checkout-title">{{ $creator->name }}</h1>
                        <p class="checkout-subtitle">Assinatura de Conteúdo</p>
                    </div>
                </div>

                <!-- Informações do Plano -->
                <div class="plan-info">
                    <div class="plan-name">{{ $plan->name }}</div>
                    <div class="plan-price">R$ {{ number_format($plan->price, 2, ',', '.') }}</div>
                    @php
                        $billingText = '';
                        if ($plan->duration_days === 30) {
                            $billingText = 'Cobrança realizada a cada mês';
                        } else if ($plan->duration_days === 90) {
                            $billingText = 'Cobrança realizada a cada 3 meses';
                        } else if ($plan->duration_days === 180) {
                            $billingText = 'Cobrança realizada a cada 6 meses';
                        } else if ($plan->duration_days === 365) {
                            $billingText = 'Cobrança realizada anualmente';
                        } else {
                            $billingText = "Cobrança realizada a cada {$plan->duration_days} dias";
                        }
                    @endphp
                    <div class="plan-billing">{{ $billingText }}</div>
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

                {{-- Pagar com saldo: só aparece se o saldo cobre o plano INTEIRO (tudo ou nada). --}}
                @if($walletBalance >= $plan->price)
                    <div style="border:1px solid #d1fae5;background:#ecfdf5;border-radius:10px;padding:16px;margin-bottom:20px">
                        <div style="font-weight:600;color:#065f46;margin-bottom:4px">
                            Você tem R$ {{ number_format($walletBalance, 2, ',', '.') }} de saldo
                        </div>
                        <div style="font-size:13px;color:#047857;margin-bottom:12px">
                            Dá para assinar agora usando o saldo, sem PIX nem cartão.
                        </div>
                        <button type="button" id="btnPagarSaldo"
                                data-url="{{ route('checkout.process', ['planId' => $plan->id, 'method' => 'wallet']) }}"
                                style="width:100%;padding:12px;background:#059669;color:#fff;border:0;border-radius:8px;font-weight:600;cursor:pointer">
                            Pagar R$ {{ number_format($plan->price, 2, ',', '.') }} com meu saldo
                        </button>
                        <div id="erroSaldo" style="display:none;margin-top:10px;font-size:13px;color:#b91c1c"></div>
                    </div>
                @endif

                @if($method === 'card')
                    <!-- Formulário de Cartão -->
                    <form id="cardPaymentForm" method="POST" action="{{ route('checkout.process', ['planId' => $plan->id, 'method' => 'card']) }}">
                        @csrf

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
                            <input type="text" name="card_name" class="form-input" placeholder="Nome como está no cartão" required>
                        </div>

                        <button type="submit" class="btn-pay">
                            Finalizar Pagamento
                        </button>
                    </form>
                @else
                    <!-- PIX -->
                    @if(isset($transaction) && $transaction->payment_code_base64)
                        <!-- QR Code já gerado automaticamente -->
                        <div class="pix-qr-code">
                            <img src="data:image/png;base64,{{ $transaction->payment_code_base64 }}" 
                                 alt="QR Code PIX" 
                                 style="width: 256px; height: 256px; margin: 0 auto; display: block; border-radius: 8px;">
                            <p style="margin-top: 16px; color: #706f6c; font-size: 14px; text-align: center;">
                                Escaneie o QR Code com o app do seu banco para pagar
                            </p>
                        </div>

                        <!-- Campo para copiar código PIX -->
                        <div class="form-group" style="margin-top: 24px;">
                            <label class="form-label">Código PIX (Copiar e Colar)</label>
                            <div style="display: flex; gap: 8px;">
                                <input type="text" 
                                       id="pixPaymentCode" 
                                       value="{{ $transaction->payment_code }}" 
                                       readonly 
                                       class="form-input"
                                       style="font-family: monospace; font-size: 12px;">
                                <button type="button" 
                                        onclick="copyPixCode()" 
                                        class="btn-pay"
                                        style="width: auto; padding: 12px 24px; margin-top: 0;">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px;">
                                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                    </svg>
                                    Copiar
                                </button>
                            </div>
                        </div>

                        <!-- Mensagem de status (será atualizada pelo polling) -->
                        <div id="paymentStatusMessage" style="background: #F0F9FF; border: 1px solid #BAE6FD; border-radius: 8px; padding: 16px; margin-top: 24px;">
                            <p id="statusText" style="color: #0369A1; font-size: 14px; margin: 0; text-align: center;">
                                ⏳ Aguardando confirmação do pagamento. Você será redirecionado automaticamente quando o pagamento for confirmado.
                            </p>
                        </div>

                        <!-- Inicia polling automaticamente -->
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                startPaymentPolling({{ $transaction->id }});
                            });
                        </script>
                    @else
                        <!-- Erro ao gerar QR Code -->
                        <div class="pix-qr-code">
                            <div class="pix-qr-code-placeholder" style="color: #DC2626;">
                                ⚠️ Erro ao gerar QR Code PIX
                            </div>
                            <p style="margin-top: 16px; color: #706f6c; font-size: 14px; text-align: center;">
                                Por favor, recarregue a página ou tente novamente mais tarde.
                            </p>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <!-- Profile Overlay -->
    <x-profile-overlay />

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Pagar com saldo da carteira: desabilita no primeiro clique (o servidor também trava
        // com lock, isto aqui é só pra não piscar dois pedidos).
        document.getElementById('btnPagarSaldo')?.addEventListener('click', async function () {
            const btn = this;
            const erro = document.getElementById('erroSaldo');
            btn.disabled = true;
            btn.style.opacity = '0.6';
            btn.textContent = 'Processando...';
            erro.style.display = 'none';
            try {
                const r = await fetch(btn.dataset.url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                });
                const data = await r.json();
                if (data.success && data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }
                erro.textContent = data.message || 'Não foi possível concluir.';
                erro.style.display = 'block';
            } catch (e) {
                erro.textContent = 'Falha de conexão. Tente novamente.';
                erro.style.display = 'block';
            }
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.textContent = 'Pagar com meu saldo';
        });

        // Máscara para número do cartão
        document.querySelector('input[name="card_number"]')?.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            e.target.value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
        });

        // Máscara para validade
        document.querySelector('input[name="card_expiry"]')?.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 4) {
                e.target.value = value.replace(/(\d{2})(\d{2})/, '$1/$2');
            }
        });

        // Máscara para CVV
        document.querySelector('input[name="card_cvv"]')?.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '');
        });

        // Processamento do pagamento via AJAX
        function processPayment(formId) {
            const form = document.getElementById(formId);
            const submitBtn = form.querySelector('button[type="submit"]');
            
            // Desabilita o botão durante o processamento
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processando...';

            const formData = new FormData(form);
            formData.append('_token', csrfToken);

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Se for PIX e retornou transação, mostra QR Code
                    if (formId === 'pixPaymentForm' && data.transaction) {
                        showPixQrCode(data.transaction);
                    } else if (formId === 'cardPaymentForm') {
                        // Processa resposta do cartão
                        handleCardPaymentResponse(data);
                    } else if (data.redirect) {
                        // Redireciona para tela de sucesso
                        window.location.href = data.redirect;
                    }
                } else {
                    if (data.redirect) {
                        // Redireciona para formulário de identificação se necessário
                        window.location.href = data.redirect;
                    } else {
                        // Mostra mensagem de erro
                        showCardPaymentError(data.message || 'Erro ao processar pagamento. Tente novamente.', data.details);
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Finalizar Pagamento';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erro ao processar pagamento. Tente novamente.');
                submitBtn.disabled = false;
                submitBtn.textContent = formId === 'cardPaymentForm' ? 'Finalizar Pagamento' : 'Gerar QR Code PIX';
            });
        }

        // Mostra QR Code PIX após gerar
        function showPixQrCode(transaction) {
            const pixSection = document.querySelector('.pix-qr-code');
            const form = document.getElementById('pixPaymentForm');
            
            if (!pixSection || !form) return;

            // Substitui o placeholder pelo QR Code
            pixSection.innerHTML = `
                <img src="data:image/png;base64,${transaction.payment_code_base64}" 
                     alt="QR Code PIX" 
                     style="width: 256px; height: 256px; margin: 0 auto; display: block; border-radius: 8px;">
                <p style="margin-top: 16px; color: #706f6c; font-size: 14px; text-align: center;">
                    Escaneie o QR Code com o app do seu banco para pagar
                </p>
            `;

            // Adiciona campo para copiar código
            const formGroup = document.createElement('div');
            formGroup.className = 'form-group';
            formGroup.style.marginTop = '24px';
            formGroup.innerHTML = `
                <label class="form-label">Código PIX (Copiar e Colar)</label>
                <div style="display: flex; gap: 8px;">
                    <input type="text" 
                           id="pixPaymentCode" 
                           value="${transaction.payment_code}" 
                           readonly 
                           class="form-input"
                           style="font-family: monospace; font-size: 12px;">
                    <button type="button" 
                            onclick="copyPixCode()" 
                            class="btn-pay"
                            style="width: auto; padding: 12px 24px; margin-top: 0;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px;">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                        </svg>
                        Copiar
                    </button>
                </div>
            `;

            // Adiciona mensagem de aguardando
            const waitingDiv = document.createElement('div');
            waitingDiv.style.cssText = 'background: #F0F9FF; border: 1px solid #BAE6FD; border-radius: 8px; padding: 16px; margin-top: 24px;';
            waitingDiv.innerHTML = `
                <p style="color: #0369A1; font-size: 14px; margin: 0; text-align: center;">
                    ⏳ Aguardando confirmação do pagamento. Você será redirecionado automaticamente quando o pagamento for confirmado.
                </p>
            `;

            // Insere antes do formulário
            form.parentNode.insertBefore(formGroup, form);
            form.parentNode.insertBefore(waitingDiv, form);

            // Remove o formulário (não é mais necessário)
            form.remove();

            // Inicia polling para verificar status do pagamento
            startPaymentPolling(transaction.id);
        }

        // Copia código PIX (função global)
        window.copyPixCode = function() {
            const input = document.getElementById('pixPaymentCode');
            if (!input) return;

            input.select();
            input.setSelectionRange(0, 99999); // Para mobile
            document.execCommand('copy');

            // Feedback visual
            const button = event.target.closest('button');
            if (button) {
                const originalText = button.innerHTML;
                button.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px;"><polyline points="20 6 9 17 4 12"></polyline></svg> Copiado!';
                button.style.background = '#10B981';
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.background = '#FF6B35';
                }, 2000);
            }
        }

        // Polling para verificar status do pagamento
        let pollingInterval = null;
        function startPaymentPolling(transactionId) {
            // Verifica a cada 5 segundos
            pollingInterval = setInterval(() => {
                fetch(`/checkout/transaction/${transactionId}/status`, {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    updatePaymentStatusMessage(data.status);
                    
                    // Se pagamento confirmado e assinatura criada, redireciona
                    if (data.status === 'paid_out' && data.subscription_id) {
                        clearInterval(pollingInterval);
                        // Pequeno delay para mostrar mensagem de sucesso
                        setTimeout(() => {
                            window.location.href = `/checkout/success/${data.subscription_id}`;
                        }, 1000);
                    }
                })
                .catch(error => {
                    console.error('Error checking payment status:', error);
                });
            }, 5000);

            // Para o polling após 30 minutos
            setTimeout(() => {
                if (pollingInterval) {
                    clearInterval(pollingInterval);
                }
            }, 30 * 60 * 1000);
        }

        // Processa resposta do pagamento com cartão
        function handleCardPaymentResponse(data) {
            const form = document.getElementById('cardPaymentForm');
            if (!form) return;

            if (data.status === 'paid_out' && data.redirect) {
                // Pagamento aprovado imediatamente - redireciona
                window.location.href = data.redirect;
            } else if (data.status === 'waiting_for_approval' && data.transaction_id) {
                // Pagamento em análise - mostra mensagem e inicia polling
                showCardPaymentWaiting(data.transaction_id);
            } else {
                // Erro ou status não esperado
                showCardPaymentError(data.message || 'Erro ao processar pagamento.', data.details);
            }
        }

        // Mostra mensagem de pagamento em análise e inicia polling
        function showCardPaymentWaiting(transactionId) {
            const form = document.getElementById('cardPaymentForm');
            if (!form) return;

            // Esconde o formulário
            form.style.display = 'none';

            // Cria mensagem de aguardando
            const waitingDiv = document.createElement('div');
            waitingDiv.id = 'cardPaymentWaiting';
            waitingDiv.style.cssText = 'background: #F0F9FF; border: 1px solid #BAE6FD; border-radius: 8px; padding: 24px; margin-top: 24px; text-align: center;';
            waitingDiv.innerHTML = `
                <div style="margin-bottom: 16px;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#0369A1" stroke-width="2" style="margin: 0 auto;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
                <h3 style="color: #0369A1; font-size: 18px; font-weight: 600; margin-bottom: 8px;">
                    Pagamento em Análise
                </h3>
                <p id="cardStatusText" style="color: #0369A1; font-size: 14px; margin: 0;">
                    Seu pagamento está sendo processado. Você será redirecionado automaticamente quando for confirmado.
                </p>
            `;

            form.parentNode.insertBefore(waitingDiv, form);

            // Inicia polling para verificar status
            startPaymentPolling(transactionId);
        }

        // Mostra mensagem de erro no pagamento com cartão
        function showCardPaymentError(message, details = null) {
            const form = document.getElementById('cardPaymentForm');
            if (!form) return;

            // Remove mensagens de erro anteriores
            const existingError = document.getElementById('cardPaymentError');
            if (existingError) {
                existingError.remove();
            }

            // Cria mensagem de erro
            const errorDiv = document.createElement('div');
            errorDiv.id = 'cardPaymentError';
            errorDiv.style.cssText = 'background: #FEF2F2; border: 1px solid #FCA5A5; border-radius: 8px; padding: 16px; margin-bottom: 24px;';
            errorDiv.innerHTML = `
                <div style="display: flex; align-items: start; gap: 12px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2" style="flex-shrink: 0; margin-top: 2px;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <div style="flex: 1;">
                        <p style="color: #991B1B; font-size: 14px; font-weight: 600; margin: 0 0 4px 0;">
                            ${message}
                        </p>
                        ${details ? `<p style="color: #991B1B; font-size: 12px; margin: 0; opacity: 0.8;">${details}</p>` : ''}
                    </div>
                </div>
            `;

            form.insertBefore(errorDiv, form.firstChild);
        }

        // Atualiza mensagem de status baseado no status atual
        function updatePaymentStatusMessage(status) {
            const statusMessage = document.getElementById('paymentStatusMessage');
            const statusText = document.getElementById('statusText');
            
            if (!statusMessage || !statusText) return;

            switch(status) {
                case 'paid_out':
                    statusMessage.style.background = '#F0FDF4';
                    statusMessage.style.borderColor = '#86EFAC';
                    statusText.innerHTML = '✅ Pagamento confirmado! Redirecionando...';
                    statusText.style.color = '#166534';
                    break;
                    
                case 'unpaid':
                    statusMessage.style.background = '#FEF2F2';
                    statusMessage.style.borderColor = '#FCA5A5';
                    statusText.innerHTML = '❌ Pagamento não realizado ou recusado. Por favor, tente novamente.';
                    statusText.style.color = '#991B1B';
                    // Para o polling se não foi pago
                    if (pollingInterval) {
                        clearInterval(pollingInterval);
                    }
                    break;
                    
                case 'canceled':
                    statusMessage.style.background = '#FFFBEB';
                    statusMessage.style.borderColor = '#FCD34D';
                    statusText.innerHTML = '⚠️ Pagamento cancelado. Por favor, tente novamente.';
                    statusText.style.color = '#92400E';
                    // Para o polling se foi cancelado
                    if (pollingInterval) {
                        clearInterval(pollingInterval);
                    }
                    break;
                    
                case 'waiting_for_approval':
                    statusMessage.style.background = '#F0F9FF';
                    statusMessage.style.borderColor = '#BAE6FD';
                    statusText.innerHTML = '⏳ Pagamento em análise. Aguarde a confirmação...';
                    statusText.style.color = '#0369A1';
                    break;
                    
                case 'pending':
                default:
                    statusMessage.style.background = '#F0F9FF';
                    statusMessage.style.borderColor = '#BAE6FD';
                    statusText.innerHTML = '⏳ Aguardando confirmação do pagamento. Você será redirecionado automaticamente quando o pagamento for confirmado.';
                    statusText.style.color = '#0369A1';
                    break;
            }
        }

        // Atualiza mensagem de status do cartão (quando em análise)
        function updateCardPaymentStatus(status) {
            const statusText = document.getElementById('cardStatusText');
            if (!statusText) return;

            switch(status) {
                case 'paid_out':
                    statusText.innerHTML = '✅ Pagamento confirmado! Redirecionando...';
                    break;
                case 'unpaid':
                    statusText.innerHTML = '❌ Pagamento recusado. Por favor, tente novamente com outro cartão.';
                    break;
                case 'canceled':
                    statusText.innerHTML = '⚠️ Pagamento cancelado. Por favor, tente novamente.';
                    break;
                case 'waiting_for_approval':
                default:
                    statusText.innerHTML = '⏳ Seu pagamento está sendo processado. Você será redirecionado automaticamente quando for confirmado.';
                    break;
            }
        }

        // Event listeners para os formulários
        document.getElementById('cardPaymentForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            processPayment('cardPaymentForm');
        });

        // Formulário PIX não é mais necessário (QR Code é gerado automaticamente)
        // Mas mantemos caso precise de fallback
        document.getElementById('pixPaymentForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            processPayment('pixPaymentForm');
        });
    </script>
</body>
</html>

