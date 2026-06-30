<script>
(function () {
    if (window.matchMedia('(max-width: 991.98px)').matches) {
        document.documentElement.classList.add('prms-mobile-shell');
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

    function applyAttr(selector, color) {
        document.querySelectorAll(selector).forEach(function (el) {
            if (el.classList.contains('prms-sidebar-sub')) {
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
