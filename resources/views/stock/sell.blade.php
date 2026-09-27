@extends('main')

@push('styles')
<style>
    .counter-sale-page{--sale-blue:#1769ef;--sale-ink:#14213d;--sale-muted:#718096;--sale-line:#e1e8f2;min-height:calc(100vh - 65px);padding:26px 0 46px;background:#f7f9fc;color:var(--sale-ink)}
    .counter-sale-shell{max-width:1560px;margin:auto;padding:0 24px}.counter-sale-breadcrumb{margin-bottom:12px;color:var(--sale-muted);font-size:.84rem}.counter-sale-breadcrumb a{color:inherit}.counter-sale-header{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:18px}.counter-sale-heading{display:flex;align-items:center;gap:14px}.counter-sale-heading-icon{display:grid;place-items:center;width:52px;height:52px;border:1px solid var(--sale-line);border-radius:13px;background:#fff;color:var(--sale-blue);font-size:21px;box-shadow:0 5px 18px rgba(25,67,120,.06)}.counter-sale-heading h1{margin:0 0 4px;font-size:1.6rem;font-weight:800}.counter-sale-heading p{margin:0;color:var(--sale-muted)}
    .counter-sale-panel{margin-bottom:14px;border:1px solid var(--sale-line);border-radius:13px;background:#fff;box-shadow:0 5px 20px rgba(28,63,105,.045)}.counter-sale-section-head{display:flex;align-items:center;gap:10px;padding:15px 19px 0;color:var(--sale-blue);font-size:1.04rem;font-weight:800}.counter-sale-panel-body{padding:18px 19px}.counter-sale-page label{display:block;margin-bottom:7px;color:#52627b;font-weight:700}.counter-sale-page .required{color:#e53e3e}.counter-sale-page .form-control{min-height:44px;border-color:#d8e1ed;border-radius:7px;background:#fff}.counter-sale-page select.form-control{height:50px;min-height:50px;padding-top:4px;padding-bottom:10px;line-height:1.8}.counter-sale-page .form-control:focus{border-color:#7aafff;box-shadow:0 0 0 3px rgba(23,105,239,.1)}.counter-input{position:relative}.counter-input>i{position:absolute;z-index:2;top:50%;right:14px;transform:translateY(-50%);color:#8492a7}.counter-input .form-control{padding-right:42px}.counter-input .has-suffix{padding-left:48px}.counter-input-suffix{position:absolute;z-index:2;top:1px;bottom:1px;left:1px;display:flex;align-items:center;padding:0 13px;border-right:1px solid var(--sale-line);border-radius:7px 0 0 7px;background:#f8fafc;color:#66758d;font-weight:700}
    .counter-customer-modes{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-bottom:16px}.counter-customer-mode{margin:0!important;cursor:pointer}.counter-customer-mode input{position:absolute;opacity:0;pointer-events:none}.counter-customer-mode>span{display:flex;align-items:center;gap:10px;min-height:58px;padding:10px 13px;border:1px solid #d8e1ed;border-radius:10px;background:#fbfcfe;color:#52627b}.counter-customer-mode i{display:grid;place-items:center;flex:0 0 36px;width:36px;height:36px;border-radius:9px;color:#75869d;background:#edf2f7}.counter-customer-mode strong{display:block;color:#344a67}.counter-customer-mode small{display:block;margin-top:2px;color:#8190a3;font-size:.7rem}.counter-customer-mode input:checked+span{border-color:#86b5f8;background:#edf5ff;box-shadow:0 0 0 2px rgba(23,105,239,.08)}.counter-customer-mode input:checked+span i{color:#fff;background:var(--sale-blue)}.counter-customer-fields[hidden]{display:none!important}.counter-customer-note{margin:8px 0 0;color:#718096;font-size:.73rem}
    .counter-sale-item{position:relative;display:grid;grid-template-columns:140px 42px minmax(145px,1.15fr) minmax(145px,1fr) minmax(145px,1fr) minmax(105px,.65fr) minmax(125px,.75fr) minmax(120px,.7fr);align-items:end;gap:10px;padding:13px;margin-bottom:10px;border:1px solid #e4eaf3;border-radius:11px;background:#fbfcfe}.counter-sale-item .form-group{min-width:0;margin:0}.counter-sale-item label{overflow:hidden;margin-bottom:5px;white-space:nowrap;text-overflow:ellipsis;font-size:.78rem}.counter-item-identity{display:flex;align-items:center;justify-content:center;height:44px}.counter-item-number{display:grid;place-items:center;width:30px;height:30px;border-radius:8px;background:#eaf2ff;color:var(--sale-blue);font-family:Arial,sans-serif;font-weight:800}.counter-item-summary{display:flex;align-items:center;justify-content:center;flex-direction:column;align-self:stretch;gap:4px;padding:8px;border-left:1px solid #dfe7f0;border-radius:8px;background:#f3f7fd}.counter-item-summary small{color:#75869d;font-size:.68rem;font-weight:800}.counter-item-actions{display:flex;align-items:center;gap:5px}.counter-line-total{color:var(--sale-blue);font:800 .86rem Arial,sans-serif;direction:ltr;white-space:nowrap}.counter-remove-item{display:grid;place-items:center;width:28px;height:28px;border:0;border-radius:7px;background:#fff0f1;color:#dc3545}.counter-add-item{display:inline-flex;align-items:center;gap:8px;min-height:42px;padding:8px 15px;border:1px solid var(--sale-blue);border-radius:7px;background:#fff;color:var(--sale-blue);font-weight:800}.counter-add-item:hover{background:#edf4ff;color:#0f5ddf}
    .counter-summary-line{display:flex;align-items:center;justify-content:flex-end;gap:8px;margin-top:8px;font-weight:800}.counter-summary-line strong{color:var(--sale-blue);font:800 1rem Arial,sans-serif;direction:ltr}.counter-payment-panel{border-color:#d9eee1;background:linear-gradient(135deg,#fbfffc,#f1fbf5)}.counter-payment-panel .counter-sale-section-head{color:#1a9b57}.counter-balance-line{display:flex;align-items:center;gap:18px;margin-top:10px;color:#52627b;font-weight:700}.counter-balance-line strong{color:#1a9b57;font:800 1rem Arial,sans-serif;direction:ltr}.counter-sale-submit{display:flex;justify-content:center;padding-top:4px}.counter-sale-submit button{min-width:280px;min-height:48px;border:0;border-radius:8px;background:linear-gradient(135deg,#1769ef,#287fff);color:#fff;font-weight:800;box-shadow:0 9px 23px rgba(23,105,239,.22)}.counter-sale-back{min-height:43px;padding:9px 16px;border-radius:8px;font-weight:700}.counter-sale-alert{border-radius:10px}
    .live-sale-banner{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:14px;padding:14px 18px;border:1px solid #b9dcce;border-radius:12px;background:#eefaf5;color:#235e49}.live-sale-banner strong{display:block;color:#145d43}.live-sale-banner small{color:#527666}
    @media(max-width:1199.98px){.counter-sale-item{grid-template-columns:120px 42px repeat(3,minmax(150px,1fr));align-items:end}.counter-sale-item .counter-item-summary,.counter-sale-item .counter-item-identity{grid-row:span 2}.counter-sale-item .counter-item-summary{border-left:0;border-bottom:1px solid #dfe7f0}.counter-sale-item .js-rate-wrap{grid-column:auto}}
    @media(max-width:767.98px){.counter-sale-shell{padding:0 12px}.counter-sale-header{align-items:stretch;flex-direction:column}.counter-sale-back{width:100%}.counter-sale-panel-body{padding:15px 13px}.counter-customer-modes{grid-template-columns:1fr}.counter-sale-item{grid-template-columns:1fr 42px;padding:12px}.counter-sale-item .counter-item-summary{grid-column:1;grid-row:1;align-self:auto;align-items:flex-start;border-bottom:0}.counter-sale-item .counter-item-identity{grid-column:2;grid-row:1}.counter-sale-item .form-group{grid-column:1/-1}.counter-sale-submit button{width:100%;min-width:0}.counter-balance-line{align-items:flex-start;flex-direction:column;gap:5px}}
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

    @php
        $sessionDefaults = $saleForm ?? [];
        $saleCustomerMode = old('customer_mode', $sessionDefaults['customer_mode'] ?? 'regular');
        $oldBrands = old('brand_name');
        if (is_array($oldBrands)) {
            $saleItems = collect($oldBrands)->map(fn ($brand, $index) => [
                'brand_id' => $brand,
                'type_id' => data_get(old('cloth_type', []), $index, ''),
                'color' => data_get(old('color', []), $index, ''),
                'length' => data_get(old('length', []), $index, ''),
                'item_total' => data_get(old('item_total', []), $index, ''),
                'rack' => data_get(old('clothes_rack', []), $index, ''),
            ])->all();
        } else {
            $saleItems = $sessionDefaults['items'] ?? [[]];
        }
    @endphp

    @if($saleSession)
        <div class="live-sale-banner">
            <div><strong>سیلز ایجنٹ کی لائیو فروخت</strong><small>{{ $saleSession->agent?->name }} کی موبائل کارٹ اسی فارم میں کھولی گئی ہے۔</small></div>
            <span>{{ count($saleItems) }} آئٹمز</span>
        </div>
    @endif

    <form action="{{ route('admin.sellStock') }}" method="post" id="sellStockForm" data-customer-url="{{ url('/admin/getNmbr') }}" data-types-url="{{ url('/admin/getType') }}">
        @csrf
        @if($saleSession)<input type="hidden" name="sale_session_uuid" value="{{ $saleSession->uuid }}">@endif

        <section class="counter-sale-panel">
            <div class="counter-sale-section-head"><i class="far fa-file-alt"></i> بنیادی معلومات — گاہک کی معلومات</div>
            <div class="counter-sale-panel-body">
                <div class="counter-customer-modes" role="radiogroup" aria-label="گاہک کی قسم">
                    <label class="counter-customer-mode"><input type="radio" name="customer_mode" value="regular" @checked($saleCustomerMode === 'regular')><span><i class="fas fa-users"></i><span><strong>ریگولر گاہک</strong><small>موجودہ گاہکوں کی فہرست سے منتخب کریں</small></span></span></label>
                    <label class="counter-customer-mode"><input type="radio" name="customer_mode" value="random" @checked($saleCustomerMode === 'random')><span><i class="fas fa-user-plus"></i><span><strong>رینڈم / نیا گاہک</strong><small>نام لکھیں، نمبر اختیاری ہے</small></span></span></label>
                </div>
                <input type="hidden" name="c_name" id="legacy_c_name" value="{{ old('c_name') }}">
                <div class="counter-customer-fields" id="regularCustomerFields" @if($saleCustomerMode !== 'regular') hidden @endif><div class="form-row">
                    <div class="form-group col-md-6 mb-md-0"><label for="existing_customer_id">گاہک کا نام <span class="required">*</span></label><div class="counter-input"><i class="far fa-user"></i><select name="existing_customer_id" class="form-control custom-select" id="existing_customer_id"><option value="">گاہک منتخب کریں</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" data-legacy="{{ $customer->name.'|'.$customer->id }}" @selected((string) old('existing_customer_id', $sessionDefaults['customer_id'] ?? '') === (string) $customer->id)>{{ $customer->name }} (#{{ $customer->id }})</option>@endforeach</select></div>@error('existing_customer_id')<div class="text-danger mt-1">{{ $message }}</div>@enderror</div>
                    <div class="form-group col-md-6 mb-md-0"><label for="nmbr">رابطہ نمبر</label><div class="counter-input"><i class="fas fa-phone"></i><input type="tel" inputmode="tel" class="form-control" name="phone" id="nmbr" value="{{ old('phone') }}" placeholder="منتخب گاہک کا رابطہ نمبر" autocomplete="tel" readonly></div></div>
                </div></div>
                <div class="counter-customer-fields" id="randomCustomerFields" @if($saleCustomerMode !== 'random') hidden @endif><div class="form-row">
                    <div class="form-group col-md-6 mb-md-0"><label for="random_customer_name">نئے گاہک کا نام <span class="required">*</span></label><div class="counter-input"><i class="far fa-user"></i><input type="text" class="form-control" name="random_customer_name" id="random_customer_name" value="{{ old('random_customer_name', $sessionDefaults['customer_name'] ?? '') }}" maxlength="255" placeholder="گاہک کا نام لکھیں" autocomplete="name"></div>@error('random_customer_name')<div class="text-danger mt-1">{{ $message }}</div>@enderror</div>
                    <div class="form-group col-md-6 mb-md-0"><label for="random_customer_phone">رابطہ نمبر <small>(اختیاری)</small></label><div class="counter-input"><i class="fas fa-phone"></i><input type="tel" inputmode="tel" class="form-control" name="random_customer_phone" id="random_customer_phone" value="{{ old('random_customer_phone', $sessionDefaults['customer_phone'] ?? '') }}" maxlength="30" placeholder="نمبر ہو تو لکھیں" autocomplete="tel"></div>@error('random_customer_phone')<div class="text-danger mt-1">{{ $message }}</div>@enderror</div>
                </div><p class="counter-customer-note"><i class="fas fa-info-circle ml-1"></i>فروخت محفوظ ہونے پر یہ گاہک خود بخود گاہکوں کی فہرست میں شامل ہو جائے گا۔</p></div>
            </div>
        </section>

        <section class="counter-sale-panel">
            <div class="counter-sale-section-head"><i class="fas fa-plus-square"></i> فروخت کے آئٹمز شامل کریں</div>
            <div class="counter-sale-panel-body">
                <div id="stockDataContainer" aria-live="polite">
                    @foreach($saleItems as $itemIndex => $saleItem)
                    @php
                        $selectedBrand = (string) ($saleItem['brand_id'] ?? '');
                        $selectedType = (string) ($saleItem['type_id'] ?? '');
                        $selectedColor = (string) ($saleItem['color'] ?? '');
                        $typeCloths = $cloths->where('cloth_brand_id', $selectedBrand)->unique('cloth_type_id');
                        $colorCloth = $cloths->first(fn ($cloth) => (string) $cloth->cloth_brand_id === $selectedBrand && (string) $cloth->cloth_type_id === $selectedType);
                    @endphp
                    <article class="stock-data counter-sale-item">
                        <div class="counter-item-summary"><small>آئٹم کا خلاصہ</small><div class="counter-item-actions"><span class="counter-line-total">Rs. {{ number_format((float) ($saleItem['item_total'] ?? 0), 2) }}</span>@if($itemIndex > 0)<button type="button" class="counter-remove-item" aria-label="یہ آئٹم ہٹائیں"><i class="fas fa-trash"></i></button>@endif</div></div>
                        <div class="counter-item-identity"><span class="counter-item-number">{{ $itemIndex + 1 }}</span></div>
                        <div class="form-group"><label>برانڈ <span class="required">*</span></label><select class="form-control js-brand" name="brand_name[]" required><option value="" disabled @selected($selectedBrand === '')>برانڈ منتخب کریں</option>@foreach($cloths->unique('cloth_brand_id') as $cloth)<option value="{{ $cloth->cloth_brand_id }}" @selected($selectedBrand === (string) $cloth->cloth_brand_id)>{{ $cloth->brand->name }}</option>@endforeach</select></div>
                        <div class="form-group"><label>کپڑے کی قسم <span class="required">*</span></label><select class="form-control js-cloth-type" name="cloth_type[]" required><option value="" disabled @selected($selectedType === '')>پہلے برانڈ منتخب کریں</option>@foreach($typeCloths as $cloth)<option value="{{ $cloth->cloth_type_id }}" @selected($selectedType === (string) $cloth->cloth_type_id)>{{ $cloth->type->name }}</option>@endforeach</select></div>
                        <div class="form-group"><label>رنگ <small>(اختیاری)</small></label><select class="form-control js-color" name="color[]"><option value="" @selected($selectedColor === '')>رنگ معلوم نہیں</option>@foreach(($colorCloth?->colors ?? collect())->where('length', '>', 0) as $color)<option value="{{ $color->color }}" @selected($selectedColor === (string) $color->color)>{{ $color->color }} ({{ (float) $color->length }} میٹر)</option>@endforeach</select></div>
                        <div class="form-group"><label>میٹر / گز <span class="required">*</span></label><div class="counter-input"><i class="fas fa-ruler"></i><input type="number" class="form-control" name="length[]" value="{{ $saleItem['length'] ?? '' }}" min="0.01" step="0.01" placeholder="مقدار" required></div></div>
                        <div class="form-group"><label>کل قیمت <span class="required">*</span></label><div class="counter-input"><i class="fas fa-money-bill-wave"></i><input type="number" class="form-control has-suffix" name="item_total[]" value="{{ $saleItem['item_total'] ?? '' }}" min="0" step="0.01" placeholder="کل قیمت" required><input type="hidden" name="per_meter[]" value="0"><input type="hidden" name="clothes_rack[]" value=""><span class="counter-input-suffix">Rs.</span></div></div>
                        <div class="form-group js-rate-wrap"><label>ریٹ فی میٹر <small>(خودکار)</small></label><input type="text" class="form-control js-rate-per-meter" value="Rs. 0.00" readonly></div>
                    </article>
                    @endforeach
                </div>
                <button type="button" class="counter-add-item" id="addMoreBtn"><i class="fas fa-plus"></i> مزید کپڑا شامل کریں</button>
                <div class="counter-summary-line"><span>کل رقم:</span><strong id="itemsTotalText">Rs. 0.00</strong></div>
            </div>
        </section>

        <section class="counter-sale-panel counter-payment-panel">
            <div class="counter-sale-section-head"><i class="fas fa-wallet"></i> ادائیگی کی تفصیل</div>
            <div class="counter-sale-panel-body">
                <div class="form-row">
                    <div class="form-group col-xl-3 col-md-6"><label for="counter_sale_method">ادائیگی کا طریقہ <span class="required">*</span></label><select id="counter_sale_method" name="payment_method" class="form-control" required>@foreach(\App\Support\PaymentMethods::LABELS as $methodValue => $methodLabel)<option value="{{ $methodValue }}" @selected(old('payment_method', $sessionDefaults['payment_method'] ?? 'cash') === $methodValue)>{{ $methodLabel }}</option>@endforeach</select></div>
                    <div class="form-group col-xl-3 col-md-6"><label for="counter_sale_paid_on">ادائیگی کی تاریخ <span class="required">*</span></label><input id="counter_sale_paid_on" type="date" name="paid_on" value="{{ old('paid_on',now()->toDateString()) }}" class="form-control" required></div>
                    <div class="form-group col-xl-3 col-md-6"><label for="payment">رقم وصول <span class="required">*</span></label><div class="counter-input"><i class="fas fa-money-bill"></i><input type="number" class="form-control has-suffix" name="payment" id="payment" min="0" step="0.01" value="{{ old('payment', $sessionDefaults['payment'] ?? '') }}" placeholder="وصول شدہ رقم" required><span class="counter-input-suffix">Rs.</span></div></div>
                    <div class="form-group col-xl-3 col-md-6"><label for="remain">بقایا رقم</label><input type="number" class="form-control" name="remain" id="remain" step="0.01" readonly></div>
                    <div class="form-group col-md-6 mb-md-0"><label for="counter_sale_reference">حوالہ / ٹرانزیکشن نمبر</label><input id="counter_sale_reference" type="text" name="payment_reference" value="{{ old('payment_reference', $sessionDefaults['payment_reference'] ?? '') }}" maxlength="255" class="form-control" placeholder="بینک، والٹ، راست یا چیک نمبر"><small id="counter_sale_reference_hint" class="form-text text-muted">نقد ادائیگی کے لیے اختیاری ہے۔</small></div>
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
    const customerModes = form.querySelectorAll('[name="customer_mode"]');
    const customer = document.getElementById('existing_customer_id');
    const legacyCustomer = document.getElementById('legacy_c_name');
    const regularCustomerFields = document.getElementById('regularCustomerFields');
    const randomCustomerFields = document.getElementById('randomCustomerFields');
    const randomCustomerName = document.getElementById('random_customer_name');
    const phone = document.getElementById('nmbr');
    const total = document.getElementById('total');
    const payment = document.getElementById('payment');
    const remaining = document.getElementById('remain');
    const method = document.getElementById('counter_sale_method');
    const reference = document.getElementById('counter_sale_reference');
    const referenceHint = document.getElementById('counter_sale_reference_hint');
    const inventoryOptions = @json($inventoryOptions);

    const money = value => 'Rs. ' + value.toFixed(2);
    const refreshItemNumbers = function () {
        container.querySelectorAll('.stock-data').forEach(function (item, index) {
            item.querySelector('.counter-item-number').textContent = index + 1;
        });
    };
    const calculateTotals = function () {
        let saleTotal = 0;
        container.querySelectorAll('.stock-data').forEach(function (item) {
            const length = Number.parseFloat(item.querySelector('[name="length[]"]').value) || 0;
            const lineTotal = Number.parseFloat(item.querySelector('[name="item_total[]"]').value) || 0;
            const rate = length > 0 ? lineTotal / length : 0;
            saleTotal += lineTotal;
            item.querySelector('[name="per_meter[]"]').value = rate.toFixed(2);
            item.querySelector('.js-rate-per-meter').value = money(rate);
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
        const color = item.querySelector('.js-color');
        clothType.innerHTML = '<option value="" disabled selected>لوڈ ہو رہا ہے…</option>';
        color.innerHTML = '<option value="" selected>رنگ معلوم نہیں</option>';
        try {
            const response = await fetch(form.dataset.typesUrl + '?id=' + encodeURIComponent(brand.value), {headers:{'Accept':'application/json'}});
            if (!response.ok) throw new Error();
            const payload = await response.json();
            clothType.innerHTML = '<option value="" disabled selected>کپڑے کی قسم منتخب کریں</option>';
            (payload.data || []).forEach(function (entry) { const option = document.createElement('option'); option.value = entry.cloth_type_id; option.textContent = entry.type ? entry.type.name : ''; clothType.appendChild(option); });
        } catch (error) { clothType.innerHTML = '<option value="" disabled selected>اقسام لوڈ نہیں ہو سکیں</option>'; }
    };
    const loadColors = function (item) {
        const brandId = item.querySelector('.js-brand').value;
        const typeId = item.querySelector('.js-cloth-type').value;
        const color = item.querySelector('.js-color');
        const match = inventoryOptions.find(entry => entry.brand_id === brandId && entry.type_id === typeId);
        color.innerHTML = '<option value="" selected>رنگ معلوم نہیں</option>';
        (match?.colors || []).filter(entry => entry.length > 0).forEach(function (entry) {
            const option = document.createElement('option');
            option.value = entry.name;
            option.textContent = entry.name + ' (' + entry.length + ' میٹر)';
            color.appendChild(option);
        });
        if (!match || !match.colors.some(entry => entry.length > 0)) {
            color.innerHTML = '<option value="" selected>اس کپڑے کا کوئی رنگ اسٹاک میں نہیں</option>';
        }
    };
    const bindItem = function (item) {
        item.querySelector('.js-brand').addEventListener('change', () => loadClothTypes(item));
        item.querySelector('.js-cloth-type').addEventListener('change', () => loadColors(item));
        item.querySelectorAll('[name="item_total[]"], [name="length[]"]').forEach(input => input.addEventListener('input', calculateTotals));
        const remove = item.querySelector('.counter-remove-item');
        if (remove) remove.addEventListener('click', function () { item.remove(); refreshItemNumbers(); calculateTotals(); });
    };
    container.querySelectorAll('.stock-data').forEach(bindItem);

    const syncCustomerMode = function () {
        const mode = form.querySelector('[name="customer_mode"]:checked')?.value || 'regular';
        const isRegular = mode === 'regular';
        regularCustomerFields.hidden = !isRegular;
        randomCustomerFields.hidden = isRegular;
        customer.required = isRegular;
        randomCustomerName.required = !isRegular;
        if (isRegular) {
            const selected = customer.options[customer.selectedIndex];
            legacyCustomer.value = selected?.dataset.legacy || '';
        } else {
            legacyCustomer.value = '';
        }
    };
    customerModes.forEach(input => input.addEventListener('change', syncCustomerMode));
    customer.addEventListener('change', async function () {
        const selected = customer.options[customer.selectedIndex];
        legacyCustomer.value = selected?.dataset.legacy || '';
        const customerId = customer.value;
        if (!customerId) return;
        try { const response = await fetch(form.dataset.customerUrl + '?id=' + encodeURIComponent(customerId), {headers:{'Accept':'application/json'}}); if (!response.ok) throw new Error(); const payload = await response.json(); phone.value = payload.data?.phone_number1 || ''; } catch (error) { phone.value = ''; }
    });
    addButton.addEventListener('click', function () {
        const item = container.querySelector('.stock-data').cloneNode(true);
        item.querySelectorAll('input').forEach(input => { input.value = input.classList.contains('js-rate-per-meter') ? 'Rs. 0.00' : (input.name === 'per_meter[]' ? '0' : ''); });
        item.querySelectorAll('select').forEach(select => { select.selectedIndex = 0; });
        item.querySelector('.js-cloth-type').innerHTML = '<option value="" disabled selected>پہلے برانڈ منتخب کریں</option>';
        item.querySelector('.js-color').innerHTML = '<option value="" selected>رنگ معلوم نہیں</option>';
        item.querySelector('.counter-line-total').textContent = 'Rs. 0.00';
        const actionWrap = item.querySelector('.counter-item-actions');
        const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'counter-remove-item'; remove.setAttribute('aria-label','یہ آئٹم ہٹائیں'); remove.innerHTML = '<i class="fas fa-trash"></i>'; actionWrap.appendChild(remove);
        container.appendChild(item); bindItem(item); refreshItemNumbers(); item.querySelector('.js-brand').focus();
    });
    const syncReference = function () { const required = !['cash','other'].includes(method.value); reference.required = required; reference.setAttribute('aria-required', required ? 'true' : 'false'); referenceHint.textContent = required ? 'منتخب ادائیگی کے طریقے کے لیے حوالہ نمبر ضروری ہے۔' : 'نقد ادائیگی کے لیے اختیاری ہے۔'; };
    method.addEventListener('change', syncReference); payment.addEventListener('input', calculateTotals); syncCustomerMode(); syncReference(); calculateTotals();
});
</script>
@endpush
