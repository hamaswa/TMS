@extends('main')

@section('content')
<section class="main-content service-admin">
<style>
    .service-admin{background:#f3f7f8;min-height:calc(100vh - 70px)}.service-hero{background:linear-gradient(135deg,#263d62,#425f91);color:#fff;border-radius:20px;padding:1.5rem 1.7rem}.service-hero h1{color:#fff!important}.service-card{border:0;border-radius:18px;box-shadow:0 9px 28px rgba(31,45,61,.08)}.state{border-radius:999px;padding:.3rem .7rem;font-weight:700}.state-on{background:#ddf3e7;color:#17643c}.state-off{background:#ecefed;color:#68746f}@media(max-width:767px){.service-hero{border-radius:14px}}
</style>
<div class="container-fluid px-3 px-md-4 py-4" dir="rtl">
    <div class="service-hero mb-4 d-flex flex-wrap justify-content-between align-items-center"><div><div class="small">آن لائن دکان</div><h1 class="h3 mb-1">ٹیلرنگ خدمات</h1><p class="mb-0 text-white-50">سلائی، کٹنگ، ڈیزائن اور دوسری خدمات کی عوامی فہرست بنائیں۔</p></div><div class="mt-3 mt-md-0"><a class="btn btn-light" href="{{ route('admin.storefront.inquiries.index') }}">گاہکوں کی درخواستیں</a></div></div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="card service-card mb-4"><div class="card-header bg-white"><h2 class="h5 mb-0">نئی خدمت شامل کریں</h2></div><form class="card-body" method="POST" action="{{ route('admin.storefront.tailoring.store') }}">@csrf
        <div class="form-row"><div class="form-group col-md-7"><label>خدمت کا نام</label><input name="name" required maxlength="180" class="form-control" value="{{ old('name') }}" placeholder="مثلاً مردانہ شلوار قمیض سلائی"></div><div class="form-group col-md-5"><label>ترتیب</label><input type="number" name="sort_order" min="0" max="9999" class="form-control" value="{{ old('sort_order',0) }}"></div></div>
        <div class="form-group"><label>تفصیل</label><textarea name="description" maxlength="2000" rows="3" class="form-control" placeholder="خدمت، کپڑے کی ضرورت اور شامل کام کی وضاحت کریں۔">{{ old('description') }}</textarea></div>
        <div class="form-row"><div class="form-group col-md-4"><label>قیمت شروع</label><input type="number" name="price_from" min="0" step="0.01" class="form-control" value="{{ old('price_from') }}"></div><div class="form-group col-md-4"><label>قیمت کی اکائی</label><select name="price_unit" class="form-control"><option>فی سوٹ</option><option>فی لباس</option><option>فی کام</option></select></div><div class="form-group col-md-4"><label>تخمینی دن</label><input type="number" name="estimated_days" min="1" max="365" class="form-control" value="{{ old('estimated_days') }}"></div></div>
        <div class="d-flex flex-wrap justify-content-between"><div><label class="ml-3"><input type="checkbox" name="is_featured" value="1"> نمایاں خدمت</label><label><input type="checkbox" name="is_published" value="1"> عوام کو دکھائیں</label></div><button class="btn btn-primary px-4">خدمت شامل کریں</button></div>
    </form></div>

    @forelse($services as $service)
    <div class="card service-card mb-4"><form method="POST" action="{{ route('admin.storefront.tailoring.update',$service) }}" class="card-body">@csrf @method('PUT')
        <div class="d-flex justify-content-between align-items-start mb-3"><h2 class="h5">{{ $service->name }}</h2><span class="state {{ $service->is_published?'state-on':'state-off' }}">{{ $service->is_published?'عوامی':'مسودہ' }}</span></div>
        <div class="form-row"><div class="form-group col-md-7"><label>خدمت کا نام</label><input name="name" required maxlength="180" class="form-control" value="{{ $service->name }}"></div><div class="form-group col-md-5"><label>ترتیب</label><input type="number" name="sort_order" min="0" max="9999" class="form-control" value="{{ $service->sort_order }}"></div></div>
        <div class="form-group"><label>تفصیل</label><textarea name="description" maxlength="2000" rows="3" class="form-control">{{ $service->description }}</textarea></div>
        <div class="form-row"><div class="form-group col-md-4"><label>قیمت شروع</label><input type="number" name="price_from" min="0" step="0.01" class="form-control" value="{{ $service->price_from }}"></div><div class="form-group col-md-4"><label>قیمت کی اکائی</label><select name="price_unit" class="form-control">@foreach(['فی سوٹ','فی لباس','فی کام'] as $unit)<option @selected($service->price_unit===$unit)>{{ $unit }}</option>@endforeach</select></div><div class="form-group col-md-4"><label>تخمینی دن</label><input type="number" name="estimated_days" min="1" max="365" class="form-control" value="{{ $service->estimated_days }}"></div></div>
        <div class="d-flex flex-wrap justify-content-between"><div><label class="ml-3"><input type="checkbox" name="is_featured" value="1" @checked($service->is_featured)> نمایاں خدمت</label><label><input type="checkbox" name="is_published" value="1" @checked($service->is_published)> عوام کو دکھائیں</label></div><button class="btn btn-primary px-4">محفوظ کریں</button></div>
    </form></div>
    @empty<div class="card service-card"><div class="card-body text-center py-5"><h2 class="h5">ابھی کوئی خدمت شامل نہیں</h2><p class="text-muted">اوپر دیے گئے فارم سے پہلی خدمت شامل کریں۔</p></div></div>@endforelse
</div>
</section>
@endsection
