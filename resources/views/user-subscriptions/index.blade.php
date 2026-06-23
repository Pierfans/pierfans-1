<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Minhas Assinaturas - {{ config('app.name', 'Laravel') }}</title>

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

        .subscriptions-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            padding-bottom: 100px;
        }

        .page-header {
            margin-bottom: 24px;
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

        /* Filtros */
        .filter-tabs {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            background: white;
            padding: 8px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .filter-tab {
            flex: 1;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            background: transparent;
            color: #718096;
        }

        .filter-tab.active {
            background: #FF6B35;
            color: white;
        }

        .filter-tab:hover:not(.active) {
            background: #F7FAFC;
            color: #1a202c;
        }

        .filter-tab-count {
            font-size: 12px;
            opacity: 0.8;
            margin-left: 6px;
        }

        /* Grid de Assinaturas */
        .subscriptions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        @media (max-width: 640px) {
            .subscriptions-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Card de Assinatura */
        .subscription-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .subscription-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }

        .subscription-card-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }

        .subscription-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #E2E8F0;
        }

        .subscription-avatar-placeholder {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            font-weight: 600;
            border: 2px solid #E2E8F0;
        }

        .subscription-creator-info {
            flex: 1;
        }

        .subscription-creator-name {
            font-size: 16px;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 4px;
        }

        .subscription-plan-name {
            font-size: 13px;
            color: #718096;
        }

        .subscription-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 16px;
        }

        .subscription-status.active {
            background: #D1FAE5;
            color: #065F46;
        }

        .subscription-status.expired {
            background: #FEE2E2;
            color: #991B1B;
        }

        .subscription-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .subscription-detail-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
        }

        .subscription-detail-label {
            color: #718096;
        }

        .subscription-detail-value {
            color: #1a202c;
            font-weight: 500;
        }

        .subscription-amount {
            font-size: 18px;
            font-weight: 700;
            color: #10B981;
            margin-top: 4px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .empty-state-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: #F7FAFC;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .empty-state-title {
            font-size: 20px;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 8px;
        }

        .empty-state-message {
            font-size: 14px;
            color: #718096;
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
        <div class="subscriptions-container">
            <!-- Header -->
            <div class="page-header">
                <h1 class="page-title">Minhas Assinaturas</h1>
                <p class="page-subtitle">Gerencie suas assinaturas de criadores</p>
            </div>

            <!-- Filtros -->
            <div class="filter-tabs">
                <button
                    class="filter-tab {{ $filter === 'active' ? 'active' : '' }}"
                    onclick="window.location.href='{{ route('my-subscriptions.index', ['filter' => 'active']) }}'"
                >
                    Ativas
                    <span class="filter-tab-count">({{ $active_count }})</span>
                </button>
                <button
                    class="filter-tab {{ $filter === 'expired' ? 'active' : '' }}"
                    onclick="window.location.href='{{ route('my-subscriptions.index', ['filter' => 'expired']) }}'"
                >
                    Expiradas
                    <span class="filter-tab-count">({{ $expired_count }})</span>
                </button>
                <button
                    class="filter-tab {{ $filter === 'ppv' ? 'active' : '' }}"
                    onclick="window.location.href='{{ route('my-subscriptions.index', ['filter' => 'ppv']) }}'"
                >
                    Conteúdo Único
                    <span class="filter-tab-count">({{ $ppv_count }})</span>
                </button>
            </div>

            <!-- Grid de Assinaturas -->
            @if($filter !== 'ppv')
            @if($subscriptions->count() > 0)
                <div class="subscriptions-grid">
                    @foreach($subscriptions as $subscription)
                        <a href="{{ route('profile.show', $subscription->creator->username) }}" class="subscription-card">
                            <div class="subscription-card-header">
                                @if($subscription->creator->profile_photo)
                                    <img src="{{ $subscription->creator->profile_photo_url }}"
                                         alt="{{ $subscription->creator->name }}"
                                         class="subscription-avatar">
                                @else
                                    <div class="subscription-avatar-placeholder">
                                        {{ strtoupper(substr($subscription->creator->name, 0, 2)) }}
                                    </div>
                                @endif
                                <div class="subscription-creator-info">
                                    <div class="subscription-creator-name">{{ $subscription->creator->name }}</div>
                                    <div class="subscription-plan-name">{{ $subscription->plan->name }}</div>
                                </div>
                            </div>

                            <div class="subscription-status {{ $filter === 'active' ? 'active' : 'expired' }}">
                                @if($filter === 'active')
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    Ativa
                                @else
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="8" x2="12" y2="16"></line>
                                        <line x1="8" y1="12" x2="16" y2="12"></line>
                                    </svg>
                                    Expirada
                                @endif
                            </div>

                            <div class="subscription-details">
                                <div class="subscription-detail-row">
                                    <span class="subscription-detail-label">Valor pago</span>
                                    <span class="subscription-amount">R$ {{ number_format($subscription->total_amount, 2, ',', '.') }}</span>
                                </div>
                                <div class="subscription-detail-row">
                                    <span class="subscription-detail-label">Início</span>
                                    <span class="subscription-detail-value">{{ date('d/m/Y', strtotime($subscription->start_date)) }}</span>
                                </div>
                                <div class="subscription-detail-row">
                                    <span class="subscription-detail-label">Término</span>
                                    <span class="subscription-detail-value">{{ date('d/m/Y', strtotime($subscription->end_date)) }}</span>
                                </div>
                                @if($filter === 'active')
                                    @php
                                        // $subscription->end_date já é um objeto Carbon devido ao cast 'date' no modelo
                                        $daysLeft = (int) floor(now()->diffInDays($subscription->end_date, false));
                                        
                                        if ($daysLeft < 0) {
                                            $expiresText = 'Expirada';
                                        } elseif ($daysLeft == 0) {
                                            $expiresText = 'Hoje';
                                        } elseif ($daysLeft == 1) {
                                            $expiresText = '1 dia';
                                        } else {
                                            $expiresText = $daysLeft . ' dias';
                                        }
                                    @endphp
                                    <div class="subscription-detail-row" style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #E2E8F0;">
                                        <span class="subscription-detail-label">Expira em</span>
                                        <span class="subscription-detail-value" style="color: #10B981; font-weight: 600;">
                                            {{ $expiresText }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                    </div>
                    <h2 class="empty-state-title">
                        @if($filter === 'active')
                            Nenhuma assinatura ativa
                        @else
                            Nenhuma assinatura expirada
                        @endif
                    </h2>
                    <p class="empty-state-message">
                        @if($filter === 'active')
                            Você ainda não possui assinaturas ativas. Explore os criadores e assine para ter acesso exclusivo!
                        @else
                            Você não possui assinaturas expiradas no momento.
                        @endif
                    </p>
                </div>
            @endif
            @endif {{-- fim @if($filter !== 'ppv') --}}

            <!-- Grid de Conteúdo Único (PPV) -->
            @if($filter === 'ppv')
                @if($ppv_purchases->count() > 0)
                    <div class="subscriptions-grid">
                        @foreach($ppv_purchases as $purchase)
                            <a href="{{ route('profile.show', $purchase->creator->username) }}" class="subscription-card">
                                <div class="subscription-card-header">
                                    @if($purchase->creator->profile_photo)
                                        <img src="{{ $purchase->creator->profile_photo_url }}"
                                             alt="{{ $purchase->creator->name }}"
                                             class="subscription-avatar">
                                    @else
                                        <div class="subscription-avatar-placeholder">
                                            {{ strtoupper(substr($purchase->creator->name, 0, 2)) }}
                                        </div>
                                    @endif
                                    <div class="subscription-creator-info">
                                        <div class="subscription-creator-name">{{ $purchase->creator->name }}</div>
                                        <div class="subscription-plan-name">Conteúdo Único</div>
                                    </div>
                                </div>

                                <div class="subscription-status active">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    Acesso permanente
                                </div>

                                <div class="subscription-details">
                                    <div class="subscription-detail-row">
                                        <span class="subscription-detail-label">Valor pago</span>
                                        <span class="subscription-amount">R$ {{ number_format($purchase->amount_paid, 2, ',', '.') }}</span>
                                    </div>
                                    <div class="subscription-detail-row">
                                        <span class="subscription-detail-label">Comprado em</span>
                                        <span class="subscription-detail-value">{{ $purchase->purchased_at->format('d/m/Y') }}</span>
                                    </div>
                                    @if($purchase->post && $purchase->post->description)
                                        <div class="subscription-detail-row" style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #E2E8F0;">
                                            <span class="subscription-detail-label" style="font-style:italic">
                                                {{ mb_strimwidth($purchase->post->description, 0, 60, '...') }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                <polyline points="21 15 16 10 5 21"></polyline>
                            </svg>
                        </div>
                        <h2 class="empty-state-title">Nenhum Conteúdo Único comprado</h2>
                        <p class="empty-state-message">Você ainda não comprou nenhum Conteúdo Único. Explore os perfis dos criadores!</p>
                    </div>
                @endif
            @endif
        </div>
    </div>

    <!-- Profile Overlay -->
    <x-profile-overlay />
</body>
</html>

