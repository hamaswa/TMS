<!doctype html>
<html lang="ur" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $storefront->display_name }} — TMS بازار</title>
    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}">
    <style>
        @font-face{font-family:Nastaliq;src:url('{{ asset('assets/fonts/noto-nastaliq-urdu/NotoNastaliqUrdu-VariableFont_wght.woff2') }}') format('woff2');font-display:swap}
        *{box-sizing:border-box}body{margin:0;background:#f7f8f7;color:#17332a;font-family:Nastaliq,"Noto Sans Arabic",Tahoma,sans-serif}.shell{width:min(1160px,calc(100% - 30px));margin:auto}
        .preview{background:#f9c74f;color:#3d3100;text-align:center;padding:9px;font-weight:800}.nav{background:#fff;border-bottom:1px solid #e2e8e5}.nav .shell{min-height:70px;display:flex;align-items:center;justify-content:space-between;gap:18px}.brand{display:flex;align-items:center;gap:10px;font-weight:800}.logo{width:44px;height:44px;border-radius:13px;object-fit:cover;background:#dcece5}.nav a{text-decoration:none;color:#174d3a}.market{padding:8px 13px;border:1px solid #b9cec5;border-radius:10px}
        .hero{min-height:430px;display:flex;align-items:end;background:linear-gradient(90deg,rgba(5,40,29,.93),rgba(5,40,29,.48)),linear-gradient(135deg,#137253,#69b99b);background-size:cover;background-position:center;color:#fff}.hero-inner{padding:70px 0}.hero h1{font-size:clamp(2.2rem,6vw,4.6rem);line-height:1.35;margin:0}.hero p{font-size:1.12rem;max-width:690px;color:#e2f4ed}.badges{display:flex;gap:8px;flex-wrap:wrap}.badge{background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.28);border-radius:999px;padding:7px 12px}
        .section{padding:52px 0}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:22px}.service{background:#fff;border:1px solid #e0e8e4;border-radius:22px;padding:26px;box-shadow:0 14px 35px rgba(19,63,48,.07)}.icon{width:54px;height:54px;border-radius:16px;background:#e3f4ec;display:grid;place-items:center;font-size:1.5rem}.service h2{margin:15px 0 5px}.service p{color:#657b73}.soon{display:inline-block;background:#edf2f0;color:#60736c;border-radius:999px;padding:6px 11px;font-size:.84rem}
        .about{background:#e9f4ef}.about-grid{display:grid;grid-template-columns:1.4fr .9fr;gap:30px}.contact{background:#fff;border-radius:20px;padding:24px}.contact dl{margin:0}.contact dt{font-weight:800;margin-top:12px}.contact dd{margin:3px 0;color:#5f746c}.contact a{color:#126f50}.footer{background:#082d22;color:#bdd7cd;padding:30px 0;text-align:center}
        @media(max-width:760px){.grid,.about-grid{grid-template-columns:1fr}.hero{min-height:360px}.hero-inner{padding:45px 0}.nav .shell{min-height:62px}}
    </style>
</head>
<body>
@if($preview)<div class="preview">یہ صرف پیش منظر ہے — عوامی اشاعت کی حالت: {{ $storefront->is_published ? 'شائع شدہ' : 'مسودہ' }}</div>@endif
<nav class="nav"><div class="shell"><div class="brand">@if($storefront->logo_url)<img class="logo" src="{{ $storefront->logo_url }}" alt="{{ $storefront->display_name }} لوگو">@else<span class="logo"></span>@endif <span>{{ $storefront->display_name }}</span></div><a class="market" href="{{ route('storefront.index') }}">تمام دکانیں</a></div></nav>
<header class="hero" @if($storefront->cover_url) style="background-image:linear-gradient(90deg,rgba(5,40,29,.93),rgba(5,40,29,.48)),url('{{ $storefront->cover_url }}')" @endif>
    <div class="shell hero-inner">
        <div class="badges"><span class="badge">TMS فعال کاروبار</span>@if($storefront->pickup_enabled)<span class="badge">دکان سے وصولی</span>@endif @if($storefront->delivery_enabled)<span class="badge">گھر تک فراہمی</span>@endif</div>
        <h1>{{ $storefront->display_name }}</h1>
        <p>{{ $storefront->tagline ?: 'معیاری مصنوعات، نفیس کام اور قابلِ اعتماد خدمت' }}</p>
    </div>
</header>
<main>
    <section class="section"><div class="shell">
        <div class="grid">
            @if($storefront->show_clothing)<article class="service"><div class="icon">🧵</div><h2>کپڑے کی دکان</h2><p>رنگ، برانڈ، کپڑے کی قسم، قیمت اور موجودہ دستیابی دیکھیں۔</p><a class="soon" style="text-decoration:none" href="{{ route('storefront.clothing.index',$storefront) }}">کپڑوں کی فہرست دیکھیں</a></article>@endif
            @if($storefront->show_tailoring)<article class="service"><div class="icon">✂️</div><h2>ٹیلرنگ خدمات</h2><p>سلائی کی خدمات، ابتدائی قیمت، متوقع وقت اور درخواست کی سہولت دیکھیں۔</p><a class="soon" style="text-decoration:none" href="{{ route('storefront.tailoring.index',$storefront) }}">ٹیلرنگ خدمات دیکھیں</a></article>@endif
        </div>
    </div></section>
    <section class="section about"><div class="shell about-grid">
        <div><h2>ہمارے بارے میں</h2><p>{{ $storefront->description ?: $storefront->tagline ?: 'اپنے علاقے کے گاہکوں کے لیے معیاری اور قابلِ اعتماد خدمت۔' }}</p></div>
        <aside class="contact"><h2>رابطہ</h2><dl>
            @if($storefront->public_phone)<dt>فون نمبر</dt><dd dir="ltr"><a href="tel:{{ preg_replace('/[^0-9+]/','',$storefront->public_phone) }}">{{ $storefront->public_phone }}</a></dd>@endif
            @if($storefront->public_email)<dt>ای میل</dt><dd dir="ltr"><a href="mailto:{{ $storefront->public_email }}">{{ $storefront->public_email }}</a></dd>@endif
            @if($storefront->address)<dt>پتہ</dt><dd>{{ $storefront->address }}</dd>@endif
            @if($storefront->city)<dt>شہر</dt><dd>{{ $storefront->city }}</dd>@endif
        </dl></aside>
    </div></section>
</main>
<footer class="footer"><div class="shell">{{ $storefront->display_name }} · TMS بازار</div></footer>
</body>
</html>
