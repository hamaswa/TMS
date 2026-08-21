<div class="row">
    <div class="col-12 mb-3">
        <div class="field-group">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div><h3 class="h6 font-weight-bold mb-1">پیمائش کے خانے</h3><small class="text-muted">سسٹم اور اضافی پیمائش ایک ہی فہرست میں</small></div>
                <span class="badge badge-light">{{ count($systemFields) + $customFields->count() }}</span>
            </div>
            <div class="field-options">
                @foreach($systemFields as $key => $meta)<label class="field-choice" for="{{ $prefix }}-system-{{ $key }}"><input id="{{ $prefix }}-system-{{ $key }}" type="checkbox" name="system_fields[]" value="{{ $key }}" @checked(in_array($key, $selectedSystem, true))><span><strong>{{ $meta['label'] }}</strong><small>{{ $meta['unit'] === 'inch' ? 'انچ' : $meta['unit'] }}</small></span></label>@endforeach
                @foreach($customFields as $field)<label class="field-choice" for="{{ $prefix }}-custom-{{ $field->id }}"><input id="{{ $prefix }}-custom-{{ $field->id }}" type="checkbox" name="custom_field_ids[]" value="{{ $field->id }}" @checked(in_array($field->id, array_map('intval', $selectedCustom), true))><span><strong>{{ $field->label }}</strong><small>{{ $field->unit === 'inch' ? 'انچ' : ($field->unit === 'cm' ? 'سینٹی میٹر' : 'بغیر اکائی') }}</small></span></label>@endforeach
            </div>
        </div>
    </div>
</div>
