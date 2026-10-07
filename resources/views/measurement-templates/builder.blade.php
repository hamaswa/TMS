@extends('main')
@section('content')
@php
    $editing = (bool) $template;
    $selectedSystem = old('system_fields', $template?->system_fields ?? []);
    $selectedCustom = array_map('intval', old('custom_field_ids', $template?->custom_field_ids ?? []));
    $selectedLayout = old('field_layout', $template?->field_layout ?? []);
    $selectedColumns = (int) old('layout_columns', $template?->layout_columns ?? 2);
    $fieldTypeLabels = ['number' => 'نمبر', 'text' => 'تحریر', 'select' => 'فہرست'];
    $fieldUnitLabels = ['inch' => 'انچ', 'cm' => 'سینٹی میٹر', 'none' => 'بغیر اکائی'];
@endphp
<section class="main-content template-page" dir="rtl">
    <div class="container-fluid px-3 px-lg-5 py-4">
        <div class="builder-head mb-4">
            <div>
                <a href="{{ route('admin.measurement-templates.index') }}" class="builder-back"><i class="fas fa-arrow-right ml-1"></i> تمام ٹیمپلیٹس</a>
                <h1 class="h3 font-weight-bold mt-2 mb-1">{{ $editing ? $template->name.' میں ترمیم' : 'نیا لباس ٹیمپلیٹ' }}</h1>
                <p class="text-muted mb-0">صرف وہ خانے منتخب کریں جو اس لباس کے گاہک فارم میں دکھانے ہیں۔</p>
            </div>
        </div>

        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger"><strong>براہ کرم معلومات درست کریں:</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <form method="POST" action="{{ $editing ? route('admin.measurement-templates.update', $template) : route('admin.measurement-templates.store') }}" id="templateBuilder">
            @csrf @if($editing) @method('PUT') @endif
            <div class="row">
                <div class="col-xl-8">
                    <div class="card template-card mb-4"><div class="card-body p-4">
                        <div class="builder-section-title"><span>1</span><div><h2>بنیادی معلومات</h2><p>نام سے واضح کریں کہ یہ فارم کس لباس کے لیے ہے۔</p></div></div>
                        <div class="row">
                            <div class="col-md-7 form-group"><label class="font-weight-bold">ٹیمپلیٹ کا نام</label><input class="form-control form-control-lg" name="name" value="{{ old('name', $template?->name) }}" placeholder="مثلاً واسکٹ" required @readonly($template?->is_builtin)></div>
                            <div class="col-md-5 form-group"><label class="font-weight-bold">مختصر وضاحت</label><input class="form-control form-control-lg" name="description" value="{{ old('description', $template?->description) }}" placeholder="یہ ٹیمپلیٹ کب استعمال ہوگا؟"></div>
                        </div>
                        @if($template?->is_builtin)<div class="template-default"><i class="fas fa-lock ml-1"></i><strong>بنیادی ڈیفالٹ ٹیمپلیٹ</strong><span>یہ ہمیشہ دستیاب رہے گا؛ اس کے بنیادی خانے بند نہیں کیے جا سکتے۔</span></div>@endif
                    </div></div>

                    <div class="card template-card mb-4"><div class="card-body p-4">
                        <div class="builder-section-title"><span>2</span><div><h2>جسمانی پیمائش</h2><p>صرف اس لباس کے لیے ضروری ناپ منتخب کریں۔</p></div></div>
                        <div class="field-options">@foreach($measurementFields as $key => $meta) @include('measurement-templates.partials.field-choice', ['kind' => 'system', 'value' => $key, 'label' => $meta['label'], 'hint' => $meta['unit'] === 'inch' ? 'انچ' : $meta['unit'], 'checked' => in_array($key, $selectedSystem, true), 'defaultColumn' => 'right', 'locked' => (bool)$template?->is_builtin]) @endforeach</div>
                    </div></div>

                    <div class="card template-card mb-4"><div class="card-body p-4">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2"><div class="builder-section-title"><span>3</span><div><h2>سلائی اور ڈیزائن کی پسند</h2><p>معیاری خانے مشترک ہیں، مگر ان کے انتخاب صرف اسی ٹیمپلیٹ کے ہیں۔</p></div></div>@if($editing)<a href="{{ route('admin.OptionType.index', ['template' => $template->id]) }}" class="btn btn-sm btn-outline-primary mb-3"><i class="fas fa-list ml-1"></i> اس ٹیمپلیٹ کے انتخاب</a>@endif</div>
                        <div class="field-options">@foreach($preferenceFields as $key => $meta) @include('measurement-templates.partials.field-choice', ['kind' => 'system', 'value' => $key, 'label' => $meta['label'], 'hint' => 'انتخابی فہرست', 'checked' => in_array($key, $selectedSystem, true), 'defaultColumn' => 'left', 'locked' => (bool)$template?->is_builtin]) @endforeach</div>
                    </div></div>

                    <div class="card template-card mb-4"><div class="card-body p-4">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2"><div class="builder-section-title"><span>4</span><div><h2>اس ٹیمپلیٹ کے خصوصی خانے</h2><p>یہ خانے صرف {{ $template?->name ?? 'اس لباس' }} میں استعمال ہوں گے۔</p></div></div>@if($editing)<button type="button" class="btn btn-sm btn-outline-primary mb-3" data-toggle="modal" data-target="#newTemplateField"><i class="fas fa-plus ml-1"></i> نیا خانہ</button>@endif</div>
                        @if(!$editing)
                            <div class="builder-empty">پہلے ٹیمپلیٹ محفوظ کریں، پھر اس لباس کے خصوصی خانے شامل کریں۔</div>
                        @elseif($customFields->isEmpty())
                            <div class="builder-empty">اس ٹیمپلیٹ کا کوئی خصوصی خانہ نہیں۔ ضرورت ہو تو “نیا خانہ” دبائیں۔</div>
                        @else
                            <div class="field-options">@foreach($customFields as $field)<div class="template-custom-field">@include('measurement-templates.partials.field-choice', ['kind' => 'custom', 'value' => $field->id, 'label' => $field->label, 'hint' => $field->field_type === 'select' ? 'فہرست' : ($field->unit === 'inch' ? 'انچ' : ($field->unit === 'cm' ? 'سینٹی میٹر' : 'اپنی قدر')), 'checked' => true, 'defaultColumn' => $field->field_type === 'number' ? 'right' : 'left', 'locked' => true])<button type="button" class="template-field-edit" data-toggle="modal" data-target="#editTemplateField{{ $field->id }}" aria-label="{{ $field->label }} تبدیل کریں"><i class="fas fa-pen"></i></button></div>@endforeach</div>
                        @endif
                    </div></div>
                </div>

                <div class="col-xl-4"><aside class="builder-preview card template-card"><div class="card-body p-4">
                    <span class="preview-label">گاہک فارم کی ترتیب</span><h2 class="h5 font-weight-bold mt-2">فارم کے کالم اور ترتیب</h2><p class="text-muted small">ایک یا دو کالم منتخب کریں۔ کارڈ پکڑ کر اوپر نیچے کریں، یا تیر سے دوسرے کالم میں بھیجیں۔</p>
                    <div class="column-mode" role="radiogroup" aria-label="فارم کے کالم"><label><input type="radio" name="layout_columns" value="1" @checked($selectedColumns === 1)><span><i class="fas fa-bars"></i> ایک کالم</span></label><label><input type="radio" name="layout_columns" value="2" @checked($selectedColumns !== 1)><span><i class="fas fa-columns"></i> دو کالم</span></label></div>
                    <div id="templatePreview" class="layout-preview"><div class="layout-lane" data-column="right"><strong class="lane-title">دائیں کالم</strong><div id="templatePreviewRight" class="preview-list" data-column="right"></div></div><div class="layout-lane" data-column="left"><strong class="lane-title">بائیں کالم</strong><div id="templatePreviewLeft" class="preview-list" data-column="left"></div></div></div><div id="templatePreviewEmpty" class="preview-empty">ابھی کوئی خانہ منتخب نہیں کیا گیا۔</div>
                    <button class="btn btn-primary btn-lg btn-block mt-4"><i class="fas fa-save ml-1"></i> {{ $editing ? 'تبدیلی محفوظ کریں' : 'ٹیمپلیٹ بنائیں' }}</button>
                </div></aside></div>
            </div>
        </form>

        @if($editing)
        <div class="modal fade template-field-modal" id="newTemplateField" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered" role="document"><div class="modal-content" dir="rtl">
            <div class="modal-header"><div><h2 class="h5 font-weight-bold mb-1">{{ $template->name }} کا نیا خانہ</h2><p class="text-muted small mb-0">یہ خانہ اور اس کے انتخاب صرف اسی ٹیمپلیٹ میں رہیں گے۔</p></div><button type="button" class="close ml-0 mr-auto" data-dismiss="modal" aria-label="بند کریں"><span>&times;</span></button></div>
            <form method="POST" action="{{ route('admin.measurement-fields.store') }}">@csrf<input type="hidden" name="measurement_template_id" value="{{ $template->id }}"><input type="hidden" name="is_active" value="1">@include('measurement-templates.partials.custom-field-form', ['field' => null])<div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">منسوخ</button><button class="btn btn-primary"><i class="fas fa-check ml-1"></i> خانہ شامل کریں</button></div></form>
        </div></div></div>
        @foreach($customFields as $field)
        <div class="modal fade template-field-modal" id="editTemplateField{{ $field->id }}" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered" role="document"><div class="modal-content" dir="rtl">
            <div class="modal-header"><h2 class="h5 font-weight-bold mb-0">{{ $field->label }}</h2><button type="button" class="close ml-0 mr-auto" data-dismiss="modal" aria-label="بند کریں"><span>&times;</span></button></div>
            <form method="POST" action="{{ route('admin.measurement-fields.update', $field) }}">@csrf @method('PUT')<input type="hidden" name="measurement_template_id" value="{{ $template->id }}"><input type="hidden" name="is_active" value="1">@include('measurement-templates.partials.custom-field-form', ['field' => $field])<div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">منسوخ</button><button class="btn btn-primary">تبدیلی محفوظ کریں</button></div></form>
        </div></div></div>
        @endforeach
        @endif

    </div>
</section>
@include('measurement-templates.partials.styles')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.jQuery) window.jQuery('.template-field-modal').appendTo(document.body);
    var builder = document.getElementById('templateBuilder'), preview = document.getElementById('templatePreview'), right = document.getElementById('templatePreviewRight'), left = document.getElementById('templatePreviewLeft'), empty = document.getElementById('templatePreviewEmpty');
    if (!builder || !preview || !right || !left) return;
    var layout = {};
    var dragged = null;
    @foreach($selectedLayout as $item)
        layout[@json($item['source'] ?? '')] = {column: @json($item['column'] ?? 'right'), order: {{ (int)($item['order'] ?? 0) }}};
    @endforeach

    function columnCount() {
        var selected = builder.querySelector('[name="layout_columns"]:checked');
        return selected ? Number(selected.value) : 2;
    }

    function syncLayout() {
        var index = 0;
        [right, left].forEach(function (list) {
            Array.from(list.children).forEach(function (item, position) {
                var column = list.dataset.column;
                layout[item.dataset.source] = {column: column, order: (position + 1) * 10};
                item.querySelector('[data-layout-column]').value = column;
                item.querySelector('[data-layout-order]').value = (position + 1) * 10;
                item.querySelectorAll('[data-layout-input]').forEach(function (input) {
                    input.name = 'field_layout[' + index + '][' + input.dataset.layoutInput + ']';
                });
                var arrow = item.querySelector('.move-column');
                arrow.hidden = columnCount() === 1;
                arrow.title = column === 'right' ? 'بائیں کالم میں بھیجیں' : 'دائیں کالم میں بھیجیں';
                arrow.setAttribute('aria-label', arrow.title);
                arrow.querySelector('i').className = column === 'right' ? 'fas fa-arrow-left' : 'fas fa-arrow-right';
                index++;
            });
        });
    }

    function makeItem(input) {
        var source = input.dataset.source;
        var item = document.createElement('div');
        item.className = 'preview-item'; item.dataset.source = source; item.draggable = true;
        var handle = document.createElement('button');
        handle.type = 'button'; handle.className = 'drag-handle'; handle.title = 'پکڑ کر ترتیب بدلیں'; handle.setAttribute('aria-label', handle.title); handle.innerHTML = '<i class="fas fa-grip-vertical"></i>';
        var label = document.createElement('span'); label.className = 'preview-item-label'; label.textContent = input.dataset.label || '';
        var move = document.createElement('button'); move.type = 'button'; move.className = 'move-column'; move.innerHTML = '<i class="fas fa-arrow-left"></i>';
        var sourceInput = document.createElement('input'); sourceInput.type = 'hidden'; sourceInput.dataset.layoutInput = 'source'; sourceInput.dataset.layoutSource = ''; sourceInput.value = source;
        var columnInput = document.createElement('input'); columnInput.type = 'hidden'; columnInput.dataset.layoutInput = 'column'; columnInput.dataset.layoutColumn = '';
        var orderInput = document.createElement('input'); orderInput.type = 'hidden'; orderInput.dataset.layoutInput = 'order'; orderInput.dataset.layoutOrder = '';
        item.appendChild(handle); item.appendChild(label); item.appendChild(move); item.appendChild(sourceInput); item.appendChild(columnInput); item.appendChild(orderInput);
        item.addEventListener('dragstart', function () { dragged = item; item.classList.add('is-dragging'); });
        item.addEventListener('dragend', function () { item.classList.remove('is-dragging'); dragged = null; syncLayout(); });
        move.addEventListener('click', function () { (item.parentElement === right ? left : right).appendChild(item); syncLayout(); });
        return item;
    }

    function renderPreview() {
        var chosen = Array.from(builder.querySelectorAll('.field-choice input:checked'));
        right.innerHTML = ''; left.innerHTML = '';
        chosen.sort(function (a, b) {
            var aLayout = layout[a.dataset.source], bLayout = layout[b.dataset.source];
            return (aLayout ? aLayout.order : 9999) - (bLayout ? bLayout.order : 9999);
        });
        chosen.forEach(function (input, index) {
            var source = input.dataset.source;
            if (!layout[source]) layout[source] = {column: input.dataset.defaultColumn || 'right', order: (index + 1) * 10};
            (layout[source].column === 'left' ? left : right).appendChild(makeItem(input));
        });
        if (columnCount() === 1) Array.from(left.children).forEach(function (item) { right.appendChild(item); });
        preview.classList.toggle('is-one-column', columnCount() === 1);
        syncLayout();
        empty.hidden = chosen.length > 0;
    }

    [right, left].forEach(function (list) {
        list.addEventListener('dragover', function (event) {
            event.preventDefault();
            if (!dragged || (columnCount() === 1 && list === left)) return;
            var after = Array.from(list.querySelectorAll('.preview-item:not(.is-dragging)')).find(function (item) {
                return event.clientY < item.getBoundingClientRect().top + item.offsetHeight / 2;
            });
            list.insertBefore(dragged, after || null);
        });
        list.addEventListener('drop', function (event) { event.preventDefault(); syncLayout(); });
    });
    builder.addEventListener('change', function (event) {
        if (event.target.name === 'layout_columns') {
            if (columnCount() === 1) Array.from(left.children).forEach(function (item) { right.appendChild(item); });
            preview.classList.toggle('is-one-column', columnCount() === 1);
            syncLayout();
            return;
        }
        if (event.target.closest('.field-choice')) renderPreview();
    });
    renderPreview();

    document.querySelectorAll('.template-field-modal').forEach(function (modal) {
        var type = modal.querySelector('.template-field-type');
        var unitWrap = modal.querySelector('.template-field-unit');
        var unit = unitWrap ? unitWrap.querySelector('select') : null;
        var options = modal.querySelector('.template-field-options');
        if (!type || !options) return;
        var stored = options.querySelector('.template-field-options-value');
        var draft = options.querySelector('.template-option-draft');
        var add = options.querySelector('.template-option-add');
        var list = options.querySelector('.template-option-list');
        var values = (stored.value || '').split(/[\r\n,،]+/).map(function (value) { return value.trim(); }).filter(Boolean);

        function syncOptions() {
            values = values.filter(function (value, index, all) { return all.indexOf(value) === index; });
            stored.value = values.join('، ');
            list.innerHTML = '';
            values.forEach(function (value, index) {
                var chip = document.createElement('span');
                chip.className = 'template-option-chip';
                var text = document.createElement('span');
                text.textContent = value;
                var remove = document.createElement('button');
                remove.type = 'button';
                remove.setAttribute('aria-label', value + ' حذف کریں');
                remove.innerHTML = '<i class="fas fa-times"></i>';
                remove.addEventListener('click', function () { values.splice(index, 1); syncOptions(); });
                chip.appendChild(text); chip.appendChild(remove); list.appendChild(chip);
            });
        }

        function addDraft() {
            var additions = (draft.value || '').split(/[\r\n,،]+/).map(function (value) { return value.trim(); }).filter(Boolean);
            if (!additions.length) {
                draft.setCustomValidity('پہلے ایک انتخاب لکھیں۔');
                draft.reportValidity();
                return;
            }
            draft.setCustomValidity('');
            additions.forEach(function (value) { if (values.indexOf(value) === -1) values.push(value); });
            draft.value = '';
            syncOptions();
            draft.focus();
        }

        function toggleOptions() {
            options.hidden = type.value !== 'select';
            if (unitWrap) unitWrap.hidden = type.value !== 'number';
            if (unit && type.value !== 'number') unit.value = 'none';
            draft.setCustomValidity('');
        }
        add.addEventListener('click', addDraft);
        draft.addEventListener('input', function () { draft.setCustomValidity(''); });
        draft.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') { event.preventDefault(); addDraft(); }
        });
        modal.querySelector('form').addEventListener('submit', function (event) {
            if (type.value === 'select' && !values.length) {
                event.preventDefault();
                draft.setCustomValidity('کم از کم ایک انتخاب شامل کریں۔');
                draft.reportValidity();
            }
        });
        type.addEventListener('change', toggleOptions);
        syncOptions();
        toggleOptions();
    });
});
</script>
@endsection
