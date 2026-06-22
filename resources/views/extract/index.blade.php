<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Extrato - {{ config('app.name', 'Laravel') }}</title>

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
        .extract-card {
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

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-complete {
            background: #D1FAE5;
            color: #065F46;
        }

        .status-pending {
            background: #FEF3C7;
            color: #92400E;
        }

        .status-rejected {
            background: #FEE2E2;
            color: #991B1B;
        }

        .status-active {
            background: #DBEAFE;
            color: #1E40AF;
        }

        .status-inactive {
            background: #F3F4F6;
            color: #6B7280;
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
                <h1 class="text-3xl font-bold text-[#1b1b18] mb-2">Extrato Completo</h1>
                <p class="text-[#706f6c]">Visualize todas as suas transações</p>
            </div>

            <!-- Card Saldo a liberar -->
            <div class="extract-card mb-6">
                <h2 class="text-lg font-semibold text-[#1b1b18] mb-2">Saldo a liberar</h2>
                <p class="text-3xl font-bold text-[#1b1b18]">
                    R$ {{ number_format($pendingBalance, 2, ',', '.') }}
                </p>
            </div>

            <!-- Filtros -->
            <div class="extract-card mb-6">
                <h2 class="text-lg font-semibold text-[#1b1b18] mb-4">Filtros</h2>
                
                <form method="GET" action="{{ route('extract.index') }}" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Data Inicial -->
                        <div>
                            <label class="block text-sm font-medium text-[#1b1b18] mb-2">
                                DATA - Data inicial
                            </label>
                            <input 
                                type="date" 
                                name="start_date" 
                                value="{{ $startDate }}"
                                class="input-field"
                            >
                        </div>

                        <!-- Data Final -->
                        <div>
                            <label class="block text-sm font-medium text-[#1b1b18] mb-2">
                                DATA - Data final
                            </label>
                            <input 
                                type="date" 
                                name="end_date" 
                                value="{{ $endDate }}"
                                class="input-field"
                            >
                        </div>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-[#1b1b18] mb-2">
                            STATUS
                        </label>
                        <select name="status" class="input-field">
                            <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Todos</option>
                            <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pendente</option>
                            <option value="transferred" {{ $status === 'transferred' ? 'selected' : '' }}>Completa</option>
                            <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Reprovado</option>
                        </select>
                    </div>

                    <div class="flex gap-3">
                        <button 
                            type="submit" 
                            class="px-6 py-3 bg-[#FF6B35] text-white font-semibold rounded-lg hover:bg-[#E55A2B] transition-colors"
                        >
                            Filtrar
                        </button>
                        <a 
                            href="{{ route('extract.index') }}" 
                            class="px-6 py-3 border-2 border-[#E2E8F0] text-[#1b1b18] font-semibold rounded-lg hover:bg-[#F7FAFC] transition-colors"
                        >
                            Limpar
                        </a>
                    </div>
                </form>
            </div>

            <!-- Lista de Transações -->
            <div class="extract-card">
                <h2 class="text-lg font-semibold text-[#1b1b18] mb-4">Transações</h2>
                
                @if($transactions->count() > 0)
                    <div class="space-y-0">
                        @foreach($transactions as $transaction)
                            <div class="extract-item">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="font-medium text-[#1b1b18]">{{ $transaction['type_label'] }}</span>
                                            @if($transaction['type'] === 'withdrawal')
                                                @if($transaction['status'] === 'pending')
                                                    <span class="status-badge status-pending">{{ $transaction['status_label'] }}</span>
                                                @elseif($transaction['status'] === 'transferred')
                                                    <span class="status-badge status-complete">{{ $transaction['status_label'] }}</span>
                                                @elseif($transaction['status'] === 'rejected')
                                                    <span class="status-badge status-rejected">{{ $transaction['status_label'] }}</span>
                                                @endif
                                            @else
                                                @if($transaction['status'] === 'active')
                                                    <span class="status-badge status-active">{{ $transaction['status_label'] }}</span>
                                                @else
                                                    <span class="status-badge status-inactive">{{ $transaction['status_label'] }}</span>
                                                @endif
                                            @endif
                                        </div>
                                        <p class="text-sm text-[#706f6c]">
                                            {{ $transaction['date']->format('d M, Y, h:i A') }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <div class="text-right">
                                            <p class="font-semibold text-[#1b1b18]">
                                                R$ {{ number_format($transaction['amount'], 2, ',', '.') }}
                                            </p>
                                        </div>
                                        <button 
                                            onclick="openDetailsModal('{{ $transaction['id'] }}', '{{ $transaction['type'] }}')"
                                            class="text-[#FF6B35] hover:underline text-sm font-medium"
                                        >
                                            Detalhes
                                        </button>
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
            </div>
        </div>
    </div>

    <!-- Modal de Detalhes -->
    <div id="detailsModal" class="modal-overlay" onclick="if(event.target === this) closeDetailsModal()">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-[#1b1b18]">Resumo da transação</h2>
                    <button id="closeDetailsModalBtn" class="text-[#706f6c] hover:text-[#1b1b18]">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div id="detailsContent">
                    <!-- Conteúdo será preenchido via JavaScript -->
                </div>

                <div class="mt-6 flex justify-end">
                    <button 
                        onclick="closeDetailsModal()"
                        class="px-6 py-3 bg-[#FF6B35] text-white font-semibold rounded-lg hover:bg-[#E55A2B] transition-colors"
                    >
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Overlay -->
    <x-profile-overlay />

    <script>
        // Dados das transações para JavaScript
        const transactions = @json($transactionsForJs);

        function openDetailsModal(transactionId, transactionType) {
            const transaction = transactions.find(t => t.id === transactionId);
            if (!transaction) return;

            const modal = document.getElementById('detailsModal');
            const content = document.getElementById('detailsContent');
            
            let html = '';

            // Apenas saques são exibidos
            const withdrawal = transaction;
            const statusLabels = {
                'pending': 'Pendente',
                'transferred': 'Completa',
                'rejected': 'Reprovado',
            };
            
            html = `
                <div class="space-y-4">
                    <div class="flex justify-between">
                        <span class="text-[#706f6c]">Valor do saque</span>
                        <span class="font-semibold text-[#1b1b18]">R$ ${parseFloat(withdrawal.amount).toFixed(2).replace('.', ',')}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#706f6c]">Taxa</span>
                        <span class="font-semibold text-[#1b1b18]">Gratuita</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#706f6c]">Valor a receber</span>
                        <span class="font-semibold text-[#1b1b18]">R$ ${parseFloat(withdrawal.amount).toFixed(2).replace('.', ',')}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#706f6c]">Solicitado em</span>
                        <span class="font-semibold text-[#1b1b18]">${formatDateTime(withdrawal.created_at)}</span>
                    </div>
                    ${withdrawal.processed_at ? `
                    <div class="flex justify-between">
                        <span class="text-[#706f6c]">Transferido em</span>
                        <span class="font-semibold text-[#1b1b18]">${formatDateTime(withdrawal.processed_at)}</span>
                    </div>
                    ` : ''}
                    <div class="flex justify-between">
                        <span class="text-[#706f6c]">Status</span>
                        <span class="font-semibold text-[#1b1b18]">${statusLabels[withdrawal.status] || withdrawal.status}</span>
                    </div>
                    ${withdrawal.bank_account ? `
                    <div class="pt-4 border-t border-[#E2E8F0]">
                        <p class="text-sm font-medium text-[#1b1b18] mb-2">Conta bancária</p>
                        <p class="text-sm text-[#706f6c]">${withdrawal.bank_account.bank_name}</p>
                        <p class="text-sm text-[#706f6c]">${withdrawal.bank_account.pix_key_type ? withdrawal.bank_account.pix_key_type.charAt(0).toUpperCase() + withdrawal.bank_account.pix_key_type.slice(1) : ''}: ${withdrawal.bank_account.pix_key || ''}</p>
                    </div>
                    ` : ''}
                </div>
            `;

            content.innerHTML = html;
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeDetailsModal() {
            const modal = document.getElementById('detailsModal');
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }

        function formatDateTime(dateString) {
            const date = new Date(dateString);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            const seconds = String(date.getSeconds()).padStart(2, '0');
            return `${day}/${month}/${year} - ${hours}:${minutes}:${seconds}`;
        }

        $(document).ready(function() {
            const closeBtn = document.getElementById('closeDetailsModalBtn');
            if (closeBtn) {
                closeBtn.addEventListener('click', closeDetailsModal);
            }
        });
    </script>
</body>
</html>
