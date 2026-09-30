@php
    $isLoginHeader = $isLoginHeader ?? false;
    $platformHeaderLocale = $platformHeaderLocale ?? app()->getLocale();
    $accountRoute = null;

    if (Auth::check()) {
        $user = Auth::user();
        $accountRoute = match (true) {
            $user->isBusinessMember() => route('admin.home'),
            $user->hasRole('stock_seller') => route('admin.stock.index'),
            $user->hasRole('user') => route('user.shops'),
            $user->hasRole('administrative') => route('administrator.index'),
            default => route('storefront.index'),
        };
    }
@endphp
<nav @class(['tms-nav', 'is-login' => $isLoginHeader, 'is-english' => $platformHeaderLocale === 'en']) aria-label="مرکزی نیویگیشن">
    <div class="shell">
        <a class="tms-brand" href="{{ route('storefront.index') }}" aria-label="BuyNStitch ہوم">
            <span class="tms-mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M8.4 7.9 18.8 3m-10.4 13.1L18.8 21M8.2 12h11.2M8.4 7.9a3.2 3.2 0 1 1-6.4 0 3.2 3.2 0 0 1 6.4 0Zm0 8.2a3.2 3.2 0 1 1-6.4 0 3.2 3.2 0 0 1 6.4 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            <span class="tms-wordmark" lang="en"><strong>BuyNStitch</strong><small>Tailoring &amp; Clothing Business Platform</small></span>
        </a>
        <div class="nav-center">
            <a href="{{ route('storefront.about') }}" @class(['is-active' => request()->routeIs('storefront.about')])><span lang="en">About us</span><span class="nav-separator" aria-hidden="true">·</span><span lang="ur" dir="rtl">ہمارے بارے میں</span></a>
            <a href="{{ route('storefront.index') }}#features"><span lang="en">Features</span><span class="nav-separator" aria-hidden="true">·</span><span lang="ur" dir="rtl">خصوصیات</span></a>
            <a href="{{ route('storefront.index') }}#workspaces"><span lang="en">Solutions</span><span class="nav-separator" aria-hidden="true">·</span><span lang="ur" dir="rtl">حل</span></a>
            <a href="{{ route('storefront.index') }}#marketplace"><span lang="en">Marketplace</span><span class="nav-separator" aria-hidden="true">·</span><span lang="ur" dir="rtl">مارکیٹ</span></a>
            <a href="{{ route('storefront.index') }}#how"><span lang="en">How it works</span><span class="nav-separator" aria-hidden="true">·</span><span lang="ur" dir="rtl">طریقہ</span></a>
        </div>
        <div class="nav-actions">
            @if ($isLoginHeader)
                <a class="nav-login" lang="ur" dir="rtl" href="{{ route('storefront.index') }}"><span aria-hidden="true">←</span> واپس جائیں</a>
            @else
                @include('storefront.public.partials.language-switch')
                @auth
                    <a class="nav-account" href="{{ $accountRoute }}" title="Open your account">
                        <span class="nav-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}</span>
                        <span>{{ Auth::user()->name }}</span>
                        <span class="account-arrow" aria-hidden="true">→</span>
                    </a>
                @else
                    <a class="nav-login" href="{{ route('login') }}">{{ $platformHeaderLocale === 'ur' ? 'لاگ اِن' : 'Login' }}</a>
                    @if (config('demo.enabled'))
                        <a class="nav-demo" href="{{ route('login', ['demo' => 1]) }}">{{ $platformHeaderLocale === 'ur' ? 'ڈیمو دیکھیں' : 'Try Demo' }} <span aria-hidden="true">→</span></a>
                    @endif
                @endauth
            @endif
        </div>
    </div>
</nav>
