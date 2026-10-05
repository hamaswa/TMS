@extends('storefront.public.layout')
@section('title', $listing->localizedName().' — '.$storefront->localizedName())
@section('meta_description', \App\Support\StorefrontSeo::description($listing->localizedDescription(), collect([$listing->cloth->brand?->name,$listing->cloth->type?->localizedName(),$storefront->localizedName()])->filter()->implode(' · ')))
@section('canonical_url', route('storefront.clothing.show',[$storefront,$listing]))
@section('meta_type', 'product')
@section('meta_image', $listing->cloth->images->first(fn($image)=>$image->image_url)?->image_url ?: $storefront->cover_url ?: '')
@section('meta_image_alt', $listing->localizedName())
@push('structured_data')
<script type="application/ld+json">{!! \App\Support\StorefrontSeo::json(\App\Support\StorefrontSeo::graph(
    \App\Support\StorefrontSeo::product($storefront,$listing)
)) !!}</script>
@endpush
@push('styles')
@include('storefront.public.partials.shop-navigation-styles')
.product{display:grid;grid-template-columns:1.05fr 1fr;gap:38px}.gallery{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.photo{min-height:250px;background:#e1ede8;border-radius:18px;overflow:hidden;display:grid;place-items:center;font-size:4rem}.photo:first-child{grid-column:1/-1;min-height:390px}.photo img{width:100%;height:100%;object-fit:cover}.colors{display:grid;gap:9px}.purchase-modes{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin:14px 0}.purchase-mode{display:flex;gap:10px;padding:13px;border:1px solid var(--line);border-radius:12px;background:#fafcfb;cursor:pointer}.purchase-mode:has(input:checked){border-color:var(--brand);background:#edf7f2}.purchase-mode strong,.purchase-mode small{display:block}.purchase-mode small{margin-top:4px}.cart-grid{display:grid;grid-template-columns:1.3fr .7fr auto;gap:10px}@media(max-width:760px){.product{grid-template-columns:1fr}.purchase-modes,.cart-grid{grid-template-columns:1fr}.photo:first-child{min-height:290px}}
@endpush
@section('body')
@include('storefront.public.partials.shop-navigation')
<main class="shell section product"><div class="gallery">@forelse($listing->cloth->images->filter(fn($item)=>$item->image_url) as $image)<div class="photo"><img src="{{ $image->image_url }}" alt="{{ $listing->localizedName() }} — {{ $image->image_color }}"></div>@empty<div class="photo">@include('storefront.public.partials.icon', ['name' => 'shirt', 'class' => 'is-xl'])</div>@endforelse</div>
<section>@if($listing->is_featured)<div class="featured">{{ __('storefront.clothing.featured') }}</div>@endif<h1>{{ $listing->localizedName() }}</h1><div class="muted">{{ $listing->cloth->brand->name ?? __('storefront.clothing.no_brand') }} · {{ $listing->cloth->type->localizedName() ?? __('storefront.clothing.fabric') }}</div><div class="price">{!! \App\Support\PakistanCurrency::html($listing->cloth->sale_price ?: $listing->cloth->price) !!} {{ __('storefront.clothing.per_metre') }}</div>@if($listing->localizedDescription())<p>{{ $listing->localizedDescription() }}</p>@endif
@php($totalAvailable=(float)$listing->cloth->colors->sum(fn($color)=>$color->reservableLength()))
@if($listing->cloth->tracksColors())<h2>{{ __('storefront.clothing.colors_availability') }}</h2><div class="colors">@foreach($listing->cloth->colors as $color)@php($available=$color->reservableLength())<div class="card row"><strong>{{ $color->localizedName() }}</strong><span>{{ $available>0 ? __('storefront.clothing.available',['amount'=>number_format($available,2)]) : __('storefront.clothing.stock_out') }}</span></div>@endforeach</div>@elseif($listing->cloth->usesDisplayOnlyColors())<h2>{{ __('storefront.clothing.colors_availability') }}</h2><div class="colors">@foreach($listing->cloth->selectableColorNames() as $color)<div class="card row"><strong>{{ $color }}</strong></div>@endforeach<div class="card row"><strong>{{ __('storefront.clothing.aggregate_stock') }}</strong><span>{{ $totalAvailable>0 ? __('storefront.clothing.available',['amount'=>number_format($totalAvailable,2)]) : __('storefront.clothing.stock_out') }}</span></div></div><small>{{ __('storefront.clothing.shared_stock_note') }}</small>@else<h2>{{ __('storefront.clothing.stock_availability') }}</h2><div class="colors"><div class="card row"><strong>{{ __('storefront.clothing.aggregate_stock') }}</strong><span>{{ $totalAvailable>0 ? __('storefront.clothing.available',['amount'=>number_format($totalAvailable,2)]) : __('storefront.clothing.stock_out') }}</span></div></div>@endif
@if($storefront->clothingOrderingEnabled() && $listing->acceptsOnlineOrders() && $totalAvailable>0)
<div class="card" style="margin-top:20px">
    @if($errors->any())<div class="errors">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('storefront.cart.store',[$storefront,$listing]) }}">
        @csrf
        @if($canAddStitching)
        <div>
            <strong>{{ __('storefront.clothing.purchase_type') }}</strong>
            <div class="purchase-modes">
                <label class="purchase-mode"><input type="radio" name="purchase_mode" value="fabric_only" @checked(old('purchase_mode','fabric_only')==='fabric_only')><span><strong>{{ __('storefront.clothing.fabric_only') }}</strong><small>{{ __('storefront.clothing.fabric_only_help') }}</small></span></label>
                <label class="purchase-mode"><input type="radio" name="purchase_mode" value="fabric_and_stitching" @checked(old('purchase_mode')==='fabric_and_stitching')><span><strong>{{ __('storefront.clothing.fabric_and_stitching') }}</strong><small>{{ __('storefront.clothing.fabric_and_stitching_help') }}</small></span></label>
            </div>
        </div>
        @else
        <input type="hidden" name="purchase_mode" value="fabric_only">
        @endif
        <div class="cart-grid">
            @if($listing->cloth->tracksColors())
            <div><label for="product_color">{{ __('storefront.clothing.product_color_label') }}</label><select id="product_color" name="cloth_color_id" class="control" required><option value="">{{ __('storefront.clothing.select_color') }}</option>@foreach($listing->cloth->colors as $color)@php($available=$color->reservableLength())<option value="{{ $color->id }}" @disabled($available<=0)>{{ $color->localizedName() }} — {{ number_format($available,2) }}m</option>@endforeach</select></div>
            @elseif($listing->cloth->usesDisplayOnlyColors())
            <input type="hidden" name="cloth_color_id" value="{{ $listing->cloth->colors->first()?->id }}"><div><label for="product_color">{{ __('storefront.clothing.product_color_label') }}</label><select id="product_color" name="selected_color" class="control" required><option value="">{{ __('storefront.clothing.select_color') }}</option>@foreach($listing->cloth->selectableColorNames() as $color)<option value="{{ $color }}" @selected(old('selected_color') === $color)>{{ $color }}</option>@endforeach</select></div>
            @else
            <input type="hidden" name="cloth_color_id" value="{{ $listing->cloth->colors->first()?->id }}">
            @endif
            <div><label for="product_quantity">{{ __('storefront.clothing.quantity_label') }}</label><input id="product_quantity" type="number" name="quantity" min="{{ $listing->minimumOrderQuantity() }}" max="{{ $listing->maximumOrderQuantity() }}" step="{{ $listing->orderIncrement() }}" value="{{ old('quantity',$listing->minimumOrderQuantity()) }}" class="control" required></div>
            <button class="btn">{{ __('storefront.clothing.reserve') }}</button>
        </div>
    </form>
    <small>{{ __('storefront.clothing.order_limits',['min'=>number_format($listing->minimumOrderQuantity(),2),'max'=>number_format($listing->maximumOrderQuantity(),2),'step'=>number_format($listing->orderIncrement(),2)]) }}</small><br><small>{{ __('storefront.clothing.reservation_note') }}</small>
</div>
@elseif(!$listing->is_available)<div class="notice" style="margin-top:20px">{{ __('storefront.clothing.temporarily_unavailable') }}</div>
@elseif(!$storefront->clothingOrderingEnabled())<div class="notice" style="margin-top:20px">{{ __('storefront.clothing.catalog_only') }}</div>
@elseif(!$listing->online_order_enabled)<div class="notice" style="margin-top:20px">{{ __('storefront.clothing.product_catalog_only') }}</div>
@else<div class="notice" style="margin-top:20px">{{ __('storefront.clothing.out_of_stock') }}</div>@endif
@if($listing->preorder_enabled && $totalAvailable<=0)<div class="notice" style="margin-top:12px">{{ __('storefront.clothing.preorder_contact',['days'=>$listing->preorder_lead_days ?: '—']) }}</div>@endif
<div class="notice" style="margin-top:20px">{{ __('storefront.clothing.live_stock_note') }}</div>@if($storefront->public_phone)<a class="btn" href="tel:{{ preg_replace('/[^0-9+]/','',$storefront->public_phone) }}">{{ __('storefront.common.contact_shop') }}</a>@endif</section></main>
@include('storefront.public.partials.shop-footer')
@endsection
