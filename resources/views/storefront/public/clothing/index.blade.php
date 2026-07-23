<!doctype html>
<html lang="ur" dir="rtl">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>کپڑوں کی فہرست — {{ $storefront->display_name }}</title>
    <style>
        @font-face{font-family:Nastaliq;src:url('{{ asset('assets/fonts/noto-nastaliq-urdu/NotoNastaliqUrdu-VariableFont_wght.woff2') }}') format('woff2');font-display:swap}
        *{box-sizing:border-box}body{margin:0;background:#f4f7f5;color:#17382e;font-family:Nastaliq,Tahoma,sans-serif}.shell{width:min(1160px,calc(100% - 28px));margin:auto}
        .nav{background:#fff;border-bottom:1px solid #dfe9e4}.nav .shell{min-height:70px;display:flex;align-items:center;justify-content:space-between;gap:14px}.nav a{text-decoration:none;color:#145d45}.back{border:1px solid #bdd2c9;border-radius:10px;padding:7px 13px}
        .hero{background:linear-gradient(135deg,#0b4937,#17805f);color:#fff;padding:55px 0}.hero h1{font-size:clamp(2rem,5vw,3.6rem);margin:0}.hero p{color:#d9eee6}
        .filters{background:#fff;border:1px solid #dfe9e4;border-radius:18px;padding:18px;margin:-26px auto 30px;position:relative;box-shadow:0 12px 28px rgba(14,67,51,.08)}.filter-grid{display:grid;grid-template-columns:2fr 1fr auto;gap:12px}.control{width:100%;border:1px solid #cad9d3;border-radius:10px;padding:11px 12px;font:inherit;background:#fff}.btn{border:0;border-radius:10px;padding:10px 20px;font:inherit;cursor:pointer;background:#126b4f;color:#fff}
        .grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px;padding-bottom:55px}.card{background:#fff;border:1px solid #dfe8e4;border-radius:20px;overflow:hidden;box-shadow:0 10px 27px rgba(26,68,54,.07)}.photo{height:235px;background:linear-gradient(135deg,#e2eee9,#f2f7f4);display:grid;place-items:center;color:#759086;font-size:3rem}.photo img{width:100%;height:100%;object-fit:cover}.body{padding:19px}.body h2{margin:0;font-size:1.35rem}.meta{color:#667c74;font-size:.9rem}.price{font-size:1.15rem;font-weight:800;margin:10px 0}.colors{display:flex;flex-wrap:wrap;gap:6px}.pill{border-radius:999px;background:#edf4f1;padding:4px 9px;font-size:.82rem}.status{color:#1a704c}.out{color:#a23c3c}.view{display:block;text-align:center;background:#123f33;color:#fff;text-decoration:none;border-radius:10px;padding:9px;margin-top:14px}.empty{grid-column:1/-1;text-align:center;background:#fff;border-radius:18px;padding:50px}
        @media(max-width:850px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:600px){.filter-grid,.grid{grid-template-columns:1fr}.hero{padding:38px 0}.nav .shell{min-height:62px}.photo{height:220px}}
    </style>
</head>
<body>
<nav class="nav"><div class="shell"><strong>{{ $storefront->display_name }}</strong><div style="display:flex;gap:8px"><a class="back" href="{{ route('storefront.cart.show',$storefront) }}">میری ٹوکری</a><a class="back" href="{{ route('storefront.show',$storefront) }}">دکان کا تعارف</a></div></div></nav>
<header class="hero"><div class="shell"><h1>کپڑوں کی فہرست</h1><p>دستیاب رنگ، قیمت اور محفوظ کی جا سکنے والی موجودہ مقدار دیکھیں۔</p></div></header>
<main class="shell">
    <form class="filters" method="GET"><div class="filter-grid">
        <input class="control" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="برانڈ، قسم یا نام تلاش کریں">
        <select class="control" name="color"><option value="">تمام رنگ</option>@foreach($colors as $color)<option value="{{ $color }}" @selected(($filters['color']??'')===$color)>{{ $color }}</option>@endforeach</select>
        <button class="btn">تلاش کریں</button>
    </div></form>
    <div class="grid">
        @forelse($listings as $listing)
            @php($image=$listing->cloth->images->first(fn($item)=>$item->image_url))
            @php($available=(float)$listing->cloth->colors->sum(fn($color)=>$color->reservableLength()))
            <article class="card">
                <div class="photo">@if($image)<img src="{{ $image->image_url }}" alt="{{ $listing->display_name }}">@else<span>🧵</span>@endif</div>
                <div class="body">
                    @if($listing->is_featured)<div class="meta">منتخب کپڑا</div>@endif
                    <h2>{{ $listing->display_name }}</h2>
                    <div class="meta">{{ $listing->cloth->brand->name ?? 'بغیر برانڈ' }} · {{ $listing->cloth->type->name ?? 'کپڑا' }}</div>
                    <div class="price">Rs {{ number_format((float)($listing->cloth->sale_price ?: $listing->cloth->price),2) }} فی میٹر</div>
                    <div class="{{ $available>0?'status':'out' }}">{{ $available>0 ? number_format($available,2).' میٹر دستیاب' : 'فی الحال اسٹاک ختم' }}</div>
                    <div class="colors mt-2">@foreach($listing->cloth->colors as $color)<span class="pill">{{ $color->color }} — {{ number_format($color->reservableLength(),2) }}m</span>@endforeach</div>
                    <a class="view" href="{{ route('storefront.clothing.show',[$storefront,$listing]) }}">تفصیل دیکھیں</a>
                </div>
            </article>
        @empty
            <div class="empty"><h2>کوئی کپڑا نہیں ملا</h2><p>تلاش تبدیل کریں یا کچھ دیر بعد دوبارہ دیکھیں۔</p></div>
        @endforelse
    </div>
    @if($listings->hasPages())<div style="padding-bottom:40px">{{ $listings->links() }}</div>@endif
</main>
</body>
</html>
