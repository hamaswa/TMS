<!doctype html>
<html lang="ur" dir="rtl">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>میری ٹوکری — {{ $storefront->display_name }}</title>
    <style>
        @font-face{font-family:Nastaliq;src:url('{{ asset('assets/fonts/noto-nastaliq-urdu/NotoNastaliqUrdu-VariableFont_wght.woff2') }}') format('woff2');font-display:swap}
        *{box-sizing:border-box}body{margin:0;background:#f4f7f5;color:#19382e;font-family:Nastaliq,Tahoma,sans-serif}.shell{width:min(1050px,calc(100% - 28px));margin:auto}.nav{background:#fff;border-bottom:1px solid #dce7e2}.nav .shell{min-height:70px;display:flex;justify-content:space-between;align-items:center}.nav a{color:#156046;text-decoration:none;border:1px solid #bfd2ca;border-radius:10px;padding:7px 13px}.hero{background:linear-gradient(135deg,#0c4c38,#197b5c);color:#fff;padding:42px 0}.hero h1{font-size:clamp(2rem,5vw,3.4rem);margin:0}.section{padding:35px 0}.layout{display:grid;grid-template-columns:1.4fr .8fr;gap:22px}.card{background:#fff;border:1px solid #dfe8e4;border-radius:18px;padding:20px;box-shadow:0 9px 27px rgba(22,69,53,.07);margin-bottom:16px}.item{display:grid;grid-template-columns:1fr auto;gap:14px;border-bottom:1px solid #e5ece9;padding:15px 0}.item:last-child{border:0}.muted{color:#697d75}.price{font-weight:900}.controls{display:flex;gap:7px;align-items:center;flex-wrap:wrap}.control{border:1px solid #c9d8d1;border-radius:9px;padding:8px;font:inherit;max-width:130px}.btn{border:0;border-radius:9px;background:#146b4f;color:#fff;padding:8px 13px;font:inherit;cursor:pointer}.danger{background:#9b3d3d}.outline{background:#fff;color:#146b4f;border:1px solid #a9c8bc}.notice{background:#e5f2ed;border-radius:12px;padding:12px}.success{background:#dcf3e6;color:#175e38;border-radius:12px;padding:12px;margin-bottom:14px}.errors{background:#f7e0e0;color:#8f3030;border-radius:12px;padding:12px;margin-bottom:14px}.total{display:flex;justify-content:space-between;font-size:1.25rem;font-weight:900}.empty{text-align:center;padding:50px 20px}.linked{background:#e2f4ea;border-radius:13px;padding:14px}.form-group{margin-bottom:12px}.form-group label{display:block;font-weight:800}.full{width:100%;max-width:none}.continue{display:block;text-align:center;text-decoration:none;margin-top:12px}@media(max-width:760px){.layout{grid-template-columns:1fr}.item{grid-template-columns:1fr}.nav .shell{min-height:62px}}
    </style>
</head>
<body>
<nav class="nav"><div class="shell"><strong>{{ $storefront->display_name }}</strong><a href="{{ route('storefront.clothing.index',$storefront) }}">مزید کپڑے دیکھیں</a></div></nav>
<header class="hero"><div class="shell"><h1>میری ٹوکری</h1><p>کپڑے کی محفوظ مقدار، قیمت اور گاہک شناخت۔</p></div></header>
<main class="section"><div class="shell">
    @if(session('success'))<div class="success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="errors">{{ $errors->first() }}</div>@endif
    <div class="layout"><section class="card">
        @forelse($cart?->items ?? [] as $item)
        <article class="item"><div><h2>{{ $item->listing->display_name }}</h2><div class="muted">{{ $item->color->color }} · Rs {{ number_format($item->unit_price_snapshot,2) }} فی میٹر</div><div class="price">Rs {{ number_format($item->line_total,2) }}</div><small>محفوظ تا {{ $item->reserved_until->format('h:i A') }}</small></div><div class="controls">
            <form method="POST" action="{{ route('storefront.cart.update',[$storefront,$item->id]) }}">@csrf @method('PATCH')<input class="control" type="number" name="quantity" min="0.25" max="1000" step="0.25" value="{{ $item->quantity }}" aria-label="مقدار میٹر میں"><button class="btn">تازہ کریں</button></form>
            <form method="POST" action="{{ route('storefront.cart.destroy',[$storefront,$item->id]) }}">@csrf @method('DELETE')<button class="btn danger">نکالیں</button></form>
        </div></article>
        @empty<div class="empty"><h2>ٹوکری خالی ہے</h2><p class="muted">کپڑوں کی فہرست سے رنگ اور مقدار منتخب کریں۔</p><a class="btn continue" href="{{ route('storefront.clothing.index',$storefront) }}">کپڑے دیکھیں</a></div>@endforelse
        @if($cart && $cart->items->isNotEmpty())
            <div class="total"><span>کل تخمینی رقم</span><span>Rs {{ number_format($cart->items->sum(fn($item)=>$item->line_total),2) }}</span></div>
            <div class="notice" style="margin-top:14px">یہ مقدار آرڈر کی تصدیق تک محفوظ ہے۔ تصدیق کے وقت اسٹاک دوبارہ جانچ کر محفوظ طریقے سے کم ہوگا۔</div>
            @if($cart->customer)
                <form method="POST" action="{{ route('storefront.checkout.store',$storefront) }}" style="margin-top:20px">
                    @csrf
                    <h2>آرڈر کی تصدیق</h2>
                    <p class="muted">رقم ابھی وصول شدہ درج نہیں ہوگی۔ یہ آرڈر گاہک کے مشترکہ بقایا میں کپڑے کی فروخت کے طور پر شامل ہوگا، اور ادائیگی دکان اپنے معمول کے طریقے سے درج کرے گی۔</p>
                    <div class="form-group">
                        <label>وصولی کا طریقہ</label>
                        @if($storefront->pickup_enabled)
                            <label class="notice" style="display:block;margin:7px 0"><input type="radio" name="fulfillment_method" value="pickup" @checked(old('fulfillment_method','pickup')==='pickup')> دکان سے وصول کریں</label>
                        @endif
                        @if($storefront->delivery_enabled)
                            <label class="notice" style="display:block;margin:7px 0"><input type="radio" name="fulfillment_method" value="delivery" @checked(old('fulfillment_method')==='delivery')> گھر تک فراہمی</label>
                        @endif
                    </div>
                    @if($storefront->delivery_enabled)
                        <div class="form-group"><label for="delivery_address">فراہمی کا مکمل پتہ</label><textarea id="delivery_address" name="delivery_address" class="control full" rows="3" maxlength="1000">{{ old('delivery_address') }}</textarea></div>
                    @endif
                    <div class="form-group"><label for="customer_note">آرڈر کے متعلق نوٹ (اختیاری)</label><textarea id="customer_note" name="customer_note" class="control full" rows="3" maxlength="1000">{{ old('customer_note') }}</textarea></div>
                    @if($storefront->pickup_enabled || $storefront->delivery_enabled)
                        <button class="btn full" type="submit">آرڈر حتمی کریں</button>
                    @else
                        <div class="errors">دکان نے ابھی وصولی یا فراہمی کا طریقہ فعال نہیں کیا۔ دکان سے رابطہ کریں۔</div>
                    @endif
                </form>
            @else
                <div class="notice" style="margin-top:14px">آرڈر حتمی کرنے کے لیے پہلے فون اور پن سے اپنا گاہک ریکارڈ منسلک کریں۔</div>
            @endif
        @endif
    </section>
    <aside class="card"><h2>گاہک شناخت</h2>
        @if($cart?->customer)<div class="linked"><strong>{{ $cart->customer->name }}</strong><div dir="ltr" style="text-align:right">{{ $cart->customer->phone_number1 }}</div><p>موجودہ متحد گاہک ریکارڈ منسلک ہے۔</p><form method="POST" action="{{ route('storefront.cart.customer.unlink',$storefront) }}">@csrf @method('DELETE')<button class="btn outline">شناخت ہٹائیں</button></form></div>
        @elseif($cart && $cart->items->isNotEmpty())<p class="muted">اگر آپ اس دکان کے موجودہ گاہک ہیں تو فون اور 6 ہندسوں کا پن درج کریں۔</p><form method="POST" action="{{ route('storefront.cart.customer.link',$storefront) }}">@csrf<div class="form-group"><label for="cart_phone">فون نمبر</label><input id="cart_phone" name="phone" dir="ltr" class="control full" required maxlength="50"></div><div class="form-group"><label for="cart_pin">6 ہندسوں کا پن</label><input id="cart_pin" name="pin" dir="ltr" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" class="control full" required></div><button class="btn full">گاہک ریکارڈ منسلک کریں</button></form><p class="muted" style="margin-top:14px">نئے گاہک دکان سے رابطہ کر کے پن حاصل کر سکتے ہیں۔ پن کے بغیر ٹوکری بن سکتی ہے، مگر حتمی آرڈر نہیں ہوگا۔</p>
        @else<p class="muted">پہلے ٹوکری میں کپڑا شامل کریں۔</p>@endif
    </aside></div>
</div></main>
</body>
</html>
