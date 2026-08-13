@extends('main')

@section('content')
    @php
        $quickActions = collect([
            Auth::user()->hasBusinessPermission('tailoring.customers') ? ['نیا گاہک', route('admin.Customers.create'), 'fa-user-plus'] : null,
            Auth::user()->hasBusinessPermission('tailoring.workshop') ? ['کام کی فہرست', route('admin.tailor-jobs.index'), 'fa-tasks'] : null,
            Auth::user()->hasBusinessPermission('tailoring.orders') ? ['ٹیلرنگ آرڈرز', route('admin.order.total'), 'fa-clipboard-list'] : null,
            Auth::user()->hasBusinessPermission('tailoring.customers') ? ['گاہک اور پیمائش', route('admin.Customers.index'), 'fa-user-friends'] : null,
            Auth::user()->hasBusinessPermission('tailoring.tailors') ? ['درزی', route('admin.Tailor.index'), 'fa-user-cog'] : null,
            Auth::user()->hasBusinessPermission('tailoring.configuration') ? ['پیمائش کے اختیارات', route('admin.OptionType.index'), 'fa-ruler-combined'] : null,
        ])->filter()->values();
        $metricCards = collect();
        if ($canWorkshop) {
            $metricCards->push(
                ['جاری کام', $tailoring['active'], 'fa-spinner', 'primary'],
                ['آج واجب', $tailoring['due_today'], 'fa-calendar-day', 'warning'],
                ['تیار', $tailoring['ready'], 'fa-check-circle', 'success']
            );
        }
        if ($canOrders) $metricCards->push(['اس ماہ کے سوٹ', $tailoring['month_suits'], 'fa-tshirt', 'info']);
    @endphp

    <style>
        .tailoring-dashboard { --dash-navy:#102a50; --dash-muted:#6a7990; --dash-line:#e1e9f3; direction:rtl; padding:26px 0 50px; }
        .tailoring-dashboard .dash-shell { width:min(100% - 32px,1720px); margin-inline:auto; }
        .workspace-hero { display:flex; align-items:center; justify-content:space-between; gap:22px; min-height:145px; padding:26px 32px; color:#fff!important; background:linear-gradient(135deg,#102a43,#1769a8); border-radius:19px; box-shadow:0 16px 40px rgba(16,42,67,.15); }
        .workspace-hero h1 { margin:0 0 6px; color:#fff!important; font-size:clamp(1.65rem,2vw,2.05rem); font-weight:800; }
        .workspace-hero p { margin:0; color:rgba(255,255,255,.78)!important; }
        .workspace-badge { display:inline-block; margin-bottom:8px; padding:4px 10px; color:#1769e0; background:#fff; border-radius:7px; font-size:.78rem; font-weight:800; }
        .workspace-switch { display:inline-flex; align-items:center; gap:8px; min-height:42px; padding:9px 15px; color:#27425f; background:#fff; border:1px solid rgba(255,255,255,.8); border-radius:9px; font-weight:700; }
        .workspace-switch:hover { color:#1769e0; text-decoration:none; }
        .metrics-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; margin:20px 0; }
        .metric-card { display:flex; align-items:center; gap:14px; min-height:102px; padding:17px 18px; background:#fff; border:1px solid var(--dash-line); border-radius:15px; box-shadow:0 7px 24px rgba(31,45,61,.055); }
        .metric-icon { display:grid; place-items:center; flex:0 0 50px; width:50px; height:50px; border-radius:14px; font-size:1.15rem; }
        .metric-icon.primary { color:#1769e0; background:#eaf3ff; }.metric-icon.warning { color:#d9890b; background:#fff4df; }.metric-icon.success { color:#09955e; background:#e7f8ef; }.metric-icon.info { color:#119bb4; background:#e5f8fb; }
        .metric-card small { display:block; color:var(--dash-muted); font-size:.84rem; }
        .metric-card strong { display:block; margin-top:4px; color:var(--dash-navy); font-size:1.5rem; font-weight:800; }
        .quick-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:13px; margin-bottom:20px; }
        .quick-action { display:flex; align-items:center; gap:12px; min-height:68px; padding:14px 17px; color:#29445f; background:#fff; border:1px solid var(--dash-line); border-radius:13px; font-weight:800; transition:.2s ease; }
        .quick-action__icon { display:grid; place-items:center; flex:0 0 38px; width:38px; height:38px; color:#1769e0; background:#edf5ff; border-radius:10px; }
        .quick-action:hover { color:#1769e0; background:#f8fbff; border-color:#9cc2f4; text-decoration:none; transform:translateY(-1px); }
        .operations-panel { overflow:hidden; background:#fff; border:1px solid var(--dash-line); border-radius:16px; box-shadow:0 8px 28px rgba(21,47,81,.055); }
        .operations-head { display:flex; align-items:center; justify-content:space-between; gap:18px; padding:18px 20px; border-bottom:1px solid var(--dash-line); }
        .operations-head h2 { margin:0 0 4px; color:var(--dash-navy); font-size:1.16rem; font-weight:800; }
        .operations-head p { margin:0; color:var(--dash-muted); font-size:.82rem; }
        .operations-head a { display:inline-flex; align-items:center; gap:7px; color:#1769e0; font-size:.84rem; font-weight:800; }
        .operations-list { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); }
        .operation-item { min-width:0; padding:16px; border-left:1px solid #e7edf5; }
        .operation-item:last-child { border-left:0; }
        .operation-top { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:10px; }
        .operation-id { color:#1769e0; font-size:.78rem; font-weight:800; direction:ltr; }
        .operation-status { padding:4px 8px; color:#526780; background:#f1f5f9; border-radius:999px; font-size:.7rem; font-weight:700; white-space:nowrap; }
        .operation-item strong { display:block; overflow:hidden; color:var(--dash-navy); font-size:.94rem; text-overflow:ellipsis; white-space:nowrap; }
        .operation-item small { display:block; margin-top:5px; overflow:hidden; color:#7d8ba0; font-size:.75rem; text-overflow:ellipsis; white-space:nowrap; }
        .operation-due { display:flex; align-items:center; gap:6px; margin-top:11px; color:#63758d; font-size:.76rem; }
        .operation-due.is-overdue { color:#d13c49; font-weight:800; }
        .operations-empty { padding:30px; color:var(--dash-muted); text-align:center; }
        .operations-empty i { display:block; margin-bottom:9px; color:#b3c1d2; font-size:1.7rem; }
        @media(max-width:1150px){.metrics-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.operations-list{grid-template-columns:repeat(2,minmax(0,1fr))}.operation-item{border-bottom:1px solid #e7edf5}}
        @media(max-width:850px){.quick-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.workspace-hero{align-items:flex-start;flex-direction:column}}
        @media(max-width:600px){.tailoring-dashboard .dash-shell{width:min(100% - 20px,1720px)}.workspace-hero{min-height:0;padding:23px}.metrics-grid,.quick-grid,.operations-list{grid-template-columns:1fr}.workspace-switch{width:100%;justify-content:center}.operations-head{align-items:flex-start;flex-direction:column}.operation-item{border-left:0}}
    </style>

    <section class="main-content tailoring-dashboard">
        <div class="dash-shell">
            <div class="workspace-hero">
                <div><span class="workspace-badge">ٹیلرنگ ورک اسپیس</span><h1>ٹیلرنگ ڈیش بورڈ</h1><p>آرڈر سے حوالگی تک ورکشاپ کی تمام پیش رفت ایک جگہ۔</p></div>
                @if(Auth::user()->enabledModules() === ['tailoring','clothing'])<a href="{{ route('admin.home') }}" class="workspace-switch"><i class="fas fa-random"></i> ورک اسپیس تبدیل کریں</a>@endif
            </div>

            @if($metricCards->isNotEmpty())
                <div class="metrics-grid">
                    @foreach($metricCards as [$label,$value,$icon,$color])
                        <div class="metric-card"><span class="metric-icon {{ $color }}"><i class="fas {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($value) }}</strong></div></div>
                    @endforeach
                </div>
            @endif

            @if($quickActions->isNotEmpty())
                <div class="quick-grid">
                    @foreach($quickActions as [$label,$url,$icon])<a class="quick-action" href="{{ $url }}"><span class="quick-action__icon"><i class="fas {{ $icon }}"></i></span><span>{{ $label }}</span></a>@endforeach
                </div>
            @endif

            @if($canWorkshop || $canOrders)
                <div class="operations-panel">
                    <div class="operations-head"><div><h2>قریب آنے والی حوالگیاں</h2><p>زیرِ تکمیل آرڈرز کو واپسی کی تاریخ کے مطابق ترجیح دی گئی ہے۔</p></div><a href="{{ $canWorkshop ? route('admin.tailor-jobs.index') : route('admin.order.total') }}">تمام ریکارڈ <i class="fas fa-arrow-left"></i></a></div>
                    @if($operationalOrders->isNotEmpty())
                        <div class="operations-list">
                            @foreach($operationalOrders as $order)
                                @php($dueDate = $order->returnDate ? \Carbon\Carbon::parse($order->returnDate) : null)
                                <div class="operation-item">
                                    <div class="operation-top"><span class="operation-id">#{{ $order->id }}</span><span class="operation-status">{{ \App\Models\Order::STATUS_LABELS[$order->status] ?? ($order->status ?: 'درج شدہ') }}</span></div>
                                    <strong>{{ $order->customers?->name ?? 'گاہک درج نہیں' }}</strong><small>{{ $order->tailor?->name ? 'درزی: '.$order->tailor->name : 'درزی مقرر نہیں' }} · {{ max(1,(int)$order->suitQuantity) }} سوٹ</small>
                                    <div class="operation-due {{ $dueDate && $dueDate->isPast() && !$dueDate->isToday() ? 'is-overdue' : '' }}"><i class="fas fa-calendar-alt"></i>{{ $dueDate ? $dueDate->format('d-m-Y') : 'تاریخ مقرر نہیں' }}</div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="operations-empty"><i class="fas fa-check-circle"></i>کوئی زیرِ تکمیل آرڈر موجود نہیں۔</div>
                    @endif
                </div>
            @endif
        </div>
    </section>
@endsection
