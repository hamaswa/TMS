@php
    $field = $field ?? null;
    $fieldType = $field?->field_type ?? 'number';
    $fieldUnit = $field?->unit ?? ($fieldType === 'number' ? 'inch' : 'none');
    $fieldOptions = $field?->options ?? [];
@endphp
<div class="modal-body">
    <div class="row">

        <div class="col-md-6 form-group">
            <label>خانے کا نام</label>
            <div class="mf-urdu-wrap position-relative">
                <input type="text" class="form-control mf-urdu-input" name="label" value="{{ $field?->label }}"
                    required maxlength="100" placeholder="مثلاً لیپل چوڑائی" autocomplete="off" inputmode="none"
                    lang="ur" dir="rtl" aria-haspopup="true" aria-expanded="false">

                <div class="mf-urdu-keyboard" role="group" aria-label="اردو کی بورڈ">
                    <div class="mf-urdu-keyboard-head">
                        <span>
                            <i class="fas fa-keyboard ml-1 text-primary"></i>
                            اردو کی بورڈ
                        </span>
                        <button type="button" class="mf-urdu-close" aria-label="کی بورڈ بند کریں">
                            &times;
                        </button>
                    </div>

                    <div class="mf-urdu-keys" aria-label="اردو حروف"></div>

                    <div class="mf-urdu-actions">
                        <button type="button" class="mf-urdu-action" data-action="backspace">مٹائیں</button>
                        <button type="button" class="mf-urdu-action is-space" data-action="space">خالی جگہ</button>
                        <button type="button" class="mf-urdu-action" data-action="clear">سب صاف</button>
                    </div>
                </div>
            </div>
            <small class="text-muted">نام کے خانے پر کلک کریں اور اردو کی بورڈ سے لکھیں۔</small>
        </div>
        <div class="col-md-3 form-group">
            <label>قسم</label>
            <select class="form-control template-field-type" name="field_type">
                @foreach ($fieldTypeLabels as $value => $label)
                    <option value="{{ $value }}" @selected($fieldType === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 form-group template-field-unit">
            <label>اکائی</label>
            <select class="form-control" name="unit">
                @foreach ($fieldUnitLabels as $value => $label)
                    <option value="{{ $value }}" @selected($fieldUnit === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 form-group template-field-options" data-option-editor
            @if ($fieldType !== 'select') hidden @endif>
            <label>اس خانے کے انتخاب</label>
            <input type="hidden" name="options_text" class="template-field-options-value"
                value="{{ implode('، ', $fieldOptions) }}">
            <div class="template-option-entry">
                <input type="text" class="form-control template-option-draft" maxlength="100"
                    placeholder="ایک انتخاب لکھیں، مثلاً مینڈرن">
                <button type="button" class="btn btn-outline-primary template-option-add"><i
                        class="fas fa-plus ml-1"></i> شامل کریں</button>
            </div>
            <small class="text-muted d-block mt-2">ہر انتخاب الگ شامل کریں۔ Enter دبانے سے بھی شامل ہو جائے گا۔</small>
            <div class="template-option-list mt-3" aria-live="polite"></div>
            <div class="template-option-empty">ابھی کوئی انتخاب شامل نہیں کیا گیا۔</div>
        </div>
        <div class="col-12">
            <label class="template-check"><input type="checkbox" name="is_required" value="1"
                    @checked($field?->is_required)><span>اس ٹیمپلیٹ میں لازمی</span></label>
        </div>
    </div>
</div>
