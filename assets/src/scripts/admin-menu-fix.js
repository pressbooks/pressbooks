// Dynamic submenu height adjustment for WordPress admin
document.addEventListener('DOMContentLoaded', function() {
    // Admin menu submenus
    document.querySelectorAll('#adminmenu .wp-has-submenu').forEach(item => {
        item.addEventListener('mouseenter', function() {
            const submenu = this.querySelector('.wp-submenu');
            if (submenu) {
                const rect = this.getBoundingClientRect();
                const availableHeight = window.innerHeight - rect.top - 20;
                submenu.style.maxHeight = availableHeight + 'px';
            }
        });
    });
    
    // Admin bar dropdowns
    document.querySelectorAll('#wpadminbar .menupop').forEach(item => {
        item.addEventListener('mouseenter', function() {
            const submenu = this.querySelector('.ab-sub-wrapper');
            if (submenu) {
                const rect = this.getBoundingClientRect();
                const availableHeight = window.innerHeight - rect.bottom - 20;
                submenu.style.maxHeight = availableHeight + 'px';
            }
        });
    });
});