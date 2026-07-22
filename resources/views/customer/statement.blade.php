@extends('main')
@section('content')
@php
    $statusLabels = ['assigned' => 'تفویض شدہ', 'cutting' => 'کٹائی', 'stitching' => 'سلائی', 'trial' => 'ٹرائل', 'ready' => 'تیار', 'delivered' => 'حوالے شدہ'];
@endphp
<section class="main-content customer-workspace" dir="rtl">
    <div class="container-fluid px-3 px-lg-5 py-4">
        <div class="customer-hero mb-4">
            <div class="row align-items-center">
                <div class="col-lg-8 d-flex align-items-center">
                    <div class="customer-avatar">{{ \Illuminate\Support\Str::substr($customer->name, 0, 1) }}</div>
                    <div><span class="badge badge-light text-primary mb-2">مشترکہ گاہک پروفائل</span><h1 class="h3 font-weight-bold mb-1">{{ $customer->name }}</h1><p class="mb-0">{{ $customer->phone_number1 }} · ٹیلرنگ اور کپڑے کی دکان کا ایک ریکارڈ</p></div>
                </div>
                <div class="col-lg-4 text-lg-left mt-3 mt-lg-0">
                    @if($canManageMeasurements)<a href="{{ route('admin.Customers.edit', $customer) }}" class="btn btn-light ml-2"><i class="fas fa-edit ml-1"></i> معلومات تبدیل کریں</a>@endif
                    <a href="{{ route('admin.workspace.current') }}" class="btn btn-outline-light">ڈیش بورڈ</a>
                </div>
            </div>
        </div>

        @if(session('insert'))<div class="alert alert-success">{{ session('insert') }}</div>@endif
        @if(session('balanceError'))<div class="alert alert-danger">{{ session('balanceError') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <nav class="customer-tabs mb-4" aria-label="گاہک پروفائل">
            @foreach($tabs as $key => $tab)
                <a class="customer-tab {{ $activeTab === $key ? 'active' : '' }}" href="{{ route('admin.customers.statement', ['id' => $customer->id, 'tab' => $key]) }}"><i class="fas {{ $tab['icon'] }}"></i><span>{{ $tab['label'] }}</span></a>
            @endforeach
        </nav>

        @if($activeTab === 'overview')
            <div class="row mb-2">
                @if($canViewBalances)
                    <div class="col-md-6 col-xl-3 mb-3"><div class="profile-stat balance"><span class="stat-icon"><i class="fas fa-wallet"></i></span><small>کل مشترکہ بقایا</small><strong>Rs {{ number_format($totalBalance, 2) }}</strong><span>دکان اور ٹیلرنگ کا مجموعہ</span></div></div>
                    <div class="col-md-6 col-xl-3 mb-3"><div class="profile-stat paid"><span class="stat-icon"><i class="fas fa-hand-holding-usd"></i></span><small>کل وصول شدہ رقم</small><strong>Rs {{ number_format($totalReceived, 2) }}</strong><span>ابتدائی اور بعد کی ادائیگیاں</span></div></div>
                @endif
                @if($canViewTailoring)<div class="col-md-6 col-xl-3 mb-3"><div class="profile-stat"><span class="stat-icon"><i class="fas fa-cut"></i></span><small>ٹیلرنگ آرڈرز</small><strong>{{ $orders->count() }}</strong><a href="{{ route('admin.customers.statement', ['id' => $customer->id, 'tab' => 'tailoring']) }}">تمام آرڈرز دیکھیں</a></div></div>@endif
                @if($canViewShop)<div class="col-md-6 col-xl-3 mb-3"><div class="profile-stat"><span class="stat-icon"><i class="fas fa-shopping-bag"></i></span><small>حالیہ دکان خریداری</small><strong>{{ $sales->count() }}</strong><a href="{{ route('admin.customers.statement', ['id' => $customer->id, 'tab' => 'shop']) }}">خریداری دیکھیں</a></div></div>@endif
            </div>

            @unless($canViewBalances)<div class="alert alert-secondary"><i class="fas fa-lock ml-1"></i> آپ کے رول کو گاہک کا بقایا اور ادائیگیاں دیکھنے کی اجازت نہیں ہے۔ دیگر اجازت یافتہ ریکارڈ نیچے دستیاب ہیں۔</div>@endunless

            <div class="row">
                @if($canViewTailoring)
                    <div class="col-lg-6 mb-4"><div class="card workspace-card h-100"><div class="card-body p-4"><div class="section-heading"><div><h2 class="h5 font-weight-bold">حالیہ ٹیلرنگ آرڈرز</h2><p>سلائی کے تازہ ترین کام اور موجودہ حالت</p></div><a href="{{ route('admin.customers.statement', ['id' => $customer->id, 'tab' => 'tailoring']) }}">تمام دیکھیں</a></div>
                        @forelse($orders->take(5) as $order)<div class="activity-row"><span class="activity-icon tailoring"><i class="fas fa-cut"></i></span><div class="flex-grow-1"><strong>آرڈر #{{ $order->id }} · {{ $order->suitQuantity ?: 1 }} سوٹ</strong><small>{{ $order->created_at?->format('d-m-Y') }} · {{ $statusLabels[$order->status] ?? $order->status }}</small></div><strong>Rs {{ number_format((float) $order->totalPayment, 0) }}</strong></div>@empty<div class="empty-state">ابھی کوئی ٹیلرنگ آرڈر موجود نہیں۔</div>@endforelse
                    </div></div></div>
                @endif
                @if($canViewShop)
                    <div class="col-lg-6 mb-4"><div class="card workspace-card h-100"><div class="card-body p-4"><div class="section-heading"><div><h2 class="h5 font-weight-bold">حالیہ کپڑے کی خریداری</h2><p>فروخت، اشیاء اور واجب الادا رقم</p></div><a href="{{ route('admin.customers.statement', ['id' => $customer->id, 'tab' => 'shop']) }}">تمام دیکھیں</a></div>
                        @forelse($sales->take(5) as $sale)<div class="activity-row"><span class="activity-icon shop"><i class="fas fa-shopping-bag"></i></span><div class="flex-grow-1"><strong>رسید #{{ $sale->id }} · {{ $sale->items_count }} اشیاء</strong><small>{{ $sale->created_at?->format('d-m-Y') }} · {{ $sale->summary ?: 'کپڑے کی فروخت' }}</small></div><strong>Rs {{ number_format($sale->amount, 0) }}</strong></div>@empty<div class="empty-state">ابھی کوئی دکان خریداری موجود نہیں۔</div>@endforelse
                    </div></div></div>
                @endif
            </div>
        @endif

        @if($activeTab === 'transactions' && $canViewBalances)
            <div class="row">
                <div class="col-xl-8 mb-4"><div class="card workspace-card"><div class="card-body p-0"><div class="section-heading p-4 mb-0"><div><h2 class="h5 font-weight-bold">مشترکہ کھاتہ</h2><p>ہر اندراج پر شعبہ درج ہے، لیکن بقایا ایک مجموعی رقم ہے۔</p></div><span class="balance-pill">بقایا: Rs {{ number_format($totalBalance, 2) }}</span></div><div class="table-responsive"><table class="table table-hover mb-0 text-right"><thead><tr><th>تاریخ</th><th>شعبہ</th><th>حوالہ</th><th>وصول شدہ</th><th>بقایا تبدیلی</th><th>تفصیل</th></tr></thead><tbody>
                    @forelse($transactions as $transaction)<tr><td>{{ $transaction->created_at?->format('d-m-Y') }}</td><td><span class="type-badge type-{{ strtolower($transaction->Order_type) }}">{{ $transaction->Order_type === 'Tailor' ? 'ٹیلرنگ' : ($transaction->Order_type === 'Sale' ? 'دکان' : ($transaction->Order_type === 'Payment' ? 'مشترکہ ادائیگی' : 'دیگر')) }}</span></td><td>{{ $transaction->orderId ? 'آرڈر #'.$transaction->orderId : ($transaction->sale_id ? 'فروخت #'.$transaction->sale_id : 'ادائیگی') }}</td><td>Rs {{ number_format((float) $transaction->recivedPayment, 2) }}</td><td class="{{ (float) $transaction->remainingBalance < 0 ? 'text-success' : 'text-danger' }}">Rs {{ number_format((float) $transaction->remainingBalance, 2) }}</td><td>{{ $transaction->comment ?: '—' }}</td></tr>
                    @empty<tr><td colspan="6" class="empty-state">ابھی کوئی لین دین موجود نہیں۔</td></tr>@endforelse
                    </tbody></table></div>@if($transactions->hasPages())<div class="card-footer">{{ $transactions->links() }}</div>@endif</div></div></div>
                <div class="col-xl-4 mb-4">
                    <div class="card workspace-card mb-3"><div class="card-body p-4"><small class="text-muted">موجودہ مشترکہ بقایا</small><div class="display-4 font-weight-bold text-primary">Rs {{ number_format($totalBalance, 2) }}</div><p class="text-muted mb-0">یہ رقم ٹیلرنگ اور کپڑے کی دکان دونوں کی مجموعی واجب الادا رقم ہے۔</p></div></div>
                    @if($paymentRoute && $totalBalance > 0)<div class="card workspace-card"><div class="card-body p-4"><h2 class="h5 font-weight-bold">نئی ادائیگی درج کریں</h2><p class="text-muted small">فی الحال ادائیگی مشترکہ بقایا میں جمع ہوگی۔</p><form method="POST" action="{{ $paymentRoute }}">@csrf<input type="hidden" name="customer_id" value="{{ $customer->id }}"><input type="hidden" name="return_to_statement" value="1"><div class="form-group"><label>وصول شدہ رقم</label><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">Rs</span></div><input type="number" class="form-control" name="DirectPayment" min="0.01" max="{{ $totalBalance }}" step="0.01" required></div></div><div class="form-group"><label>نوٹ <small class="text-muted">(اختیاری)</small></label><textarea class="form-control" name="comment" rows="3" placeholder="مثلاً نقد ادائیگی"></textarea></div><button class="btn btn-primary btn-block">ادائیگی محفوظ کریں</button></form></div></div>@endif
                </div>
            </div>
        @endif

        @if($activeTab === 'tailoring' && $canViewTailoring)
            <div class="card workspace-card"><div class="card-body p-0"><div class="section-heading p-4 mb-0"><div><h2 class="h5 font-weight-bold">ٹیلرنگ آرڈرز</h2><p>گاہک کے تمام حالیہ سلائی آرڈرز اور کام کی حالت</p></div><span class="badge badge-primary px-3 py-2">{{ $orders->count() }} آرڈرز</span></div><div class="table-responsive"><table class="table table-hover mb-0 text-right"><thead><tr><th>آرڈر</th><th>تاریخ</th><th>سوٹ</th><th>کل رقم</th><th>بقایا</th><th>درزی</th><th>حالت</th><th></th></tr></thead><tbody>
                @forelse($orders as $order)<tr><td>#{{ $order->id }}</td><td>{{ $order->created_at?->format('d-m-Y') }}</td><td>{{ $order->suitQuantity ?: 1 }}</td><td>Rs {{ number_format((float) $order->totalPayment, 2) }}</td><td>Rs {{ number_format((float) ($order->outstanding_amount ?? 0), 2) }}</td><td>{{ $order->tailor?->name ?: 'مقرر نہیں' }}</td><td><span class="status-badge">{{ $statusLabels[$order->status] ?? $order->status }}</span></td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.order-print', $order) }}">رسید</a></td></tr>
                @empty<tr><td colspan="8" class="empty-state">ابھی کوئی ٹیلرنگ آرڈر موجود نہیں۔</td></tr>@endforelse
                </tbody></table></div></div></div>
        @endif

        @if($activeTab === 'shop' && $canViewShop)
            <div class="card workspace-card"><div class="card-body p-0"><div class="section-heading p-4 mb-0"><div><h2 class="h5 font-weight-bold">کپڑے کی خریداری</h2><p>دکان سے خریدی گئی اشیاء، رقم اور بقایا</p></div><span class="badge badge-primary px-3 py-2">{{ $sales->count() }} رسیدیں</span></div><div class="table-responsive"><table class="table table-hover mb-0 text-right"><thead><tr><th>رسید</th><th>تاریخ</th><th>تفصیل</th><th>اشیاء</th><th>کل رقم</th><th>وصول شدہ</th><th>بقایا</th><th></th></tr></thead><tbody>
                @forelse($sales as $sale)<tr><td>#{{ $sale->id }}</td><td>{{ $sale->created_at?->format('d-m-Y') }}</td><td>{{ $sale->summary ?: 'کپڑے کی فروخت' }}</td><td>{{ $sale->items_count }}</td><td>Rs {{ number_format($sale->amount, 2) }}</td><td>Rs {{ number_format($sale->received, 2) }}</td><td>Rs {{ number_format($sale->balance, 2) }}</td><td>@if($sale->source === 'stock')<a class="btn btn-sm btn-outline-primary" href="{{ route('admin.printStock', ['id' => $sale->id, 'customerId' => $customer->id]) }}">رسید</a>@else<span class="text-muted">—</span>@endif</td></tr>
                @empty<tr><td colspan="8" class="empty-state">ابھی کوئی کپڑے کی خریداری موجود نہیں۔</td></tr>@endforelse
                </tbody></table></div></div></div>
        @endif

        @if($activeTab === 'measurements' && $canManageMeasurements)
            <div class="card workspace-card"><div class="card-body p-4 p-lg-5"><div class="section-heading"><div><h2 class="h4 font-weight-bold">محفوظ شدہ پیمائش</h2><p>سسٹم اور کاروبار کے اپنے اضافی پیمائشی خانے</p></div><a class="btn btn-outline-primary" href="{{ route('admin.Customers.edit', $customer) }}"><i class="fas fa-edit ml-1"></i> پیمائش تبدیل کریں</a></div>
                <h3 class="h6 font-weight-bold text-muted mt-4 mb-3">بنیادی پیمائش</h3><div class="measurement-grid">@forelse($systemMeasurements as $measurement)<div class="measurement-item"><small>{{ $measurement['label'] }}</small><strong>{{ $measurement['value'] }} <span>{{ $measurement['unit'] === 'inch' ? 'انچ' : $measurement['unit'] }}</span></strong></div>@empty<div class="empty-state grid-full">بنیادی پیمائش درج نہیں کی گئی۔</div>@endforelse</div>
                <h3 class="h6 font-weight-bold text-muted mt-5 mb-3">اضافی پیمائش</h3><div class="measurement-grid">@forelse($customMeasurements as $measurement)<div class="measurement-item custom"><small>{{ $measurement->field->label }}</small><strong>{{ $measurement->value }} @if($measurement->field->unit !== 'none')<span>{{ $measurement->field->unit === 'inch' ? 'انچ' : $measurement->field->unit }}</span>@endif</strong></div>@empty<div class="empty-state grid-full">اس گاہک کے لیے اضافی پیمائش درج نہیں کی گئی۔</div>@endforelse</div>
            </div></div>
        @endif

        @if($activeTab === 'profile')
            <div class="row"><div class="col-xl-8"><div class="card workspace-card"><div class="card-body p-4 p-lg-5"><div class="section-heading"><div><h2 class="h4 font-weight-bold">ذاتی معلومات</h2><p>تمام نظاموں میں استعمال ہونے والی مشترکہ شناخت</p></div>@if($canManageMeasurements)<a class="btn btn-outline-primary" href="{{ route('admin.Customers.edit', $customer) }}">تبدیل کریں</a>@endif</div><div class="profile-details"><div><small>نام</small><strong>{{ $customer->name }}</strong></div><div><small>فون نمبر</small><strong dir="ltr">{{ $customer->phone_number1 }}</strong></div><div><small>گاہک نمبر</small><strong>#{{ $customer->id }}</strong></div><div><small>شامل ہونے کی تاریخ</small><strong>{{ $customer->created_at?->format('d-m-Y') }}</strong></div><div class="wide"><small>نوٹ</small><strong>{{ $customer->note ?: 'کوئی نوٹ درج نہیں۔' }}</strong></div></div></div></div></div></div>
        @endif
    </div>
</section>

<style>
    .customer-workspace{background:#f4f7fa;min-height:calc(100vh - 70px);color:#243b53}.customer-hero{background:linear-gradient(135deg,#102a43,#1769aa);color:#fff;border-radius:22px;padding:1.7rem 2rem;box-shadow:0 15px 35px rgba(16,42,67,.16)}
    .customer-hero h1{color:#fff!important}.customer-hero p{color:rgba(255,255,255,.78)}.customer-avatar{width:65px;height:65px;border-radius:19px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:1.8rem;font-weight:800;margin-left:1rem;flex:0 0 auto}
    .customer-tabs{display:flex;gap:.55rem;overflow-x:auto;padding:.15rem}.customer-tab{display:flex;align-items:center;gap:.45rem;white-space:nowrap;padding:.7rem 1rem;border-radius:999px;border:1px solid #d7e2eb;background:#fff;color:#52667a;font-weight:700}.customer-tab:hover{text-decoration:none;border-color:#1769aa;color:#1769aa}.customer-tab.active{background:#1769aa;border-color:#1769aa;color:#fff;box-shadow:0 6px 16px rgba(23,105,170,.22)}
    .workspace-card{border:0;border-radius:18px;box-shadow:0 9px 26px rgba(31,45,61,.07);overflow:hidden}.profile-stat{height:100%;border:0;border-radius:17px;background:#fff;padding:1.25rem;box-shadow:0 8px 22px rgba(31,45,61,.07);display:flex;flex-direction:column;position:relative}.profile-stat small{color:#718096}.profile-stat strong{font-size:1.65rem;margin:.35rem 0}.profile-stat>span:last-child,.profile-stat a{font-size:.82rem;color:#718096}.profile-stat .stat-icon{position:absolute;left:1.1rem;top:1.1rem;width:39px;height:39px;border-radius:11px;background:#edf5fb;color:#1769aa;display:flex;align-items:center;justify-content:center}.profile-stat.balance{border-right:4px solid #1769aa}.profile-stat.paid{border-right:4px solid #20a06b}
    .section-heading{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1.2rem}.section-heading h2,.section-heading p{margin-bottom:0}.section-heading p{color:#718096;font-size:.9rem;margin-top:.2rem}.activity-row{display:flex;align-items:center;gap:.75rem;padding:.85rem 0;border-bottom:1px solid #edf2f7}.activity-row:last-child{border-bottom:0}.activity-row small{display:block;color:#718096;margin-top:.15rem}.activity-icon{width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex:0 0 auto}.activity-icon.tailoring{background:#f0eafd;color:#7048b5}.activity-icon.shop{background:#e7f7ef;color:#198754}
    .empty-state{text-align:center;color:#718096;padding:2rem!important}.balance-pill{background:#e8f3fb;color:#1769aa;border-radius:999px;padding:.55rem .9rem;font-weight:800}.type-badge,.status-badge{display:inline-block;border-radius:999px;padding:.25rem .6rem;background:#edf2f7;font-size:.78rem;font-weight:700}.type-tailor{background:#f0eafd;color:#7048b5}.type-sale{background:#e7f7ef;color:#198754}.type-payment{background:#e8f3fb;color:#1769aa}
    .measurement-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(145px,1fr));gap:.8rem}.measurement-item{border:1px solid #dfe7f0;border-radius:14px;padding:1rem;background:#fbfdff}.measurement-item.custom{border-color:#cfe7d9;background:#f5fbf8}.measurement-item small,.measurement-item strong{display:block}.measurement-item small{color:#718096;margin-bottom:.3rem}.measurement-item strong{font-size:1.2rem}.measurement-item strong span{font-size:.75rem;color:#718096}.grid-full{grid-column:1/-1}
    .profile-details{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.profile-details>div{border:1px solid #e1e8ef;border-radius:14px;padding:1rem}.profile-details small,.profile-details strong{display:block}.profile-details small{color:#718096;margin-bottom:.35rem}.profile-details .wide{grid-column:1/-1}
    .table thead th{border-top:0;background:#f7fafc;color:#627487;font-size:.8rem;white-space:nowrap}.table td{vertical-align:middle}.display-4{font-size:2.15rem}
    @media(max-width:767px){.customer-hero{padding:1.3rem}.customer-avatar{width:50px;height:50px}.profile-details{grid-template-columns:1fr}.profile-details .wide{grid-column:auto}.section-heading{align-items:flex-start;flex-direction:column}}
</style>
@endsection
