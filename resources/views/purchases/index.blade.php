@extends('main')
@push('styles')
<style>
@media (max-width: 767.98px) {
    .purchase-list-table, .purchase-list-table tbody, .purchase-list-table tr, .purchase-list-table td { display:block; width:100%; }
    .purchase-list-table thead { display:none; }
    .purchase-list-table tr { border:1px solid #e3e8ee; border-radius:.65rem; margin:.75rem; padding:.4rem .75rem; width:calc(100% - 1.5rem); }
    .purchase-list-table td { display:flex; justify-content:space-between; align-items:center; gap:1rem; border-top:1px solid #eef1f4; padding:.7rem 0; }
    .purchase-list-table td:first-child { border-top:0; }
    .purchase-list-table td::before { content:attr(data-label); flex:0 0 38%; color:#6c757d; font-weight:700; }
    .purchase-list-table .purchase-list-action::before { display:none; }
    .purchase-list-table .purchase-list-action .btn { width:100%; }
}
</style>
@endpush
@section('content')
<section class="main-content"><div class="container-fluid px-3 px-md-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div><h1 class="h3 mb-1">خریداریاں</h1><p class="text-muted mb-0">خریداری آرڈرز، مال کی وصولی، واپسی اور سپلائر بقایا جات۔</p></div>
        <a href="{{ route('admin.purchases.create') }}" class="btn btn-primary">نئی خریداری</a>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form class="card card-body mb-3" method="GET">
        <div class="form-row align-items-end">
            <div class="form-group col-lg-3"><label>تلاش</label><input name="q" maxlength="100" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="خریداری نمبر، حوالہ یا سپلائر"></div>
            <div class="form-group col-lg-2"><label>حالت</label><select name="status" class="form-control"><option value="">تمام حالتیں</option>@foreach(['draft','received','cancelled'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ['draft'=>'زیرِ تیاری','received'=>'وصول شدہ','cancelled'=>'منسوخ'][$status] }}</option>@endforeach</select></div>
            <div class="form-group col-lg-2"><label>سپلائر</label><select name="supplier_id" class="form-control"><option value="">تمام سپلائرز</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((int)($filters['supplier_id'] ?? 0)===$supplier->id)>{{ $supplier->name }}</option>@endforeach</select></div>
            <div class="form-group col-lg-2"><label>شروع تاریخ</label><input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}" class="form-control"></div>
            <div class="form-group col-lg-2"><label>آخری تاریخ</label><input type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}" class="form-control"></div>
            <div class="form-group col-lg-1"><label>قطاریں</label><select name="per_page" class="form-control">@foreach([15,25,50,100] as $size)<option value="{{ $size }}" @selected((int)($filters['per_page'] ?? 25)===$size)>{{ $size }}</option>@endforeach</select></div>
        </div>
        <div><button class="btn btn-primary">فلٹر کریں</button> <a href="{{ route('admin.purchases.index') }}" class="btn btn-light">صاف کریں</a></div>
    </form>
    <div class="card">
        <div class="card-header py-2 text-muted small">کل {{ $purchases->total() }} خریداریوں میں سے {{ $purchases->firstItem() ?? 0 }}–{{ $purchases->lastItem() ?? 0 }} دکھائی جا رہی ہیں</div>
        <div class="table-responsive"><table class="table table-hover mb-0 purchase-list-table"><thead class="thead-light"><tr><th>نمبر</th><th>تاریخ</th><th>سپلائر</th><th>حالت</th><th>کل رقم</th><th>ادا شدہ</th><th>بقایا</th><th>عمل</th></tr></thead><tbody>
            @forelse($purchases as $purchase)<tr><td data-label="نمبر"><strong>{{ $purchase->purchase_number }}</strong><br><small>{{ $purchase->reference }}</small></td><td data-label="تاریخ">{{ $purchase->purchase_date->format('d M Y') }}</td><td data-label="سپلائر">{{ $purchase->supplier->name }}</td><td data-label="حالت"><span class="badge badge-{{ $purchase->status==='received'?'success':($purchase->status==='draft'?'warning':'secondary') }}">{{ ['draft'=>'زیرِ تیاری','received'=>'وصول شدہ','cancelled'=>'منسوخ'][$purchase->status] ?? $purchase->status }}</span></td><td data-label="کل رقم">روپے {{ number_format($purchase->total_amount,2) }}</td><td data-label="ادا شدہ">روپے {{ number_format($purchase->paid_amount,2) }}</td><td data-label="بقایا">روپے {{ number_format($purchase->balance_amount,2) }}</td><td class="purchase-list-action" data-label="عمل"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.purchases.show',$purchase) }}">کھولیں</a></td></tr>
            @empty<tr><td colspan="8" class="text-center text-muted py-5">منتخب فلٹر کے مطابق کوئی خریداری موجود نہیں۔</td></tr>@endforelse
        </tbody></table></div>
        @if($purchases->hasPages())<div class="card-footer">{{ $purchases->links() }}</div>@endif
    </div>
</div></section>
@endsection
