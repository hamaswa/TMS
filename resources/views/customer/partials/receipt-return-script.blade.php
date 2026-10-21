@once
<script>
document.addEventListener('DOMContentLoaded', function () {
    function round(value) { return Math.round(value * 100) / 100; }
    function money(value) {
        return 'Rs ' + value.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    }
    function show(form, key, value) {
        form.querySelectorAll('[data-out="' + key + '"]').forEach(function (el) { el.textContent = value; });
    }
    document.querySelectorAll('.receipt-return-form').forEach(function (form) {
        var totalDues = round((parseFloat(form.dataset.outstanding) || 0) + (parseFloat(form.dataset.otherDues) || 0));
        var settlement = form.querySelector('.return-settlement');
        var cashInput = form.querySelector('.return-cash');
        var details = form.querySelector('.return-details');
        var note = form.querySelector('[data-cash-note]');
        // The submit button may sit in a modal footer, linked with form="id".
        var submit = form.querySelector('.return-submit') || (form.id && document.querySelector('.return-submit[form="' + form.id + '"]'));
        function rowOf(el) { return el.closest('[data-return-row], tr'); }

        function update() {
            var total = 0, count = 0;
            form.querySelectorAll('.return-qty').forEach(function (input) {
                var qty = Math.min(Math.max(parseFloat(input.value) || 0, 0), parseFloat(input.dataset.remaining) || 0);
                total += qty * (parseFloat(input.dataset.unitPrice) || 0);
                var pick = rowOf(input).querySelector('.return-pick');
                if (pick) pick.checked = qty > 0;
                if (qty > 0) count++;
            });
            total = round(total);
            // Same rule as CounterSaleReturnService: new dues = dues - return value + cash given,
            // so cash must cover whatever the dues can't absorb, and can't exceed the return value.
            var minCash = Math.max(0, round(total - totalDues));
            var empty = cashInput.value.trim() === '';
            var cash = round(parseFloat(cashInput.value) || 0);
            var error = '';
            if (cash < minCash) error = 'گاہک کو کم از کم ' + money(minCash) + ' دینے ہیں (واپسی ' + money(total) + ' − بقایا ' + money(totalDues) + ')';
            else if (cash > total) error = 'آئٹم کی قیمت ' + money(total) + ' ہے، اس سے زیادہ نہیں دے سکتے';
            // The custom message also blocks submit, so an empty box can't slip past the minimum.
            cashInput.setCustomValidity(error);
            note.textContent = error || 'گاہک پر باقی بقایا: ' + money(Math.max(0, round(totalDues - total + cash)));
            note.classList.toggle('is-error', error !== '');
            note.hidden = empty && !note.dataset.tried;
            show(form, 'count', count);
            if (details) details.hidden = !(total > 0);
            settlement.hidden = !(cash > 0);
            form.querySelector('[name="payment_method"]').required = cash > 0;
            if (submit) submit.disabled = !(total > 0);
        }

        form.querySelectorAll('.return-pick').forEach(function (pick) {
            pick.addEventListener('change', function () {
                var input = rowOf(pick).querySelector('.return-qty');
                if (input) input.value = pick.checked ? input.dataset.remaining : 0;
                update();
            });
        });
        form.querySelectorAll('.return-qty').forEach(function (input) { input.addEventListener('input', update); });
        form.querySelectorAll('.rr-step-btn').forEach(function (button) {
            button.addEventListener('click', function () {
                var input = button.parentNode.querySelector('.return-qty');
                var next = (parseFloat(input.value) || 0) + parseFloat(button.dataset.step);
                input.value = Math.min(Math.max(next, 0), parseFloat(input.dataset.remaining) || 0);
                update();
            });
        });
        cashInput.addEventListener('input', update);
        // Submitting with a wrong or missing amount shows the red line next to the box.
        cashInput.addEventListener('invalid', function () {
            note.dataset.tried = '1';
            note.hidden = false;
        });
        update();
    });
});
</script>
@endonce
