<script>
(function () {
    if (window.matchMedia('(max-width: 999.98px)').matches) {
        document.documentElement.classList.add('prms-mobile-shell');
        document.documentElement.classList.remove('topbar_open');
    }
})();
(function () {
    var key = 'prms-chrome-colors';
    var defaults = { logo: 'dark', navbar: 'white', sidebar: 'dark' };
    var saved = defaults;
    var isMobile = window.matchMedia('(max-width: 999.98px)').matches;
    try {
        var raw = localStorage.getItem(key);
        if (raw) saved = Object.assign({}, defaults, JSON.parse(raw));
    } catch (e) {}

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
})();
</script>
<style id="prms-mobile-topbar-critical">
@media screen and (max-width: 999.98px) {
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
  body:not(.prms-kaiadmin-public):not(.prms-landing) .wrapper .main-panel > .main-header .prms-app-topnav {
    position: absolute !important;
    top: 0 !important;
    right: 0 !important;
    left: auto !important;
    width: auto !important;
    max-width: 52% !important;
    height: 3.25rem !important;
    min-height: 3.25rem !important;
    transform: none !important;
    -webkit-transform: none !important;
    display: flex !important;
    visibility: visible !important;
    opacity: 1 !important;
    background: transparent !important;
    border: none !important;
    z-index: 5 !important;
  }

  body:not(.prms-kaiadmin-public):not(.prms-landing) .wrapper .main-panel > .main-header .navbar-header .navbar-nav {
    flex-direction: row !important;
    flex-wrap: nowrap !important;
    align-items: center !important;
    justify-content: flex-end !important;
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

@media screen and (min-width: 992px) and (max-width: 999.98px) {
  body:not(.prms-kaiadmin-public):not(.prms-landing) .wrapper .main-panel > .main-header .main-header-logo {
    display: block !important;
  }
}
</style>
