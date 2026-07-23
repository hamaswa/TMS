<!doctype html>
<html lang="ur" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ٹی ایم ایس بازار — مقامی کپڑے اور ٹیلرنگ</title>
    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}">
    <style>
        @font-face{font-family:Nastaliq;src:url('{{ asset('assets/fonts/noto-nastaliq-urdu/NotoNastaliqUrdu-VariableFont_wght.woff2') }}') format('woff2');font-display:swap}
        *{box-sizing:border-box}body{margin:0;background:#f5f7f6;color:#17332a;font-family:Nastaliq,"Noto Sans Arabic",Tahoma,sans-serif}.shell{width:min(1160px,calc(100% - 30px));margin:auto}
        .nav{background:#0b3d2e;color:#fff}.nav .shell{min-height:70px;display:flex;align-items:center;justify-content:space-between}.brand{font-size:1.35rem;font-weight:800}.nav a{color:#fff;text-decoration:none}
        .hero{padding:72px 0 58px;background:radial-gradient(circle at 15% 20%,#2ca679 0,transparent 28%),linear-gradient(135deg,#0b3d2e,#126f50);color:#fff}.hero h1{font-size:clamp(2rem,5vw,4rem);line-height:1.45;margin:0 0 10px}.hero p{font-size:1.05rem;max-width:670px;color:#d9f2e8}
        .section{padding:45px 0}.section-head{display:flex;justify-content:space-between;align-items:end;gap:15px;margin-bottom:22px}.section h2{margin:0;font-size:1.75rem}.muted{color:#71837d}
        .grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px}.shop{background:#fff;border:1px solid #e0e8e4;border-radius:20px;overflow:hidden;box-shadow:0 12px 32px rgba(15,54,42,.07);display:flex;flex-direction:column}.cover{height:170px;background:linear-gradient(135deg,#d9efe6,#9ed7c0);background-size:cover;background-position:center}.shop-body{padding:20px;display:flex;flex-direction:column;flex:1}.shop h3{margin:0 0 5px;font-size:1.35rem}.tags{display:flex;flex-wrap:wrap;gap:7px;margin:12px 0}.tag{background:#e9f6f0;color:#126f50;border-radius:999px;padding:5px 10px;font-size:.82rem}.open{margin-top:auto;display:inline-flex;justify-content:center;background:#126f50;color:#fff;text-decoration:none;border-radius:12px;padding:10px 14px;font-weight:700}
        .empty{background:#fff;border:1px dashed #aabdb5;border-radius:20px;padding:42px;text-align:center}.pagination{margin-top:25px}.footer{background:#082d22;color:#bdd7cd;padding:30px 0;text-align:center}
        @media(max-width:900px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:600px){.grid{grid-template-columns:1fr}.hero{padding:48px 0}.section-head{align-items:start;flex-direction:column}}
    </style>
</head>
<body>
<nav class="nav"><div class="shell"><a class="brand" href="{{ route('storefront.index') }}">TMS بازار</a><a href="{{ route('login') }}">کاروباری لاگ اِن</a></div></nav>
<header class="hero"><div class="shell"><h1>مقامی کپڑا اور نفیس سلائی<br>ایک ہی جگہ</h1><p>قابلِ اعتماد کپڑے کی دکانیں اور ٹیلرنگ خدمات دیکھیں۔ اپنے شہر کے کاروبار سے براہِ راست رابطہ کریں۔</p></div></header>
<main class="section"><div class="shell">
    <div class="section-head"><div><h2>عوامی دکانیں</h2><div class="muted">صرف فعال اور شائع شدہ TMS کاروبار</div></div><div class="muted">{{ $storefronts->total() }} دکانیں</div></div>
    @if($storefronts->count())
        <div class="grid">
            @foreach($storefronts as $storefront)
            <article class="shop">
                <div class="cover" @if($storefront->cover_url) style="background-image:url('{{ $storefront->cover_url }}')" @endif></div>
                <div class="shop-body">
                    <h3>{{ $storefront->display_name }}</h3>
                    <div class="muted">{{ $storefront->city ?: 'پاکستان' }}</div>
                    <p>{{ $storefront->tagline ?: 'معیاری مصنوعات اور قابلِ اعتماد خدمت' }}</p>
                    <div class="tags">@if($storefront->show_clothing)<span class="tag">کپڑے کی دکان</span>@endif @if($storefront->show_tailoring)<span class="tag">ٹیلرنگ</span>@endif @if($storefront->delivery_enabled)<span class="tag">گھر تک فراہمی</span>@endif</div>
                    <a class="open" href="{{ route('storefront.show',$storefront) }}">دکان دیکھیں</a>
                </div>
            </article>
            @endforeach
        </div>
        <div class="pagination">{{ $storefronts->links() }}</div>
    @else
        <div class="empty"><h3>ابھی کوئی عوامی دکان شائع نہیں ہوئی</h3><p class="muted">نئی دکانیں شائع ہونے پر یہاں نظر آئیں گی۔</p></div>
    @endif
</div></main>
<footer class="footer"><div class="shell">TMS — مقامی کاروبار کا قابلِ اعتماد آن لائن پلیٹ فارم</div></footer>
</body>
</html>
