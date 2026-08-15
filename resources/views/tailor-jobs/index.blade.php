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

        .tj-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
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
            background: #fbfdff
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
            grid-template-columns: minmax(150px, 1fr) minmax(170px, 1fr) auto;
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
                    <h1><i class="fas fa-tasks ml-2 text-primary"></i>سلائی کے کام</h1>
                    <p>ہر آرڈر کا درزی، تاریخ اور موجودہ مرحلہ ایک جگہ دیکھیں۔</p>
                </div>
                @if (!$isTailor)
                    <a href="{{ route('admin.Tailor.index') }}" class="tj-button"><i class="fas fa-user-cog"></i> درزیوں کی
                        فہرست</a>
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
                @foreach ([['جاری کام', $stats['active'], 'fa-cut', ''], ['آج دینے ہیں', $stats['due_today'], 'fa-calendar-day', 'is-due'], ['دیر ہو گئی', $stats['overdue'], 'fa-exclamation-triangle', 'is-late'], ['تیار ہیں', $stats['ready'], 'fa-check-circle', 'is-ready']] as [$label, $value, $icon, $class])
                    <div class="tj-stat {{ $class }}"><span class="tj-stat-icon"><i
                                class="fas {{ $icon }}"></i></span>
                        <div><small>{{ $label }}</small><strong>{{ $value }}</strong></div>
                    </div>
                @endforeach
            </div>

            @if (!$isTailor)
                <form method="GET" action="{{ route('admin.tailor-jobs.index') }}" class="tj-panel tj-search-form">
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
                                    <option value="">تمام مراحل</option>
                                    @foreach (\App\Models\Order::STATUSES as $status)
                                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>
                                            {{ $statusLabels[$status] ?? $status }}</option>
                                    @endforeach
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
                        <a class="tj-filter-reset" href="{{ route('admin.tailor-jobs.index') }}"><i
                                class="fas fa-times ml-1"></i>تمام فلٹر ختم کریں</a>
                    </details>
                </form>
            @endif

            <div class="tj-system-note"><i class="fas fa-info-circle"></i>گاہک کی اطلاع ابھی اندرونی ریکارڈ میں محفوظ ہوتی
                ہے۔ SMS یا واٹس ایپ فراہم کنندہ منسلک نہیں ہے۔</div>

            <section class="tj-panel">
                <div class="tj-panel-head">
                    <h2>کام کی فہرست</h2><span>کل {{ $orders->total() }} کام</span>
                </div>
                <div class="tj-list">
                    @forelse ($orders as $order)
                        @php
                            $deadline = $order->returnDate ? \Carbon\Carbon::parse($order->returnDate) : null;
                            $overdue = $deadline && $deadline->isBefore(today()) && $order->status !== 'delivered';
                            $nextStatusOptions = collect($order->nextStatusOptions())->reject(
                                fn($option) => $isTailor && $option['value'] === 'delivered',
                            );
                            $earned = $order->tailorAmountDue();
                            $deliveries = $order->notificationDeliveries->keyBy('stage');
                            $paymentErrors = $errors->getBag('tailorPayment' . $order->id);
                        @endphp
                        <article class="tj-job-card {{ $overdue ? 'is-overdue' : '' }}">
                            <div class="tj-card-head">
                                <div class="tj-order-main"><span class="tj-order-number">#{{ $order->id }}</span>
                                    <div>
                                        <h3>{{ $order->customers->name ?? 'گاہک نمبر ' . $order->customerId }}</h3>
                                        <small>آرڈر کی تاریخ: {{ optional($order->created_at)->format('d M Y') }}</small>
                                    </div>
                                </div><span class="tj-status {{ $overdue ? 'is-overdue' : '' }}"><i
                                        class="fas {{ $overdue ? 'fa-exclamation-circle' : 'fa-circle' }}"></i>{{ $overdue ? 'دیر ہو گئی' : $statusLabels[$order->status] ?? $order->status }}</span>
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
                            </div>
                            <div class="tj-card-body">
                                <div class="tj-action-box">
                                    <h4><i class="fas fa-forward ml-1 text-primary"></i>اگلا کام</h4>
                                    @if ($nextStatusOptions->isNotEmpty())
                                        <form class="tj-progress-form" method="POST"
                                            action="{{ $isTailor ? route('tailor.jobs.status', $order) : route('admin.tailor-jobs.status', $order) }}">
                                            @csrf @method('PATCH')
                                            <select name="status" class="form-control" required>
                                                @foreach ($nextStatusOptions as $nextStatus)
                                                    <option value="{{ $nextStatus['value'] }}">{{ $nextStatus['label'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <input type="text" name="note" maxlength="1000" class="form-control"
                                                placeholder="نوٹ (اختیاری)"><button class="tj-button tj-primary"
                                                type="submit"><i class="fas fa-check"></i> مرحلہ بدلیں</button>
                                        </form>
                                    @else<span class="text-muted small"><i
                                                class="fas fa-check-circle ml-1 text-success"></i>یہ کام مکمل ہو چکا
                                            ہے۔</span>
                                    @endif
                                    @if (!$isTailor)
                                        <a class="tj-button mt-2"
                                            href="{{ route('admin.orders.workforce.index', $order) }}"><i
                                                class="fas fa-users-cog"></i> کاریگر اور کام</a>
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
                                    </div>
                                @endif
                            </div>
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
@endsection
