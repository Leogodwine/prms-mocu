(function () {
    'use strict';

    const DEBOUNCE_MS = 450;

    function isFilterForm(form) {
        if (form.dataset.prmsAutoFilter === 'off') {
            return false;
        }

        const action = form.querySelector('input[name="_filter_action"]');

        return action && action.value === 'apply';
    }

    function isTextField(el) {
        const type = (el.getAttribute('type') || 'text').toLowerCase();

        return type === 'text' || type === 'search';
    }

    function isDebouncedField(el) {
        const type = (el.getAttribute('type') || 'text').toLowerCase();

        return isTextField(el) || type === 'number';
    }

    function submitForm(form) {
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    }

    function debounce(fn, ms) {
        let timer;

        return function () {
            clearTimeout(timer);
            timer = setTimeout(fn, ms);
        };
    }

    function initForm(form) {
        if (!isFilterForm(form) || form.dataset.prmsAutoFilterInit === '1') {
            return;
        }

        form.dataset.prmsAutoFilterInit = '1';
        form.classList.add('prms-auto-filter');

        const debouncedSubmit = debounce(function () {
            submitForm(form);
        }, DEBOUNCE_MS);

        form.querySelectorAll('select').forEach(function (el) {
            el.addEventListener('change', function () {
                submitForm(form);
            });
        });

        form.querySelectorAll('input[type="date"], input[type="datetime-local"]').forEach(function (el) {
            el.addEventListener('change', function () {
                submitForm(form);
            });
        });

        form.querySelectorAll('input').forEach(function (el) {
            if (!isDebouncedField(el)) {
                return;
            }

            el.addEventListener('input', debouncedSubmit);

            if (isTextField(el)) {
                el.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        submitForm(form);
                    }
                });
            }
        });
    }

    document.querySelectorAll('form').forEach(initForm);
})();
