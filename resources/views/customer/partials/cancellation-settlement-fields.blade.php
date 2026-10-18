@php($paidAmount = round((float) $paidAmount, 2))
@if($paidAmount <= 0)
<div class="alert alert-info mb-3">
    اس آرڈر پر کوئی رقم ادا نہیں ہوئی۔ منسوخی پر صرف اسی آرڈر کا غیر ادا شدہ بقایا ختم ہوگا؛ رقم واپسی یا کریڈٹ تقسیم درکار نہیں۔
</div>
<input type="hidden" name="cancellation_charge" value="0">
<input type="hidden" name="compensation" value="0">
<input type="hidden" name="applied_to_other_balance" value="0">
<input type="hidden" name="cash_refund" value="0">
<input type="hidden" name="retained_credit" value="0">
@else
<div class="alert alert-light border">
    اس آرڈر پر ادا شدہ رقم: <strong>Rs {{ number_format($paidAmount, 2) }}</strong>۔ اسے نیچے مکمل تقسیم کریں؛ دوسرے آرڈرز خود تبدیل نہیں ہوں گے۔
</div>
<div class="form-row">
    <div class="col-md-4 form-group"><label>منسوخی کٹوتی</label><input type="number" name="cancellation_charge" class="form-control" min="0" max="{{ $paidAmount }}" step="0.01" value="0" required></div>
    @if($allowCompensation ?? false)<div class="col-md-4 form-group"><label>اضافی ہرجانہ</label><input type="number" name="compensation" class="form-control" min="0" step="0.01" value="0" required></div>@else<input type="hidden" name="compensation" value="0">@endif
    <div class="col-md-4 form-group"><label>پرانے بقایا میں منتقل</label><input type="number" name="applied_to_other_balance" class="form-control" min="0" step="0.01" value="0" required></div>
</div>
<div class="form-row">
    <div class="col-md-4 form-group"><label>اب نقد / بینک واپسی</label><input type="number" name="cash_refund" class="form-control" min="0" step="0.01" value="{{ $paidAmount }}" required></div>
    <div class="col-md-4 form-group"><label>گاہک کے کھاتے میں کریڈٹ رکھیں</label><input type="number" name="retained_credit" class="form-control" min="0" step="0.01" value="0" required></div>
    <div class="col-md-4 form-group"><label>واپسی کا طریقہ</label><select name="{{ $methodName ?? 'payment_method' }}" class="form-control"><option value="">منتخب کریں</option>@foreach(\App\Support\PaymentMethods::LABELS as $method=>$label)<option value="{{ $method }}">{{ $label }}</option>@endforeach</select></div>
</div>
<div class="form-group"><label>ادائیگی حوالہ</label><input name="{{ $referenceName ?? 'payment_reference' }}" class="form-control" maxlength="255"></div>
<small class="text-muted">فارم تبھی محفوظ ہوگا جب: ادا شدہ رقم − کٹوتی + ہرجانہ = پرانے بقایا میں منتقلی + نقد واپسی + محفوظ کریڈٹ۔</small>
@endif
