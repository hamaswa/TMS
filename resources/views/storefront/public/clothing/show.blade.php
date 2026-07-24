@extends('storefront.public.layout')
@section('title', $listing->display_name.' — '.$storefront->display_name)
@section('meta_description', \App\Support\StorefrontSeo::description($listing->description, collect([$listing->cloth->brand?->name,$listing->cloth->type?->name,$storefront->display_name])->filter()->implode(' · ')))
@section('canonical_url', route('storefront.clothing.show',[$storefront,$listing]))
@section('meta_type', 'product')
@section('meta_image', $listing->cloth->images->first(fn($image)=>$image->image_url)?->image_url ?: $storefront->cover_url ?: '')
@section('meta_image_alt', $listing->display_name)
@push('structured_data')
<script type="application/ld+json">{!! \App\Support\StorefrontSeo::json(\App\Support\StorefrontSeo::graph(
    \App\Support\StorefrontSeo::product($storefront,$listing)
)) !!}</script>
@endpush
@push('styles')
.product{display:grid;grid-template-columns:1.05fr 1fr;gap:38px}.gallery{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.photo{min-height:250px;background:#e1ede8;border-radius:18px;overflow:hidden;display:grid;place-items:center;font-size:4rem}.photo:first-child{grid-column:1/-1;min-height:390px}.photo img{width:100%;height:100%;object-fit:cover}.colors{display:grid;gap:9px}.cart-grid{display:grid;grid-template-columns:1.3fr .7fr auto;gap:10px}@media(max-width:760px){.product{grid-template-columns:1fr}.cart-grid{grid-template-columns:1fr}.photo:first-child{min-height:290px}}
@endpush
@section('body')
<nav class="nav"><div class="shell"><a class="nav-brand" href="{{ route('storefront.show',$storefront) }}">{{ $storefront->display_name }}</a><div class="nav-actions">@include('storefront.public.partials.language-switch')<a class="nav-link" href="{{ route('storefront.cart.show',$storefront) }}">{{ __('storefront.clothing.cart') }}</a><a class="nav-link" href="{{ route('storefront.clothing.index',$storefront) }}">{{ __('storefront.clothing.all_clothing') }}</a></div></div></nav>
<main class="shell section product"><div class="gallery">@forelse($listing->cloth->images->filter(fn($item)=>$item->image_url) as $image)<div class="photo"><img src="{{ $image->image_url }}" alt="{{ $listing->display_name }} — {{ $image->image_color }}"></div>@empty<div class="photo">🧵</div>@endforelse</div>
<section>@if($listing->is_featured)<div class="featured">{{ __('storefront.clothing.featured') }}</div>@endif<h1>{{ $listing->display_name }}</h1><div class="muted">{{ $listing->cloth->brand->name ?? __('storefront.clothing.no_brand') }} · {{ $listing->cloth->type->name ?? __('storefront.clothing.fabric') }}</div><div class="price">{{ __('storefront.common.rs') }} {{ number_format((float)($listing->cloth->sale_price ?: $listing->cloth->price),2) }} {{ __('storefront.clothing.per_metre') }}</div>@if($listing->description)<p>{{ $listing->description }}</p>@endif
<h2>{{ __('storefront.clothing.colors_availability') }}</h2><div class="colors">@foreach($listing->cloth->colors as $color)@php($available=$color->reservableLength())<div class="card row"><strong>{{ $color->color }}</strong><span>{{ $available>0 ? __('storefront.clothing.available',['amount'=>number_format($available,2)]) : __('storefront.clothing.stock_out') }}</span></div>@endforeach</div>
<div class="card" style="margin-top:20px">@if($errors->any())<div class="errors">{{ $errors->first() }}</div>@endif<form method="POST" action="{{ route('storefront.cart.store',[$storefront,$listing]) }}">@csrf<div class="cart-grid"><select name="cloth_color_id" class="control" required><option value="">{{ __('storefront.clothing.select_color') }}</option>@foreach($listing->cloth->colors as $color)@php($available=$color->reservableLength())<option value="{{ $color->id }}" @disabled($available<=0)>{{ $color->color }} — {{ number_format($available,2) }}m</option>@endforeach</select><input type="number" name="quantity" min="0.25" max="1000" step="0.25" value="1" class="control" aria-label="{{ __('storefront.clothing.quantity_label') }}" required><button class="btn">{{ __('storefront.clothing.reserve') }}</button></div></form><small>{{ __('storefront.clothing.reservation_note') }}</small></div>
<div class="notice" style="margin-top:20px">{{ __('storefront.clothing.live_stock_note') }}</div>@if($storefront->public_phone)<a class="btn" href="tel:{{ preg_replace('/[^0-9+]/','',$storefront->public_phone) }}">{{ __('storefront.common.contact_shop') }}</a>@endif</section></main>
@endsection
