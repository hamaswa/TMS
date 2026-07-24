@extends('storefront.public.layout')
@section('title', $service->name.' — '.$storefront->display_name)
@section('meta_description', \App\Support\StorefrontSeo::description($service->description, __('storefront.tailoring.default_description')))
@section('canonical_url', route('storefront.tailoring.show',[$storefront,$service]))
@section('meta_image', $storefront->cover_url ?: $storefront->logo_url ?: '')
@push('structured_data')
<script type="application/ld+json">{!! \App\Support\StorefrontSeo::json(\App\Support\StorefrontSeo::graph(
    \App\Support\StorefrontSeo::service($storefront,$service)
)) !!}</script>
@endpush
@push('styles')
:root{--primary:#314f7e;--primary-dark:#253d63;--ink:#243750;--bg:#f5f6f8;--line:#e0e5eb}.detail{max-width:920px}.panel{padding:34px}.panel h1{font-size:clamp(2.2rem,6vw,4rem);line-height:1.4}.meta{display:flex;flex-wrap:wrap;gap:10px;margin:22px 0}
@endpush
@section('body')
<nav class="nav"><div class="shell"><a class="nav-brand" href="{{ route('storefront.show',$storefront) }}">{{ $storefront->display_name }}</a><div class="nav-actions">@include('storefront.public.partials.language-switch')<a class="nav-link" href="{{ route('storefront.tailoring.index',$storefront) }}">{{ __('storefront.tailoring.all_services') }}</a></div></div></nav>
<main class="section"><div class="shell detail"><article class="card panel">@if($service->is_featured)<div class="featured">{{ __('storefront.tailoring.featured') }}</div>@endif<h1>{{ $service->name }}</h1><div class="meta">@if($service->price_from!==null)<span class="pill">{{ __('storefront.tailoring.starting_price') }}: {!! \App\Support\PakistanCurrency::html($service->price_from) !!} · {{ $service->price_unit }}</span>@endif @if($service->estimated_days)<span class="pill">{{ __('storefront.tailoring.estimated_time') }}: {{ $service->estimated_days }} {{ __('storefront.common.days') }}</span>@endif</div><p>{{ $service->description ?: __('storefront.tailoring.default_description') }}</p><div class="notice">{{ __('storefront.tailoring.price_notice') }}</div>@if($storefront->inquiries_enabled)<a class="btn" href="{{ route('storefront.tailoring.index',[$storefront,'service'=>$service->id]).'#inquiry' }}">{{ __('storefront.tailoring.inquire_service') }}</a>@elseif($storefront->public_phone)<a class="btn" href="tel:{{ preg_replace('/[^0-9+]/','',$storefront->public_phone) }}">{{ __('storefront.common.contact_shop') }}</a>@endif</article></div></main>
@endsection
