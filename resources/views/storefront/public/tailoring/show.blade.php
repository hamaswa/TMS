<!doctype html>
<html lang="ur" dir="rtl">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $service->name }} — {{ $storefront->display_name }}</title>
    <style>
        @font-face{font-family:Nastaliq;src:url('{{ asset('assets/fonts/noto-nastaliq-urdu/NotoNastaliqUrdu-VariableFont_wght.woff2') }}') format('woff2');font-display:swap}
        *{box-sizing:border-box}body{margin:0;background:#f5f6f8;color:#243750;font-family:Nastaliq,Tahoma,sans-serif}.shell{width:min(920px,calc(100% - 28px));margin:auto}.nav{background:#fff;border-bottom:1px solid #e0e5eb}.nav .shell{min-height:70px;display:flex;align-items:center;justify-content:space-between}.nav a{color:#324f7c;text-decoration:none;border:1px solid #c5d0df;border-radius:10px;padding:7px 13px}.detail{padding:65px 0}.panel{background:#fff;border-radius:25px;padding:35px;box-shadow:0 14px 38px rgba(36,57,88,.09)}.label{color:#4d6e9d;font-weight:800}.panel h1{font-size:clamp(2.2rem,6vw,4rem);line-height:1.4;margin:8px 0}.meta{display:flex;flex-wrap:wrap;gap:10px;margin:22px 0}.pill{background:#edf1f6;border-radius:999px;padding:8px 14px}.notice{background:#e8edf5;border-radius:14px;padding:14px}.cta{display:inline-block;background:#314f7e;color:#fff;text-decoration:none;border-radius:11px;padding:10px 20px;margin-top:20px}@media(max-width:600px){.detail{padding:28px 0}.panel{padding:22px}.nav .shell{min-height:62px}}
    </style>
</head>
<body>
<nav class="nav"><div class="shell"><strong>{{ $storefront->display_name }}</strong><a href="{{ route('storefront.tailoring.index',$storefront) }}">تمام خدمات</a></div></nav>
<main class="detail"><div class="shell"><article class="panel">@if($service->is_featured)<div class="label">منتخب خدمت</div>@endif<h1>{{ $service->name }}</h1><div class="meta">@if($service->price_from!==null)<span class="pill">ابتدائی قیمت: Rs {{ number_format($service->price_from,2) }} · {{ $service->price_unit }}</span>@endif @if($service->estimated_days)<span class="pill">متوقع وقت: {{ $service->estimated_days }} دن</span>@endif</div><p>{{ $service->description ?: 'تفصیل اور حتمی قیمت کے لیے دکان سے رابطہ کریں۔' }}</p><div class="notice">یہ قیمت ابتدائی رہنمائی ہے۔ کپڑے، ڈیزائن، پیمائش اور کام کی تصدیق کے بعد حتمی قیمت اور تاریخ طے ہوگی۔</div>@if($storefront->inquiries_enabled)<a class="cta" href="{{ route('storefront.tailoring.index',[$storefront,'service'=>$service->id]).'#inquiry' }}">اس خدمت کے لیے درخواست بھیجیں</a>@elseif($storefront->public_phone)<a class="cta" href="tel:{{ preg_replace('/[^0-9+]/','',$storefront->public_phone) }}">دکان سے رابطہ کریں</a>@endif</article></div></main>
</body>
</html>
