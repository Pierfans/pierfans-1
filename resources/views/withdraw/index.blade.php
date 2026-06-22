<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Saque - {{ config('app.name', 'Laravel') }}</title>

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
        .withdraw-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.08);
        }

        .extract-item {
            padding: 16px 0;
            border-bottom: 1px solid #E2E8F0;
        }

        .extract-item:last-child {
            border-bottom: none;
        }

        .bank-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid rgba(0, 0, 0, 0.08);
            min-width: 200px;
            scroll-snap-align: start;
        }

        .bank-cards-container {
            display: flex;
            overflow-x: auto;
            gap: 12px;
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 8px;
        }

        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 50;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 16px;
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .input-field {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            font-size: 14px;
        }

        .input-field:focus {
            outline: none;
            border-color: #FF6B35;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-pending {
            background: #FEF3C7;
            color: #92400E;
        }

        .status-transferred {
            background: #D1FAE5;
            color: #065F46;
        }

        .status-rejected {
            background: #FEE2E2;
            color: #991B1B;
        }

        .status-complete {
            background: #DBEAFE;
            color: #1E40AF;
        }
    </style>
</head>
<body class="bg-[#FDFDFC] text-[#1b1b18] min-h-screen">
    <!-- Top Navigation (Desktop) -->
    <x-topnav />

    <!-- Bottom Navigation (Mobile) -->
    <x-bottomnav />

    <!-- Profile Drawer -->
    <x-profile-drawer />

    <!-- Main Content -->
    <div class="pt-0 md:pt-16 pb-16 md:pb-0">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-[#1b1b18] mb-2">Saque</h1>
                <p class="text-[#706f6c]">Gerencie seus saques e saldo disponível</p>
            </div>

            <!-- Card Nacional -->
            <div class="withdraw-card mb-6">
                <h2 class="text-lg font-semibold text-[#1b1b18] mb-6">Nacional</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <p class="text-sm text-[#706f6c] mb-2">Liberado para saque</p>
                        <p class="text-2xl font-bold text-[#1b1b18]" id="availableBalance">
                            R$ {{ number_format($availableBalance, 2, ',', '.') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-[#706f6c] mb-2">Saldo a liberar</p>
                        <p class="text-2xl font-bold text-[#1b1b18]">
                            R$ {{ number_format($pendingBalance, 2, ',', '.') }}
                        </p>
                    </div>
                </div>

                <button
                    id="withdrawButton"
                    class="w-full md:w-auto px-6 py-3 bg-[#FF6B35] text-white font-semibold rounded-lg hover:bg-[#E55A2B] transition-colors"
                >
                    Sacar
                </button>
            </div>

            <!-- Extrato -->
            <div class="withdraw-card mb-6">
                <h2 class="text-lg font-semibold text-[#1b1b18] mb-4">Extrato</h2>

                @if($extract->count() > 0)
                    <div class="space-y-0">
                        @foreach($extract as $item)
                            <div class="extract-item">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="font-medium text-[#1b1b18]">Saque</span>
                                            @if($item->status === 'pending')
                                                <span class="status-badge status-pending">Saque solicitado</span>
                                            @elseif($item->status === 'transferred')
                                                <span class="status-badge status-transferred">Saque aprovado</span>
                                            @elseif($item->status === 'rejected')
                                                <span class="status-badge status-rejected">Saque reprovado</span>
                                            @else
                                                <span class="status-badge status-complete">Completa</span>
                                            @endif
                                        </div>
                                        <p class="text-sm text-[#706f6c]">
                                            {{ $item->created_at->format('d M, Y, h:i A') }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-[#1b1b18]">
                                            R$ {{ number_format($item->amount, 2, ',', '.') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-[#706f6c] text-center py-8">
                        Nenhuma transação encontrada.
                    </p>
                @endif

                <a
                    href="{{ route('extract.index') }}"
                    class="mt-4 inline-block w-full md:w-auto text-center px-6 py-3 border-2 border-[#FF6B35] text-[#FF6B35] font-semibold rounded-lg hover:bg-[#FF6B35] hover:text-white transition-colors"
                >
                    Ver extrato completo
                </a>
            </div>

            <!-- Dados bancários -->
            <div class="withdraw-card">
                <h2 class="text-lg font-semibold text-[#1b1b18] mb-4">Dados bancários</h2>

                <div class="bank-cards-container">
                    <!-- Card Adicionar -->
                    <div class="bank-card cursor-pointer" onclick="openBankAccountModal()">
                        <div class="flex flex-col items-center justify-center h-full min-h-[120px]">
                            <div class="w-12 h-12 rounded-full bg-[#FF6B35] flex items-center justify-center mb-3">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-[#1b1b18] text-center">
                                Adicionar novo banco
                            </p>
                        </div>
                    </div>

                    <!-- Cards de contas cadastradas -->
                    @foreach($bankAccounts as $account)
                        <div class="bank-card cursor-pointer" onclick="openEditBankAccountModal({{ $account->id }})">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-[#1b1b18]">
                                        {{ $account->bank_name }}
                                    </h3>
                                    @if($account->is_primary)
                                        <p class="text-sm text-[#FF6B35] font-medium">Principal</p>
                                    @endif
                                </div>
                            </div>
                            <div class="mt-3 space-y-1">
                                <p class="text-xs text-[#706f6c]">
                                    Chave Pix: {{ ucfirst($account->pix_key_type) }}
                                </p>
                                <p class="text-xs text-[#706f6c]">
                                    {{ $account->pix_key }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Saque -->
    <div id="withdrawModal" class="modal-overlay" onclick="if(event.target === this && typeof closeWithdrawModal === 'function') closeWithdrawModal()">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-[#1b1b18]">Saque na hora</h2>
                    <button id="closeWithdrawModalXBtn" class="text-[#706f6c] hover:text-[#1b1b18]">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                @if($bankAccounts->count() === 0)
                    <!-- Mensagem quando não tem conta bancária -->
                    <div class="text-center py-8">
                        <div class="mb-4">
                            <svg class="w-16 h-16 mx-auto text-[#706f6c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-[#1b1b18] mb-2">
                            Cadastre uma conta bancária
                        </h3>
                        <p class="text-sm text-[#706f6c] mb-6">
                            Você precisa cadastrar uma conta bancária antes de fazer um saque.
                        </p>
                        <button
                            id="openBankAccountFromWithdrawBtn"
                            class="px-6 py-3 bg-[#FF6B35] text-white font-semibold rounded-lg hover:bg-[#E55A2B] transition-colors"
                        >
                            Cadastrar conta bancária
                        </button>
                    </div>
                @else
                    <!-- Formulário de saque -->
                    <p class="text-sm text-[#706f6c] mb-6">
                        Faça até {{ $dailyWithdrawLimit }} saques diários, sendo o primeiro inteiramente grátis. Os próximos saques do dia custam apenas R$ 3,50.
                    </p>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-[#FF6B35] mb-2">Disponível para saque</label>
                        <p class="text-2xl font-bold text-[#1b1b18]" id="modalAvailableBalance">
                            R$ {{ number_format($availableBalance, 2, ',', '.') }}
                        </p>
                    </div>

                    <form id="withdrawForm">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-[#1b1b18] mb-2">
                                VALOR
                            </label>
                            <input
                                type="text"
                                id="withdrawAmount"
                                name="amount"
                                class="input-field"
                                placeholder="R$ 0,00"
                                required
                            >
                        <p class="text-xs text-red-500 mt-1 hidden" id="amountError">
                            O valor mínimo para saque é de R$ {{ number_format($minWithdrawAmount, 2, ',', '.') }}
                        </p>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-[#1b1b18] mb-2">
                                CONTA DE DESTINO DO SAQUE
                            </label>
                            <select
                                id="withdrawBankAccount"
                                name="bank_account_id"
                                class="input-field"
                                required
                            >
                                <option value="">Selecione uma conta</option>
                                @foreach($bankAccounts as $account)
                                    <option value="{{ $account->id }}" {{ $account->is_primary ? 'selected' : '' }}>
                                        {{ $account->bank_name }} - {{ ucfirst($account->pix_key_type) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex gap-3">
                            <button
                                type="submit"
                                id="confirmWithdrawBtn"
                                class="flex-1 px-6 py-3 bg-[#F5E6D3] text-[#1b1b18] font-semibold rounded-lg hover:bg-[#E8D4B8] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                disabled
                            >
                                Confirmar
                            </button>
                            <button
                                type="button"
                                id="cancelWithdrawBtn"
                                class="flex-1 px-6 py-3 border-2 border-[#E2E8F0] text-[#1b1b18] font-semibold rounded-lg hover:bg-[#F7FAFC] transition-colors"
                            >
                                Cancelar
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Saque -->
    <div id="withdrawSuccessModal" class="modal-overlay" onclick="if(event.target === this && typeof closeWithdrawSuccessModal === 'function') closeWithdrawSuccessModal()">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="p-6">
                <div class="text-center">
                    <div class="mb-4">
                        <div class="w-16 h-16 mx-auto bg-[#D1FAE5] rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-[#065F46]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                    <h2 class="text-xl font-bold text-[#1b1b18] mb-2">
                        Saque solicitado com sucesso!
                    </h2>
                    <p class="text-sm text-[#706f6c] mb-6">
                        Sua solicitação de saque foi enviada e está em análise de liberação. Você receberá uma notificação quando o saque for processado.
                    </p>
                    <button
                        id="closeWithdrawSuccessBtn"
                        class="px-6 py-3 bg-[#FF6B35] text-white font-semibold rounded-lg hover:bg-[#E55A2B] transition-colors"
                    >
                        Entendi
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Adicionar Dados Bancários -->
    <div id="bankAccountModal" class="modal-overlay" onclick="if(event.target === this) closeBankAccountModal()">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-[#1b1b18]">ADICIONAR DADOS BANCÁRIOS</h2>
                    <button id="closeBankAccountModalXBtn" class="text-[#706f6c] hover:text-[#1b1b18]">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form id="bankAccountForm" onsubmit="window.submitBankAccount(event)">
                    <input type="hidden" name="account_id" id="bankAccountId" value="">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-[#1b1b18] mb-2">
                                BANCO
                            </label>
                            <input
                                type="text"
                                name="bank_name"
                                class="input-field"
                                placeholder="Banco"
                                required
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-[#1b1b18] mb-2">
                                TIPO DE CONTA
                            </label>
                            <select name="account_type" class="input-field" required>
                                <option value="">Selecione</option>
                                <option value="corrente">Corrente</option>
                                <option value="poupanca">Poupança</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-[#1b1b18] mb-2">
                                AGÊNCIA <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                name="agency"
                                class="input-field"
                                placeholder="0000"
                                required
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-[#1b1b18] mb-2">
                                NÚMERO DA CONTA (COM DÍGITO) <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                name="account_number"
                                class="input-field"
                                placeholder="0000000"
                                required
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-[#1b1b18] mb-2">
                                TIPO CHAVE PIX
                            </label>
                            <select name="pix_key_type" class="input-field" required>
                                <option value="">Selecione</option>
                                <option value="cpf">CPF</option>
                                <option value="email">Email</option>
                                <option value="telefone">Telefone</option>
                                <option value="aleatoria">Aleatória</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-[#1b1b18] mb-2">
                                CHAVE PIX <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                name="pix_key"
                                class="input-field"
                                placeholder="Selecione o tipo chave"
                                required
                            >
                        </div>
                    </div>

                    <input type="hidden" name="bank_code" value="000">
                    
                    <div class="mb-6 flex items-center">
                        <input type="checkbox" id="is_primary" name="is_primary" class="h-4 w-4 text-[#FF6B35] border-gray-300 rounded">
                        <label for="is_primary" class="ml-2 block text-sm text-[#1b1b18]">
                            Definir como conta principal
                        </label>
                    </div>

                    <div class="flex gap-3 mt-6" id="bankAccountFormActions">
                        <button
                            type="submit"
                            class="flex-1 px-6 py-3 bg-[#FF6B35] text-white font-semibold rounded-lg hover:bg-[#E55A2B] transition-colors"
                        >
                            Salvar
                        </button>
                        <button
                            type="button"
                            id="cancelBankAccountBtn"
                            class="flex-1 px-6 py-3 border-2 border-[#E2E8F0] text-[#1b1b18] font-semibold rounded-lg hover:bg-[#F7FAFC] transition-colors"
                        >
                            Cancelar
                        </button>
                    </div>
                    <div class="mt-4" id="deleteBankAccountSection" style="display: none;">
                        <button
                            type="button"
                            id="deleteBankAccountBtn"
                            class="w-full px-6 py-3 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition-colors"
                        >
                            Excluir Conta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Profile Overlay -->
    <x-profile-overlay />

    <script>
        // Define funções globalmente antes do DOM estar pronto
        const availableBalance = {{ $availableBalance }};
        const minWithdrawAmount = {{ $minWithdrawAmount }};
        const dailyWithdrawLimit = {{ $dailyWithdrawLimit }};
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        // Modal de Saque
        function openWithdrawModal() {
            const modal = document.getElementById('withdrawModal');
            if (!modal) {
                console.error('Modal withdrawModal não encontrado');
                return;
            }
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';

            // Garante que o event listener está configurado quando o modal abre
            const withdrawForm = document.getElementById('withdrawForm');
            if (withdrawForm) {
                // Remove listener anterior se existir (para evitar duplicação)
                const newForm = withdrawForm.cloneNode(true);
                withdrawForm.parentNode.replaceChild(newForm, withdrawForm);
                
                // Adiciona listener único ao novo formulário
                const form = document.getElementById('withdrawForm');
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    if (window.submitWithdraw) {
                        window.submitWithdraw(e);
                    } else {
                        console.error('submitWithdraw não está definida');
                    }
                });

                // Reconfigura o listener do input de valor após clonar o formulário
                const amountInput = document.getElementById('withdrawAmount');
                if (amountInput) {
                    amountInput.addEventListener('input', function(e) {
                        let value = e.target.value.replace(/[^\d]/g, '');
                        if (!value) {
                            e.target.value = '';
                            const errorEl = document.getElementById('amountError');
                            if (errorEl) {
                                errorEl.classList.add('hidden');
                            }
                            const confirmBtn = document.getElementById('confirmWithdrawBtn');
                            if (confirmBtn) {
                                confirmBtn.disabled = true;
                            }
                            return;
                        }

                        // Converte para número (centavos)
                        const numValue = parseFloat(value) / 100;

                        // Formata para exibição
                        e.target.value = window.formatCurrency ? window.formatCurrency(numValue) : formatCurrency(numValue);

                        // Validação
                        const errorEl = document.getElementById('amountError');
                        const confirmBtn = document.getElementById('confirmWithdrawBtn');

                        if (numValue < minWithdrawAmount) {
                            if (errorEl) {
                                errorEl.textContent = 'O valor mínimo para saque é de R$ ' + minWithdrawAmount.toFixed(2).replace('.', ',');
                                errorEl.classList.remove('hidden');
                            }
                            if (confirmBtn) {
                                confirmBtn.disabled = true;
                            }
                        } else if (numValue > availableBalance) {
                            if (errorEl) {
                                errorEl.textContent = 'Valor solicitado excede o saldo disponível.';
                                errorEl.classList.remove('hidden');
                            }
                            if (confirmBtn) {
                                confirmBtn.disabled = true;
                            }
                        } else {
                            if (errorEl) {
                                errorEl.classList.add('hidden');
                            }
                            if (confirmBtn) {
                                confirmBtn.disabled = false;
                            }
                        }
                    });
                }
            }
        }

        function closeWithdrawModal() {
            const modal = document.getElementById('withdrawModal');
            if (modal) {
                modal.classList.remove('active');
            }
            document.body.style.overflow = '';
            const form = document.getElementById('withdrawForm');
            if (form) {
                form.reset();
            }
            const errorEl = document.getElementById('amountError');
            if (errorEl) {
                errorEl.classList.add('hidden');
            }
            const confirmBtn = document.getElementById('confirmWithdrawBtn');
            if (confirmBtn) {
                confirmBtn.disabled = true;
            }
        }

        $(document).ready(function() {
            // Adiciona evento ao botão Sacar
            const withdrawButton = document.getElementById('withdrawButton');
            if (withdrawButton) {
                withdrawButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    openWithdrawModal();
                });
            }

            // Adiciona evento ao botão Cancelar
            const cancelWithdrawBtn = document.getElementById('cancelWithdrawBtn');
            if (cancelWithdrawBtn) {
                cancelWithdrawBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    closeWithdrawModal();
                });
            }

            // Adiciona evento ao botão X (fechar)
            const closeWithdrawModalXBtn = document.getElementById('closeWithdrawModalXBtn');
            if (closeWithdrawModalXBtn) {
                closeWithdrawModalXBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    closeWithdrawModal();
                });
            }

            // Adiciona evento ao botão "Cadastrar conta bancária" dentro do modal
            const openBankAccountFromWithdrawBtn = document.getElementById('openBankAccountFromWithdrawBtn');
            if (openBankAccountFromWithdrawBtn) {
                openBankAccountFromWithdrawBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    closeWithdrawModal();
                    openBankAccountModal();
                });
            }

            // Adiciona evento ao botão "Entendi" do modal de sucesso
            const closeWithdrawSuccessBtn = document.getElementById('closeWithdrawSuccessBtn');
            if (closeWithdrawSuccessBtn) {
                closeWithdrawSuccessBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (window.closeWithdrawSuccessModal) {
                        window.closeWithdrawSuccessModal();
                    }
                });
            }

            // Event listener removido - já está sendo adicionado diretamente no formulário quando o modal abre

            // Validação de valor em tempo real
            const amountInput = document.getElementById('withdrawAmount');
            if (amountInput) {
                amountInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/[^\d]/g, '');
                if (!value) {
                    e.target.value = '';
                    document.getElementById('amountError').classList.add('hidden');
                    document.getElementById('confirmWithdrawBtn').disabled = true;
                    return;
                }

                // Converte para número (centavos)
                const numValue = parseFloat(value) / 100;

                // Formata para exibição
                e.target.value = window.formatCurrency ? window.formatCurrency(numValue) : formatCurrency(numValue);

                // Validação
                const errorEl = document.getElementById('amountError');
                const confirmBtn = document.getElementById('confirmWithdrawBtn');

                if (numValue < minWithdrawAmount) {
                    errorEl.textContent = 'O valor mínimo para saque é de R$ ' + minWithdrawAmount.toFixed(2).replace('.', ',');
                    errorEl.classList.remove('hidden');
                    confirmBtn.disabled = true;
                } else if (numValue > availableBalance) {
                    errorEl.textContent = 'Valor solicitado excede o saldo disponível.';
                    errorEl.classList.remove('hidden');
                    confirmBtn.disabled = true;
                } else {
                    errorEl.classList.add('hidden');
                    confirmBtn.disabled = false;
                    }
                });
            }

            window.formatCurrency = function(value) {
                if (!value) return '';
                const num = parseFloat(value) || 0;
                return 'R$ ' + num.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            };

            // Flag para evitar múltiplas submissões simultâneas
            let isSubmitting = false;

            window.submitWithdraw = function(e) {
                e.preventDefault();

                // Previne múltiplas submissões simultâneas
                if (isSubmitting) {
                    console.log('Submissão já em andamento, ignorando...');
                    return;
                }

                const form = e.target;
                const formData = new FormData(form);
                const amountStr = formData.get('amount').replace(/[^\d,]/g, '').replace(',', '.');
                const amount = parseFloat(amountStr);

                if (isNaN(amount) || amount < minWithdrawAmount) {
                    alert('O valor mínimo para saque é de R$ ' + minWithdrawAmount.toFixed(2).replace('.', ','));
                    return;
                }

                if (amount > availableBalance) {
                    alert('Valor solicitado excede o saldo disponível.');
                    return;
                }

                // Marca como submetendo
                isSubmitting = true;

                // Desabilita o botão durante o processamento
                const confirmBtn = document.getElementById('confirmWithdrawBtn');
                if (confirmBtn) {
                    confirmBtn.disabled = true;
                    confirmBtn.textContent = 'Processando...';
                }

                fetch('{{ route("withdraw.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        amount: amount,
                        bank_account_id: formData.get('bank_account_id'),
                    }),
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response ok:', response.ok);

                    // Primeiro, tenta fazer parse do JSON
                    return response.json().then(data => {
                        console.log('Response data:', data);

                        // Se a resposta não foi OK (status 400, 500, etc), lança erro
                        if (!response.ok) {
                            console.error('Response não OK:', data);
                            throw new Error(data.message || 'Erro ao processar solicitação.');
                        }
                        // Se response.ok é true, retorna os dados
                        return data;
                    }).catch(jsonError => {
                        // Se não conseguir fazer parse do JSON, lança erro
                        // console.error('Erro ao fazer parse da resposta:', jsonError);
                        // throw new Error('Erro ao processar resposta do servidor.');
                    });
                })
                .then(data => {
                    console.log('Processando resposta:', data);

                    // Verifica se realmente foi sucesso
                    if (data && data.success === true) {
                        console.log('Saque criado com sucesso, abrindo modal de confirmação');
                        // Libera flag de submissão
                        isSubmitting = false;
                        // Fecha o modal de saque
                        closeWithdrawModal();
                        // Abre o modal de confirmação
                        if (window.openWithdrawSuccessModal) {
                            window.openWithdrawSuccessModal();
                        }
                    } else {
                        console.error('Resposta não foi sucesso:', data);
                        // Se não foi sucesso, mostra alert e reabilita botão
                        const errorMessage = data?.message || 'Erro ao processar solicitação.';
                        alert(errorMessage);
                        if (confirmBtn) {
                            confirmBtn.disabled = false;
                            confirmBtn.textContent = 'Confirmar';
                        }
                        // Libera flag de submissão
                        isSubmitting = false;
                    }
                })
                .catch(error => {
                    console.error('Error capturado:', error);
                    // Em caso de erro, mostra alert e reabilita botão
                    // NÃO mostra o modal de sucesso
                    alert(error.message || 'Erro ao processar solicitação. Tente novamente.');
                    if (confirmBtn) {
                        confirmBtn.disabled = false;
                        confirmBtn.textContent = 'Confirmar';
                    }
                    // Libera flag de submissão
                    isSubmitting = false;
                });
            };

            // Modal de Confirmação
            window.openWithdrawSuccessModal = function() {
                const modal = document.getElementById('withdrawSuccessModal');
                if (modal) {
                    modal.classList.add('active');
                    document.body.style.overflow = 'hidden';
                }
            };

            window.closeWithdrawSuccessModal = function() {
                const modal = document.getElementById('withdrawSuccessModal');
                if (modal) {
                    modal.classList.remove('active');
                }
                document.body.style.overflow = '';
                // Recarrega a página após fechar o modal
                location.reload();
            };

            // Modal de Conta Bancária - Funções globais
        window.openBankAccountModal = function() {
            const modal = document.getElementById('bankAccountModal');
            if (modal) {
                // Limpa o formulário e reseta para modo de criação
                const form = document.getElementById('bankAccountForm');
                if (form) {
                    form.reset();
                    document.getElementById('bankAccountId').value = '';
                }
                const modalTitle = document.querySelector('#bankAccountModal h2');
                if (modalTitle) {
                    modalTitle.textContent = 'ADICIONAR DADOS BANCÁRIOS';
                }
                document.getElementById('deleteBankAccountSection').style.display = 'none';
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        };

        window.openEditBankAccountModal = function(accountId) {
            const modal = document.getElementById('bankAccountModal');
            if (!modal) return;

            // Busca os dados da conta
            fetch(`/withdraw/bank-account/${accountId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.bank_account) {
                        const account = data.bank_account;
                        const form = document.getElementById('bankAccountForm');
                        
                        // Preenche o formulário
                        document.getElementById('bankAccountId').value = account.id;
                        form.elements.bank_name.value = account.bank_name || '';
                        form.elements.account_type.value = account.account_type || '';
                        form.elements.agency.value = account.agency || '';
                        form.elements.account_number.value = account.account_number || '';
                        form.elements.pix_key_type.value = account.pix_key_type || '';
                        form.elements.pix_key.value = account.pix_key || '';
                        form.elements.is_primary.checked = account.is_primary || false;
                        
                        // Atualiza título e mostra botão de deletar
                        const modalTitle = document.querySelector('#bankAccountModal h2');
                        if (modalTitle) {
                            modalTitle.textContent = 'EDITAR DADOS BANCÁRIOS';
                        }
                        document.getElementById('deleteBankAccountSection').style.display = 'block';
                        
                        modal.classList.add('active');
                        document.body.style.overflow = 'hidden';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Erro ao carregar dados da conta bancária.');
                });
        };

        window.closeBankAccountModal = function() {
            const modal = document.getElementById('bankAccountModal');
            if (modal) {
                modal.classList.remove('active');
            }
            document.body.style.overflow = '';
            const form = document.getElementById('bankAccountForm');
            if (form) {
                form.reset();
                document.getElementById('bankAccountId').value = '';
            }
            document.getElementById('deleteBankAccountSection').style.display = 'none';
        };

            window.submitBankAccount = function(e) {
                e.preventDefault();

                const form = e.target;
                const formData = new FormData(form);
                const accountId = document.getElementById('bankAccountId').value;
                const data = Object.fromEntries(formData);
                
                // Converte is_primary para boolean
                data.is_primary = form.elements.is_primary.checked;
                
                // Remove account_id do data se estiver vazio
                if (!accountId) {
                    delete data.account_id;
                }

                const url = accountId 
                    ? `/withdraw/bank-account/${accountId}`
                    : '{{ route("withdraw.bank-account.store") }}';
                const method = accountId ? 'PUT' : 'POST';

                fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(data),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(accountId ? 'Conta bancária atualizada com sucesso!' : 'Conta bancária cadastrada com sucesso!');
                        closeBankAccountModal();
                        location.reload();
                    } else {
                        alert(data.message || 'Erro ao salvar conta bancária.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Erro ao salvar conta bancária. Tente novamente.');
                });
            };

        window.deleteBankAccount = function() {
            const accountId = document.getElementById('bankAccountId').value;
            if (!accountId) return;

            if (!confirm('Tem certeza que deseja excluir esta conta bancária?')) {
                return;
            }

            fetch(`/withdraw/bank-account/${accountId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Conta bancária excluída com sucesso!');
                    closeBankAccountModal();
                    location.reload();
                } else {
                    alert(data.message || 'Erro ao excluir conta bancária.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erro ao excluir conta bancária. Tente novamente.');
            });
        };

            // Event listeners para modal de conta bancária
            const closeBankAccountModalXBtn = document.getElementById('closeBankAccountModalXBtn');
            if (closeBankAccountModalXBtn) {
                closeBankAccountModalXBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    closeBankAccountModal();
                });
            }

            const cancelBankAccountBtn = document.getElementById('cancelBankAccountBtn');
            if (cancelBankAccountBtn) {
                cancelBankAccountBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    closeBankAccountModal();
                });
            }

            const deleteBankAccountBtn = document.getElementById('deleteBankAccountBtn');
            if (deleteBankAccountBtn) {
                deleteBankAccountBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    deleteBankAccount();
                });
            }

            // Fechar modais com ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeWithdrawModal();
                    closeWithdrawSuccessModal();
                    closeBankAccountModal();
                }
            });
        });
    </script>
</body>
</html>
