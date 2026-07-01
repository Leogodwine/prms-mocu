/**
 * Kaiadmin copies .sidebar .logo-header into .main-header .logo-header on load.
 * Rebuild mobile toolbar: [hamburger] [MoCU-PRMS] with utility icons on the right.
 */
(function () {
    'use strict';

    var MOBILE_QUERY = window.matchMedia('(max-width: 999.98px)');

    function resolveBrandName(header) {
        var toolbarBrand = header.querySelector('.prms-mobile-toolbar-brand');
        if (toolbarBrand && toolbarBrand.textContent.trim()) {
            return toolbarBrand.textContent.trim();
        }

        var sidebarBrand = header.querySelector('.prms-sidebar-brand-name');
        if (sidebarBrand && sidebarBrand.textContent.trim()) {
            return sidebarBrand.textContent.trim();
        }

        var titlePart = document.title.split('|').pop();
        if (titlePart && titlePart.trim()) {
            return titlePart.trim();
        }

        return 'MoCU-PRMS';
    }

    function normalizeMobileToolbar() {
        var header = document.querySelector('.main-header .main-header-logo .logo-header');
        if (!header) {
            return;
        }

        header.classList.add('prms-mobile-toolbar');
        document.documentElement.classList.remove('topbar_open');

        if (!MOBILE_QUERY.matches) {
            return;
        }

        var brandName = resolveBrandName(header);
        var navToggle = header.querySelector('.nav-toggle');

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

        if (navToggle) {
            header.prepend(navToggle);
            navToggle.insertAdjacentElement('afterend', brand);
        }

        header.querySelectorAll('.logo').forEach(function (el) {
            el.remove();
        });
    }

    function boot() {
        normalizeMobileToolbar();

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', normalizeMobileToolbar);
        }

        window.addEventListener('load', normalizeMobileToolbar);
        window.requestAnimationFrame(normalizeMobileToolbar);
        setTimeout(normalizeMobileToolbar, 0);
    }

    boot();

    if (typeof MOBILE_QUERY.addEventListener === 'function') {
        MOBILE_QUERY.addEventListener('change', normalizeMobileToolbar);
    } else if (typeof MOBILE_QUERY.addListener === 'function') {
        MOBILE_QUERY.addListener(normalizeMobileToolbar);
    }
})();
