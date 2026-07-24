@php
    $methodDetails = [
        \App\Models\StorefrontOrder::PAYMENT_EASYPAISA => [
            'title' => $storefront->easypaisa_account_title,
            'number' => $storefront->easypaisa_account_number,
        ],
        \App\Models\StorefrontOrder::PAYMENT_JAZZCASH => [
            'title' => $storefront->jazzcash_account_title,
            'number' => $storefront->jazzcash_account_number,
        ],
        \App\Models\StorefrontOrder::PAYMENT_BANK_TRANSFER => [
            'title' => $storefront->bank_account_title,
            'number' => $storefront->bank_account_number,
            'bank' => $storefront->bank_name,
            'iban' => $storefront->bank_iban,
        ],
        \App\Models\StorefrontOrder::PAYMENT_RAAST => [
            'title' => $storefront->raast_account_title,
            'number' => $storefront->raast_id,
        ],
    ];
@endphp
@foreach($methodDetails as $methodValue => $details)
    @if($storefront->acceptsPaymentMethod($methodValue) || array_key_exists($methodValue, $storefront->acceptedInquiryPaymentMethods()))
        <section class="payment-instructions" data-payment-instructions="{{ $methodValue }}" hidden>
            <strong>{{ \App\Models\StorefrontOrder::publicPaymentMethods()[$methodValue] }}</strong>
            <p>{{ __('storefront.payment.send_then_reference') }}</p>
            @if(!empty($details['bank']))<div><span>{{ __('storefront.payment.bank') }}:</span> <b>{{ $details['bank'] }}</b></div>@endif
            @if(!empty($details['title']))<div><span>{{ __('storefront.payment.account_title') }}:</span> <b>{{ $details['title'] }}</b></div>@endif
            @if(!empty($details['number']))<div><span>{{ $methodValue === \App\Models\StorefrontOrder::PAYMENT_RAAST ? __('storefront.payment.raast_id') : __('storefront.payment.account_number') }}:</span> <b dir="ltr">{{ $details['number'] }}</b></div>@endif
            @if(!empty($details['iban']))<div><span>IBAN:</span> <b dir="ltr">{{ strtoupper($details['iban']) }}</b></div>@endif
            @if($methodValue === \App\Models\StorefrontOrder::PAYMENT_RAAST && $storefront->raast_qr_url)
                <img class="raast-payment-qr" src="{{ $storefront->raast_qr_url }}" alt="{{ __('storefront.payment.raast_qr_alt') }}">
            @endif
        </section>
    @endif
@endforeach
