@extends('storefront.public.layout')
@section('title', __('storefront.tailoring.title').' — '.$storefront->display_name)
@section('meta_description', __('storefront.tailoring.intro'))
@section('canonical_url', route('storefront.tailoring.index',$storefront))
@section('meta_image', $storefront->cover_url ?: $storefront->logo_url ?: '')
@push('structured_data')
<script type="application/ld+json">{!! \App\Support\StorefrontSeo::json(\App\Support\StorefrontSeo::graph(
    \App\Support\StorefrontSeo::collection(
        __('storefront.tailoring.title').' — '.$storefront->display_name,
        __('storefront.tailoring.intro'),
        route('storefront.tailoring.index',$storefront),
        $storefront
    )
)) !!}</script>
@endpush
@push('styles')
:root{--primary:#314f7e;--primary-dark:#253d63;--ink:#23364d;--bg:#f5f6f8;--line:#e0e5eb}.inquiry-wrap{background:#e9eef5}.inquiry-grid{display:grid;grid-template-columns:.8fr 1.25fr;gap:30px}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:13px}.wide{grid-column:1/-1}@media(max-width:850px){.inquiry-grid{grid-template-columns:1fr}}@media(max-width:600px){.form-grid{grid-template-columns:1fr}.wide{grid-column:auto}}
@endpush
@section('body')
<nav class="nav"><div class="shell"><a class="nav-brand" href="{{ route('storefront.show',$storefront) }}">{{ $storefront->display_name }}</a><div class="nav-actions">@include('storefront.public.partials.language-switch')<a class="nav-link" href="{{ route('storefront.show',$storefront) }}">{{ __('storefront.common.shop_home') }}</a></div></div></nav>
<header class="hero"><div class="shell"><h1>{{ __('storefront.tailoring.title') }}</h1><p>{{ __('storefront.tailoring.intro') }}</p></div></header>
<main><section class="section"><div class="shell grid">@forelse($services as $service)<article class="card">@if($service->is_featured)<div class="featured">{{ __('storefront.tailoring.featured') }}</div>@endif<h2>{{ $service->name }}</h2><p>{{ \Illuminate\Support\Str::limit($service->description,150) ?: __('storefront.tailoring.default_description') }}</p><div>@if($service->price_from!==null)<span class="pill">{!! \App\Support\PakistanCurrency::html($service->price_from) !!} {{ __('storefront.tailoring.from') }} · {{ $service->price_unit }}</span>@endif @if($service->estimated_days)<span class="pill">{{ __('storefront.tailoring.estimated_days',['days'=>$service->estimated_days]) }}</span>@endif</div><a class="btn" style="display:block;margin-top:15px" href="{{ route('storefront.tailoring.show',[$storefront,$service]) }}">{{ __('storefront.tailoring.details') }}</a></article>@empty<div class="card empty" style="grid-column:1/-1"><h2>{{ __('storefront.tailoring.empty_title') }}</h2><p>{{ __('storefront.tailoring.empty_text') }}</p></div>@endforelse</div></section>
@if($storefront->inquiries_enabled)<section class="section inquiry-wrap" id="inquiry"><div class="shell inquiry-grid"><div><h2>{{ __('storefront.tailoring.inquiry_title') }}</h2><p>{{ __('storefront.tailoring.inquiry_intro') }}</p>@if($storefront->public_phone)<p><strong>{{ __('storefront.common.phone') }}:</strong> <span dir="ltr">{{ $storefront->public_phone }}</span></p>@endif</div><div class="card">@if(session('inquiry_success'))<div class="success">{{ session('inquiry_success') }}</div>@endif @if($errors->any())<div class="errors"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('storefront.inquiries.store',$storefront) }}">@csrf<div class="hp" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div><div class="form-grid">
<div class="form-group"><label for="customer_name">{{ __('storefront.tailoring.name') }}</label><input id="customer_name" name="customer_name" required maxlength="150" class="control" value="{{ old('customer_name') }}"></div>
<div class="form-group"><label for="phone">{{ __('storefront.common.phone') }}</label><input id="phone" name="phone" type="tel" inputmode="tel" required minlength="7" maxlength="50" dir="ltr" class="control" value="{{ old('phone') }}" placeholder="{{ __('storefront.common.phone_placeholder') }}"></div>
<div class="form-group"><label for="service">{{ __('storefront.tailoring.service') }}</label><select id="service" name="tailoring_service_id" class="control"><option value="">{{ __('storefront.tailoring.general') }}</option>@foreach($services as $service)<option value="{{ $service->id }}" @selected((string)old('tailoring_service_id',request('service'))===(string)$service->id)>{{ $service->name }}</option>@endforeach</select></div>
<div class="form-group"><label for="preferred_date">{{ __('storefront.tailoring.preferred_date') }}</label><input id="preferred_date" type="date" min="{{ now()->toDateString() }}" name="preferred_date" class="control" value="{{ old('preferred_date') }}"></div>
<div class="form-group"><label for="email">{{ __('storefront.common.email') }} ({{ __('storefront.common.optional') }})</label><input id="email" type="email" name="email" maxlength="150" dir="ltr" class="control" value="{{ old('email') }}"></div>
<div class="form-group"><label for="city">{{ __('storefront.common.city') }} ({{ __('storefront.common.optional') }})</label><input id="city" name="city" maxlength="100" class="control" value="{{ old('city') }}"></div>
<div class="form-group wide"><label for="tailoring_payment_method">{{ __('storefront.tailoring.payment_preference') }}</label><select id="tailoring_payment_method" name="payment_method" class="control">@foreach(\App\Models\StorefrontInquiry::publicPaymentMethods() as $value=>$label)<option value="{{ $value }}" @selected(old('payment_method','unpaid')===$value)>{{ $label }}</option>@endforeach</select><small>{{ __('storefront.tailoring.payment_help') }}</small></div>
<div id="tailoring-easypaisa-fields" class="wide"><div class="form-grid"><div class="form-group"><label for="tailoring_payment_phone">{{ __('storefront.cart.sender_phone') }}</label><input id="tailoring_payment_phone" name="payment_sender_phone" dir="ltr" maxlength="50" class="control" value="{{ old('payment_sender_phone') }}"></div><div class="form-group"><label for="tailoring_payment_reference">{{ __('storefront.cart.payment_reference') }}</label><input id="tailoring_payment_reference" name="payment_reference" dir="ltr" maxlength="100" class="control" value="{{ old('payment_reference') }}"></div></div></div>
<div class="form-group wide"><label for="message">{{ __('storefront.tailoring.details_question') }}</label><textarea id="message" name="message" maxlength="3000" rows="4" class="control">{{ old('message') }}</textarea></div><div class="wide"><button class="btn">{{ __('storefront.tailoring.send') }}</button></div>
</div></form></div></div></section>@endif</main>
@endsection
@push('scripts')
<script>(()=>{const method=document.getElementById('tailoring_payment_method'),fields=document.getElementById('tailoring-easypaisa-fields'),phone=document.getElementById('tailoring_payment_phone'),reference=document.getElementById('tailoring_payment_reference');const refresh=()=>{const visible=method?.value==='easypaisa';if(fields)fields.hidden=!visible;if(phone)phone.required=visible;if(reference)reference.required=visible};method?.addEventListener('change',refresh);refresh()})()</script>
@endpush
