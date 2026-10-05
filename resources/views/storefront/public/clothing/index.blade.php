@extends('storefront.public.layout')
@section('title', (isset($collection) ? $collection->localizedName() : __('storefront.clothing.catalog_title')).' — '.$storefront->localizedName())
@section('meta_description', isset($collection) ? ($collection->localizedDescription() ?: __('storefront.clothing.catalog_intro')) : __('storefront.clothing.catalog_intro'))
@section('canonical_url', isset($collection) ? route('storefront.collections.show',[$storefront,$collection]) : route('storefront.clothing.index',$storefront))
@section('meta_image', $storefront->cover_url ?: $storefront->logo_url ?: '')
@push('structured_data')
<script type="application/ld+json">{!! \App\Support\StorefrontSeo::json(\App\Support\StorefrontSeo::graph(
    \App\Support\StorefrontSeo::collection(
        __('storefront.clothing.catalog_title').' — '.$storefront->localizedName(),
        __('storefront.clothing.catalog_intro'),
        route('storefront.clothing.index',$storefront),
        $storefront
    )
)) !!}</script>
@endpush
@push('styles')
@include('storefront.public.partials.shop-navigation-styles')
.filters{margin:-24px auto 30px;position:relative}.filter-grid{display:grid;grid-template-columns:2fr repeat(5,minmax(120px,1fr));gap:12px;align-items:end}.filter-field label{display:block;font-size:.82rem;font-weight:800;margin-bottom:6px}.filter-actions{display:flex;gap:8px;grid-column:1/-1}.storefront-product-grid{grid-template-columns:repeat({{ in_array((int)$storefront->product_columns,[2,3,4],true) ? (int)$storefront->product_columns : 3 }},minmax(0,1fr))}.product-card{padding:0;overflow:hidden}.photo{height:235px;background:linear-gradient(135deg,#e2eee9,#f2f7f4);display:grid;place-items:center;font-size:3rem}.photo img{width:100%;height:100%;object-fit:cover}.product-body{padding:19px}.colors{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}.product-body .btn{display:block;margin-top:14px}@media(max-width:1050px){.filter-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.storefront-product-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:600px){.filter-grid,.storefront-product-grid{grid-template-columns:1fr}}
.storefront-product-grid{align-items:stretch;grid-auto-rows:1fr}
.storefront-product-grid .product-card{display:flex;flex-direction:column;min-width:0;height:100%}
.product-card .photo{position:relative;height:235px;min-height:235px;flex:0 0 235px;overflow:hidden}
.product-card .photo img{position:absolute;inset:0;display:block;width:100%;height:100%;object-fit:cover}
.product-card .product-body{display:flex;flex-direction:column;flex:1;min-width:0;background:var(--shop-surface)}
.product-card .product-body h2{overflow-wrap:anywhere}
.product-card .product-body .btn{margin-top:auto;padding-top:10px;padding-bottom:10px}
.product-card .colors{margin-bottom:14px}
.product-card .product-body>:last-child{margin-top:auto}
@endpush
@section('body')
@include('storefront.public.partials.shop-navigation')
<header class="hero"><div class="shell"><h1>{{ isset($collection) ? $collection->localizedName() : __('storefront.clothing.catalog_title') }}</h1><p>{{ isset($collection) ? ($collection->localizedDescription() ?: __('storefront.clothing.catalog_intro')) : __('storefront.clothing.catalog_intro') }}</p></div></header>
<main class="shell section">
@if(!isset($collection))
<form class="card filters" method="GET"><div class="filter-grid">
<div class="filter-field"><label for="catalog_q">{{ __('storefront.clothing.search_label') }}</label><input id="catalog_q" class="control" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('storefront.clothing.search_placeholder') }}"></div>
<div class="filter-field"><label for="catalog_type">{{ __('storefront.clothing.type_label') }}</label><select id="catalog_type" class="control" name="type"><option value="">{{ __('storefront.clothing.all_types') }}</option>@foreach($types as $type)<option value="{{ $type->id }}" @selected((string)($filters['type']??'')===(string)$type->id)>{{ $type->localizedName() }}</option>@endforeach</select></div>
<div class="filter-field"><label for="catalog_color">{{ __('storefront.clothing.color_label') }}</label><select id="catalog_color" class="control" name="color"><option value="">{{ __('storefront.clothing.all_colors') }}</option>@foreach($colors as $color)<option value="{{ $color }}" @selected(($filters['color']??'')===$color)>{{ $color }}</option>@endforeach</select></div>
<div class="filter-field"><label for="catalog_min_price">{{ __('storefront.clothing.min_price') }}</label><input id="catalog_min_price" type="number" min="0" max="10000000" step="1" inputmode="numeric" dir="ltr" class="control" name="min_price" value="{{ $filters['min_price'] ?? '' }}"></div>
<div class="filter-field"><label for="catalog_max_price">{{ __('storefront.clothing.max_price') }}</label><input id="catalog_max_price" type="number" min="0" max="10000000" step="1" inputmode="numeric" dir="ltr" class="control" name="max_price" value="{{ $filters['max_price'] ?? '' }}"></div>
<div class="filter-field"><label for="catalog_availability">{{ __('storefront.clothing.availability_label') }}</label><select id="catalog_availability" class="control" name="availability"><option value="">{{ __('storefront.clothing.any_availability') }}</option><option value="in_stock" @selected(($filters['availability']??'')==='in_stock')>{{ __('storefront.clothing.in_stock_only') }}</option></select></div>
<div class="filter-actions"><button class="btn">{{ __('storefront.clothing.apply_filters') }}</button>@if(collect($filters)->contains(fn($value)=>$value!==null && $value!==''))<a class="btn btn-secondary" href="{{ route('storefront.clothing.index',$storefront) }}">{{ __('storefront.clothing.clear_filters') }}</a>@endif</div>
</div></form>
@endif
<div class="grid storefront-product-grid">@forelse($listings as $listing)
@php($image=$listing->cloth->images->first(fn($item)=>$item->image_url)) @php($available=(float)$listing->cloth->colors->sum(fn($color)=>$color->reservableLength()))
<article class="card product-card"><div class="photo">@if($image)<img src="{{ $image->image_url }}" alt="{{ $listing->localizedName() }}">@else @include('storefront.public.partials.icon', ['name' => 'shirt', 'class' => 'is-xl']) @endif</div><div class="product-body">@if($listing->is_featured)<div class="featured">{{ __('storefront.clothing.featured') }}</div>@endif<h2>{{ $listing->localizedName() }}</h2><div class="muted">{{ $listing->cloth->brand->name ?? __('storefront.clothing.no_brand') }} · {{ $listing->cloth->type->localizedName() ?? __('storefront.clothing.fabric') }}</div><div class="price">{!! \App\Support\PakistanCurrency::html($listing->cloth->sale_price ?: $listing->cloth->price) !!} {{ __('storefront.clothing.per_metre') }}</div><div>@if(!$listing->is_available){{ __('storefront.clothing.temporarily_unavailable') }}@elseif($available>0){{ __('storefront.clothing.available',['amount'=>number_format($available,2)]) }}@elseif($listing->preorder_enabled){{ __('storefront.clothing.preorder_available') }}@else{{ __('storefront.clothing.out_of_stock') }}@endif</div>@if(!$listing->online_order_enabled)<div class="muted">{{ __('storefront.clothing.catalogue_badge') }}</div>@endif @if($listing->cloth->hasSelectableColors())<div class="colors">@foreach($listing->cloth->selectableColorNames() as $color)<span class="pill">{{ $color }}@if($listing->cloth->tracksColors()) — {{ number_format($listing->cloth->colors->firstWhere('color',$color)?->reservableLength() ?? 0,2) }}m @endif</span>@endforeach</div>@endif<a class="btn" href="{{ route('storefront.clothing.show',[$storefront,$listing]) }}">{{ __('storefront.clothing.view_details') }}</a></div></article>
@empty<div class="card empty" style="grid-column:1/-1"><h2>{{ __('storefront.clothing.empty_title') }}</h2><p>{{ __('storefront.clothing.empty_text') }}</p></div>@endforelse</div>
@if($listings->hasPages())<div style="margin-top:25px">{{ $listings->links() }}</div>@endif
</main>
@include('storefront.public.partials.shop-footer')
@endsection
