/**
 * PRMS toast notifications — bottom-right stack (Bootstrap 5).
 */
(function (global) {
    'use strict';

    const TYPE_META = {
        success: { title: 'Success', icon: 'fa-check-circle', tone: 'success' },
        info: { title: 'Information', icon: 'fa-info-circle', tone: 'info' },
        warning: { title: 'Warning', icon: 'fa-exclamation-triangle', tone: 'warning' },
        danger: { title: 'Error', icon: 'fa-times-circle', tone: 'danger' },
        error: { title: 'Error', icon: 'fa-times-circle', tone: 'danger' },
    };

    function stackEl() {
        let el = document.getElementById('prmsToastStack');
        if (!el) {
            el = document.createElement('div');
            el.id = 'prmsToastStack';
            el.className = 'prms-toast-stack';
            el.setAttribute('aria-live', 'polite');
            el.setAttribute('aria-atomic', 'true');
            document.body.appendChild(el);
        }

        return el;
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function normalizeType(type) {
        const key = String(type || 'info').toLowerCase();

        return TYPE_META[key] ? key : 'info';
    }

    function showToast(payload) {
        if (!global.bootstrap || !global.bootstrap.Toast) {
            return null;
        }

        const type = normalizeType(payload.type);
        const meta = TYPE_META[type];
        const duration = Number(payload.duration ?? 6500);
        const autohide = payload.autohide !== false && duration > 0;
        const message = payload.message ?? '';
        const title = payload.title ?? meta.title;
        const useHtml = Boolean(payload.html);

        if (!message && !payload.htmlBody) {
            return null;
        }

        const toastId = 'prms-toast-' + Date.now() + '-' + Math.random().toString(36).slice(2, 8);
        const bodyHtml = payload.htmlBody
            ? payload.htmlBody
            : (useHtml ? message : escapeHtml(message).replace(/\n/g, '<br>'));

        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = 'toast prms-toast prms-toast--' + meta.tone;
        toast.setAttribute('role', type === 'danger' || type === 'error' ? 'alert' : 'status');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        toast.innerHTML =
            '<div class="toast-header">'
            + '<i class="fas ' + meta.icon + ' text-' + meta.tone + ' me-2" aria-hidden="true"></i>'
            + '<strong class="me-auto">' + escapeHtml(title) + '</strong>'
            + '<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Dismiss"></button>'
            + '</div>'
            + '<div class="toast-body">' + bodyHtml + '</div>';

        stackEl().appendChild(toast);

        const instance = global.bootstrap.Toast.getOrCreateInstance(toast, {
            autohide,
            delay: duration,
        });

        toast.addEventListener('hidden.bs.toast', function () {
            toast.remove();
        });

        instance.show();

        return instance;
    }

    function showAll(queue) {
        if (!Array.isArray(queue)) {
            return;
        }

        queue.forEach(function (item, index) {
            if (!item) {
                return;
            }

            window.setTimeout(function () {
                showToast(item);
            }, index * 180);
        });
    }

    function drainQueue() {
        const queue = global.__prmsToastQueue;
        if (!Array.isArray(queue) || queue.length === 0) {
            return;
        }

        showAll(queue);
        global.__prmsToastQueue = [];
    }

    global.PrmsToast = {
        show: function (message, type, options) {
            if (typeof message === 'object' && message !== null) {
                return showToast(message);
            }

            return showToast(Object.assign({}, options || {}, {
                message: message,
                type: type || 'info',
            }));
        },
        showAll: showAll,
    };

    function initAutoToasts() {
        document.querySelectorAll('[data-prms-toast]').forEach(function (node) {
            const type = normalizeType(node.getAttribute('data-prms-toast') || 'info');
            if (type !== 'success') {
                return;
            }

            const title = node.getAttribute('data-prms-toast-title');
            const duration = node.hasAttribute('data-prms-toast-duration')
                ? Number(node.getAttribute('data-prms-toast-duration'))
                : 6500;
            const autohide = node.getAttribute('data-prms-toast-autohide') !== 'false';
            const htmlBody = node.innerHTML.trim();

            showToast({
                type,
                title,
                duration,
                autohide,
                htmlBody,
            });

            node.remove();
        });
    }

    function promotePageAlertsToToasts() {
        document.querySelectorAll('.alert.alert-success').forEach(function (alert) {
            if (alert.closest('.modal')) {
                return;
            }

            if (alert.hasAttribute('data-prms-alert-keep')) {
                return;
            }

            const htmlBody = alert.innerHTML.trim();
            if (!htmlBody) {
                return;
            }

            showToast({
                type: 'success',
                htmlBody,
                duration: 7000,
            });

            alert.remove();
        });
    }

    function boot() {
        drainQueue();
        initAutoToasts();
        promotePageAlertsToToasts();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})(window);
