@extends('main')
@section('content')
<section class="main-content"><div class="container-fluid px-3 px-md-4" dir="rtl">
    <style>
        .reconciliation-hero{background:linear-gradient(135deg,#123a58,#176b65);border-radius:18px;color:#fff;padding:1.5rem}
        .reconciliation-card{border:0;border-radius:14px;box-shadow:0 8px 22px rgba(15,42,59,.07)}
        .status-dot{display:inline-block;width:9px;height:9px;border-radius:50%;margin-left:5px}
        .status-pending{background:#d39e00}.status-matched{background:#198754}.status-variance{background:#dc3545}
        .amount-ltr{direction:ltr;display:inline-block}
        @media(max-width:767px){.reconciliation-hero{padding:1.15rem}.reconciliation-actions .btn{display:block;width:100%;margin:.25rem 0!important}}
    </style>
    <div class="reconciliation-hero mb-3 d-flex flex-wrap align-items-center justify-content-between">
        <div><div class="small text-white-50 mb-1">آن لائن ادائیگیاں</div><h1 class="h3 mb-2">روزانہ ادائیگی مصالحت</h1><p class="mb-0 text-white-50">تصدیق شدہ رقم کو والٹ یا بینک کی روزانہ رپورٹ سے ملائیں۔ یہ عمل گاہک کے کھاتے میں دوبارہ رقم درج نہیں کرتا۔</p></div>
        <div class="reconciliation-actions mt-3 mt-md-0">
            <a class="btn btn-light ml-2" href="{{ route('admin.financial-reports.index') }}"><i class="fas fa-chart-line ml-1"></i> مالیاتی ڈیش بورڈ</a>
            <a class="btn btn-outline-light" href="{{ route('admin.payment-reconciliation.export', ['start_date'=>$report['period']['start'],'end_date'=>$report['period']['end'],'payment_method'=>$selectedMethod]) }}"><i class="fas fa-file-csv ml-1"></i> CSV</a>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <form class="card reconciliation-card card-body mb-3" method="GET" action="{{ route('admin.payment-reconciliation.index') }}">
        <div class="form-row align-items-end">
            <div class="form-group col-md-3 mb-md-0"><label for="start_date">شروع تاریخ</label><input id="start_date" type="date" name="start_date" value="{{ $report['period']['start'] }}" class="form-control" required></div>
            <div class="form-group col-md-3 mb-md-0"><label for="end_date">آخری تاریخ</label><input id="end_date" type="date" name="end_date" value="{{ $report['period']['end'] }}" class="form-control" required></div>
            <div class="form-group col-md-3 mb-md-0"><label for="payment_method_filter">ادائیگی کا طریقہ</label><select id="payment_method_filter" name="payment_method" class="form-control"><option value="">تمام دستی طریقے</option>@foreach($methods as $value=>$label)<option value="{{ $value }}" @selected($selectedMethod===$value)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-3"><button class="btn btn-primary">رپورٹ دکھائیں</button> <a class="btn btn-light" href="{{ route('admin.payment-reconciliation.index') }}">موجودہ مہینہ</a></div>
        </div>
    </form>

    <div class="row mb-3">
        @foreach([
            ['تصدیق شدہ متوقع رقم',$report['summary']['expected_amount'],'primary'],
            ['فراہم کنندہ کی درج رقم',$report['summary']['actual_amount'],'info'],
            ['ابھی ملانے والی رقم',$report['summary']['outstanding_amount'],$report['summary']['outstanding_amount']==0?'success':'warning'],
            ['درست دن',$report['summary']['matched_days'],'success'],
            ['زیرِ انتظار دن',$report['summary']['pending_days'],'warning'],
            ['فرق والے دن',$report['summary']['variance_days'],'danger'],
        ] as [$label,$amount,$color])
        <div class="col-6 col-xl-2 mb-2"><div class="card reconciliation-card h-100"><div class="card-body py-3"><small class="text-muted">{{ $label }}</small><div class="h5 text-{{ $color }} mb-0">@if(str_contains($label,'رقم')) روپے {{ number_format($amount,2) }} @else {{ number_format($amount) }} @endif</div></div></div></div>
        @endforeach
    </div>

    <div class="alert alert-info">
        <strong>طریقہ:</strong> ہر تاریخ اور ادائیگی طریقے کے لیے TMS کی تصدیق شدہ رقم دیکھیں، پھر والٹ/بینک رپورٹ کی اصل رقم اور حوالہ درج کریں۔ صفر رقم درج کرنے پر وضاحتی نوٹ ضروری ہے۔ فرق درست ہونے تک ریکارڈ دوبارہ محفوظ کیا جا سکتا ہے؛ ہر تبدیلی کی تاریخ محفوظ رہتی ہے۔
    </div>

    @forelse($report['rows'] as $row)
        @php
            $record = $row['reconciliation'];
            $statusLabel = ['pending'=>'زیرِ انتظار','matched'=>'مکمل درست','variance'=>'فرق موجود'][$row['status']];
            $statusClass = ['pending'=>'warning','matched'=>'success','variance'=>'danger'][$row['status']];
        @endphp
        <article class="card reconciliation-card mb-3">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center">
                <div><strong>{{ \Carbon\Carbon::parse($row['date'])->format('d-m-Y') }} · {{ $row['method_label'] }}</strong><div class="small text-muted">{{ $row['expected_count'] }} تصدیق شدہ ادائیگیاں</div></div>
                <span class="badge badge-{{ $statusClass }}"><span class="status-dot status-{{ $row['status'] }}"></span>{{ $statusLabel }}</span>
            </div>
            <div class="card-body">
                @if($row['snapshot_changed'])<div class="alert alert-warning">اس تاریخ کی تصدیق شدہ ادائیگیاں پچھلی مصالحت کے بعد تبدیل ہوئی ہیں۔ موجودہ رقم دوبارہ ملائیں۔</div>@endif
                <div class="row mb-3">
                    <div class="col-md-3 mb-2"><small class="text-muted d-block">TMS متوقع رقم</small><strong class="amount-ltr">PKR {{ number_format($row['expected_amount'],2) }}</strong></div>
                    <div class="col-md-3 mb-2"><small class="text-muted d-block">فراہم کنندہ رقم</small><strong class="amount-ltr">PKR {{ number_format($row['actual_amount'],2) }}</strong></div>
                    <div class="col-md-3 mb-2"><small class="text-muted d-block">فرق</small><strong class="amount-ltr text-{{ $row['variance_amount']==0?'success':'danger' }}">PKR {{ number_format($row['variance_amount'],2) }}</strong></div>
                    <div class="col-md-3 mb-2"><small class="text-muted d-block">آخری مصالحت</small><strong>{{ $record?->reconciled_at?->format('d-m-Y h:i A') ?: '—' }}</strong>@if($record)<div class="small text-muted">{{ $record->reconciler->name ?? $record->reconciler->username ?? 'سابق صارف' }} · {{ $record->events_count }} اندراج</div>@endif</div>
                </div>
                @if($record?->external_reference)<div class="small mb-1"><strong>فراہم کنندہ حوالہ:</strong> <code>{{ $record->external_reference }}</code></div>@endif
                @if($record?->notes)<div class="small mb-3"><strong>نوٹ:</strong> {{ $record->notes }}</div>@endif
                @if($record?->events->isNotEmpty())
                    <details class="mb-3">
                        <summary class="text-primary" style="cursor:pointer">تبدیلی کی مکمل تاریخ ({{ $record->events_count }})</summary>
                        <div class="table-responsive mt-2"><table class="table table-sm table-bordered bg-white mb-0">
                            <thead><tr><th>وقت / صارف</th><th>اس وقت متوقع</th><th>درج رقم</th><th>فرق</th><th>حوالہ / نوٹ</th></tr></thead>
                            <tbody>@foreach($record->events as $event)<tr>
                                <td>{{ $event->reconciled_at->format('d-m-Y h:i A') }}<br><small>{{ $event->reconciler->name ?? $event->reconciler->username ?? 'سابق صارف' }}</small></td>
                                <td><span dir="ltr">PKR {{ number_format($event->expected_amount,2) }}</span><br><small>{{ $event->expected_count }} ادائیگیاں</small></td>
                                <td dir="ltr">PKR {{ number_format($event->actual_amount,2) }}</td>
                                <td dir="ltr">PKR {{ number_format($event->variance_amount,2) }}</td>
                                <td>@if($event->external_reference)<code>{{ $event->external_reference }}</code>@endif @if($event->notes)<div class="small">{{ $event->notes }}</div>@endif</td>
                            </tr>@endforeach</tbody>
                        </table></div>
                    </details>
                @endif
                <form method="POST" action="{{ route('admin.payment-reconciliation.store') }}" class="border rounded p-3 bg-light">
                    @csrf
                    <input type="hidden" name="settlement_date" value="{{ $row['date'] }}">
                    <input type="hidden" name="payment_method" value="{{ $row['method'] }}">
                    <div class="form-row align-items-end">
                        <div class="form-group col-md-3 mb-md-0"><label>والٹ/بینک کی اصل رقم</label><input type="number" name="actual_amount" min="0" max="999999999999.99" step="0.01" class="form-control" value="{{ old('actual_amount', $record?->actual_amount ?? number_format($row['expected_amount'],2,'.','')) }}" required></div>
                        <div class="form-group col-md-3 mb-md-0"><label>سیٹلمنٹ / اسٹیٹمنٹ حوالہ</label><input type="text" name="external_reference" maxlength="100" class="form-control" dir="ltr" value="{{ old('external_reference',$record?->external_reference) }}"></div>
                        <div class="form-group col-md-4 mb-md-0"><label>وضاحتی نوٹ</label><input type="text" name="notes" maxlength="1000" class="form-control" value="{{ old('notes',$record?->notes) }}"></div>
                        <div class="col-md-2"><button class="btn btn-{{ $record?'outline-primary':'primary' }} btn-block">{{ $record ? 'دوبارہ ملائیں' : 'مصالحت محفوظ کریں' }}</button></div>
                    </div>
                </form>
            </div>
        </article>
    @empty
        <div class="card reconciliation-card"><div class="card-body text-center py-5"><i class="fas fa-balance-scale fa-3x text-muted mb-3"></i><h2 class="h5">اس مدت میں تصدیق شدہ دستی ادائیگی موجود نہیں</h2><p class="text-muted mb-0">گاہک کی EasyPaisa، JazzCash، بینک یا Raast ادائیگی تصدیق ہونے کے بعد یہاں روزانہ مصالحت کے لیے نظر آئے گی۔</p></div></div>
    @endforelse
</div></section>
@endsection
