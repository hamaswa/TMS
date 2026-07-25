@extends('main')
@section('content')
<section class="main-content"><div class="container-fluid px-3 px-md-4">
    <div class="d-flex justify-content-between align-items-center mb-3"><div><h3 class="mb-1">سپلائرز</h3><p class="text-muted mb-0">سپلائرز، رابطہ معلومات اور واجب الادا رقوم کا انتظام کریں۔</p></div><a href="{{ route('admin.purchases.index') }}" class="btn btn-outline-primary">خریداریاں</a></div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="row">
        <div class="col-lg-4 mb-3"><div class="card"><div class="card-header">نیا سپلائر شامل کریں</div><div class="card-body">
            <form method="POST" action="{{ route('admin.suppliers.store') }}">@csrf
                <div class="form-group"><label>نام</label><input name="name" value="{{ old('name') }}" class="form-control" required maxlength="255"></div>
                <div class="form-group"><label>رابطہ شخص</label><input name="contact_person" value="{{ old('contact_person') }}" class="form-control"></div>
                <div class="form-group"><label>فون نمبر</label><input name="phone" value="{{ old('phone') }}" class="form-control"></div>
                <div class="form-group"><label>ای میل</label><input type="email" name="email" value="{{ old('email') }}" class="form-control"></div>
                <div class="form-group"><label>ابتدائی بقایا</label><input type="number" step="0.01" min="0" name="opening_balance" value="{{ old('opening_balance', 0) }}" class="form-control"></div>
                <div class="form-group"><label>پتہ</label><textarea name="address" class="form-control">{{ old('address') }}</textarea></div>
                <button class="btn btn-primary" type="submit">سپلائر محفوظ کریں</button>
            </form>
        </div></div></div>
        <div class="col-lg-8"><div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
            <thead class="thead-light"><tr><th>سپلائر</th><th>رابطہ</th><th>ابتدائی بقایا</th><th>خریداری بقایا</th><th>عمل</th></tr></thead>
            <tbody>@forelse($suppliers as $supplier)<tr>
                <td><strong>{{ $supplier->name }}</strong>@unless($supplier->active)<span class="badge badge-secondary ml-1">غیر فعال</span>@endunless<br><small>{{ $supplier->contact_person }}</small></td>
                <td>{{ $supplier->phone }}<br><small>{{ $supplier->email }}</small></td>
                <td>Rs {{ number_format($supplier->opening_balance, 2) }}</td>
                <td>Rs {{ number_format(($supplier->purchase_balance ?? 0) + $supplier->opening_balance - ($supplier->unallocated_payments ?? 0), 2) }}</td>
                <td><a href="{{ route('admin.suppliers.edit', $supplier) }}" class="btn btn-sm btn-outline-primary">ترمیم</a>
                    <form method="POST" action="{{ route('admin.suppliers.destroy', $supplier) }}" class="d-inline" data-confirm="کیا آپ یہ سپلائر حذف کرنا چاہتے ہیں؟">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">حذف کریں</button></form></td>
            </tr>@empty<tr><td colspan="5" class="text-center text-muted py-5">ابھی کوئی سپلائر شامل نہیں کیا گیا۔</td></tr>@endforelse</tbody>
        </table></div></div></div>
    </div>
</div></section>
@endsection
