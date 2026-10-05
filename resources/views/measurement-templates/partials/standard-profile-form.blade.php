@php
    $profileValues = collect($profile?->measurement_values ?? [])->keyBy('source_key');
    $templateCustomFields = $customFields->whereIn('id', array_map('intval', $template->custom_field_ids ?? []));
    $formPrefix = $profile ? 'profile-'.$profile->id : 'new-profile-'.$template->id;
@endphp
<div class="standard-profile-fields">
    <div class="row">
        <div class="col-md-8 form-group">
            <label class="font-weight-bold" for="{{ $formPrefix }}-name">معیاری پیمائش کا نام</label>
            <input id="{{ $formPrefix }}-name" class="form-control" name="name" maxlength="100" value="{{ old('name', $profile?->name) }}" placeholder="مثلاً Small A، Medium B یا 15 سال" required>
            <small class="text-muted">آپ اپنی ضرورت کے مطابق کوئی بھی نام رکھ سکتے ہیں۔</small>
        </div>
        <div class="col-md-4 form-group">
            <label for="{{ $formPrefix }}-sort">ترتیب</label>
            <input id="{{ $formPrefix }}-sort" type="number" min="0" max="9999" class="form-control" name="sort_order" value="{{ old('sort_order', $profile?->sort_order ?? 0) }}">
        </div>
    </div>
    <div class="profile-measurement-grid">
        @foreach($template->system_fields ?? [] as $key)
            @if(isset($systemFields[$key]))
                @php($meta = $systemFields[$key])
                <div class="form-group mb-0">
                    <label for="{{ $formPrefix }}-system-{{ $key }}">{{ $meta['label'] }} @if($meta['unit'])<small>({{ $meta['unit'] }})</small>@endif</label>
                    <input id="{{ $formPrefix }}-system-{{ $key }}" type="number" min="0" step="0.01" class="form-control" name="values[system][{{ $key }}]" value="{{ old('values.system.'.$key, $profileValues->get('system.'.$key)['value'] ?? '') }}" required>
                </div>
            @endif
        @endforeach
        @foreach($templateCustomFields as $field)
            <div class="form-group mb-0">
                <label for="{{ $formPrefix }}-custom-{{ $field->id }}">{{ $field->label }} @if($field->unit !== 'none')<small>({{ $field->unit }})</small>@endif</label>
                @if($field->field_type === 'select')
                    <select id="{{ $formPrefix }}-custom-{{ $field->id }}" class="form-control" name="values[custom][{{ $field->id }}]" @required($field->is_required)>
                        <option value="">منتخب کریں</option>
                        @foreach($field->options ?? [] as $option)<option value="{{ $option }}" @selected(old('values.custom.'.$field->id, $profileValues->get('custom.'.$field->id)['value'] ?? '') === $option)>{{ $option }}</option>@endforeach
                    </select>
                @else
                    <input id="{{ $formPrefix }}-custom-{{ $field->id }}" class="form-control" name="values[custom][{{ $field->id }}]" value="{{ old('values.custom.'.$field->id, $profileValues->get('custom.'.$field->id)['value'] ?? '') }}" type="{{ $field->field_type === 'number' ? 'number' : 'text' }}" @if($field->field_type === 'number') min="0" step="0.01" @endif @required($field->is_required)>
                @endif
            </div>
        @endforeach
    </div>
</div>
