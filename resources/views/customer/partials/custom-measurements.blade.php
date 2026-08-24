@if($measurementFields->isNotEmpty())
@php($embedded = $embedded ?? false)
@unless($embedded)
<div class="card border-primary mb-4" dir="rtl">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <div>
            <strong>اضافی پیمائش</strong>
        </div>
        @if(Auth::user()->hasBusinessPermission('tailoring.configuration'))
            <a href="{{ route('admin.measurement-fields.index') }}" class="btn btn-sm btn-outline-primary">خانے ترتیب دیں</a>
        @endif
    </div>
    <div class="card-body"><div class="form-row">
@endunless
        @foreach($measurementFields as $field)
            @php($value = old('custom_measurements.'.$field->id, $measurementValues->get($field->id)))
            <div class="form-group {{ $embedded ? 'edit-field mb-0' : 'col-md-6' }}" data-measurement-field="custom.{{ $field->id }}">
                <label for="custom_measurement_{{ $field->id }}">{{ $field->label }} @if($field->unit && $field->unit !== 'none')<small class="text-muted">({{ $field->unit === 'inch' ? 'انچ' : 'سینٹی میٹر' }})</small>@endif @if($field->is_required)<span class="text-danger">*</span>@endif</label>
                @if($field->field_type === 'select')
                    <select class="form-control" id="custom_measurement_{{ $field->id }}" name="custom_measurements[{{ $field->id }}]" @required($field->is_required)>
                        <option value="">منتخب کریں</option>
                        @foreach($field->options ?? [] as $option)<option value="{{ $option }}" @selected((string) $value === (string) $option)>{{ $option }}</option>@endforeach
                    </select>
                @else
                    <input class="form-control{{ $field->field_type === 'number' ? ' js-no-wheel-number' : '' }}" id="custom_measurement_{{ $field->id }}" name="custom_measurements[{{ $field->id }}]" value="{{ $value }}" type="{{ $field->field_type === 'number' ? 'number' : 'text' }}" @if($field->field_type === 'number') step="0.01" min="0" @endif @required($field->is_required)>
                @endif
                @error('custom_measurements.'.$field->id)<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
        @endforeach
@unless($embedded)
    </div></div>
</div>
@endunless
@endif
