@extends('main')
@section('content')
<section class="main-content template-page" dir="rtl"><div class="container-fluid px-3 px-lg-5 py-4">
    <div class="template-hero mb-4"><div class="row align-items-center"><div class="col-lg-8"><span class="badge badge-light text-primary mb-2">پیمائش کی ترتیب</span><h1 class="h3 font-weight-bold mb-1">لباس کے پیمائش ٹیمپلیٹس</h1><p class="mb-0">ہر لباس کے لیے صرف ضروری بنیادی اور اضافی پیمائش خانے منتخب کریں۔</p></div><div class="col-lg-4 text-lg-left mt-3 mt-lg-0"><a href="{{ route('admin.measurement-fields.index') }}" class="btn btn-light"><i class="fas fa-plus ml-1"></i> اضافی خانے ترتیب دیں</a></div></div></div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><strong>براہ کرم درج ذیل معلومات درست کریں:</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="card template-card mb-4"><div class="card-body p-4 p-lg-5"><div class="section-heading"><div><h2 class="h4 font-weight-bold mb-1">نیا ٹیمپلیٹ بنائیں</h2><p class="text-muted mb-0">مثلاً مردانہ شلوار قمیض، واسکٹ یا بچوں کا لباس</p></div><span class="badge badge-info px-3 py-2">کم از کم ایک خانہ منتخب کریں</span></div>
        <form method="POST" action="{{ route('admin.measurement-templates.store') }}">@csrf
            <div class="row"><div class="col-md-5 form-group"><label class="font-weight-bold">ٹیمپلیٹ کا نام</label><input class="form-control form-control-lg" name="name" value="{{ old('name') }}" placeholder="مثلاً مردانہ شلوار قمیض" required></div><div class="col-md-5 form-group"><label class="font-weight-bold">مختصر وضاحت</label><input class="form-control form-control-lg" name="description" value="{{ old('description') }}" placeholder="یہ ٹیمپلیٹ کب استعمال ہوگا؟"></div><div class="col-md-2 d-flex align-items-center"><label class="template-default"><input type="checkbox" name="is_default" value="1" @checked(old('is_default'))> ڈیفالٹ ٹیمپلیٹ</label></div></div>
            @include('measurement-templates.partials.fields', ['prefix' => 'new', 'selectedSystem' => old('system_fields', array_keys($systemFields)), 'selectedCustom' => old('custom_field_ids', [])])
            <div class="text-left mt-4"><button class="btn btn-primary btn-lg px-5"><i class="fas fa-save ml-1"></i> ٹیمپلیٹ محفوظ کریں</button></div>
        </form>
    </div></div>

    <div class="d-flex justify-content-between align-items-center mb-3"><div><h2 class="h4 font-weight-bold mb-1">محفوظ ٹیمپلیٹس</h2><p class="text-muted mb-0">ترمیم کا اثر آئندہ پیمائش اور آرڈرز پر ہوگا؛ پرانے آرڈر محفوظ رہیں گے۔</p></div><span class="badge badge-secondary px-3 py-2">{{ $templates->where('is_active', true)->count() }} فعال</span></div>
    <div class="row">
        @forelse($templates as $template)
            <div class="col-xl-6 mb-4"><div class="card template-card h-100 {{ $template->is_active ? '' : 'template-inactive' }}"><div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-3"><div><h3 class="h5 font-weight-bold mb-1">{{ $template->name }} @if($template->is_default)<span class="badge badge-primary mr-1">ڈیفالٹ</span>@endif @unless($template->is_active)<span class="badge badge-secondary mr-1">غیر فعال</span>@endunless</h3><p class="text-muted mb-0">{{ $template->description ?: 'کوئی وضاحت درج نہیں۔' }}</p></div><span class="template-count">{{ count($template->system_fields ?? []) + count($template->custom_field_ids ?? []) }} خانے</span></div>
                @if($template->is_active)
                    <details><summary class="btn btn-outline-primary btn-block">ٹیمپلیٹ میں ترمیم کریں</summary><form class="mt-4" method="POST" action="{{ route('admin.measurement-templates.update', $template) }}">@csrf @method('PUT')
                        <div class="row"><div class="col-md-6 form-group"><label>نام</label><input class="form-control" name="name" value="{{ $template->name }}" required></div><div class="col-md-6 form-group"><label>وضاحت</label><input class="form-control" name="description" value="{{ $template->description }}"></div></div><label class="template-default mb-3"><input type="checkbox" name="is_default" value="1" @checked($template->is_default)> ڈیفالٹ ٹیمپلیٹ</label>
                        @include('measurement-templates.partials.fields', ['prefix' => 'template-'.$template->id, 'selectedSystem' => $template->system_fields ?? [], 'selectedCustom' => $template->custom_field_ids ?? []])
                        <button class="btn btn-success mt-3">تبدیلی محفوظ کریں</button>
                    </form></details>
                    <form method="POST" action="{{ route('admin.measurement-templates.destroy', $template) }}" class="text-left mt-2" data-confirm="کیا اس ٹیمپلیٹ کو غیر فعال کرنا ہے؟">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger">غیر فعال کریں</button></form>
                @endif
            </div></div></div>
        @empty<div class="col-12"><div class="card template-card"><div class="card-body empty-state">ابھی کوئی ٹیمپلیٹ نہیں بنایا گیا۔ اوپر پہلے ٹیمپلیٹ سے آغاز کریں۔</div></div></div>@endforelse
    </div>
</div></section>
<style>
    .template-page{background:#f4f7fa;min-height:calc(100vh - 70px)}.template-hero{background:linear-gradient(135deg,#102a43,#1769aa);color:#fff;border-radius:22px;padding:1.7rem 2rem;box-shadow:0 14px 34px rgba(16,42,67,.16)}.template-hero h1{color:#fff!important}.template-hero p{color:rgba(255,255,255,.8)}.template-card{border:0;border-radius:18px;box-shadow:0 9px 25px rgba(31,45,61,.08);overflow:hidden}.section-heading{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1.5rem}.field-group{border:1px solid #dfe7f0;border-radius:15px;padding:1rem;background:#fbfdff}.field-options{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:.55rem}.field-choice{display:flex;align-items:flex-start;gap:.55rem;border:1px solid #e1e8ef;border-radius:11px;padding:.7rem;background:#fff;margin:0;cursor:pointer}.field-choice input{margin-top:.25rem}.field-choice small{display:block;color:#718096}.template-default{border:1px solid #cfe0ed;background:#eef7fd;border-radius:12px;padding:.65rem .8rem;margin:0}.template-count{background:#edf5fb;color:#1769aa;border-radius:999px;padding:.35rem .65rem;font-weight:700;white-space:nowrap}.template-inactive{opacity:.65}.empty-state{text-align:center;color:#718096;padding:3rem}details summary{cursor:pointer;list-style:none}details summary::-webkit-details-marker{display:none}@media(max-width:767px){.template-hero{padding:1.3rem}.section-heading{align-items:flex-start;flex-direction:column}}
</style>
@endsection
