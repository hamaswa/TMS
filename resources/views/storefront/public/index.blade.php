@php
    $isUrdu = app()->getLocale() === 'ur';

    $copy = $isUrdu
        ? [
            'seo_title' => 'TMS — ٹیلرنگ مینجمنٹ سسٹم',
            'seo_description' => 'اپنی ٹیلرنگ شاپ، آرڈرز، گاہکوں، انوینٹری اور مالی معاملات کو ایک جگہ سے منظم کریں۔',

            'home' => 'ہوم',
            'features' => 'خصوصیات',
            'about' => 'ہمارے بارے میں',
            'contact' => 'رابطہ',
            'login' => 'دکاندار لاگ اِن',

            'eyebrow' => 'جدید ٹیلرز کے لیے اسمارٹ حل',
            'hero_title_1' => 'اپنی شاپ کو',
            'hero_title_2' => 'زیادہ اسمارٹ طریقے سے چلائیں',
            'hero_text' => 'TMS کے ساتھ اپنے آرڈرز، گاہکوں، پیمائش، کپڑے، ادائیگیوں اور کاروباری ریکارڈ کو آسانی سے ایک ہی جگہ پر منظم کریں۔',

            'get_started' => 'شروع کریں',
            'learn_more' => 'مزید جانیں',

            'easy' => 'استعمال میں آسان',
            'secure' => 'محفوظ اور قابلِ اعتماد',
            'tailors' => 'ٹیلرز کے لیے بنایا گیا',

            'login_title' => 'دکاندار لاگ اِن',
            'login_subtitle' => 'اپنے شاپ ڈیش بورڈ تک رسائی حاصل کریں',
            'email' => 'ای میل ایڈریس',
            'password' => 'پاس ورڈ',
            'remember' => 'مجھے یاد رکھیں',
            'forgot' => 'پاس ورڈ بھول گئے؟',

            'feature_eyebrow' => 'ہماری خصوصیات',
            'feature_title' => 'اپنی شاپ کو منظم کرنے کے لیے سب کچھ',
            'feature_text' => 'آپ کے ٹیلرنگ کاروبار کو تیز، منظم اور بہتر بنانے کے لیے طاقتور فیچرز۔',

            'orders' => 'آرڈرز مینجمنٹ',
            'orders_text' => 'اپنے تمام آرڈرز کو شروع سے آخر تک آسانی سے منظم کریں۔',

            'customers' => 'گاہک',
            'customers_text' => 'اپنے تمام گاہکوں کی معلومات ایک منظم جگہ پر محفوظ رکھیں۔',

            'tailoring' => 'ٹیلرنگ',
            'tailoring_text' => 'پیمائش، فٹنگ اور کسٹم ڈیزائن کو آسانی سے منظم کریں۔',

            'inventory' => 'کپڑے اور انوینٹری',
            'inventory_text' => 'کپڑے، فیبرک اور دیگر سامان کا اسٹاک ریئل ٹائم میں دیکھیں۔',

            'finance' => 'ادائیگیاں اور فنانس',
            'finance_text' => 'ادائیگیوں، اخراجات اور منافع کو آسانی سے منظم کریں۔',

            'reports' => 'رپورٹس',
            'reports_text' => 'اپنے کاروبار کی کارکردگی کے بارے میں تفصیلی رپورٹس حاصل کریں۔',

            'how_eyebrow' => 'یہ کیسے کام کرتا ہے',
            'how_title' => 'صرف 3 آسان مراحل میں شروع کریں',
            'how_text' => 'چند منٹ میں اپنی شاپ کو منظم کرنا شروع کریں۔',

            'step_login' => 'لاگ اِن',
            'step_login_text' => 'اپنے اکاؤنٹ میں محفوظ طریقے سے داخل ہوں۔',

            'step_manage' => 'مینیج کریں',
            'step_manage_text' => 'آرڈرز، گاہکوں اور انوینٹری کو منظم کریں۔',

            'step_grow' => 'ترقی کریں',
            'step_grow_text' => 'اپنے کاروبار کو اگلے لیول تک لے جائیں۔',

            'business_eyebrow' => 'آپ کے کاروبار کے لیے بنایا گیا',
            'business_title' => 'اپنی ٹیلرنگ شاپ کو پروفیشنل انداز میں مینیج کریں',
            'business_text' => 'TMS آپ کا وقت بچانے، کام کو منظم کرنے اور اپنے گاہکوں پر زیادہ توجہ دینے میں مدد کرتا ہے۔',

            'benefit_1' => 'سادہ اور استعمال میں آسان',
            'benefit_2' => 'کہیں سے بھی رسائی',
            'benefit_3' => 'آپ کا ڈیٹا محفوظ',
            'benefit_4' => 'چھوٹی اور درمیانے درجے کی شاپس کے لیے موزوں',

            'cta_eyebrow' => 'شروع کرنے کے لیے تیار ہیں؟',
            'cta_title' => 'TMS کے ساتھ اپنی شاپ کو اسمارٹ طریقے سے چلائیں',
            'cta_text' => 'اپنے اکاؤنٹ میں لاگ اِن کریں اور آج ہی اپنے ٹیلرنگ کاروبار کا کنٹرول حاصل کریں۔',

            'footer_text' => 'TMS — ٹیلرنگ کاروبار کے لیے اسمارٹ مینجمنٹ سسٹم',
            'rights' => 'تمام حقوق محفوظ ہیں۔',
        ]
        : [
            'seo_title' => 'TMS — Tailoring Management System',
            'seo_description' => 'Manage your tailoring shop, orders, customers, inventory and finances from one place.',

            'home' => 'Home',
            'features' => 'Features',
            'about' => 'About',
            'contact' => 'Contact',
            'login' => 'Shop Owner Login',

            'eyebrow' => 'SMART SOLUTIONS FOR MODERN TAILORS',
            'hero_title_1' => 'TMS — Your Complete',
            'hero_title_2' => 'Shop Management System',
            'hero_text' => 'Manage your tailoring business with ease. Track orders, handle customers, manage inventory, and grow your business — all in one place.',

            'get_started' => 'Shop Owner Login',
            'learn_more' => 'Learn More',

            'easy' => 'Easy to Use',
            'secure' => 'Secure & Reliable',
            'tailors' => 'Made for Tailors',

            'login_title' => 'Shop Owner Login',
            'login_subtitle' => 'Access your shop dashboard',
            'email' => 'Email Address',
            'password' => 'Password',
            'remember' => 'Remember Me',
            'forgot' => 'Forgot Password?',

            'feature_eyebrow' => 'OUR FEATURES',
            'feature_title' => 'Everything You Need to Manage Your Shop',
            'feature_text' => 'Powerful features designed to make your tailoring business more efficient and profitable.',

            'orders' => 'Orders Management',
            'orders_text' => 'Track and manage all your orders from start to finish.',

            'customers' => 'Customers',
            'customers_text' => 'Keep your customer information organized and easily accessible.',

            'tailoring' => 'Tailoring',
            'tailoring_text' => 'Manage measurements, fittings and custom designs.',

            'inventory' => 'Clothing & Inventory',
            'inventory_text' => 'Track your stock, fabrics and materials in real-time.',

            'finance' => 'Payments & Finance',
            'finance_text' => 'Handle payments, expenses and profits with ease.',

            'reports' => 'Reports',
            'reports_text' => 'Get detailed reports and insights to grow your business.',

            'how_eyebrow' => 'HOW IT WORKS',
            'how_title' => 'Get Started in 3 Simple Steps',
            'how_text' => 'Start managing your shop in just a few minutes.',

            'step_login' => 'Login',
            'step_login_text' => 'Access your account securely.',

            'step_manage' => 'Manage',
            'step_manage_text' => 'Handle your orders, customers and inventory.',

            'step_grow' => 'Grow',
            'step_grow_text' => 'Take your business to the next level.',

            'business_eyebrow' => 'BUILT FOR YOUR BUSINESS',
            'business_title' => 'Professional Management for Your Tailoring Shop',
            'business_text' => 'TMS helps you save time, reduce stress and focus on what matters most — creating great clothes and happy customers.',

            'benefit_1' => 'Simple and easy to use',
            'benefit_2' => 'Access from anywhere',
            'benefit_3' => 'Secure your data',
            'benefit_4' => 'Built for small & medium tailoring shops',

            'cta_eyebrow' => 'READY TO GET STARTED?',
            'cta_title' => 'Manage Your Shop Smarter with TMS',
            'cta_text' => 'Sign in to your account and take control of your tailoring business today.',

            'footer_text' => 'TMS — Smart Management System for Tailoring Businesses',
            'rights' => 'All rights reserved.',
        ];
@endphp

@extends('storefront.public.layout')

@section('title', $copy['seo_title'])
@section('meta_description', $copy['seo_description'])
@section('canonical_url', route('storefront.index'))

@push('structured_data')
    <script type="application/ld+json">
        {!! \App\Support\StorefrontSeo::json(
            \App\Support\StorefrontSeo::graph(
                \App\Support\StorefrontSeo::website(
                    $copy['seo_title'],
                    $copy['seo_description'],
                    route('storefront.index')
                )
            )
        ) !!}
    </script>
@endpush

@push('styles')

    /* =========================================================
       TMS LANDING PAGE
       ========================================================= */

    :root {
        --tms-blue: #2563eb;
        --tms-blue-dark: #123a8c;
        --tms-navy: #102a56;
        --tms-purple: #6366f1;
        --tms-text: #102a56;
        --tms-muted: #60708d;
        --tms-bg: #f6f9ff;
        --tms-line: #e5ebf5;
        --tms-white: #ffffff;
    }

    html {
        scroll-behavior: smooth;
    }

    body {
        background: #fff;
        color: var(--tms-text);
    }

    .tms-page {
        overflow: hidden;
        background: #fff;
    }

    .tms-shell {
        width: min(1180px, calc(100% - 40px));
        margin: 0 auto;
    }

    /* =========================================================
       NAVBAR
       ========================================================= */

    .tms-navbar {
        position: sticky;
        top: 0;
        z-index: 100;
        background: rgba(255,255,255,.94);
        backdrop-filter: blur(14px);
        border-bottom: 1px solid rgba(220,228,241,.75);
    }

    .tms-navbar-inner {
        min-height: 76px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 25px;
    }

    .tms-brand {
        display: inline-flex;
        align-items: center;
        gap: 11px;
        text-decoration: none;
        color: var(--tms-navy);
    }

    .tms-brand-icon {
        width: 42px;
        height: 42px;
        border: 3px solid var(--tms-navy);
        border-radius: 9px 9px 18px 18px;
        position: relative;
        transform: scaleX(.9);
    }

    .tms-brand-icon:before {
        content: "";
        position: absolute;
        width: 12px;
        height: 12px;
        border: 3px solid var(--tms-navy);
        border-radius: 50%;
        top: -13px;
        left: 12px;
        background: #fff;
    }

    .tms-brand-icon:after {
        content: "";
        position: absolute;
        width: 26px;
        height: 3px;
        background: var(--tms-navy);
        left: 5px;
        top: 17px;
        transform: rotate(-27deg);
    }

    .tms-brand-text strong {
        display: block;
        font-size: 24px;
        line-height: 1;
        font-weight: 900;
        letter-spacing: -.5px;
    }

    .tms-brand-text small {
        display: block;
        font-size: 10px;
        margin-top: 3px;
        color: #6f7f9b;
        white-space: nowrap;
    }

    .tms-nav-links {
        display: flex;
        align-items: center;
        gap: 30px;
    }

    .tms-nav-links a {
        color: #3f5070;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        transition: .25s ease;
    }

    .tms-nav-links a:hover {
        color: var(--tms-blue);
    }

    .tms-nav-actions {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .tms-language {
        display: flex;
        align-items: center;
        border: 1px solid #e0e7f2;
        border-radius: 10px;
        padding: 2px 5px;
        background: #fff;
    }

    .tms-language .locale-switch {
        gap: 2px;
    }

    .tms-language .locale-switch a {
        min-height: 34px;
        min-width: 42px;
        color: #4d5e7d;
        font-size: 12px;
        border-radius: 7px;
    }

    .tms-language .locale-switch a.active {
        background: #edf4ff;
        color: var(--tms-blue);
    }

    .tms-language .locale-switch span {
        color: #c8d1df;
    }

    .tms-login-btn,
    .tms-primary-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        min-height: 46px;
        padding: 0 21px;
        border-radius: 11px;
        background: linear-gradient(135deg, #2563eb, #6366f1);
        color: #fff !important;
        text-decoration: none;
        font-size: 13px;
        font-weight: 800;
        box-shadow: 0 9px 22px rgba(37,99,235,.23);
        transition: transform .25s ease, box-shadow .25s ease;
    }

    .tms-login-btn:hover,
    .tms-primary-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 30px rgba(37,99,235,.30);
    }

    /* =========================================================
       HERO
       ========================================================= */

    .tms-hero {
        position: relative;
        min-height: 510px;
        background:
            radial-gradient(circle at 5% 20%, rgba(37,99,235,.16), transparent 25%),
            linear-gradient(110deg, #f7fbff 0%, #edf5ff 52%, #dcecff 100%);
        display: flex;
        align-items: center;
    }

    .tms-hero:before {
        content: "";
        position: absolute;
        width: 360px;
        height: 360px;
        border-radius: 50%;
        background: rgba(99,102,241,.08);
        right: -120px;
        top: -140px;
    }

    .tms-hero-inner {
        position: relative;
        z-index: 2;
        display: grid;
        grid-template-columns: 1fr 1fr;
        align-items: center;
        gap: 45px;
        padding: 55px 0;
    }

    .tms-eyebrow {
        color: var(--tms-blue);
        font-size: 12px;
        font-weight: 900;
        letter-spacing: 1.2px;
        text-transform: uppercase;
        margin-bottom: 13px;
    }

    .tms-hero h1 {
        color: var(--tms-navy);
        font-size: clamp(38px, 5vw, 62px);
        line-height: 1.05;
        letter-spacing: -2px;
        margin: 0 0 20px;
        font-weight: 900;
    }

    .tms-hero h1 span {
        color: var(--tms-blue);
    }

    .tms-hero-text {
        max-width: 600px;
        color: #526582;
        font-size: 17px;
        line-height: 1.75;
        margin-bottom: 27px;
    }

    .tms-hero-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 13px;
        margin-bottom: 22px;
    }

    .tms-secondary-btn {
        min-height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        padding: 0 20px;
        border-radius: 11px;
        border: 1px solid #d5e0f0;
        background: rgba(255,255,255,.75);
        color: var(--tms-navy);
        text-decoration: none;
        font-size: 13px;
        font-weight: 800;
        transition: .25s ease;
    }

    .tms-secondary-btn:hover {
        border-color: var(--tms-blue);
        color: var(--tms-blue);
        transform: translateY(-2px);
    }

    .tms-trust-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .tms-trust-item {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: rgba(255,255,255,.82);
        border: 1px solid #e4ebf5;
        padding: 8px 12px;
        border-radius: 9px;
        font-size: 11px;
        color: #455878;
        font-weight: 700;
    }

    .tms-trust-item i {
        color: var(--tms-blue);
    }

    /* =========================================================
       HERO VISUAL
       ========================================================= */

    .tms-hero-visual {
        min-height: 410px;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
    }

.tms-workspace {
    position: absolute;
    width: 100%;
    height: 100%;
    right: -5%;
    border-radius: 30px;
    overflow: hidden;

    background-image:
        linear-gradient(
            135deg,
            rgba(16,42,86,.35),
            rgba(37,99,235,.15)
        ),
        url("{{ asset('/assets/images/landing_image.png') }}");

    background-size: cover;
    background-position: center;

    box-shadow: 0 28px 70px rgba(27,59,105,.25);
}

    .tms-workspace:before {
        content: "";
        position: absolute;
        width: 300px;
        height: 300px;
        background: rgba(255,255,255,.08);
        border-radius: 50%;
        right: -90px;
        top: -90px;
    }

    .tms-workspace:after {
        content: "";
        position: absolute;
        width: 500px;
        height: 100px;
        background: rgba(255,255,255,.07);
        transform: rotate(-13deg);
        bottom: 40px;
        left: -70px;
    }

    /* =========================================================
       LOGIN CARD
       ========================================================= */

    .tms-login-card {
        position: absolute;
        z-index: 5;
        width: 300px;
        right: -25px;
        top: 20px;
        background: rgba(255,255,255,.96);
        border: 1px solid rgba(255,255,255,.9);
        border-radius: 18px;
        padding: 23px;
        box-shadow: 0 25px 55px rgba(14,43,82,.25);
        backdrop-filter: blur(12px);
        animation: tmsFloat 5s ease-in-out infinite;
    }

    .tms-login-heading {
        display: flex;
        align-items: center;
        gap: 11px;
        margin-bottom: 18px;
    }

    .tms-login-icon {
        width: 34px;
        height: 34px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #e8f0ff;
        color: var(--tms-blue);
    }

    .tms-login-heading strong {
        display: block;
        color: var(--tms-navy);
        font-size: 16px;
    }

    .tms-login-heading small {
        display: block;
        color: #75849d;
        font-size: 10px;
        margin-top: 2px;
    }

    .tms-demo-field {
        height: 43px;
        border: 1px solid #dce4ef;
        border-radius: 9px;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 0 12px;
        margin-bottom: 10px;
        color: #91a0b7;
        background: #fff;
        font-size: 11px;
    }

    .tms-demo-field i {
        color: #5975a2;
    }

    .tms-login-options {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        font-size: 10px;
        color: #5c6c86;
        margin: 10px 0 15px;
    }

    .tms-login-options a {
        color: var(--tms-blue);
        text-decoration: none;
        font-weight: 700;
    }

    .tms-demo-check {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .tms-demo-check span {
        width: 11px;
        height: 11px;
        border: 1px solid #b6c6de;
        border-radius: 2px;
    }

    .tms-card-login {
        width: 100%;
        min-height: 43px;
        border: 0;
        border-radius: 9px;
        background: linear-gradient(135deg,#2563eb,#6366f1);
        color: #fff;
        font: inherit;
        font-size: 12px;
        font-weight: 800;
        cursor: pointer;
    }

    @keyframes tmsFloat {
        0%,100% { transform: translateY(0); }
        50% { transform: translateY(-9px); }
    }

    /* =========================================================
       FEATURES
       ========================================================= */

    .tms-section {
        padding: 90px 0;
    }

    .tms-section-light {
        background: #f7faff;
    }

    .tms-section-heading {
        text-align: center;
        max-width: 760px;
        margin: 0 auto 45px;
    }

    .tms-section-heading .tms-eyebrow {
        margin-bottom: 8px;
    }

    .tms-section-heading h2 {
        margin: 0 0 10px;
        color: var(--tms-navy);
        font-size: clamp(28px,4vw,42px);
        line-height: 1.15;
        font-weight: 900;
        letter-spacing: -1px;
    }

    .tms-section-heading p {
        color: var(--tms-muted);
        margin: 0;
        font-size: 15px;
    }

    .tms-feature-grid {
        display: grid;
        grid-template-columns: repeat(3,1fr);
        gap: 20px;
    }

    .tms-feature-card {
        position: relative;
        min-height: 180px;
        background: #fff;
        border: 1px solid #e8edf5;
        border-radius: 16px;
        padding: 25px;
        box-shadow: 0 10px 30px rgba(23,55,96,.06);
        transition: transform .3s ease, box-shadow .3s ease, border-color .3s ease;
    }

    .tms-feature-card:hover {
        transform: translateY(-8px);
        border-color: #cdddf8;
        box-shadow: 0 20px 42px rgba(37,99,235,.12);
    }

    .tms-feature-icon {
        width: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 13px;
        margin-bottom: 17px;
        font-size: 18px;
    }

    .tms-blue-icon {
        background: #e6efff;
        color: #2563eb;
    }

    .tms-green-icon {
        background: #e4f9ef;
        color: #16a765;
    }

    .tms-purple-icon {
        background: #eee9ff;
        color: #7c3aed;
    }

    .tms-orange-icon {
        background: #fff0e6;
        color: #f97316;
    }

    .tms-cyan-icon {
        background: #e3f8fb;
        color: #0891b2;
    }

    .tms-pink-icon {
        background: #ffe8f1;
        color: #e11d65;
    }

    .tms-feature-card h3 {
        margin: 0 0 7px;
        color: var(--tms-navy);
        font-size: 17px;
    }

    .tms-feature-card p {
        margin: 0;
        color: #63738e;
        font-size: 13px;
        line-height: 1.6;
        max-width: 250px;
    }

    .tms-feature-arrow {
        position: absolute;
        right: 22px;
        bottom: 20px;
        color: var(--tms-blue);
        transition: transform .25s ease;
    }

    .tms-feature-card:hover .tms-feature-arrow {
        transform: translateX(5px);
    }

    /* =========================================================
       HOW IT WORKS
       ========================================================= */

    .tms-how {
        background:
            radial-gradient(circle at 80% 50%, rgba(37,99,235,.10), transparent 30%),
            linear-gradient(110deg,#edf5ff,#f7faff);
    }

    .tms-how-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 65px;
        align-items: center;
    }

    .tms-how-content .tms-eyebrow {
        margin-bottom: 8px;
    }

    .tms-how-content h2 {
        color: var(--tms-navy);
        font-size: 38px;
        line-height: 1.15;
        margin: 0 0 10px;
        font-weight: 900;
    }

    .tms-how-content > p {
        color: var(--tms-muted);
        margin: 0 0 35px;
    }

    .tms-steps {
        display: grid;
        grid-template-columns: repeat(3,1fr);
        gap: 12px;
    }

    .tms-step {
        position: relative;
        text-align: center;
    }

    .tms-step-number {
        width: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 12px;
        border-radius: 50%;
        color: #fff;
        font-weight: 900;
        background: linear-gradient(135deg,#2563eb,#6366f1);
        box-shadow: 0 8px 20px rgba(37,99,235,.2);
    }

    .tms-step:nth-child(3) .tms-step-number {
        background: linear-gradient(135deg,#16a765,#27b974);
    }

    .tms-step h4 {
        margin: 0 0 5px;
        color: var(--tms-navy);
        font-size: 14px;
    }

    .tms-step p {
        margin: 0;
        color: #70809a;
        font-size: 11px;
        line-height: 1.5;
    }

    .tms-dashboard-wrap {
        position: relative;
        padding: 20px;
    }

    .tms-dashboard {
        position: relative;
        background: #fff;
        border: 7px solid #102a56;
        border-radius: 13px;
        box-shadow: 0 25px 55px rgba(20,53,99,.18);
        overflow: hidden;
    }

    .tms-dashboard-top {
        height: 32px;
        background: #f4f7fc;
        border-bottom: 1px solid #e6ebf3;
        display: flex;
        align-items: center;
        gap: 5px;
        padding: 0 10px;
    }

    .tms-dashboard-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #c4cfdf;
    }

    .tms-dashboard-body {
        min-height: 270px;
        display: grid;
        grid-template-columns: 72px 1fr;
    }

    .tms-dashboard-sidebar {
        background: #102a56;
        padding: 15px 8px;
    }

    .tms-dashboard-logo {
        color: #fff;
        font-weight: 900;
        font-size: 10px;
        margin-bottom: 22px;
        text-align: center;
    }

    .tms-dashboard-menu {
        height: 8px;
        border-radius: 3px;
        background: rgba(255,255,255,.18);
        margin: 11px 5px;
    }

    .tms-dashboard-main {
        padding: 17px;
        background: #fbfcfe;
    }

    .tms-dashboard-title {
        font-size: 13px;
        font-weight: 900;
        color: #18365f;
        margin-bottom: 14px;
    }

    .tms-stat-row {
        display: grid;
        grid-template-columns: repeat(3,1fr);
        gap: 8px;
        margin-bottom: 13px;
    }

    .tms-stat {
        border: 1px solid #e6ebf3;
        border-radius: 7px;
        background: #fff;
        padding: 10px;
    }

    .tms-stat small {
        display: block;
        color: #8090a8;
        font-size: 7px;
    }

    .tms-stat strong {
        display: block;
        color: #17365f;
        font-size: 16px;
        margin-top: 4px;
    }

    .tms-chart {
        height: 125px;
        background: #fff;
        border: 1px solid #e6ebf3;
        border-radius: 8px;
        position: relative;
        overflow: hidden;
    }

    .tms-chart:before {
        content: "";
        position: absolute;
        left: 20px;
        right: 20px;
        bottom: 25px;
        height: 2px;
        background: #dbe5f4;
        box-shadow:
            0 -28px 0 #edf1f7,
            0 -56px 0 #edf1f7;
    }

    .tms-chart-line {
        position: absolute;
        left: 30px;
        right: 35px;
        top: 48px;
        height: 40px;
        border-top: 3px solid #2563eb;
        transform: skewY(-13deg);
    }

    /* =========================================================
       BUSINESS SECTION
       ========================================================= */

    .tms-business-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 65px;
        align-items: center;
    }

    .tms-business-visual {
        min-height: 330px;
        border-radius: 22px;
        background:
            linear-gradient(135deg,rgba(16,42,86,.88),rgba(37,99,235,.65)),
            linear-gradient(45deg,#ccd9e9,#eef3f9);
        position: relative;
        overflow: hidden;
        box-shadow: 0 25px 50px rgba(21,55,99,.15);
    }

    .tms-cloth {
        position: absolute;
        width: 250px;
        height: 65px;
        border-radius: 50%;
        left: 60px;
        bottom: 72px;
        background: #d9e5f5;
        transform: rotate(-6deg);
        box-shadow: 0 13px 20px rgba(0,0,0,.16);
    }

    .tms-cloth:nth-child(2) {
        left: 85px;
        bottom: 125px;
        background: #8fa9cc;
    }

    .tms-cloth:nth-child(3) {
        left: 115px;
        bottom: 178px;
        background: #5277aa;
    }

    .tms-scissors {
        position: absolute;
        right: 80px;
        bottom: 85px;
        font-size: 85px;
        color: #fff;
        opacity: .85;
        transform: rotate(-25deg);
    }

    .tms-tape {
        position: absolute;
        right: 70px;
        top: 55px;
        width: 110px;
        height: 110px;
        border: 14px solid #f7c94c;
        border-radius: 50%;
        opacity: .9;
    }

    .tms-business-content .tms-eyebrow {
        margin-bottom: 9px;
    }

    .tms-business-content h2 {
        color: var(--tms-navy);
        font-size: 36px;
        line-height: 1.18;
        margin: 0 0 15px;
        font-weight: 900;
    }

    .tms-business-content > p {
        color: var(--tms-muted);
        line-height: 1.75;
        margin-bottom: 22px;
    }

    .tms-benefits {
        display: grid;
        gap: 11px;
    }

    .tms-benefit {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #465a78;
        font-size: 13px;
        font-weight: 600;
    }

    .tms-benefit i {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #e5f0ff;
        color: var(--tms-blue);
        font-size: 10px;
    }

    /* =========================================================
       CTA
       ========================================================= */

    .tms-cta {
        position: relative;
        overflow: hidden;
        background: linear-gradient(120deg,#102a56,#123f91);
        color: #fff;
        text-align: center;
        padding: 70px 20px;
    }

    .tms-cta:before,
    .tms-cta:after {
        content: "";
        position: absolute;
        width: 270px;
        height: 150px;
        border-radius: 50%;
        background: rgba(37,99,235,.35);
    }

    .tms-cta:before {
        left: -90px;
        bottom: -80px;
    }

    .tms-cta:after {
        right: -90px;
        top: -80px;
    }

    .tms-cta-inner {
        position: relative;
        z-index: 2;
    }

    .tms-cta .tms-eyebrow {
        color: #bcd2ff;
    }

    .tms-cta h2 {
        margin: 0 0 9px;
        font-size: clamp(28px,4vw,39px);
        font-weight: 900;
    }

    .tms-cta p {
        margin: 0 auto 22px;
        max-width: 650px;
        color: #c9d8f0;
        font-size: 14px;
    }

    .tms-cta .tms-primary-btn {
        background: linear-gradient(135deg,#2563eb,#7167f2);
    }

    /* =========================================================
       FOOTER
       ========================================================= */

    .tms-footer {
        background: #091c38;
        color: #9cafc9;
        padding: 28px 0 20px;
    }

    .tms-footer-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        padding-bottom: 22px;
        border-bottom: 1px solid rgba(255,255,255,.09);
    }

    .tms-footer .tms-brand {
        color: #fff;
    }

    .tms-footer .tms-brand-icon {
        border-color: #fff;
    }

    .tms-footer .tms-brand-icon:before {
        border-color: #fff;
        background: #091c38;
    }

    .tms-footer .tms-brand-icon:after {
        background: #fff;
    }

    .tms-footer .tms-brand-text small {
        color: #8094b1;
    }

    .tms-footer-links {
        display: flex;
        gap: 28px;
    }

    .tms-footer-links a {
        color: #b8c6da;
        text-decoration: none;
        font-size: 12px;
    }

    .tms-footer-links a:hover {
        color: #fff;
    }

    .tms-footer-bottom {
        padding-top: 17px;
        display: flex;
        justify-content: space-between;
        gap: 20px;
        font-size: 10px;
    }

    /* =========================================================
       URDU / RTL
       ========================================================= */

    [dir="rtl"] .tms-hero h1,
    [dir="rtl"] .tms-section-heading,
    [dir="rtl"] .tms-business-content,
    [dir="rtl"] .tms-how-content {
        letter-spacing: 0;
    }

    [dir="rtl"] .tms-feature-arrow {
        right: auto;
        left: 22px;
    }

    [dir="rtl"] .tms-feature-card:hover .tms-feature-arrow {
        transform: translateX(-5px);
    }

    [dir="rtl"] .tms-brand-text {
        text-align: right;
    }

    [dir="rtl"] .tms-hero-text {
        line-height: 2;
    }

    /* =========================================================
       RESPONSIVE
       ========================================================= */

    @media(max-width: 1050px) {
        .tms-nav-links {
            gap: 16px;
        }

        .tms-hero-inner {
            gap: 25px;
        }

        .tms-login-card {
            right: 5px;
        }

        .tms-feature-grid {
            grid-template-columns: repeat(2,1fr);
        }
    }

    @media(max-width: 850px) {

        .tms-nav-links {
            display: none;
        }

        .tms-hero-inner,
        .tms-how-grid,
        .tms-business-grid {
            grid-template-columns: 1fr;
        }

        .tms-hero {
            padding-bottom: 40px;
        }

        .tms-hero-visual {
            min-height: 430px;
        }

        .tms-login-card {
            right: 20px;
        }

        .tms-how-grid,
        .tms-business-grid {
            gap: 35px;
        }
    }

    @media(max-width: 600px) {

        .tms-shell {
            width: min(100% - 24px, 1180px);
        }

        .tms-navbar-inner {
            min-height: 68px;
        }

        .tms-brand-text small {
            display: none;
        }

        .tms-nav-actions .tms-login-btn {
            padding: 0 13px;
            font-size: 11px;
        }

        .tms-hero-inner {
            padding: 42px 0;
        }

        .tms-hero h1 {
            font-size: 39px;
            letter-spacing: -1px;
        }

        .tms-hero-text {
            font-size: 15px;
        }

        .tms-hero-visual {
            min-height: 390px;
        }

        .tms-workspace {
            right: 0;
        }

        .tms-login-card {
            width: 270px;
            right: 0;
            top: 12px;
        }

        .tms-feature-grid {
            grid-template-columns: 1fr;
        }

        .tms-section {
            padding: 65px 0;
        }

        .tms-how-content h2,
        .tms-business-content h2 {
            font-size: 30px;
        }

        .tms-steps {
            grid-template-columns: 1fr;
            gap: 22px;
        }

        .tms-step p {
            max-width: 220px;
            margin: auto;
        }

        .tms-dashboard-wrap {
            padding: 0;
        }

        .tms-dashboard-body {
            grid-template-columns: 55px 1fr;
        }

        .tms-business-visual {
            min-height: 260px;
        }

        .tms-footer-top,
        .tms-footer-bottom {
            flex-direction: column;
            align-items: flex-start;
        }

        .tms-footer-links {
            gap: 18px;
        }
    }

@endpush


@section('body')

<div class="tms-page">

    {{-- =====================================================
         NAVBAR
         ===================================================== --}}

    <nav class="tms-navbar">
        <div class="tms-shell tms-navbar-inner">

            <a href="{{ route('storefront.index') }}" class="tms-brand">

                <span class="tms-brand-icon"></span>

                <span class="tms-brand-text">
                    <strong>TMS</strong>
                    <small>Tailoring Management System</small>
                </span>

            </a>

            <div class="tms-nav-links">
                <a href="#home">{{ $copy['home'] }}</a>
                <a href="#features">{{ $copy['features'] }}</a>
                <a href="#about">{{ $copy['about'] }}</a>
                <a href="#contact">{{ $copy['contact'] }}</a>
            </div>

            <div class="tms-nav-actions">

                <div class="tms-language">
                    @include('storefront.public.partials.language-switch')
                </div>

                <a href="{{ route('login') }}" class="tms-login-btn">
                    <i class="fas fa-user"></i>
                    {{ $copy['login'] }}
                </a>

            </div>

        </div>
    </nav>


    {{-- =====================================================
         HERO
         ===================================================== --}}

    <header class="tms-hero" id="home">

        <div class="tms-shell tms-hero-inner">

            <div class="tms-hero-content">

                <div class="tms-eyebrow">
                    {{ $copy['eyebrow'] }}
                </div>

                <h1>
                    <span>{{ $copy['hero_title_1'] }}</span><br>
                    {{ $copy['hero_title_2'] }}
                </h1>

                <p class="tms-hero-text">
                    {{ $copy['hero_text'] }}
                </p>

                <div class="tms-hero-buttons">

                    <a href="{{ route('login') }}" class="tms-primary-btn">
                        {{ $copy['get_started'] }}
                        <i class="fas fa-arrow-right"></i>
                    </a>

                    <a href="#features" class="tms-secondary-btn">
                        <i class="far fa-play-circle"></i>
                        {{ $copy['learn_more'] }}
                    </a>

                </div>

                <div class="tms-trust-row">

                    <div class="tms-trust-item">
                        <i class="fas fa-check-circle"></i>
                        {{ $copy['easy'] }}
                    </div>

                    <div class="tms-trust-item">
                        <i class="fas fa-check-circle"></i>
                        {{ $copy['secure'] }}
                    </div>

                    <div class="tms-trust-item">
                        <i class="fas fa-check-circle"></i>
                        {{ $copy['tailors'] }}
                    </div>

                </div>

            </div>


            {{-- Hero Visual --}}

            <div class="tms-hero-visual">

                <div class="tms-workspace">

                    <div class="tms-fabric"></div>

                    <div class="tms-fabric-roll"></div>

                    <div class="tms-measuring-tape"></div>

                    <div class="tms-sewing-machine">

                        <div class="tms-machine-arm"></div>
                        <div class="tms-machine-needle"></div>
                        <div class="tms-machine-body"></div>
                        <div class="tms-machine-base"></div>

                    </div>

                </div>


                {{-- Visual Login Card --}}
                <div class="tms-login-card">

                    <div class="tms-login-heading">

                        <div class="tms-login-icon">
                            <i class="fas fa-user"></i>
                        </div>

                        <div>
                            <strong>{{ $copy['login_title'] }}</strong>
                            <small>{{ $copy['login_subtitle'] }}</small>
                        </div>

                    </div>


                    <div class="tms-demo-field">
                        <i class="far fa-envelope"></i>
                        <span>{{ $copy['email'] }}</span>
                    </div>

                    <div class="tms-demo-field">
                        <i class="fas fa-lock"></i>
                        <span>{{ $copy['password'] }}</span>
                        <i class="far fa-eye" style="margin-left:auto"></i>
                    </div>

                    <div class="tms-login-options">

                        <div class="tms-demo-check">
                            <span></span>
                            {{ $copy['remember'] }}
                        </div>

                        <a href="{{ route('login') }}">
                            {{ $copy['forgot'] }}
                        </a>

                    </div>

                    <a href="{{ route('login') }}"
                       class="tms-card-login"
                       style="display:flex;align-items:center;justify-content:center;text-decoration:none;">
                        <i class="fas fa-sign-in-alt" style="margin-right:7px;"></i>
                        {{ $copy['login'] }}
                    </a>

                </div>

            </div>

        </div>

    </header>


    {{-- =====================================================
         FEATURES
         ===================================================== --}}

    <section class="tms-section" id="features">

        <div class="tms-shell">

            <div class="tms-section-heading">

                <div class="tms-eyebrow">
                    {{ $copy['feature_eyebrow'] }}
                </div>

                <h2>
                    {{ $copy['feature_title'] }}
                </h2>

                <p>
                    {{ $copy['feature_text'] }}
                </p>

            </div>


            <div class="tms-feature-grid">

                <article class="tms-feature-card">

                    <div class="tms-feature-icon tms-blue-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>

                    <h3>{{ $copy['orders'] }}</h3>

                    <p>{{ $copy['orders_text'] }}</p>

                    <span class="tms-feature-arrow">
                        <i class="fas fa-arrow-right"></i>
                    </span>

                </article>


                <article class="tms-feature-card">

                    <div class="tms-feature-icon tms-green-icon">
                        <i class="fas fa-users"></i>
                    </div>

                    <h3>{{ $copy['customers'] }}</h3>

                    <p>{{ $copy['customers_text'] }}</p>

                    <span class="tms-feature-arrow">
                        <i class="fas fa-arrow-right"></i>
                    </span>

                </article>


                <article class="tms-feature-card">

                    <div class="tms-feature-icon tms-purple-icon">
                        <i class="fas fa-cut"></i>
                    </div>

                    <h3>{{ $copy['tailoring'] }}</h3>

                    <p>{{ $copy['tailoring_text'] }}</p>

                    <span class="tms-feature-arrow">
                        <i class="fas fa-arrow-right"></i>
                    </span>

                </article>


                <article class="tms-feature-card">

                    <div class="tms-feature-icon tms-orange-icon">
                        <i class="fas fa-tshirt"></i>
                    </div>

                    <h3>{{ $copy['inventory'] }}</h3>

                    <p>{{ $copy['inventory_text'] }}</p>

                    <span class="tms-feature-arrow">
                        <i class="fas fa-arrow-right"></i>
                    </span>

                </article>


                <article class="tms-feature-card">

                    <div class="tms-feature-icon tms-cyan-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>

                    <h3>{{ $copy['finance'] }}</h3>

                    <p>{{ $copy['finance_text'] }}</p>

                    <span class="tms-feature-arrow">
                        <i class="fas fa-arrow-right"></i>
                    </span>

                </article>


                <article class="tms-feature-card">

                    <div class="tms-feature-icon tms-pink-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>

                    <h3>{{ $copy['reports'] }}</h3>

                    <p>{{ $copy['reports_text'] }}</p>

                    <span class="tms-feature-arrow">
                        <i class="fas fa-arrow-right"></i>
                    </span>

                </article>

            </div>

        </div>

    </section>


    {{-- =====================================================
         HOW IT WORKS
         ===================================================== --}}

    <section class="tms-section tms-how">

        <div class="tms-shell tms-how-grid">

            <div class="tms-how-content">

                <div class="tms-eyebrow">
                    {{ $copy['how_eyebrow'] }}
                </div>

                <h2>
                    {{ $copy['how_title'] }}
                </h2>

                <p>
                    {{ $copy['how_text'] }}
                </p>


                <div class="tms-steps">

                    <div class="tms-step">

                        <div class="tms-step-number">1</div>

                        <h4>{{ $copy['step_login'] }}</h4>

                        <p>{{ $copy['step_login_text'] }}</p>

                    </div>


                    <div class="tms-step">

                        <div class="tms-step-number">2</div>

                        <h4>{{ $copy['step_manage'] }}</h4>

                        <p>{{ $copy['step_manage_text'] }}</p>

                    </div>


                    <div class="tms-step">

                        <div class="tms-step-number">3</div>

                        <h4>{{ $copy['step_grow'] }}</h4>

                        <p>{{ $copy['step_grow_text'] }}</p>

                    </div>

                </div>

            </div>


            {{-- Dashboard Preview --}}

            <div class="tms-dashboard-wrap">

                <div class="tms-dashboard">

                    <div class="tms-dashboard-top">

                        <span class="tms-dashboard-dot"></span>
                        <span class="tms-dashboard-dot"></span>
                        <span class="tms-dashboard-dot"></span>

                    </div>

                    <div class="tms-dashboard-body">

                        <div class="tms-dashboard-sidebar">

                            <div class="tms-dashboard-logo">
                                TMS
                            </div>

                            <div class="tms-dashboard-menu"></div>
                            <div class="tms-dashboard-menu"></div>
                            <div class="tms-dashboard-menu"></div>
                            <div class="tms-dashboard-menu"></div>
                            <div class="tms-dashboard-menu"></div>
                            <div class="tms-dashboard-menu"></div>

                        </div>


                        <div class="tms-dashboard-main">

                            <div class="tms-dashboard-title">
                                TMS Dashboard
                            </div>

                            <div class="tms-stat-row">

                                <div class="tms-stat">
                                    <small>Total Orders</small>
                                    <strong>24</strong>
                                </div>

                                <div class="tms-stat">
                                    <small>Customers</small>
                                    <strong>18</strong>
                                </div>

                                <div class="tms-stat">
                                    <small>Pending</small>
                                    <strong>5</strong>
                                </div>

                            </div>


                            <div class="tms-chart">
                                <div class="tms-chart-line"></div>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>


    {{-- =====================================================
         BUSINESS SECTION
         ===================================================== --}}

    <section class="tms-section" id="about">

        <div class="tms-shell tms-business-grid">


            <div class="tms-business-visual">

                <div class="tms-cloth"></div>
                <div class="tms-cloth"></div>
                <div class="tms-cloth"></div>

                <div class="tms-tape"></div>

                <div class="tms-scissors">
                    <i class="fas fa-cut"></i>
                </div>

            </div>


            <div class="tms-business-content">

                <div class="tms-eyebrow">
                    {{ $copy['business_eyebrow'] }}
                </div>

                <h2>
                    {{ $copy['business_title'] }}
                </h2>

                <p>
                    {{ $copy['business_text'] }}
                </p>


                <div class="tms-benefits">

                    <div class="tms-benefit">
                        <i class="fas fa-check"></i>
                        {{ $copy['benefit_1'] }}
                    </div>

                    <div class="tms-benefit">
                        <i class="fas fa-check"></i>
                        {{ $copy['benefit_2'] }}
                    </div>

                    <div class="tms-benefit">
                        <i class="fas fa-check"></i>
                        {{ $copy['benefit_3'] }}
                    </div>

                    <div class="tms-benefit">
                        <i class="fas fa-check"></i>
                        {{ $copy['benefit_4'] }}
                    </div>

                </div>

            </div>

        </div>

    </section>


    {{-- =====================================================
         CTA
         ===================================================== --}}

    <section class="tms-cta" id="contact">

        <div class="tms-cta-inner">

            <div class="tms-eyebrow">
                {{ $copy['cta_eyebrow'] }}
            </div>

            <h2>
                {{ $copy['cta_title'] }}
            </h2>

            <p>
                {{ $copy['cta_text'] }}
            </p>

            <a href="{{ route('login') }}" class="tms-primary-btn">

                {{ $copy['login'] }}

                <i class="fas fa-arrow-right"></i>

            </a>

        </div>

    </section>


    {{-- =====================================================
         FOOTER
         ===================================================== --}}

    <footer class="tms-footer">

        <div class="tms-shell">

            <div class="tms-footer-top">

                <a href="{{ route('storefront.index') }}" class="tms-brand">

                    <span class="tms-brand-icon"></span>

                    <span class="tms-brand-text">

                        <strong>TMS</strong>

                        <small>
                            Tailoring Management System
                        </small>

                    </span>

                </a>


                <div class="tms-footer-links">

                    <a href="#home">
                        {{ $copy['home'] }}
                    </a>

                    <a href="#features">
                        {{ $copy['features'] }}
                    </a>

                    <a href="#about">
                        {{ $copy['about'] }}
                    </a>

                    <a href="#contact">
                        {{ $copy['contact'] }}
                    </a>

                </div>


                <div class="tms-footer-language">

                    @include('storefront.public.partials.language-switch')

                </div>

            </div>


            <div class="tms-footer-bottom">

                <span>
                    © {{ date('Y') }} TMS.
                    {{ $copy['rights'] }}
                </span>

                <span>
                    {{ $copy['footer_text'] }}
                </span>

            </div>

        </div>

    </footer>

</div>

@endsection