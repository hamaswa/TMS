(function () {
    'use strict';

    var modalElement;
    var titleElement;
    var messageElement;
    var acceptButton;
    var cancelButton;
    var activeResolver;
    var lastTrigger;

    function isUrdu() {
        return document.documentElement.lang === 'ur';
    }

    function labels() {
        return isUrdu()
            ? {
                title: 'تصدیق درکار',
                accept: 'جاری رکھیں',
                cancel: 'واپس جائیں',
                close: 'بند کریں',
                message: 'کیا آپ یہ کارروائی جاری رکھنا چاہتے ہیں؟'
            }
            : {
                title: 'Confirmation required',
                accept: 'Continue',
                cancel: 'Cancel',
                close: 'Close',
                message: 'Do you want to continue with this action?'
            };
    }

    function initialise() {
        modalElement = document.getElementById('tmsConfirmModal');
        if (!modalElement) {
            return false;
        }

        titleElement = document.getElementById('tmsConfirmModalTitle');
        messageElement = document.getElementById('tmsConfirmModalMessage');
        acceptButton = modalElement.querySelector('[data-tms-confirm-accept]');
        cancelButton = modalElement.querySelector('[data-tms-confirm-cancel]');

        var copy = labels();
        titleElement.textContent = copy.title;
        acceptButton.textContent = copy.accept;
        cancelButton.textContent = copy.cancel;
        var closeButton = modalElement.querySelector('.close');
        if (closeButton) {
            closeButton.setAttribute('aria-label', copy.close);
        }

        acceptButton.addEventListener('click', function () {
            finish(true);
            hide();
        });

        if (window.jQuery) {
            window.jQuery(modalElement).on('hidden.bs.modal', function () {
                finish(false);
            });
        }

        return true;
    }

    function show() {
        if (window.jQuery && typeof window.jQuery.fn.modal === 'function') {
            window.jQuery(modalElement).modal({
                backdrop: 'static',
                keyboard: true,
                show: true
            });
            return;
        }

        modalElement.classList.add('show');
        modalElement.style.display = 'block';
        modalElement.removeAttribute('aria-hidden');
        modalElement.setAttribute('aria-modal', 'true');
        document.body.classList.add('modal-open');
        acceptButton.focus();
    }

    function hide() {
        if (window.jQuery && typeof window.jQuery.fn.modal === 'function') {
            window.jQuery(modalElement).modal('hide');
            return;
        }

        modalElement.classList.remove('show');
        modalElement.style.display = 'none';
        modalElement.setAttribute('aria-hidden', 'true');
        modalElement.removeAttribute('aria-modal');
        document.body.classList.remove('modal-open');
        if (lastTrigger && typeof lastTrigger.focus === 'function') {
            lastTrigger.focus();
        }
    }

    function finish(confirmed) {
        if (!activeResolver) {
            return;
        }

        var resolve = activeResolver;
        activeResolver = null;
        resolve(confirmed);
    }

    function ask(message, options) {
        options = options || {};
        if (!modalElement && !initialise()) {
            return Promise.resolve(false);
        }

        if (activeResolver) {
            return Promise.resolve(false);
        }

        var copy = labels();
        titleElement.textContent = options.title || copy.title;
        messageElement.textContent = message || copy.message;
        acceptButton.textContent = options.acceptLabel || copy.accept;
        cancelButton.textContent = options.cancelLabel || copy.cancel;
        acceptButton.className = 'btn btn-' + (options.variant || 'danger');
        lastTrigger = options.trigger || document.activeElement;

        return new Promise(function (resolve) {
            activeResolver = resolve;
            show();
        });
    }

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) {
            return;
        }

        if (form.dataset.confirmBypass === 'true') {
            delete form.dataset.confirmBypass;
            return;
        }

        event.preventDefault();
        var submitter = event.submitter || null;
        ask(form.dataset.confirm, {
            title: form.dataset.confirmTitle,
            acceptLabel: form.dataset.confirmAccept,
            cancelLabel: form.dataset.confirmCancel,
            variant: form.dataset.confirmVariant || 'danger',
            trigger: submitter || form
        }).then(function (confirmed) {
            if (!confirmed) {
                return;
            }

            form.dataset.confirmBypass = 'true';
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit(submitter);
            } else {
                form.submit();
            }
        });
    }, true);

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-confirm]');
        if (!trigger || trigger instanceof HTMLFormElement) {
            return;
        }

        if (trigger.dataset.confirmBypass === 'true') {
            delete trigger.dataset.confirmBypass;
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        ask(trigger.dataset.confirm, {
            title: trigger.dataset.confirmTitle,
            acceptLabel: trigger.dataset.confirmAccept,
            cancelLabel: trigger.dataset.confirmCancel,
            variant: trigger.dataset.confirmVariant || 'danger',
            trigger: trigger
        }).then(function (confirmed) {
            if (!confirmed) {
                return;
            }

            if (trigger.matches('a[href]')) {
                window.location.href = trigger.href;
                return;
            }

            var form = trigger.form;
            if (form && typeof form.requestSubmit === 'function') {
                trigger.dataset.confirmBypass = 'true';
                form.requestSubmit(trigger);
                return;
            }

            trigger.dataset.confirmBypass = 'true';
            trigger.click();
        });
    }, true);

    document.addEventListener('DOMContentLoaded', initialise);

    window.TmsConfirm = {
        ask: ask
    };
})();
