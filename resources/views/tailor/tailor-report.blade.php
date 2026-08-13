@extends('main')

@section('content')
@php
    $periodLabel = $filterType === 'monthly' ? 'موجودہ مہینہ' : 'موجودہ ہفتہ';
    $periodRange = $startDate->format('d-m-Y').' تا '.$endDate->format('d-m-Y');
    $totalSuits = $tailor_report->sum(fn ($order) => max(1, (int) $order->suitQuantity));
    $paidAmount = (float) $tailor_records->whereIn('comment', ['salary', 'chai'])->sum('amount');
    $salaryAmount = (float) $tailor_records->where('comment', 'salary')->sum('amount');
    $otherExpenseAmount = (float) $tailor_records->where('comment', 'chai')->sum('amount');
    $periodAdvance = (float) $tailor_records->where('comment', 'advance')->sum('amount');
    $advanceCoveredFromMain = min($periodAdvance, (float) $advanceCutAmount);
    $advanceToDeductFromWeeklyPayment = max(0, $periodAdvance - $advanceCoveredFromMain);
    $weeklySettlementTotal = $paidAmount - $advanceToDeductFromWeeklyPayment;
    $payableAmount = max(0, (float) $total_amount - $paidAmount);
    $recordLabels = ['advance' => 'ایڈوانس', 'salary' => 'اجرت کی ادائیگی', 'chai' => 'چائے / خرچ'];
@endphp

<style>
    .tailor-report-page{--tr-blue:#1769e0;--tr-navy:#102a50;--tr-muted:#687a91;--tr-line:#e0e8f2;direction:rtl;padding:28px 0 50px}
    .tailor-report-shell{width:min(100% - 32px,1700px);margin-inline:auto}
    .tailor-report-head{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:20px}
    .tailor-report-title{display:flex;align-items:center;gap:14px}.tailor-report-avatar{display:grid;place-items:center;width:56px;height:56px;border-radius:16px;color:#fff;background:linear-gradient(135deg,#2479ee,#0c5bd1);font-size:1.3rem;font-weight:800;box-shadow:0 9px 20px rgba(23,105,224,.2)}
    .tailor-report-title h1{margin:0 0 5px;color:var(--tr-navy);font-size:clamp(1.5rem,2vw,2rem);font-weight:800}.tailor-report-title p{margin:0;color:var(--tr-muted)}
    .tailor-head-actions{display:flex;flex-wrap:wrap;gap:9px}.tailor-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:43px;padding:9px 15px;border:1px solid #d4deeb;border-radius:10px;color:#3c526d;background:#fff;font-weight:700;text-decoration:none!important}.tailor-btn:hover{color:var(--tr-blue);border-color:#a9c9f3}.tailor-btn.is-primary{color:#fff;border-color:var(--tr-blue);background:var(--tr-blue)}.tailor-btn.is-success{color:#fff;border-color:#15915a;background:#15915a}
    .tailor-flash{display:flex;gap:10px;padding:14px 16px;margin-bottom:16px;border:1px solid #c9eadb;border-radius:12px;color:#146e46;background:#ecf9f3}
    .tailor-profile{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:19px 22px;margin-bottom:18px;border:1px solid #dce7f5;border-radius:16px;background:linear-gradient(135deg,#f5f9ff,#fff);box-shadow:0 6px 22px rgba(24,58,96,.05)}
    .tailor-profile-info{display:flex;flex-wrap:wrap;gap:28px}.tailor-profile-item small{display:block;margin-bottom:4px;color:var(--tr-muted);font-size:.78rem}.tailor-profile-item strong{color:var(--tr-navy);font-size:1.05rem}.tailor-profile-item strong.money{direction:ltr;display:block}
    .tailor-filter{display:flex;align-items:end;gap:10px}.tailor-filter label{display:block;margin-bottom:6px;color:#52657e;font-size:.78rem;font-weight:800}.tailor-filter select{min-width:180px;height:43px;padding:7px 12px;border:1px solid #d2ddea;border-radius:10px;background:#fff}.tailor-filter button{height:43px;border:0;border-radius:10px;color:#fff;background:var(--tr-blue);font-weight:800;padding:0 16px}
    .tailor-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:20px}.tailor-stat{display:flex;align-items:center;gap:13px;min-height:105px;padding:17px;border:1px solid var(--tr-line);border-radius:15px;background:#fff;box-shadow:0 6px 22px rgba(21,47,81,.05)}.tailor-stat-icon{display:grid;place-items:center;flex:0 0 48px;width:48px;height:48px;border-radius:50%;font-size:19px}.tailor-stat:nth-child(1) .tailor-stat-icon{color:#1769e0;background:#eaf3ff}.tailor-stat:nth-child(2) .tailor-stat-icon{color:#15915a;background:#e8f8f0}.tailor-stat:nth-child(3) .tailor-stat-icon{color:#e38b14;background:#fff4df}.tailor-stat:nth-child(4) .tailor-stat-icon{color:#7b4ad9;background:#f1ebff}.tailor-stat small{display:block;color:var(--tr-muted)}.tailor-stat strong{direction:ltr;display:block;margin-top:5px;color:var(--tr-navy);font-size:1.25rem;font-weight:800;text-align:right}
    .tailor-report-grid{display:grid;grid-template-columns:minmax(0,1.45fr) minmax(380px,.55fr);gap:18px;align-items:start}.tailor-panel{overflow:hidden;border:1px solid var(--tr-line);border-radius:16px;background:#fff;box-shadow:0 8px 28px rgba(21,47,81,.055)}.tailor-panel-head{display:flex;align-items:center;justify-content:space-between;gap:15px;padding:18px 20px;border-bottom:1px solid var(--tr-line)}.tailor-panel-head h2{margin:0 0 4px;color:var(--tr-navy);font-size:1.15rem;font-weight:800}.tailor-panel-head p{margin:0;color:var(--tr-muted);font-size:.78rem}.tailor-count{padding:6px 11px;border-radius:999px;color:#1769e0;background:#eaf3ff;font-weight:800}
    .tailor-table-wrap{overflow-x:auto}.tailor-table{width:100%;min-width:760px;margin:0!important}.tailor-table thead th{padding:13px 14px!important;border:0!important;border-bottom:1px solid var(--tr-line)!important;color:#566980;background:#f5f8fc;font-size:.82rem;font-weight:800;text-align:right;white-space:nowrap}.tailor-table tbody td{padding:15px 14px!important;border-top:0!important;border-bottom:1px solid #edf1f6!important;color:#2d425d;text-align:right;vertical-align:middle!important}.tailor-table tbody tr:hover{background:#fbfdff}.tailor-primary{color:var(--tr-navy);font-weight:800}.tailor-secondary{display:block;margin-top:3px;color:#8190a3;font-size:.73rem}.tailor-money{direction:ltr;display:inline-block;color:#118452;font-weight:800}.tailor-table-total td{color:var(--tr-navy)!important;background:#f4f8fd;font-weight:800}.tailor-empty{padding:45px 20px!important;color:#8593a6!important;text-align:center!important}.tailor-empty i{display:block;margin-bottom:10px;color:#b4c0cf;font-size:2rem}
    .tailor-payment-table{min-width:500px}.tailor-record-badge{display:inline-block;padding:5px 9px;border-radius:999px;font-size:.75rem;font-weight:800}.tailor-record-badge.advance{color:#a66a0a;background:#fff3db}.tailor-record-badge.salary{color:#15784c;background:#e8f8f0}.tailor-record-badge.chai{color:#7650b9;background:#f1ebff}
    .tailor-payment-summary{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:9px;padding:14px;border-bottom:1px solid var(--tr-line);background:#fbfdff}.tailor-payment-summary div{padding:11px;border:1px solid #e3eaf3;border-radius:10px;background:#fff}.tailor-payment-summary small{display:block;margin-bottom:4px;color:var(--tr-muted);font-size:.72rem}.tailor-payment-summary strong{direction:ltr;display:block;color:var(--tr-navy);font-size:.95rem;font-weight:800;text-align:right}.tailor-payment-summary .is-salary strong{color:#118452}.tailor-payment-summary .is-advance strong{color:#c27a0b}.tailor-payment-total td{color:var(--tr-navy)!important;background:#f4f8fd;font-weight:800}
    .tailor-advance-note{padding:14px 18px;border-top:1px solid var(--tr-line);color:#586b82;background:#fafcff}.tailor-advance-note strong{direction:ltr;display:inline-block;color:#d24652}
    .tailor-report-page .modal-content{overflow:hidden;border:0;border-radius:15px;box-shadow:0 20px 60px rgba(12,35,68,.22)}.tailor-report-page .modal-header{align-items:center;border-bottom:1px solid var(--tr-line)}.tailor-report-page .modal-title{color:var(--tr-navy);font-weight:800}.tailor-report-page .modal-body{text-align:right}.tailor-report-page .modal-footer{justify-content:flex-start;border-top:1px solid var(--tr-line)}.tailor-report-page .form-control{min-height:44px;border-color:#d5dfeb;border-radius:9px}
    @media(max-width:1150px){.tailor-stats{grid-template-columns:repeat(2,1fr)}.tailor-report-grid{grid-template-columns:1fr}.tailor-profile{align-items:flex-start;flex-direction:column}}
    @media(max-width:767px){.tailor-report-page{padding-top:18px}.tailor-report-shell{width:min(100% - 20px,1700px)}.tailor-report-head{align-items:flex-start;flex-direction:column}.tailor-head-actions,.tailor-filter{width:100%}.tailor-btn{flex:1}.tailor-filter>div{flex:1}.tailor-filter select{width:100%;min-width:0}.tailor-stats{grid-template-columns:1fr 1fr;gap:10px}.tailor-stat{min-height:92px;padding:13px}.tailor-profile-info{gap:17px}.tailor-panel-head{align-items:flex-start;flex-direction:column}}
    @media(max-width:480px){.tailor-stats,.tailor-payment-summary{grid-template-columns:1fr}.tailor-filter{align-items:stretch;flex-direction:column}}
</style>

<section class="main-content tailor-report-page">
    <div class="tailor-report-shell">
        <header class="tailor-report-head">
            <div class="tailor-report-title"><span class="tailor-report-avatar">{{ mb_substr($tailor->name,0,1) }}</span><div><h1>{{ $tailor->name }} کا حساب</h1><p>سلائی کی اجرت، ادائیگی، ایڈوانس اور کام کی مکمل تفصیل۔</p></div></div>
            <div class="tailor-head-actions"><a class="tailor-btn" href="{{ route('admin.Tailor.index') }}"><i class="fas fa-arrow-right"></i> درزیوں کی فہرست</a><a class="tailor-btn is-primary" href="{{ url('admin/tailor-weekly-report-print/'.$tailor->id) }}" target="_blank"><i class="fas fa-print"></i> رپورٹ پرنٹ کریں</a><button type="button" class="tailor-btn is-success" data-toggle="modal" data-target="#addRecordModal"><i class="fas fa-money-check-alt"></i> رقم درج کریں</button></div>
        </header>

        @if(session('success'))<div class="tailor-flash"><i class="fas fa-check-circle"></i><div>{{ session('success') }}</div></div>@endif
        @if($errors->any())<div class="alert alert-danger text-right">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

        <section class="tailor-profile">
            <div class="tailor-profile-info">
                <div class="tailor-profile-item"><small>درزی کا نام</small><strong>{{ $tailor->name }}</strong></div>
                <div class="tailor-profile-item"><small>رابطہ نمبر</small><strong dir="ltr">{{ $tailor->phone_number1 ?: '—' }}</strong></div>
                <div class="tailor-profile-item"><small>دکان کے پاس سیکیورٹی ڈپازٹ</small><strong class="money">Rs. {{ number_format((float)$tailor->security_deposit,2) }}</strong></div>
                <div class="tailor-profile-item"><small>درزی کو دیا گیا قابلِ وصول ایڈوانس</small><strong class="money">Rs. {{ number_format((float)$tailor->advance,2) }}</strong></div>
            </div>
            <form method="GET" action="{{ route('admin.tailor-report',$tailor) }}" class="tailor-filter"><div><label for="filterType">رپورٹ کی مدت</label><select id="filterType" name="filterType"><option value="weekly" @selected($filterType==='weekly')>موجودہ ہفتہ</option><option value="monthly" @selected($filterType==='monthly')>موجودہ مہینہ</option></select></div><button type="submit"><i class="fas fa-filter ml-1"></i> دکھائیں</button></form>
        </section>

        <div class="tailor-stats">
            <article class="tailor-stat"><span class="tailor-stat-icon"><i class="fas fa-cut"></i></span><div><small>{{ $periodLabel }} کی سلائی اجرت</small><strong>Rs. {{ number_format((float)$total_amount,2) }}</strong></div></article>
            <article class="tailor-stat"><span class="tailor-stat-icon"><i class="fas fa-check-circle"></i></span><div><small>اس مدت میں ادائیگی</small><strong>Rs. {{ number_format($paidAmount,2) }}</strong></div></article>
            <article class="tailor-stat"><span class="tailor-stat-icon"><i class="fas fa-wallet"></i></span><div><small>قابلِ ادائیگی اجرت</small><strong>Rs. {{ number_format($payableAmount,2) }}</strong></div></article>
            <article class="tailor-stat"><span class="tailor-stat-icon"><i class="fas fa-tshirt"></i></span><div><small>کل سوٹ</small><strong>{{ number_format($totalSuits) }}</strong></div></article>
        </div>

        <div class="tailor-report-grid">
            <section class="tailor-panel">
                <div class="tailor-panel-head"><div><h2>سلائی اور کام کا ریکارڈ</h2><p>{{ $periodLabel }} · {{ $periodRange }}</p></div><span class="tailor-count">{{ $tailor_report->count() }} آرڈرز</span></div>
                <div class="tailor-table-wrap"><table class="table tailor-table"><thead><tr><th>#</th><th>تاریخ</th><th>سیریل نمبر</th><th>سلائی کی قسم</th><th>فی سوٹ اجرت</th><th>سوٹ</th><th>کل اجرت</th></tr></thead><tbody>
                    @forelse($tailor_report as $order)<tr><td>{{ $loop->iteration }}</td><td><span class="tailor-primary">{{ $order->created_at?->format('d-m-Y') }}</span><span class="tailor-secondary">{{ $order->created_at?->format('D') }}</span></td><td><span class="tailor-primary">{{ $order->suitNum ?: '—' }}</span></td><td><span class="tailor-primary">{{ $order->rate?->options?->Name ?: $order->rate?->type ?: $order->design ?: '—' }}</span></td><td><span class="tailor-money">Rs. {{ number_format((float)$order->tailor_price,2) }}</span></td><td>{{ max(1,(int)$order->suitQuantity) }}</td><td><span class="tailor-money">Rs. {{ number_format((float)$order->tailor_price*max(1,(int)$order->suitQuantity),2) }}</span></td></tr>@empty<tr><td colspan="7" class="tailor-empty"><i class="fas fa-inbox"></i>اس مدت میں کوئی سلائی ریکارڈ موجود نہیں۔</td></tr>@endforelse
                </tbody><tfoot><tr class="tailor-table-total"><td colspan="5">{{ $periodLabel }} کی کل سلائی</td><td>{{ $totalSuits }}</td><td>Rs. {{ number_format((float)$total_amount,2) }}</td></tr></tfoot></table></div>
            </section>

            <section class="tailor-panel">
                <div class="tailor-panel-head"><div><h2>ادائیگی اور ایڈوانس</h2><p>اس مدت میں درج کیے گئے مالی ریکارڈ</p></div><span class="tailor-count">{{ $tailor_records->count() }} اندراج</span></div>
                <div class="tailor-payment-summary">
                    <div class="is-salary"><small>اجرت اور دیگر ادائیگیاں</small><strong>Rs. {{ number_format($paidAmount,2) }}</strong></div>
                    <div class="is-advance"><small>ہفتہ وار ایڈوانس</small><strong>Rs. {{ number_format($periodAdvance,2) }}</strong></div>
                    <div><small>حتمی ہفتہ وار رقم</small><strong>Rs. {{ number_format($weeklySettlementTotal,2) }}</strong></div>
                </div>
                <div class="tailor-table-wrap"><table class="table tailor-table tailor-payment-table"><thead><tr><th>تاریخ</th><th>قسم</th><th>رقم</th></tr></thead><tbody>
                    @forelse($tailor_records as $record)<tr><td><span class="tailor-primary">{{ $record->created_at?->format('d-m-Y') }}</span><span class="tailor-secondary">{{ $record->created_at?->format('D') }}</span></td><td><span class="tailor-record-badge {{ $record->comment }}">{{ $recordLabels[$record->comment] ?? $record->comment }}</span></td><td><span class="tailor-money">Rs. {{ number_format((float)$record->amount,2) }}</span></td></tr>@empty<tr><td colspan="3" class="tailor-empty"><i class="fas fa-receipt"></i>اس مدت میں کوئی ادائیگی درج نہیں۔</td></tr>@endforelse
                </tbody><tfoot><tr class="tailor-payment-total"><td colspan="2">قابل ادائیگی ہفتہ وار کل</td><td>Rs. {{ number_format($weeklySettlementTotal,2) }}</td></tr></tfoot></table></div>
                @if($advanceCoveredFromMain > 0)
                    <div class="tailor-advance-note"><i class="fas fa-check-circle text-success ml-1"></i> ہفتہ وار ایڈوانس میں سے <strong>Rs. {{ number_format($advanceCoveredFromMain,2) }}</strong> مرکزی ایڈوانس سے کاٹا جا چکا ہے، اس لیے اسے ہفتہ وار رقم سے دوبارہ منہا نہیں کیا گیا۔</div>
                @elseif($periodAdvance > 0)
                    <div class="tailor-advance-note"><i class="fas fa-info-circle ml-1"></i> ہفتہ وار ایڈوانس <strong>Rs. {{ number_format($periodAdvance,2) }}</strong> اجرت اور دیگر ادائیگیوں سے منہا کیا گیا ہے۔</div>
                @endif
                @if($paidAmount>0 && (float)$tailor->advance>0)<div class="tailor-advance-note"><button type="button" class="tailor-btn" data-toggle="modal" data-target="#cutAdvanceModal"><i class="fas fa-minus-circle"></i> ادائیگی کو ایڈوانس سے کاٹیں</button></div>@endif
            </section>
        </div>

        <div class="modal fade" id="addRecordModal" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form method="post" action="{{ route('admin.tailor.addRecord',$tailor) }}">@csrf<div class="modal-header"><h5 class="modal-title"><i class="fas fa-money-check-alt text-success ml-2"></i> درزی کا مالی ریکارڈ</h5><button type="button" class="close mr-auto ml-0" data-dismiss="modal"><span>&times;</span></button></div><div class="modal-body"><div class="form-group"><label for="recordComment">لین دین کی قسم</label><select id="recordComment" name="comment" class="form-control" required><option value="salary">اجرت کی ادائیگی</option><option value="advance">ایڈوانس</option><option value="chai">چائے / دیگر خرچ</option></select></div><div class="form-group mb-0"><label for="recordAmount">رقم</label><div class="input-group" dir="ltr"><div class="input-group-prepend"><span class="input-group-text">Rs.</span></div><input id="recordAmount" type="number" min="0.01" step="0.01" name="amount" class="form-control" required></div></div></div><div class="modal-footer"><button type="submit" class="btn btn-success">محفوظ کریں</button><button type="button" class="btn btn-light" data-dismiss="modal">منسوخ کریں</button></div></form></div></div></div>

        <div class="modal fade" id="cutAdvanceModal" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form method="post" action="{{ route('admin.tailor.cutAdvanceRecord',$tailor) }}">@csrf<input type="hidden" name="tailor_id" value="{{ $tailor->id }}"><input type="hidden" name="total" value="{{ $paidAmount }}"><div class="modal-header"><h5 class="modal-title">ایڈوانس سے کٹوتی</h5><button type="button" class="close mr-auto ml-0" data-dismiss="modal"><span>&times;</span></button></div><div class="modal-body"><div class="alert alert-info">دستیاب ایڈوانس: <strong>Rs. {{ number_format((float)$tailor->advance,2) }}</strong></div><div class="form-group mb-0"><label for="advanceCutAmount">کٹوتی کی رقم</label><input id="advanceCutAmount" type="number" min="0.01" max="{{ min((float)$tailor->advance,$paidAmount) }}" step="0.01" name="amount" class="form-control" required></div></div><div class="modal-footer"><button type="submit" class="btn btn-primary">کٹوتی محفوظ کریں</button><button type="button" class="btn btn-light" data-dismiss="modal">منسوخ کریں</button></div></form></div></div></div>
    </div>
</section>
@endsection
