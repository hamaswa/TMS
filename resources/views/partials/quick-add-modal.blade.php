{{--
    One small form for adding a new choice (brand, cloth type, stitching option…) from a dropdown.
    window.TMSQuickAdd.open({ title, url, fields: [{ name, label, urdu }], extra: { key: value } })
    resolves with the saved JSON, or null when the form is closed.
--}}
@once
<div class="modal fade" id="tmsQuickAddModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" dir="rtl">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="close ml-0 mr-auto" data-dismiss="modal" aria-label="بند کریں">&times;</button>
            </div>
            <form id="tmsQuickAddForm" novalidate>
                <div class="modal-body">
                    <div data-quick-fields></div>
                    <div class="text-danger small mt-2" data-quick-error role="alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">منسوخ</button>
                    <button type="submit" class="btn btn-primary">شامل کریں</button>
                </div>
            </form>
        </div>
    </div>
</div>
<style>
    /* Nastaliq glyphs are taller than the default line box; give them room so they aren't clipped. */
    #tmsQuickAddModal .form-control { height: auto; line-height: 2.4; padding-top: .25rem; padding-bottom: .25rem; }
    /* It can open on top of another modal (e.g. the measurement editor). */
    #tmsQuickAddModal { z-index: 1065; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('tmsQuickAddModal');
    if (!modal || !window.jQuery) return;
    var form = document.getElementById('tmsQuickAddForm');
    var fieldsBox = modal.querySelector('[data-quick-fields]');
    var errorBox = modal.querySelector('[data-quick-error]');
    var current = null;

    function token() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        var input = document.querySelector('input[name="_token"]');
        return (meta && meta.content) || (input && input.value) || '';
    }

    function finish(result) {
        if (!current) return;
        var resolve = current.resolve;
        current = null;
        resolve(result);
    }

    window.TMSQuickAdd = {
        open: function (config) {
            finish(null);
            return new Promise(function (resolve) {
                current = { config: config, resolve: resolve };
                modal.querySelector('.modal-title').textContent = config.title;
                errorBox.textContent = '';
                fieldsBox.innerHTML = '';
                config.fields.forEach(function (field, index) {
                    var group = document.createElement('div');
                    group.className = 'form-group' + (index === config.fields.length - 1 ? ' mb-0' : '');
                    var label = document.createElement('label');
                    label.htmlFor = 'tmsQuickAdd-' + field.name;
                    label.textContent = field.label;
                    var input = document.createElement('input');
                    input.id = 'tmsQuickAdd-' + field.name;
                    input.name = field.name;
                    input.className = 'form-control';
                    input.maxLength = 255;
                    input.autocomplete = 'off';
                    input.required = true;
                    if (field.urdu) input.dataset.urduKeyboard = 'on';
                    else input.dir = 'ltr';
                    group.appendChild(label);
                    group.appendChild(input);
                    fieldsBox.appendChild(group);
                });
                window.jQuery(modal).modal('show');
            });
        }
    };

    window.jQuery(modal).on('shown.bs.modal', function () {
        // Keep this backdrop above any modal underneath, and focus the first field.
        var backdrops = document.querySelectorAll('.modal-backdrop');
        if (backdrops.length > 1) backdrops[backdrops.length - 1].style.zIndex = 1060;
        var first = fieldsBox.querySelector('input');
        if (first) first.focus();
    });
    window.jQuery(modal).on('hidden.bs.modal', function () {
        finish(null);
        // Closing this one must not unlock scrolling for a modal that is still open.
        if (document.querySelector('.modal.show')) document.body.classList.add('modal-open');
    });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (!current) return;
        var inputs = fieldsBox.querySelectorAll('input');
        var empty = Array.prototype.find.call(inputs, function (input) { return !input.value.trim(); });
        if (empty) {
            errorBox.textContent = empty.labels[0].textContent + ' لکھیں۔';
            empty.focus();
            return;
        }
        var data = new FormData();
        inputs.forEach(function (input) { data.append(input.name, input.value.trim()); });
        Object.keys(current.config.extra || {}).forEach(function (key) { data.append(key, current.config.extra[key] ?? ''); });
        data.append('_token', token());
        var button = form.querySelector('[type="submit"]');
        button.disabled = true;
        errorBox.textContent = '';
        try {
            var response = await fetch(current.config.url, {
                method: 'POST', body: data, credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            var result = await response.json();
            if (!response.ok) throw new Error(result.message || Object.values(result.errors || {}).flat().join(' '));
            finish(result);
            window.jQuery(modal).modal('hide');
        } catch (error) {
            errorBox.textContent = error.message || 'محفوظ نہیں ہوا، دوبارہ کوشش کریں۔';
        } finally {
            button.disabled = false;
        }
    });
});
</script>
@endonce
