/**
 * PRMS modal fixes — backdrop stacking (2090) must stay below modals (2110).
 * Ensures dismiss controls work when Bootstrap data API is blocked by theme JS.
 */
(function () {
    if (window.__prmsModalsInit) {
        return;
    }
    window.__prmsModalsInit = true;

    function moveModalToBody(modalEl) {
        if (!modalEl || modalEl.parentElement === document.body) {
            return;
        }
        document.body.appendChild(modalEl);
    }

    document.querySelectorAll('.modal').forEach(function (modalEl) {
        moveModalToBody(modalEl);

        modalEl.addEventListener('show.bs.modal', function () {
            moveModalToBody(modalEl);
        });

        modalEl.addEventListener('click', function (event) {
            var dismissTrigger = event.target.closest('[data-bs-dismiss="modal"]');
            if (!dismissTrigger || !modalEl.contains(dismissTrigger)) {
                return;
            }
            if (window.bootstrap && window.bootstrap.Modal) {
                event.preventDefault();
                window.bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            }
        });
    });

    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-bs-toggle="modal"][data-bs-target]');
        if (!toggle || !window.bootstrap || !window.bootstrap.Modal) {
            return;
        }
        var targetSelector = toggle.getAttribute('data-bs-target');
        if (!targetSelector || targetSelector.charAt(0) !== '#') {
            return;
        }
        var modalEl = document.querySelector(targetSelector);
        if (modalEl) {
            moveModalToBody(modalEl);
        }
    });
})();
