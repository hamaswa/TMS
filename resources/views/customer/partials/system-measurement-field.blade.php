{{-- Shared system measurement field for customer create/edit and counter-order editor.
     Context-specific input names are supplied by the caller; the visible control stays consistent. --}}
@php
    $fieldKey = $fieldKey ?? $name;
    $inputId = $inputId ?? 'measurement-'.$name;
    $inputName = $inputName ?? $name;
    $inputValue = old($inputName, $value ?? null);
    $unit = $unit ?? 'inch';
    $choices = collect($choices ?? []);
    $required = $required ?? false;
    $wrapperClass = $wrapperClass ?? 'form-group measurement-field';
@endphp
<div class="{{ $wrapperClass }}" data-measurement-field="system.{{ $fieldKey }}"
    data-template-field="system.{{ $fieldKey }}" data-default-column="{{ $unit === 'inch' ? 'right' : 'left' }}">
    <label for="{{ $inputId }}">{{ $label }}
        @if($unit === 'inch')<small class="text-muted">(انچ)</small>@endif
        @if($required)<span class="text-danger">*</span>@endif
    </label>
    @if($unit === 'inch')
        <div class="input-group">
            <input id="{{ $inputId }}" name="{{ $inputName }}" class="form-control"
                data-measure-key="system.{{ $fieldKey }}" type="number" step="0.01" min="0"
                value="{{ $inputValue }}" dir="ltr" @required($required)>
            <div class="input-group-append"><span class="input-group-text">انچ</span></div>
        </div>
    @elseif($choices->isNotEmpty())
        <select id="{{ $inputId }}" name="{{ $inputName }}" class="form-control"
            data-measure-key="system.{{ $fieldKey }}" @required($required)>
            <option value="">منتخب کریں</option>
            @foreach($choices as $choice)
                <option value="{{ $choice }}" @selected((string)$inputValue === (string)$choice)>{{ $choice }}</option>
            @endforeach
            @if(filled($inputValue) && !$choices->contains((string)$inputValue))
                <option value="{{ $inputValue }}" selected>{{ $inputValue }}</option>
            @endif
        </select>
    @else
        <input id="{{ $inputId }}" name="{{ $inputName }}" class="form-control"
            data-measure-key="system.{{ $fieldKey }}" type="text" maxlength="255"
            value="{{ $inputValue }}" @required($required)>
    @endif
    @error($inputName)<div class="text-danger small mt-1">{{ $message }}</div>@enderror
</div>
