/**
 * Admin Panel JavaScript
 * 
 * Interactive features for admin panel
 * 
 * @author Danny Duong
 */

(function () {
    'use strict';

    // Mobile sidebar toggle
    const initMobileSidebar = () => {
        const toggleBtn = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('adminSidebar');
        const closeBtn = document.getElementById('sidebarClose');
        const overlay = document.getElementById('sidebarOverlay');

        if (!toggleBtn || !sidebar) return;

        function openSidebar() {
            sidebar.classList.add('open');
            overlay.classList.add('open');
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        }

        function closeSidebar() {
            sidebar.classList.remove('open');
            overlay.classList.remove('open');
            document.body.style.overflow = '';
        }

        toggleBtn.addEventListener('click', openSidebar);

        if (closeBtn) {
            closeBtn.addEventListener('click', closeSidebar);
        }

        if (overlay) {
            overlay.addEventListener('click', closeSidebar);
        }

        // Close when clicking escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && sidebar.classList.contains('open')) {
                closeSidebar();
            }
        });
    };

    // Dark Mode Toggle
    const initDarkMode = () => {
        const toggleBtn = document.getElementById('darkModeToggle');
        const theme = localStorage.getItem('admin_theme');

        // Apply theme (already handled by inline script, but safe to re-check)
        if (theme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        }

        if (toggleBtn) {
            toggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                const current = document.documentElement.getAttribute('data-theme');
                if (current === 'dark') {
                    document.documentElement.removeAttribute('data-theme');
                    localStorage.setItem('admin_theme', 'light');
                    toggleBtn.textContent = '🌙 Dark Mode';
                } else {
                    document.documentElement.setAttribute('data-theme', 'dark');
                    localStorage.setItem('admin_theme', 'dark');
                    toggleBtn.textContent = '☀️ Light Mode';
                }
            });

            // Set initial text
            if (theme === 'dark') {
                toggleBtn.textContent = '☀️ Light Mode';
            }
        }
    };

    // Initialize
    initMobileSidebar();
    initDarkMode();
})();
