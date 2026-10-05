<header class="hero store-hero is-fabric"><div class="shell">
    <div class="store-hero-content">
        <div class="badges"><span class="badge">@include('storefront.public.partials.icon', ['name' => 'check-circle']) {{ __('storefront.home.active_business') }}</span>@if($storefront->offersPickup())<span class="badge">{{ __('storefront.common.pickup') }}</span>@endif</div>
        <h1>{{ $storefront->localized('hero_title') ?: $storefront->localizedName() }}</h1>
        <p>{{ $storefront->localized('hero_text') ?: $storefront->localized('tagline') ?: __('storefront.home.default_tagline') }}</p>
        <div class="hero-actions"><a class="hero-btn" href="{{ $clothingUrl }}">@include('storefront.public.partials.icon', ['name' => 'bag']) {{ __('storefront.home.shop_now') }}</a>@if($storefront->show_tailoring)<a class="hero-btn is-outline" href="{{ $tailoringUrl }}">{{ __('storefront.home.view_tailoring') }}</a>@endif</div>
    </div>
    <div class="fabric-book">
        @foreach(range(0,4) as $swatch)
            @php($swatchListing = $storefront->clothingListings->values()->get($swatch))
            @php($swatchImage = $swatchListing?->cloth?->images?->first(fn ($item) => $item->image_url))
            <a class="fabric-swatch fabric-texture" style="--swatch:{{ $swatch - 2 }}" href="{{ $swatchListing && ! $preview ? route('storefront.clothing.show', [$storefront, $swatchListing]) : $clothingUrl }}" aria-label="{{ $swatchListing?->localizedName() ?: __('storefront.home.all_products') }}">@if($swatchImage)<img src="{{ $swatchImage->image_url }}" alt="">@endif</a>
        @endforeach
        <span class="fabric-book-label">{{ __('storefront.home.browse_by_type') }} · {{ __('storefront.home.browse_by_brand') }}</span>
    </div>
</div></header>
