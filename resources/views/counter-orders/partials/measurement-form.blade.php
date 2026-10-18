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
                            @include('customer.partials.system-measurement-field', [
                                'name' => $row['key'],
                                'fieldKey' => $row['key'],
                                'inputId' => 'co-measurement-system-'.$row['key'],
                                'inputName' => 'system_measurements['.$row['key'].']',
                                'label' => $row['label'],
                                'unit' => $row['unit'],
                                'value' => $row['value'],
                                'choices' => $row['choices'],
                                'required' => true,
                                'wrapperClass' => 'co-measurement-field form-group',
                            ])
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
