@extends('main')

@section('content')
<section class="main-content" dir="rtl" style="min-height:calc(100vh - 65px);background:#f7f9fc;padding:30px 0">
    <div class="container" style="max-width:900px">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
            <div><h1 class="h3 mb-1">نیا سپلائر</h1><p class="text-muted mb-0">سپلائر کی بنیادی معلومات درج کریں؛ خریداری الگ مرحلے میں شامل ہوگی۔</p></div>
            <a href="{{ route('admin.suppliers.index') }}" class="btn btn-light"><i class="fas fa-arrow-right ml-1"></i>سپلائرز پر واپس</a>
        </div>
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <div class="card border-0 shadow-sm"><div class="card-body p-4">
            <form method="POST" action="{{ route('admin.suppliers.store') }}">@csrf
                <div class="form-row"><div class="form-group col-md-6"><label for="supplier_name" class="font-weight-bold">سپلائر کا نام <span class="text-danger">*</span></label><input id="supplier_name" name="name" value="{{ old('name') }}" class="form-control" required maxlength="255"></div><div class="form-group col-md-6"><label for="supplier_contact" class="font-weight-bold">رابطہ شخص</label><input id="supplier_contact" name="contact_person" value="{{ old('contact_person') }}" class="form-control" maxlength="255"></div></div>
                <div class="form-row"><div class="form-group col-md-6"><label for="supplier_phone" class="font-weight-bold">فون نمبر</label><input id="supplier_phone" name="phone" value="{{ old('phone') }}" class="form-control" maxlength="50" dir="ltr"></div><div class="form-group col-md-6"><label for="supplier_email" class="font-weight-bold">ای میل</label><input id="supplier_email" type="email" name="email" value="{{ old('email') }}" class="form-control" maxlength="255" dir="ltr"></div></div>
                <div class="form-group"><label for="supplier_opening_balance" class="font-weight-bold">ابتدائی بقایا</label><div class="input-group" dir="ltr"><div class="input-group-prepend"><span class="input-group-text">Rs.</span></div><input id="supplier_opening_balance" type="number" step="0.01" min="0" name="opening_balance" value="{{ old('opening_balance', 0) }}" class="form-control"></div><small class="form-text text-muted">صرف پہلے سے واجب رقم؛ نئی خریداری کا بقایا خود شامل ہوگا۔</small></div>
                <div class="form-group"><label for="supplier_address" class="font-weight-bold">پتہ</label><textarea id="supplier_address" name="address" rows="3" class="form-control" maxlength="1000">{{ old('address') }}</textarea></div>
                <div class="d-flex justify-content-end" style="gap:8px"><a href="{{ route('admin.suppliers.index') }}" class="btn btn-light">منسوخ</a><button class="btn btn-primary" type="submit"><i class="far fa-save ml-1"></i>سپلائر محفوظ کریں</button></div>
            </form>
        </div></div>
    </div>
</section>
@endsection
