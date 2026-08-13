@extends('main')

@push('styles')
<style>
    .counter-sale-page{--sale-blue:#1769ef;--sale-ink:#14213d;--sale-muted:#718096;--sale-line:#e1e8f2;min-height:calc(100vh - 65px);padding:26px 0 46px;background:#f7f9fc;color:var(--sale-ink)}
    .counter-sale-shell{max-width:1560px;margin:auto;padding:0 24px}.counter-sale-breadcrumb{margin-bottom:12px;color:var(--sale-muted);font-size:.84rem}.counter-sale-breadcrumb a{color:inherit}.counter-sale-header{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:18px}.counter-sale-heading{display:flex;align-items:center;gap:14px}.counter-sale-heading-icon{display:grid;place-items:center;width:52px;height:52px;border:1px solid var(--sale-line);border-radius:13px;background:#fff;color:var(--sale-blue);font-size:21px;box-shadow:0 5px 18px rgba(25,67,120,.06)}.counter-sale-heading h1{margin:0 0 4px;font-size:1.6rem;font-weight:800}.counter-sale-heading p{margin:0;color:var(--sale-muted)}
    .counter-sale-panel{margin-bottom:14px;border:1px solid var(--sale-line);border-radius:13px;background:#fff;box-shadow:0 5px 20px rgba(28,63,105,.045)}.counter-sale-section-head{display:flex;align-items:center;gap:10px;padding:15px 19px 0;color:var(--sale-blue);font-size:1.04rem;font-weight:800}.counter-sale-panel-body{padding:18px 19px}.counter-sale-page label{display:block;margin-bottom:7px;color:#52627b;font-weight:700}.counter-sale-page .required{color:#e53e3e}.counter-sale-page .form-control{min-height:44px;border-color:#d8e1ed;border-radius:7px;background:#fff}.counter-sale-page select.form-control{height:50px;min-height:50px;padding-top:4px;padding-bottom:10px;line-height:1.8}.counter-sale-page .form-control:focus{border-color:#7aafff;box-shadow:0 0 0 3px rgba(23,105,239,.1)}.counter-input{position:relative}.counter-input>i{position:absolute;z-index:2;top:50%;right:14px;transform:translateY(-50%);color:#8492a7}.counter-input .form-control{padding-right:42px}.counter-input .has-suffix{padding-left:48px}.counter-input-suffix{position:absolute;z-index:2;top:1px;bottom:1px;left:1px;display:flex;align-items:center;padding:0 13px;border-right:1px solid var(--sale-line);border-radius:7px 0 0 7px;background:#f8fafc;color:#66758d;font-weight:700}
    .counter-sale-item{position:relative;padding:17px;margin-bottom:13px;border:1px solid #e4eaf3;border-radius:11px;background:#fbfcfe}.counter-item-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px}.counter-item-title{display:flex;align-items:center;gap:8px;color:#53647d;font-weight:800}.counter-item-number{display:grid;place-items:center;width:27px;height:27px;border-radius:7px;background:#eaf2ff;color:var(--sale-blue);font-family:Arial,sans-serif}.counter-line-total{padding:9px 12px;border-radius:7px;background:#eef4ff;color:var(--sale-blue);font:800 .9rem Arial,sans-serif;direction:ltr}.counter-remove-item{border:0;background:transparent;color:#dc3545}.counter-add-item{display:inline-flex;align-items:center;gap:8px;min-height:42px;padding:8px 15px;border:1px solid var(--sale-blue);border-radius:7px;background:#fff;color:var(--sale-blue);font-weight:800}.counter-add-item:hover{background:#edf4ff;color:#0f5ddf}
    .counter-summary-line{display:flex;align-items:center;justify-content:flex-end;gap:8px;margin-top:8px;font-weight:800}.counter-summary-line strong{color:var(--sale-blue);font:800 1rem Arial,sans-serif;direction:ltr}.counter-payment-panel{border-color:#d9eee1;background:linear-gradient(135deg,#fbfffc,#f1fbf5)}.counter-payment-panel .counter-sale-section-head{color:#1a9b57}.counter-balance-line{display:flex;align-items:center;gap:18px;margin-top:10px;color:#52627b;font-weight:700}.counter-balance-line strong{color:#1a9b57;font:800 1rem Arial,sans-serif;direction:ltr}.counter-sale-submit{display:flex;justify-content:center;padding-top:4px}.counter-sale-submit button{min-width:280px;min-height:48px;border:0;border-radius:8px;background:linear-gradient(135deg,#1769ef,#287fff);color:#fff;font-weight:800;box-shadow:0 9px 23px rgba(23,105,239,.22)}.counter-sale-back{min-height:43px;padding:9px 16px;border-radius:8px;font-weight:700}.counter-sale-alert{border-radius:10px}
    @media(max-width:767.98px){.counter-sale-shell{padding:0 12px}.counter-sale-header{align-items:stretch;flex-direction:column}.counter-sale-back{width:100%}.counter-sale-panel-body{padding:15px 13px}.counter-sale-item{padding:14px 11px}.counter-sale-submit button{width:100%;min-width:0}.counter-balance-line{align-items:flex-start;flex-direction:column;gap:5px}}
</style>
@endpush

@section('content')
<section class="main-content counter-sale-page" dir="rtl">
<div class="counter-sale-shell" id="formContainer">
    <div class="counter-sale-breadcrumb"><a href="{{ route('admin.home') }}">ڈیش بورڈ</a><span class="mx-2">‹</span>فروخت<span class="mx-2">‹</span>کپڑے کی فروخت</div>
    <header class="counter-sale-header">
        <div class="counter-sale-heading"><span class="counter-sale-heading-icon"><i class="fas fa-tags"></i></span><div><h1>کپڑے کی فروخت کریں</h1><p>فروخت کی مکمل معلومات درج کریں</p></div></div>
        <a href="{{ route('admin.stock.index') }}" class="btn btn-outline-secondary counter-sale-back"><i class="fas fa-arrow-right ml-1"></i> واپس جائیں</a>
    </header>

    @include('inc.message')
    @if($errors->any())
        <div class="alert alert-danger counter-sale-alert" role="alert"><strong>فروخت محفوظ نہیں ہو سکی:</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form action="{{ route('admin.sellStock') }}" method="post" id="sellStockForm" data-customer-url="{{ url('/admin/getNmbr') }}" data-types-url="{{ url('/admin/getType') }}">
        @csrf

        <section class="counter-sale-panel">
            <div class="counter-sale-section-head"><i class="far fa-file-alt"></i> بنیادی معلومات — گاہک کی معلومات</div>
            <div class="counter-sale-panel-body"><div class="form-row">
                <div class="form-group col-md-6 mb-md-0"><label for="c_name">گاہک کا نام <span class="required">*</span></label><div class="counter-input"><i class="far fa-user"></i><select name="c_name" required class="form-control custom-select" id="c_name"><option value="" disabled selected>گاہک منتخب کریں</option>@foreach($customers as $customer)<option value="{{ $customer->name.'|'.$customer->id }}" @selected(old('c_name') === $customer->name.'|'.$customer->id)>{{ $customer->name }}</option>@endforeach</select></div>@error('c_name')<div class="text-danger mt-1">{{ $message }}</div>@enderror</div>
                <div class="form-group col-md-6 mb-md-0"><label for="nmbr">رابطہ نمبر <span class="required">*</span></label><div class="counter-input"><i class="fas fa-phone"></i><input type="tel" inputmode="tel" class="form-control" name="phone" id="nmbr" value="{{ old('phone') }}" placeholder="گاہک کا رابطہ نمبر" autocomplete="tel" required></div>@error('phone')<div class="text-danger mt-1">{{ $message }}</div>@enderror</div>
            </div></div>
        </section>

        <section class="counter-sale-panel">
            <div class="counter-sale-section-head"><i class="fas fa-plus-square"></i> فروخت کے آئٹمز شامل کریں</div>
            <div class="counter-sale-panel-body">
                <div id="stockDataContainer" aria-live="polite">
                    <article class="stock-data counter-sale-item">
                        <div class="counter-item-head"><div class="counter-item-title"><span class="counter-item-number">1</span><span>کپڑے کی تفصیل</span></div><div class="d-flex align-items-center"><span class="counter-line-total">Rs. 0.00</span></div></div>
                        <div class="form-row">
                            <div class="form-group col-xl-3 col-md-6"><label>برانڈ <span class="required">*</span></label><select class="form-control js-brand" name="brand_name[]" required><option value="" disabled selected>برانڈ منتخب کریں</option>@foreach($cloths->unique('cloth_brand_id') as $cloth)<option value="{{ $cloth->cloth_brand_id }}">{{ $cloth->brand->name }}</option>@endforeach</select></div>
                            <div class="form-group col-xl-3 col-md-6"><label>کپڑے کی قسم <span class="required">*</span></label><select class="form-control js-cloth-type" name="cloth_type[]" required><option value="" disabled selected>پہلے برانڈ منتخب کریں</option></select></div>
                            <div class="form-group col-xl-3 col-md-6"><label>رنگ <span class="required">*</span></label><select class="form-control" name="color[]" required><option value="" disabled selected>رنگ منتخب کریں</option>@foreach($cloths as $cloth)@foreach($cloth->colors as $color)<option value="{{ $color->color }}">{{ $color->color }}</option>@endforeach @endforeach</select></div>
                            <div class="form-group col-xl-3 col-md-6"><label>میٹر / گز <span class="required">*</span></label><div class="counter-input"><i class="fas fa-ruler"></i><input type="number" class="form-control" name="length[]" min="0.01" step="0.01" placeholder="مقدار درج کریں" required></div></div>
                            <div class="form-group col-xl-4 col-md-6 mb-xl-0"><label>ریٹ فی میٹر <span class="required">*</span></label><div class="counter-input"><i class="fas fa-money-bill-wave"></i><input type="number" class="form-control has-suffix" name="per_meter[]" min="0" step="0.01" placeholder="ریٹ درج کریں" required><span class="counter-input-suffix">Rs.</span></div></div>
                            <div class="form-group col-xl-4 col-md-6 mb-xl-0"><label>فیبرک رول / ریک</label><div class="counter-input"><i class="fas fa-warehouse"></i><input type="text" class="form-control" name="clothes_rack[]" maxlength="100" placeholder="ریک یا رول نمبر"></div></div>
                            <div class="form-group col-xl-4 col-md-12 mb-0"><label>آئٹم کی رقم</label><input type="text" class="form-control js-line-total" value="Rs. 0.00" readonly></div>
                        </div>
                    </article>
                </div>
                <button type="button" class="counter-add-item" id="addMoreBtn"><i class="fas fa-plus"></i> مزید کپڑا شامل کریں</button>
                <div class="counter-summary-line"><span>کل رقم:</span><strong id="itemsTotalText">Rs. 0.00</strong></div>
            </div>
        </section>

        <section class="counter-sale-panel counter-payment-panel">
            <div class="counter-sale-section-head"><i class="fas fa-wallet"></i> ادائیگی کی تفصیل</div>
            <div class="counter-sale-panel-body">
                <div class="form-row">
                    <div class="form-group col-xl-3 col-md-6"><label for="counter_sale_method">ادائیگی کا طریقہ <span class="required">*</span></label><select id="counter_sale_method" name="payment_method" class="form-control" required>@foreach(\App\Support\PaymentMethods::LABELS as $methodValue => $methodLabel)<option value="{{ $methodValue }}" @selected(old('payment_method','cash') === $methodValue)>{{ $methodLabel }}</option>@endforeach</select></div>
                    <div class="form-group col-xl-3 col-md-6"><label for="counter_sale_paid_on">ادائیگی کی تاریخ <span class="required">*</span></label><input id="counter_sale_paid_on" type="date" name="paid_on" value="{{ old('paid_on',now()->toDateString()) }}" class="form-control" required></div>
                    <div class="form-group col-xl-3 col-md-6"><label for="payment">رقم وصول <span class="required">*</span></label><div class="counter-input"><i class="fas fa-money-bill"></i><input type="number" class="form-control has-suffix" name="payment" id="payment" min="0" step="0.01" value="{{ old('payment') }}" placeholder="وصول شدہ رقم" required><span class="counter-input-suffix">Rs.</span></div></div>
                    <div class="form-group col-xl-3 col-md-6"><label for="remain">بقایا رقم</label><input type="number" class="form-control" name="remain" id="remain" step="0.01" readonly></div>
                    <div class="form-group col-md-6 mb-md-0"><label for="counter_sale_reference">حوالہ / ٹرانزیکشن نمبر</label><input id="counter_sale_reference" type="text" name="payment_reference" value="{{ old('payment_reference') }}" maxlength="255" class="form-control" placeholder="بینک، والٹ، راست یا چیک نمبر"><small id="counter_sale_reference_hint" class="form-text text-muted">نقد ادائیگی کے لیے اختیاری ہے۔</small></div>
                    <div class="form-group col-md-6 mb-md-0"><label for="total">کل فروخت</label><input type="number" class="form-control" name="total" id="total" step="0.01" readonly></div>
                </div>
                <div class="counter-balance-line"><span>کل فروخت: <strong id="paymentTotalText">Rs. 0.00</strong></span><span>بقایا: <strong id="balanceText">Rs. 0.00</strong></span></div>
            </div>
        </section>

        <div class="counter-sale-submit"><button type="submit"><i class="far fa-save ml-2"></i> فروخت محفوظ کریں</button></div>
    </form>
</div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('sellStockForm');
    const container = document.getElementById('stockDataContainer');
    const addButton = document.getElementById('addMoreBtn');
    const customer = document.getElementById('c_name');
    const phone = document.getElementById('nmbr');
    const total = document.getElementById('total');
    const payment = document.getElementById('payment');
    const remaining = document.getElementById('remain');
    const method = document.getElementById('counter_sale_method');
    const reference = document.getElementById('counter_sale_reference');
    const referenceHint = document.getElementById('counter_sale_reference_hint');

    const money = value => 'Rs. ' + value.toFixed(2);
    const refreshItemNumbers = function () {
        container.querySelectorAll('.stock-data').forEach(function (item, index) {
            item.querySelector('.counter-item-number').textContent = index + 1;
        });
    };
    const calculateTotals = function () {
        let saleTotal = 0;
        container.querySelectorAll('.stock-data').forEach(function (item) {
            const rate = Number.parseFloat(item.querySelector('[name="per_meter[]"]').value) || 0;
            const length = Number.parseFloat(item.querySelector('[name="length[]"]').value) || 0;
            const lineTotal = rate * length;
            saleTotal += lineTotal;
            item.querySelector('.js-line-total').value = money(lineTotal);
            item.querySelector('.counter-line-total').textContent = money(lineTotal);
        });
        const received = Number.parseFloat(payment.value) || 0;
        const balance = Math.max(0, saleTotal - received);
        total.value = saleTotal.toFixed(2);
        remaining.value = balance.toFixed(2);
        document.getElementById('itemsTotalText').textContent = money(saleTotal);
        document.getElementById('paymentTotalText').textContent = money(saleTotal);
        document.getElementById('balanceText').textContent = money(balance);
    };
    const loadClothTypes = async function (item) {
        const brand = item.querySelector('.js-brand');
        const clothType = item.querySelector('.js-cloth-type');
        clothType.innerHTML = '<option value="" disabled selected>لوڈ ہو رہا ہے…</option>';
        try {
            const response = await fetch(form.dataset.typesUrl + '?id=' + encodeURIComponent(brand.value), {headers:{'Accept':'application/json'}});
            if (!response.ok) throw new Error();
            const payload = await response.json();
            clothType.innerHTML = '<option value="" disabled selected>کپڑے کی قسم منتخب کریں</option>';
            (payload.data || []).forEach(function (entry) { const option = document.createElement('option'); option.value = entry.cloth_type_id; option.textContent = entry.type ? entry.type.name : ''; clothType.appendChild(option); });
        } catch (error) { clothType.innerHTML = '<option value="" disabled selected>اقسام لوڈ نہیں ہو سکیں</option>'; }
    };
    const bindItem = function (item) {
        item.querySelector('.js-brand').addEventListener('change', () => loadClothTypes(item));
        item.querySelectorAll('[name="per_meter[]"], [name="length[]"]').forEach(input => input.addEventListener('input', calculateTotals));
        const remove = item.querySelector('.counter-remove-item');
        if (remove) remove.addEventListener('click', function () { item.remove(); refreshItemNumbers(); calculateTotals(); });
    };
    bindItem(container.querySelector('.stock-data'));

    customer.addEventListener('change', async function () {
        const customerId = customer.value.split('|')[1];
        if (!customerId) return;
        try { const response = await fetch(form.dataset.customerUrl + '?id=' + encodeURIComponent(customerId), {headers:{'Accept':'application/json'}}); if (!response.ok) throw new Error(); const payload = await response.json(); phone.value = payload.data?.phone_number1 || ''; } catch (error) { phone.value = ''; }
    });
    addButton.addEventListener('click', function () {
        const item = container.querySelector('.stock-data').cloneNode(true);
        item.querySelectorAll('input').forEach(input => { input.value = input.classList.contains('js-line-total') ? 'Rs. 0.00' : ''; });
        item.querySelectorAll('select').forEach(select => { select.selectedIndex = 0; });
        item.querySelector('.js-cloth-type').innerHTML = '<option value="" disabled selected>پہلے برانڈ منتخب کریں</option>';
        item.querySelector('.counter-line-total').textContent = 'Rs. 0.00';
        const actionWrap = item.querySelector('.counter-item-head .d-flex');
        const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'counter-remove-item mr-2'; remove.setAttribute('aria-label','یہ آئٹم ہٹائیں'); remove.innerHTML = '<i class="fas fa-trash"></i>'; actionWrap.appendChild(remove);
        container.appendChild(item); bindItem(item); refreshItemNumbers(); item.querySelector('.js-brand').focus();
    });
    const syncReference = function () { const required = !['cash','other'].includes(method.value); reference.required = required; reference.setAttribute('aria-required', required ? 'true' : 'false'); referenceHint.textContent = required ? 'منتخب ادائیگی کے طریقے کے لیے حوالہ نمبر ضروری ہے۔' : 'نقد ادائیگی کے لیے اختیاری ہے۔'; };
    method.addEventListener('change', syncReference); payment.addEventListener('input', calculateTotals); syncReference(); calculateTotals();
});
</script>
@endpush
