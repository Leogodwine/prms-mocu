(function () {
    'use strict';

    var form = document.getElementById('prms-login-form');
    if (!form) {
        return;
    }

    var passwordInput = document.getElementById('password');
    var toggleBtn = document.getElementById('prms-password-toggle');
    var capsWarning = document.getElementById('prms-caps-lock-warning');
    var submitBtn = document.getElementById('prms-login-submit');
    var submitLabel = submitBtn ? submitBtn.querySelector('.prms-auth-submit-label') : null;
    var submitSpinner = submitBtn ? submitBtn.querySelector('.prms-auth-submit-spinner') : null;

    function setCapsLockWarning(visible) {
        if (!capsWarning) {
            return;
        }
        capsWarning.classList.toggle('d-none', !visible);
        capsWarning.setAttribute('aria-hidden', visible ? 'false' : 'true');
    }

    function detectCapsLock(event) {
        if (!event || typeof event.getModifierState !== 'function') {
            return;
        }
        setCapsLockWarning(event.getModifierState('CapsLock'));
    }

    if (passwordInput) {
        passwordInput.addEventListener('keydown', detectCapsLock);
        passwordInput.addEventListener('keyup', detectCapsLock);
        passwordInput.addEventListener('blur', function () {
            setCapsLockWarning(false);
        });
    }

    if (toggleBtn && passwordInput) {
        toggleBtn.addEventListener('click', function () {
            var isHidden = passwordInput.type === 'password';
            passwordInput.type = isHidden ? 'text' : 'password';
            toggleBtn.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
            toggleBtn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
            var icon = toggleBtn.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-eye', !isHidden);
                icon.classList.toggle('fa-eye-slash', isHidden);
            }
            passwordInput.focus();
        });
    }

    form.addEventListener('submit', function () {
        if (!submitBtn || submitBtn.disabled) {
            return;
        }
        submitBtn.disabled = true;
        submitBtn.setAttribute('aria-busy', 'true');
        form.setAttribute('aria-busy', 'true');
        if (submitLabel) {
            submitLabel.textContent = 'Signing in…';
        }
        if (submitSpinner) {
            submitSpinner.classList.remove('d-none');
        }
    });

    form.addEventListener('invalid', function (event) {
        var target = event.target;
        if (target && target.classList && target.classList.contains('form-control')) {
            target.classList.add('is-invalid');
        }
    }, true);
})();
