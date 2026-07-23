/**
 * Kaiadmin copies .sidebar .logo-header into .main-header .logo-header on load.
 * Rebuild mobile toolbar: [hamburger] [MoCU-PRMS] with utility icons on the right.
 *
 * Guarded against DevTools Inspect freezes: MutationObserver ignores our own writes,
 * normalize runs at most once per viewport mode change, resize is heavily debounced.
 */
(function ($) {
    'use strict';

    var MOBILE_QUERY = window.matchMedia('(max-width: 991.98px)');
    var VIEWPORT_DEBOUNCE_MS = 300;
    var NAV_INLINE_PROPS = [
        'transform',
        'webkitTransform',
        'visibility',
        'opacity',
        'display',
        'position',
        'top',
        'right',
        'left',
        'width',
        'maxWidth',
        'height',
        'minHeight',
        'zIndex',
        'pointerEvents',
        'background',
        'border',
        'overflow',
    ];

    var isNormalizing = false;
    var suppressObserver = false;
    var lastMobile = null;
    var viewportTimer = null;
    var bootDone = false;

    function isMobile() {
        return MOBILE_QUERY.matches;
    }

    function resolveBrandName(header) {
        var toolbarBrand = header.querySelector('.prms-mobile-toolbar-brand');
        if (toolbarBrand && toolbarBrand.textContent.trim()) {
            return toolbarBrand.textContent.trim();
        }

        var sidebarBrand = header.querySelector('.prms-sidebar-brand-name');
        if (sidebarBrand && sidebarBrand.textContent.trim()) {
            return sidebarBrand.textContent.trim();
        }

        var sidebarRootBrand = document.querySelector('.sidebar .prms-sidebar-brand-name');
        if (sidebarRootBrand && sidebarRootBrand.textContent.trim()) {
            return sidebarRootBrand.textContent.trim();
        }

        var titlePart = document.title.split('|').pop();
        if (titlePart && titlePart.trim()) {
            return titlePart.trim();
        }

        return 'MoCU-PRMS';
    }

    function ensureToolbarNavToggle(header) {
        var navToggle = header.querySelector('.nav-toggle');
        if (navToggle) {
            return navToggle;
        }

        var sidebarToggle = document.querySelector('.sidebar .logo-header .nav-toggle');
        if (sidebarToggle) {
            navToggle = sidebarToggle.cloneNode(true);
            header.prepend(navToggle);
            return navToggle;
        }

        navToggle = document.createElement('div');
        navToggle.className = 'nav-toggle';
        navToggle.innerHTML =
            '<button type="button" class="btn btn-toggle toggle-sidebar" aria-label="Open navigation menu">' +
            '<i class="gg-menu-right"></i></button>';
        header.prepend(navToggle);

        return navToggle;
    }

    function forceMobileNavVisible() {
        var navbar = document.querySelector('.main-header .navbar-header');
        if (!navbar) {
            return;
        }

        navbar.style.transform = 'none';
        navbar.style.webkitTransform = 'none';
        navbar.style.visibility = 'visible';
        navbar.style.opacity = '1';
        navbar.style.display = 'flex';
        navbar.style.position = 'absolute';
        navbar.style.top = '0';
        navbar.style.right = '0';
        navbar.style.left = 'auto';
        navbar.style.width = 'max-content';
        navbar.style.maxWidth = 'none';
        navbar.style.height = '3.25rem';
        navbar.style.minHeight = '3.25rem';
        navbar.style.zIndex = '5';
        navbar.style.pointerEvents = 'none';
        navbar.style.background = 'transparent';
        navbar.style.border = 'none';
        navbar.style.overflow = 'visible';
    }

    function clearMobileNavInlineStyles() {
        var navbar = document.querySelector('.main-header .navbar-header');
        if (!navbar) {
            return;
        }

        NAV_INLINE_PROPS.forEach(function (prop) {
            navbar.style[prop] = '';
        });
    }

    function forceHamburgerVisible(navToggle) {
        if (!navToggle) {
            return;
        }

        navToggle.style.display = 'flex';
        navToggle.style.visibility = 'visible';
        navToggle.style.opacity = '1';
        navToggle.style.position = 'static';
        navToggle.style.left = 'auto';
        navToggle.style.right = 'auto';

        navToggle.querySelectorAll('.toggle-sidebar').forEach(function (btn) {
            btn.style.display = 'inline-flex';
            btn.style.visibility = 'visible';
            btn.style.opacity = '1';
            btn.style.color = '#fff';
        });

        navToggle.querySelectorAll('.sidenav-toggler').forEach(function (btn) {
            btn.style.display = 'none';
        });
    }

    function clearHamburgerInlineStyles(navToggle) {
        if (!navToggle) {
            return;
        }

        ['display', 'visibility', 'opacity', 'position', 'left', 'right'].forEach(function (prop) {
            navToggle.style[prop] = '';
        });

        navToggle.querySelectorAll('.toggle-sidebar, .sidenav-toggler').forEach(function (btn) {
            ['display', 'visibility', 'opacity', 'color'].forEach(function (prop) {
                btn.style[prop] = '';
            });
        });
    }

    function toolbarLooksReady(header) {
        return !!(
            header.classList.contains('prms-mobile-toolbar') &&
            header.querySelector('.nav-toggle .toggle-sidebar') &&
            header.querySelector('.prms-mobile-toolbar-brand')
        );
    }

    function withObserverSuppressed(fn) {
        suppressObserver = true;
        try {
            fn();
        } finally {
            // Defer clear so nested MutationObserver callbacks from our writes are ignored.
            window.setTimeout(function () {
                suppressObserver = false;
            }, 0);
        }
    }

    function normalizeMobileToolbar() {
        if (!isMobile() || isNormalizing) {
            return;
        }

        var header = document.querySelector('.main-header .main-header-logo .logo-header');
        if (!header) {
            return;
        }

        if (toolbarLooksReady(header)) {
            forceHamburgerVisible(header.querySelector('.nav-toggle'));
            forceMobileNavVisible();
            return;
        }

        isNormalizing = true;
        withObserverSuppressed(function () {
            document.documentElement.classList.add('prms-mobile-shell');
            document.documentElement.classList.remove('topbar_open');

            header.classList.add('prms-mobile-toolbar');
            header.setAttribute('data-background-color', 'dark');

            var brandName = resolveBrandName(header);
            var navToggle = ensureToolbarNavToggle(header);

            header.querySelectorAll('.topbar-toggler.more').forEach(function (el) {
                el.remove();
            });

            header.querySelectorAll('.logo img, .logo .prms-brand-logo').forEach(function (el) {
                el.remove();
            });

            var brand = header.querySelector('.prms-mobile-toolbar-brand');
            if (!brand) {
                brand = document.createElement('span');
                brand.className = 'prms-sidebar-brand-name prms-mobile-toolbar-brand';
                header.appendChild(brand);
            }

            brand.textContent = brandName;

            header.prepend(navToggle);
            navToggle.insertAdjacentElement('afterend', brand);

            header.querySelectorAll('.logo').forEach(function (el) {
                el.remove();
            });

            forceHamburgerVisible(navToggle);
            forceMobileNavVisible();
        });
        isNormalizing = false;
    }

    function teardownMobileToolbar() {
        if (isNormalizing) {
            return;
        }

        isNormalizing = true;
        withObserverSuppressed(function () {
            clearMobileNavInlineStyles();

            var header = document.querySelector('.main-header .main-header-logo .logo-header');
            if (header) {
                header.classList.remove('prms-mobile-toolbar');
                clearHamburgerInlineStyles(header.querySelector('.nav-toggle'));

                var sidebarLogo = document.querySelector('.sidebar .logo-header');
                if (sidebarLogo) {
                    header.innerHTML = sidebarLogo.innerHTML;
                }
            }
        });
        isNormalizing = false;
    }

    /**
     * Only react when the mobile/desktop mode actually flips.
     * Avoids thrashing when DevTools docks and fires many resize events.
     */
    function applyViewportMode(force) {
        var mobile = isMobile();

        if (!force && lastMobile === mobile) {
            return;
        }

        lastMobile = mobile;
        document.documentElement.classList.toggle('prms-mobile-shell', mobile);

        if (mobile) {
            document.documentElement.classList.remove('topbar_open');
            normalizeMobileToolbar();
        } else {
            document.documentElement.classList.remove('prms-mobile-sidebar-expanded', 'nav_open');
            teardownMobileToolbar();
        }
    }

    function scheduleViewportApply(force) {
        if (viewportTimer) {
            clearTimeout(viewportTimer);
        }

        viewportTimer = setTimeout(function () {
            viewportTimer = null;
            applyViewportMode(!!force);
        }, VIEWPORT_DEBOUNCE_MS);
    }

    function onViewportChange() {
        scheduleViewportApply(false);
    }

    function watchToolbar() {
        var slot = document.querySelector('.main-header .main-header-logo');
        if (!slot || typeof MutationObserver === 'undefined') {
            return;
        }

        new MutationObserver(function () {
            if (suppressObserver || isNormalizing || !isMobile()) {
                return;
            }

            // Kaiadmin may replace the logo strip once after load — rebuild once, not in a loop.
            scheduleViewportApply(true);
        }).observe(slot, { childList: true, subtree: false });
    }

    function boot() {
        if (bootDone) {
            return;
        }
        bootDone = true;

        lastMobile = null;
        applyViewportMode(true);
        watchToolbar();
    }

    if (typeof $ === 'function') {
        $(boot);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    window.addEventListener('load', function () {
        // One late pass after Kaiadmin logo copy — not a timeout chain.
        scheduleViewportApply(true);
    });

    window.addEventListener('resize', onViewportChange);

    if (typeof MOBILE_QUERY.addEventListener === 'function') {
        MOBILE_QUERY.addEventListener('change', onViewportChange);
    } else if (typeof MOBILE_QUERY.addListener === 'function') {
        MOBILE_QUERY.addListener(onViewportChange);
    }
})(window.jQuery);
