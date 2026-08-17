<!doctype html>
<html lang="ur" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $tailor->name }} — ہفتہ وار حساب</title>
    <style>
        @font-face{font-family:'Noto Nastaliq Urdu';src:url('{{ asset('assets/fonts/noto-nastaliq-urdu/NotoNastaliqUrdu-VariableFont_wght.woff2') }}') format('woff2');font-display:swap}
        *{box-sizing:border-box}
        html,body{margin:0;padding:0;background:#eef1f5;color:#000;font-family:'Noto Nastaliq Urdu',Tahoma,Arial,sans-serif}
        body{direction:rtl;text-align:right}
        .screen-actions{display:flex;direction:rtl;justify-content:center;gap:8px;padding:14px}
        .screen-actions button{min-height:42px;padding:8px 15px;border:1px solid #cfd8e4;border-radius:8px;color:#243b53;background:#fff;font:700 14px 'Noto Nastaliq Urdu',Tahoma,sans-serif;cursor:pointer}
        .screen-actions .print{color:#fff;border-color:#1769e0;background:#1769e0}
        .receipt{width:80mm;min-height:80mm;margin:0 auto 30px;padding:3mm 4mm;background:#fff;box-shadow:0 12px 36px rgba(18,42,70,.16)}
        .receipt-header{text-align:center;border-bottom:1px dashed #000;padding-bottom:2.5mm}
        .receipt-logo{display:block;max-width:24mm;max-height:16mm;margin:0 auto 1.5mm;object-fit:contain;filter:grayscale(1)}
        .shop-name{margin:0;font-size:16px;font-weight:900;line-height:1.8}
        .receipt-kind{margin-top:1mm;font-size:10px;font-weight:700}
        .receipt-meta{display:grid;grid-template-columns:1fr 1fr;gap:1mm 3mm;padding:2.5mm 0;border-bottom:1px dashed #000;font-size:10px;line-height:1.9}
        .receipt-meta div:nth-child(even){text-align:left}.receipt-meta strong{font-weight:900}.ltr{direction:ltr;display:inline-block}
        .section-title{display:flex;align-items:center;justify-content:space-between;gap:2mm;margin:2.8mm 0 1.5mm;font-size:11px;font-weight:900}.section-title span:last-child{font-size:8px;font-weight:600}
        .work-head{display:grid;grid-template-columns:.9fr .8fr .65fr 1.15fr;gap:1.5mm;padding:1.5mm 1mm;border-top:1px solid #000;border-bottom:1px solid #000;font-size:7px;font-weight:900}.work-head span:nth-child(n+2){text-align:left}
        .work-item{padding:1.7mm 1mm;border-bottom:1px dotted #777;break-inside:avoid;page-break-inside:avoid}
        .work-main{display:grid;grid-template-columns:.9fr .8fr .65fr 1.15fr;align-items:center;gap:1.5mm;font-size:9px;font-weight:800;line-height:1.9}.work-main .rate-breakdown,.work-main .work-total{direction:ltr;text-align:left;white-space:nowrap;font-family:Tahoma,Arial,sans-serif;font-size:8px}.work-main .serial-list{direction:ltr;text-align:left;overflow-wrap:anywhere;font:700 8px/1.7 Tahoma,Arial,sans-serif}
        .money-lines{padding:1mm 0;border-top:1px solid #000;border-bottom:1px solid #000}
        .money-line{display:flex;align-items:center;justify-content:space-between;gap:3mm;padding:1.1mm 1mm;font-size:10px;line-height:1.8}.money-line strong{direction:ltr;white-space:nowrap}.money-line.muted{font-size:9px}.money-line.deduction strong:before{content:'− '}.money-line.covered{border:1px solid #777;border-radius:2px;margin:1mm 0;padding:1.5mm 1mm}.money-line.final{margin-top:1mm;padding:2mm 1mm;border-top:2px solid #000;font-size:13px;font-weight:900}.money-line.final strong{font-size:14px}
        .advance-note{margin-top:1.5mm;padding:1.5mm 1mm;border:1px dashed #555;border-radius:2px;font-size:8px;font-weight:800;line-height:1.9;text-align:center}.advance-note strong{direction:ltr;display:inline-block;font-size:9px}
        .receipt-footer{padding-top:2.5mm;text-align:center;font-size:8px;line-height:2}.receipt-footer .contact{direction:ltr;font-weight:900}.receipt-footer .thank-you{margin-top:1mm;font-size:10px;font-weight:900}
        .empty{padding:5mm 1mm;border:1px dashed #777;text-align:center;font-size:9px}
        @media print{
            @page{size:80mm auto;margin:0}
            html,body{width:80mm!important;min-width:80mm!important;background:#fff!important}
            .screen-actions{display:none!important}
            .receipt{width:80mm!important;min-height:0;margin:0!important;padding:3mm 4mm!important;box-shadow:none!important}
            *{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}
        }
    </style>
</head>
<body>
@php
    $salaryAmount = (float) $tailor_records->where('comment', 'salary')->sum('amount');
    $otherExpenseAmount = (float) $tailor_records->where('comment', 'chai')->sum('amount');
    $weeklyAdvance = (float) $tailor_records->where('comment', 'advance')->sum('amount');
    $salaryAndOtherPayments = $salaryAmount + $otherExpenseAmount;
    $advanceCoveredFromMain = min($weeklyAdvance, (float) $advanceCutAmount);
    $advanceToDeductFromWeeklyPayment = max(0, $weeklyAdvance - $advanceCoveredFromMain);
    $weeklySettlementTotal = max(0, $salaryAndOtherPayments - $advanceToDeductFromWeeklyPayment);
    $totalSuits = $tailor_report->sum(fn ($order) => max(1, (int) $order->suitQuantity));
    $workSummary = $tailor_report
        ->groupBy(fn ($order) => $order->rate?->options?->Name ?: $order->rate?->type ?: $order->design ?: '—')
        ->map(function ($orders, $sewingName) {
            $serials = $orders->flatMap(function ($order) {
                $decoded = json_decode((string) $order->suitNum, true);
                return is_array($decoded) ? $decoded : [$order->suitNum];
            })->filter(fn ($serial) => filled($serial))->unique()->values();
            $rateGroups = $orders->groupBy(fn ($order) => number_format((float) $order->tailor_price, 2, '.', ''));
            $rateBreakdown = $rateGroups->map(function ($rateOrders, $rate) {
                $suits = $rateOrders->sum(fn ($order) => max(1, (int) $order->suitQuantity));
                return $suits.' × '.number_format((float) $rate, 0);
            })->implode(' + ');

            return [
                'name' => $sewingName,
                'suits' => $orders->sum(fn ($order) => max(1, (int) $order->suitQuantity)),
                'rate_breakdown' => $rateBreakdown,
                'total' => $orders->sum(fn ($order) => $order->tailorAmountDue()),
                'serials' => $serials,
            ];
        })->values();
    $shopName = $setting?->name ?: auth()->user()->name;
@endphp

<div class="screen-actions">
    <button type="button" class="print" id="printReceipt">رسید پرنٹ کریں</button>
    <button type="button" id="goBack">رپورٹ پر واپس جائیں</button>
</div>

<main class="receipt" aria-label="درزی کا ہفتہ وار حساب">
    <header class="receipt-header">
        @if($setting?->logo_url)<img class="receipt-logo" src="{{ $setting->logo_url }}" alt="{{ $shopName }}">@endif
        <h1 class="shop-name">{{ $shopName }}</h1>
        <div class="receipt-kind">درزی کا ہفتہ وار حساب</div>
    </header>

    <section class="receipt-meta">
        <div>درزی: <strong>{{ $tailor->name }}</strong></div>
        <div>فون: <strong class="ltr">{{ $tailor->phone_number1 ?: '—' }}</strong></div>
        <div>مدت: <strong>{{ $startDate->format('d-m-Y') }}</strong></div>
        <div>تا: <strong>{{ $endDate->format('d-m-Y') }}</strong></div>
        <div>پرنٹ: <strong>{{ now()->format('d-m-Y') }}</strong></div>
        <div>وقت: <strong class="ltr">{{ now()->format('h:i A') }}</strong></div>
    </section>

    <div class="section-title"><span>سلائی کا ریکارڈ</span><span>{{ $tailor_report->count() }} آرڈرز · {{ $totalSuits }} سوٹ</span></div>
    @if($workSummary->isNotEmpty())
        <div class="work-head"><span>سلائی کی قسم</span><span>سوٹ × اجرت</span><span>کل اجرت</span><span>سیریل نمبر</span></div>
        @foreach($workSummary as $work)
            <article class="work-item">
                <div class="work-main">
                    <span>{{ $work['name'] }}</span>
                    <span class="rate-breakdown">{{ $work['rate_breakdown'] }}</span>
                    <span class="work-total">Rs. {{ number_format($work['total'], 0) }}</span>
                    <span class="serial-list">{{ $work['serials']->isNotEmpty() ? $work['serials']->implode(', ') : '—' }}</span>
                </div>
            </article>
        @endforeach
    @else
        <div class="empty">اس ہفتے کوئی سلائی ریکارڈ موجود نہیں۔</div>
    @endif

    <div class="section-title"><span>ہفتہ وار ادائیگی کا حساب</span><span>رقم روپے میں</span></div>
    <section class="money-lines">
        <div class="money-line"><span>اجرت</span><strong>Rs. {{ number_format($salaryAmount,2) }}</strong></div>
        <div class="money-line"><span>دیگر ادائیگی</span><strong>Rs. {{ number_format($otherExpenseAmount,2) }}</strong></div>
        <div class="money-line"><span>ہفتہ وار ایڈوانس</span><strong>Rs. {{ number_format($weeklyAdvance,2) }}</strong></div>
        @if($advanceCoveredFromMain > 0)<div class="money-line"><span>مرکزی ایڈوانس سے کٹوتی</span><strong>Rs. {{ number_format($advanceCoveredFromMain,2) }}</strong></div>@endif
        @if($advanceToDeductFromWeeklyPayment > 0)<div class="money-line"><span>ادائیگیوں سے منہا ایڈوانس</span><strong>- Rs. {{ number_format($advanceToDeductFromWeeklyPayment,2) }}</strong></div>@endif
        <div class="money-line final"><span>حتمی ہفتہ وار رقم</span><strong>Rs. {{ number_format($weeklySettlementTotal,2) }}</strong></div>
    </section>
    @if($advanceCoveredFromMain > 0)
        <div class="advance-note"><strong>Rs. {{ number_format($advanceCoveredFromMain,2) }}</strong> مرکزی ایڈوانس سے کاٹا جا چکا ہے۔</div>
    @endif

    <footer class="receipt-footer">
        @if($setting?->address)<div>{{ strip_tags($setting->address) }}</div>@endif
        @if($setting?->contact_no)<div class="contact">{{ $setting->contact_no }}</div>@endif
        <div class="thank-you">شکریہ</div>
    </footer>
</main>

<script>
    document.getElementById('printReceipt').addEventListener('click',function(){window.print();});
    document.getElementById('goBack').addEventListener('click',function(){window.history.back();});
</script>
</body>
</html>
