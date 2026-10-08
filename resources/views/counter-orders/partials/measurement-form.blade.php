@php
    $columns = ['right' => collect(), 'left' => collect()];
    foreach ($template->system_fields ?? [] as $index => $key) {
        $meta = \App\Services\MeasurementService::SYSTEM_FIELDS[$key] ?? null;
        if (! $meta) continue;
        $layoutItem = $layout->get('system.'.$key, []);
        $column = data_get($layoutItem, 'column', $meta['unit'] === 'inch' ? 'right' : 'left');
        $column = array_key_exists($column, $columns) ? $column : 'right';
        $columns[$column]->push([
            'kind' => 'system',
            'key' => $key,
            'label' => $meta['label'],
            'unit' => $meta['unit'],
            'value' => $systemValues->get($key),
            'choices' => $preferenceChoices->get($key, collect()),
            'order' => (int) data_get($layoutItem, 'order', $index),
        ]);
    }
    foreach ($fields as $index => $field) {
        $layoutItem = $layout->get('custom.'.$field->id, []);
        $column = data_get($layoutItem, 'column', $field->field_type === 'number' ? 'right' : 'left');
        $column = array_key_exists($column, $columns) ? $column : 'right';
        $columns[$column]->push([
            'kind' => 'custom',
            'field' => $field,
            'label' => $field->label,
            'value' => $customValues->get($field->id),
            'order' => (int) data_get($layoutItem, 'order', 1000 + $index),
        ]);
    }
    $columns = collect($columns)->map(fn ($rows) => $rows->sortBy('order')->values());
@endphp

<form id="co-measurement-editor-form" method="POST"
    action="{{ route('admin.counter-orders.measurements.update', [$counterOrder, $profile]) }}">
    @csrf
    @method('PATCH')
    <input type="hidden" name="measurement_template_id" value="{{ $template->id }}">
    <div id="co-measurement-errors" class="alert alert-danger" hidden></div>
    <div class="co-measurement-context">
        <div><span>ناپ</span><strong>{{ $profile->name }}</strong></div>
        <div><span>لباس</span><strong>{{ $template->name }}</strong></div>
    </div>
    <div class="co-measurement-columns">
        @foreach(['right', 'left'] as $column)
            <section class="co-measurement-column" data-measurement-column="{{ $column }}">
                <div class="co-measurement-stack">
                    @forelse($columns->get($column) as $row)
                        @if($row['kind'] === 'system')
                            @php($inputId = 'co-measurement-system-'.$row['key'])
                            <div class="co-measurement-field">
                                <label for="{{ $inputId }}">{{ $row['label'] }} @if($row['unit'] === 'inch')<small>(انچ)</small>@endif</label>
                                @if($row['unit'] === 'inch')
                                    <input id="{{ $inputId }}" class="form-control" type="number" step="0.01" min="0"
                                        name="system_measurements[{{ $row['key'] }}]" value="{{ $row['value'] }}" required dir="ltr">
                                @elseif($row['choices']->isNotEmpty())
                                    <select id="{{ $inputId }}" class="form-control" name="system_measurements[{{ $row['key'] }}]" required>
                                        <option value="">{{ $row['label'] }} منتخب کریں</option>
                                        @foreach($row['choices'] as $choice)
                                            <option value="{{ $choice }}" @selected((string) $row['value'] === (string) $choice)>{{ $choice }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input id="{{ $inputId }}" class="form-control" type="text" maxlength="255"
                                        name="system_measurements[{{ $row['key'] }}]" value="{{ $row['value'] }}" required>
                                @endif
                            </div>
                        @else
                            @php($field = $row['field'])
                            <div class="co-measurement-field">
                                <label for="co-measurement-custom-{{ $field->id }}">{{ $field->label }} @if($field->unit && $field->unit !== 'none')<small>({{ $field->unit === 'inch' ? 'انچ' : 'سینٹی میٹر' }})</small>@endif</label>
                                @if($field->field_type === 'select')
                                    <select id="co-measurement-custom-{{ $field->id }}" class="form-control"
                                        name="custom_measurements[{{ $field->id }}]" @required($field->is_required)>
                                        <option value="">منتخب کریں</option>
                                        @foreach($field->options ?? [] as $option)
                                            <option value="{{ $option }}" @selected((string) $row['value'] === (string) $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input id="co-measurement-custom-{{ $field->id }}" class="form-control"
                                        name="custom_measurements[{{ $field->id }}]" value="{{ $row['value'] }}"
                                        type="{{ $field->field_type === 'number' ? 'number' : 'text' }}"
                                        @if($field->field_type === 'number') step="0.01" min="0" dir="ltr" @endif
                                        @required($field->is_required)>
                                @endif
                            </div>
                        @endif
                    @empty
                        <div class="co-measurement-empty">اس طرف کوئی خانہ نہیں۔</div>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>
    <div class="modal-footer px-0 pb-0">
        <button class="co-btn" type="button" data-dismiss="modal">منسوخ</button>
        <button class="co-btn is-tailor" type="submit">پیمائش محفوظ کریں</button>
    </div>
</form>
