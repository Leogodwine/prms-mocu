(function () {
    'use strict';

    var EXAMPLE = '255738234345';
    var EXAMPLE_PLUS = '+255738234345';
    var INVALID_MESSAGE = 'SMS is sent only to valid E.164 numbers (e.g. ' + EXAMPLE + ' or ' + EXAMPLE_PLUS
        + '). Use digits only; the + country-code prefix is recommended.';
    var REQUIRED_MESSAGE = 'Phone number is required.';

    function normalizePhone(value) {
        var digits = String(value || '').trim().replace(/\D+/g, '');

        if (digits === '') {
            return null;
        }

        if (digits.charAt(0) === '0') {
            digits = '255' + digits.slice(1);
        }

        if (digits.indexOf('255') !== 0) {
            return null;
        }

        if (digits.length < 12 || digits.length > 13) {
            return null;
        }

        return '+' + digits;
    }

    function feedbackEl(input) {
        var group = input.closest('[data-prms-phone-group]') || input.parentElement;

        return group ? group.querySelector('.prms-phone-feedback') : null;
    }

    function setInvalid(input, message) {
        input.classList.add('is-invalid');
        input.setCustomValidity(message || INVALID_MESSAGE);

        var feedback = feedbackEl(input);
        if (feedback) {
            feedback.textContent = message || INVALID_MESSAGE;
            feedback.classList.add('d-block');
        }
    }

    function clearInvalid(input) {
        input.classList.remove('is-invalid');
        input.setCustomValidity('');

        var feedback = feedbackEl(input);
        if (feedback && !feedback.hasAttribute('data-server-error')) {
            feedback.textContent = '';
            feedback.classList.remove('d-block');
        }
    }

    function validateInput(input, options) {
        var showRequired = options && options.showRequired;
        var raw = String(input.value || '').trim();

        if (raw === '') {
            if (input.required && showRequired) {
                setInvalid(input, REQUIRED_MESSAGE);
                return false;
            }

            clearInvalid(input);
            return !input.required || !showRequired;
        }

        if (normalizePhone(raw) === null) {
            setInvalid(input, INVALID_MESSAGE);
            return false;
        }

        clearInvalid(input);
        return true;
    }

    function bindForm(form) {
        if (!form || form.dataset.prmsPhoneBound === '1') {
            return;
        }

        form.dataset.prmsPhoneBound = '1';

        form.addEventListener('submit', function (event) {
            var valid = true;

            form.querySelectorAll('.prms-phone-field').forEach(function (input) {
                if (!validateInput(input, { showRequired: true })) {
                    valid = false;
                }
            });

            if (!valid) {
                event.preventDefault();
                event.stopPropagation();
            }
        });
    }

    function initInput(input) {
        if (!input || input.dataset.prmsPhoneInit === '1') {
            return;
        }

        input.dataset.prmsPhoneInit = '1';

        input.addEventListener('input', function () {
            var raw = String(input.value || '').trim();

            if (raw === '') {
                clearInvalid(input);
                return;
            }

            validateInput(input, { showRequired: false });
        });

        input.addEventListener('blur', function () {
            validateInput(input, { showRequired: true });
        });

        bindForm(input.closest('form'));
    }

    function initAll(root) {
        (root || document).querySelectorAll('.prms-phone-field').forEach(initInput);
    }

    document.addEventListener('DOMContentLoaded', function () {
        initAll(document);
    });

    window.PrmsPhoneField = {
        initAll: initAll,
        normalizePhone: normalizePhone,
        INVALID_MESSAGE: INVALID_MESSAGE,
        REQUIRED_MESSAGE: REQUIRED_MESSAGE,
    };
})();
