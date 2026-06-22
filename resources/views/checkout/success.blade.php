<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pagamento Confirmado - {{ config('app.name', 'Laravel') }}</title>

    <!-- TailwindCSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Estilos e scripts customizados -->
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/profile-overlay.css">
    <script src="/js/app.js"></script>
    <script src="/js/profile-overlay.js"></script>

    <style>
        .success-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 40px 20px;
            text-align: center;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            background: #10B981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .success-title {
            font-size: 28px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 12px;
        }

        .success-message {
            font-size: 16px;
            color: #718096;
            margin-bottom: 32px;
            line-height: 1.6;
        }

        .success-details {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 32px;
            text-align: left;
        }

        .success-detail-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #E2E8F0;
        }

        .success-detail-item:last-child {
            border-bottom: none;
        }

        .success-detail-label {
            font-size: 14px;
            color: #718096;
        }

        .success-detail-value {
            font-size: 14px;
            font-weight: 600;
            color: #1a202c;
        }

        .btn-conclude {
            background: #FF6B35;
            color: white;
            padding: 16px 32px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            width: 100%;
            max-width: 300px;
            transition: background 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-conclude:hover {
            background: #e55a2b;
        }
    </style>
</head>
<body class="bg-[#F5F7FA] text-[#1a202c] min-h-screen">
    <!-- Top Navigation -->
    <x-topnav />

    <!-- Bottom Navigation -->
    <x-bottomnav />

    <!-- Profile Drawer -->
    <x-profile-drawer />

    <!-- Main Content -->
    <div class="pt-0 md:pt-16 md:pb-0 pb-16">
        <div class="success-container">
            <!-- Ícone de Sucesso -->
            <div class="success-icon">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>

            <!-- Título e Mensagem -->
            <h1 class="success-title">Pagamento Confirmado!</h1>
            <p class="success-message">
                Sua assinatura foi realizada com sucesso. Agora você tem acesso exclusivo ao conteúdo de <strong>{{ $creator->name }}</strong>.
            </p>

            <!-- Detalhes da Assinatura -->
            <div class="success-details">
                <div class="success-detail-item">
                    <span class="success-detail-label">Criador</span>
                    <span class="success-detail-value">{{ $creator->name }}</span>
                </div>
                <div class="success-detail-item">
                    <span class="success-detail-label">Plano</span>
                    <span class="success-detail-value">{{ $subscription->plan->name }}</span>
                </div>
                <div class="success-detail-item">
                    <span class="success-detail-label">Valor Pago</span>
                    <span class="success-detail-value">R$ {{ number_format($subscription->total_amount, 2, ',', '.') }}</span>
                </div>
                <div class="success-detail-item">
                    <span class="success-detail-label">Data de Início</span>
                    <span class="success-detail-value">{{ date('d/m/Y', strtotime($subscription->start_date)) }}</span>
                </div>
                <div class="success-detail-item">
                    <span class="success-detail-label">Data de Término</span>
                    <span class="success-detail-value">{{ date('d/m/Y', strtotime($subscription->end_date)) }}</span>
                </div>
            </div>

            <!-- Botão Concluir -->
            <a href="{{ route('profile.show', $creator->username) }}" class="btn-conclude">
                Concluir
            </a>
        </div>
    </div>

    <!-- Profile Overlay -->
    <x-profile-overlay />
</body>
</html>

