<!doctype html>
<html lang="ur" dir="rtl">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $listing->display_name }} — {{ $storefront->display_name }}</title>
    <style>
        @font-face{font-family:Nastaliq;src:url('{{ asset('assets/fonts/noto-nastaliq-urdu/NotoNastaliqUrdu-VariableFont_wght.woff2') }}') format('woff2');font-display:swap}
        *{box-sizing:border-box}body{margin:0;background:#f4f7f5;color:#17382e;font-family:Nastaliq,Tahoma,sans-serif}.shell{width:min(1080px,calc(100% - 28px));margin:auto}.nav{background:#fff;border-bottom:1px solid #dde7e2}.nav .shell{min-height:70px;display:flex;align-items:center;justify-content:space-between}.nav a{color:#145d45;text-decoration:none;border:1px solid #bdd2c9;border-radius:10px;padding:7px 13px}
        .product{display:grid;grid-template-columns:1.05fr 1fr;gap:38px;padding:55px 0}.gallery{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.photo{min-height:250px;background:#e1ede8;border-radius:18px;overflow:hidden;display:grid;place-items:center;font-size:4rem}.photo:first-child{grid-column:1/-1;min-height:390px}.photo img{width:100%;height:100%;object-fit:cover}.label{color:#187151;font-weight:800}.details h1{font-size:clamp(2rem,5vw,3.5rem);line-height:1.35;margin:8px 0}.muted{color:#667b73}.price{font-size:1.5rem;font-weight:900;margin:20px 0}.colors{display:grid;gap:9px;margin-top:18px}.color{display:flex;justify-content:space-between;background:#fff;border:1px solid #dfe8e4;border-radius:12px;padding:10px 14px}.available{color:#1b744f}.out{color:#a23d3d}.notice{background:#e7f3ee;border-radius:14px;padding:14px;margin-top:22px}.contact{display:inline-block;background:#126b4f;color:#fff;text-decoration:none;border-radius:11px;padding:10px 18px;margin-top:15px}.cart-form{background:#fff;border:1px solid #dce7e2;border-radius:16px;padding:17px;margin-top:20px}.cart-grid{display:grid;grid-template-columns:1.3fr .7fr auto;gap:10px}.control{width:100%;border:1px solid #c8d7d0;border-radius:10px;padding:10px;font:inherit}.cart-btn{border:0;border-radius:10px;background:#126b4f;color:#fff;padding:10px 16px;font:inherit}
        @media(max-width:760px){.product{grid-template-columns:1fr;padding:28px 0}.photo:first-child{min-height:300px}.nav .shell{min-height:62px}.cart-grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
<nav class="nav"><div class="shell"><strong>{{ $storefront->display_name }}</strong><div style="display:flex;gap:8px"><a href="{{ route('storefront.cart.show',$storefront) }}">میری ٹوکری</a><a href="{{ route('storefront.clothing.index',$storefront) }}">تمام کپڑے</a></div></div></nav>
<main class="shell product">
    <div class="gallery">
        @forelse($listing->cloth->images->filter(fn($item)=>$item->image_url) as $image)<div class="photo"><img src="{{ $image->image_url }}" alt="{{ $listing->display_name }} — {{ $image->image_color }}"></div>@empty<div class="photo">🧵</div>@endforelse
    </div>
    <section class="details">
        @if($listing->is_featured)<div class="label">منتخب کپڑا</div>@endif
        <h1>{{ $listing->display_name }}</h1>
        <div class="muted">{{ $listing->cloth->brand->name ?? 'بغیر برانڈ' }} · {{ $listing->cloth->type->name ?? 'کپڑا' }}</div>
        <div class="price">Rs {{ number_format((float)($listing->cloth->sale_price ?: $listing->cloth->price),2) }} فی میٹر</div>
        @if($listing->description)<p>{{ $listing->description }}</p>@endif
        <h2>رنگ اور دستیابی</h2>
        <div class="colors">@foreach($listing->cloth->colors as $color)@php($available=$color->reservableLength())<div class="color"><strong>{{ $color->color }}</strong><span class="{{ $available>0?'available':'out' }}">{{ $available>0 ? number_format($available,2).' میٹر' : 'اسٹاک ختم' }}</span></div>@endforeach</div>
        <div class="cart-form">
            @if($errors->any())<div class="out">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('storefront.cart.store',[$storefront,$listing]) }}">@csrf<div class="cart-grid">
                <select name="cloth_color_id" class="control" required><option value="">رنگ منتخب کریں</option>@foreach($listing->cloth->colors as $color)@php($available=$color->reservableLength())<option value="{{ $color->id }}" @disabled($available<=0)>{{ $color->color }} — {{ number_format($available,2) }} میٹر</option>@endforeach</select>
                <input type="number" name="quantity" min="0.25" max="1000" step="0.25" value="1" class="control" aria-label="مقدار میٹر میں" required>
                <button class="cart-btn">ٹوکری میں محفوظ کریں</button>
            </div></form>
            <small>منتخب مقدار 30 منٹ محفوظ رہے گی۔ ابھی کوئی ادائیگی یا حتمی آرڈر نہیں بنے گا۔</small>
        </div>
        <div class="notice">قیمت اور دستیابی تازہ انوینٹری اور فعال ٹوکریوں کی محفوظ مقدار سے دکھائی جا رہی ہے۔</div>
        @if($storefront->public_phone)<a class="contact" href="tel:{{ preg_replace('/[^0-9+]/','',$storefront->public_phone) }}">دکان سے رابطہ کریں</a>@endif
    </section>
</main>
</body>
</html>
