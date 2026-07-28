@php
    $paymentFieldPrefix = $prefix ?? 'payment';
    $paymentMethodName = $methodName ?? 'payment_method';
    $paymentReferenceName = $referenceName ?? 'payment_reference';
    $paymentReferenceHintId = $paymentFieldPrefix.'_reference_hint';
    $selectedPaymentMethod = old($paymentMethodName, $currentMethod ?? 'cash');
    $paymentReferenceValue = old($paymentReferenceName, $currentReference ?? null);
@endphp
<div class="{{ $methodGroupClass ?? 'form-group' }}">
    <label for="{{ $paymentFieldPrefix }}_method">ادائیگی کا طریقہ</label>
    <select id="{{ $paymentFieldPrefix }}_method" name="{{ $paymentMethodName }}" class="form-control" required>
        @foreach(\App\Support\PaymentMethods::LABELS as $methodValue => $methodLabel)
            <option value="{{ $methodValue }}" @selected($selectedPaymentMethod === $methodValue)>{{ $methodLabel }}</option>
        @endforeach
    </select>
</div>
<div class="{{ $referenceGroupClass ?? 'form-group' }}">
    <label for="{{ $paymentFieldPrefix }}_reference">حوالہ / ٹرانزیکشن نمبر</label>
    <input
        id="{{ $paymentFieldPrefix }}_reference"
        type="text"
        name="{{ $paymentReferenceName }}"
        value="{{ $paymentReferenceValue }}"
        maxlength="255"
        class="form-control"
        placeholder="بینک، والٹ، راست یا چیک نمبر"
        aria-describedby="{{ $paymentReferenceHintId }}"
    >
    <small id="{{ $paymentReferenceHintId }}" class="form-text text-muted">نقد اور دیگر ادائیگی کے لیے اختیاری ہے۔</small>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const method = document.getElementById(@json($paymentFieldPrefix.'_method'));
    const reference = document.getElementById(@json($paymentFieldPrefix.'_reference'));
    const hint = document.getElementById(@json($paymentReferenceHintId));

    if (!method || !reference || !hint) {
        return;
    }

    const syncReferenceRequirement = function () {
        const required = !['cash', 'other'].includes(method.value);
        reference.required = required;
        reference.setAttribute('aria-required', required ? 'true' : 'false');
        hint.textContent = required
            ? 'منتخب ادائیگی کے طریقے کے لیے حوالہ نمبر ضروری ہے۔'
            : 'نقد اور دیگر ادائیگی کے لیے اختیاری ہے۔';
    };

    method.addEventListener('change', syncReferenceRequirement);
    syncReferenceRequirement();
});
</script>
@endpush
