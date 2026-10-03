@extends('storefront.public.layout')

@section('html_lang', 'en')
@section('html_dir', 'ltr')
@section('title', 'About BuyNStitch — Better Fabric, Better Stitching')
@section('meta_description', 'Meet BuyNStitch, the platform connecting customers with trusted local fabric shops and skilled tailors across Pakistan.')
@section('canonical_url', route('storefront.about'))
@section('meta_image', asset('images/about/tailoring-studio-hero.png'))
@section('meta_image_alt', 'A tailored navy suit and fabrics inside a local tailoring studio')

@push('styles')
    :root{--navy:#0a2854;--blue:#1267e9;--sky:#32aaf8;--ink:#17325d;--muted:#657797;--line:#e3ebf6}
    body{background:#fff;color:var(--ink)}
    .shell{width:min(1240px,calc(100% - 40px))}
    .about-page{overflow:hidden;font-family:Inter,Arial,sans-serif;line-height:1.65}
    .about-page h1,.about-page h2,.about-page h3,.about-page p{margin-top:0}
    .about-icon{width:1em;height:1em;display:inline-block;flex:0 0 auto;vertical-align:-.14em;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
    .section-label{display:flex;align-items:center;gap:12px;margin-bottom:14px;color:var(--blue);font-size:.72rem;font-weight:900;letter-spacing:.24em;text-transform:uppercase}
    .section-label:after{content:"";width:34px;height:2px;background:var(--blue);opacity:.6}
    .about-hero{position:relative;background:#eff8ff}
    .about-hero-grid{min-height:clamp(460px,58vh,560px);display:grid;grid-template-columns:52% 48%;align-items:stretch}
    .about-hero-copy{position:relative;z-index:2;display:flex;flex-direction:column;justify-content:center;padding:42px clamp(28px,5vw,76px) 42px max(20px,calc((100vw - 1240px)/2))}
    .about-hero-copy:before{content:"";position:absolute;z-index:-1;width:300px;height:300px;left:-190px;bottom:-190px;border-radius:50%;background:#d8efff}
    .about-hero h1{max-width:650px;margin-bottom:16px;color:var(--navy);font-size:clamp(2.15rem,3.2vw,3.15rem);line-height:1.08;letter-spacing:-.04em}
    .about-hero h1 span{color:var(--blue)}
    .about-hero p{max-width:540px;margin-bottom:0;color:#3f5f8d;font-size:.98rem}
    .about-hero-media{position:relative;min-height:inherit;overflow:hidden}
    .about-hero-media:after{content:"Local Fabric.\A Expert Stitching.\A All in One Place.";white-space:pre;position:absolute;top:36px;right:28px;color:#fff;font:italic 1rem/1.55 Georgia,serif;text-align:center;text-shadow:0 2px 14px rgba(0,0,0,.55);transform:rotate(-4deg)}
    .about-hero-media img{width:100%;height:100%;object-fit:cover;object-position:58% center}
    .story{padding:72px 0}
    .story-grid{display:grid;grid-template-columns:.93fr 1.07fr;align-items:center;gap:74px}
    .story-copy{padding-left:45px}
    .story h2,.difference h2,.mission-card h2{margin-bottom:12px;color:var(--navy);font-size:clamp(1.75rem,3vw,2.5rem);line-height:1.2;letter-spacing:-.025em}
    .story p{color:var(--muted)}
    .story-link,.about-cta{display:inline-flex;align-items:center;gap:13px;min-height:48px;margin-top:8px;padding:10px 21px;border-radius:12px;background:linear-gradient(135deg,#1674f4,#075cd4);color:#fff;text-decoration:none;font-weight:800;box-shadow:0 12px 25px rgba(18,103,233,.2)}
    .story-visual{position:relative;margin-right:45px}
    .story-visual:before{content:"";position:absolute;z-index:-1;right:-55px;top:55px;width:180px;height:260px;border-radius:12px;background:#d9eaff}
    .story-visual img{display:block;width:100%;height:390px;object-fit:cover;border-radius:18px;box-shadow:0 22px 48px rgba(16,45,84,.14)}
    .quality-note{position:absolute;right:-58px;bottom:20px;width:230px;padding:20px;border:1px solid #e3eaf3;border-radius:18px;background:rgba(255,255,255,.97);box-shadow:0 18px 42px rgba(23,50,93,.13)}
    .quality-note>.about-icon{width:37px;height:37px;display:block;margin-bottom:10px;padding:8px;border-radius:11px;background:#e6f1ff;color:var(--blue)}
    .quality-note span{display:block;color:#4f6384;font-size:.88rem;line-height:1.55}
    .difference{padding:12px 0 66px}
    .difference-intro{max-width:650px;margin-left:45px}
    .difference-intro p{color:var(--muted)}
    .benefit-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:28px;margin:36px 45px 0}
    .benefit{min-width:0}
    .benefit-icon{width:60px;height:60px;display:grid;place-items:center;margin-bottom:16px;border-radius:18px;background:#e7f0ff;color:#1f75f2;font-size:1.45rem}.benefit-icon .about-icon{width:27px;height:27px}
    .benefit:nth-child(2) .benefit-icon{background:#e2faf3;color:#08a87b}.benefit:nth-child(3) .benefit-icon{background:#eee9ff;color:#7151eb}.benefit:nth-child(4) .benefit-icon{background:#fff0d9;color:#ef9717}.benefit:nth-child(5) .benefit-icon{background:#ffe7f1;color:#f43c88}
    .benefit h3{margin-bottom:7px;color:var(--navy);font-size:.98rem}
    .benefit p{margin:0;color:var(--muted);font-size:.86rem}
    .values-mission{padding:0 0 28px}
    .values-grid{display:grid;grid-template-columns:1.04fr .96fr;gap:20px}
    .values-card,.mission-card{min-height:280px;padding:36px 42px;border:1px solid #e4edf8;border-radius:20px;background:linear-gradient(125deg,#f7fbff,#eaf5ff);box-shadow:0 12px 34px rgba(18,63,112,.05)}
    .mission-card{background:linear-gradient(145deg,#fbfdff,#f4f8fc)}
    .value-words{color:var(--navy);font-size:1.35rem;font-weight:900;word-spacing:.25em}
    .values-card p,.mission-card p{color:var(--muted)}
    .handwritten{margin-top:26px;color:#1674f4;font:italic 1.25rem/1.4 "Segoe Script","Brush Script MT",cursive}
    .vision{display:flex;align-items:center;gap:16px;margin-top:24px}
    .vision-icon{width:55px;height:55px;flex:0 0 55px;display:grid;place-items:center;border-radius:50%;background:#e5f1ff;color:var(--blue);font-size:1.3rem}.vision-icon .about-icon{width:25px;height:25px}
    .vision strong,.vision small{display:block}.vision strong{color:var(--navy)}.vision small{color:var(--muted)}
    .stats{margin:0 0 24px}
    .stats-grid{display:grid;grid-template-columns:repeat(4,1fr);padding:28px 22px;border-radius:18px;background:linear-gradient(120deg,#093b78,#0b67ce);color:#fff;box-shadow:0 20px 45px rgba(11,75,150,.2)}
    .stat{text-align:center;border-right:1px solid rgba(255,255,255,.25)}.stat:last-child{border:0}
    .stat .about-icon{display:block;width:28px;height:28px;margin:0 auto 8px;color:#4dd4ff}.stat strong{display:block;font-size:1.55rem;line-height:1.15}.stat span{font-size:.77rem;color:#e0efff}
    .join{margin-bottom:48px}
    .join-card{position:relative;overflow:hidden;display:flex;align-items:center;justify-content:space-between;gap:30px;padding:24px 48px;border:1px solid #dbeafb;border-radius:18px;background:linear-gradient(105deg,#edf7ff,#fff)}
    .join-card:after{content:"";position:absolute;right:-80px;bottom:-100px;width:250px;height:180px;border-radius:50%;border:22px solid rgba(47,139,245,.08)}
    .join-copy{display:flex;align-items:center;gap:18px}.join-icon{width:52px;height:52px;display:grid;place-items:center;border-radius:50%;background:#dfeeff;color:var(--blue)}.join-icon .about-icon{width:24px;height:24px}
    .join h3{margin:0 0 2px;color:var(--navy);font-size:1.05rem}.join p{margin:0;color:var(--muted);font-size:.86rem}.join .about-cta{position:relative;z-index:1;margin:0;padding-inline:25px}
    .about-footer{padding:28px 0;background:#071e3c;color:#d7e7fa}.about-footer .shell{display:flex;align-items:center;justify-content:space-between;gap:20px}.about-footer strong{display:flex;align-items:center;gap:7px;font-size:1.25rem}.about-footer strong .about-icon{width:22px;height:22px}.about-footer span{color:#9fb6d3;font-size:.82rem}
    @media(max-width:1000px){.benefit-grid{grid-template-columns:repeat(3,1fr)}.story-grid{gap:40px}.story-copy{padding-left:15px}.story-visual{margin-right:45px}}
    @media(max-width:780px){.about-hero-grid{grid-template-columns:1fr}.about-hero-copy{padding:38px 25px}.about-hero-media{min-height:290px}.story-grid,.values-grid{grid-template-columns:1fr}.story-copy{padding:0 5px}.story-visual{margin:0 40px 0 5px}.benefit-grid{grid-template-columns:repeat(2,1fr);margin-inline:5px}.difference-intro{margin-left:5px}.stats-grid{grid-template-columns:repeat(2,1fr);gap:22px}.stat:nth-child(2){border:0}.join-card{align-items:flex-start;flex-direction:column;padding:28px}.about-footer .shell{align-items:flex-start;flex-direction:column}}
    @media(max-width:520px){.shell{width:min(100% - 28px,1240px)}.about-hero-copy{padding:32px 18px}.about-hero h1{font-size:1.95rem}.about-hero p{font-size:.92rem}.about-hero-media{min-height:250px}.about-hero-media:after{right:16px;top:22px;font-size:.88rem}.story{padding:52px 0}.story-visual{margin:0}.story-visual img{height:300px}.story-visual:before{display:none}.quality-note{right:12px;bottom:-30px;width:200px}.difference{padding-top:48px}.benefit-grid{grid-template-columns:1fr}.values-card,.mission-card{padding:28px 24px}.stats-grid{padding-inline:12px}.join-copy{align-items:flex-start}.about-footer{padding-bottom:34px}}
    @include('storefront.public.partials.platform-header-styles')
@endpush

@section('body')
    @php($platformHeaderLocale = 'en')
    @include('storefront.public.partials.platform-header')

    <main class="about-page">
        <section class="about-hero">
            <div class="about-hero-grid">
                <div class="about-hero-copy">
                    <div class="section-label">About us</div>
                    <h1>Making tailoring and fabric businesses <span>simpler, smarter and more successful.</span></h1>
                    <p>BuyNStitch brings trusted local fabrics and expert tailoring together in one convenient platform.</p>
                </div>
                <div class="about-hero-media">
                    <img src="{{ asset('images/about/tailoring-studio-hero.png') }}" alt="A navy tailored suit, premium fabric and tailoring tools in a bright studio">
                </div>
            </div>
        </section>

        <section class="story">
            <div class="shell story-grid">
                <div class="story-copy">
                    <div class="section-label">Our story</div>
                    <h2>From a simple idea<br>to a complete solution</h2>
                    <p>BuyNStitch started with a simple belief — getting quality fabric and professional tailoring should be easy, reliable and accessible for everyone.</p>
                    <p>We noticed that many people struggle to find the right fabric, trusted tailors and a smooth ordering experience. So, we built a platform that connects customers with local fabric shops and skilled tailors, helping them get the best materials and craftsmanship without the hassle.</p>
                    <a class="story-link" href="#mission">Our journey @include('storefront.public.partials.about-icon', ['name' => 'arrow'])</a>
                </div>
                <div class="story-visual">
                    <img src="{{ asset('images/about/tailor-craftsmanship.png') }}" alt="A tailor carefully marking navy fabric by hand" loading="lazy">
                    <div class="quality-note">@include('storefront.public.partials.about-icon', ['name' => 'users'])<span>Quality Fabrics</span><span>Skilled Tailors</span><span>Happy Customers</span></div>
                </div>
            </div>
        </section>

        <section class="difference">
            <div class="shell">
                <div class="difference-intro">
                    <div class="section-label">Why choose us</div>
                    <h2>What Makes BuyNStitch Different?</h2>
                    <p>We focus on quality, convenience and trust — so you can get the best fabrics and tailoring services, without any worry.</p>
                </div>
                <div class="benefit-grid">
                    @foreach ([
                        ['shield','Verified Tailors','Work with skilled and trusted local tailors.'],
                        ['fabric','Wide Fabric Range','Explore a variety of high-quality fabrics.'],
                        ['clock','Easy Ordering','Book, customize and track — all online.'],
                        ['pin','Support Local','Help local businesses grow and thrive.'],
                        ['star','Customer Satisfaction','Your trust is our biggest reward.'],
                    ] as [$icon,$title,$copy])
                        <article class="benefit"><div class="benefit-icon">@include('storefront.public.partials.about-icon', ['name' => $icon])</div><h3>{{ $title }}</h3><p>{{ $copy }}</p></article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="values-mission" id="mission">
            <div class="shell values-grid">
                <article class="values-card">
                    <div class="section-label">Our values</div>
                    <div class="value-words">Quality &nbsp;•&nbsp; Trust &nbsp;•&nbsp; Community &nbsp;•&nbsp; Growth</div>
                    <p>We believe in supporting local talent, promoting traditional craftsmanship and creating opportunities for small businesses. Our platform is built on trust, quality and a commitment to our community.</p>
                    <div class="handwritten">Better Fabric &nbsp; ✂ &nbsp; Better Stitching &nbsp; ✂ &nbsp; A Brighter Future</div>
                </article>
                <article class="mission-card">
                    <div class="section-label">Our mission</div>
                    <h2>To make tailoring and fabric services accessible to everyone.</h2>
                    <p>We aim to empower local fabric shops and tailors with technology, while giving customers a seamless, reliable and enjoyable experience.</p>
                    <div class="vision"><span class="vision-icon">@include('storefront.public.partials.about-icon', ['name' => 'target'])</span><span><strong>Our Vision</strong><small>To become the leading platform for fabric and tailoring services in every city.</small></span></div>
                </article>
            </div>
        </section>

        <section class="stats">
            <div class="shell stats-grid">
                <div class="stat">@include('storefront.public.partials.about-icon', ['name' => 'users'])<strong>10K+</strong><span>Happy Customers</span></div>
                <div class="stat">@include('storefront.public.partials.about-icon', ['name' => 'store'])<strong>500+</strong><span>Local Fabric Shops</span></div>
                <div class="stat">@include('storefront.public.partials.about-icon', ['name' => 'shirt'])<strong>1K+</strong><span>Skilled Tailors</span></div>
                <div class="stat">@include('storefront.public.partials.about-icon', ['name' => 'star'])<strong>100%</strong><span>Customer Satisfaction</span></div>
            </div>
        </section>

        <section class="join">
            <div class="shell join-card">
                <div class="join-copy"><span class="join-icon">@include('storefront.public.partials.about-icon', ['name' => 'seedling'])</span><div><h3>Let’s Build Something Great Together</h3><p>Join BuyNStitch and experience the perfect blend of tradition and technology.</p></div></div>
                <a class="about-cta" href="{{ route('storefront.business.signup') }}">Get started @include('storefront.public.partials.about-icon', ['name' => 'arrow'])</a>
            </div>
        </section>
    </main>

    <footer class="about-footer">
        <div class="shell"><strong>@include('storefront.public.partials.about-icon', ['name' => 'scissors']) BuyNStitch</strong><span>Tailoring &amp; Clothing Business Platform · © {{ now()->year }}</span><span>Made for businesses in Pakistan</span></div>
    </footer>
@endsection
