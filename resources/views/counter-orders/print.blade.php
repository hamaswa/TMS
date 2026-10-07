<!DOCTYPE html>
<html lang="ur" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مشترکہ آرڈر رسید — {{ $counterOrder->reference }}</title>
    <style>
        @font-face{font-family:'Noto Nastaliq Urdu';src:url('/assets/fonts/noto-nastaliq-urdu/NotoNastaliqUrdu-VariableFont_wght.woff2') format('woff2');font-display:swap}
        *{box-sizing:border-box}body{margin:0;color:#16283d;background:#eef2f6;font-family:'Noto Nastaliq Urdu',serif}.print-toolbar{display:flex;justify-content:center;gap:8px;padding:12px}.print-toolbar a,.print-toolbar button{border:1px solid #bdd0e4;border-radius:8px;padding:7px 12px;color:#173b62;background:#fff;font:700 13px inherit;text-decoration:none;cursor:pointer}.print-toolbar .primary{border-color:#1769e0;color:#fff;background:#1769e0}.receipt{width:80mm;min-height:120mm;margin:0 auto 24px;padding:7mm;background:#fff;box-shadow:0 10px 30px rgba(22,40,61,.12)}.tms-paper-a4 .receipt{width:210mm;min-height:297mm;padding:16mm}.brand{text-align:center}.brand img{display:block;max-width:86px;max-height:68px;margin:0 auto 7px;object-fit:contain}.brand h1{margin:0;font-size:20px}.brand p{margin:3px 0;color:#68798b;font-size:11px}.receipt-head{display:flex;justify-content:space-between;gap:15px;margin:17px 0 11px;padding:10px 0;border-top:2px solid #173b62;border-bottom:1px solid #ccd7e3}.receipt-head div{min-width:0}.receipt-head small{display:block;color:#718096;font-size:10px}.receipt-head strong{display:block;direction:ltr;font:800 13px Arial,sans-serif;overflow-wrap:anywhere}.customer{margin-bottom:13px;padding:10px 12px;border-radius:9px;background:#f6f9fc}.customer small{display:block;color:#718096;font-size:10px}.customer strong{font-size:15px}.items{display:grid;gap:8px}.item{display:grid;grid-template-columns:minmax(0,1fr);gap:5px;padding:10px 0;border-bottom:1px solid #dce4ec;break-inside:avoid}.item-heading{display:flex;align-items:flex-start;gap:6px}.item h2{min-width:0;margin:0 0 3px;font-size:13px;line-height:1.75}.item p{margin:0;color:#65768a;font-size:10px;line-height:1.9}.item-price{direction:ltr;justify-self:start;white-space:nowrap;font:800 12px Arial,sans-serif;text-align:left}.tag{display:inline-block;flex:0 0 auto;padding:2px 6px;border-radius:99px;color:#1769e0;background:#eaf3ff;font-size:9px}.summary{margin-top:14px;border-top:2px solid #173b62}.summary-row{display:flex;justify-content:space-between;gap:12px;padding:7px 0;border-bottom:1px solid #e1e7ee}.summary-row strong{direction:ltr;font:800 13px Arial,sans-serif}.summary-row.total{font-size:15px}.summary-row.balance{color:#a63b32}.order-note{margin-top:12px;padding:9px 11px;border-radius:8px;color:#44566b;background:#f7f9fb;font-size:10px;line-height:2}.footer{margin-top:16px;text-align:center;color:#526477;font-size:10px}.footer p{margin:2px 0}.qr{display:flex;align-items:center;justify-content:center;gap:8px;margin-top:12px}.qr svg{width:68px;height:68px}.qr span{max-width:120px;font-size:9px}.reference{direction:ltr;font:700 9px Arial,sans-serif}.tms-paper-a4 .items{grid-template-columns:1fr 1fr;gap:0 20px}.tms-paper-a4 .item{grid-template-columns:minmax(0,1fr) auto;gap:10px;padding:13px 0}.tms-paper-a4 .item h2{font-size:15px}.tms-paper-a4 .item p{font-size:12px}.tms-paper-a4 .summary{max-width:390px;margin-right:auto}.tms-paper-a4 .receipt-head strong{font-size:15px}
        @media screen and (max-width:700px){.receipt,.tms-paper-a4 .receipt{width:calc(100% - 20px);min-height:0;padding:18px}.tms-paper-a4 .items{grid-template-columns:1fr}.print-toolbar{flex-wrap:wrap}}
        @media print{@page{size:80mm auto;margin:6mm 0 3mm}body{background:#fff}.print-toolbar{display:none!important}.receipt{margin:0;box-shadow:none;width:80mm;padding:5mm}}
    </style>
</head>
<body class="tms-counter-order-print tms-paper-{{ $printConfig['paper'] }}">
    <nav class="print-toolbar" aria-label="پرنٹ اختیارات">
        <button class="primary" type="button" onclick="window.print()">ابھی پرنٹ کریں</button>
        @if(count($printConfig['paper_options']) > 1)
            @foreach($printConfig['paper_options'] as $paper=>$label)
                <a href="{{ request()->fullUrlWithQuery(['paper'=>$paper]) }}">{{ $label }}</a>
            @endforeach
        @endif
        <button type="button" onclick="window.history.back()">واپس جائیں</button>
    </nav>
    <main class="receipt">
        <header class="brand">
            @if($setting?->logo_url)<img src="{{ $setting->logo_url }}" alt="{{ $setting->name }} لوگو">@endif
            <h1>{{ $setting?->name ?: 'BuyNStitch' }}</h1>
            <p>کپڑا اور سلائی — مشترکہ آرڈر رسید</p>
        </header>
        <section class="receipt-head">
            <div><small>آرڈر نمبر</small><strong>{{ $counterOrder->reference }}</strong></div>
            <div><small>تاریخ</small><strong>{{ $counterOrder->created_at?->format('d-m-Y h:i A') }}</strong></div>
        </section>
        <section class="customer"><small>گاہک</small><strong>{{ $counterOrder->customer?->name }}</strong></section>
        <section class="items" aria-label="آرڈر آئٹمز">
            @foreach($counterOrder->items->where('status','confirmed') as $item)
                <article class="item">
                    <div>
                        @if($item->type==='cloth')
                            <div class="item-heading"><span class="tag">کپڑا</span><h2 dir="auto">{{ $item->cloth?->name }} — {{ $item->cloth?->brand?->name }} — {{ $item->cloth?->type?->name }}</h2></div>
                            <p>{{ data_get($item->details,'color') ?: 'رنگ لاگو نہیں' }} · {{ number_format((float)$item->length,2) }} میٹر · Rs. {{ number_format((float)$item->unit_price,2) }} فی میٹر</p>
                        @else
                            <div class="item-heading"><span class="tag">سلائی</span><h2 dir="auto">{{ $item->measurementProfile?->name }}</h2></div>
                            <p>{{ $item->measurementTemplate?->name }} · {{ (int)$item->quantity }} سوٹ · واپسی {{ $item->due_date?->format('d-m-Y') }}</p>
                        @endif
                        @if($item->note)<p dir="auto">{{ $item->note }}</p>@endif
                    </div>
                    <strong class="item-price">Rs. {{ number_format((float)$item->line_total,2) }}</strong>
                </article>
            @endforeach
        </section>
        <section class="summary">
            <div class="summary-row total"><span>کل آرڈر</span><strong>Rs. {{ number_format((float)$counterOrder->subtotal,2) }}</strong></div>
            <div class="summary-row"><span>وصول شدہ</span><strong>Rs. {{ number_format((float)$counterOrder->paid_amount,2) }}</strong></div>
            <div class="summary-row balance"><span>اس آرڈر کا بقایا</span><strong>Rs. {{ number_format((float)$counterOrder->balance_amount,2) }}</strong></div>
        </section>
        @if($counterOrder->note)<div class="order-note" dir="auto">{{ $counterOrder->note }}</div>@endif
        <footer class="footer">
            @if($setting?->address)<p>{{ $setting->address }}</p>@endif
            @if($setting?->contact_no)<p dir="ltr">{{ $setting->contact_no }}</p>@endif
            @if($setting?->note)<p>{{ $setting->note }}</p>@endif
            @if($printConfig['show_qr'] && $printConfig['qr_svg'])<div class="qr">{!! $printConfig['qr_svg'] !!}<span>رسید کی شناخت کے لیے تصدیقی حوالہ</span></div>@endif
            <p class="reference">ORDER REF: {{ $counterOrder->reference }}</p>
        </footer>
    </main>
</body>
</html>
