@extends('storefront.public.layout')
@section('title', __('storefront.tailoring.title').' — '.$storefront->localizedName())
@section('meta_description', __('storefront.tailoring.intro'))
@section('canonical_url', route('storefront.tailoring.index',$storefront))
@section('meta_image', $storefront->cover_url ?: $storefront->logo_url ?: '')
@push('structured_data')
<script type="application/ld+json">{!! \App\Support\StorefrontSeo::json(\App\Support\StorefrontSeo::graph(
    \App\Support\StorefrontSeo::collection(
        __('storefront.tailoring.title').' — '.$storefront->localizedName(),
        __('storefront.tailoring.unified_intro'),
        route('storefront.tailoring.index',$storefront),
        $storefront
    )
)) !!}</script>
@endpush
@push('styles')
@include('storefront.public.partials.shop-navigation-styles')
.service-actions{display:flex;gap:9px;align-items:center;flex-wrap:wrap;margin-top:15px}.unified-note{margin-bottom:22px;padding:16px;border:1px solid var(--line);border-radius:14px;background:#edf7f2}.service-card{display:flex;flex-direction:column}.service-card .service-actions{margin-top:auto;padding-top:16px}
@endpush
@section('body')
@include('storefront.public.partials.shop-navigation')
<header class="hero"><div class="shell"><h1>{{ __('storefront.tailoring.title') }}</h1><p>{{ __('storefront.tailoring.unified_intro') }}</p></div></header>
<main class="section"><div class="shell">
@if(session('inquiry_success'))<div class="success">{{ session('inquiry_success') }}</div>@endif
<div class="unified-note"><strong>{{ __('storefront.tailoring.one_order_title') }}</strong><div>{{ __('storefront.tailoring.one_order_text') }}</div></div>
@php
    $publicPaymentMethods = $storefront->acceptedUnifiedOrderPaymentMethods();
@endphp
@if(collect(array_keys($publicPaymentMethods))->contains(fn($method) => \App\Models\StorefrontOrder::requiresManualVerification($method)))
<section class="card" style="margin-bottom:22px"><strong>{{ __('storefront.cart.payment_method') }}</strong>
@foreach($publicPaymentMethods as $method => $label)
    @if(\App\Models\StorefrontOrder::requiresManualVerification($method))
        <div class="row"><span>{{ $label }}</span><span>
            @if($method === \App\Models\StorefrontOrder::PAYMENT_EASYPAISA){{ $storefront->easypaisa_account_title }} · <b dir="ltr">{{ $storefront->easypaisa_account_number }}</b>
            @elseif($method === \App\Models\StorefrontOrder::PAYMENT_JAZZCASH){{ $storefront->jazzcash_account_title }} · <b dir="ltr">{{ $storefront->jazzcash_account_number }}</b>
            @elseif($method === \App\Models\StorefrontOrder::PAYMENT_BANK_TRANSFER){{ $storefront->bank_account_title }} · <b dir="ltr">{{ $storefront->bank_iban ?: $storefront->bank_account_number }}</b>
            @elseif($method === \App\Models\StorefrontOrder::PAYMENT_RAAST){{ $storefront->raast_account_title }} · <b dir="ltr">{{ $storefront->raast_id }}</b>
            @endif
        </span></div>
    @endif
@endforeach
</section>
@endif
<div class="grid">
@forelse($services as $service)
<article class="card service-card">
    @if($service->is_featured)<div class="featured">{{ __('storefront.tailoring.featured') }}</div>@endif
    <div class="pill">{{ $service->is_available ? __('storefront.tailoring.available') : __('storefront.tailoring.temporarily_unavailable') }}</div>
    <h2>{{ $service->localizedName() }}</h2>
    <p>{{ \Illuminate\Support\Str::limit($service->localizedDescription(),150) ?: __('storefront.tailoring.default_description') }}</p>
    <div>@if($service->price_from!==null)<span class="pill">{!! \App\Support\PakistanCurrency::html($service->price_from) !!} {{ __('storefront.tailoring.from') }} · {{ $service->localizedPriceUnit() }}</span>@endif @if($service->estimated_days)<span class="pill">{{ __('storefront.tailoring.estimated_days',['days'=>$service->estimated_days]) }}</span>@endif</div>
    <div class="service-actions"><a class="btn" href="{{ route('storefront.tailoring.show',[$storefront,$service,'cloth_item'=>request('cloth_item')]) }}">{{ __('storefront.tailoring.configure_order') }}</a></div>
</article>
@empty<div class="card empty" style="grid-column:1/-1"><h2>{{ __('storefront.tailoring.empty_title') }}</h2><p>{{ __('storefront.tailoring.empty_text') }}</p></div>@endforelse
</div></div></main>
@include('storefront.public.partials.shop-footer')
@endsection
