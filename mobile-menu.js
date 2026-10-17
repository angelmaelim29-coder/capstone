// ============================================
// MOBILE MENU - Hamburger Toggle
// ============================================

(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        
        // Find sidebar (works for both .sidebar and .app-sidebar)
        const sidebar = document.querySelector('.sidebar, .app-sidebar');
        if (!sidebar) return;
        
        // Get navbar height to position toggle below it
        const navbar = document.querySelector('.navbar-custom');
        const navbarHeight = navbar ? navbar.offsetHeight : 70;
        
        // Create hamburger button
        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'mobile-menu-toggle';
        toggleBtn.setAttribute('aria-label', 'Toggle menu');
        toggleBtn.innerHTML = '<i class="bi bi-list"></i>';
        toggleBtn.style.top = (navbarHeight + 14) + 'px';
        
        // Create overlay
        const overlay = document.createElement('div');
        overlay.className = 'mobile-menu-overlay';
        
        // Add to body
        document.body.appendChild(toggleBtn);
        document.body.appendChild(overlay);
        
        // ============================================
        // Toggle menu
        // ============================================
        function openMenu() {
            sidebar.classList.add('mobile-open');
            overlay.classList.add('active');
            document.body.classList.add('menu-open');
            toggleBtn.innerHTML = '<i class="bi bi-x-lg"></i>';
        }
        
        function closeMenu() {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
            document.body.classList.remove('menu-open');
            toggleBtn.innerHTML = '<i class="bi bi-list"></i>';
        }
        
        function toggleMenu() {
            if (sidebar.classList.contains('mobile-open')) {
                closeMenu();
            } else {
                openMenu();
            }
        }
        
        // Click handlers
        toggleBtn.addEventListener('click', toggleMenu);
        overlay.addEventListener('click', closeMenu);
        
        // Close menu when a nav link is clicked
        sidebar.querySelectorAll('.nav-link, .nav-item a').forEach(link => {
            link.addEventListener('click', function() {
                // Don't close if it's just opening a modal
                if (!this.hasAttribute('data-bs-toggle')) {
                    closeMenu();
                } else {
                    // Close sidebar before modal opens
                    setTimeout(closeMenu, 150);
                }
            });
        });
        
        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar.classList.contains('mobile-open')) {
                closeMenu();
            }
        });
        
        // Close on resize to desktop
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if (window.innerWidth > 992 && sidebar.classList.contains('mobile-open')) {
                    closeMenu();
                }
            }, 150);
        });
        
        // ============================================
        // AUTO-WRAP TABLES IN SCROLLABLE CONTAINERS
        // ============================================
        document.querySelectorAll('.data-table, .modal-table').forEach(function(table) {
            // Skip if already wrapped
            if (table.parentElement.classList.contains('table-responsive')) return;
            
            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        });
        
    });
})();