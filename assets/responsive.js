/**
 * Mobile Menu Toggle Script
 * Expense Management ERP - Loydence Academy
 * 
 * Handles mobile navigation, table wrapping, and responsive behavior
 * Mobile-first approach
 */

(function () {
    'use strict';

    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function () {
        initMobileMenu();
        wrapTables();
        handleResize();
    });

    /**
     * Initialize mobile menu toggle
     */
    function initMobileMenu() {
        // Check if toggle already exists
        if (document.querySelector('.menu-toggle')) return;

        // Create toggle button
        const toggle = document.createElement('button');
        toggle.className = 'menu-toggle';
        toggle.setAttribute('aria-label', 'Toggle navigation menu');
        toggle.innerHTML = '<i class="fas fa-bars"></i>';
        document.body.appendChild(toggle);

        // Create overlay
        const overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);

        // Toggle click handler
        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            const sidebar = document.querySelector('.sidebar');

            if (sidebar) {
                const isActive = sidebar.classList.toggle('active');
                toggle.classList.toggle('active');
                overlay.classList.toggle('active');

                // Update icon
                const icon = toggle.querySelector('i');
                if (isActive) {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-times');
                    document.body.style.overflow = 'hidden';
                } else {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                    document.body.style.overflow = '';
                }
            }
        });

        // Overlay click handler
        overlay.addEventListener('click', function () {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.remove('active');
            toggle.classList.remove('active');
            overlay.classList.remove('active');

            const icon = toggle.querySelector('i');
            icon.classList.remove('fa-times');
            icon.classList.add('fa-bars');
            document.body.style.overflow = '';
        });
    }

    /**
     * Wrap tables in scrollable container
     */
    function wrapTables() {
        const tables = document.querySelectorAll('.table-card table, .card table, .table-card > table');

        tables.forEach(function (table) {
            // Skip if already wrapped
            if (table.parentElement.classList.contains('table-wrapper')) return;

            // Check if parent is already a card that handles overflow
            const parent = table.parentElement;
            if (parent.classList.contains('table-card') || parent.classList.contains('card')) {
                // Ensure parent has overflow-x handling
                parent.style.overflowX = 'auto';
                return;
            }

            // Create wrapper
            const wrapper = document.createElement('div');
            wrapper.className = 'table-wrapper';

            // Insert wrapper before table
            if (parent) {
                parent.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            }
        });
    }

    /**
     * Handle window resize
     */
    function handleResize() {
        let resizeTimer;

        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);

            resizeTimer = setTimeout(function () {
                const sidebar = document.querySelector('.sidebar');
                const toggle = document.querySelector('.menu-toggle');
                const overlay = document.querySelector('.sidebar-overlay');

                // Reset on desktop
                if (window.innerWidth >= 769) {
                    if (sidebar) sidebar.classList.remove('active');
                    if (toggle) {
                        toggle.classList.remove('active');
                        const icon = toggle.querySelector('i');
                        if (icon) {
                            icon.classList.remove('fa-times');
                            icon.classList.add('fa-bars');
                        }
                    }
                    if (overlay) overlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            }, 250);
        });
    }

    /**
     * Close menu on escape key
     */
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            const sidebar = document.querySelector('.sidebar');
            const toggle = document.querySelector('.menu-toggle');
            const overlay = document.querySelector('.sidebar-overlay');

            if (sidebar && sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
                if (toggle) toggle.classList.remove('active');
                if (overlay) overlay.classList.remove('active');

                const icon = toggle.querySelector('i');
                if (icon) {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
                document.body.style.overflow = '';
            }
        }
    });

    /**
     * Handle form inputs on mobile - prevent zoom on iOS
     */
    document.addEventListener('DOMContentLoaded', function () {
        const inputs = document.querySelectorAll('input, select, textarea');
        inputs.forEach(function (input) {
            // Prevent zoom on iOS when input is focused
            input.addEventListener('focus', function () {
                if (window.innerWidth < 769) {
                    document.querySelector('meta[name="viewport"]').setAttribute('content', 'width=device-width, initial-scale=1, maximum-scale=1');
                }
            });

            input.addEventListener('blur', function () {
                document.querySelector('meta[name="viewport"]').setAttribute('content', 'width=device-width, initial-scale=1.0');
            });
        });
    });

})();

