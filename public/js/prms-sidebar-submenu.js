/**
 * Sidebar sub-menus: click parent row to expand/collapse; one open at a time.
 */
(function () {
    'use strict';

    var sidebar = document.querySelector('.wrapper > .sidebar:not(.prms-sidebar-sub) .nav.nav-primary');
    if (!sidebar) {
        return;
    }

    function collapseApi(panel) {
        if (!panel || typeof bootstrap === 'undefined' || !bootstrap.Collapse) {
            return null;
        }

        return bootstrap.Collapse.getOrCreateInstance(panel, { toggle: false });
    }

    function syncTrigger(trigger, expanded) {
        if (!trigger) {
            return;
        }

        trigger.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        var item = trigger.closest('.nav-item.submenu');
        if (item) {
            item.classList.add('submenu');
            item.classList.toggle('is-submenu-open', expanded);
        }
    }

    function closePanel(panel) {
        if (!panel || !panel.classList.contains('show')) {
            return;
        }

        var api = collapseApi(panel);
        if (api) {
            api.hide();
            return;
        }

        panel.classList.remove('show');
        syncTrigger(sidebar.querySelector('[data-prms-submenu-toggle][aria-controls="' + panel.id + '"]'), false);
    }

    function openPanel(panel) {
        if (!panel) {
            return;
        }

        var api = collapseApi(panel);
        if (api) {
            api.show();
            return;
        }

        panel.classList.add('show');
        syncTrigger(sidebar.querySelector('[data-prms-submenu-toggle][aria-controls="' + panel.id + '"]'), true);
    }

    sidebar.querySelectorAll('[data-prms-submenu-toggle]').forEach(function (trigger) {
        var targetId = trigger.getAttribute('aria-controls');
        var panel = targetId ? document.getElementById(targetId) : null;
        if (!panel) {
            return;
        }

        syncTrigger(trigger, panel.classList.contains('show'));

        panel.addEventListener('shown.bs.collapse', function () {
            syncTrigger(trigger, true);
        });
        panel.addEventListener('hidden.bs.collapse', function () {
            syncTrigger(trigger, false);
        });

        trigger.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            var isOpen = panel.classList.contains('show');

            sidebar.querySelectorAll('.nav-item.submenu > .collapse.show').forEach(function (openPanel) {
                if (openPanel !== panel) {
                    closePanel(openPanel);
                }
            });

            if (isOpen) {
                closePanel(panel);
            } else {
                openPanel(panel);
            }
        });

        trigger.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                trigger.click();
            }
        });
    });
})();
