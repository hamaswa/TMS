<!doctype html>
<html lang="ur" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ڈسپیچ شیٹ — {{ $order->reference }}</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#eef1f4;color:#17202a;font-family:"Noto Nastaliq Urdu","Segoe UI",Tahoma,sans-serif;line-height:1.7}.sheet{width:210mm;min-height:297mm;margin:12px auto;padding:14mm;background:#fff;box-shadow:0 3px 18px rgba(0,0,0,.12)}.toolbar{width:210mm;margin:16px auto 0;display:flex;gap:8px;direction:rtl}.btn{border:0;border-radius:6px;padding:10px 18px;cursor:pointer;font:inherit;background:#17202a;color:#fff;text-decoration:none}.btn.secondary{background:#6c757d}.head{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:3px solid #17202a;padding-bottom:10px}.head h1{margin:0;font-size:25px}.reference{font-family:Consolas,monospace;font-size:20px;direction:ltr;text-align:left}.grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:16px}.box{border:1px solid #aeb6bf;border-radius:7px;padding:12px}.box h2{font-size:15px;margin:0 0 8px;border-bottom:1px solid #ddd;padding-bottom:5px}.row{display:flex;justify-content:space-between;gap:14px;margin:4px 0}.row strong{white-space:nowrap}.address{white-space:pre-line}.badge{display:inline-block;border:2px solid #1b7f47;color:#1b7f47;border-radius:5px;padding:2px 10px;font-weight:700}table{width:100%;border-collapse:collapse;margin-top:18px}th,td{border:1px solid #8d969f;padding:8px;text-align:right}th{background:#edf0f2}.num{direction:ltr;text-align:left}.totals{width:48%;margin-right:auto;margin-top:12px}.note{margin-top:16px;border:1px dashed #7f8c8d;padding:10px;white-space:pre-line}.signatures{display:grid;grid-template-columns:1fr 1fr;gap:60px;margin-top:45px}.signature{border-top:1px solid #333;padding-top:7px;text-align:center}.footer{margin-top:28px;border-top:1px solid #bbb;padding-top:8px;text-align:center;font-size:12px;color:#5d6d7e}@page{size:A4;margin:0}@media print{body{background:#fff}.toolbar{display:none}.sheet{margin:0;box-shadow:none;width:210mm;min-height:297mm;page-break-after:avoid}}
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn" type="button" onclick="window.print()">پرنٹ کریں</button>
        <a class="btn secondary" href="{{ route('admin.storefront.orders.index') }}">آرڈرز پر واپس جائیں</a>
    </div>
    <main class="sheet">
        <header class="head">
            <div>
                <h1>{{ $storefront->display_name }}</h1>
                @if($storefront->address || $storefront->city)<div>{{ collect(preg_split('/\s*،\s*/u', $storefront->address ?: $storefront->city))->filter()->unique()->join('، ') }}</div>@endif
                @if($storefront->public_phone)<div dir="ltr">{{ $storefront->public_phone }}</div>@endif
            </div>
            <div>
                <div class="badge">تصدیق شدہ ڈسپیچ</div>
                <div class="reference">{{ $order->reference }}</div>
                <small>{{ now()->format('d-m-Y h:i A') }}</small>
            </div>
        </header>

        <section class="grid">
            <div class="box">
                <h2>گاہک کی تفصیل</h2>
                <div class="row"><strong>نام</strong><span>{{ $order->customer->name }}</span></div>
                <div class="row"><strong>فون</strong><span dir="ltr">{{ $order->customer->phone_number1 }}</span></div>
                @if($order->customer->phone_number2)<div class="row"><strong>متبادل فون</strong><span dir="ltr">{{ $order->customer->phone_number2 }}</span></div>@endif
                @if($order->delivery_address)<div class="row"><strong>پتہ</strong><span class="address">{{ $order->delivery_address }}</span></div>@endif
            </div>
            <div class="box">
                <h2>آرڈر کی تفصیل</h2>
                <div class="row"><strong>آرڈر کا وقت</strong><span>{{ $order->placed_at->format('d-m-Y h:i A') }}</span></div>
                <div class="row"><strong>تصدیق کا وقت</strong><span>{{ $order->confirmed_at?->format('d-m-Y h:i A') ?: '—' }}</span></div>
                <div class="row"><strong>تصدیق کرنے والا</strong><span>{{ $order->confirmedBy?->name ?: ($order->confirmedBy?->username ?: '—') }}</span></div>
                <div class="row"><strong>وصولی</strong><span>{{ $order->fulfillment_method === 'delivery' ? 'ڈیلیوری' : 'دکان سے وصولی' }}</span></div>
                <div class="row"><strong>ادائیگی</strong><span>{{ \App\Models\StorefrontOrder::paymentMethods()[$order->payment_method] ?? $order->payment_method }}</span></div>
            </div>
        </section>

        <table>
            <thead><tr><th>#</th><th>کپڑا</th><th>رنگ</th><th>مقدار</th><th>فی میٹر</th><th>کل</th></tr></thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr><td>{{ $loop->iteration }}</td><td>{{ $item->item_name }}</td><td>{{ $item->color }}</td><td>{{ number_format((float)$item->quantity, 2) }} میٹر</td><td class="num">Rs {{ number_format((float)$item->unit_price, 2) }}</td><td class="num">Rs {{ number_format((float)$item->line_total, 2) }}</td></tr>
                @endforeach
            </tbody>
        </table>
        <div class="totals box">
            <div class="row"><strong>آرڈر کل</strong><span class="num">Rs {{ number_format((float)$order->subtotal, 2) }}</span></div>
            <div class="row"><strong>وصول شدہ</strong><span class="num">Rs {{ number_format((float)$order->paid_amount, 2) }}</span></div>
            <div class="row"><strong>ڈسپیچ پر واجب</strong><span class="num">Rs {{ number_format((float)$order->balance_amount, 2) }}</span></div>
        </div>
        @if($order->customer_note)<div class="note"><strong>گاہک کا نوٹ:</strong><br>{{ $order->customer_note }}</div>@endif
        <div class="signatures"><div class="signature">پیک کرنے والے کے دستخط</div><div class="signature">ڈسپیچ / کورئیر کے دستخط</div></div>
        <footer class="footer">یہ ڈسپیچ شیٹ تصدیق شدہ آن لائن آرڈر کے لیے TMS سے تیار کی گئی ہے۔</footer>
    </main>
</body>
</html>
