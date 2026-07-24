@extends('storefront.public.layout')
@section('title', __('storefront.order.secure_title').' — '.$storefront->display_name)
@section('meta_description', __('storefront.order.secure_text'))
@section('canonical_url', route('storefront.show',$storefront))
@section('robots', 'noindex,nofollow')
@push('styles')
.payment-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.payment-metric{background:#f4f8f6;border:1px solid var(--line);border-radius:14px;padding:14px}.payment-metric strong{display:block;margin-bottom:4px}.payment-timeline{list-style:none;padding:0;margin:14px 0 0}.payment-timeline li{position:relative;padding-block:0 18px;padding-inline-start:25px;border-inline-start:2px solid #c9ddd5}.payment-timeline li:before{content:"";position:absolute;inset-inline-start:-7px;top:7px;width:12px;height:12px;border-radius:50%;background:var(--primary)}.payment-timeline li:last-child{border-inline-start-color:transparent;padding-bottom:0}@media(max-width:650px){.payment-grid{grid-template-columns:1fr}.payment-timeline li{padding-inline-start:22px}.order-summary .row>span{display:block;margin-top:4px}}
@endpush
@section('body')
<nav class="nav"><div class="shell"><a class="nav-brand" href="{{ route('storefront.show',$storefront) }}">{{ $storefront->display_name }}</a><div class="nav-actions">@include('storefront.public.partials.language-switch')<a class="nav-link" href="{{ route('storefront.clothing.index',$storefront) }}">{{ __('storefront.common.back_to_shop') }}</a></div></div></nav>
<header class="hero"><div class="shell"><div>{{ __('storefront.order.online_order') }}</div><h1 dir="ltr">{{ $order->reference }}</h1></div></header>
<main class="section"><div class="shell">@if(session('success'))<div class="success">{{ session('success') }}</div>@endif @if($errors->any())<div class="errors">{{ $errors->first() }}</div>@endif
@if(!$authorized)<section class="card"><h2>{{ __('storefront.order.secure_title') }}</h2><p class="muted">{{ __('storefront.order.secure_text') }}</p><form method="POST" action="{{ route('storefront.orders.authenticate',[$storefront,$order->reference]) }}">@csrf<div class="form-group"><label for="order_phone">{{ __('storefront.common.phone') }}</label><input id="order_phone" name="phone" type="tel" inputmode="tel" dir="ltr" class="control" required maxlength="50" placeholder="{{ __('storefront.common.phone_placeholder') }}"></div><div class="form-group"><label for="order_pin">{{ __('storefront.cart.pin') }}</label><input id="order_pin" name="pin" dir="ltr" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" class="control" required></div><button class="btn">{{ __('storefront.order.view') }}</button></form></section>
@else
@php
    $fullRefundAmount = (float) $order->refunds->sum('amount');
    $returnCreditAmount = (float) $order->returns->sum('refund_amount');
    $adjustedAmount = $fullRefundAmount + $returnCreditAmount;
    $hasPaidPartialRefund = $order->returns->contains(fn($return) => filled($return->refund_method));
    $paymentDisplayStatus = $fullRefundAmount > 0 || $hasPaidPartialRefund
        ? 'refunded'
        : ($returnCreditAmount > 0 ? 'adjusted' : $order->payment_verification_status);
@endphp
<section class="card order-summary"><div class="row"><strong>{{ __('storefront.order.status') }}</strong><span class="pill">{{ __('storefront.order.statuses.'.$order->status) }}</span></div><div class="row"><strong>{{ __('storefront.order.customer') }}</strong><span>{{ $order->customer->name }}</span></div><div class="row"><strong>{{ __('storefront.order.placed_at') }}</strong><span>{{ $order->placed_at->format('d-m-Y h:i A') }}</span></div><div class="row"><strong>{{ __('storefront.order.fulfillment') }}</strong><span>{{ __('storefront.order.methods.'.$order->fulfillment_method) }}</span></div><div class="row"><strong>{{ __('storefront.order.payment_choice') }}</strong><span>{{ \App\Models\StorefrontOrder::publicPaymentMethods()[$order->payment_method] ?? $order->payment_method }}</span></div>
@if(\App\Models\StorefrontOrder::requiresManualVerification($order->payment_method))<div class="row"><strong>{{ __('storefront.order.payment_reference') }}</strong><span dir="ltr">{{ $order->payment_reference }}</span></div><div class="notice">@if($order->payment_verification_status===\App\Models\StorefrontOrder::VERIFICATION_VERIFIED){{ __('storefront.order.payment_verified') }}@elseif($order->payment_verification_status===\App\Models\StorefrontOrder::VERIFICATION_REJECTED){{ __('storefront.order.payment_rejected') }}@else{{ __('storefront.order.payment_pending') }}@endif</div>@if($order->payment_evidence_path)<div class="notice">{{ __('storefront.order.payment_evidence_received') }}</div>@endif @endif
@if($order->delivery_address)<div class="row"><strong>{{ __('storefront.order.delivery_address') }}</strong><span>{{ $order->delivery_address }}</span></div>@endif</section>
<section class="card" style="margin-top:18px">
    <h2>{{ __('storefront.order.payment_overview') }}</h2>
    <div class="payment-grid">
        <div class="payment-metric"><strong>{{ __('storefront.order.payment_status') }}</strong><span class="pill">{{ __('storefront.order.payment_statuses.'.$paymentDisplayStatus) }}</span></div>
        <div class="payment-metric"><strong>{{ __('storefront.order.verified_amount') }}</strong>{!! \App\Support\PakistanCurrency::html($order->paid_amount) !!}</div>
        <div class="payment-metric"><strong>{{ __('storefront.order.remaining_amount') }}</strong>{!! \App\Support\PakistanCurrency::html($order->balance_amount) !!}</div>
        @if($adjustedAmount > 0)<div class="payment-metric"><strong>{{ $returnCreditAmount > 0 ? __('storefront.order.adjusted_amount') : __('storefront.order.refunded_amount') }}</strong>{!! \App\Support\PakistanCurrency::html($adjustedAmount) !!}</div>@endif
    </div>
    <h3>{{ __('storefront.order.payment_history') }}</h3>
    <ol class="payment-timeline">
        <li>{{ __('storefront.order.history_time', ['event' => __('storefront.order.history_order_placed', ['method' => \App\Models\StorefrontOrder::publicPaymentMethods()[$order->payment_method] ?? $order->payment_method]), 'time' => $order->placed_at->format('d-m-Y h:i A')]) }}</li>
        @if(\App\Models\StorefrontOrder::requiresManualVerification($order->payment_method))
            <li>{{ __('storefront.order.history_time', ['event' => __('storefront.order.history_reference_submitted', ['reference' => $order->payment_reference]), 'time' => $order->placed_at->format('d-m-Y h:i A')]) }}</li>
            @if($order->payment_evidence_path)<li>{{ __('storefront.order.history_evidence_submitted') }}</li>@endif
            @if($order->payment_verification_status===\App\Models\StorefrontOrder::VERIFICATION_PENDING)
                <li>{{ __('storefront.order.history_pending') }}</li>
            @elseif($order->payment_verification_status===\App\Models\StorefrontOrder::VERIFICATION_VERIFIED && $order->payment_verified_at)
                <li>{{ __('storefront.order.history_time', ['event' => __('storefront.order.history_verified'), 'time' => $order->payment_verified_at->format('d-m-Y h:i A')]) }}</li>
            @elseif($order->payment_verification_status===\App\Models\StorefrontOrder::VERIFICATION_REJECTED && $order->payment_rejected_at)
                <li>{{ __('storefront.order.history_time', ['event' => __('storefront.order.history_rejected'), 'time' => $order->payment_rejected_at->format('d-m-Y h:i A')]) }}</li>
            @endif
        @endif
        @foreach($order->refunds as $refund)
            <li>
                {{ __('storefront.order.history_time', [
                    'event' => __('storefront.order.history_refunded', [
                        'amount' => \App\Support\PakistanCurrency::format($refund->amount),
                        'method' => \App\Models\StorefrontOrderRefund::publicMethods()[$refund->method] ?? $refund->method,
                        'reference' => $refund->reference,
                    ]),
                    'time' => $refund->refunded_at->format('d-m-Y h:i A'),
                ]) }}
                @if($refund->external_reference)<div class="muted" dir="ltr">{{ __('storefront.order.refund_external_reference', ['reference' => $refund->external_reference]) }}</div>@endif
            </li>
        @endforeach
        @foreach($order->returns as $return)
            @php $returnItem = $return->items->first(); @endphp
            <li>
                @if($return->type === \App\Models\StorefrontOrderReturn::TYPE_EXCHANGE)
                    {{ __('storefront.order.history_time', [
                        'event' => __('storefront.order.history_exchanged', [
                            'quantity' => number_format((float) $returnItem?->quantity, 2),
                            'item' => $returnItem?->orderItem?->item_name,
                            'color' => $returnItem?->replacementColor?->color,
                            'reference' => $return->reference,
                        ]),
                        'time' => $return->processed_at->format('d-m-Y h:i A'),
                    ]) }}
                @else
                    {{ __('storefront.order.history_time', [
                        'event' => __('storefront.order.history_partially_returned', [
                            'quantity' => number_format((float) $returnItem?->quantity, 2),
                            'item' => $returnItem?->orderItem?->item_name,
                            'amount' => \App\Support\PakistanCurrency::format($return->refund_amount),
                            'method' => $return->refund_method
                                ? (\App\Models\StorefrontOrderRefund::publicMethods()[$return->refund_method] ?? $return->refund_method)
                                : __('storefront.order.balance_credit'),
                            'reference' => $return->reference,
                        ]),
                        'time' => $return->processed_at->format('d-m-Y h:i A'),
                    ]) }}
                    @if($return->external_reference)<div class="muted" dir="ltr">{{ __('storefront.order.refund_external_reference', ['reference' => $return->external_reference]) }}</div>@endif
                @endif
            </li>
        @endforeach
        @if($order->status===\App\Models\StorefrontOrder::STATUS_CANCELLED && $order->cancelled_at)
            <li>{{ __('storefront.order.history_time', ['event' => __('storefront.order.history_cancelled'), 'time' => $order->cancelled_at->format('d-m-Y h:i A')]) }}</li>
        @endif
    </ol>
</section>
<section class="card" style="margin-top:18px"><h2>{{ __('storefront.order.items') }}</h2>@foreach($order->items as $item)<div class="row"><div><strong>{{ $item->item_name }}</strong><div class="muted">{{ $item->color }} · {{ number_format($item->quantity,2) }}m × {!! \App\Support\PakistanCurrency::html($item->unit_price) !!}</div></div><strong>{!! \App\Support\PakistanCurrency::html($item->line_total) !!}</strong></div>@endforeach<div class="row price"><span>{{ __('storefront.order.total') }}</span><span>{!! \App\Support\PakistanCurrency::html($order->subtotal) !!}</span></div></section>
<div class="notice">{{ __('storefront.order.balance_note') }}</div>@if($order->customer_note)<section class="card"><strong>{{ __('storefront.order.your_note') }}</strong><p>{{ $order->customer_note }}</p></section>@endif
@endif</div></main>
@endsection
