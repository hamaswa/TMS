@extends('main')
@push('styles')
<style>
@media (max-width: 767.98px) {
    .supplier-payment-table, .supplier-payment-table tbody, .supplier-payment-table tr, .supplier-payment-table td { display:block; width:100%; }
    .supplier-payment-table thead { display:none; }
    .supplier-payment-table tr { border:1px solid #e3e8ee; border-radius:.65rem; margin:.75rem; padding:.35rem .75rem; width:calc(100% - 1.5rem); }
    .supplier-payment-table td { display:flex; justify-content:space-between; gap:1rem; border-top:1px solid #eef1f4; padding:.7rem 0; }
    .supplier-payment-table td:first-child { border-top:0; }
    .supplier-payment-table td::before { content:attr(data-label); flex:0 0 38%; color:#6c757d; font-weight:700; }
}
</style>
@endpush
@section('content')
<section class="main-content"><div class="container-fluid px-3 px-md-4">
    @php($outstanding = max(0, (float)$supplier->opening_balance + (float)($supplier->purchase_balance ?? 0) - (float)($supplier->unallocated_payments ?? 0)))
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div><h1 class="h3 mb-1">{{ $supplier->name }}</h1><p class="text-muted mb-0">سپلائر کی ادائیگیوں اور لین دین کی تاریخ۔</p></div>
        <div class="d-flex flex-wrap align-items-center" style="gap:8px">
            <button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#editSupplier{{ $supplier->id }}"><i class="fas fa-pen ml-1"></i> سپلائر کی ترمیم</button>
            @if($supplier->active && $outstanding > 0)
                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#paySupplier{{ $supplier->id }}"><i class="fas fa-money-bill-wave ml-1"></i> نئی ادائیگی</button>
            @endif
            <a href="{{ route('admin.suppliers.index') }}" class="btn btn-light">سپلائرز پر واپس جائیں</a>
        </div>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif


    <div class="row mb-3">
        <div class="col-6 col-lg-3 mb-2"><div class="card h-100"><div class="card-body py-3"><small class="text-muted">ابتدائی بقایا</small><div class="h5 mb-0">روپے {{ number_format($supplier->opening_balance,2) }}</div></div></div></div>
        <div class="col-6 col-lg-3 mb-2"><div class="card h-100"><div class="card-body py-3"><small class="text-muted">خریداری بقایا</small><div class="h5 mb-0">روپے {{ number_format($supplier->purchase_balance ?? 0,2) }}</div></div></div></div>
        <div class="col-6 col-lg-3 mb-2"><div class="card h-100"><div class="card-body py-3"><small class="text-muted">عمومی ادائیگیاں</small><div class="h5 mb-0">روپے {{ number_format($supplier->unallocated_payments ?? 0,2) }}</div></div></div></div>
        <div class="col-6 col-lg-3 mb-2"><div class="card h-100 border-{{ $outstanding > 0 ? 'warning' : 'success' }}"><div class="card-body py-3"><small class="text-muted">موجودہ بقایا</small><div class="h5 mb-0">روپے {{ number_format($outstanding,2) }}</div></div></div></div>
    </div>

    <div class="card"><div class="card-header">ادائیگیوں کی تاریخ</div><div class="table-responsive"><table class="table table-hover mb-0 supplier-payment-table">
        <thead class="thead-light"><tr><th>تاریخ</th><th>قسم</th><th>طریقہ</th><th>حوالہ</th><th>نوٹ</th><th>رقم</th></tr></thead>
        <tbody>@forelse($supplier->payments as $payment)<tr>
            <td data-label="تاریخ">{{ $payment->payment_date->format('d-m-Y') }}</td>
            <td data-label="قسم">
                @if($payment->purchase)
                    @if(Auth::user()->hasBusinessPermission('clothing.purchases'))<a href="{{ route('admin.purchases.show', $payment->purchase) }}">{{ $payment->purchase->purchase_number }}</a>@else{{ $payment->purchase->purchase_number }}@endif
                @else عمومی ادائیگی @endif
            </td>
            <td data-label="طریقہ">{{ \App\Support\PaymentMethods::label($payment->payment_method) }}</td>
            <td data-label="حوالہ">{{ $payment->reference ?: '—' }}</td>
            <td data-label="نوٹ">{{ $payment->note ?: '—' }}</td>
            <td data-label="رقم"><strong>روپے {{ number_format($payment->amount,2) }}</strong></td>
        </tr>@empty<tr><td colspan="6" class="text-center text-muted py-4">ابھی کوئی ادائیگی درج نہیں کی گئی۔</td></tr>@endforelse</tbody>
    </table></div></div>
</div>
        <div class="modal fade" id="editSupplier{{ $supplier->id }}" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered" role="document"><div class="modal-content" dir="rtl"><div class="modal-header"><h5 class="modal-title">{{ $supplier->name }} — ترمیم</h5><button type="button" class="close ml-0 mr-auto" data-dismiss="modal" aria-label="بند کریں">&times;</button></div><form method="POST" action="{{ route('admin.suppliers.update', $supplier) }}">@csrf @method('PUT')<div class="modal-body"><div class="form-row"><div class="form-group col-md-6"><label>نام</label><input class="form-control" name="name" value="{{ $supplier->name }}" required maxlength="255"></div><div class="form-group col-md-6"><label>رابطہ شخص</label><input class="form-control" name="contact_person" value="{{ $supplier->contact_person }}"></div><div class="form-group col-md-6"><label>فون</label><input class="form-control" name="phone" value="{{ $supplier->phone }}"></div><div class="form-group col-md-6"><label>ای میل</label><input type="email" class="form-control" name="email" value="{{ $supplier->email }}"></div><div class="form-group col-md-6"><label>ابتدائی بقایا</label><input type="number" step="0.01" min="0" class="form-control" name="opening_balance" value="{{ $supplier->opening_balance }}"></div><div class="form-group col-md-6"><label>پتہ</label><textarea class="form-control" name="address">{{ $supplier->address }}</textarea></div></div><input type="hidden" name="active" value="0"><label><input type="checkbox" name="active" value="1" @checked($supplier->active)> فعال سپلائر</label></div><div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">منسوخ</button><button class="btn btn-primary">محفوظ کریں</button></div></form></div></div></div>
        @if($outstanding > 0 && $supplier->active)
        <div class="modal fade" id="paySupplier{{ $supplier->id }}" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered" role="document"><div class="modal-content" dir="rtl"><div class="modal-header"><h5 class="modal-title">{{ $supplier->name }} — ادائیگی · بقایا Rs. {{ number_format($outstanding,2) }}</h5><button type="button" class="close ml-0 mr-auto" data-dismiss="modal" aria-label="بند کریں">&times;</button></div><form method="POST" action="{{ route('admin.suppliers.payment', $supplier) }}" data-confirm="کیا آپ یہ ادائیگی درج کرنا چاہتے ہیں؟" data-confirm-variant="success">@csrf<div class="modal-body"><div class="form-row"><div class="form-group col-md-6"><label>رقم</label><input class="form-control" type="number" name="amount" min="0.01" max="{{ $outstanding }}" step="0.01" required></div><div class="form-group col-md-6"><label>تاریخ</label><input class="form-control" type="date" name="payment_date" value="{{ now()->toDateString() }}" required></div><x-payment-method-fields prefix="supplier_list_payment_{{ $supplier->id }}" method-name="payment_method" reference-name="reference" method-group-class="form-group col-md-6" reference-group-class="form-group col-md-6" /><div class="form-group col-12"><label>نوٹ</label><input class="form-control" name="note" maxlength="1000"></div></div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">منسوخ</button><button class="btn btn-success">ادائیگی درج کریں</button></div></form></div></div></div>
        @endif

</section>
@endsection
