@extends('main')

@section('content')
    @php($activeSetting = $settings->firstWhere('status', 1) ?: $settings->first())
    @include('setting.partials.styles')
    <style>
        /* One settings page, full width, split into sections with quick links at the top. */
        .st-nav{position:sticky;top:70px;z-index:5;display:flex;flex-wrap:wrap;gap:10px;margin-bottom:22px;padding:10px;border:1px solid #e3eaf4;border-radius:16px;background:rgba(255,255,255,.94);box-shadow:0 8px 22px rgba(15,37,71,.05)}
        .st-nav a{display:inline-flex;align-items:center;gap:8px;min-height:42px;padding:0 16px;border-radius:11px;background:#f1f5fb;color:#163b62!important;font-weight:800;text-decoration:none!important}
        .st-nav a:hover,.st-nav a.is-active{background:#1769e0;color:#fff!important}
        .st-sections{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:22px;align-items:stretch}
        .st-sections>.st-card{margin:0;scroll-margin-top:140px}.st-sections>.is-full{grid-column:1/-1}
        .st-card-head p{margin:4px 0 0;color:#64748b;font-size:.85rem}
        .st-edit{display:inline-flex;flex:0 0 auto;align-items:center;gap:7px;padding:8px 14px;border:1px solid #cfe0f7;border-radius:10px;background:#f1f7ff;color:#1769e0!important;font-weight:800;text-decoration:none!important}
        .st-shop{display:grid;grid-template-columns:150px minmax(0,1fr);gap:18px;align-items:start}
        .st-stack{display:grid;gap:14px}
        @media(max-width:1100px){.st-sections{grid-template-columns:1fr}.st-sections>.is-full{grid-column:auto}}
        @media(max-width:767px){.st-shop{grid-template-columns:1fr}.st-nav{top:60px}.st-nav a{flex:1 1 calc(50% - 10px);justify-content:center}}
    </style>
    <section class="main-content st-page">
        <div class="st-shell">
            <header class="st-hero">
                <span class="st-hero-icon"><i class="fas fa-cog"></i></span>
                <div class="st-hero-text">
                    <h1>کاروباری سیٹنگز</h1>
                    <p>دکان کی شناخت، رسید، پرنٹ اور آرڈر اسکرین کی ترتیب ایک جگہ</p>
                </div>
                @unless ($activeSetting)
                    <a href="{{ route('admin.add-setting') }}" class="st-hero-action"><i class="fas fa-plus"></i> دکان کی معلومات شامل کریں</a>
                @endunless
            </header>
            @foreach (['success' => 'success', 'update' => 'success', 'delete' => 'warning', 'error' => 'danger'] as $key => $class)
                @if (session($key))<div class="alert alert-{{ $class }}">{{ session($key) }}</div>@endif
            @endforeach

            <nav class="st-nav" aria-label="سیٹنگز کے حصے">
                <a href="#shop"><i class="fas fa-store"></i> دکان کی معلومات</a>
                <a href="#print"><i class="fas fa-print"></i> رسید اور پرنٹ</a>
                <a href="#order-screen"><i class="fas fa-th-large"></i> آرڈر اسکرین</a>
                <a href="#order-serial"><i class="fas fa-hashtag"></i> آرڈر نمبر</a>
                @if ($business?->shop_code)
                    <a href="#portal"><i class="fas fa-user-tie"></i> درزی پورٹل</a>
                @endif
            </nav>

            <div class="st-sections">
                @if ($activeSetting)
                    <section class="st-card" id="shop">
                        <div class="st-card-head">
                            <div>
                                <h2 class="st-card-title"><i class="fas fa-store"></i> دکان کی معلومات</h2>
                                <p>رسید، پرنٹ اور گاہک کے پیغامات میں دکان کی شناخت</p>
                            </div>
                            <a href="{{ route('admin.edit-setting', $activeSetting->id) }}" class="st-edit"><i class="fas fa-pen"></i> تبدیل کریں</a>
                        </div>
                        <div class="st-shop">
                            <div class="st-logo">
                                @if ($activeSetting->logo_url)
                                    <img src="{{ $activeSetting->logo_url }}" alt="{{ $activeSetting->name }} لوگو">
                                @else
                                    <div><i class="fas fa-store fa-2x"></i><div class="small mt-2">لوگو شامل نہیں</div></div>
                                @endif
                            </div>
                            <div class="st-stack">
                                <div class="st-tile"><i class="far fa-user"></i><div><small>کاروبار کا نام</small><strong>{{ $activeSetting->name }}</strong></div></div>
                                <div class="st-tile"><i class="fas fa-phone-alt"></i><div><small>رابطہ نمبر</small><strong dir="ltr">{{ $activeSetting->contact_no ?: 'درج نہیں' }}</strong></div></div>
                                <div class="st-tile"><i class="fas fa-map-marker-alt"></i><div><small>پتہ</small><strong>{{ $activeSetting->address ?: 'درج نہیں' }}</strong></div></div>
                            </div>
                        </div>
                    </section>

                    <section class="st-card" id="print">
                        <div class="st-card-head">
                            <div>
                                <h2 class="st-card-title"><i class="fas fa-print"></i> رسید اور پرنٹ</h2>
                                <p>رسید پر نوٹ، کاغذ کا سائز اور QR حوالہ</p>
                            </div>
                            <a href="{{ route('admin.edit-setting', $activeSetting->id) }}" class="st-edit"><i class="fas fa-pen"></i> تبدیل کریں</a>
                        </div>
                        <div class="st-stack">
                            <div class="st-tile"><i class="far fa-file-alt"></i><div><small>رسید / کاروباری نوٹ</small><strong>{{ $activeSetting->note ?: 'درج نہیں' }}</strong></div></div>
                            <div class="st-tile"><i class="fas fa-ruler"></i><div><small>پرنٹ سائز</small><strong>{{ \App\Models\Setting::printPaperSizes()[$activeSetting->print_paper_size] ?? $activeSetting->print_paper_size }}</strong></div></div>
                            <div class="st-tile"><i class="fas fa-qrcode"></i><div><small>QR حوالہ</small><strong>{{ $activeSetting->print_show_qr ? 'رسید پر دکھائیں' : 'نہ دکھائیں' }}</strong></div></div>
                        </div>
                    </section>
                @else
                    <section class="st-card is-full" id="shop">
                        <div class="st-empty"><i class="fas fa-store"></i><strong>دکان کی معلومات ابھی شامل نہیں ہوئیں۔</strong><div class="mt-2">رسید اور آرڈر پرنٹ کے لیے کاروباری پروفائل بنائیں۔</div>
                            <a href="{{ route('admin.add-setting') }}" class="st-btn is-primary mt-3"><i class="fas fa-plus"></i> دکان کی معلومات شامل کریں</a></div>
                        <span id="print"></span>
                    </section>
                @endif

                @include('setting.partials.order-screen')
                @include('setting.partials.order-serial')

                @if ($business?->shop_code)
                    <section class="st-card {{ $settings->count() > 1 ? '' : 'is-full' }}" id="portal">
                        <div class="st-card-head">
                            <div>
                                <h2 class="st-card-title"><i class="fas fa-user-tie"></i> درزی پورٹل</h2>
                                <p>یہ کوڈ درزی کو اس کے فون نمبر اور پاس ورڈ کے ساتھ دیں۔</p>
                            </div>
                        </div>
                        <div class="st-code mb-0"><strong>درزی پورٹل کا دکان کوڈ</strong><code dir="ltr">{{ $business->shop_code }}</code></div>
                    </section>
                @endif

                @if ($settings->count() > 1)
                    <section class="st-card {{ $business?->shop_code ? '' : 'is-full' }}">
                        <div class="st-card-head">
                            <div>
                                <h2 class="st-card-title"><i class="fas fa-archive"></i> غیر فعال سابقہ پروفائلز</h2>
                                <p>صرف ضرورت کے وقت استعمال کریں</p>
                            </div>
                        </div>
                        <details>
                            <summary class="font-weight-bold" style="cursor:pointer">{{ $settings->count() - 1 }} سابقہ پروفائل دکھائیں</summary>
                            <div class="mt-3">
                                @foreach ($settings->where('id', '!=', $activeSetting?->id) as $setting)
                                    <div class="st-archive">
                                        <div><strong>{{ $setting->name }}</strong><div class="small text-muted">{{ $setting->contact_no ?: 'رابطہ درج نہیں' }}</div></div>
                                        <div class="st-archive-actions">
                                            <form action="{{ route('admin.active-setting', $setting->id) }}" method="POST" data-confirm="کیا یہ پروفائل فعال کرنا ہے؟">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success">فعال کریں</button></form>
                                            <a href="{{ route('admin.edit-setting', $setting->id) }}" class="btn btn-sm btn-light">ترمیم</a>
                                            <form action="{{ route('admin.delete-setting', $setting->id) }}" method="POST" data-confirm="کیا یہ سابقہ پروفائل حذف کرنا ہے؟">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger">حذف</button></form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    </section>
                @endif
            </div>
        </div>
    </section>
    <script>
        // Marks the section link of the part of the page being looked at.
        (function() {
            var links = Array.prototype.slice.call(document.querySelectorAll('.st-nav a'));
            if (!links.length || !('IntersectionObserver' in window)) return;
            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (!entry.isIntersecting) return;
                    links.forEach(function(link) {
                        link.classList.toggle('is-active', link.getAttribute('href') === '#' + entry.target.id);
                    });
                });
            }, {rootMargin: '-40% 0px -55% 0px'});
            links.forEach(function(link) {
                var section = document.querySelector(link.getAttribute('href'));
                if (section && section.classList.contains('st-card')) observer.observe(section);
            });
        })();
    </script>
@endsection
