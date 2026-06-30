/**
 * Kaiadmin copies .sidebar .logo-header into .main-header .logo-header on load.
 * Keep mobile toolbar class/order; CSS shows logo text without the image.
 */
(function () {
    'use strict';

    var MOBILE_QUERY = window.matchMedia('(max-width: 991.98px)');

    function normalizeMobileToolbar() {
        var header = document.querySelector('.main-header .main-header-logo .logo-header');
        if (!header) {
            return;
        }

        header.classList.add('prms-mobile-toolbar');

        if (!MOBILE_QUERY.matches) {
            return;
        }

        var navToggle = header.querySelector('.nav-toggle');
        var brandLink = header.querySelector('.logo');
        var toolbarBrand = header.querySelector('.prms-mobile-toolbar-brand');

        header.querySelectorAll('.topbar-toggler.more').forEach(function (el) {
            el.remove();
        });

        if (navToggle) {
            header.prepend(navToggle);
        }

        if (toolbarBrand && navToggle) {
            navToggle.insertAdjacentElement('afterend', toolbarBrand);
        } else if (brandLink && navToggle) {
            navToggle.insertAdjacentElement('afterend', brandLink);
        }
    }

    function boot() {
        normalizeMobileToolbar();

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', normalizeMobileToolbar);
        }

        window.addEventListener('load', normalizeMobileToolbar);
        window.requestAnimationFrame(normalizeMobileToolbar);
    }

    boot();

    if (typeof MOBILE_QUERY.addEventListener === 'function') {
        MOBILE_QUERY.addEventListener('change', normalizeMobileToolbar);
    } else if (typeof MOBILE_QUERY.addListener === 'function') {
        MOBILE_QUERY.addListener(normalizeMobileToolbar);
    }
})();
