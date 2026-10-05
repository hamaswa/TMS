@php
    $footerStyle = in_array($storefront->footer_style, ['simple','columns','brand'], true) ? $storefront->footer_style : 'columns';
    $footerLinks = collect($storefront->navigation_links ?? [])->filter(
        fn ($link) => in_array($link['location'] ?? 'both', ['footer','both'], true)
    );
    $configuredFooterLinks = $storefront->menuItems()->whereNull('parent_id')->where('is_visible', true)->whereIn('location', ['footer','both'])->with('collection')->get()->filter->isPubliclyAvailable()->values();
    $socialLinks = collect([
        'Facebook' => $storefront->facebook_url,
        'Instagram' => $storefront->instagram_url,
        'TikTok' => $storefront->tiktok_url,
        'YouTube' => $storefront->youtube_url,
    ])->filter();
@endphp
<footer class="shop-footer style-{{ $footerStyle }}">
    <div class="shell shop-footer-grid">
        <div class="shop-footer-brand">
            <a href="{{ route('storefront.show', $storefront) }}">@if($storefront->logo_url)<img src="{{ $storefront->logo_url }}" alt="">@endif<strong>{{ $storefront->localizedName() }}</strong></a>
            <p>{{ $storefront->localized('footer_text') ?: $storefront->localized('tagline') ?: __('storefront.home.default_tagline') }}</p>
        </div>
        @if($footerStyle !== 'simple' && ($configuredFooterLinks->isNotEmpty() || $footerLinks->isNotEmpty()))<nav class="shop-footer-links" aria-label="Footer">@if($configuredFooterLinks->isNotEmpty())@foreach($configuredFooterLinks as $link)<a href="{{ $link->publicUrl() }}" @if($link->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif>{{ $link->localizedLabel() }}</a>@endforeach @else @foreach($footerLinks as $link)@php($label=$link['label_'.app()->getLocale()] ?? $link['label_'.$storefront->default_locale] ?? $link['label_en'] ?? $link['label_ur'] ?? '')@if($label && !empty($link['url']))<a href="{{ $link['url'] }}" @if($link['new_tab'] ?? false) target="_blank" rel="noopener noreferrer" @endif>{{ $label }}</a>@endif @endforeach @endif</nav>@endif
        @if($footerStyle !== 'simple')<div class="shop-footer-contact">@if($storefront->public_phone)<a dir="ltr" href="tel:{{ preg_replace('/[^0-9+]/','',$storefront->public_phone) }}">{{ $storefront->public_phone }}</a>@endif @if($storefront->public_email)<a dir="ltr" href="mailto:{{ $storefront->public_email }}">{{ $storefront->public_email }}</a>@endif @if($socialLinks->isNotEmpty())<div class="shop-socials">@foreach($socialLinks as $label=>$url)<a href="{{ $url }}" target="_blank" rel="noopener noreferrer">{{ $label }}</a>@endforeach</div>@endif</div>@endif
    </div>
    <div class="shell shop-footer-bottom"><span>© {{ now()->year }} {{ $storefront->localizedName() }}</span><a href="{{ route('storefront.index') }}">BuyNStitch</a></div>
</footer>
