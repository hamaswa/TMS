@extends('main')

@section('content')
@php
    $dayNames = ['پیر', 'منگل', 'بدھ', 'جمعرات', 'جمعہ', 'ہفتہ', 'اتوار'];
    $statusLabels = $detailedWorkflow ? \App\Models\Order::STATUS_LABELS : [
        'assigned' => 'کارخانے میں ہے', 'cutting' => 'کارخانے میں ہے', 'stitching' => 'کارخانے میں ہے',
        'trial' => 'کارخانے میں ہے', 'ready' => 'تیار ہے', 'delivered' => 'حوالہ کر دیا گیا',
    ];
    $canManageOrders = Auth::user()->hasBusinessPermission('tailoring.orders');
    $canManageWorkshop = Auth::user()->hasBusinessPermission('tailoring.workshop');
    $pageMeta = [
        'week' => [
            'title' => 'ہفتہ وار ٹیلرنگ آرڈرز',
            'description' => 'واپسی کی تاریخ کے مطابق ہر دن کے آرڈرز دیکھیں، رسید چیک کریں یا آرڈر میں ترمیم کریں۔',
            'summary' => 'اس ہفتے کے آرڈرز',
            'range' => $weekStart->format('d-m-Y').' سے '.$weekEnd->format('d-m-Y'),
            'range_note' => 'آرڈر اس دن دکھایا جاتا ہے جس دن گاہک کو واپس دینا ہے۔',
            'empty' => 'اس دن واپسی کے لیے کوئی آرڈر مقرر نہیں۔',
        ],
        'upcoming' => [
            'title' => 'قریب آنے والی حوالگیاں',
            'description' => 'آج اور آنے والے دنوں کے تمام زیرِ تکمیل آرڈرز۔',
            'summary' => 'آنے والے آرڈرز',
            'range' => 'آج سے آنے والی تمام تاریخیں',
            'range_note' => 'تیار اور حوالہ شدہ آرڈرز اس فہرست میں شامل نہیں ہیں۔',
            'empty' => 'اس تاریخ کے لیے کوئی آنے والا آرڈر موجود نہیں۔',
        ],
        'overdue' => [
            'title' => 'تاخیر کا شکار آرڈرز',
            'description' => 'وہ تمام آرڈرز جن کی واپسی کی تاریخ گزر چکی ہے اور کام ابھی مکمل نہیں ہوا۔',
            'summary' => 'تاخیر والے آرڈرز',
            'range' => 'آج سے پہلے کی نامکمل حوالگیاں',
            'range_note' => 'تیار اور حوالہ شدہ آرڈرز اس فہرست میں شامل نہیں ہیں۔',
            'empty' => 'اس تاریخ کا کوئی نامکمل تاخیر والا آرڈر موجود نہیں۔',
        ],
        'ready' => [
            'title' => 'تیار، حوالگی کے منتظر',
            'description' => 'وہ تمام تیار آرڈرز جو ابھی گاہکوں کے حوالے نہیں کیے گئے۔',
            'summary' => 'حوالگی کے منتظر',
            'range' => 'تیار مگر غیر حوالہ شدہ آرڈرز',
            'range_note' => 'گاہک کے حوالے ہوتے ہی آرڈر اس فہرست سے نکل جائے گا۔',
            'empty' => 'اس تاریخ کا کوئی تیار آرڈر حوالگی کا منتظر نہیں۔',
        ],
    ][$filter ?: 'week'];
    $visibleOrders = $weekDays->flatMap(fn ($day) => $day['orders']);
    $tailorFilters = $visibleOrders->pluck('tailor')->filter()->unique('id')->sortBy('name')->values();
    $overdueCount = $visibleOrders->filter(fn ($order) => $order->returnDate
        && \Illuminate\Support\Carbon::parse($order->returnDate)->isBefore(today())
        && ! in_array($order->status, ['ready', 'delivered'], true))->count();
    $canStartOrder = $canManageOrders && Auth::user()->hasBusinessPermission('tailoring.customers');
@endphp

<style>
    .weekly-orders-page{--wo-blue:#1769e0;--wo-navy:#12385b;--wo-muted:#718198;--wo-line:#dce6f1;--wo-canvas:#f5f8fb;min-height:calc(100vh - 70px);background:var(--wo-canvas)}
    .weekly-orders-page [hidden]{display:none!important}
    .wo-shell{width:min(100% - 38px,1540px);margin:0 auto;padding:26px 0 48px}
    .wo-head{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:16px}.wo-head h1{margin:0;color:var(--wo-navy);font-size:1.62rem;font-weight:900}.wo-head p{margin:5px 0 0;color:var(--wo-muted);font-size:.82rem}.wo-new-order{display:inline-flex;align-items:center;gap:8px;padding:10px 16px;border-radius:10px;color:#fff;background:var(--wo-blue);font-weight:900;text-decoration:none;box-shadow:0 7px 18px rgba(23,105,224,.2)}.wo-new-order:hover{color:#fff;background:#0e5bc9;text-decoration:none;transform:translateY(-1px)}
    .wo-toolbar{display:grid;grid-template-columns:minmax(260px,1.25fr) repeat(2,minmax(150px,.55fr));gap:10px;margin-bottom:12px}.wo-control{position:relative}.wo-control i{position:absolute;top:50%;right:14px;z-index:1;color:#7890aa;transform:translateY(-50%)}.wo-control input,.wo-control select{width:100%;height:46px;padding:8px 42px 8px 13px;border:1px solid var(--wo-line);border-radius:11px;color:#334f6c;background:#fff;font-family:inherit;font-size:.78rem;outline:none}.wo-control input:focus,.wo-control select:focus{border-color:#8cb8f2;box-shadow:0 0 0 3px rgba(23,105,224,.09)}
    .wo-filter-tabs{display:flex;align-items:center;gap:7px;overflow-x:auto;margin-bottom:14px;padding-bottom:2px}.wo-filter-tabs a{flex:0 0 auto;padding:7px 12px;border:1px solid var(--wo-line);border-radius:999px;color:#526981;background:#fff;font-size:.73rem;font-weight:800;text-decoration:none}.wo-filter-tabs a:hover{color:var(--wo-blue);border-color:#a8c8f0}.wo-filter-tabs a.is-current,.wo-filter-tabs a.is-current:hover{color:#fff;border-color:var(--wo-blue);background:var(--wo-blue)}
    .wo-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:11px;margin-bottom:14px}.wo-summary-card{display:flex;align-items:center;justify-content:space-between;gap:12px;min-height:92px;padding:14px 16px;border:1px solid var(--wo-line);border-radius:13px;background:#fff}.wo-summary-icon{display:grid;place-items:center;flex:0 0 44px;width:44px;height:44px;border-radius:12px;color:var(--wo-blue);background:#e8f2ff}.wo-summary-card small{display:block;color:var(--wo-muted);font-size:.7rem}.wo-summary-card strong{display:block;margin-top:2px;color:var(--wo-navy);font-size:1.32rem;font-weight:900}.wo-summary-card.is-work .wo-summary-icon{color:#b36d00;background:#fff2d7}.wo-summary-card.is-ready .wo-summary-icon{color:#128354;background:#e4f7ed}.wo-summary-card.is-overdue .wo-summary-icon{color:#ce3847;background:#ffebed}.wo-summary-card.is-overdue strong{color:#c92f40}
    .wo-week-bar{display:flex;align-items:center;gap:9px;margin-bottom:14px;padding:9px;border:1px solid var(--wo-line);border-radius:13px;background:#fff}.wo-week-arrow{display:grid;place-items:center;flex:0 0 38px;width:38px;height:38px;border:1px solid #d8e3ef;border-radius:9px;color:#506981;background:#fff;text-decoration:none}.wo-week-arrow:hover{color:var(--wo-blue);border-color:#9fc1ee;text-decoration:none}.wo-week-days{display:grid;grid-template-columns:repeat(7,minmax(86px,1fr));flex:1;gap:5px}.wo-week-day{padding:7px 5px;border-radius:9px;color:#60758d;text-align:center;text-decoration:none}.wo-week-day b{display:block;color:#344f6c;font-size:.76rem}.wo-week-day small{display:block;margin-top:2px;font-size:.65rem}.wo-week-day:hover,.wo-week-day.is-today{color:var(--wo-blue);background:#eaf3ff;text-decoration:none}.wo-week-day.is-today b{color:var(--wo-blue)}
    .wo-range{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px;padding:11px 15px;border:1px solid var(--wo-line);border-radius:11px;background:#fff}.wo-range strong{color:var(--wo-navy);font-size:.82rem}.wo-range span{color:var(--wo-muted);font-size:.73rem}
    .wo-days{display:grid;gap:12px}.wo-day{overflow:hidden;border:1px solid var(--wo-line);border-radius:14px;background:#fff;box-shadow:0 5px 16px rgba(25,55,88,.035)}.wo-day.is-today{border-color:#91bdf5}.wo-day-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:11px 15px;border-bottom:1px solid #e6edf5;background:#f8fafc}.wo-day.is-today .wo-day-head{background:#edf6ff}.wo-day-title{display:flex;align-items:center;gap:10px}.wo-date-box{display:grid;place-items:center;min-width:42px;height:42px;border-radius:10px;color:var(--wo-blue);background:#e7f1ff;font-size:1rem;font-weight:900}.wo-day-title h2{margin:0;color:var(--wo-navy);font-size:.94rem;font-weight:900}.wo-day-title small{display:inline-block;margin-right:7px;color:var(--wo-muted);font-weight:500}.wo-day-count{padding:5px 10px;border-radius:999px;color:#60738a;background:#edf2f7;font-size:.68rem;font-weight:800}.wo-day.has-orders .wo-day-count{color:var(--wo-blue);background:#e4f0ff}
    .wo-table-head,.wo-order{display:grid;grid-template-columns:minmax(190px,1.45fr) 75px minmax(110px,.7fr) 115px minmax(120px,.75fr) minmax(170px,1.05fr) 92px;align-items:center;gap:10px}.wo-table-head{padding:8px 15px;color:#7b8ca0;background:#fbfcfe;font-size:.65rem;font-weight:800}.wo-order{position:relative;min-height:74px;padding:10px 15px;border-top:1px solid #e8eef5;background:#fff;transition:background .18s ease,box-shadow .18s ease}.wo-order:first-child{border-top:0}.wo-order:hover{z-index:1;background:#edf6ff;box-shadow:inset 3px 0 0 var(--wo-blue)}.wo-order.is-overdue{box-shadow:inset -3px 0 0 #dc3545}.wo-order.is-overdue:hover{box-shadow:inset -3px 0 0 #dc3545,inset 3px 0 0 var(--wo-blue)}.wo-customer{min-width:0}.wo-customer strong{display:block;overflow:hidden;color:#203b5a;font-size:.87rem;text-overflow:ellipsis;white-space:nowrap}.wo-customer small{display:block;margin-top:3px;overflow:hidden;color:var(--wo-muted);font-size:.68rem;text-overflow:ellipsis;white-space:nowrap}.wo-suits{text-align:center}.wo-suits b{display:inline-flex;align-items:center;justify-content:center;gap:5px;min-width:42px;padding:6px 8px;border-radius:8px;color:#6648b6;background:#f0ebff;font-size:.76rem}.wo-tailor{color:#405a74;font-size:.76rem}.wo-tailor i{margin-left:5px;color:#94a5b7}.wo-due{direction:ltr;color:#324d68;font-size:.73rem;font-weight:800;text-align:right}.wo-due small{display:block;margin-top:2px;color:#8191a4;font-size:.63rem}.wo-order.is-overdue .wo-due,.wo-order.is-overdue .wo-due small{color:#cf3545}.wo-payment{direction:ltr;color:#29445f;font-size:.73rem;font-weight:900;text-align:right}.wo-payment small{display:block;margin-top:2px;color:#7d8da0;font-weight:600}.wo-payment.has-balance{color:#d63345}
    .wo-state-cell{display:flex;align-items:center;flex-wrap:wrap;gap:5px}.wo-status{display:inline-flex;align-items:center;gap:5px;padding:5px 8px;border-radius:8px;color:#9b6200;background:#fff1d5;font-size:.64rem;font-weight:900;white-space:nowrap}.wo-status.is-ready{color:#138052;background:#e4f7ed}.wo-status.is-delivered{color:#60738a;background:#edf2f7}.wo-status.is-overdue{color:#c93142;background:#ffeaed}.wo-status-switch{display:inline-flex;overflow:hidden;border:1px solid #d9e2ed;border-radius:8px;background:#fff}.wo-status-option{padding:5px 7px;border:0;border-left:1px solid #e3e9f1;color:#63758a;background:#fff;font-family:inherit;font-size:.61rem;font-weight:800;cursor:pointer}.wo-status-option:last-child{border-left:0}.wo-status-option:hover:not(:disabled){color:var(--wo-blue);background:#f0f6ff}.wo-status-option.is-workshop:disabled{color:#fff;background:#d98a14}.wo-status-option.is-ready:disabled{color:#fff;background:#198754}.wo-status-option:disabled{cursor:default;opacity:1}.wo-detailed-status{display:flex;gap:4px}.wo-detailed-status select{max-width:105px;padding:5px;border:1px solid #d9e2ed;border-radius:7px;color:#49627e;background:#fff;font-family:inherit;font-size:.62rem}.wo-detailed-status button{padding:5px 7px;border:0;border-radius:7px;color:#fff;background:var(--wo-blue);font-family:inherit;font-size:.61rem;font-weight:800}.wo-actions{display:flex;justify-content:flex-end;gap:5px}.wo-action{display:grid;place-items:center;width:34px;height:34px;border:1px solid #d8e2ef;border-radius:8px;color:#49627e;background:#fff;text-decoration:none}.wo-action:hover{color:var(--wo-blue);border-color:#9fc1ee;background:#fff;text-decoration:none}.wo-action.is-edit{color:#fff;border-color:var(--wo-blue);background:var(--wo-blue)}
    .wo-empty-day{display:flex;align-items:center;justify-content:center;gap:7px;padding:13px;color:#8795a6;font-size:.74rem}.wo-empty-day i{color:#b3c0cf}.wo-no-results{display:none;padding:28px;border:1px dashed #cbd8e6;border-radius:13px;color:#75869a;background:#fff;text-align:center}.wo-no-results i{display:block;margin-bottom:8px;color:#a5b6c8;font-size:1.5rem}
    @media(max-width:1200px){.wo-table-head{display:none}.wo-order{grid-template-columns:minmax(190px,1.3fr) 65px minmax(100px,.7fr) 105px minmax(110px,.7fr) minmax(160px,1fr) 78px;padding:10px}.wo-shell{width:min(100% - 24px,1540px)}}
    @media(max-width:900px){.wo-toolbar{grid-template-columns:1fr 1fr}.wo-toolbar .wo-control:first-child{grid-column:1/-1}.wo-week-days{overflow-x:auto;display:flex}.wo-week-day{flex:0 0 100px}.wo-order{grid-template-columns:1fr 70px 1fr}.wo-customer{grid-column:1/3}.wo-state-cell{grid-column:1/3}.wo-actions{grid-column:3;grid-row:1/3}.wo-due,.wo-payment{margin-top:4px}}
    @media(max-width:650px){.wo-shell{width:min(100% - 16px,1540px);padding-top:18px}.wo-head{align-items:flex-start;flex-direction:column}.wo-new-order{width:100%;justify-content:center}.wo-toolbar{grid-template-columns:1fr}.wo-toolbar .wo-control:first-child{grid-column:auto}.wo-summary{grid-template-columns:repeat(2,minmax(0,1fr))}.wo-week-bar{padding:7px}.wo-week-arrow{display:none}.wo-range{align-items:flex-start;flex-direction:column}.wo-order{grid-template-columns:1fr 58px;padding:11px}.wo-customer{grid-column:1}.wo-suits{grid-column:2;grid-row:1}.wo-tailor,.wo-due,.wo-payment,.wo-state-cell{grid-column:1/3}.wo-actions{grid-column:1/3;grid-row:auto;justify-content:flex-start}.wo-day-title small{display:block;margin:2px 0 0}}
</style>

<section class="main-content weekly-orders-page" dir="rtl">
    <div class="wo-shell">
        @include('inc.message')

        <header class="wo-head">
            <div><h1><i class="fas fa-calendar-week ml-2 text-primary"></i>{{ $pageMeta['title'] }}</h1><p>{{ $pageMeta['description'] }}</p></div>
            @if($canStartOrder)<a class="wo-new-order" href="{{ route('admin.Customers.index') }}" title="پہلے گاہک منتخب کریں"><i class="fas fa-plus"></i>نیا آرڈر</a>@endif
        </header>

        <div class="wo-toolbar" aria-label="آرڈر تلاش اور فلٹر کریں">
            <label class="wo-control"><i class="fas fa-search"></i><input id="wo-search" type="search" placeholder="گاہک کا نام، فون، سیریل یا درزی تلاش کریں…" autocomplete="off"></label>
            <label class="wo-control"><i class="fas fa-tag"></i><select id="wo-status-filter" aria-label="حالت کے مطابق فلٹر کریں"><option value="all">تمام حالتیں</option><option value="workshop">جاری کام</option><option value="ready">تیار</option><option value="delivered">حوالہ شدہ</option><option value="overdue">تاخیر کا شکار</option></select></label>
            <label class="wo-control"><i class="fas fa-user-cog"></i><select id="wo-tailor-filter" aria-label="درزی کے مطابق فلٹر کریں"><option value="all">تمام درزی</option><option value="unassigned">درزی مقرر نہیں</option>@foreach($tailorFilters as $tailorOption)<option value="{{ $tailorOption->id }}">{{ $tailorOption->name }}</option>@endforeach</select></label>
        </div>

        <nav class="wo-filter-tabs" aria-label="آرڈر فہرست تبدیل کریں">
            <a @class(['is-current' => !$filter]) href="{{ route('admin.order.total') }}">موجودہ ہفتہ</a>
            <a @class(['is-current' => $filter === 'upcoming']) href="{{ route('admin.order.total', ['filter' => 'upcoming']) }}">آنے والے</a>
            <a @class(['is-current' => $filter === 'overdue']) href="{{ route('admin.order.total', ['filter' => 'overdue']) }}">تاخیر والے</a>
            <a @class(['is-current' => $filter === 'ready']) href="{{ route('admin.order.total', ['filter' => 'ready']) }}">تیار، حوالگی کے منتظر</a>
        </nav>

        <div class="wo-summary">
            <div class="wo-summary-card"><span class="wo-summary-icon"><i class="fas fa-clipboard-list"></i></span><div><small>{{ $pageMeta['summary'] }}</small><strong>{{ number_format($summary['orders']) }}</strong></div></div>
            <div class="wo-summary-card is-work"><span class="wo-summary-icon"><i class="fas fa-store"></i></span><div><small>جاری کام</small><strong>{{ number_format($summary['in_workshop']) }}</strong></div></div>
            <div class="wo-summary-card is-ready"><span class="wo-summary-icon"><i class="fas fa-check-circle"></i></span><div><small>تیار / حوالہ شدہ</small><strong>{{ number_format($summary['ready']) }}</strong></div></div>
            <div class="wo-summary-card is-overdue"><span class="wo-summary-icon"><i class="fas fa-clock"></i></span><div><small>تاخیر کا شکار</small><strong>{{ number_format($overdueCount) }}</strong></div></div>
        </div>

        @if(!$filter)
            <nav class="wo-week-bar" aria-label="ہفتے کے دن">
                <a class="wo-week-arrow" href="{{ route('admin.order.total', ['week' => $weekStart->copy()->subWeek()->toDateString()]) }}" title="پچھلا ہفتہ"><i class="fas fa-chevron-right"></i></a>
                <div class="wo-week-days">@foreach($weekDays as $day)<a @class(['wo-week-day', 'is-today' => $day['date']->isToday()]) href="#day-{{ $day['date']->toDateString() }}"><b>{{ $day['date']->format('l') }}</b><small>{{ $day['date']->format('d-m-Y') }}</small></a>@endforeach</div>
                <a class="wo-week-arrow" href="{{ route('admin.order.total', ['week' => $weekStart->copy()->addWeek()->toDateString()]) }}" title="اگلا ہفتہ"><i class="fas fa-chevron-left"></i></a>
            </nav>
        @endif

        <div class="wo-range"><strong><i class="far fa-calendar-alt ml-2 text-primary"></i>{{ $pageMeta['range'] }}</strong><span>{{ $pageMeta['range_note'] }}</span></div>

        <div class="wo-days" id="wo-days">
            @foreach($weekDays as $day)
                @php
                    $dayOrders = $day['orders'];
                    $isToday = $day['date']->isToday();
                    $dayName = $dayNames[$day['date']->dayOfWeekIso - 1];
                @endphp
                <section id="day-{{ $day['date']->toDateString() }}" class="wo-day {{ $dayOrders->isNotEmpty() ? 'has-orders' : '' }} {{ $isToday ? 'is-today' : '' }}" data-order-day>
                    <div class="wo-day-head">
                        <div class="wo-day-title"><span class="wo-date-box">{{ $day['date']->format('d') }}</span><div><h2>{{ $dayName }} / {{ $day['date']->format('l') }} <small>{{ $day['date']->format('d-m-Y') }}{{ $isToday ? ' · آج' : '' }}</small></h2></div></div>
                        <span class="wo-day-count" data-day-count>{{ $dayOrders->count() }} آرڈر</span>
                    </div>

                    @if($dayOrders->isNotEmpty())
                        <div class="wo-table-head" aria-hidden="true"><span>گاہک / سیریل</span><span>سوٹ</span><span>درزی</span><span>واپسی</span><span>رقم / بقایا</span><span>حالت</span><span>ایکشن</span></div>
                        <div class="wo-orders" data-order-list>
                            @foreach($dayOrders as $order)
                                @php
                                    $isDelivered = $order->status === 'delivered';
                                    $isReady = $order->status === 'ready';
                                    $isWorkshop = ! $isDelivered && ! $isReady;
                                    $isOverdue = $isWorkshop && $order->returnDate && \Illuminate\Support\Carbon::parse($order->returnDate)->isBefore(today());
                                    $statusClass = $isOverdue ? 'is-overdue' : ($isDelivered ? 'is-delivered' : ($isReady ? 'is-ready' : ''));
                                    $filterState = $isOverdue ? 'overdue' : ($isDelivered ? 'delivered' : ($isReady ? 'ready' : 'workshop'));
                                    $nextStatusOptions = $detailedWorkflow ? collect($order->nextStatusOptions()) : collect();
                                    $outstanding = max(0, (float) ($order->outstanding_amount ?? 0));
                                    $searchText = collect([$order->customers?->name, $order->customers?->phone_number1, $order->customers?->serial_number, $order->tailor?->name])->filter()->implode(' ');
                                @endphp
                                <article class="wo-order {{ $isOverdue ? 'is-overdue' : '' }}" data-order-row data-search="{{ $searchText }}" data-state="{{ $filterState }}" data-tailor="{{ $order->tailor?->id ?: 'unassigned' }}">
                                    <div class="wo-customer"><strong>{{ $order->customers?->name ?: 'گاہک دستیاب نہیں' }} · #{{ $order->customers?->serial_number ?: ($order->customers?->id ?: '—') }}</strong><small dir="ltr">{{ $order->customers?->phone_number1 ?: 'فون نمبر موجود نہیں' }}</small></div>
                                    <div class="wo-suits"><b><i class="fas fa-tshirt"></i>{{ $order->suitQuantity ?: 1 }}</b></div>
                                    <div class="wo-tailor"><i class="fas fa-user"></i>{{ $order->tailor?->name ?: 'مقرر نہیں' }}</div>
                                    <div class="wo-due"><span>{{ $order->returnDate ? \Illuminate\Support\Carbon::parse($order->returnDate)->format('d-m-Y') : '—' }}</span><small>{{ $isOverdue ? 'تاریخ گزر چکی' : 'واپسی کی تاریخ' }}</small></div>
                                    <div class="wo-payment {{ $outstanding > 0 ? 'has-balance' : '' }}"><span>Rs. {{ number_format($outstanding) }}</span><small>کل: Rs. {{ number_format((float)$order->totalPayment) }}</small></div>
                                    <div class="wo-state-cell">
                                            <span class="wo-status {{ $statusClass }}"><i class="fas {{ $isOverdue ? 'fa-clock' : ($isDelivered ? 'fa-truck' : ($isReady ? 'fa-check-circle' : 'fa-tools')) }}"></i>{{ $isOverdue ? 'تاخیر کا شکار' : ($statusLabels[$order->status] ?? $order->status) }}</span>
                                            @if($canManageWorkshop && $detailedWorkflow && $nextStatusOptions->isNotEmpty())
                                                <form class="wo-detailed-status" method="POST" action="{{ route('admin.tailor-jobs.status', $order) }}">
                                                    @csrf @method('PATCH')
                                                    <select name="status" aria-label="اگلا مرحلہ">
                                                        @foreach($nextStatusOptions as $nextStatus)
                                                            <option value="{{ $nextStatus['value'] }}">{{ $nextStatus['label'] }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit">تبدیل کریں</button>
                                                </form>
                                            @elseif($canManageWorkshop && ! $detailedWorkflow && ! $isDelivered)
                                                <form class="wo-status-switch" method="POST" action="{{ route('admin.order.status') }}" aria-label="آرڈر کی حالت تبدیل کریں">
                                                    @csrf
                                                    <input type="hidden" name="order_id" value="{{ $order->id }}">
                                                    <button class="wo-status-option is-workshop" type="submit" name="order_status" value="start" title="کارخانے میں ہے" {{ $isWorkshop ? 'disabled' : '' }}>
                                                        <i class="fas fa-tools"></i>
                                                    </button>
                                                    <button class="wo-status-option is-ready" type="submit" name="order_status" value="complete" title="تیار ہے" {{ $isReady ? 'disabled' : '' }}>
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                @if($isReady)
                                                    <form method="POST" action="{{ route('admin.order.status') }}">
                                                        @csrf
                                                        <input type="hidden" name="order_id" value="{{ $order->id }}">
                                                        <button class="wo-status-option" type="submit" name="order_status" value="deliver" title="گاہک کے حوالے کریں">
                                                            <i class="fas fa-handshake ml-1"></i>حوالہ کریں
                                                        </button>
                                                    </form>
                                                @endif
                                            @endif
                                        </div>
                                    @if($canManageOrders)
                                        <div class="wo-actions">
                                            <a class="wo-action" href="{{ route('admin.order-print', $order->id) }}" title="رسید دیکھیں" aria-label="رسید دیکھیں"><i class="fas fa-print"></i></a>
                                            <a class="wo-action is-edit" href="{{ route('admin.order.edit', $order->id) }}" title="آرڈر میں ترمیم کریں" aria-label="آرڈر میں ترمیم کریں"><i class="fas fa-pen"></i></a>
                                        </div>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="wo-empty-day"><i class="far fa-calendar-check"></i>{{ $pageMeta['empty'] }}</div>
                    @endif
                </section>
            @endforeach
            <div class="wo-no-results" id="wo-no-results"><i class="fas fa-search"></i>موجودہ تلاش اور فلٹر کے مطابق کوئی آرڈر نہیں ملا۔</div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const search = document.getElementById('wo-search');
    const status = document.getElementById('wo-status-filter');
    const tailor = document.getElementById('wo-tailor-filter');
    const days = Array.from(document.querySelectorAll('[data-order-day]'));
    const noResults = document.getElementById('wo-no-results');

    function applyOrderFilters() {
        const term = (search.value || '').trim().toLocaleLowerCase();
        const selectedStatus = status.value;
        const selectedTailor = tailor.value;
        const filtersActive = term !== '' || selectedStatus !== 'all' || selectedTailor !== 'all';
        let totalVisible = 0;

        days.forEach(function (day) {
            const rows = Array.from(day.querySelectorAll('[data-order-row]'));
            let dayVisible = 0;

            rows.forEach(function (row) {
                const matchesSearch = !term || (row.dataset.search || '').toLocaleLowerCase().includes(term);
                const matchesStatus = selectedStatus === 'all' || row.dataset.state === selectedStatus;
                const matchesTailor = selectedTailor === 'all' || row.dataset.tailor === selectedTailor;
                const visible = matchesSearch && matchesStatus && matchesTailor;
                row.hidden = !visible;
                if (visible) dayVisible++;
            });

            const count = day.querySelector('[data-day-count]');
            if (count && rows.length) count.textContent = dayVisible + ' آرڈر';
            day.hidden = filtersActive && dayVisible === 0;
            totalVisible += dayVisible;
        });

        noResults.style.display = filtersActive && totalVisible === 0 ? 'block' : 'none';
    }

    search.addEventListener('input', applyOrderFilters);
    status.addEventListener('change', applyOrderFilters);
    tailor.addEventListener('change', applyOrderFilters);
});
</script>
@endsection
