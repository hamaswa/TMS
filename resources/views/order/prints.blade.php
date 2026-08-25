<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}" type="image/x-icon">

    <!-- Latest compiled JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/css/bootstrap.min.css">

    <title>Tailor Managment Order Recipt</title>
    <style>
         @font-face {
    font-family: 'Noto Nastaliq Urdu';
    src: url('/assets/fonts/noto-nastaliq-urdu/NotoNastaliqUrdu-VariableFont_wght.woff2') format('woff2');
    font-weight: 100 900;
    font-style: normal;
    font-display: swap;
}

body {
    font-family: 'Noto Nastaliq Urdu', serif;
}

        /* #invoice-POS {
            box-shadow: 0 0 1in -0.25in rgba(0, 0, 0, 0.5);
            padding: 2mm;
            margin-left: 10px !important;
            width: 88mm;
            background: #FFF;

        } */
        #invoice-POS {
            box-shadow: 0 0 1in -0.25in rgba(0, 0, 0, 0.5);
            padding-left: 5mm; /* Remove all padding */
            padding-right: 0mm;
            margin: 0; /* Ensure no margin at the top */
            width: 100mm; /* Increase the width */
            background: #FFF;
}

        ::selection {
            background: #f31544;
            color: #FFF;
        }

        ::moz-selection {
            background: #f31544;
            color: #FFF;
        }

        h1,
        h2,
        h3 {
            color: #000;
            /* Darken the font color */
        }

        h1 {
            font-size: 18px;
            /*color: #222;*/
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
            min-height: 100px;
        }

        #mid {
            min-height: 80px;
        }

        #bot {
            min-height: 50px;
        }

        #top .logo {
            //float: left;
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
            width: 100%;
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

        .measurement-row {
            align-items: flex-start;
            margin-bottom: 4px;
        }

        .measurement-row,
        .measurement-row * {
            box-sizing: border-box;
            line-height: 1.5 !important;
        }

        .measurement-label {
            color: #000 !important;
            font-weight: 900 !important;
        }

        .measurement-value {
            color: #000 !important;
            font-weight: 900 !important;
        }

        .measurement-header {
            padding: 5mm 8px 0;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .measurement-logo-wrap {
            margin: 0 !important;
            line-height: 1;
        }

        .measurement-logo {
            display: block;
            width: 82px;
            max-height: 70px;
            margin: 0 auto;
            object-fit: contain;
        }

        .measurement-shop-name {
            margin: 4px 0 7px !important;
            font-weight: 900 !important;
            line-height: 1.4;
            text-align: center;
        }

        .measurement-meta {
            padding-right: 8px !important;
            padding-left: 8px !important;
        }

        .measurement-meta-row {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            align-items: start;
            gap: 12px;
            margin-bottom: 7px !important;
        }

        .measurement-meta-cell {
            min-width: 0;
            color: #000;
            font-size: 15px;
            font-weight: 800;
            line-height: 1.45;
        }

        .measurement-serial {
            direction: ltr;
            font-family: Arial, sans-serif;
            font-size: 14px;
            font-weight: 800;
            text-align: left;
            white-space: nowrap;
        }

        .measurement-footer {
            margin-top: 8px;
            padding: 7px 8px 5px;
            border-top: 1px solid #777;
            text-align: center;
        }

        .measurement-footer,
        .measurement-footer * {
            color: #000 !important;
            font-size: 14px !important;
            font-weight: 900 !important;
            line-height: 1.7 !important;
        }

        .measurement-footer p {
            margin: 0 !important;
        }

        .measurement-footer-contact {
            direction: ltr;
            font-family: Arial, sans-serif;
            letter-spacing: .2px;
        }

        .order-detail-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            direction: rtl;
        }

        .order-detail-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            min-height: 30px;
        }

        .order-detail-label {
            flex: 0 0 66%;
            color: #000;
            font-size: 18px;
            font-weight: 900;
            line-height: 1.65;
            text-align: right;
        }

        .order-detail-value {
            flex: 1 1 34%;
            min-width: 0;
            color: #000;
            direction: ltr;
            font-size: 18px;
            font-weight: 800;
            line-height: 1.5;
            overflow-wrap: anywhere;
            text-align: left;
        }

        .order-footer {
            width: 100%;
            margin-top: 10px;
            padding: 9px 6px 5px;
            border-top: 1px solid #777;
            text-align: center;
        }

        .order-footer,
        .order-footer * {
            color: #000 !important;
            font-size: 13px !important;
            font-weight: 900 !important;
            line-height: 1.7 !important;
        }

        .order-footer p {
            margin: 0 !important;
            padding: 0 !important;
        }

        .order-footer-contact {
            direction: ltr;
            font-family: Arial, sans-serif;
            letter-spacing: .2px;
        }

        .order-detail-date {
            font-size: 17px;
            white-space: nowrap;
        }

        .order-tracking-qr {
            direction: rtl;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin: 12px 0 7px;
            padding: 9px 0;
            border-top: 1px solid #999;
            border-bottom: 1px solid #999;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .order-tracking-qr svg {
            display: block;
            flex: 0 0 105px;
            width: 105px;
            height: 105px;
        }

        .order-tracking-qr__text {
            max-width: 145px;
            color: #000;
            font-size: 12px;
            font-weight: 900;
            line-height: 1.9;
            text-align: right;
        }

        @media print {
            @page {
                margin: 0;
            }

            html,
            body,
            #invoice-POS {
                margin-top: 0 !important;
                padding-top: 0 !important;
            }

            #top {
                height: 0 !important;
                min-height: 0 !important;
                overflow: hidden;
                border: 0;
            }

            #orderSection {
                margin-top: 0 !important;
            }

            #sizeSection {
                margin-top: 0 !important;
                padding-top: 0 !important;
            }

            #orderSection > p:first-child {
                margin: 0 !important;
                line-height: normal;
            }

            .btn {
                display: none;
            }
        }

        /* .printbtn{
                position: relative;
            } */
    </style>
</head>

<body>

    <div id="invoice-POS">

        <center id="top">
            <div class="info">
                <div class="naap-button">
                    <button class="btn btn-primary btn-sm go">آرڈر پر واپس جائیں </button>
                        <button class="btn btn-primary btn-sm naap">پمائیش</button>
                        <button class="btn btn-primary btn-sm order">آرڈر تفصیل</button>
                        <button class="btn btn-primary btn-sm full">مکمل </button>
                </div>
            </div><!--End Info-->
        </center><!--End InvoiceTop-->
        <div id="fullSection">
            <div id="orderSection" style="max-width: 350px; margin-top:-60px;" class="ticket order-section">
                <p align="center"><img src="{{ asset('images/setting/' . $setting->logo) }}" width="100"></p>
                <h1 class="text-center"  style="font-size: 16px;font-weight: 600;text-align: center;margin-top:-20px; ">{{ $setting->name }}
                </h1>
                <div class="pl-3 pr-3">
                    <div class="d-flex justify-content-between ">
                        <div>
                            <h2 style="font-size: 16px;font-weight:600;">invoice No # {{ $orderDetail->id }}
                            </h2>
                        </div>
                        <div>
                            <h2 style="font-weight:900;">{{ date('d-m-Y', strtotime($orderDetail->created_at)) }}</h2>
                        </div>
                    </div>
                    <hr>
                    <div class="order-detail-list">
                        <div class="order-detail-row"><span class="order-detail-label">نام:</span><strong class="order-detail-value">{{ $orderDetail->customers->name }}</strong></div>
                        <div class="order-detail-row"><span class="order-detail-label">سوٹ کی تعداد:</span><strong class="order-detail-value">{{ $orderDetail->suitQuantity }}</strong></div>
                        <div class="order-detail-row"><span class="order-detail-label">سیریل نمبر:</span><strong class="order-detail-value">{{ $orderDetail->sub_customer }}</strong></div>
                        <div class="order-detail-row"><span class="order-detail-label">آرڈر کی رقم:</span><strong class="order-detail-value">{{ $orderDetail->totalPayment }}</strong></div>
                        <div class="order-detail-row"><span class="order-detail-label">موجودہ رقم کی ادائیگی:</span><strong class="order-detail-value">{{ $orderDetail->transactions->first()?->recivedPayment ?? 0 }}</strong></div>
                        @if ($orderBalance > 0)
                            <div class="order-detail-row"><span class="order-detail-label">موجودہ ادائیگی واجب الادا:</span><strong class="order-detail-value">{{ $orderBalance }}</strong></div>
                        @endif
                        @if ($previousBalance > 0)
                            <div class="order-detail-row"><span class="order-detail-label">گزشتہ ادائیگی کے واجبات:</span><strong class="order-detail-value">{{ $previousBalance }}</strong></div>
                        @endif
                        @if ($latestBalance > 0)
                            <div class="order-detail-row"><span class="order-detail-label">کل ادائیگی واجب الادا:</span><strong class="order-detail-value">{{ $latestBalance }}</strong></div>
                        @endif
                        <div class="order-detail-row"><span class="order-detail-label">واپسی کی تاریخ:</span><strong class="order-detail-value order-detail-date">{{ $orderDetail->returnDate }}</strong></div>
                    </div>
                    <aside class="order-tracking-qr" data-tracking-url="{{ $trackingUrl }}" aria-label="آرڈر کی صورتحال دیکھنے کا QR کوڈ">
                        {!! $trackingQrSvg !!}
                        <div class="order-tracking-qr__text">آرڈر کی صورتحال اور بقایا دیکھنے کے لیے اسکین کریں۔</div>
                    </aside>
                    <div>
                        <h3 class="text-center font-weight-bold mt-2" style="font-size:18px;">{{$orderDetail->remarks}}
                        </h3>
                    </div>
                    <div class="order-footer">
                        <p>{!! $setting->address !!}</p>
                        <p class="order-footer-contact">{{ $setting->contact_no }}</p>
                        <p>{{ $setting->note }}</p>
                    </div>
                    <hr>
                </div>

                <!--<p style="text-align:center; font-size: 10px">{{ $setting->note }}</p>-->
                <!--<hr>-->
            </div>
            <div id="sizeSection" style="max-width: 350px;" class="ticket size-section">
                <div class="measurement-header">
                    <p align="center" class="measurement-logo-wrap"><img class="measurement-logo" src="{{asset('images/setting/' . $setting->logo)}}" alt=""></p>
                    <h1 class="text-center measurement-shop-name">{{$setting->name}}</h1>
                </div>
                <div class="pl-1 pr-1 measurement-meta">
                    <hr style="margin-top:4px;">
                    <div class="desing-flex measurement-meta-row">
                        <div class="measurement-meta-cell measurement-serial">
                            Serial num: {{$orderDetail->sub_customer}}
                        </div>
                        <div class="measurement-meta-cell" style="text-align:right;">
                            {{$orderDetail->customers->name}}
                        </div>
                    </div>
                    <div class="desing-flex measurement-meta-row">
                        <div class="measurement-meta-cell" style="text-align:left;">
                            {{$tailor->name}}
                        </div>
                        <div class="measurement-meta-cell" style="text-align:right;">
                            درزی کا نام
                        </div>
                    </div>
                    <div class="desing-flex measurement-meta-row">
                        <div class="measurement-meta-cell" style="direction:ltr;text-align:left;font-family:Arial,sans-serif;font-size:13px;white-space:nowrap;">
                            {{date('d-m-Y h:m A', strtotime($orderDetail->created_at))}}
                        </div>
                        <div class="measurement-meta-cell" style="direction:ltr;text-align:right;font-family:Arial,sans-serif;font-size:14px;white-space:nowrap;">
                            {{$orderDetail->customers->phone_number1}}
                        </div>
                    </div>
                    @if($order->measurementValues->count())
                    @php
                        $allMeasurements = $orderDetail->measurementValues->keyBy('source_key');

                        // Left column (Design fields)
                        $leftFields = [
                            'system.necktype',
                            'system.sleeve',
                            'system.Daaman',
                            'system.jeab',
                            'system.swingtype',
                            'system.button',
                            'system.plate_type',
                        ];

                        // Right column (Measurements)
                        $rightFields = [
                            'system.length',
                            'system.arms',
                            'system.teraa',
                            'system.senaChorai',
                            'system.damanchorai',
                            'system.shalwar',
                            'system.pancha',
                            'system.shalwarGheer',
                            'system.shoulder',
                            'system.chuta',
                        ];

        // Only keep system fields that were saved with this order.
        $leftFields = array_values(array_filter($leftFields, fn($key) => isset($allMeasurements[$key])));
        $rightFields = array_values(array_filter($rightFields, fn($key) => isset($allMeasurements[$key])));

        $customFields = $orderDetail->measurementValues
            ->filter(fn($item) => str_starts_with($item->source_key, 'custom.'))
            ->sortBy('sort_order')
            ->pluck('source_key')
            ->values()
            ->toArray();

        // Keep the original system rows together, then print custom fields in pairs.
        $systemRows = max(count($leftFields), count($rightFields));
        $leftFields = array_pad($leftFields, $systemRows, null);
        $rightFields = array_pad($rightFields, $systemRows, null);
        foreach (array_chunk($customFields, 2) as $customPair) {
            $leftFields[] = $customPair[0];
            $rightFields[] = $customPair[1] ?? null;
        }

        $rows = max(count($leftFields), count($rightFields));
                    @endphp

                    <hr>

                    @for($i = 0; $i < $rows; $i++)

                    <div class="row measurement-row" style="display:flex;justify-content:space-between;padding:0 10px;">

                        {{-- LEFT COLUMN --}}
                        <div class="col-6" style="width:45%;">

                            @if(isset($leftFields[$i]) && isset($allMeasurements[$leftFields[$i]]))

                                @php
                                    $item = $allMeasurements[$leftFields[$i]];
                                @endphp

                                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:6px;font-weight:600;">

                                    <span class="measurement-value" style="min-width:0; white-space:normal; overflow-wrap:anywhere;">{{ $item->value }}</span>

                                    <span class="measurement-label" style="flex-shrink:0; white-space:nowrap; font-size:20px;">{{ $item->label }}</span>

                                </div>

                            @endif

                        </div>

                        {{-- RIGHT COLUMN --}}
                        <div class="col-6" style="width:45%;">

                            @if(isset($rightFields[$i]) && isset($allMeasurements[$rightFields[$i]]))

                                @php
                                    $item = $allMeasurements[$rightFields[$i]];
                                @endphp

                                <div style="display:flex;align-items:flex-start;gap:6px;font-weight:600;padding:2px 4px;">

                                    <span class="measurement-value" style="width:35%; text-align:left; white-space:normal; overflow-wrap:anywhere;">
                                        {{ $item->value }}
                                    </span>

                                    <span class="measurement-label" style="width:65%; text-align:right; white-space:nowrap; font-size:20px;">
                                        {{ $item->label }}
                                    </span>

                                </div>

                            @endif

                        </div>

                    </div>

                    @endfor
                     @else
                    <div class="row" style="display: flex; justify-content: space-between; padding: 0 10px">
                        <div class="col-6 mt-2 mb-2" style="width: 45%">
                            <div class="d-flex justify-content-between"
                                style="display: flex;justify-content: space-between;font-weight:800;">
                                <div style="word-break: break-word; white-space: normal; min-width: 0;">
                                    {{$orderDetail->customers->necktype}}
                                </div>
                                <div style="font-weight:600; font-size:20px;">گلہ</div>
                            </div>
                        </div>
                        <div class="col-6" style="width: 45%">
                            <div class="d-flex justify-content-between"
                                style="display: flex;justify-content: space-between;font-weight:600;">
                                <div style="word-break: break-word; white-space: normal; min-width: 0;">
                                    {{$orderDetail->customers->length}}
                                </div>
                                <div style="font-weight:600; font-size:22px">لمبائی</div>
                            </div>
                        </div>
                    </div>

                    <div class="row" style="display: flex; justify-content: space-between; padding: 0 10px">
                        <div class="col-6" style="width: 45%">
                            <div class="d-flex justify-content-between"
                                style="display: flex;justify-content: space-between;font-weight:600;">
                                <div style="word-break: break-word; white-space: normal; min-width: 0;">
                                    {{$orderDetail->customers->sleeve}}
                                </div>
                                <div style="font-weight:600; font-size:20px;">کف</div>
                            </div>
                        </div>
                        <div class="col-6 mb-2" style="width: 45%">
                            <div class="d-flex justify-content-between"
                                style="display: flex;justify-content: space-between;font-weight:600;">
                                <div style="word-break: break-word; white-space: normal; min-width: 0;">
                                    {{$orderDetail->customers->arms}}
                                </div>
                                <div style="font-weight:600; font-size:20px;">بازو</div>
                            </div>
                        </div>
                    </div>

                    <div class="row" style="display: flex; justify-content: space-between; padding: 0 10px">
                        <div class="col-6 mt-2 mb-2" style="width: 45%">
                            <div class="d-flex justify-content-between"
                                style="display: flex;justify-content: space-between;font-weight:600;">
                                <div style="word-break: break-word; white-space: normal; min-width: 0;">
                                    {{ $orderDetail->customers->Daaman }}
                                </div>
                                <div style="font-weight:600; font-size:20px;">دامن</div>
                            </div>
                        </div>
                        <div class="col-6" style="width: 45%">
                            <div class="d-flex justify-content-between"
                                style="display: flex;justify-content: space-between;font-weight:600;">
                                <div>{{ $orderDetail->customers->teraa }}</div>
                                <div style="font-weight:600; font-size:20px;">تیرا</div>
                            </div>

                        </div>
                    </div>

                    <div class="row" style="display: flex; justify-content: space-between; padding: 0 10px">
                        <div class="col-6 mt-2 mb-2" style="width: 45%">
                            <div class="d-flex justify-content-between"
                                style="display: flex; justify-content: space-between;font-weight:600;">
                                <div style="word-break: break-word; white-space: normal; min-width: 0;">
                                    {{$orderDetail->customers->jeab}}
                                </div>
                                <div style="font-weight:600; font-size:20px;">جیب</div>
                            </div>
                        </div>
                        <div class="col-6" style="width: 45%">
                            <div class="d-flex justify-content-between"
                                style="display: flex; justify-content: space-between;font-weight:600;">
                                <div style="word-break: break-word; white-space: normal; min-width: 0;">
                                    {{$orderDetail->customers->senaChorai}}
                                </div>
                                <div style="font-weight:600; font-size:20px;">سینہ چوڑائی</div>
                            </div>
                        </div>
                    </div>

                    <div class="row" style="display: flex; justify-content: space-between; padding: 0 10px">
                        <div class="col-6 mt-2 mb-2" style="width: 45%">
                            <div class="d-flex justify-content-between mb-2" style="display: flex;font-weight:600;">
                                <div style="word-break: break-word; white-space: normal; min-width: 0;">
                                    {{ $orderDetail->customers->swingtype }}
                                </div>
                                <div style="font-weight:600; font-size:20px;">سلائی</div>
                            </div>
                        </div>
                        <div class="col-6" style="width: 45%">
                            <div class="d-flex justify-content-between"
                                style="display: flex;justify-content: space-between;font-weight:600;">
                                <div style="word-break: break-word; white-space: normal; min-width: 0;">
                                    {{ $orderDetail->customers->damanchorai }}
                                </div>
                                <div style="font-weight:600; font-size:20px;">دامن چوڑائی</div>
                            </div>
                        </div>

                    </div>

                    <div class="row" style="display: flex; justify-content: space-between; padding: 0 10px">
                        <div class="col-6 mt-2 mb-2" style="width: 45%">
                            <div class="d-flex justify-content-between mb-1"
                                style="display: flex;justify-content: space-between;font-weight:600;">
                                <div style="word-break: break-word; white-space: normal; min-width: 0;">
                                    {{ $orderDetail->customers->button }}
                                </div>
                                <div style="font-weight:600; font-size:20px;">بٹن</div>
                            </div>
                        </div>
                        <div class="col-6" style="width: 45%">
                            <div class="d-flex justify-content-between"
                                style="display: flex;justify-content: space-between;font-weight:600;">
                                <div style="word-break: break-word; white-space: normal; min-width: 0;">
                                    {{ $orderDetail->customers->shalwar }}
                                </div>
                                <div style="font-weight:600; font-size:20px;">شلوار</div>
                            </div>
                        </div>
                    </div>

                    <div class="row" style="display: flex; justify-content: space-between; padding: 0 10px">
                        <div class="col-6 mt-2 mb-2" style="width: 45%">
                            <div class="d-flex justify-content-between"
                                style="display: flex;justify-content: space-between;font-weight:600;">
                                <div style="word-break: break-word; white-space: normal; min-width: 0;">
                                    {{ $orderDetail->customers->plate_type }}
                                </div>
                                <div style="font-weight:600; font-size:20px;">پلیٹ</div>
                            </div>
                        </div>
                        <div class="col-6" style="width: 45%">
                            <div class="d-flex justify-content-between"
                                style="display: flex;justify-content: space-between;font-weight:600;">
                                <div style="word-break: break-word; white-space: normal; min-width: 0;">
                                    {{ $orderDetail->customers->pancha }}
                                </div>
                                <div style="font-weight:600; font-size:20px;">پانچہ</div>
                            </div>
                        </div>
                    </div>

                    <div class="row" style="display: flex; justify-content: space-between; padding: 0 10px">
                        <div class="col-6 mt-2 mb-2" style="width: 45%">

                        </div>
                        <div class="col-6 mt-2 " style="width: 45%">
                            <div class="d-flex justify-content-between"
                                style="display: flex;justify-content: space-between;font-weight:600;">
                                <div style="word-break: break-word; white-space: normal; min-width: 0;">
                                    {{ $orderDetail->customers->shalwarGheer }}
                                </div>
                                <div style="font-weight:600; font-size:20px;">شلوار گھیر</div>
                            </div>
                        </div>
                    </div>

                    <div class="row" style="display: flex; justify-content: space-between; padding: 0 10px">
                        <div class="col-6 " style="width: 45%">

                        </div>

                        <div class="col-6 mt-2 mb-2" style="width: 45%">
                            <div class="d-flex justify-content-between"
                                style="display: flex;justify-content: space-between;font-weight:600;">
                                <div style="word-break: break-word; white-space: normal; min-width: 0;">
                                    {{ $orderDetail->customers->shoulder }}
                                </div>
                                <div style="font-weight:600; font-size:20px;">مونڈا</div>
                            </div>
                        </div>

                    </div>
                    <div class="row" style="display: flex; justify-content: space-between; padding: 0 10px">
                        <div class="col-6 mt-2" style="width: 45%">

                        </div>

                        <div class="col-6  mt-2 mb-2" style="width: 45%">
                            <div class="d-flex justify-content-between"
                                style="display: flex;justify-content: space-between;font-weight:600;">
                                <div style="word-break: break-word; white-space: normal; min-width: 0;">
                                    {{ $orderDetail->customers->chuta }}
                                </div>
                                <div style="font-weight:600; font-size:20px;">چوتا</div>
                            </div>
                        </div>

                    </div>
                    @endif
                    <hr>
                    <div>
                        <div align="center">
                            <div>
                                <h3 class="text-center font-weight-400 mt-2" style="font-size: 16px;">{{$orderDetail->remarks}}</h3>
                            </div>
                            <div class="measurement-footer">
                                <p>{!! $setting->address !!}</p>
                                <p class="measurement-footer-contact">{{$setting->contact_no}}</p>
                            </div>
                        </div>
                    </div>
                    <!--<p style="text-align:center">{{$setting->note}}</p>-->
                </div>
            </div>
        </div>



    </div><!--End Invoice-->
    <script>
     $(document).ready(function () {

    var status = $('#status').val();

    if (status == 'default') {

        window.print();

    } else {

        $('.naap').on('click', function () {

            $('.naap-button').hide();
            $('.order-section').hide();

            window.print();

            $('.naap-button').show();
            $('.order-section').show();

        });

        $('.order').on('click', function () {

            $('.naap-button').hide();
            $('.size-section').hide();

            window.print();

            $('.naap-button').show();
            $('.size-section').show();

        });

        $('.full').on('click', function () {

            $('.naap-button').hide();

            window.print();

            $('.naap-button').show();

        });

    }

    // Back Button
    $('.go').on('click', function () {
        window.history.back();
    });

});

        //   to open print blade into new tab
        //   var orderId = {{ $order->id }};

        //  function openPrintViewInNewTab(orderId) {
        //     window.open(url, '_blank'); // Open the URL in a new tab
        // }

        // // Use the window.onload event handler to call the function once when the page loads
        // window.onload = function() {
        //     // Check if the page is the main window, not a popup window (to avoid multiple tabs)
        //     if (window.opener === null) {
        //         openPrintViewInNewTab(orderId);
        //     }
        // };
    </script>
</body>

</html>
