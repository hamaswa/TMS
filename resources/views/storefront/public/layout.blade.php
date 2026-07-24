<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
<head>
    @php
        $seoTitle = trim($__env->yieldContent('title', __('storefront.common.marketplace')));
        $seoDescription = \App\Support\StorefrontSeo::description(
            trim($__env->yieldContent('meta_description')),
            __('storefront.marketplace.hero_text')
        );
        $seoCanonical = trim($__env->yieldContent('canonical_url', url()->current()));
        $seoType = trim($__env->yieldContent('meta_type', 'website'));
        $seoRobots = trim($__env->yieldContent('robots', 'index,follow'));
        $seoImageValue = trim($__env->yieldContent('meta_image'));
        $seoImage = \App\Support\StorefrontSeo::absoluteUrl(
            $seoImageValue ?: asset('assets/images/public-layout/public-banner.jpg')
        );
        $seoImageAlt = trim($__env->yieldContent('meta_image_alt', $seoTitle));
        $seoLocale = app()->getLocale() === 'ur' ? 'ur_PK' : 'en_PK';
        $seoAlternateLocale = app()->getLocale() === 'ur' ? 'en_PK' : 'ur_PK';
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="{{ $seoRobots }}">
    <link rel="canonical" href="{{ $seoCanonical }}">
    <meta property="og:site_name" content="{{ __('storefront.common.marketplace') }}">
    <meta property="og:type" content="{{ $seoType }}">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $seoCanonical }}">
    <meta property="og:locale" content="{{ $seoLocale }}">
    <meta property="og:locale:alternate" content="{{ $seoAlternateLocale }}">
    @if($seoImage)<meta property="og:image" content="{{ $seoImage }}"><meta property="og:image:alt" content="{{ $seoImageAlt }}">@endif
    <meta name="twitter:card" content="{{ $seoImage ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    @if($seoImage)<meta name="twitter:image" content="{{ $seoImage }}">@endif
    <meta name="theme-color" content="#0b4937">
    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}">
    @stack('structured_data')
    @stack('head')
    <style>
        @font-face{font-family:Nastaliq;src:url('{{ asset('assets/fonts/noto-nastaliq-urdu/NotoNastaliqUrdu-VariableFont_wght.woff2') }}') format('woff2');font-display:swap}
        :root{--ink:#17382e;--muted:#687d75;--primary:#126b4f;--primary-dark:#0b4937;--line:#dfe8e4;--paper:#fff;--bg:#f4f7f5;--danger:#923b3b}
        *{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--ink);font-family:{{ app()->getLocale() === 'ur' ? 'Nastaliq,"Noto Sans Arabic",Tahoma,sans-serif' : 'Inter,Arial,sans-serif' }};line-height:1.7}.shell{width:min(1120px,calc(100% - 28px));margin:auto}
        .nav{background:#fff;border-bottom:1px solid var(--line);position:relative;z-index:2}.nav .shell{min-height:70px;display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap}.nav-brand{min-height:44px;display:inline-flex;align-items:center;font-weight:900;color:var(--ink);text-decoration:none}.nav-actions{display:flex;align-items:center;gap:9px;flex-wrap:wrap}.nav-link{min-height:44px;display:inline-flex;align-items:center;color:var(--primary);text-decoration:none;border:1px solid #bdd2c9;border-radius:10px;padding:7px 12px}
        .hero{background:linear-gradient(135deg,var(--primary-dark),#17805f);color:#fff;padding:48px 0}.hero h1{font-size:clamp(2rem,5vw,3.7rem);line-height:1.35;margin:0}.hero p{max-width:760px;color:#dcefe8}.section{padding:38px 0}.grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}.two-col{display:grid;grid-template-columns:1.35fr .85fr;gap:22px}.card{background:var(--paper);border:1px solid var(--line);border-radius:18px;padding:20px;box-shadow:0 9px 27px rgba(22,69,53,.07)}.muted{color:var(--muted)}.pill{display:inline-block;background:#edf4f1;border-radius:999px;padding:4px 10px}.featured{color:var(--primary);font-weight:900}.btn{min-height:44px;display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:10px;background:var(--primary);color:#fff;padding:9px 16px;font:inherit;text-decoration:none;cursor:pointer;text-align:center}.btn-danger{background:var(--danger)}.control{width:100%;min-height:44px;border:1px solid #c9d8d1;border-radius:10px;padding:9px 11px;font:inherit;background:#fff;color:var(--ink)}.form-group{margin-bottom:13px}.form-group label{display:block;font-weight:800;margin-bottom:4px}.notice,.success,.errors{border-radius:12px;padding:12px;margin-bottom:14px}.notice{background:#e5f2ed}.success{background:#dcf3e6;color:#175e38}.errors{background:#f7e0e0;color:#8f3030}.row{display:flex;justify-content:space-between;gap:15px;padding:10px 0;border-bottom:1px solid #e4ece8}.row:last-child{border:0}.empty{text-align:center;padding:45px 20px}.price{font-weight:900}.money{white-space:nowrap;unicode-bidi:isolate}.footer{background:#082d22;color:#bdd7cd;padding:25px 0;text-align:center}.hp{position:fixed;top:-10000px;width:1px;height:1px;overflow:hidden}
        @media(max-width:850px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}.two-col{grid-template-columns:1fr}}@media(max-width:600px){.grid{grid-template-columns:1fr}.nav .shell{padding:10px 0}.hero{padding:35px 0}.row{display:block}}
        @stack('styles')
    </style>
</head>
<body>
@yield('body')
@stack('scripts')
</body>
</html>
