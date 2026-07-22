@php
    $selectedTemplateId = old('measurement_template_id', $selectedTemplateId ?? null);
    $systemInputNames = ['length' => 'length', 'arms' => 'arms', 'teraa' => 'teraa', 'senaChorai' => 'senaChorai', 'damanchorai' => 'damanchorai', 'shalwar' => 'shalwar', 'pancha' => 'pancha', 'shalwarGheer' => 'shalwarGheer', 'shoulder' => 'monda', 'chuta' => 'chuta'];
@endphp
<div class="measurement-template-picker mb-4" dir="rtl">
    @if($measurementTemplates->isNotEmpty())
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-2"><div><label for="measurement-template-select" class="font-weight-bold mb-1">لباس کا پیمائش ٹیمپلیٹ</label><p class="text-muted small mb-0">ٹیمپلیٹ منتخب کرنے سے صرف متعلقہ پیمائش خانے دکھائی دیں گے۔</p></div>@if(Auth::user()->hasBusinessPermission('tailoring.configuration'))<a href="{{ route('admin.measurement-templates.index') }}" class="btn btn-sm btn-outline-primary">ٹیمپلیٹس ترتیب دیں</a>@endif</div>
        <select id="measurement-template-select" class="form-control form-control-lg" name="measurement_template_id">
            <option value="" data-system='@json(array_keys(\App\Services\MeasurementService::SYSTEM_FIELDS))' data-custom='@json($measurementFields->pluck('id')->values())'>تمام پیمائش خانے</option>
            @foreach($measurementTemplates as $template)<option value="{{ $template->id }}" data-system='@json($template->system_fields ?? [])' data-custom='@json(array_map('intval', $template->custom_field_ids ?? []))' @selected((string) $selectedTemplateId === (string) $template->id)>{{ $template->name }}{{ $template->is_default ? ' — ڈیفالٹ' : '' }}</option>@endforeach
        </select>
    @else
        <div class="alert alert-info mb-0"><strong>تمام پیمائش خانے دکھائے جا رہے ہیں۔</strong> @if(Auth::user()->hasBusinessPermission('tailoring.configuration'))<a href="{{ route('admin.measurement-templates.index') }}">لباس کے حساب سے ٹیمپلیٹ بنائیں</a>@endif</div>
    @endif
</div>

@if($measurementTemplates->isNotEmpty())
<script>
document.addEventListener('DOMContentLoaded', function () {
    var select = document.getElementById('measurement-template-select');
    if (!select) return;
    var systemInputs = @json($systemInputNames);

    function toggleControl(input, visible) {
        if (!input) return;
        var container = input.closest('.form-group');
        if (!container) return;
        if (input.dataset.templateRequired === undefined) input.dataset.templateRequired = input.required ? '1' : '0';
        container.style.display = visible ? '' : 'none';
        input.required = visible && input.dataset.templateRequired === '1';
    }

    function applyTemplate() {
        var option = select.options[select.selectedIndex];
        var selectedSystem = JSON.parse(option.dataset.system || '[]');
        var selectedCustom = JSON.parse(option.dataset.custom || '[]').map(Number);
        Object.keys(systemInputs).forEach(function (key) {
            toggleControl(document.querySelector('[name="' + systemInputs[key] + '"]'), selectedSystem.indexOf(key) !== -1);
        });
        document.querySelectorAll('[name^="custom_measurements["]').forEach(function (input) {
            var match = input.name.match(/\[(\d+)\]/);
            toggleControl(input, match && selectedCustom.indexOf(Number(match[1])) !== -1);
        });
    }

    select.addEventListener('change', applyTemplate);
    applyTemplate();
});
</script>
@endif
