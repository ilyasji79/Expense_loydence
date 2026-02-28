/**
 * Mobile Menu Toggle Script
 * Expense Management ERP - Loydence Academy
 * 
 * Handles mobile navigation, table wrapping, responsive behavior, and fullscreen toggle
 * Mobile-first approach
 */

(function () {
    'use strict';

    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function () {
        initMobileMenu();
        initFullscreenToggle();
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
     * Initialize fullscreen toggle button
     */
    function initFullscreenToggle() {
        // Check if fullscreen toggle already exists
        if (document.querySelector('.fullscreen-toggle')) return;

        // Find header actions container
        let headerActions = document.querySelector('.header-actions');

        // If no header actions, create one in the top header
        if (!headerActions) {
            const topHeader = document.querySelector('.top-header');
            if (topHeader) {
                headerActions = document.createElement('div');
                headerActions.className = 'header-actions';
                topHeader.appendChild(headerActions);
            }
        }

        // If still no container, exit
        if (!headerActions) return;

        // Create fullscreen toggle button
        const fullscreenBtn = document.createElement('button');
        fullscreenBtn.className = 'fullscreen-toggle';
        fullscreenBtn.setAttribute('aria-label', 'Toggle fullscreen');
        fullscreenBtn.setAttribute('title', 'Toggle Fullscreen');
        fullscreenBtn.innerHTML = '<i class="fas fa-expand"></i>';

        // Add click handler for fullscreen
        fullscreenBtn.addEventListener('click', function (e) {
            e.preventDefault();
            toggleFullscreen();
        });

        // Add button to header
        headerActions.appendChild(fullscreenBtn);

        // Listen for fullscreen changes to update icon
        document.addEventListener('fullscreenchange', updateFullscreenIcon);
        document.addEventListener('webkitfullscreenchange', updateFullscreenIcon);
        document.addEventListener('mozfullscreenchange', updateFullscreenIcon);
        document.addEventListener('MSFullscreenChange', updateFullscreenIcon);
    }

    /**
     * Toggle fullscreen mode
     */
    function toggleFullscreen() {
        const elem = document.documentElement;

        // Check if in fullscreen
        if (!document.fullscreenElement &&
            !document.webkitFullscreenElement &&
            !document.mozFullScreenElement &&
            !document.msFullscreenElement) {

            // Enter fullscreen
            if (elem.requestFullscreen) {
                elem.requestFullscreen();
            } else if (elem.webkitRequestFullscreen) { /* Safari */
                elem.webkitRequestFullscreen();
            } else if (elem.mozRequestFullScreen) { /* Firefox */
                elem.mozRequestFullScreen();
            } else if (elem.msRequestFullscreen) { /* IE11 */
                elem.msRequestFullscreen();
            }
        } else {
            // Exit fullscreen
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.webkitExitFullscreen) { /* Safari */
                document.webkitExitFullscreen();
            } else if (document.mozCancelFullScreen) { /* Firefox */
                document.mozCancelFullScreen();
            } else if (document.msExitFullscreen) { /* IE11 */
                document.msExitFullscreen();
            }
        }
    }

    /**
     * Update fullscreen icon based on current state
     */
    function updateFullscreenIcon() {
        const btn = document.querySelector('.fullscreen-toggle');
        if (!btn) return;

        const icon = btn.querySelector('i');
        if (!icon) return;

        const isFullscreen = document.fullscreenElement ||
            document.webkitFullscreenElement ||
            document.mozFullScreenElement ||
            document.msFullscreenElement;

        if (isFullscreen) {
            icon.classList.remove('fa-expand');
            icon.classList.add('fa-compress');
            btn.setAttribute('title', 'Exit Fullscreen');
        } else {
            icon.classList.remove('fa-compress');
            icon.classList.add('fa-expand');
            btn.setAttribute('title', 'Toggle Fullscreen');
        }
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

