@once
<script>
document.addEventListener('DOMContentLoaded', function () {
    function money(value) {
        return 'Rs ' + value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function show(form, key, value) {
        form.querySelectorAll('[data-out="' + key + '"]').forEach(function (el) { el.textContent = value; });
    }
    document.querySelectorAll('.receipt-return-form').forEach(function (form) {
        var outstanding = parseFloat(form.dataset.outstanding) || 0;
        var paid = parseFloat(form.dataset.paid) || 0;
        var settlement = form.querySelector('.return-settlement');
        var refundFields = form.querySelector('.return-refund-fields');
        var details = form.querySelector('.return-details');
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
            total = Math.round(total * 100) / 100;
            // Same rule as CounterSaleReturnService: keeping the balance refunds the full value,
            // but never more than was paid on this receipt.
            var apply = form.querySelector('[name="apply_to_balance"]:checked');
            var reduction = !apply || apply.value === '1'
                ? Math.min(total, outstanding)
                : Math.min(outstanding, Math.max(0, Math.round((total - paid) * 100) / 100));
            var extra = Math.round((total - reduction) * 100) / 100;
            show(form, 'total', money(total));
            show(form, 'reduction', money(reduction));
            show(form, 'extra', money(extra));
            show(form, 'owed', money(Math.max(0, Math.round((outstanding - reduction) * 100) / 100)));
            show(form, 'count', count);
            if (details) details.hidden = !(total > 0);
            settlement.hidden = !(extra > 0);
            var refund = form.querySelector('[name="settlement_type"]:checked');
            refundFields.hidden = !(extra > 0) || !refund || refund.value !== 'refund';
            form.querySelector('[name="payment_method"]').required = !refundFields.hidden;
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
        form.querySelectorAll('[name="settlement_type"], [name="apply_to_balance"]').forEach(function (radio) { radio.addEventListener('change', update); });
        update();
    });
});
</script>
@endonce
