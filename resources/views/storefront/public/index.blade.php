@extends('storefront.public.layout')
@section('title',
    app()->getLocale() === 'ur'
    ? 'BuyNStitch — ٹیلرنگ اور کپڑے کے کاروبار کا مکمل سافٹ ویئر'
    : 'BuyNStitch — Tailoring
    and Cloth Shop Management Software')
@section('meta_description',
    app()->getLocale() === 'ur'
    ? 'پاکستانی درزیوں اور کپڑے کی دکانوں کے لیے آرڈرز، پیمائش،
    اسٹاک، QR فروخت، خریداری اور ادائیگی کا مکمل سافٹ ویئر۔'
    : 'Complete tailoring and cloth shop software for orders,
    measurements, inventory, QR sales, purchases, payments and business reports in Pakistan.')
@section('canonical_url', route('storefront.index'))
@push('structured_data')
    @php($marketplaceItems = $topStorefronts->map(fn($storefront, $index) => ['@type' => 'ListItem', 'position' => $index + 1, 'name' => $storefront->display_name, 'url' => route('storefront.show', $storefront)])->values()->all())
    <script type="application/ld+json">{!! \App\Support\StorefrontSeo::json(\App\Support\StorefrontSeo::graph(\App\Support\StorefrontSeo::website(__('storefront.common.marketplace'),__('storefront.marketplace.hero_text'),route('storefront.index')),$marketplaceItems ? ['@type'=>'ItemList','@id'=>route('storefront.index').'#top-stores','name'=>__('storefront.marketplace.top_stores'),'itemListElement'=>$marketplaceItems] : [])) !!}</script>
@endpush
@push('styles')
.workspace-card{position:relative;overflow:hidden;padding:30px;border:1px solid
    var(--line);border-radius:22px;background:#fff;box-shadow:0 15px 38px
    rgba(11,36,71,.07)}
    .workspace-card.shop{display:block}
    :root{--navy:#0b2447;--blue:#2477ff;--green:#0e8f68;--dark-green:#087254;--gold:#e7b75a;--ink:#142b4a;--muted:#64748b;--line:#dce7f2}body{background:#fff;color:var(--ink)}.shell{width:min(1240px,calc(100%
    - 36px))}
    .tms-nav{position:sticky;top:0;z-index:50;background:rgba(255,255,255,.96);border-bottom:1px solid #e4edf5;box-shadow:0
    8px 30px rgba(11,36,71,.06);backdrop-filter:blur(14px)}.tms-nav
    .shell{min-height:76px;display:flex;align-items:center;justify-content:space-between;gap:22px}.tms-brand{display:flex;align-items:center;gap:11px;color:var(--navy);text-decoration:none;direction:ltr}.tms-mark{width:44px;height:44px;display:grid;place-items:center;border-radius:13px;background:linear-gradient(145deg,var(--green),#16b887);color:#fff;box-shadow:0
    9px 20px rgba(14,143,104,.2)}.tms-wordmark strong{display:block;font:900 1.35rem/1 Arial}.tms-wordmark
    small{display:block;margin-top:5px;color:var(--muted);font:600 .63rem/1
    Arial}.nav-center,.nav-actions{display:flex;align-items:center;gap:6px}.nav-center
    a{min-height:44px;display:inline-flex;align-items:center;padding:8px
    10px;color:#334a68;text-decoration:none;font-weight:800;font-size:.87rem}.tms-nav
    .locale-switch{color:#334a68}.nav-login,.nav-demo{min-height:44px;display:inline-flex;align-items:center;justify-content:center;border-radius:11px;padding:8px
    15px;text-decoration:none;font-weight:900}.nav-login{border:1px solid
    #a8c4b9;color:var(--dark-green)}.nav-demo{background:var(--blue);color:#fff;box-shadow:0 9px 22px rgba(36,119,255,.2)}
    .tms-hero{position:relative;overflow:hidden;padding:70px 0 56px;background:radial-gradient(circle at 80%
    18%,rgba(36,119,255,.11),transparent 31%),radial-gradient(circle at 9% 75%,rgba(14,143,104,.13),transparent
    27%),linear-gradient(180deg,#fff,#f5faff)}.tms-hero:after{content:"";position:absolute;inset:0;pointer-events:none;opacity:.22;background-image:linear-gradient(rgba(36,119,255,.08)
    1px,transparent 1px),linear-gradient(90deg,rgba(36,119,255,.08) 1px,transparent 1px);background-size:42px
    42px;mask-image:linear-gradient(to bottom,black,transparent
    72%)}.hero-grid{position:relative;z-index:1;display:grid;grid-template-columns:1.08fr
    .92fr;align-items:center;gap:58px}.hero-copy{direction:rtl;text-align:right;font-family:"Noto Nastaliq Urdu","Noto Sans Arabic",Tahoma,Arial,sans-serif}.eyebrow{display:inline-flex;align-items:center;gap:8px;margin-bottom:17px;color:var(--dark-green);font-weight:900;line-height:2}.eyebrow:before{content:"";width:30px;height:2px;background:var(--gold)}.hero-copy
    h1{margin:0;color:var(--navy);font-size:clamp(2.45rem,4.8vw,4.65rem);line-height:1.7;letter-spacing:0}.hero-copy
    h1 span{color:var(--green)}.hero-english{max-width:620px;margin:18px 0 0 auto;color:#334a68;font:700
    clamp(1rem,1.8vw,1.27rem)/1.6
    Arial;direction:ltr}.hero-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:28px}.hero-btn{min-height:50px;display:inline-flex;align-items:center;gap:10px;border-radius:12px;padding:10px
    21px;text-decoration:none;font-weight:900}.hero-btn.primary{background:linear-gradient(135deg,var(--green),#0ca678);color:#fff;box-shadow:0
    12px 25px rgba(14,143,104,.22)}.hero-btn.secondary{border:1px solid
    #abc8e6;background:#fff;color:var(--blue)}.hero-trust{display:flex;gap:18px;flex-wrap:wrap;margin-top:23px;color:#50657e;font-size:.82rem;font-weight:800}.hero-trust
    span{display:inline-flex;align-items:center;gap:7px}.hero-trust i{color:var(--green)}
    .product-scene{position:relative;direction:ltr}.dashboard-window{position:relative;overflow:hidden;border:1px solid
    #c8d9e9;border-radius:20px;background:#fff;box-shadow:0 35px 80px rgba(11,36,71,.22);transform:perspective(1200px)
    rotateY(2deg)}.window-top{height:40px;display:flex;align-items:center;gap:6px;padding:0 14px;border-bottom:1px solid
    #e5edf5;background:#f9fbfd}.window-top i{width:8px;height:8px;border-radius:50%;background:#f2bd56}.window-top
    i:first-child{background:#ee6b6e}.window-top
    i:last-child{background:#53c88c}.dashboard-body{display:grid;grid-template-columns:108px
    1fr;min-height:370px}.mock-sidebar{padding:18px
    11px;background:linear-gradient(180deg,var(--navy),#0d3159);color:#dbeaff}.mock-logo{margin-bottom:22px;color:#fff;font:900
    1rem Arial}.mock-link{display:flex;align-items:center;gap:8px;margin-bottom:5px;padding:9px;border-radius:8px;font:700
    .59rem
    Arial}.mock-link.active{background:var(--blue);color:#fff}.mock-main{padding:20px;background:#f6f9fc}.mock-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:15px}.mock-head
    strong{font:800 .94rem Arial}.mock-head small{display:block;color:#7a8ba0;font-size:.55rem}.mock-head
    span{width:28px;height:28px;display:grid;place-items:center;border-radius:50%;background:#e5f0ff;color:var(--blue);font:800
    .7rem Arial}.mock-metrics{display:grid;grid-template-columns:repeat(3,1fr);gap:9px}.mock-metric{padding:11px;border:1px
    solid #e4ecf4;border-radius:10px;background:#fff}.mock-metric small{display:block;color:#718096;font:600 .54rem
    Arial}.mock-metric b{display:block;margin-top:4px;color:var(--navy);font:900 .85rem
    Arial}.mock-lower{display:grid;grid-template-columns:1.05fr
    .95fr;gap:10px;margin-top:11px}.mock-panel{height:178px;padding:12px;border:1px solid
    #e4ecf4;border-radius:11px;background:#fff}.mock-panel-title{color:#425b78;font:800 .6rem
    Arial}.mock-chart{height:115px;display:flex;align-items:end;gap:8px;padding-top:15px;border-bottom:1px solid
    #e9eff5}.mock-chart span{flex:1;border-radius:5px 5px 0
    0;background:linear-gradient(#2ccf9b,var(--green))}.mock-order{display:flex;align-items:center;justify-content:space-between;margin-top:10px;padding:8px;border-radius:7px;background:#f4f8fc;font:700
    .52rem Arial}.mock-order em{padding:3px
    6px;border-radius:9px;background:#dff6ed;color:var(--dark-green);font-style:normal}.scene-chip{position:absolute;z-index:2;display:flex;align-items:center;gap:9px;padding:11px
    14px;border:1px solid #d5e2ee;border-radius:13px;background:rgba(255,255,255,.96);box-shadow:0 13px 30px
    rgba(11,36,71,.15);font:800 .72rem Arial}.scene-chip
    i{width:34px;height:34px;display:grid;place-items:center;border-radius:9px}.chip-qr{left:-26px;top:52px}.chip-qr
    i{background:#e5efff;color:var(--blue)}.chip-stock{right:-20px;top:22px}.chip-stock
    i{background:#e0f6ee;color:var(--green)}.chip-measure{left:25px;bottom:-25px}.chip-measure
    i{background:#fff1d6;color:#c18718}
    .proof-bar{border-block:1px solid
    #d8e7ee;background:#f0faf7}.proof-grid{display:grid;grid-template-columns:repeat(4,1fr)}.proof-item{display:flex;align-items:center;justify-content:center;gap:13px;padding:18px;border-inline-end:1px
    solid #d9e8e3}.proof-item:last-child{border:0}.proof-item i{color:var(--green);font-size:1.35rem}.proof-item
    strong{display:block;color:var(--navy);font:900 1.25rem Arial}.proof-item
    span{display:block;color:var(--muted);font-size:.72rem}
    .landing-section{padding:78px 0}.soft{background:#f6f9fc}.landing-head{max-width:760px;margin:0 auto
    35px;text-align:center}.kicker{color:var(--green);font:900 .82rem
    Arial;text-transform:uppercase;letter-spacing:.12em}.landing-head h2{margin:7px
    0;color:var(--navy);font-size:clamp(1.85rem,3vw,2.75rem);line-height:1.45}.landing-head
    p{margin:0;color:var(--muted)}.feature-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}.feature-card{padding:23px;border:1px
    solid var(--line);border-radius:17px;background:#fff;box-shadow:0 10px 30px
    rgba(11,36,71,.05);transition:.2s}.feature-card:hover{transform:translateY(-4px);box-shadow:0 18px 38px
    rgba(11,36,71,.1)}.feature-icon{width:48px;height:48px;display:grid;place-items:center;margin-bottom:17px;border-radius:13px;background:#e9f2ff;color:var(--blue);font-size:1.15rem}.feature-card:nth-child(2n)
    .feature-icon{background:#e4f7f0;color:var(--green)}.feature-card:nth-child(3n)
    .feature-icon{background:#fff3d9;color:#b57a0c}.feature-card
    h3{margin:0;color:var(--navy);font:800 1.05rem/1.5 Inter,Arial,sans-serif}.feature-card h3
    small{display:block;margin-top:7px;color:var(--green);font:800 .83rem/2 "Noto Nastaliq Urdu","Noto Sans Arabic",Tahoma,sans-serif}.feature-card p{margin:10px 0
    0;color:var(--muted);font-size:.9rem}
    .workspace-grid{display:grid;grid-template-columns:1fr
    1fr;gap:22px}.workspace-card{position:relative;overflow:hidden;padding:30px;border:1px solid
    var(--line);border-radius:22px;background:#fff;box-shadow:0 15px 38px
    rgba(11,36,71,.07)}.workspace-card:before{content:"";position:absolute;inset-inline-end:-60px;top:-70px;width:180px;height:180px;border-radius:50%;background:#e5f1ff}.workspace-card.shop:before{background:#ddf6ed}.workspace-card>*{position:relative}.workspace-label{display:inline-flex;align-items:center;gap:9px;padding:7px
    12px;border-radius:999px;background:#e9f2ff;color:var(--blue);font-weight:900}.workspace-card.shop
    .workspace-label{background:#e1f7ef;color:var(--dark-green)}.workspace-card h3{margin:18px 0
    5px;color:var(--navy);font-size:1.65rem}.workspace-card
    p{color:var(--muted)}.workspace-list{display:grid;grid-template-columns:1fr 1fr;gap:9px;margin:20px 0}
    .workspace-shop{position:relative;overflow:hidden;padding:30px;border:1px solid
    var(--line);border-radius:22px;background:#fff;box-shadow:0 15px 38px
    rgba(11,36,71,.07)}.workspace-shop>*{position:relative}.workspace-card:before{content:"";position:absolute;inset-inline-end:-60px;top:-70px;width:180px;height:180px;border-radius:50%;background:#e5f1ff}.workspace-card.shop:before{background:#ddf6ed}
    .workspace-label{display:inline-flex;align-items:center;gap:9px;padding:7px
    12px;border-radius:999px;background:#e9f2ff;color:var(--blue);font-weight:900}
    .workspace-list
    span{display:flex;align-items:center;gap:7px;color:#435a75;font-size:.86rem}.workspace-list
    i{color:var(--green)}.workspace-link{color:var(--blue);text-decoration:none;font-weight:900}
    .steps-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}.process-step{position:relative;padding:27px;text-align:center}.process-step:not(:last-child):after{content:"";position:absolute;top:34px;inset-inline-end:-20px;width:40px;border-top:2px
    dashed #b7cbe0}.step-no{width:50px;height:50px;display:grid;place-items:center;margin:0 auto
    15px;border-radius:14px;background:linear-gradient(145deg,var(--blue),#145ac8);color:#fff;font:900 1.1rem
    Arial;box-shadow:0 10px 22px rgba(36,119,255,.2)}.process-step h3{margin:0;color:var(--navy);font-family:Inter,Arial,sans-serif;line-height:1.5}.process-step h3
    small{display:block;margin-top:6px;color:var(--green);font:800 .78rem/2 "Noto Nastaliq Urdu","Noto Sans Arabic",Tahoma,sans-serif}.process-step p{color:var(--muted);font-family:Inter,Arial,sans-serif;font-size:.88rem;line-height:1.7}
    .marketplace{padding:76px
    0;background:#f5f9fc}.market-intro,.section-head{display:flex;align-items:end;justify-content:space-between;gap:25px;margin-bottom:25px}.market-intro
    h2,.section-head h2{margin:0;color:var(--navy);font-size:clamp(1.7rem,3vw,2.55rem)}.market-intro p{margin:5px 0
    0}.market-filters{margin-bottom:34px;padding:20px}.filter-grid{display:grid;grid-template-columns:1.5fr .8fr 1fr 1fr
    auto;gap:11px;align-items:end}.filter-field
    label{display:block;margin-bottom:5px;color:#425b78;font-size:.76rem;font-weight:900}.filter-actions{display:flex;gap:8px}.market-filters
    .btn{background:var(--blue)}.shop-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}.shop{position:relative;padding:0;overflow:hidden;display:flex;flex-direction:column}.cover{height:150px;background:linear-gradient(135deg,#dcecff,#dff7ee);background-size:cover;background-position:center}.cover-placeholder{height:100%;display:grid;place-items:center;color:var(--green);font-size:2.4rem}.shop-rank{position:absolute;top:13px;inset-inline-start:13px;display:grid;place-items:center;width:36px;height:36px;border-radius:10px;background:#fff;color:var(--blue);font:900
    .9rem Arial;box-shadow:0 5px 18px
    rgba(0,0,0,.12)}.shop-body{padding:19px;display:flex;flex-direction:column;flex:1}.shop-body
    h3{margin:0;color:var(--navy)}.shop-city{color:var(--muted);font-size:.83rem}.tags{display:flex;flex-wrap:wrap;gap:7px;margin:11px
    0}.shop .btn,.product
    .btn{margin-top:auto;background:var(--blue)}.product-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:17px}.product{padding:0;overflow:hidden}.product-image{position:relative;height:190px;display:grid;place-items:center;background:#e4f3ed;color:var(--green);font-size:2.5rem;text-decoration:none}.product-image
    img{width:100%;height:100%;object-fit:cover}.product-badge{position:absolute;top:12px;inset-inline-start:12px;padding:4px
    9px;border-radius:999px;background:#fff;color:var(--dark-green);font-size:.72rem;font-weight:900}.product-body{padding:17px}.product-body
    h3{margin:0;color:var(--navy);font-size:1rem}.product-shop{color:var(--muted);font-size:.8rem}.product-price{margin-top:10px;font-weight:900}.market-block{padding-top:48px}.business-cta{display:grid;grid-template-columns:1fr
    auto;align-items:center;gap:25px;padding:34px;border-radius:22px;background:linear-gradient(135deg,var(--navy),#125d78);color:#fff;box-shadow:0
    22px 50px rgba(11,36,71,.2)}.business-cta h2{margin:0;font-size:1.8rem}.business-cta p{margin:5px 0
    0;color:#dcecff}.business-cta
    .btn{background:#fff;color:var(--navy);font-weight:900}.faq-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:15px}.faq
    summary{cursor:pointer;color:var(--navy);font-weight:900}.tms-footer{background:var(--navy);color:#d7e5f3;padding:28px
    0}.footer-grid{display:flex;align-items:center;justify-content:space-between;gap:20px}.footer-brand{font:900 1.4rem
    Arial}.footer-brand i{color:#39d3a3}.footer-copy{font-size:.82rem;color:#9eb5ca}
    @media(max-width:1050px){.nav-center{display:none}.hero-grid{gap:30px}.dashboard-body{grid-template-columns:86px
    1fr}.filter-grid{grid-template-columns:repeat(2,1fr)}.filter-actions{grid-column:1/-1}.product-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:820px){.hero-grid{grid-template-columns:1fr}.hero-copy{text-align:center}.hero-english{margin-inline:auto}.hero-actions,.hero-trust{justify-content:center}.product-scene{width:min(650px,100%);margin:18px
    auto}.feature-grid,.proof-grid{grid-template-columns:repeat(2,1fr)}.workspace-grid{grid-template-columns:1fr}.shop-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:620px){.shell{width:min(100%
    - 24px,1240px)}.tms-nav .shell{padding:10px 0}.nav-actions .locale-switch,.nav-login,.tms-wordmark
    small{display:none}.nav-demo{padding:8px 11px}.tms-hero{padding:45px 0}.hero-copy
    h1{font-size:2.35rem}.dashboard-window{transform:none}.scene-chip{display:none}.dashboard-body{grid-template-columns:70px
    1fr;min-height:310px}.mock-main{padding:12px}.mock-lower{grid-template-columns:1fr}.mock-lower
    .mock-panel:last-child{display:none}.proof-grid,.feature-grid,.steps-grid,.filter-grid,.shop-grid,.product-grid,.faq-grid{grid-template-columns:1fr}.proof-item{border-inline-end:0;border-bottom:1px
    solid
    #d9e8e3}.workspace-list{grid-template-columns:1fr}.process-step:after{display:none}.landing-section,.marketplace{padding:55px
    0}.market-intro,.section-head,.footer-grid{align-items:flex-start;flex-direction:column}.business-cta{grid-template-columns:1fr}.filter-actions
    .btn{flex:1}}
    @include('storefront.public.partials.platform-header-styles')
@endpush
@section('body')
    @include('storefront.public.partials.platform-header')
    <header class="tms-hero">
        <div class="shell hero-grid">
            <div class="product-scene">
                <div class="scene-chip chip-qr"><i class="fas fa-qrcode"></i><span>Scan & Sell<br><small>QR
                            Billing</small></span></div>
                <div class="scene-chip chip-stock"><i class="fas fa-layer-group"></i><span>Cloth Inventory<br><small>320+
                            Items</small></span></div>
                <div class="scene-chip chip-measure"><i class="fas fa-ruler-combined"></i><span>Measurements<br><small>Save
                            & track</small></span></div>
                <div class="dashboard-window">
                    <div class="window-top"><i></i><i></i><i></i></div>
                    <div class="dashboard-body">
                        <aside class="mock-sidebar">
                            <div class="mock-logo"><i class="fas fa-cut"></i> BuyNStitch</div>
                            @foreach ([['fa-th-large', 'Dashboard'], ['fa-clipboard-list', 'Orders'], ['fa-users', 'Customers'], ['fa-ruler', 'Measurements'], ['fa-layer-group', 'Inventory'], ['fa-chart-bar', 'Reports']] as [$icon, $label])
                                <div @class(['mock-link', 'active' => $loop->first])><i class="fas {{ $icon }}"></i>
                                    {{ $label }}</div>
                            @endforeach
                        </aside>
                        <div class="mock-main">
                            <div class="mock-head"><strong>Good morning!<small>Here is your business
                                        today.</small></strong><span>A</span></div>
                            <div class="mock-metrics">
                                <div class="mock-metric"><small>Total Orders</small><b>48</b></div>
                                <div class="mock-metric"><small>Sales (PKR)</small><b>125,430</b></div>
                                <div class="mock-metric"><small>Customers</small><b>320</b></div>
                            </div>
                            <div class="mock-lower">
                                <div class="mock-panel">
                                    <div class="mock-panel-title">Sales overview</div>
                                    <div class="mock-chart">
                                        @foreach ([35, 52, 46, 72, 64, 92] as $height)
                                            <span style="height:{{ $height }}%"></span>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="mock-panel">
                                    <div class="mock-panel-title">Recent orders</div>
                                    @foreach ([['1042', 'In progress'], ['1041', 'Completed'], ['1040', 'Ready']] as [$number, $status])
                                        <div class="mock-order">
                                            <span>#BNS-{{ $number }}</span><em>{{ $status }}</em>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="hero-copy">
            <div class="eyebrow">پاکستان کے درزیوں اور کپڑے کے کاروبار کے لیے</div>
            <h1>درزی اور کپڑے کے کاروبار کو <span>آسان بنائیں</span></h1>
            <p class="hero-english">Run your tailoring shop, cloth inventory and sales from one smart platform. Find the right fabric. Book the right tailor.</p>
            <div class="hero-actions">
                @if (config('demo.enabled'))
                    <a class="hero-btn primary" href="{{ route('login', ['demo' => 1]) }}">مفت ڈیمو دیکھیں <i
                            class="fas fa-arrow-left"></i></a>
                @endif
                <a class="hero-btn secondary" href="{{ route('storefront.business.signup') }}">
                    کاروبار شروع کریں</a>
            </div>
            <div class="hero-trust"><span><i class="fas fa-check-circle"></i> آسان</span><span><i
                        class="fas fa-check-circle"></i> محفوظ</span><span><i class="fas fa-check-circle"></i> پاکستانی
                    کاروبار کے مطابق</span></div>
        </div>
        </div>
    </header>
    <section class="proof-bar">
        <div class="shell proof-grid">
            <div class="proof-item"><i class="fas fa-store"></i>
                <div><strong>2</strong><span>Business workspaces</span></div>
            </div>
            <div class="proof-item"><i class="fas fa-qrcode"></i>
                <div><strong>QR</strong><span>Stock & sales workflow</span></div>
            </div>
            <div class="proof-item"><i class="fas fa-language"></i>
                <div><strong>اردو</strong><span>Urdu + English</span></div>
            </div>
            <div class="proof-item"><i class="fas fa-cloud"></i>
                <div><strong>24/7</strong><span>Secure cloud access</span></div>
            </div>
        </div>
    </section>
    <main>
        <section class="landing-section" id="features">
            <div class="shell">
                <header class="landing-head"><span class="kicker">Everything you need</span>
                    <h2>آپ کے کاروبار کے لیے ایک مکمل نظام</h2>
                    <p>From measurements to inventory and payments, BuyNStitch keeps every part of your business connected.</p>
                </header>
                <div class="feature-grid">
                    @foreach ([['fa-clipboard-list', 'Tailoring Orders', 'سلائی کے آرڈرز', 'Create, track and deliver every order on time.'], ['fa-ruler-combined', 'Customer Measurements', 'گاہک کی پیمائش', 'Keep accurate measurement history for every customer.'], ['fa-layer-group', 'Cloth Inventory', 'کپڑے کا اسٹاک', 'Manage brands, fabric types, colors and live stock.'], ['fa-qrcode', 'QR Sales', 'کیو آر فروخت', 'Scan cloth labels and prepare a sale in seconds.'], ['fa-truck', 'Supplier Purchases', 'سپلائر خریداری', 'Receive complete bundles and update inventory quickly.'], ['fa-chart-line', 'Reports & Payments', 'رپورٹس اور ادائیگیاں', 'Understand sales, balances, expenses and growth.']] as [$icon, $en, $ur, $text])
                        <article class="feature-card"><span class="feature-icon"><i
                                    class="fas {{ $icon }}"></i></span>
                            <h3>{{ $en }}<small>{{ $ur }}</small></h3>
                            <p>{{ $text }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
        <section class="landing-section soft" id="workspaces">
            <div class="shell">
                <header class="landing-head"><span class="kicker">One system, two workspaces</span>
                    <h2>جس شعبے میں کام کریں، وہی اوزار سامنے پائیں</h2>
                    <p>Switch between tailoring operations and cloth-shop sales without changing systems.</p>
                </header>
                <div class="workspace-grid">
                    <article class="workspace-card"><span class="workspace-label"><i class="fas fa-cut"></i> Tailoring
                            Workspace</span>
                        <h3>ٹیلرنگ ورک اسپیس</h3>
                        <p>Manage customers, measurements, stitching stages, workers, delivery dates and receipts.</p>
                        <div class="workspace-list"><span><i class="fas fa-check"></i> Customer profiles</span><span><i
                                    class="fas fa-check"></i> Measurement history</span><span><i class="fas fa-check"></i>
                                Workshop stages</span><span><i class="fas fa-check"></i> Tailor assignments</span></div>
                        @if (config('demo.enabled'))
                            <a class="workspace-link" href="{{ route('login', ['demo' => 1]) }}">Explore tailoring demo <i
                                    class="fas fa-arrow-right"></i></a>
                        @endif
                    </article>
                    <article class="workspace-card"><span class="workspace-label"><i class="fas fa-store"></i>
                            Clothing & Sales Workspace</span>
                        <h3>دکان اور فروخت ورک اسپیس</h3>
                        <p>Control cloth stock, supplier purchases, QR labels, counter sales, payments and reports.</p>
                        <div class="workspace-list"><span><i class="fas fa-check"></i> Live inventory</span><span><i
                                    class="fas fa-check"></i> QR-assisted sales</span><span><i class="fas fa-check"></i>
                                Supplier records</span><span><i class="fas fa-check"></i> Sales analytics</span></div>
                        @if (config('demo.enabled'))
                            <a class="workspace-link" href="{{ route('login', ['demo' => 1]) }}">Explore sales demo <i
                                    class="fas fa-arrow-right"></i></a>
                        @endif
                    </article>
                </div>
            </div>
        </section>
        <section class="landing-section" id="how">
            <div class="shell">
                <header class="landing-head"><span class="kicker">Simple setup</span>
                    <h2>صرف تین مراحل میں کام شروع کریں</h2>
                    <p>Start small, add your existing data and grow with one reliable system.</p>
                </header>
                <div class="steps-grid">
                    @foreach ([['Sign up', 'اکاؤنٹ بنائیں', 'Create your account and choose the workspaces you need.'], ['Set up your shop', 'دکان تیار کریں', 'Add staff, customers, stock and business preferences.'], ['Start managing', 'کام شروع کریں', 'Handle daily orders, sales and reports from one dashboard.']] as [$en, $ur, $text])
                        <article class="process-step"><span class="step-no">{{ $loop->iteration }}</span>
                            <h3>{{ $en }}<small>{{ $ur }}</small></h3>
                            <p>{{ $text }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
        <section class="marketplace" id="marketplace">
            <div class="shell">
                <div class="market-intro">
                    <div><span class="kicker">BuyNStitch marketplace</span>
                        <h2>{{ __('storefront.marketplace.shops') }}</h2>
                        <p class="muted">{{ __('storefront.marketplace.active_only') }}</p>
                    </div><span
                        class="pill">{{ __('storefront.marketplace.shop_count', ['count' => $storefronts->total()]) }}</span>
                </div>
                <form class="card market-filters" method="GET">
                    <div class="filter-grid">
                        <div class="filter-field"><label
                                for="market_q">{{ __('storefront.marketplace.search_label') }}</label><input
                                id="market_q" class="control" name="q" value="{{ $filters['q'] ?? '' }}"
                                placeholder="{{ __('storefront.marketplace.search_placeholder') }}"></div>
                        <div class="filter-field"><label
                                for="market_city">{{ __('storefront.marketplace.city_label') }}</label><select
                                id="market_city" class="control" name="city">
                                <option value="">{{ __('storefront.marketplace.all_cities') }}</option>
                                @foreach ($cities as $city)
                                    <option value="{{ $city }}" @selected(($filters['city'] ?? '') === $city)>{{ $city }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="filter-field"><label
                                for="market_category">{{ __('storefront.marketplace.category_label') }}</label><select
                                id="market_category" class="control" name="category">
                                <option value="">{{ __('storefront.marketplace.all_categories') }}</option>
                                <option value="clothing" @selected(($filters['category'] ?? '') === 'clothing')>
                                    {{ __('storefront.common.clothing') }}</option>
                                <option value="tailoring" @selected(($filters['category'] ?? '') === 'tailoring')>
                                    {{ __('storefront.common.tailoring') }}</option>
                                <option value="both" @selected(($filters['category'] ?? '') === 'both')>
                                    {{ __('storefront.marketplace.both_categories') }}</option>
                            </select></div>
                        <div class="filter-field"><label
                                for="market_delivery">{{ __('storefront.marketplace.delivery_label') }}</label><select
                                id="market_delivery" class="control" name="delivery">
                                <option value="">{{ __('storefront.marketplace.any_delivery') }}</option>
                                <option value="1" @selected(($filters['delivery'] ?? '') === '1')>
                                    {{ __('storefront.marketplace.delivery_available') }}</option>
                            </select></div>
                        <div class="filter-actions"><button
                                class="btn">{{ __('storefront.marketplace.apply_filters') }}</button>
                            @if (collect($filters)->contains(fn($value) => $value !== null && $value !== ''))
                                <a class="btn btn-secondary"
                                    href="{{ route('storefront.index') }}#marketplace">{{ __('storefront.marketplace.clear_filters') }}</a>
                            @endif
                        </div>
                    </div>
                </form>
                @if ($topStorefronts->isNotEmpty())
                    <div class="section-head" id="top-stores">
                        <div>
                            <h2>{{ __('storefront.marketplace.top_stores') }}</h2>
                            <p class="muted">{{ __('storefront.marketplace.top_stores_text') }}</p>
                        </div>
                    </div>
                    <div class="shop-grid">
                        @foreach ($topStorefronts as $storefront)
                            @include('storefront.public.partials.market-shop-card', [
                                'storefront' => $storefront,
                                'rank' => $loop->iteration,
                            ])
                        @endforeach
                    </div>
                @endif
                @if ($topProducts->isNotEmpty())
                    <div class="market-block">
                        <div class="section-head">
                            <div>
                                <h2>{{ __('storefront.marketplace.top_products') }}</h2>
                                <p class="muted">{{ __('storefront.marketplace.top_products_text') }}</p>
                            </div>
                        </div>
                        <div class="product-grid">
                            @foreach ($topProducts as $listing)
                                @php($image = $listing->cloth?->images?->first(fn($item) => $item->image_url))
                                <article class="card product"><a class="product-image"
                                        href="{{ route('storefront.clothing.show', [$listing->storefront, $listing]) }}">
                                        @if ($image)
                                            <img src="{{ $image->image_url }}" alt="{{ $listing->display_name }}"
                                            loading="lazy">@else<i class="fas fa-tshirt"></i>
                                            @endif @if ($listing->is_featured)
                                                <span class="product-badge">{{ __('storefront.common.featured') }}</span>
                                            @endif
                                    </a>
                                    <div class="product-body">
                                        <h3>{{ $listing->display_name }}</h3>
                                        <div class="product-shop">
                                            {{ __('storefront.marketplace.product_shop', ['shop' => $listing->storefront->display_name]) }}
                                        </div>
                                        <div class="product-price">{!! \App\Support\PakistanCurrency::html($listing->cloth?->sale_price ?: $listing->cloth?->price) !!} <small
                                                class="muted">/{{ __('storefront.common.metre') }}</small></div><a
                                            class="btn" style="width:100%;margin-top:12px"
                                            href="{{ route('storefront.clothing.show', [$listing->storefront, $listing]) }}">{{ __('storefront.marketplace.view_product') }}</a>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endif
                <div class="market-block" id="all-stores">
                    @if ($storefronts->count())
                        <div class="section-head">
                            <h2>{{ __('storefront.common.all_shops') }}</h2>
                        </div>
                        <div class="shop-grid">
                            @foreach ($storefronts as $storefront)
                                @include('storefront.public.partials.market-shop-card', [
                                    'storefront' => $storefront,
                                ])
                            @endforeach
                        </div>
                        @if ($storefronts->hasPages())
                            <div style="margin-top:25px">{{ $storefronts->links() }}</div>
                    @endif @else<div class="card empty">
                            <h2>{{ __('storefront.marketplace.empty_title') }}</h2>
                            <p class="muted">{{ __('storefront.marketplace.empty_text') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </section>
        <section class="landing-section">
            <div class="shell">
                <div class="business-cta">
                    <div>
                        <h2>اپنے کاروبار کو آج ہی بہتر بنائیں</h2>
                        <p>Join BuyNStitch and manage your tailoring shop, inventory and sales with confidence.</p>
                    </div>
                    <div class="hero-actions" style="margin:0">
                        @if (config('demo.enabled'))
                            <a class="btn" href="{{ route('login', ['demo' => 1]) }}">مفت ڈیمو دیکھیں</a>
                        @endif
                        <a class="btn" href="{{ route('storefront.business.signup') }}">
                            اکاؤنٹ بنائیں</a>
                    </div>
                </div>
            </div>
        </section>
        <section class="landing-section soft">
            <div class="shell">
                <header class="landing-head"><span class="kicker">Questions answered</span>
                    <h2>{{ __('storefront.marketplace.faq_title') }}</h2>
                </header>
                <div class="faq-grid">
                    @foreach ([['faq_one_q', 'faq_one_a'], ['faq_two_q', 'faq_two_a'], ['faq_three_q', 'faq_three_a']] as [$question, $answer])
                        <details class="card faq">
                            <summary>{{ __('storefront.marketplace.' . $question) }}</summary>
                            <p class="muted">{{ __('storefront.marketplace.' . $answer) }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>
    </main>
    <footer class="tms-footer">
        <div class="shell footer-grid">
            <div class="footer-brand"><i class="fas fa-cut"></i> BuyNStitch</div>
            <div class="footer-copy">Tailoring &amp; Clothing Business Platform · © {{ now()->year }}</div>
            <div class="footer-copy">Made for businesses in Pakistan</div>
        </div>
    </footer>
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var grid = document.querySelector('.hero-grid');
                var copy = document.querySelector('.hero-copy');
                if (grid && copy && copy.parentElement !== grid) {
                    grid.appendChild(copy);
                }
            });
        </script>
    @endpush
@endsection
