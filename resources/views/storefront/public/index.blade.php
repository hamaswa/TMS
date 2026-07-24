@extends('storefront.public.layout')
@section('title', __('storefront.marketplace.title'))
@push('styles')
.market-nav{background:#0b3d2e;color:#fff}.market-nav .nav-brand,.market-nav .nav-link{color:#fff}.market-hero{padding:68px 0;background:radial-gradient(circle at 15% 20%,#2ca679 0,transparent 28%),linear-gradient(135deg,#0b3d2e,#126f50)}.shop{padding:0;overflow:hidden;display:flex;flex-direction:column}.cover{height:170px;background:linear-gradient(135deg,#d9efe6,#9ed7c0);background-size:cover;background-position:center}.shop-body{padding:20px;display:flex;flex-direction:column;flex:1}.shop-body h2{margin:0}.tags{display:flex;flex-wrap:wrap;gap:7px;margin:12px 0}.shop .btn{margin-top:auto}
@endpush
@section('body')
<nav class="nav market-nav"><div class="shell"><a class="nav-brand" href="{{ route('storefront.index') }}">{{ __('storefront.common.marketplace') }}</a><div class="nav-actions">@include('storefront.public.partials.language-switch')<a class="nav-link" href="{{ route('login') }}">{{ __('storefront.common.business_login') }}</a></div></div></nav>
<header class="hero market-hero"><div class="shell"><h1>{{ __('storefront.marketplace.hero_title') }}</h1><p>{{ __('storefront.marketplace.hero_text') }}</p></div></header>
<main class="section"><div class="shell">
    <div class="row"><div><h2>{{ __('storefront.marketplace.shops') }}</h2><div class="muted">{{ __('storefront.marketplace.active_only') }}</div></div><div class="muted">{{ __('storefront.marketplace.shop_count',['count'=>$storefronts->total()]) }}</div></div>
    @if($storefronts->count())<div class="grid" style="margin-top:22px">
        @foreach($storefronts as $storefront)<article class="card shop"><div class="cover" @if($storefront->cover_url) style="background-image:url('{{ $storefront->cover_url }}')" @endif></div><div class="shop-body"><h2>{{ $storefront->display_name }}</h2><div class="muted">{{ $storefront->city ?: __('storefront.common.pakistan') }}</div><p>{{ $storefront->tagline ?: __('storefront.marketplace.default_tagline') }}</p><div class="tags">@if($storefront->show_clothing)<span class="pill">{{ __('storefront.common.clothing') }}</span>@endif @if($storefront->show_tailoring)<span class="pill">{{ __('storefront.common.tailoring') }}</span>@endif @if($storefront->delivery_enabled)<span class="pill">{{ __('storefront.common.delivery') }}</span>@endif</div><a class="btn" href="{{ route('storefront.show',$storefront) }}">{{ __('storefront.marketplace.view_shop') }}</a></div></article>@endforeach
    </div><div style="margin-top:25px">{{ $storefronts->links() }}</div>
    @else<div class="card empty" style="margin-top:22px"><h2>{{ __('storefront.marketplace.empty_title') }}</h2><p class="muted">{{ __('storefront.marketplace.empty_text') }}</p></div>@endif
</div></main>
<footer class="footer"><div class="shell">{{ __('storefront.marketplace.footer') }}</div></footer>
@endsection
