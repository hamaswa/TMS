@extends('main')

@section('content')
@php
    $typeLabels = ['number' => 'نمبر لکھیں', 'text' => 'تحریری نوٹ', 'select' => 'فہرست سے چنیں'];
    $unitLabels = ['inch' => 'انچ', 'cm' => 'سینٹی میٹر', 'none' => 'بغیر اکائی'];
@endphp
<style>
    .mf-page{--mf-blue:#1769e0;--mf-navy:#102a50;--mf-muted:#6d7f94;--mf-line:#e0e8f2;direction:rtl;padding:28px 0 50px}.mf-shell{width:min(100% - 32px,1250px);margin-inline:auto}
    .mf-head{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:18px}.mf-title{display:flex;align-items:center;gap:14px}.mf-title-icon{display:grid;place-items:center;width:56px;height:56px;border-radius:16px;color:#fff;background:linear-gradient(135deg,#2479ee,#0c5bd1);font-size:21px;box-shadow:0 9px 20px rgba(23,105,224,.2)}.mf-title h1{margin:0 0 4px;color:var(--mf-navy);font-size:clamp(1.45rem,2vw,1.9rem);font-weight:800}.mf-title p{margin:0;color:var(--mf-muted);font-size:.84rem}.mf-head-actions{display:flex;gap:9px}.mf-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:42px;padding:8px 14px;border:1px solid #d5dfeb;border-radius:10px;color:#40566f;background:#fff;font-weight:800;text-decoration:none!important}.mf-btn:hover{color:var(--mf-blue);border-color:#a9c9f3}.mf-btn.is-primary{color:#fff;border-color:var(--mf-blue);background:var(--mf-blue)}
    .mf-guide{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:18px}.mf-guide-step{display:flex;align-items:flex-start;gap:11px;padding:15px;border:1px solid #dce7f5;border-radius:13px;background:#fff}.mf-step-number{display:grid;place-items:center;flex:0 0 35px;width:35px;height:35px;border-radius:50%;color:#fff;background:var(--mf-blue);font-weight:800}.mf-guide-step:nth-child(2) .mf-step-number{background:#15915a}.mf-guide-step:nth-child(3) .mf-step-number{background:#8654d5}.mf-guide-step strong{display:block;margin-bottom:3px;color:var(--mf-navy);font-size:.84rem}.mf-guide-step span{display:block;color:var(--mf-muted);font-size:.73rem;line-height:1.8}
    .mf-system{padding:16px 18px;margin-bottom:18px;border:1px solid #d8e8fb;border-radius:14px;background:#f3f8ff}.mf-system-head{display:flex;align-items:center;gap:10px;margin-bottom:12px}.mf-system-head i{color:var(--mf-blue);font-size:18px}.mf-system-head strong{display:block;color:var(--mf-navy)}.mf-system-head small{display:block;color:var(--mf-muted)}.mf-system-fields{display:flex;flex-wrap:wrap;gap:7px}.mf-system-field{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border:1px solid #d4e2f4;border-radius:999px;color:#40566f;background:#fff;font-size:.76rem;font-weight:800}.mf-system-field i{color:#15915a;font-size:.68rem}
    .mf-panel{overflow:hidden;margin-bottom:18px;border:1px solid var(--mf-line);border-radius:17px;background:#fff;box-shadow:0 8px 28px rgba(21,47,81,.055)}.mf-panel-head{display:flex;align-items:center;gap:12px;padding:18px 20px;border-bottom:1px solid var(--mf-line);background:#fbfdff}.mf-panel-icon{display:grid;place-items:center;width:42px;height:42px;border-radius:12px;color:var(--mf-blue);background:#eaf3ff;font-size:17px}.mf-panel-head h2{margin:0 0 3px;color:var(--mf-navy);font-size:1.12rem;font-weight:800}.mf-panel-head p{margin:0;color:var(--mf-muted);font-size:.75rem}.mf-form{padding:20px}.mf-form-grid{display:grid;grid-template-columns:1.35fr 1fr .8fr;gap:15px}.mf-field.is-wide{grid-column:1 / -1}.mf-field label{display:block;margin-bottom:7px;color:#344a67;font-size:.82rem;font-weight:800}.mf-field label i{width:18px;color:var(--mf-blue);text-align:center}.mf-field .form-control{min-height:48px;border-color:#d3deeb;border-radius:10px;color:var(--mf-navy);background:#fbfdff}.mf-field .form-control:focus{border-color:#75a8ef;box-shadow:0 0 0 3px rgba(23,105,224,.1);background:#fff}.mf-field small{display:block;margin-top:5px;color:var(--mf-muted);font-size:.7rem;line-height:1.7}.mf-options-box{padding:14px;border:1px solid #e3dafa;border-radius:11px;background:#f8f5ff}.mf-required{display:flex;align-items:center;gap:10px;padding:12px 14px;border:1px solid #dce5ef;border-radius:11px;background:#fbfdff}.mf-required input{width:19px;height:19px}.mf-required strong{display:block;color:var(--mf-navy);font-size:.8rem}.mf-required small{display:block;color:var(--mf-muted);font-size:.7rem}.mf-form-footer{display:flex;align-items:center;justify-content:space-between;gap:15px;margin-top:17px}.mf-form-footer p{margin:0;color:var(--mf-muted);font-size:.72rem}.mf-save{display:inline-flex;align-items:center;gap:8px;min-height:44px;padding:8px 18px;border:0;border-radius:10px;color:#fff;background:#15915a;font-weight:800;box-shadow:0 7px 16px rgba(21,145,90,.16)}
    .mf-list-head{display:flex;align-items:center;justify-content:space-between;gap:15px;padding:18px 20px;border-bottom:1px solid var(--mf-line)}.mf-list-head h2{margin:0 0 3px;color:var(--mf-navy);font-size:1.12rem;font-weight:800}.mf-list-head p{margin:0;color:var(--mf-muted);font-size:.75rem}.mf-count{padding:6px 11px;border-radius:999px;color:var(--mf-blue);background:#eaf3ff;font-weight:800}.mf-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;padding:16px}.mf-card{overflow:hidden;border:1px solid var(--mf-line);border-radius:13px;background:#fff}.mf-card-summary{display:flex;align-items:center;gap:12px;padding:14px}.mf-card-icon{display:grid;place-items:center;flex:0 0 42px;width:42px;height:42px;border-radius:12px;color:#1769e0;background:#eaf3ff}.mf-card-main{min-width:0;flex:1}.mf-card-main strong{display:block;overflow:hidden;color:var(--mf-navy);font-size:.9rem;text-overflow:ellipsis;white-space:nowrap}.mf-card-meta{display:flex;flex-wrap:wrap;gap:6px;margin-top:5px}.mf-chip{padding:3px 7px;border-radius:999px;color:#63758c;background:#eef2f6;font-size:.66rem;font-weight:800}.mf-chip.is-required{color:#9a650a;background:#fff2d9}.mf-chip.is-off{color:#a13b46;background:#fff0f2}.mf-card details{border-top:1px solid #edf1f6}.mf-card summary{padding:10px 14px;color:var(--mf-blue);background:#fbfdff;font-size:.75rem;font-weight:800;cursor:pointer;list-style:none}.mf-card summary::-webkit-details-marker{display:none}.mf-card summary i{margin-left:5px}.mf-edit{padding:14px}.mf-edit-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:11px}.mf-edit .mf-field.is-wide{grid-column:1 / -1}.mf-edit-actions{display:flex;align-items:center;gap:9px;margin-top:12px}.mf-update{display:inline-flex;align-items:center;gap:6px;padding:7px 12px;border:0;border-radius:8px;color:#fff;background:#15915a;font-weight:800}.mf-disable{padding:7px 10px;border:1px solid #f1c9ce;border-radius:8px;color:#c23b48;background:#fff7f8;font-weight:800}.mf-empty{grid-column:1 / -1;padding:42px 20px;text-align:center;color:var(--mf-muted)}.mf-empty i{display:grid;place-items:center;width:54px;height:54px;margin:0 auto 10px;border-radius:50%;color:var(--mf-blue);background:#eaf3ff;font-size:21px}.mf-empty strong{display:block;margin-bottom:3px;color:var(--mf-navy)}.mf-alert{padding:13px 16px;margin-bottom:16px;border-radius:11px}.mf-alert.is-success{border:1px solid #c9eadb;color:#146e46;background:#ecf9f3}.mf-alert.is-error{border:1px solid #f0c7cc;color:#a52c38;background:#fff4f5}
    @media(max-width:991px){.mf-guide{grid-template-columns:1fr}.mf-form-grid{grid-template-columns:1fr 1fr}.mf-list{grid-template-columns:1fr}}
    @media(max-width:767px){.mf-page{padding-top:18px}.mf-shell{width:min(100% - 20px,1250px)}.mf-head{align-items:flex-start;flex-direction:column}.mf-head-actions{width:100%}.mf-btn{flex:1}.mf-form-grid,.mf-edit-grid{grid-template-columns:1fr}.mf-field.is-wide,.mf-edit .mf-field.is-wide{grid-column:auto}.mf-form-footer{align-items:stretch;flex-direction:column}.mf-save{justify-content:center}.mf-list-head{align-items:flex-start}.mf-system{padding:14px}}
</style>

<section class="main-content mf-page">
    <div class="mf-shell">
        <header class="mf-head">
            <div class="mf-title"><span class="mf-title-icon"><i class="fas fa-ruler-combined"></i></span><div><h1>اپنی پیمائش کا نیا خانہ بنائیں</h1><p>صرف وہ پیمائش شامل کریں جو نیچے موجود تیار خانوں میں نہیں ہے۔</p></div></div>
            <div class="mf-head-actions"><a href="{{ route('admin.measurement-templates.index') }}" class="mf-btn is-primary"><i class="fas fa-clipboard-list"></i> لباس کے پیمائش فارم</a><a href="{{ route('admin.Customers.index') }}" class="mf-btn"><i class="fas fa-users"></i> گاہک اور پیمائش</a></div>
        </header>

        @if(session('success'))<div class="mf-alert is-success"><i class="fas fa-check-circle ml-1"></i>{{ session('success') }}</div>@endif
        @if($errors->any())<div class="mf-alert is-error"><strong>براہِ کرم یہ معلومات درست کریں:</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <div class="mf-guide">
            <div class="mf-guide-step"><span class="mf-step-number">1</span><div><strong>پہلے تیار خانے دیکھیں</strong><span>اگر مطلوبہ پیمائش نیچے پہلے سے موجود ہے تو نیا خانہ نہ بنائیں۔</span></div></div>
            <div class="mf-guide-step"><span class="mf-step-number">2</span><div><strong>نیا خانہ کب بنائیں؟</strong><span>مثلاً آپ کو “کالر اونچائی” یا “گھٹنے کی چوڑائی” چاہیے اور وہ موجود نہیں۔</span></div></div>
            <div class="mf-guide-step"><span class="mf-step-number">3</span><div><strong>پھر کہاں استعمال ہوگا؟</strong><span>نیا خانہ گاہک کی پیمائش اور لباس کے پیمائش فارم میں منتخب کیا جا سکے گا۔</span></div></div>
        </div>

        <section class="mf-system">
            <div class="mf-system-head"><i class="fas fa-check-double"></i><div><strong>یہ پیمائش پہلے سے موجود ہیں</strong><small>ان کے لیے دوبارہ نیا خانہ بنانے کی ضرورت نہیں۔</small></div></div>
            <div class="mf-system-fields">@foreach($systemFields as $field)<span class="mf-system-field"><i class="fas fa-check"></i>{{ $field }}</span>@endforeach</div>
        </section>

        <section class="mf-panel">
            <div class="mf-panel-head"><span class="mf-panel-icon"><i class="fas fa-plus"></i></span><div><h2>نئی پیمائش شامل کریں</h2><p>مثال: کالر اونچائی — نمبر لکھیں — انچ</p></div></div>
            <form method="POST" action="{{ route('admin.measurement-fields.store') }}" class="mf-form" id="measurementFieldForm">
                @csrf
                <input type="hidden" name="sort_order" value="0"><input type="hidden" name="is_active" value="1">
                <div class="mf-form-grid">
                    <div class="mf-field"><label for="newFieldLabel"><i class="fas fa-tag"></i> پیمائش کا نام</label><input id="newFieldLabel" class="form-control" name="label" value="{{ old('label') }}" required maxlength="100" placeholder="مثلاً کالر اونچائی"><small>وہی آسان نام لکھیں جو کاریگر روزانہ بولتے ہیں۔</small></div>
                    <div class="mf-field"><label for="newFieldType"><i class="fas fa-keyboard"></i> پیمائش کیسے درج ہوگی؟</label><select id="newFieldType" class="form-control" name="field_type"><option value="number" @selected(old('field_type','number')==='number')>نمبر لکھیں — مثلاً 15.5</option><option value="text" @selected(old('field_type')==='text')>تحریری نوٹ — مثلاً ڈھیلا رکھیں</option><option value="select" @selected(old('field_type')==='select')>فہرست سے چنیں — مثلاً تنگ یا کھلا</option></select></div>
                    <div class="mf-field"><label for="newFieldUnit"><i class="fas fa-ruler"></i> ناپ کی اکائی</label><select id="newFieldUnit" class="form-control" name="unit"><option value="inch" @selected(old('unit','inch')==='inch')>انچ</option><option value="cm" @selected(old('unit')==='cm')>سینٹی میٹر</option><option value="none" @selected(old('unit')==='none')>اکائی کی ضرورت نہیں</option></select></div>
                    <div class="mf-field is-wide mf-options-box" id="newOptionsBox"><label for="newFieldOptions"><i class="fas fa-list"></i> فہرست میں کیا کیا دکھانا ہے؟</label><input id="newFieldOptions" class="form-control" name="options_text" value="{{ old('options_text') }}" placeholder="مثلاً تنگ، درمیانہ، کھلا"><small>ہر انتخاب کے درمیان اردو کوما (،) لگائیں۔ یہ صرف “فہرست سے چنیں” کے لیے ہے۔</small></div>
                    <label class="mf-required mf-field is-wide" for="newFieldRequired"><input type="hidden" name="is_required" value="0"><input id="newFieldRequired" type="checkbox" name="is_required" value="1" @checked(old('is_required'))><span><strong>ہر گاہک کے لیے یہ پیمائش لازمی کریں</strong><small>صرف تب منتخب کریں جب اس پیمائش کے بغیر آرڈر مکمل نہیں ہو سکتا۔</small></span></label>
                </div>
                <div class="mf-form-footer"><p><i class="fas fa-info-circle text-primary ml-1"></i> شامل کرنے کے بعد اسے لباس کے پیمائش فارم میں منتخب کریں۔</p><button class="mf-save" type="submit"><i class="fas fa-plus-circle"></i> پیمائش شامل کریں</button></div>
            </form>
        </section>

        <section class="mf-panel">
            <div class="mf-list-head"><div><h2>آپ کی بنائی ہوئی پیمائش</h2><p>تبدیلی کے لیے متعلقہ خانے کے نیچے “تبدیل کریں” دبائیں۔</p></div><span class="mf-count">{{ $fields->count() }} خانے</span></div>
            <div class="mf-list">
                @forelse($fields as $field)
                    <article class="mf-card">
                        <div class="mf-card-summary"><span class="mf-card-icon"><i class="fas {{ $field->field_type === 'number' ? 'fa-ruler' : ($field->field_type === 'select' ? 'fa-list-ul' : 'fa-font') }}"></i></span><div class="mf-card-main"><strong>{{ $field->label }}</strong><div class="mf-card-meta"><span class="mf-chip">{{ $typeLabels[$field->field_type] ?? $field->field_type }}</span><span class="mf-chip">{{ $unitLabels[$field->unit ?? 'none'] }}</span>@if($field->is_required)<span class="mf-chip is-required">لازمی</span>@endif @unless($field->is_active)<span class="mf-chip is-off">بند ہے</span>@endunless</div></div></div>
                        <details><summary><i class="fas fa-edit"></i> تبدیل کریں</summary><div class="mf-edit">
                            <form method="POST" action="{{ route('admin.measurement-fields.update',$field) }}">@csrf @method('PUT')<input type="hidden" name="sort_order" value="{{ $field->sort_order }}">
                                <div class="mf-edit-grid">
                                    <div class="mf-field"><label>نام</label><input class="form-control" name="label" value="{{ $field->label }}" required></div>
                                    <div class="mf-field"><label>کیسے درج ہوگی؟</label><select class="form-control" name="field_type">@foreach($typeLabels as $value=>$label)<option value="{{ $value }}" @selected($field->field_type===$value)>{{ $label }}</option>@endforeach</select></div>
                                    <div class="mf-field"><label>اکائی</label><select class="form-control" name="unit">@foreach($unitLabels as $value=>$label)<option value="{{ $value }}" @selected(($field->unit ?? 'none')===$value)>{{ $label }}</option>@endforeach</select></div>
                                    <div class="mf-field"><label>فہرست کے انتخاب</label><input class="form-control" name="options_text" value="{{ implode('، ', $field->options ?? []) }}" placeholder="تنگ، درمیانہ، کھلا"></div>
                                    <label class="mf-required"><input type="hidden" name="is_required" value="0"><input type="checkbox" name="is_required" value="1" @checked($field->is_required)><span><strong>لازمی پیمائش</strong></span></label>
                                    <label class="mf-required"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($field->is_active)><span><strong>گاہک کے فارم میں دکھائیں</strong></span></label>
                                </div>
                                <div class="mf-edit-actions"><button class="mf-update" type="submit"><i class="fas fa-check"></i> تبدیلی محفوظ کریں</button></div>
                            </form>
                            @if($field->is_active)<form method="POST" action="{{ route('admin.measurement-fields.destroy',$field) }}" class="mt-2" data-confirm="کیا اس پیمائش کو گاہک کے فارم سے بند کرنا ہے؟ پرانا ریکارڈ محفوظ رہے گا۔">@csrf @method('DELETE')<button class="mf-disable" type="submit"><i class="fas fa-eye-slash ml-1"></i> گاہک کے فارم سے بند کریں</button></form>@endif
                        </div></details>
                    </article>
                @empty
                    <div class="mf-empty"><i class="fas fa-ruler-combined"></i><strong>ابھی کوئی اپنی پیمائش نہیں بنائی</strong><span>اگر تیار خانوں میں مطلوبہ پیمائش نہیں ہے تو اوپر سے شامل کریں۔</span></div>
                @endforelse
            </div>
        </section>
    </div>
</section>

<script>
$(function () {
    var type = $('#newFieldType');
    var optionsBox = $('#newOptionsBox');
    var optionsInput = $('#newFieldOptions');
    function updateMeasurementForm() {
        var usesList = type.val() === 'select';
        optionsBox.toggle(usesList);
        optionsInput.prop('required', usesList);
    }
    type.on('change', updateMeasurementForm);
    updateMeasurementForm();
});
</script>
@endsection
