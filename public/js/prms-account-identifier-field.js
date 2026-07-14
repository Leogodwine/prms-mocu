(function () {
    'use strict';

    var STANDARD_PATTERN = /^MoCU\/([A-Z0-9]+(?:-[A-Z0-9]+)*)\/(\d+)\/(\d{2})$/;
    var ADMIN_LEGACY_PATTERN = /^MoCU\/ADMIN\/\d+$/;

    var STUDENT_INVALID_MESSAGE = 'Format: MoCU/PROGRAMME-CODE/NUMBER/YY — exact casing required: MoCU/ prefix, uppercase code, two-digit year (e.g. MoCU/BBICT/231/20)';
    var STAFF_INVALID_MESSAGE = 'Format: MoCU/DEPT-CODE/NUMBER/YY — exact casing required: MoCU/ prefix, uppercase code, two-digit year (e.g. MoCU/ACC/231/20)';

    function identifierType(input) {
        if (input.classList.contains('prms-student-login-id')) {
            return 'student';
        }

        if (input.classList.contains('prms-staff-login-id')) {
            return 'staff';
        }

        return input.getAttribute('data-prms-identifier-type') || 'student';
    }

    function hasValidStandardFormat(value) {
        return STANDARD_PATTERN.test(String(value || '').trim());
    }

    function hasValidStaffFormat(value, allowAdminLegacy) {
        var trimmed = String(value || '').trim();

        if (hasValidStandardFormat(trimmed)) {
            return true;
        }

        return allowAdminLegacy && ADMIN_LEGACY_PATTERN.test(trimmed);
    }

    function shouldValidate(input) {
        return Boolean(input)
            && !input.disabled
            && input.getAttribute('name') === 'login_id';
    }

    function messagesFor(input) {
        var type = identifierType(input);

        if (type === 'staff') {
            return {
                required: 'Staff ID is required.',
                invalid: STAFF_INVALID_MESSAGE,
            };
        }

        return {
            required: 'Registration number is required.',
            invalid: STUDENT_INVALID_MESSAGE,
        };
    }

    function feedbackEl(input) {
        var group = input.closest('[data-prms-identifier-group]') || input.parentElement;

        return group ? group.querySelector('.prms-identifier-feedback') : null;
    }

    function setInvalid(input, message) {
        input.classList.add('is-invalid');
        input.setCustomValidity(message);

        var feedback = feedbackEl(input);
        if (feedback) {
            feedback.textContent = message;
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
        if (!shouldValidate(input)) {
            clearInvalid(input);
            return true;
        }

        var showRequired = options && options.showRequired;
        var messages = messagesFor(input);
        var raw = String(input.value || '').trim();
        var allowAdminLegacy = input.dataset.prmsAllowAdminLegacy === '1';
        if (!allowAdminLegacy && identifierType(input) === 'staff') {
            var roleSelect = input.closest('form') && input.closest('form').querySelector('[name="role"]');
            allowAdminLegacy = roleSelect && roleSelect.value === 'admin';
        }

        if (raw === '') {
            if (input.required && showRequired) {
                setInvalid(input, messages.required);
                return false;
            }

            clearInvalid(input);
            return !input.required || !showRequired;
        }

        var valid = identifierType(input) === 'staff'
            ? hasValidStaffFormat(raw, allowAdminLegacy)
            : hasValidStandardFormat(raw);

        if (!valid) {
            setInvalid(input, messages.invalid);
            return false;
        }

        clearInvalid(input);
        return true;
    }

    function bindForm(form) {
        if (!form || form.dataset.prmsIdentifierBound === '1') {
            return;
        }

        form.dataset.prmsIdentifierBound = '1';

        form.addEventListener('submit', function (event) {
            var valid = true;

            form.querySelectorAll('.prms-account-identifier-field').forEach(function (input) {
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
        if (!input || input.dataset.prmsIdentifierInit === '1') {
            return;
        }

        input.dataset.prmsIdentifierInit = '1';

        input.addEventListener('input', function () {
            if (String(input.value || '').trim() === '') {
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
        (root || document).querySelectorAll('.prms-account-identifier-field').forEach(initInput);
    }

    document.addEventListener('DOMContentLoaded', function () {
        initAll(document);
    });

    window.PrmsAccountIdentifierField = {
        initAll: initAll,
        hasValidStandardFormat: hasValidStandardFormat,
        hasValidStaffFormat: hasValidStaffFormat,
        STUDENT_INVALID_MESSAGE: STUDENT_INVALID_MESSAGE,
        STAFF_INVALID_MESSAGE: STAFF_INVALID_MESSAGE,
    };
})();
