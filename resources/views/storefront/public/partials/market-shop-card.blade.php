<article class="card shop">
    <div class="cover" @if($storefront->cover_url) style="background-image:url('{{ $storefront->cover_url }}')" @endif>
        @unless($storefront->cover_url)<div class="cover-placeholder">@include('storefront.public.partials.icon', ['name' => 'store'])</div>@endunless
    </div>
    @isset($rank)<span class="shop-rank">{{ $rank }}</span>@endisset
    <div class="shop-body">
        <h3>{{ $storefront->localizedName() }}</h3>
        <div class="shop-city">@include('storefront.public.partials.icon', ['name' => 'map-pin']) {{ $storefront->localized('city') ?: __('storefront.common.pakistan') }}</div>
        <p>{{ $storefront->localized('tagline') ?: __('storefront.marketplace.default_tagline') }}</p>
        <div class="tags">
            @if($storefront->show_clothing && $storefront->business->clothing_enabled)<span class="pill">{{ __('storefront.common.clothing') }}</span>@endif
            @if($storefront->show_tailoring && $storefront->business->tailoring_enabled)<span class="pill">{{ __('storefront.common.tailoring') }}</span>@endif
            @if($storefront->offersDelivery())<span class="pill">{{ __('storefront.common.delivery') }}</span>@endif
        </div>
        <a class="btn" href="{{ route('storefront.show',$storefront) }}">{{ __('storefront.marketplace.view_shop') }}</a>
    </div>
</article>
