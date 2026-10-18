<div class="modal fade" id="quickOptionModal" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content" dir="rtl"><div class="modal-header"><h5 class="modal-title">نیا انتخاب شامل کریں</h5><button type="button" class="close ml-0 mr-auto" data-dismiss="modal">&times;</button></div><form id="quickOptionForm" action="{{ route('admin.Options.store') }}" method="POST">@csrf<input type="hidden" name="OptionTypeId"><input type="hidden" name="measurement_template_id"><div class="modal-body"><label for="quickOptionName">انتخاب کا نام</label><input id="quickOptionName" class="form-control" name="Name" maxlength="255" required><div id="quickOptionError" class="text-danger small mt-2" role="alert"></div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">منسوخ</button><button class="btn btn-primary" type="submit">شامل کریں</button></div></form></div></div></div>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var modal = document.getElementById('quickOptionModal'), form = document.getElementById('quickOptionForm');
        if (!modal || !form || !window.jQuery) return;
        var target = null;
        document.querySelectorAll('select[data-inline-option-type]').forEach(function(select) {
            if (select.querySelector('option[value="__add_new__"]')) return;
            select.add(new Option('＋ نیا انتخاب شامل کریں', '__add_new__'));
            select.addEventListener('focus', function() { select.dataset.previousValue = select.value; });
            select.addEventListener('change', function() {
                if (select.value !== '__add_new__') { select.dataset.previousValue = select.value; return; }
                select.value = select.dataset.previousValue || '';
                target = select;
                form.elements.OptionTypeId.value = select.dataset.inlineOptionType;
                var templateInput = document.querySelector('[name="measurement_template_id"]');
                form.elements.measurement_template_id.value = templateInput ? templateInput.value : '';
                modal.querySelector('.modal-title').textContent = select.dataset.inlineOptionLabel + ' — نیا انتخاب';
                form.elements.Name.value = '';
                document.getElementById('quickOptionError').textContent = '';
                window.jQuery(modal).modal('show');
            });
        });
        window.jQuery(modal).on('show.bs.modal', function (event) {
            var trigger = event.relatedTarget;
            if (!trigger) return;
            target = document.getElementById(trigger.getAttribute('data-option-select'));
            form.elements.OptionTypeId.value = trigger.getAttribute('data-option-type');
            var templateInput = document.querySelector('[name="measurement_template_id"]');
            form.elements.measurement_template_id.value = templateInput ? templateInput.value : (trigger.getAttribute('data-option-template') || '');
            modal.querySelector('.modal-title').textContent = trigger.getAttribute('data-option-label') + ' — نیا انتخاب';
            form.elements.Name.value = '';
            document.getElementById('quickOptionError').textContent = '';
        });
        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            var button = form.querySelector('[type="submit"]'); button.disabled = true;
            try {
                var response = await fetch(form.action, {method:'POST', headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}, body:new FormData(form), credentials:'same-origin'});
                var data = await response.json();
                if (!response.ok) throw new Error(data.message || Object.values(data.errors || {}).flat().join(' '));
                if (target) { var option = new Option(data.name, data.name, true, true); option.dataset.template = data.template_id; target.add(option); target.dispatchEvent(new Event('change', {bubbles:true})); }
                window.jQuery(modal).modal('hide');
            } catch (error) { document.getElementById('quickOptionError').textContent = error.message || 'انتخاب محفوظ نہیں ہوا۔'; }
            finally { button.disabled = false; }
        });
    });
    </script>