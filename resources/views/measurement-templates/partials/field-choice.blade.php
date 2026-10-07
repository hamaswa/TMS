@php($inputName = $kind === 'custom' ? 'custom_field_ids[]' : 'system_fields[]')
@php($source = $kind === 'custom' ? 'custom.'.$value : 'system.'.$value)
<label class="field-choice">
    <input type="checkbox" name="{{ $inputName }}" value="{{ $value }}" data-source="{{ $source }}" data-label="{{ $label }}" data-default-column="{{ $defaultColumn ?? 'right' }}" @checked($checked) @disabled($locked ?? false)>
    @if($locked ?? false)<input type="hidden" name="{{ $inputName }}" value="{{ $value }}">@endif
    <span class="field-choice-icon"><i class="fas {{ $kind === 'custom' ? 'fa-plus' : 'fa-check' }}"></i></span>
    <span><strong>{{ $label }}</strong><small>{{ $hint }}</small></span>
</label>
