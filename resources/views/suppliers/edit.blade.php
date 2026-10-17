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
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div><h1 class="h3 mb-1">{{ $supplier->name }}</h1><p class="text-muted mb-0">سپلائر کی ادائیگیوں اور لین دین کی تاریخ۔</p></div>
        <a href="{{ route('admin.suppliers.index') }}" class="btn btn-light">سپلائرز پر واپس جائیں</a>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    @php($outstanding = max(0, (float)$supplier->opening_balance + (float)($supplier->purchase_balance ?? 0) - (float)($supplier->unallocated_payments ?? 0)))
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
</div></section>
@endsection
