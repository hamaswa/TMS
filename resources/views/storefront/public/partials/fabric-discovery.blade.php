@if($storefront->show_clothing && ($clothTypes->isNotEmpty() || $clothBrands->isNotEmpty()))
<section class="section fabric-discovery"><div class="shell">
    @if(($storefront->show_nav_categories ?? true) && $clothTypes->isNotEmpty())
    <div class="section-heading"><h2>{{ __('storefront.home.browse_by_type') }}</h2><a class="section-link" href="{{ $clothingUrl }}">{{ __('storefront.home.all_products') }}</a></div>
    <div class="fabric-types">
        @foreach($clothTypes as $type)
            @php($typeListing = $storefront->clothingListings->first(fn ($item) => $item->cloth?->cloth_type_id == $type->id))
            @php($typeImage = $typeListing?->cloth?->images?->first(fn ($item) => $item->image_url))
            <a class="fabric-type" href="{{ $clothingUrl }}?type={{ $type->id }}"><span class="fabric-type-art" aria-hidden="true">@if($typeImage)<img src="{{ $typeImage->image_url }}" alt="" loading="lazy">@else @include('storefront.public.partials.icon', ['name' => 'shirt', 'class' => 'is-xl']) @endif</span><strong>{{ $type->localizedName() }}</strong></a>
        @endforeach
    </div>
    @endif
    @if(($storefront->show_nav_brands ?? true) && $clothBrands->isNotEmpty())
    <div class="fabric-brands"><h3>{{ __('storefront.home.browse_by_brand') }}</h3>@foreach($clothBrands as $brand)<a href="{{ $clothingUrl }}?q={{ urlencode($brand->name) }}">{{ $brand->name }}</a>@endforeach</div>
    @endif
</div></section>
@endif
