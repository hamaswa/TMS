@extends('storefront.public.layout')
@section('title', __('storefront.clothing.catalog_title').' — '.$storefront->display_name)
@section('meta_description', __('storefront.clothing.catalog_intro'))
@section('canonical_url', route('storefront.clothing.index',$storefront))
@section('meta_image', $storefront->cover_url ?: $storefront->logo_url ?: '')
@push('structured_data')
<script type="application/ld+json">{!! \App\Support\StorefrontSeo::json(\App\Support\StorefrontSeo::graph(
    \App\Support\StorefrontSeo::collection(
        __('storefront.clothing.catalog_title').' — '.$storefront->display_name,
        __('storefront.clothing.catalog_intro'),
        route('storefront.clothing.index',$storefront),
        $storefront
    )
)) !!}</script>
@endpush
@push('styles')
.filters{margin:-24px auto 30px;position:relative}.filter-grid{display:grid;grid-template-columns:2fr repeat(5,minmax(120px,1fr));gap:12px;align-items:end}.filter-field label{display:block;font-size:.82rem;font-weight:800;margin-bottom:6px}.filter-actions{display:flex;gap:8px;grid-column:1/-1}.product-card{padding:0;overflow:hidden}.photo{height:235px;background:linear-gradient(135deg,#e2eee9,#f2f7f4);display:grid;place-items:center;font-size:3rem}.photo img{width:100%;height:100%;object-fit:cover}.product-body{padding:19px}.colors{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}.product-body .btn{display:block;margin-top:14px}@media(max-width:1050px){.filter-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}@media(max-width:600px){.filter-grid{grid-template-columns:1fr}}
@endpush
@section('body')
<nav class="nav"><div class="shell"><a class="nav-brand" href="{{ route('storefront.show',$storefront) }}">{{ $storefront->display_name }}</a><div class="nav-actions">@include('storefront.public.partials.language-switch')<a class="nav-link" href="{{ route('storefront.cart.show',$storefront) }}">{{ __('storefront.clothing.cart') }}</a><a class="nav-link" href="{{ route('storefront.show',$storefront) }}">{{ __('storefront.common.shop_home') }}</a></div></div></nav>
<header class="hero"><div class="shell"><h1>{{ __('storefront.clothing.catalog_title') }}</h1><p>{{ __('storefront.clothing.catalog_intro') }}</p></div></header>
<main class="shell section">
<form class="card filters" method="GET"><div class="filter-grid">
<div class="filter-field"><label for="catalog_q">{{ __('storefront.clothing.search_label') }}</label><input id="catalog_q" class="control" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('storefront.clothing.search_placeholder') }}"></div>
<div class="filter-field"><label for="catalog_type">{{ __('storefront.clothing.type_label') }}</label><select id="catalog_type" class="control" name="type"><option value="">{{ __('storefront.clothing.all_types') }}</option>@foreach($types as $type)<option value="{{ $type->id }}" @selected((string)($filters['type']??'')===(string)$type->id)>{{ $type->name }}</option>@endforeach</select></div>
<div class="filter-field"><label for="catalog_color">{{ __('storefront.clothing.color_label') }}</label><select id="catalog_color" class="control" name="color"><option value="">{{ __('storefront.clothing.all_colors') }}</option>@foreach($colors as $color)<option value="{{ $color }}" @selected(($filters['color']??'')===$color)>{{ $color }}</option>@endforeach</select></div>
<div class="filter-field"><label for="catalog_min_price">{{ __('storefront.clothing.min_price') }}</label><input id="catalog_min_price" type="number" min="0" max="10000000" step="1" inputmode="numeric" dir="ltr" class="control" name="min_price" value="{{ $filters['min_price'] ?? '' }}"></div>
<div class="filter-field"><label for="catalog_max_price">{{ __('storefront.clothing.max_price') }}</label><input id="catalog_max_price" type="number" min="0" max="10000000" step="1" inputmode="numeric" dir="ltr" class="control" name="max_price" value="{{ $filters['max_price'] ?? '' }}"></div>
<div class="filter-field"><label for="catalog_availability">{{ __('storefront.clothing.availability_label') }}</label><select id="catalog_availability" class="control" name="availability"><option value="">{{ __('storefront.clothing.any_availability') }}</option><option value="in_stock" @selected(($filters['availability']??'')==='in_stock')>{{ __('storefront.clothing.in_stock_only') }}</option></select></div>
<div class="filter-actions"><button class="btn">{{ __('storefront.clothing.apply_filters') }}</button>@if(collect($filters)->contains(fn($value)=>$value!==null && $value!==''))<a class="btn btn-secondary" href="{{ route('storefront.clothing.index',$storefront) }}">{{ __('storefront.clothing.clear_filters') }}</a>@endif</div>
</div></form>
<div class="grid">@forelse($listings as $listing)
@php($image=$listing->cloth->images->first(fn($item)=>$item->image_url)) @php($available=(float)$listing->cloth->colors->sum(fn($color)=>$color->reservableLength()))
<article class="card product-card"><div class="photo">@if($image)<img src="{{ $image->image_url }}" alt="{{ $listing->display_name }}">@else<span>🧵</span>@endif</div><div class="product-body">@if($listing->is_featured)<div class="featured">{{ __('storefront.clothing.featured') }}</div>@endif<h2>{{ $listing->display_name }}</h2><div class="muted">{{ $listing->cloth->brand->name ?? __('storefront.clothing.no_brand') }} · {{ $listing->cloth->type->name ?? __('storefront.clothing.fabric') }}</div><div class="price">{{ __('storefront.common.rs') }} {{ number_format((float)($listing->cloth->sale_price ?: $listing->cloth->price),2) }} {{ __('storefront.clothing.per_metre') }}</div><div>{{ $available>0 ? __('storefront.clothing.available',['amount'=>number_format($available,2)]) : __('storefront.clothing.out_of_stock') }}</div><div class="colors">@foreach($listing->cloth->colors as $color)<span class="pill">{{ $color->color }} — {{ number_format($color->reservableLength(),2) }}m</span>@endforeach</div><a class="btn" href="{{ route('storefront.clothing.show',[$storefront,$listing]) }}">{{ __('storefront.clothing.view_details') }}</a></div></article>
@empty<div class="card empty" style="grid-column:1/-1"><h2>{{ __('storefront.clothing.empty_title') }}</h2><p>{{ __('storefront.clothing.empty_text') }}</p></div>@endforelse</div>
@if($listings->hasPages())<div style="margin-top:25px">{{ $listings->links() }}</div>@endif
</main>
@endsection
