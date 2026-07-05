/**
 * Kaiadmin copies .sidebar .logo-header into .main-header .logo-header on load.
 * Rebuild mobile toolbar: [hamburger] [MoCU-PRMS] with utility icons on the right.
 */
(function ($) {
    'use strict';

    var MOBILE_QUERY = window.matchMedia('(max-width: 999.98px)');

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
        navbar.style.width = 'auto';
        navbar.style.maxWidth = '52%';
        navbar.style.height = '3.25rem';
        navbar.style.minHeight = '3.25rem';
        navbar.style.zIndex = '5';
        navbar.style.pointerEvents = 'none';
        navbar.style.background = 'transparent';
        navbar.style.border = 'none';
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

    function normalizeMobileToolbar() {
        if (!isMobile()) {
            return;
        }

        var header = document.querySelector('.main-header .main-header-logo .logo-header');
        if (!header) {
            return;
        }

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
    }

    function scheduleNormalizes() {
        normalizeMobileToolbar();
        window.requestAnimationFrame(normalizeMobileToolbar);
        setTimeout(normalizeMobileToolbar, 0);
        setTimeout(normalizeMobileToolbar, 50);
        setTimeout(normalizeMobileToolbar, 200);
        setTimeout(normalizeMobileToolbar, 500);
    }

    function watchToolbar() {
        var slot = document.querySelector('.main-header .main-header-logo');
        if (!slot || typeof MutationObserver === 'undefined') {
            return;
        }

        new MutationObserver(function () {
            if (isMobile()) {
                scheduleNormalizes();
            }
        }).observe(slot, { childList: true, subtree: true, attributes: true });
    }

    if (typeof $ === 'function') {
        $(scheduleNormalizes);
        $(watchToolbar);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scheduleNormalizes);
    } else {
        scheduleNormalizes();
    }

    document.addEventListener('DOMContentLoaded', watchToolbar);
    window.addEventListener('load', scheduleNormalizes);

    if (typeof MOBILE_QUERY.addEventListener === 'function') {
        MOBILE_QUERY.addEventListener('change', scheduleNormalizes);
    } else if (typeof MOBILE_QUERY.addListener === 'function') {
        MOBILE_QUERY.addListener(scheduleNormalizes);
    }
})(window.jQuery);
