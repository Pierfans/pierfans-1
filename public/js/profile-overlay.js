// Profile Overlay Controller
(function() {
    // Abre o overlay de perfil
    window.openProfileOverlay = function() {
        const overlay = document.getElementById('profileOverlay');
        if (overlay) {
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        } else {
            // Se o overlay não existe, o usuário não está autenticado
            // Redireciona para a página de cadastro
            window.location.href = '/register';
        }
    };

    // Fecha o overlay de perfil
    window.closeProfileOverlay = function() {
        const overlay = document.getElementById('profileOverlay');
        if (overlay) {
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    };

    // Toggle Dark Mode
    window.toggleDarkMode = function(checkbox) {
        const isDark = checkbox.checked;
        const html = document.documentElement;
        
        if (isDark) {
            html.classList.add('dark');
            localStorage.setItem('darkMode', 'enabled');
        } else {
            html.classList.remove('dark');
            localStorage.setItem('darkMode', 'disabled');
        }
    };

    // Inicialização
    $(document).ready(function() {
        // Verifica preferência salva de dark mode
        const darkMode = localStorage.getItem('darkMode');
        if (darkMode === 'enabled') {
            document.documentElement.classList.add('dark');
            const toggle = document.getElementById('darkModeToggle');
            if (toggle) {
                toggle.checked = true;
            }
        }

        // Fecha ao pressionar ESC
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                closeProfileOverlay();
            }
        });

        // Fecha ao clicar fora (opcional - se quiser manter)
        // $('#profileOverlay').on('click', function(e) {
        //     if (e.target === this) {
        //         closeProfileOverlay();
        //     }
        // });
    });
})();

