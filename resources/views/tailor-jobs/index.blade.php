@extends('main')

@section('content')
    @php
        $statusLabels = [
            ...\App\Models\Order::STATUS_LABELS,
            'pending' => 'زیرِ انتظار',
            'paid' => 'ادا شدہ',
            'partial' => 'کچھ ادائیگی',
            'unpaid' => 'ادائیگی باقی',
            'sent' => 'اطلاع درج',
            'failed' => 'ناکام',
            'skipped' => 'درج نہیں ہوئی',
        ];
        if (! $detailedWorkflow) {
            $statusLabels = [...$statusLabels, 'assigned' => 'کارخانے میں ہے', 'cutting' => 'کارخانے میں ہے', 'stitching' => 'کارخانے میں ہے', 'trial' => 'کارخانے میں ہے', 'ready' => 'تیار ہے'];
        }
        $hasMoreFilters =
            filled($filters['status'] ?? null) ||
            filled($filters['tailor_id'] ?? null) ||
            filled($filters['due'] ?? null) ||
            filled($filters['from_date'] ?? null) ||
            filled($filters['to_date'] ?? null) ||
            (int) ($filters['per_page'] ?? 25) !== 25;
    @endphp

    <style>
        .tailor-jobs-page {
            --tj-blue: #1769e0;
            --tj-navy: #11365b;
            --tj-muted: #718198;
            --tj-line: #dfe7f1;
            background: #f4f7fa;
            min-height: calc(100vh - 70px)
        }

        .tj-shell {
            width: min(100% - 38px, 1460px);
            margin: 0 auto;
            padding: 28px 0 46px
        }

        .tj-head,
        .tj-panel-head,
        .tj-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px
        }

        .tj-head {
            margin-bottom: 18px
        }

        .tj-title h1 {
            margin: 0;
            color: var(--tj-navy);
            font-size: 1.55rem;
            font-weight: 900
        }

        .tj-title p {
            margin: 5px 0 0;
            color: var(--tj-muted);
            font-size: .78rem
        }

        .tj-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 42px;
            padding: 8px 15px;
            border: 1px solid #bfd3ee;
            border-radius: 10px;
            color: var(--tj-blue);
            background: #fff;
            font-size: .75rem;
            font-weight: 800;
            text-decoration: none !important
        }

        .tj-head-actions{display:flex;align-items:center;gap:9px;flex-wrap:wrap}
        .tj-rack-field{display:none;min-width:190px}
        .tj-rack-field.is-visible{display:block}
        .tj-rack-badge{display:inline-flex;align-items:center;gap:6px;color:#1769e0}

        .tj-stats {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 16px
        }

        .tj-stat {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 15px;
            border: 1px solid var(--tj-line);
            border-radius: 13px;
            background: #fff
        }

        .tj-stat-icon {
            display: grid;
            place-items: center;
            width: 42px;
            height: 42px;
            border-radius: 11px;
            color: #1769e0;
            background: #eaf3ff
        }

        .tj-stat.is-due .tj-stat-icon {
            color: #a86f00;
            background: #fff4d8
        }

        .tj-stat.is-late .tj-stat-icon {
            color: #c33c4d;
            background: #fff0f2
        }

        .tj-stat.is-ready .tj-stat-icon {
            color: #168553;
            background: #e8f8ef
        }

        .tj-stat small {
            display: block;
            color: var(--tj-muted);
            font-size: .68rem
        }

        .tj-stat strong {
            display: block;
            color: var(--tj-navy);
            font-size: 1.2rem
        }

        .tj-panel {
            margin-bottom: 16px;
            border: 1px solid var(--tj-line);
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 5px 16px rgba(25, 55, 88, .04)
        }

        .tj-search-form {
            padding: 15px
        }

        .tj-search-row {
            display: grid;
            grid-template-columns: minmax(240px, 1fr) auto;
            gap: 10px;
            align-items: end
        }

        .tj-field label {
            display: block;
            margin-bottom: 5px;
            color: #3c526d;
            font-size: .72rem;
            font-weight: 800
        }

        .tj-field .form-control {
            min-height: 42px;
            border-color: #d2deeb;
            border-radius: 9px;
            background: #fbfdff;
            padding-top: 0px
        }

        .tj-filter-button {
            min-width: 100px;
            border-color: var(--tj-blue);
            color: #fff;
            background: var(--tj-blue)
        }

        .tj-more-filters {
            margin-top: 10px
        }

        .tj-more-filters summary {
            width: max-content;
            cursor: pointer;
            list-style: none
        }

        .tj-more-filters summary::-webkit-details-marker {
            display: none
        }

        .tj-filter-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            padding-top: 14px;
            margin-top: 14px;
            border-top: 1px solid #e7edf5
        }

        .tj-filter-reset {
            display: inline-block;
            margin-top: 12px;
            color: #7a899b;
            font-size: .7rem
        }

        .tj-system-note {
            padding: 9px 14px;
            margin-bottom: 15px;
            border-radius: 10px;
            color: #64768c;
            background: #eef3f8;
            font-size: .68rem
        }

        .tj-system-note i {
            color: var(--tj-blue);
            margin-left: 5px
        }

        .tj-panel-head {
            padding: 14px 16px;
            border-bottom: 1px solid #e8eef5
        }

        .tj-panel-head h2 {
            margin: 0;
            color: var(--tj-navy);
            font-size: 1rem;
            font-weight: 900
        }

        .tj-panel-head span {
            color: var(--tj-muted);
            font-size: .68rem
        }

        .tj-list {
            display: grid;
            gap: 13px;
            padding: 15px
        }

        .tj-job-card {
            overflow: hidden;
            border: 1px solid #dce6f0;
            border-radius: 13px;
            background: #fff
        }

        .tj-job-card.is-overdue {
            border-color: #efbdc4
        }

        .tj-card-head {
            padding: 14px 16px;
            background: #f8fbff
        }

        .tj-job-card.is-overdue .tj-card-head {
            background: #fff5f6
        }

        .tj-order-main {
            display: flex;
            align-items: center;
            gap: 11px
        }

        .tj-order-number {
            display: grid;
            place-items: center;
            min-width: 52px;
            height: 44px;
            padding: 0 8px;
            border-radius: 10px;
            color: #fff;
            background: var(--tj-blue);
            font-weight: 900
        }

        .tj-order-main h3 {
            margin: 0;
            color: var(--tj-navy);
            font-size: .95rem;
            font-weight: 900
        }

        .tj-order-main small {
            color: var(--tj-muted);
            font-size: .68rem
        }

        .tj-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            color: #1769e0;
            background: #e9f2ff;
            font-size: .68rem;
            font-weight: 800
        }

        .tj-status.is-overdue {
            color: #b83243;
            background: #ffe8eb
        }

        .tj-info-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            border-top: 1px solid #e8eef5;
            border-bottom: 1px solid #e8eef5
        }

        .tj-info {
            padding: 12px 16px;
            border-left: 1px solid #edf1f6
        }

        .tj-info:last-child {
            border-left: 0
        }

        .tj-info small {
            display: block;
            color: var(--tj-muted);
            font-size: .65rem
        }

        .tj-info strong {
            display: block;
            margin-top: 3px;
            color: #334c68;
            font-size: .78rem
        }

        .tj-card-body {
            display: grid;
            grid-template-columns: minmax(0, 1.4fr) minmax(260px, .8fr);
            gap: 14px;
            padding: 15px
        }

        .tj-action-box {
            padding: 13px;
            border: 1px solid #e1e9f2;
            border-radius: 11px;
            background: #fbfdff
        }

        .tj-action-box h4 {
            margin: 0 0 10px;
            color: var(--tj-navy);
            font-size: .78rem;
            font-weight: 900
        }

        .tj-progress-form {
            display: grid;
            grid-template-columns: minmax(170px, 1fr) auto;
            gap: 8px
        }

        .tj-progress-form .form-control,
        .tj-payment-form .form-control {
            min-height: 40px;
            border-color: #d2deeb;
            border-radius: 8px
        }

        .tj-primary {
            border-color: var(--tj-blue);
            color: #fff;
            background: var(--tj-blue)
        }

        .tj-success {
            border-color: #15945c;
            color: #fff;
            background: linear-gradient(135deg, #1daa6a, #087747)
        }

        .tj-payment-summary {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 9px;
            color: #52677f;
            font-size: .7rem
        }

        .tj-payment-summary strong {
            color: var(--tj-navy)
        }

        .tj-payment-form {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 8px
        }

        .tj-notifications {
            margin: 0 15px 15px;
            border-top: 1px dashed #dce5ef
        }

        .tj-notifications summary {
            padding: 11px 0 0;
            color: #718198;
            font-size: .68rem;
            cursor: pointer
        }

        .tj-notification-list {
            padding-top: 8px
        }

        .tj-notification-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 7px 9px;
            margin-top: 5px;
            border-radius: 8px;
            background: #f4f7fa;
            font-size: .67rem
        }

        .tj-empty {
            padding: 48px 20px;
            color: var(--tj-muted);
            text-align: center
        }

        .tj-empty i {
            display: block;
            margin-bottom: 10px;
            color: #9fb1c5;
            font-size: 2rem
        }

        .tj-pagination {
            padding: 12px 15px;
            border-top: 1px solid #e8eef5
        }

        @media(max-width:992px) {
            .tj-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr))
            }

            .tj-card-body {
                grid-template-columns: 1fr
            }

            .tj-progress-form {
                grid-template-columns: 1fr 1fr
            }

            .tj-progress-form .tj-button {
                grid-column: 1/-1
            }
        }

        @media(max-width:767px) {
            .tj-shell {
                width: min(100% - 20px, 1460px);
                padding-top: 18px
            }

            .tj-head {
                align-items: flex-start;
                flex-direction: column
            }

            .tj-head>.tj-button {
                width: 100%
            }

            .tj-search-row {
                grid-template-columns: 1fr
            }

            .tj-filter-grid {
                grid-template-columns: 1fr
            }

            .tj-info-grid {
                grid-template-columns: repeat(2, 1fr)
            }

            .tj-info:nth-child(2) {
                border-left: 0
            }

            .tj-info:nth-child(-n+2) {
                border-bottom: 1px solid #edf1f6
            }

            .tj-card-head {
                align-items: flex-start
            }

            .tj-progress-form,
            .tj-payment-form {
                grid-template-columns: 1fr
            }

            .tj-progress-form .tj-button {
                grid-column: auto
            }

            .tj-more-filters summary {
                width: 100%
            }
        }
    </style>

    <section class="main-content tailor-jobs-page" dir="rtl">
        <div class="tj-shell">
            <header class="tj-head">
                <div class="tj-title">
                    <h1><i class="fas fa-tasks ml-2 text-primary"></i>ٹیلرنگ ورک فلو</h1>
                    <p>فہرست میں درزی، کاریگر، مراحل، گاہک کی ادائیگی، رسید اور اجرت ایک ہی جگہ سنبھالیں۔</p>
                </div>
                @if (!$isTailor)
                    <div class="tj-head-actions">
                        <button type="button" class="tj-button" data-toggle="modal" data-target="#rackSetupModal"><i class="fas fa-layer-group"></i> ریک نمبرز <span class="badge badge-light">{{ $racks->count() }}</span></button>
                        <a href="{{ route('admin.team.index', ['tab' => 'tailors']) }}" class="tj-button"><i class="fas fa-users-cog"></i> ٹیم اور کاریگر</a>
                    </div>
                @endif
            </header>

            @if (session('success'))
                <div class="alert alert-success"><i class="fas fa-check-circle ml-1"></i>{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger"><strong>تبدیلی محفوظ نہیں ہو سکی۔</strong>
                    <ul class="mb-0 mt-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="tj-stats">
                @foreach ([['درزی مقرر ہونا باقی', $stats['unassigned'], 'fa-user-clock', ''], ['جاری کام', $stats['active'], 'fa-cut', ''], ['آج دینے ہیں', $stats['due_today'], 'fa-calendar-day', 'is-due'], ['دیر ہو گئی', $stats['overdue'], 'fa-exclamation-triangle', 'is-late'], ['تیار ہیں', $stats['ready'], 'fa-check-circle', 'is-ready']] as [$label, $value, $icon, $class])
                    <div class="tj-stat {{ $class }}"><span class="tj-stat-icon"><i
                                class="fas {{ $icon }}"></i></span>
                        <div><small>{{ $label }}</small><strong>{{ $value }}</strong></div>
                    </div>
                @endforeach
            </div>

            @if (!$isTailor)
                <form method="GET" action="{{ route('admin.order.total') }}" class="tj-panel tj-search-form">
                    <input type="hidden" name="view" value="list">
                    <div class="tj-search-row">
                        <div class="tj-field"><label for="q">کام تلاش کریں</label><input id="q"
                                name="q" class="form-control" maxlength="100" value="{{ $filters['q'] ?? '' }}"
                                placeholder="گاہک کا نام، فون یا آرڈر نمبر"></div>
                        <button class="tj-button tj-filter-button" type="submit"><i class="fas fa-search"></i> تلاش
                            کریں</button>
                    </div>
                    <details class="tj-more-filters" @if ($hasMoreFilters) open @endif>
                        <summary class="tj-button"><i class="fas fa-sliders-h"></i> مزید فلٹر</summary>
                        <div class="tj-filter-grid">
                            <div class="tj-field"><label for="status">کام کا مرحلہ</label><select id="status"
                                    name="status" class="form-control" style="padding-top: 0px">
                                    @if($detailedWorkflow)
                                        <option value="">تمام مراحل</option>
                                        @foreach (array_diff(\App\Models\Order::STATUSES, ['delivered']) as $status)
                                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $statusLabels[$status] }}</option>
                                        @endforeach
                                        <option value="completed" @selected(($filters['status'] ?? '') === 'completed')>مکمل شدہ تاریخ</option>
                                    @else
                                        <option value="">تمام حالتیں</option>
                                        <option value="unassigned" @selected(($filters['status'] ?? '') === 'unassigned')>درزی مقرر ہونا باقی</option>
                                        <option value="workshop" @selected(($filters['status'] ?? '') === 'workshop')>کارخانے میں ہے</option>
                                        <option value="ready" @selected(($filters['status'] ?? '') === 'ready')>تیار ہے</option>
                                        <option value="completed" @selected(($filters['status'] ?? '') === 'completed')>مکمل شدہ تاریخ</option>
                                    @endif
                                </select>
                            </div>
                            <div class="tj-field"><label for="tailor_id">درزی</label><select id="tailor_id" name="tailor_id"
                                    class="form-control">
                                    <option value="">تمام درزی</option>
                                    @foreach ($tailors as $tailor)
                                        <option value="{{ $tailor->id }}" @selected((int) ($filters['tailor_id'] ?? 0) === $tailor->id)>
                                            {{ $tailor->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="tj-field"><label for="due">دینے کی تاریخ</label><select id="due"
                                    name="due" class="form-control">
                                    <option value="">تمام تاریخیں</option>
                                    <option value="today" @selected(($filters['due'] ?? '') === 'today')>آج دینے ہیں</option>
                                    <option value="overdue" @selected(($filters['due'] ?? '') === 'overdue')>دیر ہو گئی</option>
                                </select></div>
                            <div class="tj-field"><label for="from_date">شروع تاریخ</label><input id="from_date"
                                    type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}"
                                    class="form-control"></div>
                            <div class="tj-field"><label for="to_date">آخری تاریخ</label><input id="to_date"
                                    type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}"
                                    class="form-control"></div>
                            <div class="tj-field"><label for="per_page">ایک صفحے پر</label><select id="per_page"
                                    name="per_page" class="form-control">
                                    @foreach ([15, 25, 50, 100] as $size)
                                        <option value="{{ $size }}" @selected((int) ($filters['per_page'] ?? 25) === $size)>
                                            {{ $size }} کام</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <a class="tj-filter-reset" href="{{ route('admin.order.total', ['view' => 'list']) }}"><i
                                class="fas fa-times ml-1"></i>تمام فلٹر ختم کریں</a>
                    </details>
                </form>
            @endif

            <div class="tj-system-note"><i class="fas fa-info-circle"></i>گاہک کی اطلاع ابھی اندرونی ریکارڈ میں محفوظ ہوتی
                ہے۔ SMS یا واٹس ایپ فراہم کنندہ منسلک نہیں ہے۔</div>

            <section class="tj-panel">
                <div class="tj-panel-head">
                    <h2>{{ ($filters['status'] ?? '') === 'completed' ? 'مکمل شدہ کام' : 'فعال کام' }}</h2><span>کل {{ $orders->total() }} کام</span>
                </div>
                <div class="tj-list">
                    @forelse ($orders as $order)
                        @php
                            $deadline = $order->returnDate ? \Carbon\Carbon::parse($order->returnDate) : null;
                            $overdue = $deadline && $deadline->isBefore(today()) && $order->status !== 'delivered';
                            $isDelivered = $order->status === 'delivered';
                            $isReady = $order->status === 'ready';
                            $nextStatusOptions = $detailedWorkflow
                                ? collect($order->nextStatusOptions())->reject(fn($option) => $isTailor && $option['value'] === 'delivered')
                                : collect();
                            $earned = $order->tailorAmountDue();
                            $deliveries = $order->notificationDeliveries->keyBy('stage');
                            $paymentErrors = $errors->getBag('tailorPayment' . $order->id);
                        @endphp
                        <article class="tj-job-card {{ $overdue ? 'is-overdue' : '' }}" data-workflow-order="{{ $order->id }}">
                            <div class="tj-card-head">
                                <div class="tj-order-main"><span class="tj-order-number">#{{ $order->id }}</span>
                                    <div>
                                        <h3>{{ $order->customers->name ?? 'گاہک نمبر ' . $order->customerId }}</h3>
                                        <small>آرڈر کی تاریخ: {{ optional($order->created_at)->format('d M Y') }}</small>
                                    </div>
                                </div><div class="d-flex align-items-center flex-wrap" style="gap:7px">
                                    <span class="tj-status {{ $overdue ? 'is-overdue' : '' }}"><i
                                            class="fas {{ $overdue ? 'fa-exclamation-circle' : 'fa-circle' }}"></i>{{ $overdue ? 'دیر ہو گئی' : $statusLabels[$order->status] ?? $order->status }}</span>
                                    @if(!$isTailor && Auth::user()->hasBusinessPermission('tailoring.orders'))
                                        <a class="tj-button" href="{{ route('admin.order-print', $order) }}" title="گاہک کی رسید"><i class="fas fa-print"></i> رسید</a>
                                        <a class="tj-button" href="{{ route('admin.order.edit', $order) }}" title="آرڈر میں ترمیم"><i class="fas fa-pen"></i> ترمیم</a>
                                    @endif
                                </div>
                            </div>
                            <div class="tj-info-grid">
                                <div class="tj-info"><small><i
                                            class="fas fa-user-cog ml-1"></i>درزی</small><strong>{{ $order->tailor->name ?? 'ابھی مقرر نہیں' }}</strong>
                                </div>
                                <div class="tj-info"><small><i
                                            class="fas fa-tshirt ml-1"></i>سوٹ</small><strong>{{ $order->suitQuantity ?: 1 }}</strong>
                                </div>
                                <div class="tj-info"><small><i class="fas fa-calendar-alt ml-1"></i>دینے کی
                                        تاریخ</small><strong>{{ $deadline ? $deadline->format('d M Y') : 'مقرر نہیں' }}</strong>
                                </div>
                                <div class="tj-info"><small><i class="fas fa-tasks ml-1"></i>موجودہ
                                        مرحلہ</small><strong>{{ $statusLabels[$order->status] ?? $order->status }}</strong>
                                </div>
                                <div class="tj-info"><small><i class="fas fa-layer-group ml-1"></i>تیار لباس کا ریک</small><strong class="tj-rack-badge">{{ $order->rack_no ?: 'تیار ہونے پر مقرر ہوگا' }}</strong></div>
                            </div>
                            @if(!$isTailor && $order->status === 'unassigned')
                                <div class="tj-card-body" style="grid-template-columns:1fr">
                                    <div class="tj-action-box">
                                        <h4><i class="fas fa-user-check ml-1 text-primary"></i>دستیابی دیکھ کر درزی مقرر کریں</h4>
                                        <form method="POST" action="{{ route('admin.tailor-jobs.assign', $order) }}" class="tj-assignment-form">
                                            @csrf @method('PATCH')
                                            <div class="tj-progress-form">
                                                <select name="tailor_id" class="form-control js-assignment-tailor" required aria-label="درزی منتخب کریں">
                                                    <option value="">درزی منتخب کریں</option>
                                                    @foreach($tailors as $tailor)
                                                        <option value="{{ $tailor->id }}" @disabled($tailor->tailorsalary->isEmpty())>{{ $tailor->name }} — {{ (int)($tailorWorkloads[$tailor->id] ?? 0) }} جاری کام{{ $tailor->tailorsalary->isEmpty() ? ' — شرح موجود نہیں' : '' }}</option>
                                                    @endforeach
                                                </select>
                                                <select name="tailor_price" class="form-control js-assignment-rate" required aria-label="سلائی شرح منتخب کریں">
                                                    <option value="">پہلے درزی منتخب کریں</option>
                                                    @foreach($tailors as $tailor)
                                                        @foreach($tailor->tailorsalary as $rate)
                                                            <option value="{{ $rate->id }}-{{ $rate->price }}" data-tailor="{{ $tailor->id }}" hidden>{{ number_format((float)$rate->price,2) }} — {{ $rate->options?->Name ?: ($rate->type ?: 'عام سلائی') }}</option>
                                                        @endforeach
                                                    @endforeach
                                                </select>
                                                <button class="tj-button tj-primary" type="submit"><i class="fas fa-check"></i> درزی مقرر کریں</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @else
                            <div class="tj-card-body">
                                <div class="tj-action-box">
                                    @if(!$isTailor && !$isDelivered && (float)$order->tailor_paid_amount <= 0)
                                        <details class="mb-3">
                                            <summary class="tj-button"><i class="fas fa-user-edit"></i> درزی / شرح تبدیل کریں</summary>
                                            <form method="POST" action="{{ route('admin.tailor-jobs.assign', $order) }}" class="tj-assignment-form mt-2" onsubmit="return confirm('کیا آپ واقعی اس آرڈر کا درزی یا شرح تبدیل کرنا چاہتے ہیں؟ یہ تبدیلی تاریخ میں محفوظ ہوگی۔')">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="confirm_reassign" value="1">
                                                <div class="tj-progress-form">
                                                    <select name="tailor_id" class="form-control js-assignment-tailor" required aria-label="درزی منتخب کریں">
                                                        @foreach($tailors as $tailor)
                                                            <option value="{{ $tailor->id }}" @selected((int)$order->tailorId === $tailor->id) @disabled($tailor->tailorsalary->isEmpty())>{{ $tailor->name }} — {{ (int)($tailorWorkloads[$tailor->id] ?? 0) }} جاری کام</option>
                                                        @endforeach
                                                    </select>
                                                    <select name="tailor_price" class="form-control js-assignment-rate" required aria-label="سلائی شرح منتخب کریں">
                                                        @foreach($tailors as $tailor)
                                                            @foreach($tailor->tailorsalary as $rate)
                                                                <option value="{{ $rate->id }}-{{ $rate->price }}" data-tailor="{{ $tailor->id }}" @selected((int)$order->rateId === $rate->id) @if((int)$order->tailorId !== $tailor->id) hidden @endif>{{ number_format((float)$rate->price,2) }} — {{ $rate->options?->Name ?: ($rate->type ?: 'عام سلائی') }}</option>
                                                            @endforeach
                                                        @endforeach
                                                    </select>
                                                    <button class="tj-button tj-primary" type="submit"><i class="fas fa-save"></i> تبدیلی محفوظ کریں</button>
                                                </div>
                                            </form>
                                        </details>
                                    @endif
                                    <h4><i class="fas fa-exchange-alt ml-1 text-primary"></i>کام کی حالت</h4>
                                    @if($detailedWorkflow && $nextStatusOptions->isNotEmpty())
                                        <form class="tj-progress-form js-job-status-form" method="POST" action="{{ $isTailor ? route('tailor.jobs.status', $order) : route('admin.tailor-jobs.status', $order) }}"
                                            data-offline-command="order.status.change" data-order-id="{{ $order->id }}" data-base-status="{{ $order->status }}">
                                            @csrf @method('PATCH')
                                            <select name="status" class="form-control js-job-status" required>
                                                @foreach($nextStatusOptions as $nextStatus)
                                                    <option value="{{ $nextStatus['value'] }}">{{ $nextStatus['label'] }}</option>
                                                @endforeach
                                            </select>
                                            <div class="tj-rack-field js-rack-field"><label class="small font-weight-bold mb-1">تیار لباس کہاں رکھا؟ <span class="text-muted">(اختیاری)</span></label><select name="rack_no" class="form-control js-rack-select"><option value="">ریک نمبر منتخب نہ کریں</option>@foreach($racks as $rack)<option value="{{ $rack->rack_no }}" @selected($order->rack_no === $rack->rack_no)>{{ $rack->rack_no }}</option>@endforeach</select></div>
                                            <button class="tj-button tj-primary" type="submit"><i class="fas fa-check"></i> مرحلہ بدلیں</button>
                                        </form>
                                    @elseif(! $detailedWorkflow && ! $isDelivered && ! $isReady)
                                        <form class="tj-progress-form js-job-status-form" method="POST"
                                            action="{{ $isTailor ? route('tailor.order.status') : route('admin.order.status') }}"
                                            data-offline-command="order.status.change" data-order-id="{{ $order->id }}" data-base-status="{{ $order->status }}">
                                            @csrf
                                            <input type="hidden" name="order_id" value="{{ $order->id }}">
                                            <select name="order_status" class="form-control js-job-status" required>
                                                <option value="start" @selected(! $isReady)>کارخانے میں ہے</option>
                                                <option value="complete" @selected($isReady)>تیار ہے</option>
                                            </select>
                                            <div class="tj-rack-field js-rack-field"><label class="small font-weight-bold mb-1">تیار لباس کہاں رکھا؟ <span class="text-muted">(اختیاری)</span></label><select name="rack_no" class="form-control js-rack-select"><option value="">ریک نمبر منتخب نہ کریں</option>@foreach($racks as $rack)<option value="{{ $rack->rack_no }}" @selected($order->rack_no === $rack->rack_no)>{{ $rack->rack_no }}</option>@endforeach</select></div>
                                            <button class="tj-button tj-primary" type="submit"><i class="fas fa-check"></i> حالت بدلیں</button>
                                        </form>
                                    @elseif(! $detailedWorkflow && $isReady && ! $isTailor)
                                        <form method="POST" action="{{ route('admin.order.status') }}"
                                            data-offline-command="order.status.change" data-order-id="{{ $order->id }}" data-base-status="{{ $order->status }}">
                                            @csrf
                                            <input type="hidden" name="order_id" value="{{ $order->id }}">
                                            <button class="tj-button tj-success" type="submit" name="order_status" value="deliver"><i class="fas fa-handshake"></i> گاہک کے حوالے کریں</button>
                                        </form>
                                    @else<span class="text-muted small"><i
                                                class="fas fa-check-circle ml-1 text-success"></i>یہ کام مکمل ہو چکا
                                            ہے۔</span>
                                    @endif
                                    @if (!$isTailor)
                                        <details class="mt-2 tj-workers-inline">
                                            <summary class="tj-button"><i class="fas fa-users-cog"></i> دیگر کاریگر اور کام <span class="badge badge-light">{{ $order->workAssignments->whereNull('legacy_key')->count() }}</span></summary>
                                            <div class="mt-2">
                                                @foreach($order->workAssignments->whereNull('legacy_key') as $assignment)
                                                    <div class="d-flex align-items-center justify-content-between flex-wrap border rounded p-2 mb-2" style="gap:8px">
                                                        <span><strong>{{ $assignment->worker?->name ?: 'ورکر' }}</strong> — {{ $assignment->workType?->name ?: 'کام' }}<small class="text-muted mr-2">{{ number_format((float)$assignment->quantity, 2) }} × Rs. {{ number_format((float)$assignment->rate, 2) }}</small></span>
                                                        @if(in_array($assignment->status, ['assigned', 'in_progress'], true))
                                                            <form method="POST" action="{{ route('admin.orders.workforce.status', [$order, $assignment]) }}" class="d-flex" style="gap:6px">@csrf @method('PATCH')
                                                                <select name="status" class="form-control form-control-sm">@if($assignment->status === 'assigned')<option value="in_progress">کام شروع</option>@endif<option value="completed">مکمل</option><option value="cancelled">منسوخ</option></select>
                                                                <button class="btn btn-sm btn-outline-primary">محفوظ</button>
                                                            </form>
                                                        @else<span class="badge badge-{{ $assignment->status === 'completed' ? 'success' : 'secondary' }}">{{ $assignment->status === 'completed' ? 'مکمل' : 'منسوخ' }}</span>@endif
                                                    </div>
                                                @endforeach
                                                @if(!$isDelivered && $workers->isNotEmpty())
                                                    <form method="POST" action="{{ route('admin.orders.workforce.store', $order) }}" class="tj-inline-worker-form border-top pt-2">@csrf
                                                        <div class="tj-progress-form">
                                                            <select name="production_worker_id" class="form-control js-production-worker" required><option value="">کاریگر منتخب کریں</option>@foreach($workers as $worker)<option value="{{ $worker->id }}">{{ $worker->name }}</option>@endforeach</select>
                                                            <select name="work_type_id" class="form-control js-production-work" required disabled><option value="">پہلے کاریگر منتخب کریں</option>@foreach($workers as $worker)@foreach($worker->skills as $skill)@php($plan=$worker->compensationPlans->firstWhere('work_type_id',$skill->id))@if($plan && in_array($plan->method,['per_piece','hybrid']) && (float)$plan->rate>0)<option value="{{ $skill->id }}" data-worker="{{ $worker->id }}" hidden disabled>{{ $skill->name }} — Rs. {{ number_format((float)$plan->rate,2) }}</option>@endif @endforeach @endforeach</select>
                                                            <input type="number" name="quantity" min="0.001" step="0.001" value="{{ $order->suitQuantity ?: 1 }}" class="form-control" aria-label="مقدار" required>
                                                            <button class="tj-button tj-primary" type="submit"><i class="fas fa-plus"></i> کام دیں</button>
                                                        </div>
                                                        <input name="notes" class="form-control mt-2" maxlength="1000" placeholder="کام کی ہدایت (اختیاری)">
                                                    </form>
                                                @elseif($workers->isEmpty())
                                                    <a class="tj-button" href="{{ route('admin.team.index', ['open' => 'create', 'type' => 'production']) }}"><i class="fas fa-user-plus"></i> پہلے کاریگر شامل کریں</a>
                                                @endif
                                            </div>
                                        </details>
                                    @endif
                                </div>
                                @if (!$isTailor)
                                    <div class="tj-action-box">
                                        <h4><i class="fas fa-wallet ml-1 text-success"></i>درزی کی ادائیگی</h4>
                                        <div class="tj-payment-summary"><span>کل اجرت <strong>روپے
                                                    {{ number_format($earned, 2) }}</strong></span><span>{{ $statusLabels[$order->tailor_payment_status] ?? $order->tailor_payment_status }}</span>
                                        </div>
                                        <form class="tj-payment-form" method="POST"
                                            action="{{ route('admin.tailor-jobs.payment', $order) }}">@csrf
                                            @method('PATCH')<input type="number" name="paid_amount"
                                                class="form-control {{ $paymentErrors->has('paid_amount') ? 'is-invalid' : '' }}"
                                                min="{{ (float) $order->tailor_paid_amount }}" max="{{ $earned }}"
                                                step="0.01" value="{{ (float) $order->tailor_paid_amount }}"
                                                aria-label="کل ادا شدہ رقم"
                                                aria-describedby="tailor-payment-error-{{ $order->id }}"><button
                                                class="tj-button" type="submit"><i class="fas fa-save"></i> رقم محفوظ
                                                کریں</button></form>
                                        @if ($paymentErrors->has('paid_amount'))
                                            <div id="tailor-payment-error-{{ $order->id }}"
                                                class="text-danger small mt-1" role="alert">
                                                {{ $paymentErrors->first('paid_amount') }}</div>
                                        @endif
                                        @php($customerBalance = max(0, (float)($order->outstanding_amount ?? 0)))
                                        @if(Auth::user()->hasBusinessPermission('customers.balances'))
                                            <hr>
                                            <div class="tj-payment-summary"><span>گاہک کا بقایا <strong>روپے <span class="js-customer-balance">{{ number_format($customerBalance, 2) }}</span></strong></span></div>
                                            <button type="button" class="tj-button tj-customer-payment" data-toggle="modal" data-target="#workflowCustomerPaymentModal"
                                                data-order-id="{{ $order->id }}" data-customer-id="{{ $order->customerId }}"
                                                data-customer-name="{{ $order->customers?->name }}" data-balance="{{ $customerBalance }}"
                                                @disabled($customerBalance <= 0)><i class="fas fa-hand-holding-usd"></i> گاہک سے رقم وصول کریں</button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            @endif
                            <details class="tj-notifications">
                                <summary><i class="fas fa-bell ml-1"></i>گاہک کی اندرونی اطلاع دیکھیں</summary>
                                <div class="tj-notification-list">
                                    @foreach (\App\Services\OrderLifecycleNotificationService::NOTIFIABLE_STAGES as $stage)
                                        @php($delivery = $deliveries->get($stage))
                                        @if ($delivery)
                                            <div class="tj-notification-item"><span>{{ $statusLabels[$stage] ?? $stage }}:
                                                    {{ $statusLabels[$delivery->status] ?? $delivery->status }}</span>
                                                @if (!$isTailor && in_array($delivery->status, ['failed', 'skipped'], true))
                                                    <form method="POST"
                                                        action="{{ route('admin.tailor-jobs.notifications.retry', [$order, $delivery]) }}">
                                                        @csrf<button type="submit" class="btn btn-link btn-sm p-0">دوبارہ
                                                            کوشش</button></form>
                                                @endif
                                            </div>
                                        @endif
                                    @endforeach
                                    @if ($deliveries->isEmpty())
                                        <div class="text-muted small">ابھی کوئی اندرونی اطلاع درج نہیں ہوئی۔</div>
                                    @endif
                                </div>
                            </details>
                        </article>
                    @empty<div class="tj-empty"><i class="fas fa-inbox"></i><strong>کوئی کام نہیں ملا</strong>
                            <div>تلاش یا فلٹر تبدیل کرکے دوبارہ دیکھیں۔</div>
                        </div>
                    @endforelse
                </div>
                @if ($orders->hasPages())
                    <div class="tj-pagination">{{ $orders->links() }}</div>
                @endif
            </section>
        </div>
    </section>
    @if(!$isTailor)
        <div class="modal fade" id="rackSetupModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content">
                <form method="POST" action="{{ route('admin.tailor-racks.store') }}">@csrf
                    <div class="modal-header"><h2 class="h5 modal-title"><i class="fas fa-layer-group text-primary ml-2"></i>ورکشاپ کے ریک نمبرز</h2><button type="button" class="close mr-auto ml-0" data-dismiss="modal" aria-label="بند کریں"><span>&times;</span></button></div>
                    <div class="modal-body text-right">
                        @if($racks->isNotEmpty())<div class="mb-3"><div class="small text-muted mb-2">موجودہ ریک</div><div class="d-flex flex-wrap" style="gap:7px">@foreach($racks as $rack)<span class="badge badge-light border px-3 py-2">{{ $rack->rack_no }}</span>@endforeach</div></div>@endif
                        <label for="newRackNumber" class="font-weight-bold">نیا ریک نمبر</label>
                        <input id="newRackNumber" name="rack_no" class="form-control" required maxlength="100" placeholder="مثلاً A-01 یا عید-12">
                        <small class="form-text text-muted">درزی لباس تیار کرتے وقت انہی میں سے ریک منتخب کرے گا۔</small>
                    </div>
                    <div class="modal-footer"><button class="btn btn-primary" type="submit"><i class="fas fa-plus ml-1"></i>ریک شامل کریں</button><button class="btn btn-light" type="button" data-dismiss="modal">منسوخ کریں</button></div>
                </form>
            </div></div>
        </div>
        @if(Auth::user()->hasBusinessPermission('customers.balances'))
            <div class="modal fade" id="workflowCustomerPaymentModal" tabindex="-1" role="dialog" aria-labelledby="workflowCustomerPaymentTitle" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document"><div class="modal-content" style="max-height:calc(100vh - 30px)">
                    <form method="POST" action="{{ route('admin.customer-payments.store') }}" id="workflowCustomerPaymentForm" style="display:flex;min-height:0;flex:1;flex-direction:column">
                        @csrf
                        <input type="hidden" name="customer_id" id="workflow_payment_customer_id">
                        <input type="hidden" name="order_id" id="workflow_payment_order_id">
                        <div class="modal-header"><h2 class="h5 modal-title" id="workflowCustomerPaymentTitle">گاہک کی ادائیگی درج کریں</h2><button type="button" class="close mr-auto ml-0" data-dismiss="modal" aria-label="بند کریں"><span>&times;</span></button></div>
                        <div class="modal-body text-right" style="max-height:calc(100vh - 190px);overflow-y:auto">
                            <div id="workflowPaymentFeedback" class="alert alert-danger" hidden></div>
                            <p class="alert alert-info"><strong id="workflow_payment_customer_name"></strong><br><span>اس آرڈر کا بقایا: روپے <b id="workflow_payment_balance"></b></span></p>
                            <div class="form-group"><label for="workflow_payment_amount" class="font-weight-bold">وصول شدہ رقم</label><input id="workflow_payment_amount" type="number" name="DirectPayment" min="0.01" step="0.01" class="form-control" required></div>
                            @include('components.payment-method-fields', ['prefix' => 'workflow_payment'])
                            <div class="form-group"><label for="workflow_payment_date" class="font-weight-bold">ادائیگی کی تاریخ</label><input id="workflow_payment_date" type="date" name="paid_on" value="{{ now()->toDateString() }}" class="form-control" required></div>
                            <div class="form-group mb-0"><label for="workflow_payment_comment" class="font-weight-bold">نوٹ <small class="text-muted">(اختیاری)</small></label><textarea id="workflow_payment_comment" name="comment" rows="2" maxlength="1000" class="form-control"></textarea></div>
                        </div>
                        <div class="modal-footer"><button class="btn btn-success" type="submit"><i class="fas fa-save ml-1"></i>ادائیگی محفوظ کریں</button><button class="btn btn-light" type="button" data-dismiss="modal">منسوخ کریں</button></div>
                    </form>
                </div></div>
            </div>
        @endif
    @endif
    <script>
        document.addEventListener('change', function (event) {
            if (!event.target.matches('.js-assignment-tailor')) return;
            const form = event.target.closest('.tj-assignment-form');
            const rates = form.querySelector('.js-assignment-rate');
            const tailorId = event.target.value;
            rates.value = '';
            rates.querySelectorAll('option[data-tailor]').forEach(function (option) {
                option.hidden = option.dataset.tailor !== tailorId;
            });
            const firstRate = rates.querySelector('option[data-tailor="' + tailorId + '"]');
            if (firstRate) firstRate.selected = true;
        });
        const syncRackField = function (select) {
            const form = select.closest('.js-job-status-form');
            if (!form) return;
            const field = form.querySelector('.js-rack-field');
            const rack = form.querySelector('.js-rack-select');
            const ready = select.value === 'ready' || select.value === 'complete';
            field?.classList.toggle('is-visible', ready);
            if (rack) rack.required = false;
        };
        document.querySelectorAll('.js-job-status').forEach(function (select) {
            syncRackField(select);
            select.addEventListener('change', function () { syncRackField(select); });
        });
        document.addEventListener('change', function (event) {
            if (!event.target.matches('.js-production-worker')) return;
            const form = event.target.closest('.tj-inline-worker-form');
            const work = form.querySelector('.js-production-work');
            const workerId = event.target.value;
            work.disabled = !workerId;
            work.value = '';
            work.querySelectorAll('option[data-worker]').forEach(function (option) {
                const visible = option.dataset.worker === workerId;
                option.hidden = !visible;
                option.disabled = !visible;
            });
        });

        let workflowPaymentTrigger = null;
        $('#workflowCustomerPaymentModal').on('show.bs.modal', function (event) {
            const button = $(event.relatedTarget);
            workflowPaymentTrigger = button;
            const balance = Number(button.data('balance') || 0);
            $('#workflow_payment_customer_id').val(button.data('customer-id'));
            $('#workflow_payment_order_id').val(button.data('order-id'));
            $('#workflow_payment_customer_name').text(button.data('customer-name') || 'گاہک');
            $('#workflow_payment_balance').text(balance.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#workflow_payment_amount').attr('max', balance).val('');
            $('#workflow_payment_comment').val('');
            $('#workflowPaymentFeedback').prop('hidden', true).text('');
        });
        $('#workflowCustomerPaymentForm').on('submit', function (event) {
            event.preventDefault();
            const form = $(this), submit = form.find('button[type="submit"]'), feedback = $('#workflowPaymentFeedback');
            submit.prop('disabled', true); feedback.prop('hidden', true).text('');
            $.ajax({
                url: form.attr('action'), type: 'POST', data: form.serialize(), dataType: 'json', headers: {Accept: 'application/json'},
                success: function (response) {
                    const paid = Number($('#workflow_payment_amount').val() || 0);
                    const previous = Number(workflowPaymentTrigger?.data('balance') || 0);
                    const remaining = Math.max(0, previous - paid);
                    const card = workflowPaymentTrigger?.closest('[data-workflow-order]');
                    card?.find('.js-customer-balance').text(remaining.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                    workflowPaymentTrigger?.data('balance', remaining).attr('data-balance', remaining).prop('disabled', remaining <= 0);
                    $('#workflowCustomerPaymentModal').modal('hide');
                    const notice = $('<div class="alert alert-success"><i class="fas fa-check-circle ml-1"></i></div>').text(response.message || 'ادائیگی محفوظ ہو گئی ہے۔');
                    $('.tj-shell').prepend(notice);
                    setTimeout(function () { notice.fadeOut(250, function () { notice.remove(); }); }, 4500);
                },
                error: function (xhr) {
                    const response = xhr.responseJSON || {};
                    feedback.text(response.message || 'ادائیگی محفوظ نہیں ہو سکی۔').prop('hidden', false);
                },
                complete: function () { submit.prop('disabled', false); }
            });
        });
    </script>
@endsection
