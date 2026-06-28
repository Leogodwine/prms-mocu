/**
 * Kaiadmin copies .sidebar .logo-header HTML into .main-header .logo-header on load,
 * which restores logo + MoCU-PRMS in the mobile top bar. Strip branding from the
 * top bar only; sidebar header keeps logo + product name.
 */
(function () {
    'use strict';

    var MOBILE_QUERY = window.matchMedia('(max-width: 991.98px)');

    function stripTopBarBranding() {
        var header = document.querySelector('.main-header .main-header-logo .logo-header');
        if (!header) {
            return;
        }

        header.classList.add('prms-mobile-toolbar');

        if (!MOBILE_QUERY.matches) {
            return;
        }

        header.querySelectorAll('.logo, .topbar-toggler.more').forEach(function (el) {
            el.remove();
        });
    }

    stripTopBarBranding();

    if (typeof MOBILE_QUERY.addEventListener === 'function') {
        MOBILE_QUERY.addEventListener('change', stripTopBarBranding);
    } else if (typeof MOBILE_QUERY.addListener === 'function') {
        MOBILE_QUERY.addListener(stripTopBarBranding);
    }
})();
