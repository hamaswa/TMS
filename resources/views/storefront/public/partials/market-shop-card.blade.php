<article class="card shop">
    <div class="cover" @if($storefront->cover_url) style="background-image:url('{{ $storefront->cover_url }}')" @endif>
        @unless($storefront->cover_url)<div class="cover-placeholder"><i class="fas fa-store-alt"></i></div>@endunless
    </div>
    @isset($rank)<span class="shop-rank">{{ $rank }}</span>@endisset
    <div class="shop-body">
        <h3>{{ $storefront->display_name }}</h3>
        <div class="shop-city"><i class="fas fa-map-marker-alt"></i> {{ $storefront->city ?: __('storefront.common.pakistan') }}</div>
        <p>{{ $storefront->tagline ?: __('storefront.marketplace.default_tagline') }}</p>
        <div class="tags">
            @if($storefront->show_clothing && $storefront->business->clothing_enabled)<span class="pill">{{ __('storefront.common.clothing') }}</span>@endif
            @if($storefront->show_tailoring && $storefront->business->tailoring_enabled)<span class="pill">{{ __('storefront.common.tailoring') }}</span>@endif
            @if($storefront->offersDelivery())<span class="pill">{{ __('storefront.common.delivery') }}</span>@endif
        </div>
        <a class="btn" href="{{ route('storefront.show',$storefront) }}">{{ __('storefront.marketplace.view_shop') }}</a>
    </div>
</article>
