<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Meus Assinantes - {{ config('app.name', 'Laravel') }}</title>

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

        /* Cards de Resumo - Carousel */
        .summary-cards-container {
            position: relative;
            padding: 20px 0;
            margin-bottom: 24px;
        }

        .summary-cards-scroll {
            display: flex;
            gap: 16px;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            padding: 0 20px;
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE/Edge */
        }

        .summary-cards-scroll::-webkit-scrollbar {
            display: none; /* Chrome/Safari */
        }

        .summary-card {
            min-width: 280px;
            max-width: 320px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            scroll-snap-align: start;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .summary-card.inactive {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .summary-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            pointer-events: none;
        }

        .summary-card-title {
            font-size: 14px;
            font-weight: 500;
            opacity: 0.9;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }

        .summary-card-value {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 4px;
            position: relative;
            z-index: 1;
        }

        .summary-card-subtitle {
            font-size: 12px;
            opacity: 0.8;
            position: relative;
            z-index: 1;
        }

        /* Lista de Assinantes */
        .subscribers-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px 100px;
        }

        .subscribers-header {
            margin-bottom: 20px;
        }

        .subscribers-title {
            font-size: 24px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 8px;
        }

        .subscribers-subtitle {
            font-size: 14px;
            color: #718096;
        }

        .subscribers-list {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .subscriber-item {
            padding: 20px;
            border-bottom: 1px solid #E2E8F0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.2s;
        }

        .subscriber-item:last-child {
            border-bottom: none;
        }

        .subscriber-item:hover {
            background: #F7FAFC;
        }

        .subscriber-info {
            flex: 1;
        }

        .subscriber-name {
            font-size: 16px;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 8px;
        }

        .subscriber-details {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            font-size: 13px;
            color: #718096;
        }

        .subscriber-detail-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .subscriber-amount {
            font-size: 18px;
            font-weight: 700;
            color: #10B981;
            text-align: right;
        }

        .subscriber-amount-label {
            font-size: 11px;
            color: #718096;
            font-weight: 500;
            margin-top: 4px;
        }

        /* Desktop: cards lado a lado */
        @media (min-width: 768px) {
            .summary-cards-scroll {
                flex-wrap: wrap;
                justify-content: center;
                overflow-x: visible;
            }

            .summary-card {
                flex: 1;
                min-width: 300px;
                max-width: 400px;
            }
        }

        /* Indicador de scroll */
        .scroll-indicator {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            width: 30px;
            height: 30px;
            background: rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            opacity: 0.5;
        }

        @media (min-width: 768px) {
            .scroll-indicator {
                display: none;
            }
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
        <div class="subscribers-container">
            <!-- Cards de Resumo (Carousel) -->
            <div class="summary-cards-container">
                <div class="summary-cards-scroll" id="summaryCardsScroll">
                    <!-- Card: Assinantes Ativos -->
                    <div class="summary-card">
                        <div class="summary-card-title">Assinantes Ativos</div>
                        <div class="summary-card-value">{{ $active_count }}</div>
                        <div class="summary-card-subtitle">Assinaturas em vigor</div>
                    </div>

                    <!-- Card: Assinantes Inativos -->
                    <div class="summary-card inactive">
                        <div class="summary-card-title">Assinantes Inativos</div>
                        <div class="summary-card-value">{{ $inactive_count }}</div>
                        <div class="summary-card-subtitle">Assinaturas encerradas</div>
                    </div>
                </div>
            </div>

            <!-- Lista de Assinantes -->
            <div class="subscribers-header">
                <h1 class="subscribers-title">Meus Assinantes</h1>
                <p class="subscribers-subtitle">Lista completa de assinantes</p>
            </div>

            <div class="subscribers-list">
                @forelse($subscriptions as $subscription)
                    <div class="subscriber-item">
                        <div class="subscriber-info">
                            <div class="subscriber-name">{{ $subscription->user->name }}</div>
                            <div class="subscriber-details">
                                <div class="subscriber-detail-item">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="12" y1="1" x2="12" y2="23"></line>
                                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                    </svg>
                                    <span>Plano: {{ $subscription->plan->name }}</span>
                                </div>
                                <div class="subscriber-detail-item">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                    <span>Início: {{ date('d/m/Y', strtotime($subscription->start_date)) }}</span>
                                </div>
                                <div class="subscriber-detail-item">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                    <span>Término: {{ date('d/m/Y', strtotime($subscription->end_date)) }}</span>
                                </div>
                                <div class="subscriber-detail-item">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                    </svg>
                                    <span>Plataforma: {{ number_format($subscription->platform_percentage, 2) }}% (R$ {{ number_format($subscription->platform_amount, 2, ',', '.') }})</span>
                                </div>
                            </div>
                        </div>
                        <div class="subscriber-amount">
                            R$ {{ number_format($subscription->total_amount, 2, ',', '.') }}
                            <div class="subscriber-amount-label">Total pago</div>
                            <div style="font-size: 12px; color: #10B981; margin-top: 4px; font-weight: 600;">
                                Você recebe: R$ {{ number_format($subscription->creator_amount, 2, ',', '.') }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div style="padding: 40px; text-align: center; color: #718096;">
                        <p>Nenhum assinante ainda.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Profile Overlay -->
    <x-profile-overlay />

    <script>
        // Suporte a swipe no mobile para o carousel
        let isDown = false;
        let startX;
        let scrollLeft;
        const scrollContainer = document.getElementById('summaryCardsScroll');

        if (scrollContainer) {
            scrollContainer.addEventListener('mousedown', (e) => {
                isDown = true;
                scrollContainer.style.cursor = 'grabbing';
                startX = e.pageX - scrollContainer.offsetLeft;
                scrollLeft = scrollContainer.scrollLeft;
            });

            scrollContainer.addEventListener('mouseleave', () => {
                isDown = false;
                scrollContainer.style.cursor = 'grab';
            });

            scrollContainer.addEventListener('mouseup', () => {
                isDown = false;
                scrollContainer.style.cursor = 'grab';
            });

            scrollContainer.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                e.preventDefault();
                const x = e.pageX - scrollContainer.offsetLeft;
                const walk = (x - startX) * 2;
                scrollContainer.scrollLeft = scrollLeft - walk;
            });

            // Touch events para mobile
            let touchStartX = 0;
            let touchScrollLeft = 0;

            scrollContainer.addEventListener('touchstart', (e) => {
                touchStartX = e.touches[0].pageX - scrollContainer.offsetLeft;
                touchScrollLeft = scrollContainer.scrollLeft;
            });

            scrollContainer.addEventListener('touchmove', (e) => {
                const x = e.touches[0].pageX - scrollContainer.offsetLeft;
                const walk = (x - touchStartX) * 2;
                scrollContainer.scrollLeft = touchScrollLeft - walk;
            });
        }
    </script>
</body>
</html>

