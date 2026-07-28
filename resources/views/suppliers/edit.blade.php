@extends('main')
@section('content')
<section class="main-content"><div class="container"><div class="card mb-3"><div class="card-header"><h4 class="mb-0">سپلائر میں ترمیم</h4></div><div class="card-body">
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('admin.suppliers.update', $supplier) }}">@csrf @method('PUT')
        <div class="form-row"><div class="form-group col-md-6"><label>نام</label><input name="name" value="{{ old('name', $supplier->name) }}" class="form-control" required></div><div class="form-group col-md-6"><label>رابطہ شخص</label><input name="contact_person" value="{{ old('contact_person', $supplier->contact_person) }}" class="form-control"></div></div>
        <div class="form-row"><div class="form-group col-md-6"><label>فون نمبر</label><input name="phone" value="{{ old('phone', $supplier->phone) }}" class="form-control"></div><div class="form-group col-md-6"><label>ای میل</label><input type="email" name="email" value="{{ old('email', $supplier->email) }}" class="form-control"></div></div>
        <div class="form-group"><label>ابتدائی بقایا</label><input type="number" step="0.01" min="0" name="opening_balance" value="{{ old('opening_balance', $supplier->opening_balance) }}" class="form-control"></div>
        <div class="form-group"><label>پتہ</label><textarea name="address" class="form-control">{{ old('address', $supplier->address) }}</textarea></div>
        <input type="hidden" name="active" value="0"><div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="active" value="1" id="active" @checked(old('active', $supplier->active))><label class="form-check-label" for="active">فعال سپلائر</label></div>
        <button class="btn btn-primary">سپلائر اپ ڈیٹ کریں</button> <a href="{{ route('admin.suppliers.index') }}" class="btn btn-light">منسوخ کریں</a>
    </form>
</div></div>
@php($outstanding = (float)$supplier->opening_balance + (float)($supplier->purchase_balance ?? 0) - (float)($supplier->unallocated_payments ?? 0))
<div class="card"><div class="card-header">سپلائر ادائیگی · بقایا روپے {{ number_format($outstanding,2) }}</div><div class="card-body">
    @if($outstanding > 0)<form method="POST" action="{{ route('admin.suppliers.payment',$supplier) }}">@csrf<div class="form-row align-items-end"><div class="form-group col-md-3 mb-md-0"><label>رقم</label><input type="number" name="amount" step="0.01" min="0.01" max="{{ $outstanding }}" class="form-control" required></div><div class="form-group col-md-3 mb-md-0"><label>تاریخ</label><input type="date" name="payment_date" value="{{ now()->toDateString() }}" class="form-control" required></div><div class="form-group col-md-3 mb-md-0"><label>ادائیگی کا طریقہ</label><select name="payment_method" class="form-control" required>@foreach(\App\Support\PaymentMethods::LABELS as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div><div class="form-group col-md-3 mb-md-0"><label>حوالہ / ٹرانزیکشن نمبر</label><input name="reference" maxlength="255" class="form-control"></div><div class="col-12 mt-3"><button class="btn btn-success">ادائیگی درج کریں</button></div></div></form>@else<span class="text-muted">سپلائر کی کوئی بقایا رقم نہیں۔</span>@endif
</div></div></div></section>
@endsection
