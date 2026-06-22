<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') - {{ config('app.name', 'Laravel') }}</title>
    
    <!-- TailwindCSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- jQuery via CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Estilos e scripts customizados -->
    <link rel="stylesheet" href="/css/app.css">
    <script src="/js/app.js"></script>
    
    <style>
        /* Estilos customizados para o admin */
        .admin-sidebar {
            transition: transform 0.3s ease-in-out;
        }
        
        /* Mobile: sidebar escondido por padrão */
        @media (max-width: 767px) {
            .admin-sidebar.mobile-hidden {
                transform: translateX(-100%);
            }
        }
        
        /* Desktop: sidebar sempre visível, ignora a classe mobile-hidden */
        @media (min-width: 768px) {
            .admin-sidebar {
                transform: translateX(0) !important;
            }
        }
        
        .admin-overlay {
            transition: opacity 0.3s ease-in-out;
        }
        
        .admin-overlay.hidden {
            opacity: 0;
            pointer-events: none;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Mobile Overlay -->
    <div id="adminOverlay" class="admin-overlay fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden" onclick="closeAdminSidebar()"></div>

    <!-- Sidebar -->
    <x-admin-sidebar />

    <!-- Main Content -->
    <div class="md:ml-64">
        <!-- Top Bar (Mobile) -->
        <div class="bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between md:hidden">
            <button onclick="toggleAdminSidebar()" class="p-2 text-gray-600 hover:text-gray-900">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <h1 class="text-lg font-semibold text-gray-900">Admin</h1>
            <div class="w-10"></div> <!-- Spacer para centralizar -->
        </div>

        <!-- Page Content -->
        <main class="p-4 md:p-8">
            @yield('content')
        </main>
    </div>

    <script>
        function toggleAdminSidebar() {
            const sidebar = document.getElementById('adminSidebar');
            const overlay = document.getElementById('adminOverlay');
            
            sidebar.classList.toggle('mobile-hidden');
            overlay.classList.toggle('hidden');
        }

        function closeAdminSidebar() {
            const sidebar = document.getElementById('adminSidebar');
            const overlay = document.getElementById('adminOverlay');
            
            sidebar.classList.add('mobile-hidden');
            overlay.classList.add('hidden');
        }

        // Fecha o menu ao redimensionar para desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 768) {
                const overlay = document.getElementById('adminOverlay');
                overlay.classList.add('hidden');
            }
        });
    </script>
    <x-whatsapp-float :clear-mobile-nav="false" />
</body>
</html>

