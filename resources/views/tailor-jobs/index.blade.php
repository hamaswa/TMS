@extends('main')

@section('content')
@php
    $statusLabels = [
        ...\App\Models\Order::STATUS_LABELS,
        'pending' => 'زیرِ انتظار',
        'paid' => 'ادا شدہ', 'partial' => 'جزوی ادائیگی', 'unpaid' => 'غیر ادا شدہ',
        'sent' => 'اندرونی اطلاع درج', 'failed' => 'ناکام', 'skipped' => 'درج نہیں ہوئی',
    ];
@endphp
<section class="main-content">
    <div class="container-fluid px-3 px-md-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
            <div>
                <h3 class="mb-1">ٹیلرنگ کام کی فہرست</h3>
                <p class="text-muted mb-0">درزی کی تعیناتی سے گاہک کو حوالگی تک ہر آرڈر کی پیش رفت دیکھیں۔ اطلاع فی الحال گاہک کے اندرونی ریکارڈ میں محفوظ ہوتی ہے؛ SMS یا واٹس ایپ فراہم کنندہ منسلک نہیں ہے۔</p>
            </div>
            @if (! $isTailor)
                <a href="{{ route('admin.Tailor.index') }}" class="btn btn-outline-primary">درزیوں کا انتظام</a>
            @endif
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>تبدیلی محفوظ نہیں ہو سکی۔</strong>
                <ul class="mb-0 mt-1">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <div class="row mb-3">
            @foreach ([
                ['جاری کام', $stats['active'], 'primary'],
                ['آج واجب', $stats['due_today'], 'warning'],
                ['تاخیر شدہ', $stats['overdue'], 'danger'],
                ['تیار', $stats['ready'], 'success'],
            ] as [$label, $value, $color])
                <div class="col-6 col-lg-3 mb-2">
                    <div class="card border-left-{{ $color }} h-100">
                        <div class="card-body py-3">
                            <div class="text-muted small">{{ $label }}</div>
                            <div class="h3 mb-0 text-{{ $color }}">{{ $value }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if (! $isTailor)
            <form method="GET" action="{{ route('admin.tailor-jobs.index') }}" class="card card-body mb-3">
                <div class="form-row align-items-end mb-md-3">
                    <div class="form-group col-md-4 mb-md-0">
                        <label for="q">آرڈر یا گاہک</label>
                        <input id="q" name="q" class="form-control" maxlength="100" value="{{ $filters['q'] ?? '' }}" placeholder="آرڈر نمبر، سوٹ نمبر، نام یا فون">
                    </div>
                    <div class="form-group col-md-3 mb-md-0"><label for="from_date">ابتدائی تاریخ</label><input id="from_date" type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}" class="form-control"></div>
                    <div class="form-group col-md-3 mb-md-0"><label for="to_date">آخری تاریخ</label><input id="to_date" type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}" class="form-control"></div>
                    <div class="form-group col-md-2 mb-md-0"><label for="per_page">قطاریں</label><select id="per_page" name="per_page" class="form-control">@foreach ([15,25,50,100] as $size)<option value="{{ $size }}" @selected((int)($filters['per_page'] ?? 25) === $size)>{{ $size }}</option>@endforeach</select></div>
                </div>
                <div class="form-row align-items-end">
                    <div class="form-group col-md-3 mb-md-0">
                        <label for="status">مرحلہ</label>
                        <select id="status" name="status" class="form-control">
                            <option value="">تمام مراحل</option>
                            @foreach (\App\Models\Order::STATUSES as $status)
                                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $statusLabels[$status] ?? $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3 mb-md-0">
                        <label for="tailor_id">درزی</label>
                        <select id="tailor_id" name="tailor_id" class="form-control">
                            <option value="">تمام درزی</option>
                            @foreach ($tailors as $tailor)
                                <option value="{{ $tailor->id }}" @selected((int) ($filters['tailor_id'] ?? 0) === $tailor->id)>{{ $tailor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3 mb-md-0">
                        <label for="due">مقررہ تاریخ</label>
                        <select id="due" name="due" class="form-control">
                            <option value="">تمام تاریخیں</option>
                            <option value="today" @selected(($filters['due'] ?? '') === 'today')>آج واجب</option>
                            <option value="overdue" @selected(($filters['due'] ?? '') === 'overdue')>تاخیر شدہ</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary" type="submit">فلٹر کریں</button>
                        <a class="btn btn-light" href="{{ route('admin.tailor-jobs.index') }}">صاف کریں</a>
                    </div>
                </div>
            </form>
        @endif

        <div class="card">
            <div class="card-header py-2 text-muted small">کل {{ $orders->total() }} کاموں میں سے {{ $orders->firstItem() ?? 0 }}–{{ $orders->lastItem() ?? 0 }} دکھائے جا رہے ہیں</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>آرڈر</th>
                            <th>گاہک</th>
                            <th>درزی</th>
                            <th>سوٹ</th>
                            <th>مقررہ تاریخ</th>
                            <th>مرحلہ</th>
                            <th style="min-width: 210px">گاہک کی اندرونی اطلاع</th>
                            <th style="min-width: 250px">اگلا عمل</th>
                            @if (! $isTailor)<th style="min-width: 220px">درزی کی ادائیگی</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            @php
                                $deadline = $order->returnDate ? \Carbon\Carbon::parse($order->returnDate) : null;
                                $overdue = $deadline && $deadline->isBefore(today()) && $order->status !== 'delivered';
                                $nextStatuses = collect($order->nextStatuses())
                                    ->reject(fn ($status) => $isTailor && $status === 'delivered');
                                $earned = $order->tailorAmountDue();
                                $deliveries = $order->notificationDeliveries->keyBy('stage');
                                $paymentErrors = $errors->getBag('tailorPayment'.$order->id);
                            @endphp
                            <tr class="{{ $overdue ? 'table-danger' : '' }}">
                                <td><strong>#{{ $order->id }}</strong><br><small class="text-muted">{{ optional($order->created_at)->format('d M Y') }}</small></td>
                                <td>{{ $order->customers->name ?? 'گاہک نمبر '.$order->customerId }}</td>
                                <td>{{ $order->tailor->name ?? 'مقرر نہیں' }}</td>
                                <td>{{ $order->suitQuantity ?: 1 }}</td>
                                <td>
                                    {{ $deadline ? $deadline->format('d M Y') : 'مقرر نہیں' }}
                                    @if ($overdue)<span class="badge badge-danger d-block mt-1">تاخیر شدہ</span>@endif
                                </td>
                                <td><span class="badge badge-info">{{ $statusLabels[$order->status] ?? $order->status }}</span></td>
                                <td>
                                    @foreach (\App\Services\OrderLifecycleNotificationService::NOTIFIABLE_STAGES as $stage)
                                        @php($delivery = $deliveries->get($stage))
                                        @if ($delivery)
                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                <span class="badge badge-{{ $delivery->status === 'sent' ? 'success' : ($delivery->status === 'failed' ? 'danger' : 'warning') }}">
                                                    {{ $statusLabels[$stage] ?? $stage }}: {{ $statusLabels[$delivery->status] ?? $delivery->status }}
                                                </span>
                                                @if (! $isTailor && in_array($delivery->status, ['failed', 'skipped'], true))
                                                    <form method="POST" action="{{ route('admin.tailor-jobs.notifications.retry', [$order, $delivery]) }}" class="ml-1">
                                                        @csrf
                                                        <button type="submit" class="btn btn-link btn-sm p-0">دوبارہ کوشش</button>
                                                    </form>
                                                @endif
                                            </div>
                                        @endif
                                    @endforeach
                                    @if ($deliveries->isEmpty())
                                        <span class="text-muted small">ابھی کوئی اندرونی اطلاع درج نہیں ہوئی</span>
                                    @endif
                                </td>
                                <td>
                                    @if (! $isTailor)
                                        <a class="btn btn-outline-info btn-sm btn-block mb-1" href="{{ route('admin.orders.workforce.index', $order) }}">کاریگر اور کام</a>
                                    @endif
                                    @if ($nextStatuses->isNotEmpty())
                                        <form method="POST" action="{{ $isTailor ? route('tailor.jobs.status', $order) : route('admin.tailor-jobs.status', $order) }}">
                                            @csrf
                                            @method('PATCH')
                                            <div class="input-group input-group-sm mb-1">
                                                <select name="status" class="form-control" required>
                                                    @foreach ($nextStatuses as $nextStatus)
                                                        <option value="{{ $nextStatus }}">اگلا مرحلہ: {{ $statusLabels[$nextStatus] ?? $nextStatus }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="input-group-append"><button class="btn btn-primary" type="submit">اپ ڈیٹ کریں</button></div>
                                            </div>
                                            <input type="text" name="note" maxlength="1000" class="form-control form-control-sm" placeholder="اختیاری پیش رفت نوٹ">
                                        </form>
                                    @else
                                        <span class="text-muted">مزید کوئی عمل نہیں</span>
                                    @endif
                                </td>
                                @if (! $isTailor)
                                    <td>
                                        <div class="small mb-1">
                                            کمائی: روپے {{ number_format($earned, 2) }}<br>
                                            <span class="badge badge-{{ $order->tailor_payment_status === 'paid' ? 'success' : ($order->tailor_payment_status === 'partial' ? 'warning' : 'secondary') }}">
                                                {{ $statusLabels[$order->tailor_payment_status] ?? $order->tailor_payment_status }}
                                            </span>
                                        </div>
                                        <form method="POST" action="{{ route('admin.tailor-jobs.payment', $order) }}">
                                            @csrf
                                            @method('PATCH')
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="paid_amount" class="form-control {{ $paymentErrors->has('paid_amount') ? 'is-invalid' : '' }}" min="{{ (float) $order->tailor_paid_amount }}" max="{{ $earned }}" step="0.01" value="{{ (float) $order->tailor_paid_amount }}" aria-label="کل ادا شدہ رقم" aria-describedby="tailor-payment-error-{{ $order->id }}">
                                                <div class="input-group-append"><button class="btn btn-outline-success" type="submit">ادائیگی محفوظ کریں</button></div>
                                            </div>
                                            @if ($paymentErrors->has('paid_amount'))
                                                <div id="tailor-payment-error-{{ $order->id }}" class="text-danger small mt-1" role="alert">{{ $paymentErrors->first('paid_amount') }}</div>
                                            @endif
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="{{ $isTailor ? 8 : 9 }}" class="text-center text-muted py-5">منتخب فلٹر کے مطابق کوئی کام موجود نہیں۔</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($orders->hasPages())<div class="card-footer">{{ $orders->links() }}</div>@endif
        </div>
    </div>
</section>
@endsection
