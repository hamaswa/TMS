@extends('main')

@section('content')

    <style>
        .weekly-orders-page {
            --wo-blue: #1769e0;
            --wo-navy: #12385b;
            --wo-muted: #718198;
            --wo-line: #dce6f1;
            --wo-canvas: #f5f8fb;
            min-height: calc(100vh - 70px);
            background: var(--wo-canvas)
        }

        .weekly-orders-page [hidden] {
            display: none !important
        }

        .wo-shell {
            width: min(100% - 38px, 1540px);
            margin: 0 auto;
            padding: 26px 0 48px
        }

        .wo-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 16px
        }

        .wo-head h1 {
            margin: 0;
            color: var(--wo-navy);
            font-size: 1.62rem;
            font-weight: 900
        }

        .wo-head p {
            margin: 5px 0 0;
            color: var(--wo-muted);
            font-size: .82rem
        }

        .wo-new-order {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 10px;
            color: #fff;
            background: var(--wo-blue);
            font-weight: 900;
            text-decoration: none;
            box-shadow: 0 7px 18px rgba(23, 105, 224, .2)
        }

        .wo-new-order:hover {
            color: #fff;
            background: #0e5bc9;
            text-decoration: none
        }

        .wo-toolbar {
            display: grid;
            grid-template-columns: minmax(250px, 1.2fr) repeat(3, minmax(140px, .55fr));
            gap: 10px;
            margin-bottom: 12px
        }

        .wo-control {
            position: relative
        }

        .wo-control i {
            position: absolute;
            top: 50%;
            right: 14px;
            z-index: 1;
            color: #7890aa;
            transform: translateY(-50%)
        }

        .wo-control input,
        .wo-control select {
            width: 100%;
            height: 46px;
            padding: 7px 42px 7px 13px;
            border: 1px solid var(--wo-line);
            border-radius: 11px;
            color: #334f6c;
            background: #fff;
            font-family: inherit;
            font-size: .76rem;
            outline: none
        }

        .wo-control input:focus,
        .wo-control select:focus {
            border-color: #8cb8f2;
            box-shadow: 0 0 0 3px rgba(23, 105, 224, .09)
        }

        .wo-filter-tabs {
            display: flex;
            align-items: center;
            gap: 7px;
            overflow-x: auto;
            margin-bottom: 14px;
            padding-bottom: 2px
        }

        .wo-filter-tabs a {
            flex: 0 0 auto;
            padding: 7px 12px;
            border: 1px solid var(--wo-line);
            border-radius: 999px;
            color: #526981;
            background: #fff;
            font-size: .73rem;
            font-weight: 800;
            text-decoration: none
        }

        .wo-filter-tabs a:hover {
            color: var(--wo-blue);
            border-color: #a8c8f0
        }

        .wo-filter-tabs a.is-current {
            color: #fff;
            border-color: var(--wo-blue);
            background: var(--wo-blue)
        }

        .wo-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 11px;
            margin-bottom: 14px
        }

        .wo-summary-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            min-height: 86px;
            padding: 13px 16px;
            border: 1px solid var(--wo-line);
            border-radius: 13px;
            background: #fff
        }

        .wo-summary-icon {
            display: grid;
            place-items: center;
            flex: 0 0 42px;
            width: 42px;
            height: 42px;
            border-radius: 12px;
            color: var(--wo-blue);
            background: #e8f2ff
        }

        .wo-summary-card small {
            display: block;
            color: var(--wo-muted);
            font-size: .7rem
        }

        .wo-summary-card strong {
            display: block;
            margin-top: 2px;
            color: var(--wo-navy);
            font-size: 1.25rem;
            font-weight: 900
        }

        .wo-summary-card.is-work .wo-summary-icon {
            color: #b36d00;
            background: #fff2d7
        }

        .wo-summary-card.is-ready .wo-summary-icon {
            color: #128354;
            background: #e4f7ed
        }

        .wo-summary-card.is-overdue .wo-summary-icon {
            color: #ce3847;
            background: #ffebed
        }

        .wo-summary-card.is-overdue strong {
            color: #c92f40
        }

        .wo-week-bar {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 14px;
            padding: 9px;
            border: 1px solid var(--wo-line);
            border-radius: 13px;
            background: #fff
        }

        .wo-week-arrow {
            display: grid;
            place-items: center;
            flex: 0 0 38px;
            width: 38px;
            height: 38px;
            border: 1px solid #d8e3ef;
            border-radius: 9px;
            color: #506981;
            background: #fff;
            text-decoration: none
        }

        .wo-week-days {
            display: grid;
            grid-template-columns: repeat(7, minmax(86px, 1fr));
            flex: 1;
            gap: 5px
        }

        .wo-week-day {
            padding: 7px 5px;
            border-radius: 9px;
            color: #60758d;
            text-align: center;
            text-decoration: none
        }

        .wo-week-day b {
            display: block;
            color: #344f6c;
            font-size: .76rem
        }

        .wo-week-day small {
            display: block;
            margin-top: 2px;
            font-size: .65rem
        }

        .wo-week-day:hover,
        .wo-week-day.is-today {
            color: var(--wo-blue);
            background: #eaf3ff;
            text-decoration: none
        }

        .wo-range {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
            padding: 11px 15px;
            border: 1px solid var(--wo-line);
            border-radius: 11px;
            background: #fff
        }

        .wo-range strong {
            color: var(--wo-navy);
            font-size: .82rem
        }

        .wo-range span {
            color: var(--wo-muted);
            font-size: .73rem
        }

        .wo-days {
            display: grid;
            gap: 12px
        }

        .wo-day {
            overflow: hidden;
            border: 1px solid var(--wo-line);
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 5px 16px rgba(25, 55, 88, .035)
        }

        .wo-day.is-today {
            border-color: #91bdf5
        }

        .wo-day-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 11px 15px;
            border-bottom: 1px solid #e6edf5;
            background: #f8fafc
        }

        .wo-day.is-today .wo-day-head {
            background: #edf6ff
        }

        .wo-day-title {
            display: flex;
            align-items: center;
            gap: 10px
        }

        .wo-date-box {
            display: grid;
            place-items: center;
            min-width: 42px;
            height: 42px;
            border-radius: 10px;
            color: var(--wo-blue);
            background: #e7f1ff;
            font-size: 1rem;
            font-weight: 900
        }

        .wo-day-title h2 {
            margin: 0;
            color: var(--wo-navy);
            font-size: .94rem;
            font-weight: 900
        }

        .wo-day-title small {
            display: inline-block;
            margin-right: 7px;
            color: var(--wo-muted);
            font-weight: 500
        }

        .wo-day-count {
            padding: 5px 10px;
            border-radius: 999px;
            color: #60738a;
            background: #edf2f7;
            font-size: .68rem;
            font-weight: 800
        }

        .wo-table-head,
        .wo-order {
            display: grid;
            grid-template-columns: minmax(185px, 1.35fr) minmax(135px, .9fr) minmax(150px, 1fr) 110px minmax(160px, 1fr) minmax(105px, .7fr) 102px;
            align-items: center;
            gap: 9px
        }

        .wo-table-head {
            padding: 8px 15px;
            color: #7b8ca0;
            background: #fbfcfe;
            font-size: .64rem;
            font-weight: 800
        }

        .wo-order {
            position: relative;
            min-height: 78px;
            padding: 10px 15px;
            border-top: 1px solid #e8eef5;
            background: #fff
        }

        .wo-order:hover {
            z-index: 1;
            background: #f5f9ff;
            box-shadow: inset 3px 0 0 var(--wo-blue)
        }

        .wo-order.is-overdue {
            box-shadow: inset -3px 0 0 #dc3545
        }

        .wo-primary {
            min-width: 0
        }

        .wo-primary strong,
        .wo-garment strong {
            display: block;
            overflow: hidden;
            color: #203b5a;
            font-size: .83rem;
            text-overflow: ellipsis;
            white-space: nowrap
        }

        .wo-primary small,
        .wo-garment small {
            display: block;
            margin-top: 3px;
            overflow: hidden;
            color: var(--wo-muted);
            font-size: .65rem;
            text-overflow: ellipsis;
            white-space: nowrap
        }

        .wo-due {
            direction: ltr;
            color: #324d68;
            font-size: .72rem;
            font-weight: 800;
            text-align: right
        }

        .wo-due small {
            display: block;
            margin-top: 2px;
            color: #8191a4;
            font-size: .62rem
        }

        .wo-order.is-overdue .wo-due,
        .wo-order.is-overdue .wo-due small {
            color: #cf3545
        }

        .wo-inline-form {
            display: flex;
            align-items: center;
            gap: 5px
        }

        .wo-inline-select {
            min-width: 0;
            width: 100%;
            height: 36px;
            padding: 4px 8px;
            border: 1px solid #d9e3ef;
            border-radius: 8px;
            color: #38536e;
            background: #fff;
            font-family: inherit;
            font-size: .68rem
        }

        .wo-inline-save {
            display: grid;
            place-items: center;
            flex: 0 0 34px;
            width: 34px;
            height: 34px;
            border: 0;
            border-radius: 8px;
            color: #fff;
            background: var(--wo-blue)
        }

        .wo-inline-select:disabled {
            color: #7e8ea0;
            background: #f3f6f9
        }

        .wo-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 8px;
            border-radius: 8px;
            color: #9b6200;
            background: #fff1d5;
            font-size: .64rem;
            font-weight: 900
        }

        .wo-status.is-ready {
            color: #138052;
            background: #e4f7ed
        }

        .wo-status.is-delivered {
            color: #60738a;
            background: #edf2f7
        }

        .wo-status.is-overdue {
            color: #c93142;
            background: #ffeaed
        }

        .wo-worker-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-height: 36px;
            padding: 5px 9px;
            border: 1px solid #d7e2ef;
            border-radius: 8px;
            color: #49627e;
            background: #fff;
            font-family: inherit;
            font-size: .66rem;
            font-weight: 800
        }

        .wo-worker-button:hover {
            color: var(--wo-blue);
            border-color: #9fc1ee
        }

        .wo-actions {
            display: flex;
            justify-content: flex-end;
            gap: 5px
        }

        .wo-action {
            display: grid;
            place-items: center;
            width: 34px;
            height: 34px;
            border: 1px solid #d8e2ef;
            border-radius: 8px;
            color: #49627e;
            background: #fff;
            text-decoration: none
        }

        .wo-action:hover {
            color: var(--wo-blue);
            border-color: #9fc1ee;
            text-decoration: none
        }

        .wo-action.is-edit {
            color: #fff;
            border-color: var(--wo-blue);
            background: var(--wo-blue)
        }

        .wo-empty-day {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 13px;
            color: #8795a6;
            font-size: .74rem
        }

        .wo-no-results {
            display: none;
            padding: 28px;
            border: 1px dashed #cbd8e6;
            border-radius: 13px;
            color: #75869a;
            background: #fff;
            text-align: center
        }

        .wo-feedback {
            position: fixed;
            z-index: 1100;
            top: 84px;
            left: 50%;
            transform: translateX(-50%);
            min-width: 260px;
            max-width: min(92vw, 520px);
            padding: 11px 16px;
            border: 1px solid #b8e4ce;
            border-radius: 10px;
            color: #126841;
            background: #eaf8f1;
            box-shadow: 0 12px 30px rgba(25, 54, 86, .16);
            font-size: .76rem;
            font-weight: 900;
            text-align: center
        }

        .wo-feedback.is-error {
            color: #a92d3b;
            border-color: #f1bec5;
            background: #fff0f2
        }

        .wo-modal .modal-content {
            overflow: hidden;
            border: 0;
            border-radius: 15px
        }

        .wo-modal .modal-header {
            align-items: center;
            border-bottom: 1px solid #e4ebf3;
            background: #f8fafc
        }

        .wo-modal .modal-title {
            color: var(--wo-navy);
            font-size: 1rem;
            font-weight: 900
        }

        .wo-modal .modal-body {
            max-height: 65vh;
            overflow-y: auto
        }

        .wo-modal-summary {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 12px;
            padding: 10px 12px;
            border: 1px solid #dce7f2;
            border-radius: 11px;
            background: #f8fbff
        }

        .wo-modal-summary span {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #526b84;
            font-size: .7rem
        }

        .wo-modal-summary .is-ready {
            color: #13784e;
            font-weight: 900
        }

        .wo-modal-summary .is-delivered {
            color: #526b84;
            font-weight: 900
        }

        .wo-modal-section {
            padding: 13px;
            border: 1px solid #dfe8f2;
            border-radius: 11px;
            background: #fff
        }

        .wo-modal-section+.wo-modal-section {
            margin-top: 12px
        }

        .wo-modal-section h4 {
            margin: 0 0 10px;
            color: #294967;
            font-size: .82rem;
            font-weight: 900
        }

        .wo-assignment {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 9px;
            padding: 9px 0;
            border-top: 1px solid #edf1f6
        }

        .wo-assignment:first-of-type {
            border-top: 0
        }

        .wo-assignment small {
            display: block;
            color: #7c8da1
        }

        .wo-work-locked {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
            padding: 10px 12px;
            border-radius: 9px;
            color: #176c4a;
            background: #eaf8f1;
            font-size: .7rem;
            font-weight: 800
        }

        .wo-worker-form {
            display: grid;
            grid-template-columns: 1fr 1fr 110px auto;
            gap: 8px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #e7edf4
        }

        .wo-worker-form .notes {
            grid-column: 1/-1
        }

        .wo-history {
            margin: 0;
            padding: 0;
            list-style: none
        }

        .wo-history li {
            padding: 8px 0;
            border-top: 1px solid #edf1f6;
            color: #536b83;
            font-size: .72rem
        }

        .wo-history li:first-child {
            border-top: 0
        }

        .wo-history time {
            display: block;
            color: #8a98a8;
            font-size: .62rem
        }

        .wo-note {
            padding: 10px;
            border-radius: 9px;
            color: #4e657c;
            background: #f5f8fb;
            font-size: .72rem;
            white-space: pre-wrap
        }

        @media(max-width:1200px) {
            .wo-table-head {
                display: none
            }

            .wo-order {
                grid-template-columns: 1.2fr 1fr 1fr 105px 1fr 100px 94px
            }

            .wo-shell {
                width: min(100% - 24px, 1540px)
            }
        }

        @media(max-width:900px) {
            .wo-toolbar {
                grid-template-columns: 1fr 1fr
            }

            .wo-toolbar .wo-control:first-child {
                grid-column: 1/-1
            }

            .wo-week-days {
                overflow-x: auto;
                display: flex
            }

            .wo-week-day {
                flex: 0 0 100px
            }

            .wo-order {
                grid-template-columns: 1fr 1fr
            }

            .wo-actions {
                justify-content: flex-start
            }

            .wo-summary {
                grid-template-columns: repeat(2, 1fr)
            }

            .wo-worker-form {
                grid-template-columns: 1fr 1fr
            }

            .wo-worker-form button {
                grid-column: 1/-1
            }

            .wo-worker-form .notes {
                grid-column: 1/-1
            }
        }

        @media(max-width:580px) {
            .wo-shell {
                width: min(100% - 16px, 1540px);
                padding-top: 18px
            }

            .wo-head {
                align-items: flex-start;
                flex-direction: column
            }

            .wo-new-order {
                width: 100%;
                justify-content: center
            }

            .wo-toolbar {
                grid-template-columns: 1fr
            }

            .wo-toolbar .wo-control:first-child {
                grid-column: auto
            }

            .wo-summary {
                grid-template-columns: 1fr 1fr
            }

            .wo-week-arrow {
                display: none
            }

            .wo-range {
                align-items: flex-start;
                flex-direction: column
            }

            .wo-order {
                grid-template-columns: 1fr
            }

            .wo-worker-form {
                grid-template-columns: 1fr
            }

            .wo-assignment {
                align-items: flex-start;
                flex-direction: column
            }
        }
    </style>

    {{-- Keep your own @extends / @section wrapper around this content --}}

    @php
        $dayNames = ['پیر', 'منگل', 'بدھ', 'جمعرات', 'جمعہ', 'ہفتہ', 'اتوار'];
        $statusLabels = $detailedWorkflow
            ? \App\Models\Order::STATUS_LABELS
            : [
                'unassigned' => 'درزی مقرر ہونا باقی',
                'assigned' => 'کارخانے میں ہے',
                'cutting' => 'کارخانے میں ہے',
                'stitching' => 'کارخانے میں ہے',
                'trial' => 'کارخانے میں ہے',
                'ready' => 'تیار ہے',
                'delivered' => 'حوالہ شدہ',
            ];
        $canManageOrders = Auth::user()->hasBusinessPermission('tailoring.orders');
        $canManageWorkshop = Auth::user()->hasBusinessPermission('tailoring.workshop');
        $canStartOrder = $canManageOrders && Auth::user()->hasBusinessPermission('tailoring.customers');
        $pageMeta = [
            'week' => [
                'title' => 'ٹیلرنگ ورک فلو',
                'description' => 'واپسی کی تاریخ، درزی اور کام کے مرحلے کے مطابق تمام پروڈکشن سنبھالیں۔',
                'summary' => 'اس ہفتے کے آرڈرز',
                'range' => $weekStart->format('d-m-Y') . ' سے ' . $weekEnd->format('d-m-Y'),
                'range_note' => 'آرڈر اس دن دکھایا جاتا ہے جس دن گاہک کو واپس دینا ہے۔',
                'empty' => 'اس دن واپسی کے لیے کوئی آرڈر مقرر نہیں۔',
            ],
            'upcoming' => [
                'title' => 'قریب آنے والی حوالگیاں',
                'description' => 'آج اور آنے والے دنوں کے تمام زیرِ تکمیل آرڈرز۔',
                'summary' => 'آنے والے آرڈرز',
                'range' => 'آج سے آنے والی تمام تاریخیں',
                'range_note' => 'تیار اور حوالہ شدہ آرڈرز شامل نہیں ہیں۔',
                'empty' => 'اس تاریخ کے لیے کوئی آنے والا آرڈر موجود نہیں۔',
            ],
            'overdue' => [
                'title' => 'تاخیر کا شکار آرڈرز',
                'description' => 'وہ آرڈرز جن کی واپسی کی تاریخ گزر چکی ہے اور کام ابھی مکمل نہیں ہوا۔',
                'summary' => 'تاخیر والے آرڈرز',
                'range' => 'آج سے پہلے کی نامکمل حوالگیاں',
                'range_note' => 'تیار اور حوالہ شدہ آرڈرز شامل نہیں ہیں۔',
                'empty' => 'اس تاریخ کا کوئی نامکمل تاخیر والا آرڈر موجود نہیں۔',
            ],
            'ready' => [
                'title' => 'تیار، حوالگی کے منتظر',
                'description' => 'تیار آرڈرز جو ابھی گاہکوں کے حوالے نہیں کیے گئے۔',
                'summary' => 'حوالگی کے منتظر',
                'range' => 'تیار مگر غیر حوالہ شدہ آرڈرز',
                'range_note' => 'گاہک کے حوالے ہوتے ہی آرڈر اس منظر سے نکل جائے گا۔',
                'empty' => 'اس تاریخ کا کوئی تیار آرڈر حوالگی کا منتظر نہیں۔',
            ],
        ][$filter ?: 'week'];
        $visibleOrders = $weekDays->flatMap(fn($day) => $day['orders'])->unique('id')->values();
        $tailorFilters = $visibleOrders->pluck('tailor')->filter()->unique('id')->sortBy('name')->values();
        $visibleWorkerIds = $visibleOrders
            ->flatMap(fn($order) => $order->workAssignments->whereNull('legacy_key')->pluck('production_worker_id'))
            ->unique();
        $workerFilters = $workers->whereIn('id', $visibleWorkerIds)->values();
        $overdueCount = $visibleOrders
            ->filter(
                fn($order) => $order->returnDate &&
                    \Illuminate\Support\Carbon::parse($order->returnDate)->isBefore(today()) &&
                    !in_array($order->status, ['ready', 'delivered'], true),
            )
            ->count();
    @endphp

    <section class="main-content weekly-orders-page" dir="rtl">
        <div class="wo-shell">
            @include('inc.message')

            <header class="wo-head">
                <div>
                    <h1><i class="fas fa-calendar-week ml-2 text-primary"></i>{{ $pageMeta['title'] }}</h1>
                    <p>{{ $pageMeta['description'] }}</p>
                </div>
                @if ($canStartOrder)
                    <a class="wo-new-order" href="{{ route('admin.Customers.index') }}"><i class="fas fa-plus"></i>نیا آرڈر</a>
                @endif
            </header>

            <div class="wo-toolbar" aria-label="آرڈر تلاش اور فلٹر کریں">

                <label class="wo-control">
                    <i class="fas fa-search"></i>
                    <input id="wo-search" name="search" type="search" value="{{ request('search', '') }}"
                        placeholder="گاہک، آرڈر، لباس یا درزی تلاش کریں…" autocomplete="off">
                </label> <label class="wo-control"><i class="fas fa-tag"></i><select id="wo-status-filter">
                        <option value="all">تمام حالتیں</option>
                        <option value="workshop">جاری کام</option>
                        <option value="ready">تیار</option>
                        <option value="delivered">حوالہ شدہ</option>
                        <option value="overdue">تاخیر کا شکار</option>
                    </select></label>
                <label class="wo-control"><i class="fas fa-user-cog"></i><select id="wo-tailor-filter">
                        <option value="all">تمام درزی</option>
                        <option value="unassigned">درزی مقرر نہیں</option>
                        @foreach ($tailorFilters as $tailorOption)
                            <option value="{{ $tailorOption->id }}">{{ $tailorOption->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="wo-control"><i class="fas fa-users-cog"></i><select id="wo-worker-filter">
                        <option value="all">تمام کاریگر</option>
                        @foreach ($workerFilters as $worker)
                            <option value="{{ $worker->id }}">{{ $worker->name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <nav class="wo-filter-tabs" aria-label="آرڈر منظر تبدیل کریں">
                <a @class(['is-current' => !$filter]) href="{{ route('admin.order.total') }}">موجودہ ہفتہ</a>
                <a @class(['is-current' => $filter === 'upcoming']) href="{{ route('admin.order.total', ['filter' => 'upcoming']) }}">آنے
                    والے</a>
                <a @class(['is-current' => $filter === 'overdue']) href="{{ route('admin.order.total', ['filter' => 'overdue']) }}">تاخیر
                    والے</a>
                <a @class(['is-current' => $filter === 'ready']) href="{{ route('admin.order.total', ['filter' => 'ready']) }}">تیار، حوالگی
                    کے منتظر</a>
            </nav>

            <div class="wo-summary">
                <div class="wo-summary-card"><span class="wo-summary-icon"><i class="fas fa-clipboard-list"></i></span>
                    <div><small>{{ $pageMeta['summary'] }}</small><strong
                            data-summary="orders">{{ number_format($summary['orders']) }}</strong></div>
                </div>
                <div class="wo-summary-card is-work"><span class="wo-summary-icon"><i class="fas fa-store"></i></span>
                    <div><small>جاری کام</small><strong
                            data-summary="workshop">{{ number_format($summary['in_workshop']) }}</strong></div>
                </div>
                <div class="wo-summary-card is-ready"><span class="wo-summary-icon"><i
                            class="fas fa-check-circle"></i></span>
                    <div><small>تیار / حوالہ شدہ</small><strong
                            data-summary="ready">{{ number_format($summary['ready']) }}</strong></div>
                </div>
                <div class="wo-summary-card is-overdue"><span class="wo-summary-icon"><i class="fas fa-clock"></i></span>
                    <div><small>تاخیر کا شکار</small><strong
                            data-summary="overdue">{{ number_format($summary['overdue']) }}</strong></div>
                </div>
            </div>

            @if (!$filter)
                <nav class="wo-week-bar" aria-label="ہفتے کے دن">
                    <a class="wo-week-arrow"
                        href="{{ route('admin.order.total', ['week' => $weekStart->copy()->subWeek()->toDateString()]) }}"
                        title="پچھلا ہفتہ"><i class="fas fa-chevron-right"></i></a>
                    <div class="wo-week-days">
                        @foreach ($weekDays as $day)
                            <a @class(['wo-week-day', 'is-today' => $day['date']->isToday()])
                                href="#day-{{ $day['date']->toDateString() }}"><b>{{ $dayNames[$day['date']->dayOfWeekIso - 1] }}</b><small>{{ $day['date']->format('d-m-Y') }}</small></a>
                        @endforeach
                    </div>
                    <a class="wo-week-arrow"
                        href="{{ route('admin.order.total', ['week' => $weekStart->copy()->addWeek()->toDateString()]) }}"
                        title="اگلا ہفتہ"><i class="fas fa-chevron-left"></i></a>
                </nav>
            @endif

            <div class="wo-range"><strong><i
                        class="far fa-calendar-alt ml-2 text-primary"></i>{{ $pageMeta['range'] }}</strong><span>{{ $pageMeta['range_note'] }}</span>
            </div>

            <div class="wo-days" id="wo-days">
                @foreach ($weekDays as $day)
                    @php
                        $dayOrders = $day['orders'];
                    @endphp
                    <section id="day-{{ $day['date']->toDateString() }}" @class([
                        'wo-day',
                        'has-orders' => $dayOrders->isNotEmpty(),
                        'is-today' => $day['date']->isToday(),
                    ]) data-order-day>
                        <div class="wo-day-head">
                            <div class="wo-day-title"><span class="wo-date-box">{{ $day['date']->format('d') }}</span>
                                <div>
                                    <h2>{{ $dayNames[$day['date']->dayOfWeekIso - 1] }}
                                        <small>{{ $day['date']->format('d-m-Y') }}{{ $day['date']->isToday() ? ' · آج' : '' }}</small>
                                    </h2>
                                </div>
                            </div>
                            <span class="wo-day-count" data-day-count>{{ $dayOrders->count() }} آرڈر</span>
                        </div>

                        @if ($dayOrders->isNotEmpty())
                            <div class="wo-table-head" aria-hidden="true"><span>آرڈر / گاہک</span><span>لباس /
                                    ٹیمپلیٹ</span><span>درزی</span><span>واپسی</span><span>کام کا
                                    مرحلہ</span><span>کاریگر</span><span>کارروائی</span></div>
                            <div class="wo-orders" data-order-list>
                                @foreach ($dayOrders as $order)
                                    @php
                                        $isDelivered = $order->status === 'delivered';
                                        $isReady = $order->status === 'ready';
                                        $isWorkshop = !$isDelivered && !$isReady;
                                        $isOverdue =
                                            $isWorkshop &&
                                            $order->returnDate &&
                                            \Illuminate\Support\Carbon::parse($order->returnDate)->isBefore(today());
                                        $filterState = $isOverdue
                                            ? 'overdue'
                                            : ($isDelivered
                                                ? 'delivered'
                                                : ($isReady
                                                    ? 'ready'
                                                    : 'workshop'));
                                        $assignments = $order->workAssignments->whereNull('legacy_key');
                                        $workerIds = $assignments
                                            ->pluck('production_worker_id')
                                            ->filter()
                                            ->unique()
                                            ->implode(',');
                                        $garment =
                                            $order->measurementTemplate?->name ?: ($order->suitNum ?: 'سلائی کا آرڈر');
                                        $searchText = collect([
                                            $order->id,
                                            $order->suitNum,
                                            $garment,
                                            $order->customers?->name,
                                            $order->customers?->phone_number1,
                                            $order->tailor?->name,
                                        ])
                                            ->filter()
                                            ->implode(' ');
                                        $canChangeTailor =
                                            $canManageWorkshop &&
                                            !$isReady &&
                                            !$isDelivered &&
                                            (float) $order->tailor_paid_amount <= 0;
                                        $canPotentiallyChangeTailor =
                                            $canManageWorkshop &&
                                            !$isDelivered &&
                                            (float) $order->tailor_paid_amount <= 0;
                                        $nextStatuses = $detailedWorkflow
                                            ? collect($order->nextStatusOptions())
                                            : collect();
                                    @endphp
                                    <article @class(['wo-order', 'is-overdue' => $isOverdue]) data-order-row data-search="{{ $searchText }}"
                                        data-state="{{ $filterState }}"
                                        data-tailor="{{ $order->tailor?->id ?: 'unassigned' }}"
                                        data-workers=",{{ $workerIds }},">
                                        <div class="wo-primary"><strong>#{{ $order->id }} ·
                                                {{ $order->customers?->name ?: 'گاہک دستیاب نہیں' }}</strong><small>{{ $order->suitNum ?: 'آرڈر سیریل دستیاب نہیں' }}
                                                · {{ $order->customers?->phone_number1 ?: 'فون موجود نہیں' }}</small></div>
                                        <div class="wo-garment">
                                            <strong>{{ $garment }}</strong><small>{{ max(1, (int) $order->suitQuantity) }}
                                                سوٹ</small>
                                        </div>
                                        <div>
                                            @if ($canPotentiallyChangeTailor)
                                                <select class="wo-inline-select js-tailor-picker"
                                                    data-current="{{ $order->tailorId }}" data-rate="{{ $order->rateId }}"
                                                    data-action="{{ route('admin.tailor-jobs.assign', $order) }}"
                                                    aria-label="درزی منتخب کریں" @disabled(!$canChangeTailor)>
                                                    <option value="">درزی مقرر نہیں</option>
                                                    @if ($order->tailorId)
                                                        <option value="{{ $order->tailorId }}" selected>
                                                            {{ $order->tailor?->name }}</option>
                                                    @endif
                                                </select>
                                            @else
                                                <span class="wo-status"><i
                                                        class="fas fa-user"></i>{{ $order->tailor?->name ?: 'مقرر نہیں' }}</span>
                                            @endif
                                        </div>
                                        <div class="wo-due">
                                            <span>{{ $order->returnDate ? \Illuminate\Support\Carbon::parse($order->returnDate)->format('d-m-Y') : '—' }}</span><small>{{ $isOverdue ? 'تاریخ گزر چکی' : 'واپسی کی تاریخ' }}</small>
                                        </div>
                                        <div>
                                            @if ($canManageWorkshop && $detailedWorkflow && $nextStatuses->isNotEmpty())
                                                <form class="wo-inline-form js-stage-form" method="POST"
                                                    action="{{ route('admin.tailor-jobs.status', $order) }}"
                                                    data-order-id="{{ $order->id }}"
                                                    data-current-status="{{ $order->status }}">@csrf @method('PATCH')
                                                    <select name="status" class="wo-inline-select"
                                                        aria-label="اگلا مرحلہ">
                                                        <option value="" selected disabled>
                                                            {{ $statusLabels[$order->status] ?? $order->status }}</option>
                                                        @foreach ($nextStatuses as $next)
                                                            <option value="{{ $next['value'] }}">{{ $next['label'] }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </form>
                                            @elseif($canManageWorkshop && !$detailedWorkflow && !$isDelivered && $order->status !== 'unassigned')
                                                <form class="wo-inline-form js-stage-form" method="POST"
                                                    action="{{ route('admin.order.status') }}"
                                                    data-order-id="{{ $order->id }}"
                                                    data-current-status="{{ $order->status }}">@csrf<input type="hidden"
                                                        name="order_id" value="{{ $order->id }}">
                                                    <select name="order_status" class="wo-inline-select">
                                                        <option value="" selected disabled>
                                                            {{ $statusLabels[$order->status] ?? $order->status }}</option>
                                                        @if (!$isReady)
                                                            <option value="complete">تیار ہے</option>
                                                        @else
                                                            <option value="start">کارخانے میں ہے</option>
                                                            <option value="deliver">حوالہ کریں</option>
                                                        @endif
                                                    </select>
                                                </form>
                                            @else
                                                <span @class([
                                                    'wo-status',
                                                    'is-ready' => $isReady,
                                                    'is-delivered' => $isDelivered,
                                                    'is-overdue' => $isOverdue,
                                                ])><i
                                                        class="fas {{ $isDelivered ? 'fa-handshake' : ($isReady ? 'fa-check-circle' : 'fa-tools') }}"></i>{{ $isOverdue ? 'تاخیر کا شکار' : $statusLabels[$order->status] ?? $order->status }}</span>
                                            @endif
                                        </div>
                                        <div>
                                            @if ($canManageWorkshop)
                                                <button type="button" class="wo-worker-button js-open-production"
                                                    data-url="{{ route('admin.orders.production-panel', $order) }}"><i
                                                        class="fas fa-users-cog"></i>{{ $assignments->count() }}
                                                    کام</button>
                                            @else
                                                <span class="text-muted small">{{ $assignments->count() }} کام</span>
                                            @endif
                                        </div>
                                        <div class="wo-actions">
                                            @if ($canManageOrders)
                                                <a class="wo-action" href="{{ route('admin.order-print', $order->id) }}"
                                                    target="_blank" rel="noopener" title="رسید"><i
                                                        class="fas fa-print"></i></a><a class="wo-action is-edit"
                                                    href="{{ route('admin.order.edit', $order->id) }}" title="ترمیم"><i
                                                        class="fas fa-pen"></i></a>
                                            @endif
                                            @if ($canManageWorkshop)
                                                <button type="button" class="wo-action js-open-production"
                                                    data-url="{{ route('admin.orders.production-panel', $order) }}"
                                                    title="تفصیل"><i class="fas fa-ellipsis-h"></i></button>
                                            @endif
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @else
                            <div class="wo-empty-day"><i class="far fa-calendar-check"></i>{{ $pageMeta['empty'] }}</div>
                        @endif
                    </section>
                @endforeach
                <div class="wo-no-results" id="wo-no-results"><i class="fas fa-search"></i>موجودہ تلاش اور فلٹر کے مطابق
                    کوئی آرڈر نہیں ملا۔</div>
            </div>

            @if ($paginator && $paginator->hasPages())
                <div class="d-flex justify-content-center my-4" dir="ltr">
                    {{ $paginator->links('pagination::bootstrap-4') }}
                </div>
            @endif
        </div>
    </section>

    <div class="wo-feedback" id="wo-feedback" role="status" aria-live="polite" hidden></div>

    <div class="modal fade wo-modal" id="workflowConfirmModal" tabindex="-1" role="dialog" aria-hidden="true"
        dir="rtl">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">تبدیلی کی تصدیق</h3><button type="button" class="close ml-0"
                        data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0" id="workflow-confirm-message"></p>
                    <div class="form-group mt-3 mb-0" id="workflow-confirm-reason-wrap" hidden>
                        <label class="font-weight-bold" for="workflow-confirm-reason">تبدیلی کی وجہ</label>
                        <textarea class="form-control" id="workflow-confirm-reason" rows="3" maxlength="1000"
                            placeholder="مثلاً: گاہک نے درستگی کے لیے واپس کیا"></textarea>
                        <small class="text-danger d-none" id="workflow-confirm-reason-error">واپس کارخانے میں بھیجنے کی
                            وجہ درج کریں۔</small>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light"
                        data-dismiss="modal">منسوخ</button><button type="button" class="btn btn-primary"
                        id="workflow-confirm-yes">تصدیق کریں</button></div>
            </div>
        </div>
    </div>

    @if ($canManageWorkshop)
        {{-- Shared production popup: filled by AJAX --}}
        <div class="modal fade wo-modal" id="productionModal" tabindex="-1" role="dialog" aria-hidden="true"
            dir="rtl">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content" id="productionModalContent"></div>
            </div>
        </div>

        {{-- Shared tailor popup: one copy only --}}
        <div class="modal fade wo-modal" id="tailorAssignmentModal" tabindex="-1" role="dialog" aria-hidden="true"
            dir="rtl">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title" id="tailor-modal-title">درزی مقرر کریں</h3>
                        <button type="button" class="close ml-0" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <form method="POST" action="#" class="js-tailor-assignment-form">
                        @csrf @method('PATCH')
                        <input type="hidden" name="confirm_reassign" value="1" class="js-confirm-reassign"
                            disabled>
                        <div class="modal-body">
                            <div class="alert alert-warning" id="tailor-modal-warning" hidden>
                                موجودہ درزی <strong id="tailor-modal-current"></strong> ہے۔ نیا درزی محفوظ کرنے پر تبدیلی
                                آرڈر کی تاریخ میں درج ہوگی۔
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold">درزی</label>
                                <select name="tailor_id" class="form-control js-modal-tailor" required>
                                    @foreach ($tailors as $tailor)
                                        <option value="{{ $tailor->id }}" @disabled($tailor->tailorsalary->isEmpty())>
                                            {{ $tailor->name }} · {{ (int) ($tailorWorkloads[$tailor->id] ?? 0) }} جاری
                                            کام</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mb-0">
                                <label class="font-weight-bold">سلائی کی قسم</label>
                                <select name="tailor_price" class="form-control js-modal-rate" required>
                                    <option value="">سلائی کی قسم منتخب کریں</option>
                                    @foreach ($tailors as $tailor)
                                        @foreach ($tailor->tailorsalary as $rate)
                                            <option value="{{ $rate->id }}-{{ $rate->price }}"
                                                data-tailor="{{ $tailor->id }}">
                                                {{ $rate->options?->Name ?: ($rate->type ?: 'عام سلائی') }}</option>
                                        @endforeach
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-dismiss="modal">منسوخ</button>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save ml-1"></i>محفوظ
                                کریں</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if ($canManageWorkshop)
        @php
            $woTailors = $tailors
                ->map(
                    fn($t) => [
                        'id' => $t->id,
                        'name' => $t->name,
                        'jobs' => (int) ($tailorWorkloads[$t->id] ?? 0),
                        'enabled' => $t->tailorsalary->isNotEmpty(),
                    ],
                )
                ->values();
        @endphp

        <script>
            window.WO_TAILORS = {{ Illuminate\Support\Js::from($woTailors) }};
        </script>
    @endif

    <script>
        window.WO_PAGINATED = @json((bool) $paginator);
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const search = document.getElementById('wo-search');
            const status = document.getElementById('wo-status-filter');
            const tailor = document.getElementById('wo-tailor-filter');
            const worker = document.getElementById('wo-worker-filter');
            const days = Array.from(document.querySelectorAll('[data-order-day]'));
            const noResults = document.getElementById('wo-no-results');

            /* ---------- Search & filters ---------- */
            // Cache rows once and lowercase the search text once
            const dayData = days.map(function(day) {
                const rows = Array.from(day.querySelectorAll('[data-order-row]'));
                rows.forEach(function(row) {
                    row._search = (row.dataset.search || '').toLocaleLowerCase();
                });
                return {
                    day: day,
                    rows: rows,
                    count: day.querySelector('[data-day-count]')
                };
            });

            function applyOrderFilters() {
                const term = (search.value || '').trim().toLocaleLowerCase();
                const selectedStatus = status.value;
                const selectedTailor = tailor.value;
                const selectedWorker = worker.value;
                const workerKey = ',' + selectedWorker + ',';
                const filtersActive = term || selectedStatus !== 'all' || selectedTailor !== 'all' ||
                    selectedWorker !== 'all';
                let totalVisible = 0;

                dayData.forEach(function(item) {
                    let countVisible = 0;
                    item.rows.forEach(function(row) {
                        const visible =
                            (!term || row._search.includes(term)) &&
                            (selectedStatus === 'all' || row.dataset.state === selectedStatus) &&
                            (selectedTailor === 'all' || row.dataset.tailor === selectedTailor) &&
                            (selectedWorker === 'all' || (row.dataset.workers || '').includes(
                                workerKey));
                        if (row.hidden === visible) row.hidden = !
                            visible; // only touch the DOM when it changes
                        if (visible) countVisible++;
                    });
                    if (item.count && item.rows.length) item.count.textContent = countVisible + ' آرڈر';
                    const hideDay = Boolean(filtersActive) && countVisible === 0;
                    if (item.day.hidden !== hideDay) item.day.hidden = hideDay;
                    totalVisible += countVisible;
                });
                noResults.style.display = filtersActive && totalVisible === 0 ? 'block' : 'none';
            }

            [status, tailor, worker].forEach(function(control) {
                control.addEventListener('change', applyOrderFilters);
            });


            let searchTimer = null;

            search.addEventListener('input', function() {
                clearTimeout(searchTimer);

                searchTimer = setTimeout(function() {
                    const url = new URL(window.location.href);
                    const term = search.value.trim();

                    if (term) {
                        url.searchParams.set('search', term);
                    } else {
                        url.searchParams.delete('search');
                    }

                    // Start from page 1 for the new search.
                    url.searchParams.delete('page');

                    // Preserve existing filters and load matching results from Laravel.
                    window.location.href = url.toString();
                }, 500);
            });

            /* ---------- Shared tailor modal ---------- */
            function filterRates(modal, tailorId) {
                const rate = modal.querySelector('.js-modal-rate');
                let firstVisible = null;
                Array.from(rate.options).forEach(function(option) {
                    if (!option.dataset.tailor) return;
                    const show = option.dataset.tailor === String(tailorId);
                    option.hidden = !show;
                    option.disabled = !show;
                    if (show && !firstVisible) firstVisible = option;
                });
                if (!rate.selectedOptions.length || rate.selectedOptions[0].disabled || rate.selectedOptions[0]
                    .hidden) rate.value = firstVisible ? firstVisible.value : '';
            }

            const tailorModal = document.getElementById('tailorAssignmentModal');
            const tailorForm = tailorModal ? tailorModal.querySelector('form') : null;
            let activePicker = null;

            function fillPicker(select) {
                if (select.dataset.filled || !window.WO_TAILORS) return;
                const current = select.dataset.current || '';
                const currentOption = current ? select.querySelector('option[value="' + current + '"]') : null;
                const currentLabel = currentOption ? currentOption.textContent.trim() : '';

                select.innerHTML = '';
                select.add(new Option('درزی مقرر نہیں', ''));

                let found = false;
                window.WO_TAILORS.forEach(function(t) {
                    const option = new Option(t.name + ' · ' + t.jobs + ' جاری', t.id);
                    option.disabled = !t.enabled;
                    select.add(option);
                    if (String(t.id) === current) found = true;
                });

                if (current && !found) {
                    select.add(new Option(currentLabel || ('درزی #' + current), current));
                }

                select.value = current;
                select.dataset.filled = '1';
            }

            ['mousedown', 'focus', 'touchstart'].forEach(function(type) {
                document.addEventListener(type, function(event) {
                    const select = event.target.closest && event.target.closest(
                        '.js-tailor-picker');
                    if (select) fillPicker(select);
                }, true);
            });

            if (tailorModal) {
                const modalTailor = tailorModal.querySelector('.js-modal-tailor');
                modalTailor.addEventListener('change', () => filterRates(tailorModal, modalTailor.value));

                document.addEventListener('change', function(event) {
                    const picker = event.target.closest('.js-tailor-picker');
                    if (!picker) return;
                    picker.addEventListener('change', function() {
                        if (!picker.value) {
                            picker.value = picker.dataset.current || '';
                            return;
                        }
                        activePicker = picker;
                        const hasTailor = !!picker.dataset.current;
                        const currentOption = hasTailor ? picker.querySelector(
                            'option[value="' + picker.dataset.current + '"]') : null;
                        const currentName = currentOption ? currentOption.textContent.split('·')[0]
                            .trim() : '';

                        tailorForm.action = picker.dataset.action;
                        tailorForm.querySelector('.js-confirm-reassign').disabled = !hasTailor;
                        document.getElementById('tailor-modal-warning').hidden = !hasTailor;
                        document.getElementById('tailor-modal-current').textContent = currentName;
                        document.getElementById('tailor-modal-title').textContent =
                            hasTailor ? 'درزی تبدیل کرنے کی تصدیق' : 'درزی مقرر کریں';

                        modalTailor.value = picker.value;
                        filterRates(tailorModal, picker.value);

                        // preselect the order's current rate if it belongs to this tailor
                        if (picker.dataset.rate) {
                            const match = Array.from(tailorModal.querySelectorAll(
                                '.js-modal-rate option')).find(
                                o => o.dataset.tailor === picker.value && o.value.startsWith(
                                    picker.dataset.rate + '-'));
                            if (match) match.parentElement.value = match.value;
                        }
                        $(tailorModal).modal('show');
                    });
                });

                $(tailorModal).on('hidden.bs.modal', function() {
                    if (activePicker) activePicker.value = activePicker.dataset.current || '';
                });
            }

            /* ---------- Helpers ---------- */
            function responseError(payload) {
                if (payload && payload.errors) {
                    const messages = Object.values(payload.errors).flat().filter(Boolean);
                    if (messages.length) return messages[0];
                }
                return payload && payload.message ? payload.message : 'تبدیلی محفوظ نہیں ہو سکی۔ دوبارہ کوشش کریں۔';
            }

            let feedbackTimer = null;

            function showFeedback(message, type) {
                const feedback = document.getElementById('wo-feedback');
                if (!feedback) return;
                feedback.textContent = message;
                feedback.classList.toggle('is-error', type === 'error');
                feedback.hidden = false;
                window.clearTimeout(feedbackTimer);
                feedbackTimer = window.setTimeout(function() {
                    feedback.hidden = true;
                }, 3500);
            }

            function confirmWorkflow(message, requireReason) {
                return new Promise(function(resolve) {
                    const modal = $('#workflowConfirmModal');
                    const confirmButton = $('#workflow-confirm-yes');
                    const reasonWrap = document.getElementById('workflow-confirm-reason-wrap');
                    const reasonInput = document.getElementById('workflow-confirm-reason');
                    const reasonError = document.getElementById('workflow-confirm-reason-error');
                    let settled = false;

                    document.getElementById('workflow-confirm-message').textContent = message;
                    reasonWrap.hidden = !requireReason;
                    reasonInput.value = '';
                    reasonError.classList.add('d-none');

                    function finish(result) {
                        if (settled) return;
                        settled = true;
                        confirmButton.off('.workflowConfirm');
                        modal.off('.workflowConfirm');
                        resolve(result);
                    }

                    confirmButton.on('click.workflowConfirm', function() {
                        const reason = reasonInput.value.trim();
                        if (requireReason && !reason) {
                            reasonError.classList.remove('d-none');
                            reasonInput.focus();
                            return;
                        }
                        finish({
                            confirmed: true,
                            reason: reason
                        });
                        modal.modal('hide');
                    });
                    modal.on('hidden.bs.modal.workflowConfirm', function() {
                        finish({
                            confirmed: false,
                            reason: ''
                        });
                    });
                    modal.modal('show');
                    if (requireReason) modal.one('shown.bs.modal', function() {
                        reasonInput.focus();
                    });
                });
            }

            function refreshSummary() {
                const rows = Array.from(document.querySelectorAll('[data-order-row]'));
                const values = {
                    orders: rows.length,
                    workshop: rows.filter(row => ['workshop', 'overdue'].includes(row.dataset.state)).length,
                    ready: rows.filter(row => ['ready', 'delivered'].includes(row.dataset.state)).length,
                    overdue: rows.filter(row => row.dataset.state === 'overdue').length,
                };
                Object.entries(values).forEach(function([key, value]) {
                    const target = document.querySelector('[data-summary="' + key + '"]');
                    if (target) target.textContent = Number(value).toLocaleString();
                });
            }

            /* ---------- Stage change (row dropdown) ---------- */
            function updateStageUi(form, payload) {
                const row = form.closest('[data-order-row]');
                const select = form.querySelector('select');
                select.innerHTML = '';
                const current = new Option(payload.label, '', true, true);
                current.disabled = true;
                select.add(current);
                (payload.next_actions || []).forEach(function(action) {
                    select.add(new Option(action.label, action.value));
                });
                form.dataset.currentStatus = payload.status;
                row.dataset.state = payload.state;
                row.classList.toggle('is-overdue', payload.state === 'overdue');

                const picker = row.querySelector('.js-tailor-picker');
                if (picker) picker.disabled = !payload.tailor_change_allowed;

                // The production panel is loaded fresh each time it opens,
                // so there is nothing else to update here.
                if (!window.WO_PAGINATED) refreshSummary();
                applyOrderFilters();
            }

            document.addEventListener('submit', function(event) {
                if (event.target.closest('.js-stage-form')) event.preventDefault();
            });

            document.addEventListener('change', async function(event) {
                const select = event.target.closest('.js-stage-form select');
                if (!select) return;
                const form = select.closest('.js-stage-form');
                const selected = select.value;
                if (!selected) return;

                let confirmation = {
                    confirmed: true,
                    reason: ''
                };
                if (selected === 'delivered' || selected === 'deliver') {
                    confirmation = await confirmWorkflow('کیا یہ آرڈر گاہک کے حوالے ہو چکا ہے؟', false);
                } else if (selected === 'start' || (form.dataset.currentStatus === 'ready' &&
                        selected === 'stitching')) {
                    confirmation = await confirmWorkflow(
                        'تیار آرڈر دوبارہ کارخانے میں بھیجنے کی وجہ درج کریں۔', true);
                }
                if (!confirmation.confirmed) {
                    select.value = '';
                    return;
                }

                const formData = new FormData(form);
                formData.set(select.name, selected);
                if (confirmation.reason) formData.set('note', confirmation.reason);
                select.disabled = true;
                try {
                    const response = await fetch(form.action, {
                        method: form.method || 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                    });
                    const payload = await response.json();
                    if (!response.ok) throw new Error(responseError(payload));
                    updateStageUi(form, payload);
                    showFeedback(payload.message || 'تبدیلی محفوظ کر دی گئی ہے۔');
                } catch (error) {
                    showFeedback(error.message || 'تبدیلی محفوظ نہیں ہو سکی۔', 'error');
                    select.value = '';
                } finally {
                    select.disabled = false;
                }
            });

            /* ---------- Production panel (loaded by AJAX) ---------- */
            const productionModal = document.getElementById('productionModal');
            const productionContent = document.getElementById('productionModalContent');

            document.addEventListener('click', async function(event) {
                const button = event.target.closest('.js-open-production');
                if (!button || !productionModal) return;

                productionContent.innerHTML =
                    '<div class="p-5 text-center"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
                $(productionModal).modal('show');

                try {
                    const response = await fetch(button.dataset.url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (!response.ok) throw new Error();
                    productionContent.innerHTML = await response.text();
                } catch (e) {
                    productionContent.innerHTML =
                        '<div class="p-4 text-center text-danger">تفصیل لوڈ نہیں ہو سکی۔ دوبارہ کوشش کریں۔</div>';
                }
            });

            // worker -> work type dropdown (delegated because the form arrives via AJAX)
            document.addEventListener('change', function(event) {
                const workerSelect = event.target.closest('.js-production-worker');
                if (!workerSelect) return;
                const workSelect = workerSelect.closest('.js-worker-form').querySelector(
                    '.js-production-work');
                let first = null;
                Array.from(workSelect.options).forEach(function(option) {
                    if (!option.dataset.worker) return;
                    const show = option.dataset.worker === workerSelect.value;
                    option.hidden = !show;
                    option.disabled = !show;
                    if (show && !first) first = option;
                });
                workSelect.disabled = !first;
                workSelect.value = first ? first.value : '';
            });

            /* ---------- Tailor assignment form submit ---------- */
            document.querySelectorAll('.js-tailor-assignment-form').forEach(function(form) {
                form.addEventListener('submit', async function(event) {
                    event.preventDefault();
                    const submit = form.querySelector('[type="submit"]');
                    submit.disabled = true;
                    try {
                        const response = await fetch(form.action, {
                            method: form.method || 'POST',
                            body: new FormData(form),
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                        });
                        const payload = await response.json();
                        if (!response.ok) throw new Error(responseError(payload));

                        if (activePicker) {
                            activePicker.value = String(payload.tailor.id);
                            activePicker.dataset.current = String(payload.tailor.id);
                            const row = activePicker.closest('[data-order-row]');
                            if (row) row.dataset.tailor = String(payload.tailor.id);
                        }
                        $(tailorModal).modal('hide');
                        showFeedback(payload.message || 'درزی تبدیل کر دیا گیا ہے۔');
                    } catch (error) {
                        showFeedback(error.message || 'درزی تبدیل نہیں ہو سکا۔', 'error');
                    } finally {
                        submit.disabled = false;
                    }
                });
            });
        });
    </script>
@endsection
