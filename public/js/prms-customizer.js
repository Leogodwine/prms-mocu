/**
 * KaiAdmin-style layout customizer with localStorage persistence.
 * Requires jQuery + kaiadmin.min.js (layoutsColors).
 */
(function (window, $) {
    'use strict';

    if (!$) return;

    var STORAGE_KEY = 'prms-chrome-colors';
    var DEFAULTS = { logo: 'dark', navbar: 'white', sidebar: 'dark' };

    function readSaved() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            if (raw) {
                return Object.assign({}, DEFAULTS, JSON.parse(raw));
            }
        } catch (e) {}
        return Object.assign({}, DEFAULTS);
    }

    function writeSaved(data) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
        } catch (e) {}
    }

    function applyLogoColor(color) {
        if (!color) {
            $('.logo-header').removeAttr('data-background-color');
        } else {
            $('.logo-header').attr('data-background-color', color);
        }
    }

    function applyNavbarColor(color) {
        var $nav = $('.main-header .navbar-header');
        if (!color) {
            $nav.removeAttr('data-background-color');
        } else {
            $nav.attr('data-background-color', color);
        }
    }

    function applySidebarColor(color) {
        var $sidebars = $('.wrapper > .sidebar').not('.prms-sidebar-sub');
        if (!color) {
            $sidebars.removeAttr('data-background-color');
        } else {
            $sidebars.attr('data-background-color', color);
        }
    }

    function applyAll(saved) {
        applyLogoColor(saved.logo);
        applyNavbarColor(saved.navbar);
        applySidebarColor(saved.sidebar);
        if (typeof layoutsColors === 'function') {
            layoutsColors();
        }
        markSelected(saved);
    }

    function markSelected(saved) {
        var check = '<i class="gg-check"></i>';

        $('.changeLogoHeaderColor').removeClass('selected').empty();
        $('.changeLogoHeaderColor[data-color="' + saved.logo + '"]').addClass('selected').append(check);

        $('.changeTopBarColor').removeClass('selected').empty();
        $('.changeTopBarColor[data-color="' + saved.navbar + '"]').addClass('selected').append(check);

        $('.changeSideBarColor').removeClass('selected').empty();
        $('.changeSideBarColor[data-color="' + saved.sidebar + '"]').addClass('selected').append(check);
    }

    function bindPanel() {
        var $panel = $('#prms-kaiadmin-customizer');
        if (!$panel.length) return;

        var open = false;
        var $toggle = $panel.find('.custom-toggle');

        $toggle.on('click', function () {
            open = !open;
            $panel.toggleClass('open', open);
            $toggle.toggleClass('toggled', open);
            $toggle.attr('aria-expanded', open ? 'true' : 'false');
        });

        $('.changeLogoHeaderColor').on('click', function () {
            var saved = readSaved();
            saved.logo = $(this).attr('data-color');
            writeSaved(saved);
            applyAll(saved);
        });

        $('.changeTopBarColor').on('click', function () {
            var saved = readSaved();
            saved.navbar = $(this).attr('data-color');
            writeSaved(saved);
            applyAll(saved);
        });

        $('.changeSideBarColor').on('click', function () {
            var saved = readSaved();
            saved.sidebar = $(this).attr('data-color');
            writeSaved(saved);
            applyAll(saved);
        });

        $('#prms-chrome-reset').on('click', function () {
            writeSaved(Object.assign({}, DEFAULTS));
            applyAll(readSaved());
        });
    }

    $(function () {
        applyAll(readSaved());
        bindPanel();
    });
})(window, window.jQuery);
