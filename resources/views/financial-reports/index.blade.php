@extends('main')

@push('styles')
<style>
    .rp-page{--rp-blue:#2563eb;--rp-green:#16a34a;--rp-red:#e11d48;--rp-orange:#f59e0b;--rp-purple:#8b5cf6;--rp-navy:#0f1f3d;--rp-muted:#64748b;--rp-line:#e4eaf2;min-height:calc(100vh - 65px);padding:24px 0 48px;background:#f5f7fb;color:var(--rp-navy)}
    .rp-shell{width:min(100% - 32px,1580px);margin:auto}
    .rp-head{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;margin-bottom:16px}.rp-head h1{margin:0;font-size:1.85rem;font-weight:900}.rp-head p{margin:4px 0 0;color:var(--rp-muted)}
    .rp-switch{display:inline-flex;overflow:hidden;border:1px solid var(--rp-line);border-radius:12px;background:#fff;box-shadow:0 4px 14px rgba(15,31,61,.06)}.rp-switch a{display:inline-flex;align-items:center;gap:9px;min-width:150px;justify-content:center;padding:12px 22px;color:#334155!important;font-weight:900;text-decoration:none!important}.rp-switch a.is-active{background:var(--rp-blue);color:#fff!important}.rp-switch a+a{border-right:1px solid var(--rp-line)}
    .rp-bar{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;margin-bottom:18px}.rp-dates{display:flex;flex-wrap:wrap;align-items:center;gap:8px}.rp-dates-box{display:inline-flex;align-items:center;gap:10px;padding:6px 12px;border:1px solid var(--rp-line);border-radius:11px;background:#fff}.rp-dates-box i{color:var(--rp-muted)}.rp-dates-box input{width:140px;border:0;background:transparent;color:var(--rp-navy);font:800 .86rem Arial,Tahoma,sans-serif;direction:ltr}.rp-dates-box input:focus{outline:0}.rp-apply{display:inline-flex;align-items:center;gap:7px;height:42px;padding:0 16px;border:0;border-radius:11px;background:var(--rp-blue);color:#fff;font-weight:900}.rp-apply:hover{background:#1d4ed8}.rp-icon-btn{display:inline-grid;place-items:center;width:42px;height:42px;border:1px solid var(--rp-line);border-radius:11px;background:#fff;color:#334155}.rp-icon-btn:hover{border-color:#b9cbe6;color:var(--rp-blue)}.rp-preset{padding:7px 12px;border:1px solid var(--rp-line);border-radius:999px;background:#fff;color:#475569!important;font-size:.78rem;font-weight:800;text-decoration:none!important}.rp-preset:hover{border-color:#b9cbe6;color:var(--rp-blue)!important}
    .rp-profit{display:flex;flex-wrap:wrap;align-items:center;gap:6px 14px;padding:9px 14px;border:1px solid var(--rp-line);border-radius:11px;background:#fff;font-size:.8rem;font-weight:800;color:#475569}.rp-profit b{direction:ltr;font:900 .84rem Arial,Tahoma,sans-serif;color:var(--rp-navy)}.rp-profit .is-green{color:var(--rp-green)}.rp-profit .is-red{color:var(--rp-red)}.rp-profit a{color:var(--rp-green)!important;font-weight:900}
    .rp-errors{margin-bottom:14px;padding:12px 16px;border:1px solid #f5c2cb;border-radius:11px;background:#fff1f3;color:#9f1239}
    .rp-kpis{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:14px;margin-bottom:16px}.rp-kpi{display:flex;align-items:flex-start;gap:14px;padding:18px;border:1px solid var(--rp-line);border-radius:15px;background:#fff;color:inherit!important;text-decoration:none!important;box-shadow:0 4px 18px rgba(15,31,61,.05)}a.rp-kpi:hover{border-color:#c7d6ec}.rp-kpi-icon{display:grid;place-items:center;flex:0 0 52px;height:52px;border-radius:14px;font-size:1.3rem}.rp-kpi.is-green .rp-kpi-icon{background:#e7f8ee;color:var(--rp-green)}.rp-kpi.is-blue .rp-kpi-icon{background:#e8f0ff;color:var(--rp-blue)}.rp-kpi.is-red .rp-kpi-icon{background:#ffe8ec;color:var(--rp-red)}.rp-kpi.is-orange .rp-kpi-icon{background:#fff3e1;color:#ea7a0c}.rp-kpi.is-purple .rp-kpi-icon{background:#f1ebff;color:var(--rp-purple)}.rp-kpi-body{min-width:0}.rp-kpi-body small{display:block;color:#475569;font-size:.86rem;font-weight:800}.rp-kpi-body strong{display:block;margin-top:6px;direction:ltr;text-align:right;font:900 1.32rem/1.25 Arial,Tahoma,sans-serif;white-space:nowrap}.rp-kpi-body strong em{font-style:normal;font-size:.85rem;color:var(--rp-muted)}.rp-change{display:block;margin-top:8px;font-size:.74rem;font-weight:700;color:var(--rp-muted)}.rp-change b{display:inline-flex;align-items:center;gap:4px;margin-left:4px;direction:ltr;font:900 .8rem Arial,Tahoma,sans-serif}.rp-change .is-good{color:var(--rp-green)}.rp-change .is-bad{color:var(--rp-red)}
    .rp-row{display:grid;grid-template-columns:1.45fr 1fr;gap:16px;margin-bottom:16px}.rp-card{min-width:0;padding:18px 20px;border:1px solid var(--rp-line);border-radius:15px;background:#fff;box-shadow:0 4px 18px rgba(15,31,61,.05)}.rp-card-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px}.rp-card-head h3{display:flex;align-items:center;gap:9px;margin:0;font-size:1.08rem;font-weight:900}.rp-legend{display:flex;gap:14px;font-size:.8rem;font-weight:800;color:#475569}.rp-legend span{display:inline-flex;align-items:center;gap:6px}.rp-legend i{width:10px;height:10px;border-radius:50%}.rp-more{display:inline-flex;align-items:center;gap:7px;padding:7px 13px;border:1px solid #c9d8f0;border-radius:9px;color:var(--rp-blue)!important;font-size:.8rem;font-weight:900;text-decoration:none!important;background:#fff}.rp-more:hover{background:#eef4ff}
    .rp-chart{width:100%;height:auto;direction:ltr}.rp-chart text{fill:#64748b;font:600 11px Arial,Tahoma,sans-serif}.rp-chart line{stroke:#eef2f7}.rp-empty{padding:46px 10px;color:#94a3b8;text-align:center;font-size:.86rem}
    .rp-donut-wrap{display:flex;align-items:center;gap:24px}.rp-donut{position:relative;flex:0 0 190px;height:190px;border-radius:50%}.rp-donut:after{content:"";position:absolute;inset:36px;border-radius:50%;background:#fff}.rp-donut-center{position:absolute;inset:0;z-index:1;display:grid;place-items:center;text-align:center}.rp-donut-center small{display:block;color:#475569;font-size:.8rem;font-weight:800}.rp-donut-center strong{display:block;direction:ltr;font:900 .95rem Arial,Tahoma,sans-serif}.rp-donut-legend{flex:1;min-width:0}.rp-donut-legend div{display:grid;grid-template-columns:auto 1fr auto auto;align-items:center;gap:12px;padding:11px 0;border-bottom:1px solid #f1f5f9;font-size:.86rem;font-weight:800}.rp-donut-legend div:last-child{border-bottom:0}.rp-donut-legend i{width:12px;height:12px;border-radius:50%}.rp-donut-legend b,.rp-donut-legend em{direction:ltr;font-style:normal;white-space:nowrap;font:900 .82rem Arial,Tahoma,sans-serif}.rp-donut-legend em{color:var(--rp-muted)}
    .rp-table{width:100%;margin:0;border-collapse:separate;border-spacing:0}.rp-table th{padding:10px 12px;border-bottom:1px solid var(--rp-line);background:#f6f8fc;color:#475569;font-size:.8rem;font-weight:900;white-space:nowrap}.rp-table td{padding:12px;border-bottom:1px solid #f1f5f9;font-size:.86rem;vertical-align:middle}.rp-table tr:last-child td{border-bottom:0}.rp-table .is-num,.rp-num{direction:ltr;white-space:nowrap;font:800 .84rem Arial,Tahoma,sans-serif}.rp-table .is-money,.rp-money{direction:ltr;white-space:nowrap;font:900 .86rem Arial,Tahoma,sans-serif}.rp-money.is-green{color:var(--rp-green)}.rp-money.is-red{color:var(--rp-red)}.rp-money.is-blue{color:var(--rp-blue)}.rp-muted{color:var(--rp-muted);font-size:.76rem}.rp-pill{display:inline-block;padding:3px 11px;border-radius:7px;font-size:.76rem;font-weight:900;white-space:nowrap}.rp-pill.is-green{background:#e7f8ee;color:#15803d}.rp-pill.is-blue{background:#e8f0ff;color:#1d4ed8}.rp-pill.is-orange{background:#fff3e1;color:#c2410c}.rp-pill.is-gray{background:#eef2f6;color:#475569}.rp-pill.is-purple{background:#f1ebff;color:#6d28d9}.rp-pill.is-red{background:#ffe8ec;color:#be123c}
    .rp-strip{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));margin-bottom:18px;border:1px solid var(--rp-line);border-radius:15px;background:#fff;box-shadow:0 4px 18px rgba(15,31,61,.05)}.rp-strip-item{display:flex;align-items:center;gap:14px;padding:16px 18px}.rp-strip-item+.rp-strip-item{border-right:1px solid var(--rp-line)}.rp-strip-icon{display:grid;place-items:center;flex:0 0 46px;height:46px;border-radius:13px;font-size:1.1rem}.rp-strip-item:nth-child(1) .rp-strip-icon{background:#e7f8ee;color:var(--rp-green)}.rp-strip-item:nth-child(2) .rp-strip-icon{background:#e8f0ff;color:var(--rp-blue)}.rp-strip-item:nth-child(3) .rp-strip-icon{background:#f1ebff;color:var(--rp-purple)}.rp-strip-item:nth-child(4) .rp-strip-icon{background:#eef2f6;color:#334155}.rp-strip-item small{display:block;color:var(--rp-muted);font-size:.76rem;font-weight:800}.rp-strip-item strong{display:block;margin-top:2px;overflow:hidden;font-size:.95rem;font-weight:900;text-overflow:ellipsis;white-space:nowrap}.rp-strip-item span{display:block;margin-top:2px;direction:ltr;text-align:right;color:var(--rp-muted);font:700 .76rem Arial,Tahoma,sans-serif}
    .rp-tabs-card{border:1px solid var(--rp-line);border-radius:15px;background:#fff;box-shadow:0 4px 18px rgba(15,31,61,.05)}.rp-tabs{display:flex;gap:6px;padding:10px;overflow-x:auto;border-bottom:1px solid var(--rp-line);scrollbar-width:none}.rp-tabs::-webkit-scrollbar{display:none}.rp-tab{display:inline-flex;flex:0 0 auto;align-items:center;gap:8px;padding:9px 16px;border:0;border-radius:10px;background:transparent;color:#475569;font-weight:900;white-space:nowrap}.rp-tab:hover{background:#f3f6fb;color:var(--rp-navy)}.rp-tab.is-active{background:#e8f0ff;color:var(--rp-blue)}.rp-tab-badge{padding:1px 8px;border-radius:999px;background:#eef2f6;color:#475569;font:800 .7rem Arial,Tahoma,sans-serif}.rp-tab.is-active .rp-tab-badge{background:var(--rp-blue);color:#fff}.rp-tab-panel{padding:16px 18px}.rp-tab-panel[hidden]{display:none}.rp-panel-head{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px}.rp-panel-head h4{margin:0;font-size:1rem;font-weight:900}.rp-panel-total{direction:ltr;font:900 1rem Arial,Tahoma,sans-serif}.rp-filter{display:flex;flex-wrap:wrap;gap:8px}.rp-filter .form-control{width:auto;min-width:200px;min-height:40px;border-color:#d8e1ec;border-radius:9px}.rp-filter button{min-height:40px;padding:0 16px;border:0;border-radius:9px;background:var(--rp-blue);color:#fff;font-weight:900}.rp-pagination{padding-top:12px}.rp-mini-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:12px;margin-bottom:14px}.rp-mini{padding:14px 16px;border:1px solid var(--rp-line);border-radius:12px;background:#fafcff}.rp-mini small{display:block;color:var(--rp-muted);font-size:.78rem;font-weight:800}.rp-mini strong{display:block;margin-top:5px;direction:ltr;text-align:right;font:900 1.02rem Arial,Tahoma,sans-serif}
    @media(max-width:1300px){.rp-kpis{grid-template-columns:repeat(3,1fr)}.rp-strip{grid-template-columns:1fr 1fr}.rp-strip-item:nth-child(3){border-right:0}}
    @media(max-width:1100px){.rp-row{grid-template-columns:1fr}}
    @media(max-width:767.98px){.rp-shell{width:min(100% - 22px,1580px)}.rp-head{align-items:stretch;flex-direction:column}.rp-switch a{min-width:0;flex:1}.rp-kpis,.rp-strip{grid-template-columns:1fr}.rp-strip-item+.rp-strip-item{border-right:0;border-top:1px solid var(--rp-line)}.rp-donut-wrap{flex-direction:column}.rp-dates-box input{width:120px}}
</style>
@endpush

@section('content')
@php
    $view = $report['view'];
    $isSales = $view === 'sales';
    $modules = $report['modules'];
    $current = $report['current'];
    $previous = $report['previous'];
    $period = ['start_date' => $report['period']['start'], 'end_date' => $report['period']['end']];
    $totalExpenses = collect($report['direct_costs'])->sum() + collect($report['operating_expenses'])->sum();
    $money = fn ($value) => 'Rs. '.number_format((float) $value, 2);
    // Suits are whole numbers; meters keep two decimals.
    $qty = fn ($value, $unit) => $unit === \App\Models\ClothCategory::UNIT_SUIT ? number_format((float) $value) : number_format((float) $value, 2);
    $unitLabel = fn ($unit) => \App\Models\ClothCategory::unitLabelFor($unit);
    // % change against the same number of days just before the period; null when there is nothing to compare.
    $change = fn ($now, $before) => abs((float) $before) > 0.009 ? (int) round(((float) $now - (float) $before) / abs((float) $before) * 100) : null;
    $link = fn (array $params = []) => route('admin.financial-reports.index', array_merge($period, ['view' => $view], $params));
    $statusPill = ['unassigned' => 'is-gray', 'assigned' => 'is-blue', 'cutting' => 'is-orange', 'stitching' => 'is-orange', 'trial' => 'is-purple', 'ready' => 'is-green', 'delivered' => 'is-green', 'cancelled' => 'is-red'];

    if ($isSales) {
        $cards = [
            ['color' => 'is-green', 'icon' => 'fa-shopping-cart', 'title' => 'کل فروخت', 'value' => $money($current['revenue']), 'change' => $change($current['revenue'], $previous['revenue'])],
            ['color' => 'is-blue', 'icon' => 'fa-ruler-horizontal', 'title' => 'فروخت شدہ کپڑا', 'value' => $qty($current['meters'], 'meter').' <em>میٹر</em>', 'change' => $change($current['meters'], $previous['meters'])],
            ['color' => 'is-purple', 'icon' => 'fa-tshirt', 'title' => 'فروخت شدہ سوٹ', 'value' => $qty($current['suits'], 'suit').' <em>سوٹ</em>', 'change' => $change($current['suits'], $previous['suits'])],
            ['color' => 'is-orange', 'icon' => 'fa-hand-holding-usd', 'title' => 'وصول شدہ رقم', 'value' => $money($current['received']), 'change' => $change($current['received'], $previous['received'])],
        ];
        $tabs = [
            'categories' => ['icon' => 'fa-layer-group', 'label' => 'کیٹیگری کے حساب سے', 'badge' => null],
            'all-sales' => ['icon' => 'fa-receipt', 'label' => 'تمام فروخت', 'badge' => $report['lines']->total() ?: null],
            'customers' => ['icon' => 'fa-user-clock', 'label' => 'گاہکوں کا بقایا', 'badge' => $report['customers']['count'] ?: null],
            'suppliers' => ['icon' => 'fa-truck', 'label' => 'سپلائرز کو دینا', 'badge' => $report['payables']->total() ?: null],
            'stock' => ['icon' => 'fa-boxes', 'label' => 'اسٹاک', 'badge' => null],
        ];
        $inferredTab = match (true) {
            request()->filled('sales_page') || request()->filled('sales_category') => 'all-sales',
            request()->filled('customers_q') || request()->filled('customers_page') => 'customers',
            request()->filled('payables_q') || request()->filled('payables_page') => 'suppliers',
            default => 'categories',
        };
    } else {
        $tailorDue = $report['tailors']->sum('balance');
        $cards = [
            ['color' => 'is-green', 'icon' => 'fa-cut', 'title' => 'سلائی آمدنی', 'value' => $money($current['revenue']), 'change' => $change($current['revenue'], $previous['revenue'])],
            ['color' => 'is-blue', 'icon' => 'fa-clipboard-list', 'title' => 'سلائی آرڈرز', 'value' => number_format($current['orders']).' <em>· '.number_format($current['suits']).' سوٹ</em>', 'change' => $change($current['orders'], $previous['orders'])],
            ['color' => 'is-orange', 'icon' => 'fa-hand-holding-usd', 'title' => 'وصول شدہ رقم', 'value' => $money($current['received']), 'change' => $change($current['received'], $previous['received'])],
            ['color' => 'is-purple', 'icon' => 'fa-user-tie', 'title' => 'درزیوں کو دینا ہے', 'value' => $money($tailorDue), 'change' => false, 'note' => $report['tailors']->where('balance', '>', 0.009)->count().' درزیوں کی باقی اجرت', 'tab' => 'tailors'],
        ];
        $tabs = [
            'orders' => ['icon' => 'fa-clipboard-list', 'label' => 'تمام آرڈرز', 'badge' => $report['orders']->total() ?: null],
            'customers' => ['icon' => 'fa-user-clock', 'label' => 'گاہکوں کا بقایا', 'badge' => $report['customers']['count'] ?: null],
            'tailors' => ['icon' => 'fa-user-tie', 'label' => 'درزی اور سوٹ', 'badge' => $report['tailors']->count() ?: null],
            'payouts' => ['icon' => 'fa-money-bill-wave', 'label' => 'ادائیگیاں اور تنخواہیں', 'badge' => $report['payouts']->count() ?: null],
        ];
        $inferredTab = match (true) {
            request()->filled('customers_q') || request()->filled('customers_page') => 'customers',
            default => 'orders',
        };
    }
    // The fifth card is always what customers still owe for this side.
    $cards[] = ['color' => 'is-red', 'icon' => 'fa-user-clock', 'title' => 'گاہکوں سے لینا ہے', 'value' => $money($report['customers']['total']), 'change' => false, 'note' => $report['customers']['count'].' گاہکوں کا بقایا', 'tab' => 'customers'];
    $activeTab = array_key_exists((string) request('tab'), $tabs) ? (string) request('tab') : $inferredTab;
    $keep = fn (array $extra = []) => array_merge($period, ['view' => $view, 'tab' => $activeTab], $extra);

    // Trend chart: cloth sales and stitching income per day, drawn as plain SVG.
    $daily = $report['daily'];
    $dates = array_keys($daily);
    $points = count($dates);
    // Each view charts only its own income: cloth sales, or stitching.
    $seriesKey = $isSales ? 'cloth' : 'tailoring';
    $chartMax = max(1, max(array_merge([0], array_column($daily, $seriesKey))));
    $magnitude = 10 ** max(0, (int) floor(log10($chartMax)));
    $chartTop = ceil($chartMax / $magnitude) * $magnitude;
    [$cw, $ch, $left, $right, $top, $bottom] = [660, 250, 70, 12, 12, 30];
    $px = fn ($index) => $left + ($points > 1 ? $index / ($points - 1) : 0.5) * ($cw - $left - $right);
    $py = fn ($value) => $top + (1 - $value / $chartTop) * ($ch - $top - $bottom);
    $line = fn ($key) => implode(' ', array_map(fn ($date, $index) => round($px($index), 1).','.round($py($daily[$date][$key]), 1), $dates, array_keys($dates)));
    $area = fn ($key) => round($px(0), 1).','.round($py(0), 1).' '.$line($key).' '.round($px($points - 1), 1).','.round($py(0), 1);
    $labelEvery = max(1, (int) ceil($points / 7));
    $series = array_values(array_filter([
        $isSales ? ['key' => 'cloth', 'label' => 'کپڑے کی فروخت', 'color' => '#22c55e', 'fill' => 'rpCloth'] : null,
        $isSales ? null : ['key' => 'tailoring', 'label' => 'سلائی آمدنی', 'color' => '#3b82f6', 'fill' => 'rpTailoring'],
    ]));
    $hasChartData = collect($daily)->contains(fn ($day) => $day[$seriesKey] > 0);

    // Donut: sales by category, or stitching orders by status.
    $palette = ['#16a34a', '#2563eb', '#f59e0b', '#8b5cf6', '#ef4444', '#0ea5e9', '#14b8a6', '#f97316'];
    if ($isSales) {
        $slices = $current['categories']['rows']->groupBy('category')->map(fn ($rows) => $rows->sum('amount'))->filter(fn ($amount) => $amount > 0);
        $other = $current['revenue'] - $slices->sum();
        if ($other > 0.5) {
            $slices['دیگر فروخت'] = $other;
        }
    } else {
        $slices = $report['statuses']->mapWithKeys(fn ($item) => [$item['label'] => $item['count']]);
        $statusLabels = $report['status_labels'];
    }
    $sliceTotal = $slices->sum();
    $gradient = [];
    $offset = 0;
    foreach ($slices->values() as $index => $amount) {
        $share = $sliceTotal > 0 ? $amount / $sliceTotal * 100 : 0;
        $gradient[] = $palette[$index % count($palette)].' '.round($offset, 2).'% '.round($offset + $share, 2).'%';
        $offset += $share;
    }
@endphp
<section class="main-content rp-page" dir="rtl"><div class="rp-shell">
    <header class="rp-head">
        <div><h1>رپورٹس</h1><p>{{ $isSales ? 'کپڑے کی فروخت، اسٹاک، گاہکوں اور سپلائرز کا حساب' : 'سلائی کے آرڈرز، درزیوں، گاہکوں اور ادائیگیوں کا حساب' }}</p></div>
        @if(count($modules) > 1)
            <nav class="rp-switch" aria-label="رپورٹ منتخب کریں">
                <a href="{{ route('admin.financial-reports.index', $period + ['view' => 'sales']) }}" class="{{ $isSales ? 'is-active' : '' }}" @if($isSales) aria-current="page" @endif><i class="fas fa-shopping-cart"></i> فروخت</a>
                <a href="{{ route('admin.financial-reports.index', $period + ['view' => 'tailoring']) }}" class="{{ $isSales ? '' : 'is-active' }}" @unless($isSales) aria-current="page" @endunless><i class="fas fa-cut"></i> سلائی</a>
            </nav>
        @endif
    </header>

    @if($errors->any())<div class="rp-errors">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

    <div class="rp-bar">
        <form class="rp-dates" method="GET" action="{{ route('admin.financial-reports.index') }}">
            <input type="hidden" name="view" value="{{ $view }}"><input type="hidden" name="tab" value="{{ $activeTab }}" data-rp-tab-input>
            <div class="rp-dates-box"><i class="far fa-calendar-alt"></i><input type="date" name="start_date" value="{{ $report['period']['start'] }}" aria-label="شروع تاریخ" required><i class="fas fa-arrow-left"></i><input type="date" name="end_date" value="{{ $report['period']['end'] }}" aria-label="آخری تاریخ" required></div>
            <button class="rp-apply" type="submit"><i class="fas fa-filter"></i> دکھائیں</button>
            <a class="rp-preset" href="{{ route('admin.financial-reports.index', ['view' => $view, 'start_date' => now()->subDays(6)->toDateString(), 'end_date' => now()->toDateString()]) }}">7 دن</a>
            <a class="rp-preset" href="{{ route('admin.financial-reports.index', ['view' => $view, 'start_date' => now()->subDays(29)->toDateString(), 'end_date' => now()->toDateString()]) }}">30 دن</a>
            <a class="rp-preset" href="{{ route('admin.financial-reports.index', ['view' => $view]) }}">اس مہینے</a>
        </form>
        <div class="rp-profit"><span>نفع و نقصان:</span><span>آمدنی <b>{{ $money($report['summary']['total_revenue']) }}</b></span><span>− اخراجات <b>{{ $money($totalExpenses) }}</b></span><span>= خالص منافع <b class="{{ $report['summary']['net_profit'] < 0 ? 'is-red' : 'is-green' }}">{{ $money($report['summary']['net_profit']) }}</b></span><a href="{{ route('admin.financial-reports.export', ['section' => 'summary'] + $period) }}"><i class="fas fa-file-csv"></i> CSV</a></div>
    </div>

    <div class="rp-kpis">
        @foreach($cards as $card)
            @php
                $tag = isset($card['tab']) ? 'a' : 'article';
                $good = $card['change'] !== null && $card['change'] !== false ? ($card['change'] >= 0) === ($card['title'] !== 'کل اخراجات') : null;
            @endphp
            <{{ $tag }} class="rp-kpi {{ $card['color'] }}" @isset($card['tab']) href="{{ $link(['tab' => $card['tab']]) }}" data-rp-tab-jump="{{ $card['tab'] }}" @endisset>
                <span class="rp-kpi-icon"><i class="fas {{ $card['icon'] }}"></i></span>
                <div class="rp-kpi-body"><small>{{ $card['title'] }}</small><strong>{!! $card['value'] !!}</strong>
                    {{-- Nothing to compare against: leave the line out rather than say so. --}}
                    @if($card['change'] === false)
                        <span class="rp-change">{{ $card['note'] }}</span>
                    @elseif($card['change'] !== null)
                        <span class="rp-change"><b class="{{ $good ? 'is-good' : 'is-bad' }}"><i class="fas fa-arrow-{{ $card['change'] >= 0 ? 'up' : 'down' }}"></i> {{ abs($card['change']) }}%</b> پچھلے {{ $report['days'] }} دن کے مقابلے</span>
                    @endif
                    </div>
            </{{ $tag }}>
        @endforeach
    </div>

    <div class="rp-row">
        <section class="rp-card">
            <div class="rp-card-head"><h3>{{ $isSales ? 'فروخت کا رجحان' : 'سلائی آمدنی کا رجحان' }}</h3><div class="rp-legend">@foreach($series as $item)<span><i style="background:{{ $item['color'] }}"></i> {{ $item['label'] }}</span>@endforeach</div></div>
            @if($hasChartData)
                <svg class="rp-chart" viewBox="0 0 {{ $cw }} {{ $ch }}" role="img" aria-label="روزانہ فروخت">
                    <defs><linearGradient id="rpCloth" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#22c55e" stop-opacity=".3"/><stop offset="1" stop-color="#22c55e" stop-opacity=".02"/></linearGradient><linearGradient id="rpTailoring" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#3b82f6" stop-opacity=".22"/><stop offset="1" stop-color="#3b82f6" stop-opacity=".02"/></linearGradient></defs>
                    @foreach(range(0, 4) as $step)
                        @php $tick = $chartTop * $step / 4; @endphp
                        <line x1="{{ $left }}" x2="{{ $cw - $right }}" y1="{{ round($py($tick), 1) }}" y2="{{ round($py($tick), 1) }}"/>
                        <text x="{{ $left - 8 }}" y="{{ round($py($tick), 1) + 4 }}" text-anchor="end">Rs. {{ number_format($tick) }}</text>
                    @endforeach
                    @foreach($series as $item)
                        <polygon points="{{ $area($item['key']) }}" fill="url(#{{ $item['fill'] }})"/>
                        <polyline points="{{ $line($item['key']) }}" fill="none" stroke="{{ $item['color'] }}" stroke-width="2.4" stroke-linejoin="round"/>
                    @endforeach
                    @foreach($dates as $index => $date)
                        @if($index % $labelEvery === 0 || $index === $points - 1)
                            <text x="{{ round($px($index), 1) }}" y="{{ $ch - 8 }}" text-anchor="middle">{{ \Carbon\Carbon::parse($date)->format('d M') }}</text>
                        @endif
                        <circle cx="{{ round($px($index), 1) }}" cy="{{ round($py($daily[$date][$seriesKey]), 1) }}" r="7" fill="transparent"><title>{{ $date }} — Rs. {{ number_format($daily[$date][$seriesKey]) }}</title></circle>
                    @endforeach
                </svg>
            @else
                <div class="rp-empty">اس مدت میں ابھی کوئی فروخت نہیں۔</div>
            @endif
        </section>

        <section class="rp-card">
            <div class="rp-card-head"><h3>{{ $isSales ? 'فروخت کی تقسیم' : 'آرڈرز کی حالت' }}</h3></div>
            @if($sliceTotal > 0)
                <div class="rp-donut-wrap">
                    <div class="rp-donut" style="background:conic-gradient({{ implode(', ', $gradient) }})"><div class="rp-donut-center"><div><small>{{ $isSales ? 'کل فروخت' : 'کل آرڈرز' }}</small><strong>{{ $isSales ? $money($sliceTotal) : number_format($sliceTotal) }}</strong></div></div></div>
                    <div class="rp-donut-legend">
                        @foreach($slices as $label => $amount)
                            <div><i style="background:{{ $palette[$loop->index % count($palette)] }}"></i><span>{{ $label }}</span><b>{{ $isSales ? $money($amount) : number_format($amount) }}</b><em>{{ number_format($amount / $sliceTotal * 100, 1) }}%</em></div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="rp-empty">اس مدت میں کچھ نہیں۔</div>
            @endif
        </section>
    </div>

    <div class="rp-row">
        @if($isSales)
            <section class="rp-card">
                <div class="rp-card-head"><h3><i class="fas fa-shopping-cart"></i> حالیہ فروخت</h3><a class="rp-more" href="{{ $link(['tab' => 'all-sales']) }}" data-rp-tab-jump="all-sales">سب دیکھیں <i class="fas fa-arrow-left"></i></a></div>
                <div class="table-responsive"><table class="rp-table"><thead><tr><th>#</th><th>رقم</th><th>ادائیگی</th><th>گاہک</th><th>آئٹمز</th><th>تاریخ</th></tr></thead><tbody>
                    @forelse($report['recent'] as $sale)
                        <tr><td class="is-num">{{ $loop->iteration }}</td><td><span class="rp-money is-green">{{ $money($sale['amount']) }}</span></td><td><span class="rp-pill {{ $sale['method'] === 'نقد' ? 'is-green' : 'is-blue' }}">{{ $sale['method'] }}</span></td><td>{{ $sale['customer'] }}</td><td class="is-num">{{ $sale['items'] }}</td><td class="is-num">{{ \Carbon\Carbon::parse($sale['date'])->format('Y-m-d') }}</td></tr>
                    @empty
                        <tr><td colspan="6" class="rp-empty">اس مدت میں کوئی فروخت نہیں۔</td></tr>
                    @endforelse
                </tbody></table></div>
            </section>
            <section class="rp-card">
                <div class="rp-card-head"><h3><i class="fas fa-trophy"></i> سب سے زیادہ بکنے والے سیٹ</h3><a class="rp-more" href="{{ $link(['tab' => 'categories']) }}" data-rp-tab-jump="categories">کیٹیگری دیکھیں <i class="fas fa-arrow-left"></i></a></div>
                <div class="table-responsive"><table class="rp-table"><thead><tr><th>سیٹ</th><th>کیٹیگری</th><th>مقدار</th><th>رقم</th></tr></thead><tbody>
                    @forelse($current['categories']['top_sets']->take(5) as $set)
                        <tr><td><strong>{{ $set['name'] }}</strong></td><td class="rp-muted">{{ $set['category'] ?: '—' }}</td><td><span class="rp-num">{{ $qty($set['quantity'], $set['unit']) }}</span> {{ $set['unit_label'] }}</td><td><span class="rp-money">{{ $money($set['amount']) }}</span></td></tr>
                    @empty
                        <tr><td colspan="4" class="rp-empty">ابھی کوئی فروخت نہیں۔</td></tr>
                    @endforelse
                </tbody></table></div>
            </section>
        @else
            <section class="rp-card">
                <div class="rp-card-head"><h3><i class="fas fa-cut"></i> حالیہ سلائی آرڈرز</h3><a class="rp-more" href="{{ $link(['tab' => 'orders']) }}" data-rp-tab-jump="orders">سب دیکھیں <i class="fas fa-arrow-left"></i></a></div>
                <div class="table-responsive"><table class="rp-table"><thead><tr><th>آرڈر #</th><th>گاہک</th><th>رقم</th><th>حالت</th><th>تاریخ</th></tr></thead><tbody>
                    @forelse($report['recent'] as $order)
                        <tr><td class="is-num">T-{{ $order->id }}</td><td>{{ $order->customer_name ?? '—' }}</td><td><span class="rp-money">{{ $money($order->totalPayment) }}</span></td><td><span class="rp-pill {{ $statusPill[$order->status] ?? 'is-gray' }}">{{ $statusLabels[$order->status] ?? $order->status }}</span></td><td class="is-num">{{ $order->created_at?->format('Y-m-d') }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="rp-empty">اس مدت میں کوئی آرڈر نہیں۔</td></tr>
                    @endforelse
                </tbody></table></div>
            </section>
            <section class="rp-card">
                <div class="rp-card-head"><h3><i class="fas fa-user-tie"></i> درزیوں کا خلاصہ</h3><a class="rp-more" href="{{ $link(['tab' => 'tailors']) }}" data-rp-tab-jump="tailors">سب دیکھیں <i class="fas fa-arrow-left"></i></a></div>
                <div class="table-responsive"><table class="rp-table"><thead><tr><th>درزی</th><th>اس مدت کے سوٹ</th><th>دینا ہے</th></tr></thead><tbody>
                    @forelse($report['tailors']->take(5) as $tailor)
                        <tr><td><strong>{{ $tailor->name }}</strong></td><td class="is-num">{{ number_format((int) $tailor->period_suits) }}</td><td><span class="rp-money is-red">{{ $money($tailor->balance) }}</span></td></tr>
                    @empty
                        <tr><td colspan="3" class="rp-empty">اس مدت میں کسی درزی کا کام نہیں۔</td></tr>
                    @endforelse
                </tbody></table></div>
            </section>
        @endif
    </div>

    <div class="rp-strip">
        @if($isSales)
            @php $topSet = $current['categories']['top_sets']->first(); @endphp
            <div class="rp-strip-item"><span class="rp-strip-icon"><i class="fas fa-chart-line"></i></span><div><small>سب سے زیادہ بکنے والا سیٹ</small><strong>{{ $topSet['name'] ?? '—' }}</strong><span>{{ $topSet ? $money($topSet['amount']).' ('.$qty($topSet['quantity'], $topSet['unit']).' '.$topSet['unit_label'].')' : '' }}</span></div></div>
            <div class="rp-strip-item"><span class="rp-strip-icon"><i class="fas fa-user-friends"></i></span><div><small>سب سے بڑا گاہک</small><strong>{{ $report['top_customer']['name'] ?? '—' }}</strong><span>{{ $report['top_customer'] ? $money($report['top_customer']['amount']).' ('.$report['top_customer']['count'].' بل)' : '' }}</span></div></div>
            <div class="rp-strip-item"><span class="rp-strip-icon"><i class="fas fa-file-invoice"></i></span><div><small>فروخت کے بل</small><strong>{{ number_format($current['bills']) }} بل</strong><span>{{ $money($current['revenue']) }}</span></div></div>
            <div class="rp-strip-item"><span class="rp-strip-icon"><i class="far fa-calendar-alt"></i></span><div><small>اوسط بل</small><strong class="rp-money">{{ $money($current['bills'] ? $current['revenue'] / $current['bills'] : 0) }}</strong><span>{{ $report['days'] }} دن</span></div></div>
        @else
            @php $topTailor = $report['tailors']->sortByDesc(fn ($tailor) => (int) $tailor->period_suits)->first(); @endphp
            <div class="rp-strip-item"><span class="rp-strip-icon"><i class="fas fa-medal"></i></span><div><small>سب سے زیادہ سوٹ</small><strong>{{ $topTailor && $topTailor->period_suits > 0 ? $topTailor->name : '—' }}</strong><span>{{ $topTailor && $topTailor->period_suits > 0 ? number_format((int) $topTailor->period_suits).' سوٹ' : '' }}</span></div></div>
            <div class="rp-strip-item"><span class="rp-strip-icon"><i class="fas fa-user-friends"></i></span><div><small>سب سے بڑا گاہک</small><strong>{{ $report['top_customer']['name'] ?? '—' }}</strong><span>{{ $report['top_customer'] ? $money($report['top_customer']['amount']).' ('.$report['top_customer']['count'].' آرڈر)' : '' }}</span></div></div>
            <div class="rp-strip-item"><span class="rp-strip-icon"><i class="fas fa-cut"></i></span><div><small>سلائی آرڈرز</small><strong>{{ number_format($current['orders']) }} آرڈرز</strong><span>{{ $money($current['revenue']) }}</span></div></div>
            <div class="rp-strip-item"><span class="rp-strip-icon"><i class="far fa-calendar-alt"></i></span><div><small>اوسط آرڈر</small><strong class="rp-money">{{ $money($current['orders'] ? $current['revenue'] / $current['orders'] : 0) }}</strong><span>{{ $report['days'] }} دن</span></div></div>
        @endif
    </div>

    <section class="rp-tabs-card" id="rp-details">
        <nav class="rp-tabs" role="tablist" aria-label="تفصیلی رپورٹ">
            @foreach($tabs as $key => $tab)
                <button type="button" role="tab" id="rp-tab-{{ $key }}" class="rp-tab {{ $activeTab === $key ? 'is-active' : '' }}" data-rp-tab="{{ $key }}" aria-controls="rp-panel-{{ $key }}" aria-selected="{{ $activeTab === $key ? 'true' : 'false' }}"><i class="fas {{ $tab['icon'] }}"></i> {{ $tab['label'] }}@if($tab['badge'])<span class="rp-tab-badge">{{ $tab['badge'] }}</span>@endif</button>
            @endforeach
        </nav>

        @if($isSales)
            {{-- Sales per category, in the unit each category counts stock in. --}}
            <div class="rp-tab-panel" id="rp-panel-categories" role="tabpanel" aria-labelledby="rp-tab-categories" @if($activeTab !== 'categories') hidden @endif>
                <div class="table-responsive"><table class="rp-table"><thead><tr><th>کیٹیگری</th><th>فروخت</th><th>واپسی</th><th>خالص فروخت</th><th>رقم</th><th>منافع</th></tr></thead><tbody>
                    @forelse($current['categories']['rows'] as $row)
                        <tr><td><strong>{{ $row['category'] }}</strong><div class="rp-muted">{{ $row['sales_count'] }} فروخت</div></td>
                            <td><span class="rp-num">{{ $qty($row['sold'], $row['unit']) }}</span> {{ $row['unit_label'] }}</td>
                            <td>@if($row['returned'] > 0)<span class="rp-num">{{ $qty($row['returned'], $row['unit']) }}</span> {{ $row['unit_label'] }}@else — @endif</td>
                            <td><strong><span class="rp-num">{{ $qty($row['net'], $row['unit']) }}</span> {{ $row['unit_label'] }}</strong></td>
                            <td><span class="rp-money">{{ $money($row['net_amount']) }}</span></td>
                            <td><span class="rp-money {{ $row['profit'] < 0 ? 'is-red' : 'is-green' }}">{{ $money($row['profit']) }}</span></td></tr>
                    @empty
                        <tr><td colspan="6" class="rp-empty">اس مدت میں کپڑے کی کوئی فروخت نہیں ہوئی۔</td></tr>
                    @endforelse
                </tbody></table></div>
            </div>

            <div class="rp-tab-panel" id="rp-panel-all-sales" role="tabpanel" aria-labelledby="rp-tab-all-sales" @if($activeTab !== 'all-sales') hidden @endif>
                <div class="rp-panel-head"><h4>تمام فروخت</h4>
                    <form class="rp-filter" method="GET">@foreach($keep(['tab' => 'all-sales']) as $name => $value)<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endforeach
                        <select name="sales_category" class="form-control" onchange="this.form.submit()"><option value="">تمام کیٹیگریز</option>@foreach($report['category_options'] as $category)<option value="{{ $category->id }}" @selected((int) ($filters['sales_category'] ?? 0) === $category->id)>{{ $category->fullName() }}</option>@endforeach</select>
                    </form></div>
                <div class="table-responsive"><table class="rp-table"><thead><tr><th>تاریخ</th><th>سیٹ</th><th>کیٹیگری</th><th>مقدار</th><th>رقم</th><th>گاہک</th></tr></thead><tbody>
                    @forelse($report['lines'] as $line)
                        <tr><td class="is-num">{{ \Carbon\Carbon::parse($line['date'])->format('Y-m-d') }}</td><td><strong>{{ $line['name'] }}</strong>@if($line['color'] && $line['color'] !== 'عام')<div class="rp-muted">{{ $line['color'] }}</div>@endif</td><td class="rp-muted">{{ $line['category'] }}</td><td><span class="rp-num">{{ $qty($line['quantity'], $line['unit']) }}</span> {{ $unitLabel($line['unit']) }}</td><td><span class="rp-money">{{ $money($line['amount']) }}</span></td><td>{{ $line['customer'] }}</td></tr>
                    @empty
                        <tr><td colspan="6" class="rp-empty">اس مدت میں کوئی فروخت نہیں۔</td></tr>
                    @endforelse
                </tbody></table></div>
                @if($report['lines']->hasPages())<div class="rp-pagination">{{ $report['lines']->links() }}</div>@endif
            </div>

            <div class="rp-tab-panel" id="rp-panel-suppliers" role="tabpanel" aria-labelledby="rp-tab-suppliers" @if($activeTab !== 'suppliers') hidden @endif>
                <div class="rp-panel-head"><h4>سپلائرز کو دینا ہے <span class="rp-panel-total rp-money is-red">{{ $money($report['summary']['payables']) }}</span></h4>
                    <form class="rp-filter" method="GET">@foreach($keep(['tab' => 'suppliers']) as $name => $value)<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endforeach<input name="payables_q" class="form-control" maxlength="100" value="{{ $filters['payables_q'] ?? '' }}" placeholder="سپلائر یا فون تلاش کریں"><button><i class="fas fa-search"></i></button><a class="rp-more" href="{{ route('admin.financial-reports.export', ['section' => 'payables'] + $period) }}"><i class="fas fa-download"></i> CSV</a></form></div>
                <div class="table-responsive"><table class="rp-table"><thead><tr><th>سپلائر</th><th>فون</th><th>دینا ہے</th></tr></thead><tbody>
                    @forelse($report['payables'] as $row)
                        <tr><td><strong>{{ $row->name }}</strong></td><td class="is-num">{{ $row->phone }}</td><td><span class="rp-money is-red">{{ $money($row->balance) }}</span></td></tr>
                    @empty
                        <tr><td colspan="3" class="rp-empty">کسی سپلائر کو کچھ دینا باقی نہیں۔</td></tr>
                    @endforelse
                </tbody></table></div>
                @if($report['payables']->hasPages())<div class="rp-pagination">{{ $report['payables']->links() }}</div>@endif
            </div>

            <div class="rp-tab-panel" id="rp-panel-stock" role="tabpanel" aria-labelledby="rp-tab-stock" @if($activeTab !== 'stock') hidden @endif>
                <div class="rp-mini-cards">
                    <div class="rp-mini"><small>موجودہ اسٹاک کی مالیت</small><strong>{{ $money($report['summary']['inventory_value']) }}</strong></div>
                    <div class="rp-mini"><small>اس مدت کی خریداریاں</small><strong>{{ $money($report['summary']['purchases']) }}</strong></div>
                    <div class="rp-mini"><small>خریداری واپسی</small><strong>{{ $money($report['summary']['purchase_returns']) }}</strong></div>
                </div>
                <div class="table-responsive"><table class="rp-table"><thead><tr><th>کیٹیگری</th><th>سیٹ</th><th>دستیاب اسٹاک</th><th>مالیت</th></tr></thead><tbody>
                    @forelse($report['stock'] as $row)
                        <tr><td><strong>{{ $row['category'] }}</strong></td><td class="is-num">{{ $row['sets'] }}</td><td><span class="rp-num">{{ $qty($row['quantity'], $row['unit']) }}</span> {{ $unitLabel($row['unit']) }}</td><td><span class="rp-money">{{ $money($row['value']) }}</span></td></tr>
                    @empty
                        <tr><td colspan="4" class="rp-empty">ابھی کوئی اسٹاک نہیں۔</td></tr>
                    @endforelse
                </tbody></table></div>
            </div>
        @else
            <div class="rp-tab-panel" id="rp-panel-orders" role="tabpanel" aria-labelledby="rp-tab-orders" @if($activeTab !== 'orders') hidden @endif>
                <div class="rp-panel-head"><h4>تمام سلائی آرڈرز</h4>
                    <form class="rp-filter" method="GET">@foreach($keep(['tab' => 'orders']) as $name => $value)<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endforeach
                        <select name="order_status" class="form-control" onchange="this.form.submit()"><option value="">تمام حالتیں</option>@foreach($report['status_filter'] as $status => $label)<option value="{{ $status }}" @selected(($filters['order_status'] ?? '') === $status)>{{ $label }}</option>@endforeach</select>
                    </form></div>
                <div class="table-responsive"><table class="rp-table"><thead><tr><th>آرڈر #</th><th>گاہک</th><th>درزی</th><th>سوٹ</th><th>رقم</th><th>حالت</th><th>واپسی کی تاریخ</th><th>تاریخ</th></tr></thead><tbody>
                    @forelse($report['orders'] as $order)
                        <tr><td class="is-num">T-{{ $order->id }}</td><td>{{ $order->customer_name ?? '—' }}</td><td>{{ $order->tailor_name ?? '—' }}</td><td class="is-num">{{ max(1, (int) $order->suitQuantity) }}</td><td><span class="rp-money">{{ $money($order->totalPayment) }}</span></td><td><span class="rp-pill {{ $statusPill[$order->status] ?? 'is-gray' }}">{{ $statusLabels[$order->status] ?? $order->status }}</span></td><td class="is-num">{{ $order->returnDate ? substr($order->returnDate, 0, 10) : '—' }}</td><td class="is-num">{{ $order->created_at?->format('Y-m-d') }}</td></tr>
                    @empty
                        <tr><td colspan="8" class="rp-empty">اس مدت میں کوئی آرڈر نہیں۔</td></tr>
                    @endforelse
                </tbody></table></div>
                @if($report['orders']->hasPages())<div class="rp-pagination">{{ $report['orders']->links() }}</div>@endif
            </div>

            <div class="rp-tab-panel" id="rp-panel-tailors" role="tabpanel" aria-labelledby="rp-tab-tailors" @if($activeTab !== 'tailors') hidden @endif>
                <div class="rp-panel-head"><h4>درزی اور سوٹ <span class="rp-panel-total rp-money is-red">{{ $money($report['tailors']->sum('balance')) }}</span></h4><span class="rp-muted">اس مدت کے سوٹ، اور آخری تاریخ تک کی کل اجرت میں سے ادا شدہ رقم</span></div>
                <div class="table-responsive"><table class="rp-table"><thead><tr><th>درزی</th><th>فون</th><th>اس مدت کے آرڈرز</th><th>اس مدت کے سوٹ</th><th>اس مدت کی اجرت</th><th>کل اجرت</th><th>ادا شدہ</th><th>دینا ہے</th></tr></thead><tbody>
                    @forelse($report['tailors'] as $tailor)
                        <tr><td><strong>{{ $tailor->name }}</strong></td><td class="is-num">{{ $tailor->phone_number1 }}</td><td class="is-num">{{ number_format((int) $tailor->period_jobs) }}</td><td class="is-num">{{ number_format((int) $tailor->period_suits) }}</td><td><span class="rp-money">{{ $money($tailor->period_earned) }}</span></td><td><span class="rp-money">{{ $money($tailor->earned) }}</span></td><td><span class="rp-money is-green">{{ $money($tailor->paid) }}</span></td><td><span class="rp-money is-red">{{ $money($tailor->balance) }}</span></td></tr>
                    @empty
                        <tr><td colspan="8" class="rp-empty">اس مدت میں کسی درزی کا کام نہیں۔</td></tr>
                    @endforelse
                </tbody></table></div>
            </div>

            <div class="rp-tab-panel" id="rp-panel-payouts" role="tabpanel" aria-labelledby="rp-tab-payouts" @if($activeTab !== 'payouts') hidden @endif>
                <div class="rp-panel-head"><h4>ادائیگیاں اور تنخواہیں <span class="rp-panel-total rp-money is-red">{{ $money($report['payouts']->sum('amount')) }}</span></h4></div>
                <div class="table-responsive"><table class="rp-table"><thead><tr><th>تاریخ</th><th>نام</th><th>قسم</th><th>نوٹ</th><th>رقم</th></tr></thead><tbody>
                    @forelse($report['payouts'] as $payout)
                        <tr><td class="is-num">{{ \Carbon\Carbon::parse($payout['date'])->format('Y-m-d') }}</td><td><strong>{{ $payout['name'] }}</strong></td><td><span class="rp-pill {{ $payout['kind'] === 'تنخواہ' ? 'is-purple' : 'is-blue' }}">{{ $payout['kind'] }}</span></td><td class="rp-muted">{{ $payout['note'] ?: '—' }}</td><td><span class="rp-money is-red">{{ $money($payout['amount']) }}</span></td></tr>
                    @empty
                        <tr><td colspan="5" class="rp-empty">اس مدت میں کوئی ادائیگی نہیں۔</td></tr>
                    @endforelse
                </tbody></table></div>
            </div>
        @endif

        {{-- Customers who still owe for this side (cloth or stitching). --}}
        <div class="rp-tab-panel" id="rp-panel-customers" role="tabpanel" aria-labelledby="rp-tab-customers" @if($activeTab !== 'customers') hidden @endif>
            <div class="rp-panel-head"><h4>{{ $isSales ? 'کپڑے کی فروخت کا بقایا' : 'سلائی کا بقایا' }} <span class="rp-panel-total rp-money is-green">{{ $money($report['customers']['total']) }}</span></h4>
                <form class="rp-filter" method="GET">@foreach($keep(['tab' => 'customers']) as $name => $value)<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endforeach<input name="customers_q" class="form-control" maxlength="100" value="{{ $filters['customers_q'] ?? '' }}" placeholder="گاہک یا فون تلاش کریں"><button><i class="fas fa-search"></i></button></form></div>
            <div class="table-responsive"><table class="rp-table"><thead><tr><th>گاہک</th><th>فون</th><th>{{ $isSales ? 'کپڑے کا بقایا' : 'سلائی کا بقایا' }}</th><th>کل بقایا</th></tr></thead><tbody>
                @forelse($report['customers']['list'] as $row)
                    <tr><td><strong>{{ $row['name'] }}</strong></td><td class="is-num">{{ $row['phone'] }}</td><td><span class="rp-money is-green">{{ $money($row['balance']) }}</span></td><td><span class="rp-money">{{ $money($row['total']) }}</span></td></tr>
                @empty
                    <tr><td colspan="4" class="rp-empty">کسی گاہک کا بقایا نہیں۔</td></tr>
                @endforelse
            </tbody></table></div>
            @if($report['customers']['list']->hasPages())<div class="rp-pagination">{{ $report['customers']['list']->links() }}</div>@endif
        </div>
    </section>
</div></section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Picking a date reloads the report straight away; no extra click needed.
    var dateForm = document.querySelector('.rp-dates'),
        startInput = dateForm.querySelector('[name="start_date"]'),
        endInput = dateForm.querySelector('[name="end_date"]');
    [startInput, endInput].forEach(function (input) {
        input.addEventListener('change', function () {
            // Wait for a full date (not a half-typed year).
            if (!startInput.value || !endInput.value || startInput.value < '2000-01-01' || endInput.value < '2000-01-01') return;
            if (startInput.value > endInput.value) {
                (input === startInput ? endInput : startInput).value = input.value;
            }
            dateForm.submit();
        });
    });

    var tabs = document.querySelectorAll('[data-rp-tab]');
    function showTab(key) {
        tabs.forEach(function (tab) {
            var active = tab.dataset.rpTab === key;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
            document.getElementById('rp-panel-' + tab.dataset.rpTab).hidden = !active;
        });
        // Keep the open tab when the dates change or the page reloads.
        document.querySelectorAll('[data-rp-tab-input]').forEach(function (input) { input.value = key; });
        var url = new URL(window.location.href);
        url.searchParams.set('tab', key);
        window.history.replaceState(null, '', url.toString());
    }
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () { showTab(tab.dataset.rpTab); });
    });
    // "See all" buttons and owed cards open their tab in place.
    document.querySelectorAll('[data-rp-tab-jump]').forEach(function (link) {
        link.addEventListener('click', function (event) {
            if (!document.getElementById('rp-panel-' + link.dataset.rpTabJump)) return;
            event.preventDefault();
            showTab(link.dataset.rpTabJump);
            document.getElementById('rp-details').scrollIntoView({behavior: 'smooth', block: 'start'});
        });
    });
});
</script>
@endpush
