@php
    $source = $item['source'];
    $savedMeasurement = $savedMeasurementValues->get($source);
@endphp

@if($item['type'] === 'system')
    @php
        $key = $item['key'];
        $meta = \App\Services\MeasurementService::SYSTEM_FIELDS[$key];
        $isPreference = $preferenceKeys->contains($key);
        $value = trim((string) old('system_measurements.'.$key, $savedMeasurement?->value ?? data_get($measurementCustomer, $key)));
    @endphp
    <div class="form-group">
        <label for="order-system-{{ $key }}">
            {{ $meta['label'] }}
            @if(!$useLatestMeasurements && !$savedMeasurement)<span class="badge badge-info mr-1">نیا خانہ</span>@endif
            @unless($isPreference)<small class="text-muted">(انچ)</small>@endunless
        </label>
        @if($isPreference)
            @php
                $options = $preferenceOptions->get($key, collect());
                $hasSavedOption = $options->contains(fn($option) => trim((string) $option->Name) === $value);
            @endphp
            <select id="order-system-{{ $key }}" class="form-control" name="system_measurements[{{ $key }}]">
                <option value="">{{ $meta['label'] }} منتخب کریں</option>
                @if($value !== '' && !$hasSavedOption)
                    <option value="{{ $value }}" selected>{{ $value }}</option>
                @endif
                @foreach($options as $option)
                    @php $optionName = trim((string) $option->Name); @endphp
                    <option value="{{ $optionName }}" @selected($optionName === $value)>{{ $optionName }}</option>
                @endforeach
            </select>
        @else
            <input id="order-system-{{ $key }}" class="form-control" name="system_measurements[{{ $key }}]"
                value="{{ $value }}" type="number" step="0.01" min="0">
        @endif
    </div>
@else
    @php
        $field = $item['field'];
        $value = old('custom_measurements.'.$field->id, $savedMeasurement?->value ?? $customerCustomValues->get($field->id));
    @endphp
    <div class="form-group">
        <label for="order-custom-{{ $field->id }}">
            {{ $field->label }}
            @if(!$useLatestMeasurements && !$savedMeasurement)<span class="badge badge-info mr-1">نیا خانہ</span>@endif
            @if($field->unit && $field->unit !== 'none')<small class="text-muted">({{ $field->unit === 'inch' ? 'انچ' : 'سینٹی میٹر' }})</small>@endif
        </label>
        @if($field->field_type === 'select')
            <select id="order-custom-{{ $field->id }}" class="form-control" name="custom_measurements[{{ $field->id }}]">
                <option value="">منتخب کریں</option>
                @foreach($field->options ?? [] as $option)
                    <option value="{{ $option }}" @selected((string) $value === (string) $option)>{{ $option }}</option>
                @endforeach
            </select>
        @else
            <input id="order-custom-{{ $field->id }}" class="form-control" name="custom_measurements[{{ $field->id }}]"
                value="{{ $value }}" type="{{ $field->field_type === 'number' ? 'number' : 'text' }}"
                @if($field->field_type === 'number') step="0.01" min="0" @endif>
        @endif
        @error('custom_measurements.'.$field->id)<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
@endif