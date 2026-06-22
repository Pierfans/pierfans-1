// JavaScript customizado da aplicação
// Você pode adicionar seus scripts personalizados aqui

// Profile Drawer Functions
function openProfileDrawer() {
    const drawer = document.getElementById('profileDrawer');
    const overlay = document.getElementById('profileDrawerOverlay');
    
    if (!drawer || !overlay) return;
    
    overlay.classList.remove('hidden');
    drawer.classList.remove('translate-y-full');
    drawer.classList.add('translate-y-0');
    
    // Prevent body scroll
    document.body.style.overflow = 'hidden';
}

function closeProfileDrawer() {
    const drawer = document.getElementById('profileDrawer');
    const overlay = document.getElementById('profileDrawerOverlay');
    
    if (!drawer || !overlay) return;
    
    drawer.classList.remove('translate-y-0');
    drawer.classList.add('translate-y-full');
    
    setTimeout(() => {
        overlay.classList.add('hidden');
        document.body.style.overflow = '';
    }, 300);
}

// Close drawer on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeProfileDrawer();
    }
});

// Prevent drawer from closing when clicking inside it
document.addEventListener('DOMContentLoaded', function() {
    const drawer = document.getElementById('profileDrawer');
    if (drawer) {
        drawer.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
});

