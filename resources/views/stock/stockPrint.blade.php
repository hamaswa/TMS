<!DOCTYPE html>
<html lang="ur" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}" type="image/x-icon">

    <!-- Latest compiled JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/css/bootstrap.min.css">

    <title>کپڑے کی فروخت کی رسید</title>
    <style>
     @font-face {
    font-family: 'Noto Nastaliq Urdu';
    src: url('/assets/fonts/noto-nastaliq-urdu/NotoNastaliqUrdu-VariableFont_wght.woff2') format('woff2');
    font-weight: normal;
    font-style: normal;
    font-display: swap;
}
        body {
            font-family: 'Noto Nastaliq Urdu', serif;
        }

        #invoice-POS {
    box-shadow: 0 0 1in -0.25in rgba(0, 0, 0, 0.5);
    padding: 1mm; /* Reduced padding to fit content better */
    margin-left: 8px !important;
    width: 88mm;
    background: #FFF;
    margin-top: 20px;
    overflow: hidden; /* Add this line to ensure content doesn't overflow */
}


        ::selection {
            background: #f31544;
            color: #FFF;
        }

        ::moz-selection {
            background: #f31544;
            color: #FFF;
        }
        h1,h2,h3{
            color: #000;
        }

        h1 {
            font-size: 18px;
            /* color: #222; */
        }

        h2 {
            font-size: 12px;
        }

        h3 {
            font-size: 12px;
            font-weight: 300;
            line-height: 2em;
        }

        p {
            font-size: 10px;
            color: #666;
            line-height: 1px;
        }

        #top,
        #mid,
        #bot {
            /* Targets all id with 'col-' */
            border-bottom: 1px solid #EEE;
        }

        #top {
            min-height: 10px;
        }

        #mid {
            min-height: 80px;
        }

        #bot {
            min-height: 50px;
        }

        #top .logo {
            /* //float: left; */
            height: 60px;
            width: 60px;
            background: url(http://michaeltruong.ca/images/logo1.png) no-repeat;
            background-size: 60px 60px;
        }

        .clientlogo {
            float: left;
            height: 60px;
            width: 60px;
            background: url(http://michaeltruong.ca/images/client.jpg) no-repeat;
            background-size: 60px 60px;
            border-radius: 50px;
        }

        .info {
            display: block;
            margin-left: 0;
        }

        .title {
            float: right;
        }

        .title p {
            text-align: right;
        }

        table {
            width: 90%;
            border-collapse: collapse;
        }

        .tabletitle {
            font-size: 14px;
            background: #EEE;
        }

        .service {
            border-bottom: 1px solid #EEE;
        }

        .item {
            width: 24mm;
        }

        .itemtext {
            font-size: 8px;
        }

        #legalcopy {
            margin-top: 5mm;
        }

        .desing-flex {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .printbtn {
            position: static !important;
            display: flex !important;
            gap: 8px;
            width: 100%;
            padding: 8px 0 !important;
            margin: 0 0 10px !important;
        }

        .printbtn button {
            position: static !important;
            padding: 5px 8px !important;
        }

        #orderSection {
            margin-top: 0 !important;
        }

        .counter-sale-management {
            max-width: 760px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #d9e2ec;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 8px 24px rgba(15, 45, 70, .08);
        }

        .cancelled-receipt {
            position: relative;
        }

        .cancelled-receipt::after {
            content: 'منسوخ شدہ';
            position: absolute;
            top: 42%;
            right: 8%;
            left: 8%;
            z-index: 5;
            color: rgba(185, 28, 28, .22);
            font-size: 42px;
            font-weight: 900;
            text-align: center;
            transform: rotate(-18deg);
            pointer-events: none;
        }

        @media print {
            .btn,
            .no-print,
            .modal,
            .modal-backdrop {
                display: none !important;
            }
        }

        /* .printbtn{
                position: relative;
            } */
    </style>
    @include('print.partials.document-styles')
    @include('stock.partials.pos80-styles')
</head>

<body class="tms-stock-print">
    @include('print.partials.toolbar')

        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <strong>فروخت منسوخ نہیں ہو سکی:</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($receipt?->status === 'cancelled')
            <div class="alert alert-danger mb-0" role="status">
                <h2 class="h5">یہ کاؤنٹر فروخت منسوخ ہو چکی ہے</h2>
                <p class="mb-1">رسید: <strong>{{ $receipt->receipt_number }}</strong></p>
                <p class="mb-1">وجہ: {{ $receipt->cancellation_reason }}</p>
                <p class="mb-0">اسٹاک اور گاہک کا کھاتہ واپس کر دیا گیا ہے۔ منسوخی:
                    {{ optional($receipt->cancelled_at)->format('d-m-Y h:i A') }}</p>
                @if ($cancellationTransaction && (float) $cancellationTransaction->recivedPayment !== 0.0)
                    <p class="mb-0">رقم واپسی:
                        <strong>Rs:{{ number_format(abs((float) $cancellationTransaction->recivedPayment), 2) }}</strong>
                        — {{ \App\Support\PaymentMethods::label($cancellationTransaction->payment_method) }}
                        @if ($cancellationTransaction->payment_reference)
                            ({{ $cancellationTransaction->payment_reference }})
                        @endif
                    </p>
                @endif
            </div>
            @endif

    <div id="invoice-POS" @class(['cancelled-receipt' => $receipt?->status === 'cancelled'])>

        <!--Print Button-->
        <div class="btn printbtn">
            <button class="btn btn-primary"
                style="position: absolute; top: 5%; left: 0; padding: 0px 10px; text-align: center;">
                <span style="display: inline-block; vertical-align: middle; line-height: normal;font-size:18px;">رسید
                    پرنٹ کریں۔</span>
            </button>
            <button class="btn btn-primary go"
                style="position: absolute; top: 5%; left: 14.5%; padding: 0px 10px; text-align: center;">
                <span style="display: inline-block; vertical-align: middle; line-height: normal;font-size:18px;"> فروخت
                    پر واپس جائیں</span>
            </button>

        </div>



        <div id="fullSection">

            <div id="orderSection" style="max-width: 380px; margin-top: -25px;" class="ticket order-section">
                <div class="pl-3 pr-3" style="margin-top: 10px">
                    @if($setting?->logo_url)
                        <p class="stock-receipt-logo"><img src="{{ $setting->logo_url }}" width="100" alt="{{ $setting->name }} لوگو"></p>
                    @endif
                    <h1 class="text-center stock-receipt-shop" style="font-size: 16px;font-weight: 600;text-align: center">
                        {{ $setting?->name ?: (auth()->user()->business?->name ?: auth()->user()->name) }}</h1>
                        <h5 class="stock-receipt-number" style="font-size: 16px;font-weight: 600;text-align: center">
                            رسید نمبر: {{ $receipt?->receipt_number ?? $id }}</h5>
                    <div class="stock-customer-info">
                        <div class="stock-customer-row"><span>نام:</span><strong>{{ $customerName }}</strong></div>
                        <div class="stock-customer-row is-ltr"><span>موبائل نمبر:</span><strong>{{ $phone ?: '—' }}</strong></div>
                        <div class="stock-customer-row is-ltr"><span>تاریخ:</span><strong>{{ $latestSaleStock->sellDate ? \Illuminate\Support\Carbon::parse($latestSaleStock->sellDate)->format('d-m-Y h:i A') : '—' }}</strong></div>
                    </div>
                    @php $totalAmount = 0; @endphp
                    <div class="stock-items-list">
                        @foreach ($sellStock as $sellstock)
                            @php
                                $itemTotal = $sellstock->length * $sellstock->selling_price;
                                $totalAmount += $itemTotal;
                            @endphp
                            <div class="stock-item-card">
                                <div class="stock-order-row"><span class="stock-order-label">برانڈ:</span><strong class="stock-order-value">{{ $sellstock->brand->name }}</strong></div>
                                <div class="stock-order-row"><span class="stock-order-label">قسم:</span><strong class="stock-order-value">{{ $sellstock->type->name }}</strong></div>
                                <div class="stock-order-row"><span class="stock-order-label">رنگ:</span><strong class="stock-order-value">{{ $sellstock->color }}</strong></div>
                                <div class="stock-order-row"><span class="stock-order-label">میٹر:</span><strong class="stock-order-value">{{ $sellstock->length }}</strong></div>
                                <div class="stock-order-row"><span class="stock-order-label">ٹوٹل:</span><strong class="stock-order-value">{{ number_format($itemTotal, 2) }}</strong></div>
                                <div class="stock-order-row"><span class="stock-order-label">ریک:</span><strong class="stock-order-value">{{ $sellstock->clothes_rack }}</strong></div>
                            </div>
                        @endforeach
                    </div>
                    <div class="stock-order-summary">
                        <div class="stock-order-row"><span class="stock-order-label">ٹوٹل:</span><strong class="stock-order-value">{{ $totalAmount }}</strong></div>
                        <div class="stock-order-row"><span class="stock-order-label">ادائیگی:</span><strong class="stock-order-value">{{ $payment }}</strong></div>
                        <div class="stock-order-row"><span class="stock-order-label">ادائیگی واجب الادا:</span><strong class="stock-order-value">{{ $remaining }}</strong></div>
                        @if ($tailortransactions && $previousBalance > 0)
                            <div class="stock-order-row"><span class="stock-order-label">ٹیلرنگ اور فروخت:</span><strong class="stock-order-value">{{ $previousBalance + $tailortransactions->Balance }}</strong></div>
                        @elseif ($tailortransactions)
                            <div class="stock-order-row"><span class="stock-order-label">ٹیلرنگ:</span><strong class="stock-order-value">{{ $tailortransactions->Balance }}</strong></div>
                        @elseif ($previousBalance > 0)
                            <div class="stock-order-row"><span class="stock-order-label">سابقہ ادائیگیاں:</span><strong class="stock-order-value">{{ $previousBalance }}</strong></div>
                        @endif
                    </div>
                </div>

            </div>
        </div>

        <div class="stock-receipt-footer" style="width: 100%;">
            <div style="width: 100%;font-weight:900;" align="center">
                @if($setting?->address)<p><b style="font-size: 16px;">{{ $setting->address }}</b></p>@endif
                @if($setting?->contact_no)<p><b style="font-size: 16px;">{{ $setting->contact_no }}</b></p>@endif
            </div>
        </div>
        {{-- <p style="text-align:center">{{$setting->note}}</p> --}}
    </div>
    <div id="google_translate_element"></div>
    <script type="text/javascript">
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'ur'
            }, 'google_translate_element');
        }
    </script>

    </div><!--End Invoice-->
    <script>
        document.querySelector('.printbtn button:not(.go)')?.addEventListener('click', function () {
            window.print();
        });
        document.querySelector('.go')?.addEventListener('click', function () {
            window.history.back();
        });
    </script>



    @include('print.partials.qr')
    @include('components.confirmation-modal')
    <script src="{{ asset('assets/js/confirm-modal.js') }}"></script>
</body>

</html>
