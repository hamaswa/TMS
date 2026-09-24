@extends('storefront.public.layout')
@section('title', $booking->reference.' — '.$storefront->display_name)
@push('styles')
.booking-shell{max-width:850px;margin:0 auto}.booking-card{background:#fff;border:1px solid #e2e7ec;border-radius:18px;padding:24px;box-shadow:0 10px 30px rgba(25,45,70,.08)}.booking-ref{font:700 1.15rem monospace;direction:ltr;display:inline-block;background:#edf3f8;border-radius:9px;padding:7px 11px}.booking-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.booking-field{background:#f6f8fa;border-radius:11px;padding:12px}.booking-field strong{display:block;margin-bottom:4px}.booking-status{display:inline-block;padding:7px 12px;border-radius:999px;background:#fff1c5;color:#795b00;font-weight:700}.booking-status.confirmed{background:#dff5e7;color:#176b39}.booking-status.rejected{background:#fde3e3;color:#8a2424}@media(max-width:650px){.booking-grid{grid-template-columns:1fr}}
@endpush
@section('body')
<nav class="nav"><div class="shell"><a class="nav-brand" href="{{ route('storefront.show',$storefront) }}">{{ $storefront->display_name }}</a><div class="nav-actions">@include('storefront.public.partials.language-switch')<a class="nav-link" href="{{ route('storefront.tailoring.index',$storefront) }}">{{ __('storefront.tailoring.all_services') }}</a></div></div></nav>
<main class="section"><div class="shell booking-shell">
@if(session('success'))<div class="success">{{ session('success') }}</div>@endif
<div class="booking-card">
<div style="display:flex;justify-content:space-between;gap:15px;align-items:center;flex-wrap:wrap"><div><small>{{ app()->getLocale()==='ur'?'بکنگ حوالہ':'Booking reference' }}</small><div class="booking-ref">{{ $booking->reference }}</div></div><span class="booking-status {{ $booking->status }}">{{ \App\Models\StorefrontInquiry::statuses()[$booking->status] ?? $booking->status }}</span></div>
@unless($authorized)
<hr><h1 style="font-size:1.45rem">{{ app()->getLocale()==='ur'?'اپنی بکنگ دیکھیں':'View your booking' }}</h1><p>{{ app()->getLocale()==='ur'?'وہی فون نمبر اور چھ ہندسوں کا پن درج کریں جو بکنگ کے وقت استعمال کیا تھا۔':'Enter the phone number and six-digit PIN used when placing the booking.' }}</p>
@if($errors->any())<div class="errors"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('storefront.tailoring.bookings.authenticate',[$storefront,$booking->reference]) }}">@csrf<div class="booking-grid"><div class="form-group"><label for="phone">{{ __('storefront.common.phone') }}</label><input id="phone" name="phone" required dir="ltr" class="control" value="{{ old('phone') }}"></div><div class="form-group"><label for="pin">{{ __('storefront.tailoring.booking_pin') }}</label><input id="pin" name="pin" type="password" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required dir="ltr" class="control"></div></div><button class="btn">{{ app()->getLocale()==='ur'?'بکنگ کھولیں':'Open booking' }}</button></form>
@else
<hr><div class="booking-grid">
<div class="booking-field"><strong>{{ __('storefront.tailoring.name') }}</strong>{{ $booking->customer_name }}</div>
<div class="booking-field"><strong>{{ __('storefront.tailoring.service') }}</strong>{{ $booking->service?->name }}</div>
<div class="booking-field"><strong>{{ __('storefront.tailoring.suit_quantity') }}</strong>{{ $booking->suit_quantity }}</div>
<div class="booking-field"><strong>{{ __('storefront.tailoring.measurement_method') }}</strong>{{ __('storefront.tailoring.measurement_methods.'.$booking->measurement_method) }}</div>
<div class="booking-field"><strong>{{ __('storefront.tailoring.preferred_date') }}</strong>{{ $booking->preferred_date?->format('d-m-Y') ?: '—' }}</div>
<div class="booking-field"><strong>{{ app()->getLocale()==='ur'?'دکان کی طے کردہ تاریخ':'Shop promised date' }}</strong>{{ $booking->promised_date?->format('d-m-Y') ?: '—' }}</div>
<div class="booking-field"><strong>{{ app()->getLocale()==='ur'?'ابتدائی قیمت':'Estimated price' }}</strong>{{ $booking->estimated_price !== null ? 'Rs '.number_format((float)$booking->estimated_price,2) : '—' }}</div>
<div class="booking-field"><strong>{{ app()->getLocale()==='ur'?'حتمی قیمت':'Final price' }}</strong>{{ $booking->final_price !== null ? 'Rs '.number_format((float)$booking->final_price,2) : '—' }}</div>
</div>
@if($booking->status===\App\Models\StorefrontInquiry::STATUS_CONFIRMED && $booking->order)<div class="success" style="margin-top:18px"><strong>{{ app()->getLocale()==='ur'?'ٹیلرنگ آرڈر بن گیا ہے۔':'Your tailoring order has been created.' }}</strong><br>{{ app()->getLocale()==='ur'?'کام کی موجودہ حالت:':'Current workshop status:' }} {{ \App\Models\Order::STATUS_LABELS[$booking->order->status] ?? $booking->order->status }}</div>@endif
@if($booking->status===\App\Models\StorefrontInquiry::STATUS_REJECTED)<div class="errors" style="margin-top:18px"><strong>{{ app()->getLocale()==='ur'?'وجہ:':'Reason:' }}</strong> {{ $booking->rejection_reason }}</div>@endif
@if($booking->message)<div class="booking-field" style="margin-top:14px"><strong>{{ __('storefront.tailoring.details_question') }}</strong>{{ $booking->message }}</div>@endif
@endunless
</div></div></main>
@endsection
