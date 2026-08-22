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
    font-weight: normal;
    font-style: normal;
    font-display: swap;
}
        body {
             font-family: 'Noto Nastaliq Urdu';
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
    margin-top: 25px;/* Ensure no margin at the top */
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
        h1, h2, h3 {
        color: #000; /* Darken the font color */
        }

        h1 {
            font-size: 20px;
            font-weight: 900;
            /*color: #222;*/
        }

        h2 {
            font-size: 22px;
            font-weight: 900;
        }

        h3 {
            font-size: 23px;
            font-weight: 900;
            /*line-height: 2em;*/
        }

        p {
            font-size: 16px;
            font-weight: 900;
            color: #000;
            line-height: 20px;
            margin: 0px;
            /*padding: 10px 0 0 0;*/
        }

        #top,
        #mid,
        #bot {
            /* Targets all id with 'col-' */
            border-bottom: 1px solid #EEE;
        }

        #top {
            /* new change */
            min-height: 10px;
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
            /*margin-bottom: 10px;*/
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

        .order-detail-date {
            font-size: 17px;
            white-space: nowrap;
        }
        .size .col-6{
            font-weight:300;
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

            #orderSection > p:first-child {
                margin: 0 !important;
                line-height: normal;
            }

            .btn {
                display: none !important;
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
            <!--Print Button-->
            <div class="btn btn-group printbtn">
                <button class="btn btn-primary print-order" style="padding: 5px 10px; font-size: 14px;">
                    آرڈر
                </button>
                <button class="btn btn-primary go" style="padding: 5px 10px; font-size: 14px;">
                    واپس جائیں
                </button>
                <button class="btn btn-primary print-length" style="padding: 5px 10px; font-size: 14px;">
                    پمائیش
                </button>
                <button class="btn btn-primary print-full" style="padding: 5px 10px; font-size: 14px;">
                    مکمل
                </button>
            </div>
        </center><!--End InvoiceTop-->

        <div id="fullSection">
            <div id="orderSection" style="max-width: 350px;margin-top:-30px;" class="ticket order-section">
                <p align="center"><img src="{{ asset('images/setting/' . $setting->logo) }}" width="100"></p>
                <h1 class="text-center" style="text-align: center;margin-top:-10px; ">{{ $setting->name }}
                </h1>
                <div class="pl-3 pr-3" style="margin-top: 0px">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h2>invoice No # {{ $orderDetail->id }}
                            </h2>
                        </div>
                        <div style="font-weight:900;">
                            <h3>{{ date('d-m-Y', strtotime($orderDetail->created_at)) }}</h3>
                        </div>
                    </div>
                    <hr>
                    <div class="order-detail-list">
                        <div class="order-detail-row"><span class="order-detail-label">نام:</span><strong class="order-detail-value">{{ $orderDetail->customers->name }}</strong></div>
                        <div class="order-detail-row"><span class="order-detail-label">سوٹ کی تعداد:</span><strong class="order-detail-value">{{ $orderDetail->suitQuantity }}</strong></div>
                        <div class="order-detail-row"><span class="order-detail-label">سیریل نمبر:</span><strong class="order-detail-value">{{ $orderDetail->sub_customer }}</strong></div>
                        <div class="order-detail-row"><span class="order-detail-label">آرڈر کی رقم:</span><strong class="order-detail-value">{{ $orderDetail->totalPayment }}</strong></div>
                        <div class="order-detail-row"><span class="order-detail-label">موجودہ رقم کی ادائیگی:</span><strong class="order-detail-value">{{ $orderDetail->transactions->first()?->recivedPayment ?? 0 }}</strong></div>
                        @if ($latestBalance - $previousBalance > 0)
                            <div class="order-detail-row"><span class="order-detail-label">موجودہ ادائیگی واجب الادا:</span><strong class="order-detail-value">{{ $latestBalance - $previousBalance }}</strong></div>
                        @endif
                        @if ($previousBalance > 0)
                            <div class="order-detail-row"><span class="order-detail-label">گزشتہ ادائیگی کے واجبات:</span><strong class="order-detail-value">{{ $previousBalance }}</strong></div>
                        @endif
                        @if ($latestBalance > 0)
                            <div class="order-detail-row"><span class="order-detail-label">کل ادائیگی واجب الادا:</span><strong class="order-detail-value">{{ $latestBalance }}</strong></div>
                        @endif
                        <div class="order-detail-row"><span class="order-detail-label">واپسی کی تاریخ:</span><strong class="order-detail-value order-detail-date">{{ $orderDetail->returnDate }}</strong></div>
                    </div>
                    <div>
                        <h3 class="text-center font-weight-900;" style="font-size: 18px; margin: 25px 0 0 0;">{{$orderDetail->remarks}}</h3>
                    </div>
                    <hr>
                    <div style="width: 100%;font-weight:900;" align="center">
                        <p><b style="font-size: 14px;padding:10px;">{!! $setting->address !!}</b></p>
                        <p><b style="font-size: 14px;padding:10px;">{{ $setting->contact_no }}</b></p>
                        <p style="text-align:center;padding:5px; font-weight-900"><b></b>{{ $setting->note }}</b></p>
                    </div>
                    <hr>
                </div>

                <!--<p style="text-align:center; font-size: 10px">{{ $setting->note }}</p>-->
                <!--<hr>-->
            </div>
            <div id="sizeSection" style="max-width: 350px;margin-top:-10px;" class="ticket size-section">
                <p align="center"> <img src="{{ asset('images/setting/' . $setting->logo) }}" width="100"></p>
                <h1 class="text-center" style="font-weight:800;margin-top:-5px;">{{ $setting->name }}</h1>
                <div class="pl-3 pr-3">
                    <hr>
                    <div class="desing-flex">
                        <div>
                            <p style="font-size:18px; font-weight:700;">Serial num: {{$orderDetail->sub_customer}}</p>
                        </div>
                        <div>
                            <p style="font-size:18px; font-weight:700;">{{$orderDetail->customers->name}}</p>
                        </div>
                    </div>
                    <div class="desing-flex">
                        <div style="font-size:19px;">
                            <p style="font-size:16px; font-Weight:700; margin-top:10px"> {{$tailor->name}}</p>
                        </div>
                        <div>
                            <p style="font-size:16px; font-weight:700; margin-top:10px">  درزی کا نام </P>
                        </div>
                    </div>
                    <div class="desing-flex">
                        <div>
                            <p style="font-weight:900; font-size:16px; padding-top:10px">{{ date('d-m-Y h:m A', strtotime($orderDetail->created_at)) }}</p>
                        </div>
                        <div>
                            <p style="font-weight:900; font-size:18px; padding-top:10px">{{ $orderDetail->customers->phone_number1 }}</p>
                        </div>
                    </div>
                    <hr>

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
                                style="display: flex;justify-content: space-between;font-weight:600;">
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
                                <h3 class="text-center font-weight-900" style="font-size: 18px; margin-top:-20px; margin-bottom:0px">{{$orderDetail->remarks}}</h3>
                            </div>
                            <p><b style="font-size: 14px;">{!! $setting->address !!}</b></p>
                            <p><b style="font-size: 14px;">{{ $setting->contact_no }}</b></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>



    </div><!--End Invoice-->
    <script>
        // $(document).ready(function() {
        //     function PrintElem(id) {
        //         var divContents = document.getElementById(id).innerHTML;

        //         var mywindow = window.open('', 'PRINT', 'height=500,width=322');

        //         mywindow.document.write('</head><body >');
        //         //              mywindow.document.write('<h1>' + document.title  + '</h1>');
        //         mywindow.document.write(divContents);
        //         mywindow.document.write('</body></html>');

        //         mywindow.document.close(); // necessary for IE >= 10
        //         mywindow.focus(); // necessary for IE >= 10*/

        //         mywindow.print();
        //         //              mywindow.close();

        //         return true;
        //     }

        //     var status = $('#status').val();
        //     //          var elemTarget = $("#myDiv");
        //     //          var elemId = 'myDiv';
        //     if (status == 'default') {
        //         //        window.print();
        //         //            $("#myDiv").printElement();
        //         PrintElem("myDiv");
        //     } else {
        //         $('.naap').on('click', function() {
        //             $('.naap-button, .order-section').css('display', 'none');
        //             //                window.print();
        //             //                $("#myDiv").printElement();
        //             PrintElem("orderSection");
        //         });

        //         $('.order').on('click', function() {
        //             $('.naap-button, .size-section').css('display', 'none');
        //             //                window.print();
        //             //                 $("#myDiv").printElement();
        //             PrintElem("sizeSection");
        //         });
        //         $('.full').on('click', function() {
        //             $('.naap-button').css('display', 'none');
        //             //                window.print();
        //             //                  $("#myDiv").printElement();
        //             PrintElem("fullSection");
        //         });
        //     }

        // });

        //new js code
        // Function to print only the order details
        function printOrderDetails() {
            // Hide the fullSection and show only the orderSection
            document.getElementById('sizeSection').style.display = 'none';
            document.getElementById('orderSection').style.display = 'block';

            // Print the order details
            window.print();

            // Show the fullSection again after printing
            document.getElementById('sizeSection').style.display = 'block';
            document.getElementById('orderSection').style.display = 'block';
        }

        //function to print length details
        function printLength() {
            document.getElementById('sizeSection').style.display = 'block';
            document.getElementById('orderSection').style.display = 'none';

            //print details
            window.print();

            //show after prin
            document.getElementById('sizeSection').style.display = 'block';
            document.getElementById('orderSection').style.display = 'block';
        }

        // Function to print the whole page
        function printWholePage() {
            // Print the entire page
            window.print();
        }

        // Event listener for the رسید پرنٹ کریں۔ button
        document.querySelector('.printbtn .print-order').addEventListener('click', function() {
            printOrderDetails();
        });

        // Event listener for the مکمل رسید پرنٹ کریں۔ button
        document.querySelector('.printbtn .print-full').addEventListener('click', function() {
            printWholePage();
        });

        // Event listener for the length button
        document.querySelector('.printbtn .print-length').addEventListener('click', function() {
            printLength();
        });
        //to go back to order
        document.querySelector('.go').addEventListener('click', function() {
            window.history.back();
        });

        //to print reciept
        // var printpage = document.querySelector(".btn button");
        // printpage.addEventListener('click', function() {
        //     window.print();
        // });


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

    {{-- <script>
    function printReceipt() {
        // Open a new window with the URL of prints.blade.php
        var printWindow = window.open('{{ route('admin.prints') }}', '_blank', 'width=800,height=600');

        // Check if the new window was successfully opened
        if (printWindow) {
            // Focus on the new window
            printWindow.focus();
        } else {
            // Display an error message if the window couldn't be opened
            alert('Could not open print window. Please ensure pop-ups are allowed for this site.');
        }
    }
</script> --}}



</body>

</html>
