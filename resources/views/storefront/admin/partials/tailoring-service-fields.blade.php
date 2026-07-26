@php
    $measurementValues = old('measurement_methods', $service?->availableMeasurementMethods() ?? [
        \App\Models\StorefrontTailoringService::MEASUREMENT_SHOP_VISIT,
        \App\Models\StorefrontTailoringService::MEASUREMENT_EXISTING_PROFILE,
    ]);
    $depositType = old('deposit_type', $service?->deposit_type ?? \App\Models\StorefrontTailoringService::DEPOSIT_NONE);
@endphp
<div data-service-form="{{ $formKey }}">
    <div class="form-row">
        <div class="form-group col-md-8"><label>خدمت کا نام</label><input name="name" required maxlength="180" class="form-control" placeholder="مثلاً مردانہ شلوار قمیض سلائی" value="{{ old('name', $service?->name) }}"></div>
        <div class="form-group col-md-4"><label>فہرست میں ترتیب</label><input type="number" name="sort_order" min="0" max="9999" class="form-control" value="{{ old('sort_order', $service?->sort_order ?? 0) }}"></div>
    </div>
    <div class="form-group"><label>تفصیل</label><textarea name="description" maxlength="2000" rows="3" class="form-control" placeholder="شامل کام، کپڑے کی ضرورت اور اہم شرائط واضح کریں۔">{{ old('description', $service?->description) }}</textarea></div>
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
