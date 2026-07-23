/**
 * Mobile / small screens: sidebar hidden by default; hamburger opens full menu.
 * Desktop (992px+): Kaiadmin sidebar_minimize icon-rail collapse.
 *
 * Viewport updates are debounced and only applied when mobile/desktop mode flips,
 * so docking DevTools (Inspect) does not thrash or freeze the page.
 */
(function () {
    'use strict';

    var MOBILE_QUERY = window.matchMedia('(max-width: 991.98px)');
    var VIEWPORT_DEBOUNCE_MS = 300;
    var lastMobile = null;
    var viewportTimer = null;

    function isMobile() {
        return MOBILE_QUERY.matches;
    }

    function setToggleIcons(collapsed) {
        var iconClass = collapsed ? 'gg-menu-right' : 'gg-menu-left';
        var label = collapsed ? 'Open navigation menu' : 'Close navigation menu';

        document.querySelectorAll('.main-header .toggle-sidebar, .main-header .sidenav-toggler').forEach(function (btn) {
            btn.classList.toggle('toggled', !collapsed);
            btn.innerHTML = '<i class="' + iconClass + '"></i>';
            btn.setAttribute('aria-label', label);
            btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        });
    }

    function isCollapsed() {
        return !document.documentElement.classList.contains('prms-mobile-sidebar-expanded');
    }

    function applyCollapsedState(collapsed) {
        var root = document.documentElement;

        root.classList.remove('nav_open');
        document.body.style.overflow = collapsed ? '' : 'hidden';

        if (collapsed) {
            root.classList.remove('prms-mobile-sidebar-expanded');
        } else {
            root.classList.add('prms-mobile-sidebar-expanded');
        }

        setToggleIcons(collapsed);
    }

    function initMobileShell() {
        document.documentElement.classList.add('prms-mobile-shell');
        document.documentElement.classList.remove('topbar_open');
        applyCollapsedState(true);
    }

    function teardownMobileShell() {
        var root = document.documentElement;

        root.classList.remove('prms-mobile-shell', 'prms-mobile-sidebar-expanded', 'nav_open');
        document.body.style.overflow = '';

        document.querySelectorAll('.main-header .toggle-sidebar, .main-header .sidenav-toggler').forEach(function (btn) {
            btn.classList.remove('toggled');
            btn.innerHTML = '<i class="gg-menu-right"></i>';
            btn.removeAttribute('aria-expanded');
            btn.setAttribute('aria-label', 'Toggle sidebar');
        });
    }

    function onMobileToggle(event) {
        if (!isMobile()) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        event.stopPropagation();
        applyCollapsedState(!isCollapsed());
    }

    function onMobileDismiss(event) {
        if (!isMobile() || isCollapsed()) {
            return;
        }

        var sidebar = document.querySelector('.wrapper > .sidebar:not(.prms-sidebar-sub)');

        if (sidebar && sidebar.contains(event.target)) {
            return;
        }

        if (event.target.closest('.main-header .nav-toggle, .main-header .toggle-sidebar, .main-header .sidenav-toggler')) {
            return;
        }

        applyCollapsedState(true);
    }

    function onMobileNavClick(event) {
        if (!isMobile() || isCollapsed()) {
            return;
        }

        if (event.target.closest('a[data-prms-submenu-toggle]')) {
            return;
        }

        if (event.target.closest('.sidebar-wrapper a[href]')) {
            applyCollapsedState(true);
        }
    }

    function bindMobileToggles() {
        document.querySelectorAll('.main-header .toggle-sidebar, .main-header .sidenav-toggler').forEach(function (btn) {
            btn.addEventListener('click', onMobileToggle, true);
        });

        document.addEventListener('click', onMobileDismiss);
        document.addEventListener('click', onMobileNavClick);
    }

    function applyViewportMode(force) {
        var mobile = isMobile();

        if (!force && lastMobile === mobile) {
            return;
        }

        lastMobile = mobile;

        if (mobile) {
            initMobileShell();
        } else {
            teardownMobileShell();
        }
    }

    function onViewportChange() {
        if (viewportTimer) {
            clearTimeout(viewportTimer);
        }

        viewportTimer = setTimeout(function () {
            viewportTimer = null;
            applyViewportMode(false);
        }, VIEWPORT_DEBOUNCE_MS);
    }

    function boot() {
        lastMobile = null;
        applyViewportMode(true);
        bindMobileToggles();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    window.addEventListener('resize', onViewportChange);

    if (typeof MOBILE_QUERY.addEventListener === 'function') {
        MOBILE_QUERY.addEventListener('change', onViewportChange);
    } else if (typeof MOBILE_QUERY.addListener === 'function') {
        MOBILE_QUERY.addListener(onViewportChange);
    }
})();
