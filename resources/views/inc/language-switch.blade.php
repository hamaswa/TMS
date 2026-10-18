{{-- Urdu / English switch. variant: "nav" (dark top bar) or "floating" (pages without the top bar). --}}
@php($currentUiLocale = $uiLocale ?? 'ur')
<div class="tms-lang-switch tms-lang-switch--{{ $variant ?? 'nav' }}" translate="no" role="group" aria-label="Language / زبان">
    <a href="{{ route('ui.locale', 'ur') }}" lang="ur" class="{{ $currentUiLocale === 'ur' ? 'is-active' : '' }}"
        @if ($currentUiLocale === 'ur') aria-current="true" @endif>اردو</a>
    <a href="{{ route('ui.locale', 'en') }}" lang="en" class="{{ $currentUiLocale === 'en' ? 'is-active' : '' }}"
        @if ($currentUiLocale === 'en') aria-current="true" @endif>English</a>
</div>
@once
    <style>
        .tms-lang-switch { display: inline-flex; align-items: center; gap: 2px; margin: .45rem .35rem; padding: 3px; border: 1px solid rgba(255, 255, 255, .28); border-radius: 999px; background: rgba(255, 255, 255, .08); direction: ltr; }
        .tms-lang-switch a { padding: 3px 12px; border-radius: 999px; color: rgba(255, 255, 255, .85); font-family: Arial, sans-serif; font-size: .82rem; font-weight: 700; line-height: 1.6; text-decoration: none; white-space: nowrap; }
        .tms-lang-switch a[lang="ur"] { font-family: "Noto Nastaliq Urdu", "Noto Sans Arabic", Tahoma, sans-serif; font-size: .8rem; }
        .tms-lang-switch a:hover, .tms-lang-switch a:focus { color: #fff; text-decoration: none; }
        .tms-lang-switch a.is-active { background: #fff; color: #174f78; }
        .tms-lang-switch--floating { position: fixed; top: 14px; left: 14px; z-index: 1080; border-color: #d5dfeb; background: #fff; box-shadow: 0 6px 18px rgba(16, 42, 67, .12); }
        [dir="ltr"] .tms-lang-switch--floating { right: 14px; left: auto; }
        .tms-lang-switch--floating a { color: #40566f; }
        .tms-lang-switch--floating a.is-active { background: #174f78; color: #fff; }
    </style>
@endonce
