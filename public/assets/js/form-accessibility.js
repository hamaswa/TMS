(function () {
    'use strict';

    function uniqueId(prefix) {
        var candidate;
        var index = 1;

        do {
            candidate = prefix + '-' + index;
            index += 1;
        } while (document.getElementById(candidate));

        return candidate;
    }

    function visibleControls(container) {
        return Array.prototype.filter.call(
            container.querySelectorAll('input:not([type="hidden"]), select, textarea'),
            function (control) {
                return !control.disabled && control.type !== 'submit' && control.type !== 'button';
            }
        );
    }

    function controlForLabel(label) {
        var nested = label.querySelector('input:not([type="hidden"]), select, textarea');
        if (nested) {
            return nested;
        }

        var group = label.closest('.form-group');
        if (group) {
            var groupedControls = visibleControls(group);
            if (groupedControls.length === 1) {
                return groupedControls[0];
            }
        }

        var column = label.parentElement;
        var sibling = column ? column.nextElementSibling : null;
        if (sibling) {
            var siblingControls = visibleControls(sibling);
            if (siblingControls.length === 1) {
                return siblingControls[0];
            }
        }

        return null;
    }

    function associateFormLabels() {
        document.querySelectorAll('form label:not([for])').forEach(function (label) {
            var control = controlForLabel(label);
            if (!control || label.contains(control)) {
                return;
            }

            if (!control.id) {
                control.id = uniqueId('form-field');
            }
            label.htmlFor = control.id;
        });

        document.querySelectorAll('form input:not([type="hidden"]), form select, form textarea').forEach(function (control) {
            if (control.labels && control.labels.length) {
                return;
            }
            if (control.getAttribute('aria-label') || control.getAttribute('aria-labelledby')) {
                return;
            }

            var description = control.getAttribute('placeholder') || control.getAttribute('title');
            if (!description && control.tagName === 'SELECT' && control.options.length) {
                description = control.options[0].textContent.trim();
            }
            if (!description && (control.type === 'checkbox' || control.type === 'radio')) {
                var parentText = control.parentElement ? control.parentElement.textContent.trim() : '';
                if (parentText && parentText.length <= 120) {
                    description = parentText;
                }
            }
            if (!description && control.name) {
                var fieldName = control.name
                    .replace(/\[\]/g, '')
                    .replace(/[_-]+/g, ' ')
                    .trim();
                if (fieldName) {
                    var english = document.documentElement.lang === 'en' || document.documentElement.dir === 'ltr';
                    description = (english ? 'Form field: ' : 'فارم خانہ: ') + fieldName;
                }
            }
            if (description) {
                control.setAttribute('aria-label', description);
            }
        });
    }

    function nameIconActions() {
        var english = document.documentElement.lang === 'en' || document.documentElement.dir === 'ltr';
        var names = [
            { selector: '.fa-edit, .fa-pencil-alt, .fa-pencil', ur: 'ترمیم کریں', en: 'Edit' },
            { selector: '.fa-trash, .fa-trash-alt', ur: 'حذف کریں', en: 'Delete' },
            { selector: '.fa-eye', ur: 'دیکھیں', en: 'View' },
            { selector: '.fa-print', ur: 'پرنٹ کریں', en: 'Print' },
            { selector: '.fa-plus', ur: 'شامل کریں', en: 'Add' },
            { selector: '.fa-download', ur: 'ڈاؤن لوڈ کریں', en: 'Download' }
        ];

        document.querySelectorAll('a, button').forEach(function (action) {
            if (action.getAttribute('aria-label') || action.getAttribute('title')) {
                return;
            }
            if (action.textContent.trim()) {
                return;
            }

            var match = names.find(function (item) {
                return action.querySelector(item.selector);
            });
            if (match) {
                action.setAttribute('aria-label', english ? match.en : match.ur);
                action.setAttribute('title', english ? match.en : match.ur);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        associateFormLabels();
        nameIconActions();
    });
}());
