@php
    $isPreview = $preview ?? false;
    $shopHomeUrl = $isPreview ? route('admin.storefront.preview') : route('storefront.show', $storefront);
    $clothingUrl = $isPreview && ! $storefront->is_published
        ? route('admin.storefront.clothing.index')
        : route('storefront.clothing.index', $storefront);
    $tailoringUrl = $isPreview && ! $storefront->is_published
        ? route('admin.storefront.tailoring.services')
        : route('storefront.tailoring.index', $storefront);
    $menuTypes = $clothTypes ?? $types ?? null;
    $ownerId = $storefront->business?->owner_user_id;
    $publishedClothRows = null;
    if (($menuTypes === null || !isset($clothBrands)) && $ownerId) {
        $publishedClothRows = $storefront->clothingListings()
            ->where('is_published', true)
            ->join('cloths', 'cloths.id', '=', 'storefront_clothing_listings.cloth_id')
            ->where('cloths.user_id', $ownerId)
            ->whereNull('cloths.deleted_at')
            ->get(['cloths.cloth_type_id', 'cloths.cloth_brand_id']);
    }
    $menuTypes ??= \App\Models\ClothType::query()
        ->where('user_id', $ownerId)
        ->whereIn('id', $publishedClothRows?->pluck('cloth_type_id')->filter()->unique() ?? [])
        ->orderBy('name')->get();
    $menuBrands = $clothBrands ?? \App\Models\ClothBrand::query()
        ->where('user_id', $ownerId)
        ->whereIn('id', $publishedClothRows?->pluck('cloth_brand_id')->filter()->unique() ?? [])
        ->orderBy('name')->get();
    $configuredMenuItems = $storefront->menuItems()
        ->whereNull('parent_id')->where('is_visible', true)
        ->whereIn('location', ['header','both'])
        ->with(['collection', 'children' => fn ($query) => $query->where('is_visible', true)->whereIn('location', ['header','both'])->with('collection')])
        ->get()
        ->each(fn ($item) => $item->setRelation('children', $item->children->filter->isPubliclyAvailable()->values()))
        ->filter->isPubliclyAvailable()->values();
@endphp
@if($storefront->localized('announcement'))<div class="shop-announcement">{{ $storefront->localized('announcement') }}</div>@endif
<div @class(['shop-header', 'is-sticky' => ($storefront->sticky_header ?? true)])>
    <nav class="nav shop-nav"><div class="shell">
        <a class="nav-brand" href="{{ $shopHomeUrl }}">@if($storefront->logo_url)<img class="logo" src="{{ $storefront->logo_url }}" alt="{{ __('storefront.home.logo_alt',['shop'=>$storefront->localizedName()]) }}">@else<span class="logo" style="display:grid;place-items:center">@include('storefront.public.partials.icon', ['name' => 'store', 'class' => 'is-lg'])</span>@endif <span>{{ $storefront->localizedName() }}</span></a>
        <div class="nav-actions">@include('storefront.public.partials.language-switch')
            @if(!$isPreview && $storefront->show_clothing && $storefront->clothingOrderingEnabled())<a class="nav-link cart-link" href="{{ route('storefront.cart.show',$storefront) }}">@include('storefront.public.partials.icon', ['name' => 'cart'])<span>{{ __('storefront.clothing.cart') }}</span></a>@endif
            <a class="nav-link all-shops-link" href="{{ route('storefront.index') }}">@include('storefront.public.partials.icon', ['name' => 'grid'])<span>{{ __('storefront.common.all_shops') }}</span></a>
        </div>
    </div></nav>
    <nav class="category-nav" aria-label="{{ __('storefront.home.shop_categories') }}"><div class="shell header-layout-{{ $storefront->header_layout === 'search_first' ? 'search_first' : 'menu_first' }}">
        <div class="menu-scroll">
            <a @class(['category-link','is-active'=>request()->routeIs('storefront.show')]) href="{{ $shopHomeUrl }}">@include('storefront.public.partials.icon', ['name' => 'store']){{ __('storefront.home.nav_home') }}</a>
            @if($configuredMenuItems->isNotEmpty())
                @foreach($configuredMenuItems as $menuItem)
                    @if($menuItem->children->isNotEmpty())
                        <details class="category-drop"><summary>{{ $menuItem->localizedLabel() }} @include('storefront.public.partials.icon', ['name' => 'chevron-down'])</summary><div class="category-menu">@foreach($menuItem->children as $child)<a href="{{ $child->publicUrl() }}" @if($child->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif><span>{{ $child->localizedLabel() }}</span>@include('storefront.public.partials.icon', ['name' => 'chevron-left'])</a>@endforeach</div></details>
                    @else
                        <a class="category-link" href="{{ $menuItem->publicUrl() }}" @if($menuItem->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif>{{ $menuItem->localizedLabel() }} @if($menuItem->open_in_new_tab)@include('storefront.public.partials.icon', ['name' => 'external-link'])@endif</a>
                    @endif
                @endforeach
            @else
            @if($storefront->show_clothing)<a @class(['category-link','is-active'=>request()->routeIs('storefront.clothing.*')]) href="{{ $clothingUrl }}">@include('storefront.public.partials.icon', ['name' => 'bag']){{ __('storefront.home.all_products') }}</a>@endif
            @if(($storefront->show_nav_categories ?? true) && $menuTypes->isNotEmpty())<details class="category-drop"><summary>@include('storefront.public.partials.icon', ['name' => 'grid']){{ __('storefront.home.nav_categories') }} @include('storefront.public.partials.icon', ['name' => 'chevron-down'])</summary><div class="category-menu">@foreach($menuTypes as $type)<a href="{{ $clothingUrl }}?type={{ $type->id }}"><span>@include('storefront.public.partials.icon', ['name' => 'tag']){{ $type->localizedName() }}</span>@include('storefront.public.partials.icon', ['name' => 'chevron-left'])</a>@endforeach</div></details>@endif
            @if(($storefront->show_nav_brands ?? true) && $menuBrands->isNotEmpty())<details class="category-drop"><summary>@include('storefront.public.partials.icon', ['name' => 'copyright']){{ __('storefront.home.nav_brands') }} @include('storefront.public.partials.icon', ['name' => 'chevron-down'])</summary><div class="category-menu">@foreach($menuBrands as $brand)<a href="{{ $clothingUrl }}?q={{ urlencode($brand->name) }}"><span>{{ $brand->name }}</span>@include('storefront.public.partials.icon', ['name' => 'chevron-left'])</a>@endforeach</div></details>@endif
            @if($storefront->show_tailoring)<a @class(['category-link','is-active'=>request()->routeIs('storefront.tailoring.*')]) href="{{ $tailoringUrl }}">@include('storefront.public.partials.icon', ['name' => 'scissors']){{ __('storefront.common.tailoring') }}</a>@endif
            @if($storefront->show_about ?? true)<a class="category-link" href="{{ $shopHomeUrl }}#shop-contact">@include('storefront.public.partials.icon', ['name' => 'phone']){{ __('storefront.home.contact') }}</a>@endif
            @foreach($storefront->navigation_links ?? [] as $customLink)
                @php($customLabel = $customLink['label_'.app()->getLocale()] ?? $customLink['label_'.$storefront->default_locale] ?? $customLink['label_en'] ?? $customLink['label_ur'] ?? '')
                @if($customLabel && !empty($customLink['url']) && in_array($customLink['location'] ?? 'both', ['header','both'], true))<a class="category-link" href="{{ $customLink['url'] }}" @if($customLink['new_tab'] ?? false) target="_blank" rel="noopener noreferrer" @endif>{{ $customLabel }} @if($customLink['new_tab'] ?? false)@include('storefront.public.partials.icon', ['name' => 'external-link'])@endif</a>@endif
            @endforeach
            @endif
        </div>
        @if($storefront->show_clothing)<form class="shop-search" action="{{ $clothingUrl }}" method="GET"><input name="q" aria-label="{{ __('storefront.home.search_products') }}" placeholder="{{ __('storefront.home.search_placeholder') }}"><button type="submit" aria-label="{{ __('storefront.home.search_products') }}">@include('storefront.public.partials.icon', ['name' => 'search'])</button></form>@endif
    </div></nav>
</div>
@push('scripts')
<script>
(() => {
    const menus = [...document.querySelectorAll('.shop-header .category-drop')];
    menus.forEach(menu => menu.querySelector('summary').addEventListener('click', () => {
        menus.forEach(other => { if (other !== menu) other.open = false; });
    }));
    document.addEventListener('click', event => {
        menus.forEach(menu => { if (!menu.contains(event.target)) menu.open = false; });
    });
    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;
        menus.forEach(menu => {
            if (menu.open) { menu.open = false; menu.querySelector('summary').focus(); }
        });
    });
})();
</script>
@endpush
