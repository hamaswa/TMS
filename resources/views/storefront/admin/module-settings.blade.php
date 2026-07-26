@extends('main')
@section('content')
@php
    $isTailoring = $module === 'tailoring';
    $prefix = $isTailoring ? 'tailoring' : 'clothing';
    $title = $isTailoring ? 'ٹیلرنگ کی ترتیب' : 'کپڑوں کی دکان کی ترتیب';
    $acceptingField = $isTailoring ? 'tailoring_inquiries_enabled' : 'clothing_online_ordering_enabled';
    $legacyAcceptingField = $isTailoring ? 'inquiries_enabled' : 'online_ordering_enabled';
    $setting = fn (string $field, string $legacy) => $storefront->getAttribute($field) === null
        ? (bool) $storefront->getAttribute($legacy)
        : (bool) $storefront->getAttribute($field);
    $accepting = $setting($acceptingField, $legacyAcceptingField);
    $unpaid = $setting("{$prefix}_unpaid_enabled", 'unpaid_orders_enabled');
    $cod = $setting("{$prefix}_cod_enabled", 'cod_enabled');
    $easypaisa = $setting("{$prefix}_easypaisa_enabled", 'easypaisa_enabled');
    $jazzcash = $setting("{$prefix}_jazzcash_enabled", 'jazzcash_enabled');
    $bank = $setting("{$prefix}_bank_transfer_enabled", 'bank_transfer_enabled');
    $raast = $setting("{$prefix}_raast_enabled", 'raast_enabled');
    $pickup = $setting("{$prefix}_pickup_enabled", 'pickup_enabled');
    $delivery = $setting("{$prefix}_delivery_enabled", 'delivery_enabled');
    $paymentMode = old('payment_collection_mode', $unpaid ? 'none' : 'methods');
    $showMethods = $paymentMode === 'methods';
    $selected = [
        'easypaisa' => (bool) old('easypaisa_enabled', $easypaisa),
        'jazzcash' => (bool) old('jazzcash_enabled', $jazzcash),
        'bank' => (bool) old('bank_transfer_enabled', $bank),
        'raast' => (bool) old('raast_enabled', $raast),
    ];
@endphp
<section class="main-content module-settings-page">
    <div class="container py-4" style="max-width:1050px" dir="rtl">
        <div class="module-settings-hero mb-4">
            <div>
                <div class="small text-white-50">آن لائن دکان · الگ سروس کنٹرول</div>
                <h1 class="h3 mb-1">{{ $title }}</h1>
                <p class="mb-0 text-white-50">
                    {{ $isTailoring
                        ? 'ٹیلرنگ درخواستیں، ادائیگی اور حوالگی کپڑوں کی دکان سے الگ ترتیب دیں۔'
                        : 'عوامی فہرست برقرار رکھتے ہوئے آرڈر، ادائیگی اور حوالگی الگ ترتیب دیں۔' }}
                </p>
            </div>
            <a class="btn btn-light mt-3 mt-md-0" href="{{ route('admin.storefront.edit') }}">مرکزی دکان کی ترتیب</a>
        </div>

        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <form method="POST" enctype="multipart/form-data" action="{{ route('admin.storefront.module-settings.update', $module) }}">
            @csrf @method('PUT')
            <div class="card module-card mb-4">
                <div class="card-header">
                    <h2 class="h5 mb-1">{{ $isTailoring ? 'نئی ٹیلرنگ درخواستیں' : 'آن لائن کپڑوں کے آرڈر' }}</h2>
                    <p class="small text-muted mb-0">اسے بند کرنے سے پرانا ریکارڈ محفوظ رہے گا؛ صرف نئی سرگرمی رک جائے گی۔</p>
                </div>
                <div class="card-body">
                    <label class="setting-choice">
                        <input type="checkbox" name="accepting_enabled" value="1" @checked(old('accepting_enabled', $accepting))>
                        <span>
                            <strong>{{ $isTailoring ? 'گاہکوں سے نئی سلائی درخواستیں قبول کریں' : 'گاہکوں سے آن لائن آرڈر قبول کریں' }}</strong>
                            <small>{{ $isTailoring ? 'بند ہونے پر خدمات اور قیمتیں نظر آئیں گی، مگر درخواست فارم دستیاب نہیں ہوگا۔' : 'بند ہونے پر کپڑے بطور کیٹلاگ نظر آئیں گے، مگر ٹوکری اور چیک آؤٹ بند ہوں گے۔' }}</small>
                        </span>
                    </label>
                </div>
            </div>

            <div class="card module-card mb-4">
                <div class="card-header"><h2 class="h5 mb-1">ادائیگی کی پالیسی</h2><p class="small text-muted mb-0">یہ انتخاب صرف {{ $isTailoring ? 'ٹیلرنگ درخواستوں' : 'کپڑوں کے آرڈرز' }} پر لاگو ہوگا۔</p></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="setting-choice">
                                <input type="radio" name="payment_collection_mode" value="none" data-payment-mode @checked($paymentMode === 'none') required>
                                <span><strong>ابھی ادائیگی ضروری نہیں</strong><small>گاہک بغیر ادائیگی درخواست یا آرڈر بھیج سکے گا۔</small></span>
                            </label>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="setting-choice">
                                <input type="radio" name="payment_collection_mode" value="methods" data-payment-mode @checked($paymentMode === 'methods') required>
                                <span><strong>ادائیگی کے طریقے منتخب کریں</strong><small>صرف منتخب طریقے گاہک کو دکھائے جائیں گے۔</small></span>
                            </label>
                        </div>
                    </div>

                    <div id="module-payment-methods" class="mt-3" @hidden(! $showMethods)>
                        <div class="row">
                            <div class="col-md-4 mb-2"><label class="setting-choice compact"><input type="checkbox" name="cod_enabled" value="1" data-payment-option @checked(old('cod_enabled', $cod))> <span><strong>کیش آن ڈیلیوری</strong></span></label></div>
                            <div class="col-md-4 mb-2"><label class="setting-choice compact"><input type="checkbox" name="easypaisa_enabled" value="1" data-payment-option data-method="easypaisa" @checked($selected['easypaisa'])> <span><strong>ایزی پیسہ</strong></span></label></div>
                            <div class="col-md-4 mb-2"><label class="setting-choice compact"><input type="checkbox" name="jazzcash_enabled" value="1" data-payment-option data-method="jazzcash" @checked($selected['jazzcash'])> <span><strong>جاز کیش</strong></span></label></div>
                            <div class="col-md-4 mb-2"><label class="setting-choice compact"><input type="checkbox" name="bank_transfer_enabled" value="1" data-payment-option data-method="bank" @checked($selected['bank'])> <span><strong>بینک ٹرانسفر</strong></span></label></div>
                            <div class="col-md-4 mb-2"><label class="setting-choice compact"><input type="checkbox" name="raast_enabled" value="1" data-payment-option data-method="raast" @checked($selected['raast'])> <span><strong>راست / Raast</strong></span></label></div>
                        </div>

                        <div class="payment-panel" data-details="easypaisa" @hidden(! $selected['easypaisa'])>
                            <h3 class="h6">ایزی پیسہ وصولی کی معلومات</h3>
                            <div class="form-row"><div class="form-group col-md-6"><label>اکاؤنٹ عنوان</label><input name="easypaisa_account_title" class="form-control" maxlength="150" value="{{ old('easypaisa_account_title', $storefront->easypaisa_account_title) }}"></div><div class="form-group col-md-6"><label>اکاؤنٹ نمبر</label><input name="easypaisa_account_number" dir="ltr" class="form-control text-left" maxlength="50" value="{{ old('easypaisa_account_number', $storefront->easypaisa_account_number) }}"></div></div>
                        </div>
                        <div class="payment-panel" data-details="jazzcash" @hidden(! $selected['jazzcash'])>
                            <h3 class="h6">جاز کیش وصولی کی معلومات</h3>
                            <div class="form-row"><div class="form-group col-md-6"><label>اکاؤنٹ عنوان</label><input name="jazzcash_account_title" class="form-control" maxlength="150" value="{{ old('jazzcash_account_title', $storefront->jazzcash_account_title) }}"></div><div class="form-group col-md-6"><label>اکاؤنٹ نمبر</label><input name="jazzcash_account_number" dir="ltr" class="form-control text-left" maxlength="50" value="{{ old('jazzcash_account_number', $storefront->jazzcash_account_number) }}"></div></div>
                        </div>
                        <div class="payment-panel" data-details="bank" @hidden(! $selected['bank'])>
                            <h3 class="h6">بینک وصولی کی معلومات</h3>
                            <div class="form-row"><div class="form-group col-md-4"><label>بینک</label><input name="bank_name" class="form-control" maxlength="150" value="{{ old('bank_name', $storefront->bank_name) }}"></div><div class="form-group col-md-4"><label>اکاؤنٹ عنوان</label><input name="bank_account_title" class="form-control" maxlength="150" value="{{ old('bank_account_title', $storefront->bank_account_title) }}"></div><div class="form-group col-md-4"><label>اکاؤنٹ نمبر</label><input name="bank_account_number" dir="ltr" class="form-control text-left" maxlength="100" value="{{ old('bank_account_number', $storefront->bank_account_number) }}"></div><div class="form-group col-12"><label>IBAN</label><input name="bank_iban" dir="ltr" class="form-control text-left text-uppercase" maxlength="24" value="{{ old('bank_iban', $storefront->bank_iban) }}"></div></div>
                        </div>
                        <div class="payment-panel" data-details="raast" @hidden(! $selected['raast'])>
                            <h3 class="h6">راست وصولی کی معلومات</h3>
                            <div class="form-row"><div class="form-group col-md-6"><label>اکاؤنٹ عنوان</label><input name="raast_account_title" class="form-control" maxlength="150" value="{{ old('raast_account_title', $storefront->raast_account_title) }}"></div><div class="form-group col-md-6"><label>راست ID</label><input name="raast_id" dir="ltr" class="form-control text-left" maxlength="100" value="{{ old('raast_id', $storefront->raast_id) }}"></div><div class="form-group col-12"><label>بینک یا والٹ کا جاری کردہ Raast QR <small class="text-muted">(اختیاری، زیادہ سے زیادہ 2 MB)</small></label><input type="file" name="raast_qr" class="form-control-file" accept="image/*">@if($storefront->raast_qr_url)<img src="{{ $storefront->raast_qr_url }}" alt="موجودہ Raast QR" style="display:block;max-width:160px;max-height:160px;margin-top:10px">@endif</div></div>
                        </div>
                        <p class="small text-muted mb-0">اکاؤنٹ معلومات محفوظ اور مشترک رہیں گی؛ ہر شعبہ صرف یہ طے کرتا ہے کہ کون سا طریقہ استعمال کرنا ہے۔</p>
                    </div>
                </div>
            </div>

            <div class="card module-card mb-4">
                <div class="card-header"><h2 class="h5 mb-1">وصولی اور فراہمی</h2><p class="small text-muted mb-0">یہ سہولیات بھی صرف موجودہ شعبے پر لاگو ہوں گی۔</p></div>
                <div class="card-body"><div class="row">
                    <div class="col-md-6 mb-2"><label class="setting-choice compact"><input type="checkbox" name="pickup_enabled" value="1" @checked(old('pickup_enabled', $pickup))><span><strong>{{ $isTailoring ? 'دکان پر پیمائش / وصولی' : 'دکان سے آرڈر وصول کریں' }}</strong></span></label></div>
                    <div class="col-md-6 mb-2"><label class="setting-choice compact"><input type="checkbox" name="delivery_enabled" value="1" @checked(old('delivery_enabled', $delivery))><span><strong>گھر تک فراہمی</strong></span></label></div>
                </div></div>
            </div>

            <div class="d-flex justify-content-between align-items-center">
                <a class="btn btn-light" href="{{ $isTailoring ? route('admin.storefront.tailoring.services') : route('admin.storefront.clothing.index') }}">{{ $isTailoring ? 'ٹیلرنگ خدمات دیکھیں' : 'عوامی کپڑے دیکھیں' }}</a>
                <button class="btn btn-primary px-4"><i class="fas fa-save ml-1"></i> {{ $title }} محفوظ کریں</button>
            </div>
        </form>
    </div>
</section>
<style>
.module-settings-page{background:#f3f6f8;min-height:100vh}.module-settings-hero{background:linear-gradient(135deg,#123e5d,#176b68);color:#fff;border-radius:18px;padding:28px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 12px 30px rgba(18,62,93,.16)}.module-settings-hero h1{color:#fff!important}.module-card{border:0;border-radius:16px;box-shadow:0 8px 24px rgba(26,54,73,.08);overflow:hidden}.module-card .card-header{background:#fff;border-bottom:1px solid #e9eef2;padding:20px 24px}.module-card .card-body{padding:24px}.setting-choice{border:1px solid #d9e4e8;border-radius:12px;padding:16px;display:flex;gap:12px;width:100%;background:#fff;cursor:pointer;align-items:flex-start}.setting-choice:hover{border-color:#2b7a78;background:#f8fcfc}.setting-choice input{margin-top:5px;flex:none}.setting-choice span{display:block}.setting-choice small{display:block;color:#6c757d;margin-top:4px}.setting-choice.compact{padding:13px}.payment-panel{border:1px solid #dce6ea;border-radius:12px;padding:18px;margin:12px 0;background:#f9fbfc}@media(max-width:767px){.module-settings-hero{display:block;padding:22px}.module-card .card-body{padding:18px}}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modes = Array.from(document.querySelectorAll('[data-payment-mode]'));
    var methods = document.getElementById('module-payment-methods');
    var options = Array.from(document.querySelectorAll('[data-payment-option]'));
    function refresh() {
        var enabled = modes.some(function (input) { return input.checked && input.value === 'methods'; });
        methods.hidden = ! enabled;
        options.forEach(function (input) { input.disabled = ! enabled; });
        document.querySelectorAll('[data-details]').forEach(function (panel) {
            var input = document.querySelector('[data-method="' + panel.dataset.details + '"]');
            panel.hidden = ! enabled || ! input || ! input.checked;
            panel.querySelectorAll('input').forEach(function (field) { field.disabled = panel.hidden; });
        });
    }
    modes.forEach(function (input) { input.addEventListener('change', refresh); });
    options.forEach(function (input) { input.addEventListener('change', refresh); });
    refresh();
});
</script>
@endsection
