@php
    $measurementValues = old('measurement_methods', $service?->availableMeasurementMethods() ?? [
        \App\Models\StorefrontTailoringService::MEASUREMENT_SHOP_VISIT,
        \App\Models\StorefrontTailoringService::MEASUREMENT_EXISTING_PROFILE,
    ]);
    $depositType = old('deposit_type', $service?->deposit_type ?? \App\Models\StorefrontTailoringService::DEPOSIT_NONE);
@endphp
<div data-service-form="{{ $formKey }}">
    <input type="hidden" name="name" value="{{ old('name', $service?->name ?: 'ٹیلرنگ خدمت') }}">
    <input type="hidden" name="description" value="{{ old('description', $service?->description) }}">
    <div class="form-row">
        <div class="form-group col-md-6"><label>خدمت کا نام — اردو</label><input name="name_ur" dir="rtl" maxlength="180" class="form-control" value="{{ old('name_ur', $service?->name_ur) }}"></div>
        <div class="form-group col-md-6"><label>Service name — English</label><input name="name_en" dir="ltr" maxlength="180" class="form-control text-left" value="{{ old('name_en', $service?->name_en) }}"></div>
        <div class="form-group col-md-4"><label>فہرست میں ترتیب</label><input type="number" name="sort_order" min="0" max="9999" class="form-control" value="{{ old('sort_order', $service?->sort_order ?? 0) }}"></div>
    </div>
    <div class="form-row"><div class="form-group col-md-6"><label>تفصیل — اردو</label><textarea name="description_ur" dir="rtl" maxlength="2000" rows="3" class="form-control">{{ old('description_ur', $service?->description_ur) }}</textarea></div><div class="form-group col-md-6"><label>Description — English</label><textarea name="description_en" dir="ltr" maxlength="2000" rows="3" class="form-control text-left">{{ old('description_en', $service?->description_en) }}</textarea></div></div>
    <div class="form-row">
        <div class="form-group col-md-4"><label>ابتدائی قیمت</label><input type="number" name="price_from" min="0" step="0.01" class="form-control" value="{{ old('price_from', $service?->price_from) }}"></div>
        <div class="form-group col-md-4"><label>قیمت کی اکائی</label><select name="price_unit" class="form-control">@foreach(['فی سوٹ','فی لباس','فی کام'] as $unit)<option @selected(old('price_unit', $service?->price_unit ?? 'فی سوٹ') === $unit)>{{ $unit }}</option>@endforeach</select></div>
        <div class="form-group col-md-4"><label>تخمینی دن</label><input type="number" name="estimated_days" min="1" max="365" class="form-control" value="{{ old('estimated_days', $service?->estimated_days) }}"></div>
    </div>

    <div class="control-panel mb-3">
        <h3 class="h6 mb-3">دستیابی اور درخواستیں</h3>
        <div class="row">
            <div class="col-md-6 mb-2"><label class="choice-tile"><input type="checkbox" name="is_available" value="1" @checked(old('is_available', $service?->is_available ?? true))><span><strong>خدمت دستیاب ہے</strong><small>بند کرنے پر خدمت نظر آئے گی مگر عارضی طور پر بند دکھائی جائے گی۔</small></span></label></div>
            <div class="col-md-6 mb-2"><label class="choice-tile"><input type="checkbox" name="accepts_inquiries" value="1" @checked(old('accepts_inquiries', $service?->accepts_inquiries ?? true))><span><strong>نئی درخواست قبول کریں</strong><small>یہ صرف اس خدمت کی نئی عوامی درخواستوں کو کنٹرول کرتا ہے۔</small></span></label></div>
        </div>
    </div>

    <div class="control-panel mb-3">
        <h3 class="h6 mb-3">پیمائش کے دستیاب طریقے</h3>
        <div class="row">@foreach($measurementMethods as $value => $label)<div class="col-md-4 mb-2"><label class="choice-tile"><input type="checkbox" name="measurement_methods[]" value="{{ $value }}" @checked(in_array($value, $measurementValues, true))><span><strong>{{ $label }}</strong></span></label></div>@endforeach</div>
        <div class="form-group mt-3 mb-0">
            <label for="measurement-template-{{ $formKey }}">اس خدمت کا پیمائش ٹیمپلیٹ</label>
            <select id="measurement-template-{{ $formKey }}" name="measurement_template_id" class="form-control">
                <option value="">بکنگ منظور کرتے وقت منتخب کریں</option>
                @foreach($measurementTemplates as $template)
                    <option value="{{ $template->id }}" @selected((string) old('measurement_template_id', $service?->measurement_template_id) === (string) $template->id)>{{ $template->name }}{{ $template->is_default ? ' — ڈیفالٹ' : '' }}</option>
                @endforeach
            </select>
            <small class="form-text text-muted">آن لائن آرڈر میں اپنی پیمائش کے خانے اور اسی ٹیمپلیٹ کی معیاری پیمائشیں استعمال ہوں گی۔</small>
        </div>
        <div class="alert alert-light border mt-3 mb-0">Small، Medium A یا کسی بھی نام کی مکمل معیاری پیمائش <a href="{{ route('admin.measurement-templates.index') }}">پیمائش ٹیمپلیٹس</a> میں محفوظ کریں۔ یہاں منتخب ٹیمپلیٹ کی فعال پیمائشیں خودکار طور پر آن لائن نظر آئیں گی۔</div>
    </div>

    <div class="control-panel mb-3">
        <h3 class="h6 mb-3">پیشگی رقم اور بکنگ گنجائش</h3>
        <div class="form-row">
            <div class="form-group col-md-4"><label>پیشگی رقم کی پالیسی</label><select name="deposit_type" class="form-control" data-deposit-type>@foreach($depositTypes as $value => $label)<option value="{{ $value }}" @selected($depositType === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="form-group col-md-4" data-deposit-value-wrap @hidden($depositType === 'none')><label data-deposit-value-label>{{ $depositType === 'percentage' ? 'پیشگی فیصد' : 'پیشگی رقم' }}</label><input type="number" name="deposit_value" min="0" step="0.01" class="form-control" value="{{ old('deposit_value', $service?->deposit_value) }}"></div>
            <div class="form-group col-md-4"><label>فی ہفتہ زیادہ سے زیادہ بکنگ</label><input type="number" name="weekly_booking_limit" min="1" max="999" class="form-control" value="{{ old('weekly_booking_limit', $service?->weekly_booking_limit) }}"><small class="text-muted">خالی چھوڑنے پر کوئی حد نہیں۔</small></div>
        </div>
    </div>

    <div class="d-flex flex-wrap mb-3">
        <label class="ml-4"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $service?->is_featured ?? false))> نمایاں خدمت</label>
        <label><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $service?->is_published ?? false))> عوام کو دکھائیں</label>
    </div>
</div>
