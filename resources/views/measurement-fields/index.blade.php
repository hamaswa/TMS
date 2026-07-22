@extends('main')
@section('content')
<section class="main-content py-4" dir="rtl">
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div><h2 class="mb-1">اضافی پیمائش خانے</h2><p class="text-muted mb-0">اپنے کام کے مطابق نئے خانے بنائیں۔ یہ ہر گاہک کی پیمائش میں دکھائی دیں گے۔</p></div>
        <div><a href="{{ route('admin.measurement-templates.index') }}" class="btn btn-primary ml-2">لباس ٹیمپلیٹس</a><a href="{{ route('admin.Customers.index') }}" class="btn btn-outline-primary">گاہک اور پیمائش</a></div>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><strong>درستگی درکار ہے:</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="card mb-4 border-0 shadow-sm"><div class="card-body">
        <h5>محفوظ سسٹم خانے</h5><p class="text-muted">یہ بنیادی خانے حذف نہیں کیے جا سکتے:</p>
        <div class="d-flex flex-wrap">@foreach($systemFields as $field)<span class="badge badge-light border p-2 ml-2 mb-2">{{ $field }}</span>@endforeach</div>
    </div></div>

    <div class="card mb-4 border-0 shadow-sm"><div class="card-body">
        <h4 class="mb-3">نیا خانہ شامل کریں</h4>
        <form method="post" action="{{ route('admin.measurement-fields.store') }}">@csrf
            <div class="form-row">
                <div class="form-group col-md-4"><label>خانے کا نام</label><input class="form-control" name="label" value="{{ old('label') }}" required placeholder="مثلاً گھٹنے کی چوڑائی"></div>
                <div class="form-group col-md-2"><label>قسم</label><select class="form-control" name="field_type"><option value="number">نمبر</option><option value="text">تحریر</option><option value="select">فہرست</option></select></div>
                <div class="form-group col-md-2"><label>اکائی</label><select class="form-control" name="unit"><option value="inch">انچ</option><option value="cm">سینٹی میٹر</option><option value="none">کوئی نہیں</option></select></div>
                <div class="form-group col-md-2"><label>ترتیب</label><input type="number" min="0" class="form-control" name="sort_order" value="{{ old('sort_order', 0) }}"></div>
                <div class="form-group col-md-2 d-flex align-items-end"><div class="form-check mb-2"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="new_active"><label class="form-check-label" for="new_active">فعال</label></div></div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-8"><label>فہرست کے اختیارات</label><input class="form-control" name="options_text" value="{{ old('options_text') }}" placeholder="کوما سے جدا کریں: تنگ، درمیانہ، کھلا"><small class="text-muted">قسم “فہرست” ہو تو کم از کم ایک اختیار لکھیں۔</small></div>
                <div class="form-group col-md-2 d-flex align-items-center"><div class="form-check mt-3"><input type="hidden" name="is_required" value="0"><input class="form-check-input" type="checkbox" name="is_required" value="1" id="new_required"><label class="form-check-label" for="new_required">لازمی خانہ</label></div></div>
                <div class="form-group col-md-2 d-flex align-items-end"><button class="btn btn-primary btn-block">شامل کریں</button></div>
            </div>
        </form>
    </div></div>

    <div class="card border-0 shadow-sm"><div class="card-body"><h4 class="mb-3">آپ کے اضافی خانے</h4>
        @forelse($fields as $field)
            <form method="post" action="{{ route('admin.measurement-fields.update', $field) }}" class="border rounded p-3 mb-2">@csrf @method('PUT')
                <div class="form-row align-items-end">
                    <div class="form-group col-lg-3"><label>نام</label><input class="form-control" name="label" value="{{ $field->label }}" required></div>
                    <div class="form-group col-lg-2"><label>قسم</label><select class="form-control" name="field_type">@foreach(['number'=>'نمبر','text'=>'تحریر','select'=>'فہرست'] as $value=>$label)<option value="{{ $value }}" @selected($field->field_type === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="form-group col-lg-2"><label>اکائی</label><select class="form-control" name="unit">@foreach(['inch'=>'انچ','cm'=>'سینٹی میٹر','none'=>'کوئی نہیں'] as $value=>$label)<option value="{{ $value }}" @selected(($field->unit ?? 'none') === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="form-group col-lg-2"><label>ترتیب</label><input type="number" min="0" class="form-control" name="sort_order" value="{{ $field->sort_order }}"></div>
                    <div class="form-group col-lg-3"><label>فہرست کے اختیارات</label><input class="form-control" name="options_text" value="{{ implode(', ', $field->options ?? []) }}"></div>
                </div>
                <div class="d-flex flex-wrap align-items-center">
                    <input type="hidden" name="is_required" value="0"><div class="form-check ml-4"><input class="form-check-input" type="checkbox" name="is_required" value="1" id="required_{{ $field->id }}" @checked($field->is_required)><label class="form-check-label" for="required_{{ $field->id }}">لازمی</label></div>
                    <input type="hidden" name="is_active" value="0"><div class="form-check ml-4"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="active_{{ $field->id }}" @checked($field->is_active)><label class="form-check-label" for="active_{{ $field->id }}">فعال</label></div>
                    <button class="btn btn-sm btn-success ml-2">محفوظ کریں</button>
                </div>
            </form>
            <form method="post" action="{{ route('admin.measurement-fields.destroy', $field) }}" class="text-left mb-3" onsubmit="return confirm('کیا اس خانے کو غیر فعال کرنا ہے؟')">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger">غیر فعال کریں</button></form>
        @empty<div class="text-center text-muted py-4">ابھی کوئی اضافی خانہ نہیں بنایا گیا۔</div>@endforelse
    </div></div>
</div>
</section>
@endsection
