/**
 * Admin Panel JavaScript
 * 
 * Interactive features for admin panel
 * 
 * @author Danny Duong
 */

(function () {
    'use strict';

    // Mobile sidebar toggle (if needed in future)
    const initMobileSidebar = () => {
        // Placeholder for mobile sidebar functionality
    };

    // Confirm delete actions
    document.querySelectorAll('[data-confirm]').forEach(button => {
        button.addEventListener('click', function (e) {
            const message = this.dataset.confirm || 'Bạn có chắc chắn muốn xóa?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // Auto-save drafts (future feature)
    const initAutoSave = () => {
        // Placeholder for auto-save functionality
    };

    // Initialize
    initMobileSidebar();
})();
