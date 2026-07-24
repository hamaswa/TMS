<!doctype html>
<html lang="ur" dir="rtl">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>آرڈر {{ $order->reference }} — {{ $storefront->display_name }}</title>
    <style>
        @font-face{font-family:Nastaliq;src:url('{{ asset('assets/fonts/noto-nastaliq-urdu/NotoNastaliqUrdu-VariableFont_wght.woff2') }}') format('woff2');font-display:swap}
        *{box-sizing:border-box}body{margin:0;background:#f3f7f5;color:#19382e;font-family:Nastaliq,Tahoma,sans-serif}.shell{width:min(900px,calc(100% - 28px));margin:auto}.nav{background:#fff;border-bottom:1px solid #dce7e2}.nav .shell{min-height:68px;display:flex;justify-content:space-between;align-items:center;gap:12px}.nav a{color:#156046;text-decoration:none;border:1px solid #bfd2ca;border-radius:10px;padding:7px 13px}.hero{background:linear-gradient(135deg,#0c4c38,#197b5c);color:#fff;padding:38px 0}.hero h1{margin:0;font-size:clamp(1.8rem,5vw,3rem)}.section{padding:30px 0}.card{background:#fff;border:1px solid #dfe8e4;border-radius:18px;padding:20px;margin-bottom:16px;box-shadow:0 9px 27px rgba(22,69,53,.07)}.success,.notice,.errors{border-radius:12px;padding:12px;margin-bottom:14px}.success,.notice{background:#e2f4ea;color:#175e38}.errors{background:#f7e0e0;color:#8f3030}.row{display:flex;justify-content:space-between;gap:15px;padding:10px 0;border-bottom:1px solid #e4ece8}.row:last-child{border:0}.muted{color:#697d75}.total{font-size:1.25rem;font-weight:900}.control{width:100%;border:1px solid #c9d8d1;border-radius:9px;padding:9px;font:inherit}.btn{width:100%;border:0;border-radius:9px;background:#146b4f;color:#fff;padding:9px 13px;font:inherit;cursor:pointer}.status{display:inline-block;background:#dcefe7;border-radius:999px;padding:5px 12px}@media(max-width:520px){.row{display:block}.nav .shell{align-items:flex-start;padding:10px 0;flex-direction:column}}
    </style>
</head>
<body>
<nav class="nav"><div class="shell"><strong>{{ $storefront->display_name }}</strong><a href="{{ route('storefront.clothing.index',$storefront) }}">دکان پر واپس جائیں</a></div></nav>
<header class="hero"><div class="shell"><div>آن لائن آرڈر</div><h1 dir="ltr" style="text-align:right">{{ $order->reference }}</h1></div></header>
<main class="section"><div class="shell">
    @if(session('success'))<div class="success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="errors">{{ $errors->first() }}</div>@endif
    @if(!$authorized)
        <section class="card"><h2>آرڈر کی محفوظ تفصیل</h2><p class="muted">اپنا فون نمبر اور 6 ہندسوں کا پن درج کر کے آرڈر دیکھیں۔</p>
            <form method="POST" action="{{ route('storefront.orders.authenticate',[$storefront,$order->reference]) }}">@csrf
                <p><label for="order_phone">فون نمبر</label><input id="order_phone" name="phone" dir="ltr" class="control" required maxlength="50"></p>
                <p><label for="order_pin">6 ہندسوں کا پن</label><input id="order_pin" name="pin" dir="ltr" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" class="control" required></p>
                <button class="btn">آرڈر دیکھیں</button>
            </form>
        </section>
    @else
        @php
            $statusLabels=['pending'=>'زیرِ انتظار','complete'=>'مکمل','cancelled'=>'منسوخ'];
            $methodLabels=['pickup'=>'دکان سے وصولی','delivery'=>'گھر تک فراہمی'];
            $paymentLabels=\App\Models\StorefrontOrder::paymentMethods();
        @endphp
        <section class="card">
            <div class="row"><strong>حالت</strong><span class="status">{{ $statusLabels[$order->status] ?? $order->status }}</span></div>
            <div class="row"><strong>گاہک</strong><span>{{ $order->customer->name }}</span></div>
            <div class="row"><strong>آرڈر کی تاریخ</strong><span>{{ $order->placed_at->format('d-m-Y h:i A') }}</span></div>
            <div class="row"><strong>وصولی</strong><span>{{ $methodLabels[$order->fulfillment_method] ?? $order->fulfillment_method }}</span></div>
            <div class="row"><strong>ادائیگی کا انتخاب</strong><span>{{ $paymentLabels[$order->payment_method] ?? $order->payment_method }}</span></div>
            @if($order->payment_method === \App\Models\StorefrontOrder::PAYMENT_EASYPAISA)
                <div class="row"><strong>ایزی پیسہ حوالہ</strong><span dir="ltr">{{ $order->payment_reference }}</span></div>
                <div class="notice">ایزی پیسہ کی تصدیق دکان دستی طور پر کرے گی۔ اس وقت تک رقم بقایا رہے گی۔</div>
            @endif
            @if($order->delivery_address)<div class="row"><strong>فراہمی کا پتہ</strong><span>{{ $order->delivery_address }}</span></div>@endif
        </section>
        <section class="card"><h2>کپڑے کی تفصیل</h2>
            @foreach($order->items as $item)
                <div class="row"><div><strong>{{ $item->item_name }}</strong><div class="muted">{{ $item->color }} · {{ number_format($item->quantity,2) }} میٹر × Rs {{ number_format($item->unit_price,2) }}</div></div><strong>Rs {{ number_format($item->line_total,2) }}</strong></div>
            @endforeach
            <div class="row total"><span>کل رقم</span><span>Rs {{ number_format($order->subtotal,2) }}</span></div>
        </section>
        <div class="notice">یہ رقم گاہک کے مشترکہ بقایا میں کپڑے کی فروخت کے طور پر شامل ہے۔ دکان میں درج کوئی بھی عمومی ادائیگی مجموعی بقایا کم کرے گی؛ اسے صرف اس آرڈر کے ساتھ الگ مختص نہیں کیا جائے گا۔</div>
        @if($order->customer_note)<section class="card"><strong>آپ کا نوٹ</strong><p>{{ $order->customer_note }}</p></section>@endif
    @endif
</div></main>
</body>
</html>
