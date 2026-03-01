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
        initSidebarCollapse();
    });

    /**
     * Initialize sidebar collapse functionality (icons-only mode in fullscreen)
     */
    function initSidebarCollapse() {
        // Check if fullscreen toggle exists
        const fullscreenBtn = document.querySelector('.fullscreen-toggle');
        if (!fullscreenBtn) return;

        // Listen for fullscreen changes
        document.addEventListener('fullscreenchange', handleFullscreenChange);
        document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
        document.addEventListener('mozfullscreenchange', handleFullscreenChange);
        document.addEventListener('MSFullscreenChange', handleFullscreenChange);

        // Also handle fullscreen button click
        fullscreenBtn.addEventListener('click', function () {
            // Delay to allow fullscreen state to change
            setTimeout(handleFullscreenChange, 100);
        });
    }

    /**
     * Handle fullscreen change - collapse sidebar in fullscreen mode
     */
    function handleFullscreenChange() {
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');

        if (!sidebar) return;

        const isFullscreen = document.fullscreenElement ||
            document.webkitFullscreenElement ||
            document.mozFullScreenElement ||
            document.msFullscreenElement;

        // Get sidebar width from CSS variable
        const sidebarWidth = getComputedStyle(document.documentElement).getPropertyValue('--sidebar-width').trim() || '260px';
        const collapsedWidth = '80px';

        if (isFullscreen) {
            // Collapse sidebar to icon-only mode in fullscreen
            sidebar.classList.add('fullscreen-collapsed');
            sidebar.classList.remove('collapsed');

            // Adjust main content to expand
            if (mainContent) {
                mainContent.classList.add('fullscreen-expanded');
                mainContent.style.marginLeft = collapsedWidth;
                mainContent.style.width = 'calc(100% - ' + collapsedWidth + ')';
                mainContent.style.maxWidth = 'calc(100vw - ' + collapsedWidth + ')';
            }

            // Prevent horizontal scroll when in fullscreen
            document.body.style.overflowX = 'hidden';
            document.documentElement.style.overflowX = 'hidden';
        } else {
            // Restore sidebar to full width
            sidebar.classList.remove('fullscreen-collapsed');

            // Restore main content
            if (mainContent) {
                mainContent.classList.remove('fullscreen-expanded');

                // Check if we're on desktop
                if (window.innerWidth >= 769) {
                    mainContent.style.marginLeft = sidebarWidth;
                    mainContent.style.width = 'calc(100% - ' + sidebarWidth + ')';
                    mainContent.style.maxWidth = 'calc(100vw - ' + sidebarWidth + ')';
                } else {
                    mainContent.style.marginLeft = '0';
                    mainContent.style.width = '100%';
                    mainContent.style.maxWidth = '100%';
                }
            }

            // Restore normal overflow
            document.body.style.overflowX = '';
            document.documentElement.style.overflowX = '';
        }
    }

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

        // Don't show on login page
        if (document.querySelector('.login-container')) return;

        // Find header actions container - try multiple selectors
        let headerActions = document.querySelector('.header-actions');

        // If not found, try to find it within top-header
        if (!headerActions) {
            const topHeader = document.querySelector('.top-header');
            if (topHeader) {
                // Check if there's already a header-actions div
                headerActions = topHeader.querySelector('.header-actions');

                // If not, create one and append the existing elements
                if (!headerActions) {
                    headerActions = document.createElement('div');
                    headerActions.className = 'header-actions';

                    // Move notification button and user info to header-actions if they exist
                    const notificationBtn = topHeader.querySelector('.notification-btn');
                    const userInfo = topHeader.querySelector('.user-info');

                    if (notificationBtn || userInfo) {
                        topHeader.appendChild(headerActions);
                        if (notificationBtn) headerActions.appendChild(notificationBtn);
                        if (userInfo) headerActions.appendChild(userInfo);
                    }
                }
            }
        }

        // If still no container, create a floating fullscreen button
        if (!headerActions) {
            createFloatingFullscreenButton();
            return;
        }

        // Create fullscreen toggle button
        const fullscreenBtn = document.createElement('button');
        fullscreenBtn.className = 'fullscreen-toggle';
        fullscreenBtn.setAttribute('aria-label', 'Toggle fullscreen');
        fullscreenBtn.setAttribute('title', 'Toggle Fullscreen');
        fullscreenBtn.innerHTML = '<i class="fas fa-expand"></i>';
        fullscreenBtn.style.cssText = 'visibility: visible !important; opacity: 1 !important; display: inline-flex !important; position: relative !important;';

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
     * Create floating fullscreen button for pages without header
     * Only for authenticated pages, not for login page
     */
    function createFloatingFullscreenButton() {
        // Don't create floating button on login page
        if (document.querySelector('.login-container')) {
            return;
        }

        // Check if button already exists
        if (document.querySelector('.fullscreen-toggle.floating-fullscreen')) {
            return;
        }

        const fullscreenBtn = document.createElement('button');
        fullscreenBtn.className = 'fullscreen-toggle floating-fullscreen';
        fullscreenBtn.setAttribute('aria-label', 'Toggle fullscreen');
        fullscreenBtn.setAttribute('title', 'Toggle Fullscreen');
        fullscreenBtn.innerHTML = '<i class="fas fa-expand"></i>';

        // Add floating styles
        fullscreenBtn.style.cssText = 'position: fixed !important; top: 15px !important; right: 15px !important; z-index: 9999 !important; visibility: visible !important; opacity: 1 !important; display: inline-flex !important; background: rgba(255,255,255,0.9) !important; box-shadow: 0 2px 10px rgba(0,0,0,0.1) !important;';

        // Add click handler for fullscreen
        fullscreenBtn.addEventListener('click', function (e) {
            e.preventDefault();
            toggleFullscreen();
        });

        // Add button to body
        document.body.appendChild(fullscreenBtn);

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
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen().catch((err) => {
                console.log(`Error attempting fullscreen: ${err.message}`);
            });
        } else {
            document.exitFullscreen();
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
                const mainContent = document.querySelector('.main-content');

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

                    // Reset main content margin if not in fullscreen
                    if (mainContent && !document.fullscreenElement) {
                        mainContent.style.marginLeft = '';
                        mainContent.style.width = '';
                        mainContent.style.maxWidth = '';
                    }
                } else {
                    // On mobile, ensure sidebar is collapsed
                    if (sidebar) sidebar.classList.remove('collapsed');
                    if (mainContent) {
                        mainContent.style.marginLeft = '0';
                        mainContent.style.width = '100%';
                        mainContent.style.maxWidth = '100%';
                    }
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

