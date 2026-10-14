
@php
    $selectedTemplateId = old(
        'measurement_template_id',
        $selectedTemplateId ?? null
    );
@endphp

<div class="measurement-template-picker mb-4" dir="rtl">
    @if ($measurementTemplates->isNotEmpty())
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
            <div>
                <label for="order-measurement-template-select" class="font-weight-bold mb-1">
                    لباس کا پیمائش ٹیمپلیٹ
                </label>
                <p class="text-muted small mb-0">
                    ٹیمپلیٹ منتخب کرنے سے صرف متعلقہ پیمائش خانے دکھائی دیں گے۔
                </p>
            </div>

            @if (Auth::user()->hasBusinessPermission('tailoring.configuration'))
                <a href="{{ route('admin.measurement-templates.index') }}"
                   class="btn btn-sm btn-outline-primary">
                    ٹیمپلیٹس ترتیب دیں
                </a>
            @endif
        </div>

        <select
            id="order-measurement-template-select"
            class="form-control form-control-lg"
            name="measurement_template_id"
        >
            <option
                value=""
                data-system='@json(array_keys(\App\Services\MeasurementService::SYSTEM_FIELDS))'
                data-custom='@json($allMeasurementFields->pluck('id')->values())'
                data-layout="[]"
                data-preferences="{}"
                data-columns="2"
                @selected(!$selectedTemplateId)
            >
                تمام پیمائش خانے
            </option>

            @foreach ($measurementTemplates as $template)
                @php
                    $templateChoices = $template->templateOptions
                        ->groupBy(fn ($option) => (
                            $option->optionType?->type === 'daaman'
                                ? 'Daaman'
                                : $option->optionType?->type
                        ))
                        ->map(fn ($options) => $options->pluck('Name')->values())
                        ->filter(fn ($options, $key) => filled($key));
                @endphp

                <option
                    value="{{ $template->id }}"
                    data-system='@json($template->system_fields ?? [])'
                    data-custom='@json(array_map("intval", $template->custom_field_ids ?? []))'
                    data-layout='@json($template->field_layout ?? [])'
                    data-preferences='@json($templateChoices)'
                    data-columns="{{ $template->layout_columns ?? 2 }}"
                    @selected((string) $selectedTemplateId === (string) $template->id)
                >
                    {{ $template->name }}{{ $template->is_default ? ' — بنیادی ڈیفالٹ' : '' }}
                </option>
            @endforeach
        </select>
    @else
        <div class="alert alert-info mb-0">
            <strong>تمام پیمائش خانے دکھائے جا رہے ہیں۔</strong>

            @if (Auth::user()->hasBusinessPermission('tailoring.configuration'))
                <a href="{{ route('admin.measurement-templates.index') }}">
                    لباس کے حساب سے ٹیمپلیٹ بنائیں
                </a>
            @endif
        </div>
    @endif
</div>
