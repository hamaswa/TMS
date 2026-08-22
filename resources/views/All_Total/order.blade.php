@extends('main')

@section('content')
@php
    $dayNames = ['پیر', 'منگل', 'بدھ', 'جمعرات', 'جمعہ', 'ہفتہ', 'اتوار'];
    $statusLabels = [
        'assigned' => 'کارخانے میں ہے',
        'cutting' => 'کارخانے میں ہے',
        'stitching' => 'کارخانے میں ہے',
        'trial' => 'کارخانے میں ہے',
        'ready' => 'تیار ہے',
        'delivered' => 'حوالہ کر دیا گیا',
    ];
@endphp

<style>
    .weekly-orders-page{--wo-blue:#1769e0;--wo-navy:#11365b;--wo-muted:#718198;--wo-line:#dfe7f1;min-height:calc(100vh - 70px);background:#f4f7fa}
    .wo-shell{width:min(100% - 38px,1450px);margin:0 auto;padding:28px 0 46px}
    .wo-head{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:18px}
    .wo-head h1{margin:0;color:var(--wo-navy);font-size:1.55rem;font-weight:900}.wo-head p{margin:5px 0 0;color:var(--wo-muted);font-size:.82rem}
    .wo-week-nav{display:flex;align-items:center;gap:8px}.wo-week-nav a{display:inline-flex;align-items:center;gap:7px;padding:9px 13px;border:1px solid #d7e2ef;border-radius:10px;color:#405873;background:#fff;font-weight:700;text-decoration:none}.wo-week-nav a:hover{border-color:#9fc1ee;color:var(--wo-blue)}.wo-week-nav .is-current{color:#fff;border-color:var(--wo-blue);background:var(--wo-blue)}
    .wo-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:18px}.wo-summary-card{display:flex;align-items:center;gap:12px;padding:15px;border:1px solid var(--wo-line);border-radius:13px;background:#fff}.wo-summary-icon{display:grid;place-items:center;width:43px;height:43px;border-radius:12px;color:var(--wo-blue);background:#eaf3ff}.wo-summary-card small{display:block;color:var(--wo-muted);font-size:.7rem}.wo-summary-card strong{display:block;margin-top:2px;color:var(--wo-navy);font-size:1.15rem;font-weight:900}.wo-summary-card.is-suits .wo-summary-icon{color:#7651c9;background:#f1ebff}.wo-summary-card.is-work .wo-summary-icon{color:#a56a00;background:#fff4da}.wo-summary-card.is-ready .wo-summary-icon{color:#168553;background:#e8f8ef}
    .wo-range{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px;padding:13px 16px;border:1px solid var(--wo-line);border-radius:13px;background:#fff}.wo-range strong{color:var(--wo-navy)}.wo-range span{color:var(--wo-muted);font-size:.78rem}
    .wo-days{display:grid;gap:14px}.wo-day{overflow:hidden;border:1px solid var(--wo-line);border-radius:15px;background:#fff;box-shadow:0 5px 16px rgba(25,55,88,.04)}.wo-day.is-today{border-color:#8db9f5;box-shadow:0 7px 20px rgba(23,105,224,.1)}
    .wo-day-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:13px 16px;border-bottom:1px solid #e8eef5;background:#f9fbfd}.wo-day.is-today .wo-day-head{background:#eef6ff}.wo-day-title{display:flex;align-items:center;gap:11px}.wo-date-box{display:grid;place-items:center;min-width:48px;height:48px;border-radius:12px;color:var(--wo-blue);background:#eaf3ff;font-size:1.15rem;font-weight:900}.wo-day-title h2{margin:0;color:var(--wo-navy);font-size:1rem;font-weight:900}.wo-day-title small{display:block;margin-top:3px;color:var(--wo-muted)}.wo-day-count{padding:5px 10px;border-radius:999px;color:#60738a;background:#edf2f7;font-size:.7rem;font-weight:800}.wo-day.has-orders .wo-day-count{color:var(--wo-blue);background:#e7f1ff}
    .wo-orders{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;padding:12px}.wo-order{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(120px,.7fr) auto;align-items:center;gap:14px;padding:13px;border:1px solid #e4ebf3;border-radius:12px;background:#fff}.wo-order-main{min-width:0}.wo-order-main strong{display:block;overflow:hidden;color:#203b5a;font-size:.95rem;text-overflow:ellipsis;white-space:nowrap}.wo-order-main small{display:block;margin-top:4px;color:var(--wo-muted)}.wo-order-meta{display:grid;grid-template-columns:repeat(2,1fr);gap:8px}.wo-order-meta span{padding:7px;border-radius:8px;color:#536a82;background:#f6f8fb;font-size:.7rem;text-align:center}.wo-order-meta b{display:block;margin-top:2px;color:#29435f}.wo-status{display:inline-block;margin-top:6px;padding:4px 8px;border-radius:999px;color:#9a6900;background:#fff4da;font-size:.66rem;font-weight:800}.wo-status.is-ready{color:#168553;background:#e8f8ef}.wo-status.is-delivered{color:#60738a;background:#edf2f7}.wo-order-actions{display:flex;gap:6px}.wo-action{display:grid;place-items:center;width:36px;height:36px;border:1px solid #d8e2ef;border-radius:9px;color:#49627e;background:#fff;text-decoration:none}.wo-action:hover{color:var(--wo-blue);border-color:#a9c9f2;background:#f3f8ff;text-decoration:none}.wo-action.is-edit{color:#fff;border-color:var(--wo-blue);background:var(--wo-blue)}
    .wo-empty{padding:18px;color:#8795a6;text-align:center;font-size:.78rem}.wo-empty i{margin-left:6px;color:#b5c1d0}
    @media(max-width:1050px){.wo-orders{grid-template-columns:1fr}.wo-head{align-items:flex-start;flex-direction:column}}
    @media(max-width:700px){.wo-shell{width:min(100% - 20px,1450px);padding-top:18px}.wo-summary{grid-template-columns:repeat(2,minmax(0,1fr))}.wo-week-nav{flex-wrap:wrap}.wo-order{grid-template-columns:1fr}.wo-order-actions{justify-content:flex-start}.wo-range{align-items:flex-start;flex-direction:column}}
    @media(max-width:420px){.wo-summary{grid-template-columns:1fr}}
</style>

<section class="main-content weekly-orders-page" dir="rtl">
    <div class="wo-shell">
        @include('inc.message')

        <header class="wo-head">
            <div><h1><i class="fas fa-calendar-week ml-2 text-primary"></i>ہفتہ وار ٹیلرنگ آرڈرز</h1><p>واپسی کی تاریخ کے مطابق ہر دن کے آرڈرز دیکھیں، رسید چیک کریں یا آرڈر میں ترمیم کریں۔</p></div>
            <nav class="wo-week-nav" aria-label="ہفتہ تبدیل کریں">
                <a href="{{ route('admin.order.total', ['week' => $weekStart->copy()->subWeek()->toDateString()]) }}"><i class="fas fa-chevron-right"></i>پچھلا ہفتہ</a>
                <a class="is-current" href="{{ route('admin.order.total') }}">موجودہ ہفتہ</a>
                <a href="{{ route('admin.order.total', ['week' => $weekStart->copy()->addWeek()->toDateString()]) }}">اگلا ہفتہ<i class="fas fa-chevron-left"></i></a>
            </nav>
        </header>

        <div class="wo-summary">
            <div class="wo-summary-card"><span class="wo-summary-icon"><i class="fas fa-clipboard-list"></i></span><div><small>اس ہفتے کے آرڈرز</small><strong>{{ number_format($summary['orders']) }}</strong></div></div>
            <div class="wo-summary-card is-suits"><span class="wo-summary-icon"><i class="fas fa-tshirt"></i></span><div><small>کل سوٹ</small><strong>{{ number_format($summary['suits']) }}</strong></div></div>
            <div class="wo-summary-card is-work"><span class="wo-summary-icon"><i class="fas fa-store"></i></span><div><small>کارخانے میں</small><strong>{{ number_format($summary['in_workshop']) }}</strong></div></div>
            <div class="wo-summary-card is-ready"><span class="wo-summary-icon"><i class="fas fa-check-circle"></i></span><div><small>تیار / حوالہ شدہ</small><strong>{{ number_format($summary['ready']) }}</strong></div></div>
        </div>

        <div class="wo-range"><strong><i class="far fa-calendar-alt ml-2 text-primary"></i>{{ $weekStart->format('d-m-Y') }} سے {{ $weekEnd->format('d-m-Y') }}</strong><span>آرڈر اس دن دکھایا جاتا ہے جس دن گاہک کو واپس دینا ہے۔</span></div>

        <div class="wo-days">
            @foreach($weekDays as $index => $day)
                @php
                    $dayOrders = $day['orders'];
                    $isToday = $day['date']->isToday();
                @endphp
                <section class="wo-day {{ $dayOrders->isNotEmpty() ? 'has-orders' : '' }} {{ $isToday ? 'is-today' : '' }}">
                    <div class="wo-day-head">
                        <div class="wo-day-title"><span class="wo-date-box">{{ $day['date']->format('d') }}</span><div><h2>{{ $dayNames[$index] }} / {{ $day['date']->format('l') }}</h2><small>{{ $day['date']->format('d-m-Y') }}{{ $isToday ? ' · آج' : '' }}</small></div></div>
                        <span class="wo-day-count">{{ $dayOrders->count() }} آرڈر</span>
                    </div>

                    @if($dayOrders->isNotEmpty())
                        <div class="wo-orders">
                            @foreach($dayOrders as $order)
                                @php
                                    $statusClass = $order->status === 'delivered' ? 'is-delivered' : (in_array($order->status, ['ready'], true) ? 'is-ready' : '');
                                @endphp
                                <article class="wo-order">
                                    <div class="wo-order-main">
                                        <strong>{{ $order->customers?->name ?: 'گاہک دستیاب نہیں' }} · سیریل #{{ $order->customers?->id ?: '—' }}</strong>
                                        <small>{{ $order->customers?->phone_number1 ?: 'فون نمبر موجود نہیں' }} · درزی: {{ $order->tailor?->name ?: 'مقرر نہیں' }}</small>
                                        <span class="wo-status {{ $statusClass }}">{{ $statusLabels[$order->status] ?? $order->status }}</span>
                                    </div>
                                    <div class="wo-order-meta">
                                        <span>سوٹ<b>{{ $order->suitQuantity ?: 1 }}</b></span>
                                        <span>رقم<b>Rs. {{ number_format((float)$order->totalPayment) }}</b></span>
                                    </div>
                                    <div class="wo-order-actions">
                                        <a class="wo-action" href="{{ route('admin.order-print', $order->id) }}" title="رسید دیکھیں" aria-label="رسید دیکھیں"><i class="fas fa-print"></i></a>
                                        <a class="wo-action is-edit" href="{{ route('admin.order.edit', $order->id) }}" title="آرڈر میں ترمیم کریں" aria-label="آرڈر میں ترمیم کریں"><i class="fas fa-pen"></i></a>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="wo-empty"><i class="far fa-calendar-check"></i>اس دن واپسی کے لیے کوئی آرڈر مقرر نہیں۔</div>
                    @endif
                </section>
            @endforeach
        </div>
    </div>
</section>
@endsection
