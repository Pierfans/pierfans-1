<aside id="adminSidebar" class="admin-sidebar fixed top-0 left-0 h-full w-64 bg-black text-white z-50 md:z-40 mobile-hidden flex flex-col">
    <!-- Header -->
    <div class="p-6 border-b border-gray-800 flex items-center justify-between">
        <div class="flex items-center">
            <span class="text-xl font-bold">
                <span class="text-pink-500">pier</span><span class="text-orange-500">fans</span>
            </span>
            <span class="ml-2 text-sm text-gray-400">Admin</span>
        </div>
        <!-- Close Button (Mobile) -->
        <button onclick="closeAdminSidebar()" class="md:hidden text-gray-400 hover:text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <!-- Menu Items -->
    <nav class="p-4 flex-1 overflow-y-auto">
        <ul class="space-y-2">
            <!-- Dashboard -->
            <li>
                <a href="{{ route('admin.dashboard') }}" 
                   class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-900 transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-gray-900' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span class="font-medium">Dashboard</span>
                </a>
            </li>

            <!-- Criadores -->
            <li>
                <a href="{{ route('admin.creators.index') }}" 
                   class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-900 transition-colors {{ request()->routeIs('admin.creators.*') ? 'bg-gray-900' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span class="font-medium">Criadores</span>
                </a>
            </li>

            <!-- TOP Criadores -->
            <li>
                <a href="{{ route('admin.top-creators.index') }}" 
                   class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-900 transition-colors {{ request()->routeIs('admin.top-creators.*') ? 'bg-gray-900' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                    </svg>
                    <span class="font-medium">TOP Criadores</span>
                </a>
            </li>

            <!-- Usuários -->
            <li>
                <a href="{{ route('admin.users.index') }}" 
                   class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-900 transition-colors {{ request()->routeIs('admin.users.*') ? 'bg-gray-900' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span class="font-medium">Usuários</span>
                </a>
            </li>

            <!-- Postagens -->
            <li>
                <a href="{{ route('admin.posts.index') }}" 
                   class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-900 transition-colors {{ request()->routeIs('admin.posts.*') ? 'bg-gray-900' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <span class="font-medium">Postagens</span>
                </a>
            </li>

            <!-- Posts em Destaque -->
            <li>
                <a href="{{ route('admin.featured-posts.index') }}"
                   class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-900 transition-colors {{ request()->routeIs('admin.featured-posts.*') ? 'bg-gray-900' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                    </svg>
                    <span class="font-medium">Posts em Destaque</span>
                </a>
            </li>

            <!-- Lixeira -->
            <li>
                <a href="{{ route('admin.trash.index') }}" 
                   class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-900 transition-colors {{ request()->routeIs('admin.trash.*') ? 'bg-gray-900' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span class="font-medium">Lixeira</span>
                    @php
                        $trashCount = \App\Models\Post::withoutGlobalScope('notDeletedByUser')->whereNotNull('deleted_by_user_at')->whereHas('user')->count();
                    @endphp
                    @if($trashCount > 0)
                        <span class="ml-auto bg-orange-500 text-white text-xs font-bold px-2 py-1 rounded-full">{{ $trashCount }}</span>
                    @endif
                </a>
            </li>

            <!-- Denúncias -->
            <li>
                <a href="{{ route('admin.reports.index') }}" 
                   class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-900 transition-colors {{ request()->routeIs('admin.reports.*') ? 'bg-gray-900' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span class="font-medium">Denúncias</span>
                    @php
                        $pendingReportsCount = \App\Models\Report::where('status', 'pending')->count();
                    @endphp
                    @if($pendingReportsCount > 0)
                        <span class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full">{{ $pendingReportsCount }}</span>
                    @endif
                </a>
            </li>

            <!-- Conteúdo Único (PPV) -->
            <li>
                <a href="{{ route('admin.ppv.index') }}"
                   class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-900 transition-colors {{ request()->routeIs('admin.ppv.*') ? 'bg-gray-900' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.069A1 1 0 0121 8.87v6.26a1 1 0 01-1.447.894L15 14M3 8h12a2 2 0 012 2v4a2 2 0 01-2 2H3a2 2 0 01-2-2v-4a2 2 0 012-2z" />
                    </svg>
                    <span class="font-medium">Conteúdo Único</span>
                </a>
            </li>

            <!-- Conciliação Financeira -->
            <li>
                <a href="{{ route('admin.conciliacao.index') }}"
                   class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-900 transition-colors {{ request()->routeIs('admin.conciliacao.*') ? 'bg-gray-900' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m-6 4h6m-6 4h4M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z" />
                    </svg>
                    <span class="font-medium">Taxas e Repasses</span>
                </a>
            </li>

            <!-- Saques -->
            <li>
                <a href="{{ route('admin.withdrawals.index') }}" 
                   class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-900 transition-colors {{ request()->routeIs('admin.withdrawals.*') ? 'bg-gray-900' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span class="font-medium">Saques</span>
                </a>
            </li>

            <!-- Carteiras -->
            <li>
                <a href="{{ route('admin.wallets.index') }}" 
                   class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-900 transition-colors {{ request()->routeIs('admin.wallets.*') ? 'bg-gray-900' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="font-medium">Carteiras</span>
                </a>
            </li>

            <!-- Configurações da Plataforma -->
            <li>
                <a href="{{ route('admin.platform-settings.index') }}" 
                   class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-900 transition-colors {{ request()->routeIs('admin.platform-settings.*') ? 'bg-gray-900' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="font-medium">Configurações</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Footer -->
    <div class="border-t border-gray-700 p-4">
        <a href="{{ route('dashboard') }}" 
           class="flex items-center px-4 py-2 text-gray-400 hover:text-white transition-colors">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span class="text-sm">Voltar ao Site</span>
        </a>
    </div>
</aside>

