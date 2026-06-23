@auth
<div id="profileOverlay" class="profile-overlay">
    <div class="profile-overlay-content">
        <!-- Header com botão fechar -->
        <div class="profile-overlay-header">
            <button class="profile-overlay-close" onclick="closeProfileOverlay()" aria-label="Fechar perfil">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Conteúdo com scroll -->
        <div class="profile-overlay-scroll">
            <div class="profile-overlay-body">
                <!-- Seção de Perfil do Usuário -->
                <div class="profile-section">
                    <div class="profile-user-info">
                        <div class="profile-avatar">
                            <span class="profile-avatar-initials">
                                {{ strtoupper(substr(Auth::user()->name ?? '', 0, 2)) }}
                            </span>
                        </div>
                        <div class="profile-user-details">
                            <h3 class="profile-user-name">{{ Auth::user()->name ?? '' }}</h3>
                        </div>
                    </div>

                </div>

                <!-- Banner Promocional Creator - Apenas para não criadores aprovados -->
                @if (Auth::user() && Auth::user()->creator_status !== 'approved')
                    <div class="profile-creator-banner">
                        <h3 class="creator-banner-title">Torne-se um criador de Conteúdo na Pierfans!</h3>
                        <a href="{{ route('creator.index') }}" class="creator-banner-button">
                            Seja um criador
                        </a>
                    </div>
                @endif

                <!-- Carteira - Disponível para todos os usuários -->
                <div class="profile-section">
                    <div class="financial-summary">
                        <div class="financial-cards-container">
                            <!-- Card: Saldo da carteira -->
                            <a href="{{ route('wallet.index') }}" class="financial-card" style="text-decoration: none; color: inherit;">
                                <div class="financial-card-header">
                                    <h4 class="financial-card-title">Carteira</h4>
                                    <p class="financial-card-subtitle">Saldo para usar</p>
                                </div>
                                <div class="financial-card-amount">R$ {{ number_format(Auth::user()->getWalletBalance(), 2, ',', '.') }}</div>
                            </a>
                        </div>
                    </div>
                </div>

                @if (Auth::user() && Auth::user()->creator_status === 'approved')
                    <!-- Resumo Financeiro do Criador -->
                    <div class="profile-section">
                        <div class="financial-summary">
                            <div class="financial-cards-container">
                                <!-- Card 1: Liberado -->
                                <div class="financial-card">
                                    <div class="financial-card-header">
                                        <h4 class="financial-card-title">Liberado</h4>
                                        <p class="financial-card-subtitle">Valor disponível para saque</p>
                                    </div>
                                    <div class="financial-card-amount">R$ {{ number_format(Auth::user()->getAvailableBalance(), 2, ',', '.') }}</div>
                                    <a href="{{ route('withdraw.index') }}" class="financial-card-action">
                                        Sacar agora <span class="financial-card-arrow">></span>
                                    </a>
                                </div>

                                <!-- Card 2: Bloqueado -->
                                <div class="financial-card">
                                    <div class="financial-card-header">
                                        <h4 class="financial-card-title">Bloqueado</h4>
                                        <p class="financial-card-subtitle">Saldo a liberar</p>
                                    </div>
                                    <div class="financial-card-amount">R$ {{ number_format(Auth::user()->getPendingBalance(), 2, ',', '.') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Área do Criador -->
                    <div class="profile-section">
                        <h3 class="profile-section-title">Área do Criador</h3>
                        <div class="profile-cards-grid">
                            <!-- Meu Perfil -->
                            <a href="{{ Auth::user()->username ? '/' . Auth::user()->username : '/me' }}" class="profile-card">
                                <div class="profile-card-icon">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <h4 class="profile-card-title">Meu Perfil</h4>
                                <p class="profile-card-description">Acesse e visualize seu perfil</p>
                            </a>

                            <!-- Editar Meu Perfil -->
                            <a href="{{ route('profile.edit') }}" class="profile-card">
                                <div class="profile-card-icon">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </div>
                                <h4 class="profile-card-title">Editar Meu Perfil</h4>
                                <p class="profile-card-description">Edite suas informações pessoais</p>
                            </a>

                            <!-- Ofertas -->
                            <a href="{{ route('subscription-plans.index') }}" class="profile-card">
                                <div class="profile-card-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round"
                                        class="lucide lucide-circle-dollar-sign-icon lucide-circle-dollar-sign">
                                        <circle cx="12" cy="12" r="10" />
                                        <path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8" />
                                        <path d="M12 18V6" />
                                    </svg>
                                </div>
                                <h4 class="profile-card-title">Plano de Assinatura</h4>
                                <p class="profile-card-description">Aqui você pode gerir seus planos</p>
                            </a>

                            <!-- Meus Assinantes -->
                            <a href="{{ route('subscribers.index') }}" class="profile-card">
                                <div class="profile-card-icon">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                    </svg>
                                </div>
                                <h4 class="profile-card-title">Meus Assinantes</h4>
                                <p class="profile-card-description">Veja seu engajamento</p>
                            </a>

                            <!-- Chat -->
                            <a href="/chat" class="profile-card">
                                <div class="profile-card-icon">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                </div>
                                <h4 class="profile-card-title">Chat</h4>
                                <p class="profile-card-description">Converse com seus assinantes agora</p>
                            </a>

                            <!-- Live -->
                            <div class="profile-card">
                                <div class="profile-card-icon">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <h4 class="profile-card-title">Live <span class="text-xs text-gray-500">(em
                                        breve)</span></h4>
                                <p class="profile-card-description">Transmita ao vivo para assinantes</p>
                            </div>
                        </div>
                    </div>
                @else
                    <!-- Área do Assinante -->
                    <div class="profile-section">
                        <h3 class="profile-section-title">Área do Assinante</h3>
                        <div class="profile-cards-grid">
                            <!-- Chat -->
                            <a href="/chat" class="profile-card">
                                <div class="profile-card-icon">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                </div>
                                <h4 class="profile-card-title">Chat</h4>
                                <p class="profile-card-description">Converse com creators agora</p>
                            </a>

                            <!-- Assinaturas -->
                            <a href="{{ route('my-subscriptions.index') }}" class="profile-card">
                                <div class="profile-card-icon">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                    </svg>
                                </div>
                                <h4 class="profile-card-title">Assinaturas</h4>
                                <p class="profile-card-description">Veja os creators que você assina</p>
                            </a>

                        </div>
                    </div>
                @endif

                <!-- Outras Funcionalidades -->
                <div class="profile-section pb-16">
                    <h3 class="profile-section-title">Outras Funcionalidades</h3>
                    <div class="profile-cards-grid">

                        @if(Auth::user()->is_admin)
                            <!-- Painel Admin -->
                            <a href="{{ route('admin.dashboard') }}" class="profile-card" style="text-decoration: none; color: inherit;">
                                <div class="profile-card-icon">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <h4 class="profile-card-title">Painel Admin</h4>
                                <p class="profile-card-description">Acessar painel administrativo</p>
                            </a>
                        @endif

                        <!-- Afiliado -->
                        <a href="{{ route('affiliates.index') }}" class="profile-card" style="text-decoration: none; color: inherit;">
                            <div class="profile-card-icon">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h4 class="profile-card-title">Afiliado</h4>
                            <p class="profile-card-description">Acompanhe suas indicações e comissões</p>
                        </a>

                        <!-- Ajuda -->
                        <div class="profile-card">
                            <div class="profile-card-icon">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h4 class="profile-card-title">Ajuda</h4>
                            <p class="profile-card-description">Precisa de suporte ou tem dúvidas?</p>
                        </div>

                        <!-- Sair -->
                        <div class="profile-card profile-logout-card">
                            <div class="profile-card-icon logout-icon">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                            </div>
                            <div class="logout-content">
                                <h4 class="profile-card-title">Sair</h4>
                                <p class="profile-card-description">Sair da plataforma</p>
                            </div>
                        </div>

                        <div class="profile-social-row" role="navigation" aria-label="Redes sociais">
                            <a href="https://www.instagram.com/pier.fans.oficial" class="profile-social-circle profile-social-circle--instagram" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true">
                                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />
                                </svg>
                            </a>
                            <a href="https://x.com/PierFans" class="profile-social-circle profile-social-circle--x" target="_blank" rel="noopener noreferrer" aria-label="X">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true">
                                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Adiciona evento de logout
    document.addEventListener('DOMContentLoaded', function() {
        const logoutCards = document.querySelectorAll('.profile-logout-card');
        logoutCards.forEach(function(logoutCard) {
            logoutCard.addEventListener('click', function() {
                if (confirm('Tem certeza que deseja sair?')) {
                    document.getElementById('logout-form').submit();
                }
            });
        });
    });
</script>

<form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
    @csrf
</form>
@endauth

