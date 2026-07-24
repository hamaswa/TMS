<style>
    .locale-switch{display:inline-flex;align-items:center;gap:7px;direction:ltr;white-space:nowrap;font-family:Arial,sans-serif}
    .locale-switch a{color:inherit;text-decoration:none;opacity:.72;padding:4px 7px;border-radius:7px}
    .locale-switch a.active{opacity:1;background:rgba(255,255,255,.16);font-weight:800}
</style>
<div class="locale-switch" role="group" aria-label="{{ __('storefront.language.label') }}">
    <a lang="ur" dir="rtl" href="{{ route('public.locale.update', ['locale' => 'ur', 'redirect' => request()->getRequestUri()]) }}" @class(['active' => app()->getLocale() === 'ur'])>اردو</a>
    <span aria-hidden="true">|</span>
    <a lang="en" dir="ltr" href="{{ route('public.locale.update', ['locale' => 'en', 'redirect' => request()->getRequestUri()]) }}" @class(['active' => app()->getLocale() === 'en'])>English</a>
</div>
