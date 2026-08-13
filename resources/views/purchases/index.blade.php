@extends('main')
@push('styles')
    @include('purchases._styles')
@endpush
@section('content')
<section class="main-content purchase-page" dir="rtl"><div class="purchase-shell">
    <header class="purchase-page-header">
        <div class="purchase-title-wrap"><span class="purchase-title-icon"><i class="fas fa-shopping-cart"></i></span><div><h1 class="h3 mb-1">خریداری کی فہرست</h1><div class="purchase-breadcrumb"><a href="{{ route('admin.home') }}">ڈیش بورڈ</a> <span class="mx-2">•</span> خریداری</div></div></div>
        <a href="{{ route('admin.purchases.create') }}" class="purchase-primary-btn"><i class="fas fa-plus"></i> نئی خریداری</a>
    </header>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="purchase-stats">
        <article class="purchase-stat"><div class="purchase-stat-copy"><small>کل خریداریاں</small><strong>{{ number_format($summary['count']) }}</strong></div><span class="purchase-stat-icon"><i class="far fa-clipboard"></i></span></article>
        <article class="purchase-stat"><div class="purchase-stat-copy"><small>کل رقم</small><strong>Rs. {{ number_format($summary['total'],2) }}</strong></div><span class="purchase-stat-icon"><i class="fas fa-arrow-down"></i></span></article>
        <article class="purchase-stat"><div class="purchase-stat-copy"><small>بقیہ ادائیگی</small><strong>Rs. {{ number_format($summary['balance'],2) }}</strong></div><span class="purchase-stat-icon"><i class="fas fa-shopping-cart"></i></span></article>
        <article class="purchase-stat"><div class="purchase-stat-copy"><small>ادا شدہ رقم</small><strong>Rs. {{ number_format($summary['paid'],2) }}</strong></div><span class="purchase-stat-icon"><i class="fas fa-wallet"></i></span></article>
    </div>

    <form class="purchase-panel purchase-filter" method="GET">
        <h2 class="purchase-section-title"><i class="fas fa-filter"></i> فلٹرز</h2>
        <div class="form-row align-items-end">
            <div class="form-group col-xl-3 col-lg-4"><label for="purchase_q">بل نمبر / حوالہ</label><input id="purchase_q" name="q" maxlength="100" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="بل نمبر درج کریں"></div>
            <div class="form-group col-xl-2 col-lg-4"><label for="purchase_supplier">سپلائر</label><select id="purchase_supplier" name="supplier_id" class="form-control"><option value="">تمام سپلائرز</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((int)($filters['supplier_id'] ?? 0)===$supplier->id)>{{ $supplier->name }}</option>@endforeach</select></div>
            <div class="form-group col-xl-2 col-lg-4"><label for="purchase_status">حالت</label><select id="purchase_status" name="status" class="form-control"><option value="">تمام حالتیں</option>@foreach(['draft','received','cancelled'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ['draft'=>'زیرِ تیاری','received'=>'وصول شدہ','cancelled'=>'منسوخ'][$status] }}</option>@endforeach</select></div>
            <div class="form-group col-xl-2 col-lg-4"><label for="purchase_from">تاریخ سے</label><input id="purchase_from" type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}" class="form-control"></div>
            <div class="form-group col-xl-2 col-lg-4"><label for="purchase_to">تاریخ تک</label><input id="purchase_to" type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}" class="form-control"></div>
            <div class="form-group col-xl-1 col-lg-4"><label for="purchase_rows">قطاریں</label><select id="purchase_rows" name="per_page" class="form-control">@foreach([15,25,50,100] as $size)<option value="{{ $size }}" @selected((int)($filters['per_page'] ?? 25)===$size)>{{ $size }}</option>@endforeach</select></div>
        </div>
        <div class="purchase-filter-actions"><button class="btn btn-primary"><i class="fas fa-search ml-1"></i> تلاش کریں</button><a href="{{ route('admin.purchases.index') }}" class="btn btn-outline-secondary"><i class="fas fa-redo ml-1"></i> ری سیٹ کریں</a></div>
    </form>

    <div class="purchase-panel purchase-list-card">
        <div class="purchase-card-head"><div><h2>خریداری کی فہرست</h2><p>کل {{ $purchases->total() }} خریداریوں میں سے {{ $purchases->firstItem() ?? 0 }}–{{ $purchases->lastItem() ?? 0 }}</p></div></div>
        <div class="table-responsive"><table class="table purchase-modern-table purchase-list-table"><thead><tr><th>بل نمبر</th><th>تاریخ</th><th>سپلائر</th><th>حالت</th><th>کل رقم</th><th>ادا شدہ</th><th>بقایا</th><th>عمل</th></tr></thead><tbody>
            @forelse($purchases as $purchase)
                @php($statusLabel=['draft'=>'زیرِ تیاری','received'=>'وصول شدہ','cancelled'=>'منسوخ'][$purchase->status] ?? $purchase->status)
                <tr>
                    <td data-label="بل نمبر"><div class="purchase-number">{{ $purchase->purchase_number }}</div><div class="purchase-ref">{{ $purchase->reference ?: '—' }}</div></td>
                    <td data-label="تاریخ">{{ $purchase->purchase_date->format('d M Y') }}</td><td data-label="سپلائر">{{ $purchase->supplier->name }}</td>
                    <td data-label="حالت"><span class="purchase-status purchase-status-{{ $purchase->status }}">{{ $statusLabel }}</span></td>
                    <td data-label="کل رقم"><span class="purchase-money">Rs. {{ number_format($purchase->total_amount,2) }}</span></td><td data-label="ادا شدہ"><span class="purchase-money">Rs. {{ number_format($purchase->paid_amount,2) }}</span></td><td data-label="بقایا"><span class="purchase-money purchase-balance">Rs. {{ number_format($purchase->balance_amount,2) }}</span></td>
                    <td class="purchase-list-action" data-label="عمل"><a class="purchase-open" href="{{ route('admin.purchases.show',$purchase) }}" aria-label="خریداری کھولیں"><i class="fas fa-ellipsis-v"></i></a></td>
                </tr>
            @empty<tr><td colspan="8" class="text-center text-muted py-5">منتخب فلٹر کے مطابق کوئی خریداری موجود نہیں۔</td></tr>@endforelse
        </tbody></table></div>
        @if($purchases->hasPages())<div class="card-footer">{{ $purchases->links() }}</div>@endif
    </div>
</div></section>
@endsection
