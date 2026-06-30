/**
 * Mobile / small screens: persistent icon rail by default; menu toggles full sidebar.
 * Desktop keeps Kaiadmin's native sidebar_minimize behaviour.
 */
(function () {
    'use strict';

    var MOBILE_QUERY = window.matchMedia('(max-width: 991.98px)');

    function isMobile() {
        return MOBILE_QUERY.matches;
    }

    function setToggleIcons(collapsed) {
        var iconClass = collapsed ? 'gg-menu-right' : 'gg-menu-left';
        var label = collapsed ? 'Expand navigation menu' : 'Collapse navigation menu';

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

        if (collapsed) {
            root.classList.remove('prms-mobile-sidebar-expanded');
        } else {
            root.classList.add('prms-mobile-sidebar-expanded');
        }

        setToggleIcons(collapsed);
    }

    function initMobileShell() {
        document.documentElement.classList.add('prms-mobile-shell');
        applyCollapsedState(true);
    }

    function teardownMobileShell() {
        var root = document.documentElement;

        root.classList.remove('prms-mobile-shell', 'prms-mobile-sidebar-expanded', 'nav_open');

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
        applyCollapsedState(!isCollapsed());
    }

    function bindMobileToggles() {
        document.querySelectorAll('.main-header .toggle-sidebar, .main-header .sidenav-toggler').forEach(function (btn) {
            btn.addEventListener('click', onMobileToggle, true);
        });
    }

    function onViewportChange() {
        if (isMobile()) {
            initMobileShell();
        } else {
            teardownMobileShell();
        }
    }

    function boot() {
        if (isMobile()) {
            initMobileShell();
        }
        bindMobileToggles();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    if (typeof MOBILE_QUERY.addEventListener === 'function') {
        MOBILE_QUERY.addEventListener('change', onViewportChange);
    } else if (typeof MOBILE_QUERY.addListener === 'function') {
        MOBILE_QUERY.addListener(onViewportChange);
    }
})();
