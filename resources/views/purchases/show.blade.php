@extends('main')

@push('styles')
@include('purchases._styles')
<style>
    .purchase-show-header{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;margin-bottom:18px}.purchase-show-meta{display:flex;align-items:center;gap:14px;padding:18px 20px;min-width:360px}.purchase-show-meta h1{font:800 1.35rem/1.25 Arial,sans-serif}.purchase-status-check{display:inline-grid;place-items:center;width:22px;height:22px;margin-left:5px;border-radius:50%;background:#19a95b;color:#fff}.purchase-show-actions{display:flex;gap:10px;flex-wrap:wrap}.purchase-show-actions .btn{min-height:44px;border-radius:8px;padding:9px 16px;font-weight:700}.purchase-detail-summary .purchase-stat{min-height:108px}.purchase-detail-card{margin-bottom:18px;overflow:hidden}.purchase-detail-title{display:flex;align-items:center;justify-content:space-between;padding:18px 20px;border-bottom:1px solid var(--purchase-line)}.purchase-detail-title h2{margin:0;color:#1769ef;font-size:1.12rem;font-weight:800}.purchase-item-table{margin:0}.purchase-item-table thead th,.purchase-history-table thead th{border:0;background:#f8fafd;color:#52627b;font-weight:800;white-space:nowrap}.purchase-item-table td,.purchase-item-table th{padding:14px 16px;vertical-align:middle;border-color:#edf1f6}.purchase-item-name strong{display:block;color:var(--purchase-ink)}.purchase-item-name small{color:var(--purchase-muted)}.purchase-quantity{font-weight:800}.purchase-quantity.is-received{color:#18a866}.purchase-quantity.is-returned{color:#db3f4d}.purchase-return-form{display:flex;min-width:205px}.purchase-return-form .form-control{min-height:38px;border-radius:7px 0 0 7px}.purchase-return-form .btn{white-space:nowrap;border-radius:0 7px 7px 0}.purchase-totals-strip{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:15px;margin:16px;padding:15px 18px;border:1px solid var(--purchase-line);border-radius:10px;background:#fbfcff}.purchase-totals-strip span{color:#52627b;font-weight:700}.purchase-totals-strip strong{margin-right:5px;color:var(--purchase-ink)}.purchase-totals-strip .total-primary strong{color:#1769ef}.purchase-totals-strip .total-success strong{color:#18a866}.purchase-totals-strip .total-danger strong{color:#db3f4d}.purchase-lower-grid{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(360px,.8fr);gap:18px}.purchase-form-panel,.purchase-history-card{padding:20px}.purchase-form-panel h2,.purchase-history-card h2{margin:0 0 18px;color:#1769ef;font-size:1.12rem;font-weight:800}.purchase-history-stack{display:grid;gap:18px}.purchase-history-card{padding:0;overflow:hidden}.purchase-history-card h2{padding:16px 18px;margin:0;border-bottom:1px solid var(--purchase-line)}.purchase-history-table{margin:0}.purchase-history-table td,.purchase-history-table th{padding:11px 14px;border-color:#edf1f6}.purchase-empty{padding:26px!important;color:#9ba8ba;text-align:center}.purchase-empty i{display:block;margin-bottom:8px;font-size:25px}.purchase-draft-notice{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:18px;padding:15px 18px}.purchase-draft-notice p{margin:0;color:#52627b}.purchase-draft-actions{display:flex;gap:8px;flex-shrink:0}
    @media print{.tms-sidebar,.tms-nav,.purchase-show-actions,.purchase-return-action,.purchase-draft-notice,.purchase-form-panel{display:none!important}body{padding:0!important}.purchase-page{padding:0;background:#fff}.purchase-shell{max-width:none}.purchase-panel,.purchase-stat{box-shadow:none!important}}
    @media(max-width:991.98px){.purchase-show-header{flex-direction:column}.purchase-show-meta{width:100%;min-width:0}.purchase-lower-grid{grid-template-columns:1fr}.purchase-totals-strip{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:767.98px){.purchase-show-actions{width:100%}.purchase-show-actions .btn{flex:1}.purchase-item-table,.purchase-item-table tbody,.purchase-item-table tr,.purchase-item-table td{display:block;width:100%}.purchase-item-table thead{display:none}.purchase-item-table tr{width:calc(100% - 20px);margin:10px;border:1px solid var(--purchase-line);border-radius:10px;padding:7px}.purchase-item-table td{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:10px;border-top:1px solid #edf1f6}.purchase-item-table td:first-child{border-top:0}.purchase-item-table td::before{content:attr(data-label);color:var(--purchase-muted);font-weight:800}.purchase-item-table .purchase-return-action{display:block}.purchase-item-table .purchase-return-action::before{display:none}.purchase-return-form{width:100%}.purchase-totals-strip{grid-template-columns:1fr}.purchase-draft-notice{align-items:stretch;flex-direction:column}.purchase-draft-actions{flex-direction:column}}
</style>
@endpush

@section('content')
@php
    $statusLabels = ['draft' => 'زیرِ تیاری', 'received' => 'وصول شدہ', 'cancelled' => 'منسوخ'];
    $statusLabel = $statusLabels[$purchase->status] ?? $purchase->status;
    $orderedQuantity = $purchase->items->sum('quantity');
    $receivedQuantity = $purchase->items->sum('received_quantity');
    $returnedQuantity = $purchase->items->sum('returned_quantity');
@endphp
<section class="main-content purchase-page" dir="rtl">
<div class="purchase-shell">
    <div class="purchase-breadcrumb mb-3"><a href="{{ route('admin.purchases.index') }}">خریداری</a><span class="mx-2">‹</span>خریداری کی فہرست<span class="mx-2">‹</span>خریداری کی تفصیل</div>

    <header class="purchase-show-header">
        <div class="purchase-panel purchase-show-meta">
            <div class="flex-grow-1"><h1 class="h3 mb-1">{{ $purchase->purchase_number }}</h1><div class="purchase-breadcrumb">{{ $purchase->supplier->name }} <span class="mx-2">•</span> {{ $purchase->purchase_date->format('d M Y') }}</div></div>
            <span class="purchase-status purchase-status-{{ $purchase->status }}">@if($purchase->status === 'received')<span class="purchase-status-check"><i class="fas fa-check"></i></span>@endif{{ $statusLabel }}</span>
        </div>
        <div class="purchase-show-actions">
            <a href="{{ route('admin.purchases.create') }}" class="purchase-primary-btn"><i class="fas fa-plus"></i> نئی خریداری</a>
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="fas fa-print ml-1"></i> پرنٹ</button>
            <a href="{{ route('admin.purchases.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-right ml-1"></i> فہرست</a>
        </div>
    </header>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="purchase-stats purchase-detail-summary">
        <article class="purchase-stat"><div class="purchase-stat-copy"><small>حالت</small><strong>{{ $statusLabel }}</strong></div><span class="purchase-stat-icon"><i class="fas fa-box-open"></i></span></article>
        <article class="purchase-stat"><div class="purchase-stat-copy"><small>کل رقم</small><strong>Rs. {{ number_format($purchase->total_amount, 2) }}</strong></div><span class="purchase-stat-icon"><i class="fas fa-file-invoice-dollar"></i></span></article>
        <article class="purchase-stat"><div class="purchase-stat-copy"><small>ادائیگی</small><strong>Rs. {{ number_format($purchase->paid_amount, 2) }}</strong></div><span class="purchase-stat-icon"><i class="fas fa-arrow-down"></i></span></article>
        <article class="purchase-stat"><div class="purchase-stat-copy"><small>بقایا ادائیگی</small><strong>Rs. {{ number_format($purchase->balance_amount, 2) }}</strong></div><span class="purchase-stat-icon"><i class="fas fa-wallet"></i></span></article>
    </div>

    @if($purchase->status === 'draft')
        <div class="purchase-panel purchase-draft-notice"><p><i class="fas fa-info-circle text-primary ml-1"></i> مال وصول کرنے پر تمام آئٹمز اسٹاک میں شامل ہو جائیں گے۔</p><div class="purchase-draft-actions"><form method="POST" action="{{ route('admin.purchases.receive', $purchase) }}" data-confirm="کیا تمام مال وصول کر کے اسٹاک اپ ڈیٹ کرنا ہے؟" data-confirm-variant="success">@csrf @method('PATCH')<button class="btn btn-success">مال وصول کریں</button></form><form method="POST" action="{{ route('admin.purchases.cancel', $purchase) }}" data-confirm="کیا یہ خریداری منسوخ کرنا ہے؟">@csrf @method('PATCH')<button class="btn btn-outline-danger">منسوخ کریں</button></form></div></div>
    @endif

    <section class="purchase-panel purchase-detail-card">
        <div class="purchase-detail-title"><h2>آئٹمز کی تفصیل</h2><span class="purchase-breadcrumb">کل {{ $purchase->items->count() }} آئٹمز</span></div>
        <div class="table-responsive"><table class="table purchase-item-table"><thead><tr><th>#</th><th>آئٹم</th><th>آرڈر شدہ</th><th>وصول شدہ</th><th>واپس شدہ</th><th>فی یونٹ قیمت</th><th>واپسی کے بعد رقم</th>@if($purchase->status === 'received')<th>عمل</th>@endif</tr></thead><tbody>
            @foreach($purchase->items as $item)
                <tr>
                    <td data-label="#">{{ $loop->iteration }}</td>
                    <td data-label="آئٹم" class="purchase-item-name"><strong>{{ $item->cloth->brand->name ?? 'برانڈ' }}</strong><small>{{ $item->cloth->type->name ?? 'قسم' }} / {{ $item->color }}</small></td>
                    <td data-label="آرڈر شدہ"><span class="purchase-quantity">{{ number_format($item->quantity, 2) }} میٹر</span></td>
                    <td data-label="وصول شدہ"><span class="purchase-quantity is-received">{{ number_format($item->received_quantity, 2) }} میٹر</span></td>
                    <td data-label="واپس شدہ"><span class="purchase-quantity is-returned">{{ number_format($item->returned_quantity, 2) }} میٹر</span></td>
                    <td data-label="فی اکائی لاگت">روپے {{ number_format($item->unit_cost, 2) }}</td>
                    <td data-label="واپسی کے بعد رقم">روپے {{ number_format((float) $item->line_total - ((float) $item->returned_quantity * (float) $item->unit_cost), 2) }}</td>
                    @if($purchase->status === 'received')
                        <td class="purchase-return-action" data-label="مال واپس کریں">
                            @if((float) $item->received_quantity > (float) $item->returned_quantity)
                                <form class="purchase-return-form" method="POST" action="{{ route('admin.purchases.return', $purchase) }}" data-confirm="کیا منتخب مقدار سپلائر کو واپس کر کے اسٹاک اور بقایا کم کرنا ہے؟">@csrf<input type="hidden" name="purchase_item_id" value="{{ $item->id }}"><input type="hidden" name="return_date" value="{{ now()->toDateString() }}"><input type="number" name="quantity" step="0.01" min="0.01" max="{{ (float) $item->received_quantity - (float) $item->returned_quantity }}" class="form-control" aria-label="واپسی کی مقدار میٹر میں" placeholder="میٹر" required><button class="btn btn-outline-danger">واپس کریں</button></form>
                            @else<span class="text-muted">مکمل واپس شدہ</span>@endif
                        </td>
                    @endif
                </tr>
            @endforeach
        </tbody></table></div>
        <div class="purchase-totals-strip"><span>کل آئٹمز:<strong>{{ $purchase->items->count() }}</strong></span><span class="total-primary">آرڈر شدہ:<strong>{{ number_format($orderedQuantity, 2) }} میٹر</strong></span><span class="total-success">وصول شدہ:<strong>{{ number_format($receivedQuantity, 2) }} میٹر</strong></span><span class="total-danger">واپس شدہ:<strong>{{ number_format($returnedQuantity, 2) }} میٹر</strong></span></div>
    </section>

    <div class="purchase-lower-grid">
        <div>
            @if($purchase->status === 'received' && (float) $purchase->balance_amount > 0)
                <section class="purchase-panel purchase-form-panel">
                    <h2>سپلائر ادائیگی درج کریں</h2>
                    <form method="POST" action="{{ route('admin.purchases.payment', $purchase) }}">@csrf
                        <div class="form-row"><div class="form-group col-md-6"><label>رقم</label><input type="number" name="amount" step="0.01" min="0.01" max="{{ max(0, (float) $purchase->balance_amount) }}" class="form-control" required></div><div class="form-group col-md-6"><label>تاریخ</label><input type="date" name="payment_date" value="{{ now()->toDateString() }}" class="form-control" required></div><div class="form-group col-md-6"><label>ادائیگی کا طریقہ</label><select name="payment_method" class="form-control" required>@foreach(\App\Support\PaymentMethods::LABELS as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div><div class="form-group col-md-6"><label>حوالہ / ٹرانزیکشن نمبر</label><input name="reference" maxlength="255" class="form-control" placeholder="حوالہ نمبر درج کریں"></div></div>
                        <button class="btn btn-success px-4"><i class="fas fa-check ml-1"></i> ادائیگی جمع کریں</button>
                    </form>
                </section>
            @else
                <section class="purchase-panel purchase-form-panel"><h2>خریداری کی معلومات</h2><div class="row"><div class="col-md-6 mb-3"><small class="text-muted d-block">سپلائر</small><strong>{{ $purchase->supplier->name }}</strong></div><div class="col-md-6 mb-3"><small class="text-muted d-block">خریداری کی تاریخ</small><strong>{{ $purchase->purchase_date->format('d-m-Y') }}</strong></div><div class="col-12"><small class="text-muted d-block">حوالہ</small><strong>{{ $purchase->reference ?: '—' }}</strong></div></div></section>
            @endif
        </div>

        <div class="purchase-history-stack">
            <section class="purchase-panel purchase-history-card"><h2>واپسی کی تاریخ</h2><div class="table-responsive"><table class="table table-sm purchase-history-table"><thead><tr><th>#</th><th>تاریخ</th><th>واپس شدہ رقم</th></tr></thead><tbody>@forelse($purchase->returns as $return)<tr><td>{{ $return->return_number }}</td><td>{{ $return->return_date->format('d-m-Y') }}</td><td>روپے {{ number_format($return->total_amount, 2) }}</td></tr>@empty<tr><td colspan="3" class="purchase-empty"><i class="fas fa-inbox"></i>کوئی واپسی ریکارڈ موجود نہیں</td></tr>@endforelse</tbody></table></div></section>
            <section class="purchase-panel purchase-history-card"><h2>ادائیگیوں کی تاریخ</h2><div class="table-responsive"><table class="table table-sm purchase-history-table"><thead><tr><th>تاریخ</th><th>طریقہ</th><th>رقم</th></tr></thead><tbody>@forelse($purchase->payments as $payment)<tr><td>{{ $payment->payment_date->format('d-m-Y') }}</td><td>{{ \App\Support\PaymentMethods::LABELS[$payment->payment_method] ?? 'درج نہیں' }}</td><td>روپے {{ number_format($payment->amount, 2) }}</td></tr>@empty<tr><td colspan="3" class="purchase-empty"><i class="fas fa-inbox"></i>کوئی ادائیگی ریکارڈ موجود نہیں</td></tr>@endforelse</tbody></table></div></section>
        </div>
    </div>
</div>
</section>
@endsection
