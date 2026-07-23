<script>
(function () {
    var MOBILE_QUERY = window.matchMedia('(max-width: 991.98px)');

    function syncMobileShell() {
        if (MOBILE_QUERY.matches) {
            document.documentElement.classList.add('prms-mobile-shell');
            document.documentElement.classList.remove('topbar_open');
        } else {
            document.documentElement.classList.remove('prms-mobile-shell', 'prms-mobile-sidebar-expanded');
        }
    }

    syncMobileShell();

    if (typeof MOBILE_QUERY.addEventListener === 'function') {
        MOBILE_QUERY.addEventListener('change', syncMobileShell);
    } else if (typeof MOBILE_QUERY.addListener === 'function') {
        MOBILE_QUERY.addListener(syncMobileShell);
    }
})();
(function () {
    var key = 'prms-chrome-colors';
    var defaults = { logo: 'dark', navbar: 'white', sidebar: 'dark' };
    var saved = defaults;
    try {
        var raw = localStorage.getItem(key);
        if (raw) saved = Object.assign({}, defaults, JSON.parse(raw));
    } catch (e) {}

    function applyChromeColors() {
        var isMobile = window.matchMedia('(max-width: 991.98px)').matches;

        function applyAttr(selector, color) {
            document.querySelectorAll(selector).forEach(function (el) {
                if (el.classList.contains('prms-sidebar-sub')) {
                    return;
                }
                if (isMobile && el.closest('.main-header .main-header-logo')) {
                    el.setAttribute('data-background-color', 'dark');
                    return;
                }
                if (!color) {
                    el.removeAttribute('data-background-color');
                } else {
                    el.setAttribute('data-background-color', color);
                }
            });
        }

        applyAttr('.logo-header', saved.logo);
        applyAttr('.main-header .navbar-header', saved.navbar);
        applyAttr('.wrapper > .sidebar', saved.sidebar);
    }

    applyChromeColors();

    var colorMq = window.matchMedia('(max-width: 991.98px)');
    if (typeof colorMq.addEventListener === 'function') {
        colorMq.addEventListener('change', applyChromeColors);
    } else if (typeof colorMq.addListener === 'function') {
        colorMq.addListener(applyChromeColors);
    }
})();
</script>
<style id="prms-mobile-topbar-critical">
@media screen and (max-width: 991.98px) {
  body:not(.prms-kaiadmin-public):not(.prms-landing) .wrapper .main-panel > .main-header {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    width: 100% !important;
    min-height: 3.25rem !important;
    z-index: 1040 !important;
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    transform: none !important;
    float: none !important;
  }

  body:not(.prms-kaiadmin-public):not(.prms-landing) .wrapper .main-panel > .main-header .main-header-logo {
    display: block !important;
    width: 100% !important;
    visibility: visible !important;
  }

  body:not(.prms-kaiadmin-public):not(.prms-landing) .wrapper .main-panel > .main-header .main-header-logo .logo-header {
    display: flex !important;
    align-items: center !important;
    width: 100% !important;
    min-height: 3.25rem !important;
    height: 3.25rem !important;
    float: none !important;
    background: #1a2035 !important;
    background-color: #1a2035 !important;
    visibility: visible !important;
  }

  body:not(.prms-kaiadmin-public):not(.prms-landing) .wrapper .main-panel > .main-header .logo-header .nav-toggle {
    display: flex !important;
    visibility: visible !important;
    opacity: 1 !important;
    position: static !important;
    left: auto !important;
    right: auto !important;
  }

  body:not(.prms-kaiadmin-public):not(.prms-landing) .wrapper .main-panel > .main-header .logo-header .nav-toggle .toggle-sidebar {
    display: inline-flex !important;
    visibility: visible !important;
    opacity: 1 !important;
    color: #fff !important;
  }

  body:not(.prms-kaiadmin-public):not(.prms-landing) .wrapper .main-panel > .main-header .logo-header .nav-toggle .sidenav-toggler,
  body:not(.prms-kaiadmin-public):not(.prms-landing) .wrapper .main-panel > .main-header .logo-header .topbar-toggler.more {
    display: none !important;
  }

  body:not(.prms-kaiadmin-public):not(.prms-landing) .wrapper .main-panel > .main-header .prms-mobile-toolbar-brand,
  body:not(.prms-kaiadmin-public):not(.prms-landing) .wrapper .main-panel > .main-header .logo-header .prms-sidebar-brand-name {
    display: block !important;
    color: #fff !important;
    visibility: visible !important;
  }

  body:not(.prms-kaiadmin-public):not(.prms-landing) .wrapper .main-panel > .main-header .navbar-header,
  body:not(.prms-kaiadmin-public):not(.prms-landing) .wrapper .main-panel > .main-header .prms-app-topnav,
  html.topbar_open body:not(.prms-kaiadmin-public):not(.prms-landing) .wrapper .main-panel > .main-header .navbar-header {
    position: absolute !important;
    top: 0 !important;
    right: 0 !important;
    left: auto !important;
    width: max-content !important;
    max-width: none !important;
    height: 3.25rem !important;
    min-height: 3.25rem !important;
    transform: none !important;
    -webkit-transform: none !important;
    display: flex !important;
    visibility: visible !important;
    opacity: 1 !important;
    background: transparent !important;
    border: none !important;
    overflow: visible !important;
    z-index: 5 !important;
    pointer-events: none;
  }

  body:not(.prms-kaiadmin-public):not(.prms-landing) .wrapper .main-panel > .main-header .navbar-header .container-fluid,
  body:not(.prms-kaiadmin-public):not(.prms-landing) .wrapper .main-panel > .main-header .navbar-header .navbar-nav,
  body:not(.prms-kaiadmin-public):not(.prms-landing) .wrapper .main-panel > .main-header .navbar-header .nav-link,
  body:not(.prms-kaiadmin-public):not(.prms-landing) .wrapper .main-panel > .main-header .navbar-header .dropdown-toggle,
  body:not(.prms-kaiadmin-public):not(.prms-landing) .wrapper .main-panel > .main-header .navbar-header button {
    pointer-events: auto;
  }

  body:not(.prms-kaiadmin-public):not(.prms-landing) .wrapper .main-panel > .main-header .navbar-header .navbar-nav {
    flex-direction: row !important;
    flex-wrap: nowrap !important;
    align-items: center !important;
    justify-content: flex-end !important;
    gap: 0.1rem !important;
    width: max-content !important;
  }

  body:not(.prms-kaiadmin-public):not(.prms-landing) .wrapper .main-panel > .main-header .navbar-header .nav-link,
  body:not(.prms-kaiadmin-public):not(.prms-landing) .wrapper .main-panel > .main-header .navbar-header .nav-link i {
    color: #fff !important;
  }

  body:not(.prms-kaiadmin-public):not(.prms-landing) .wrapper .main-panel > .container,
  body:not(.prms-kaiadmin-public):not(.prms-landing) .wrapper .main-panel > .container-full {
    margin-top: 3.25rem !important;
  }
}
</style>
