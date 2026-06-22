<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Afiliados - {{ config('app.name', 'Laravel') }}</title>

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
        body {
            background: #F5F7FA;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .affiliates-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            padding-bottom: 100px;
        }

        .page-header {
            margin-bottom: 32px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 8px;
        }

        .page-subtitle {
            font-size: 14px;
            color: #718096;
        }

        /* Cards de Resumo */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .summary-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.08);
        }

        .summary-card-title {
            font-size: 14px;
            color: #718096;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .summary-card-value {
            font-size: 32px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 16px;
        }

        .summary-card-action {
            display: inline-flex;
            align-items: center;
            padding: 10px 20px;
            background: #FF6B35;
            color: white;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s;
        }

        .summary-card-action:hover {
            background: #e55a2b;
        }

        /* Listagem de Indicações */
        .commissions-section {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.08);
            margin-bottom: 32px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 20px;
        }

        .commission-item {
            padding: 16px;
            border-bottom: 1px solid #E2E8F0;
            cursor: pointer;
            transition: background 0.2s;
        }

        .commission-item:last-child {
            border-bottom: none;
        }

        .commission-item:hover {
            background: #F7FAFC;
        }

        .commission-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .commission-info {
            flex: 1;
        }

        .commission-date {
            font-size: 12px;
            color: #718096;
            margin-bottom: 4px;
        }

        .commission-details {
            font-size: 14px;
            color: #1a202c;
            margin-bottom: 4px;
        }

        .commission-value {
            font-size: 18px;
            font-weight: 700;
            color: #1a202c;
        }

        .commission-status {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 16px;
        }

        .status-liberado {
            background: #D1FAE5;
            color: #065F46;
        }

        .status-bloqueado {
            background: #FEE2E2;
            color: #991B1B;
        }

        .status-pago {
            background: #DBEAFE;
            color: #1E40AF;
        }

        /* Modal */
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

        .modal-header {
            padding: 24px;
            border-bottom: 1px solid #E2E8F0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 700;
            color: #1a202c;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #718096;
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-body {
            padding: 24px;
        }

        .modal-detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #E2E8F0;
        }

        .modal-detail-row:last-child {
            border-bottom: none;
        }

        .modal-detail-label {
            font-size: 14px;
            color: #718096;
            font-weight: 500;
        }

        .modal-detail-value {
            font-size: 14px;
            color: #1a202c;
            font-weight: 600;
            text-align: right;
        }

        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: #718096;
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }

        .empty-state-text {
            font-size: 16px;
        }
    </style>
</head>
<body class="bg-[#F5F7FA]">
    <!-- Top Navigation (Desktop) -->
    <x-topnav />

    <!-- Bottom Navigation (Mobile) -->
    <x-bottomnav />

    <!-- Profile Drawer -->
    <x-profile-drawer />

    <!-- Main Content -->
    <div class="affiliates-container">
        <!-- Header -->
        <div class="page-header lg:mt-16">
            <h1 class="page-title">Afiliados</h1>
            <p class="page-subtitle">Acompanhe suas indicações e comissões</p>
        </div>

        <!-- Cards de Resumo -->
        <div class="summary-cards">
            <!-- Card: Afiliados Ativos -->
            <div class="summary-card">
                <div class="summary-card-title">Afiliados Ativos</div>
                <div class="summary-card-value">{{ $activeAffiliatesCount }}</div>
                <div class="text-sm text-gray-500">Usuários com assinatura ativa</div>
            </div>

            <!-- Card: Saldo Disponível -->
            <div class="summary-card">
                <div class="summary-card-title">Saldo Disponível</div>
                <div class="summary-card-value">R$ {{ number_format($availableBalance, 2, ',', '.') }}</div>
                <a href="{{ route('affiliates.withdraw') }}" class="summary-card-action">
                    Sacar
                </a>
            </div>

            <!-- Card: Saldo Bloqueado -->
            <div class="summary-card">
                <div class="summary-card-title">Saldo Bloqueado</div>
                <div class="summary-card-value">R$ {{ number_format($pendingBalance, 2, ',', '.') }}</div>
                <div class="text-sm text-gray-500">A liberar</div>
            </div>
        </div>

        <!-- Link de Indicação -->
        <div class="commissions-section mb-6">
            <h2 class="section-title mb-4">Seu Link de Indicação</h2>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Copie este link e compartilhe para indicar novos usuários
                </label>
                <div class="flex gap-2">
                    <input
                        type="text"
                        id="affiliateLinkInput"
                        value="{{ $affiliateLink }}"
                        readonly
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-sm"
                    >
                    <button
                        onclick="copyAffiliateLink()"
                        class="px-6 py-2 bg-[#FF6B35] text-white rounded-lg font-semibold hover:bg-[#E55A2B] transition-colors"
                    >
                        Copiar
                    </button>
                </div>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p class="text-sm text-blue-800">
                    <strong>Como funciona:</strong> Quando alguém se cadastrar através do seu link e assinar qualquer criador,
                    você receberá <strong>{{ number_format($commissionPercentage, 2, ',', '.') }}%</strong> de comissão sobre o valor da assinatura.
                </p>
            </div>
        </div>

        <!-- Listagem de Indicações -->
        <div class="commissions-section">
            <div class="flex justify-between items-center mb-6">
                <h2 class="section-title">Comissões por Indicação</h2>
                <a href="{{ route('affiliates.extract') }}" class="text-sm text-[#FF6B35] font-semibold hover:underline">
                    Ver extrato completo
                </a>
            </div>

            @if($commissions->count() > 0)
                <div class="commissions-list">
                    @foreach($commissions as $commission)
                        <div class="commission-item" onclick="openCommissionModal({{ $commission['id'] }})">
                            <div class="commission-row">
                                <div class="commission-info">
                                    <div class="commission-date">{{ $commission['date'] }}</div>
                                    <div class="commission-details">
                                        {{ $commission['plan_name'] }} - {{ $commission['creator_name'] }}
                                    </div>
                                    @if(isset($commission['commission_type']))
                                        <div class="mt-1">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                                                {{ $commission['commission_type'] === 'Assinatura' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                                {{ $commission['commission_type'] === 'Assinatura' ? '📥 Por Assinante' : '💰 Por Criador' }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex items-center">
                                    <div class="commission-value">{{ $commission['value'] }}</div>
                                    <span class="commission-status status-{{ strtolower($commission['status']) }}">
                                        {{ $commission['status'] }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">📊</div>
                    <div class="empty-state-text">Nenhuma comissão gerada ainda</div>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal de Detalhamento -->
    <div id="commissionModal" class="modal-overlay" onclick="closeCommissionModal(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 class="modal-title">Detalhes da Comissão</h3>
                <button class="modal-close" onclick="closeCommissionModal(event)">×</button>
            </div>
            <div class="modal-body" id="commissionModalBody">
                <div class="text-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#FF6B35] mx-auto"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Overlay -->
    <x-profile-overlay />

    <script>
        function copyAffiliateLink() {
            const input = document.getElementById('affiliateLinkInput');
            if (input) {
                input.select();
                input.setSelectionRange(0, 99999); // Para mobile
                document.execCommand('copy');

                // Feedback visual
                const button = event.target.closest('button');
                const originalText = button.innerHTML;
                button.innerHTML = 'Copiado!';
                button.style.background = '#10B981';

                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.background = '#FF6B35';
                }, 2000);
            }
        }

        function openCommissionModal(subscriptionId) {
            const modal = document.getElementById('commissionModal');
            const modalBody = document.getElementById('commissionModalBody');

            modal.classList.add('active');
            document.body.style.overflow = 'hidden';

            // Carrega detalhes da comissão
            modalBody.innerHTML = '<div class="text-center py-8"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#FF6B35] mx-auto"></div></div>';

            fetch(`/affiliates/commission/${subscriptionId}`, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const d = data.data;
                    modalBody.innerHTML = `
                        <div class="modal-detail-row">
                            <span class="modal-detail-label">Data da Assinatura</span>
                            <span class="modal-detail-value">${d.subscription_date}</span>
                        </div>
                        <div class="modal-detail-row">
                            <span class="modal-detail-label">Plano Contratado</span>
                            <span class="modal-detail-value">${d.plan_name}</span>
                        </div>
                        <div class="modal-detail-row">
                            <span class="modal-detail-label">Valor do Plano</span>
                            <span class="modal-detail-value">${d.plan_price}</span>
                        </div>
                        <div class="modal-detail-row">
                            <span class="modal-detail-label">Criador de Conteúdo</span>
                            <span class="modal-detail-value">${d.creator_name}</span>
                        </div>
                        <div class="modal-detail-row">
                            <span class="modal-detail-label">Percentual de Comissão</span>
                            <span class="modal-detail-value">${d.commission_percentage}</span>
                        </div>
                        <div class="modal-detail-row">
                            <span class="modal-detail-label">Tipo de Comissão</span>
                            <span class="modal-detail-value">${d.commission_type || 'Assinatura do Indicado'}</span>
                        </div>
                        <div class="modal-detail-row">
                            <span class="modal-detail-label">Valor da Comissão</span>
                            <span class="modal-detail-value">${d.commission_amount}</span>
                        </div>
                        ${d.referrer_amount ? `
                        <div class="modal-detail-row">
                            <span class="modal-detail-label">Comissão por Assinante</span>
                            <span class="modal-detail-value">${d.referrer_amount}</span>
                        </div>
                        ` : ''}
                        ${d.creator_affiliate_amount ? `
                        <div class="modal-detail-row">
                            <span class="modal-detail-label">Comissão por Criador</span>
                            <span class="modal-detail-value">${d.creator_affiliate_amount}</span>
                        </div>
                        ` : ''}
                        <div class="modal-detail-row">
                            <span class="modal-detail-label">Método de Pagamento</span>
                            <span class="modal-detail-value">${d.payment_method}</span>
                        </div>
                        <div class="modal-detail-row">
                            <span class="modal-detail-label">Status</span>
                            <span class="modal-detail-value">${d.status}</span>
                        </div>
                        ${d.status === 'Bloqueado' ? `
                        <div class="modal-detail-row">
                            <span class="modal-detail-label">Previsão de Liberação</span>
                            <span class="modal-detail-value">${d.release_date}</span>
                        </div>
                        ` : ''}
                    `;
                } else {
                    modalBody.innerHTML = '<div class="text-center py-8 text-red-600">Erro ao carregar detalhes</div>';
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                modalBody.innerHTML = '<div class="text-center py-8 text-red-600">Erro ao carregar detalhes</div>';
            });
        }

        function closeCommissionModal(event) {
            if (event) {
                event.stopPropagation();
            }
            const modal = document.getElementById('commissionModal');
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }

        // Fechar modal com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeCommissionModal();
            }
        });
    </script>
</body>
</html>

