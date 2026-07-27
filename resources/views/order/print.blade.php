@php($measurementSnapshot = $orderDetail->measurementValues->keyBy('source_key'))
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

    <title>ٹیلرنگ آرڈر اور پیمائش</title>
    <style>
     @font-face {
    font-family: 'Noto Nastaliq Urdu';
    src: url('/assets/fonts/noto-nastaliq-urdu/NotoNastaliqUrdu-VariableFont_wght.woff2') format('woff2');
    font-weight: normal;
    font-style: normal;
    font-display: swap;
}
        body {
             margin: 0;
             background: #f3f5f7;
             font-family: 'Noto Nastaliq Urdu';
        }

        * {
            box-sizing: border-box;
        }

        /* #invoice-POS {
            box-shadow: 0 0 1in -0.25in rgba(0, 0, 0, 0.5);
            padding: 2mm;
            margin-left: 10px !important;
            width: 88mm;
            background: #FFF;

        } */
        #invoice-POS {
            position: relative;
            width: 88mm;
            margin: 20px auto;
            padding: 4mm;
            overflow: hidden;
            background: #FFF;
            box-shadow: 0 0 1in -0.25in rgba(0, 0, 0, 0.5);
        }

        #fullSection,
        #orderSection,
        #sizeSection {
            position: relative;
            width: 100%;
            max-width: 100% !important;
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
        .receipt-meta {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 12px;
        }
        .receipt-meta h2,
        .receipt-meta h3 {
            margin: 0;
        }
        .receipt-date {
            direction: ltr;
            unicode-bidi: isolate;
            white-space: nowrap;
        }
        .order-summary {
            display: grid;
            gap: 7px;
            margin-top: 10px;
        }
        .order-summary-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(115px, auto);
            align-items: start;
            gap: 12px;
            direction: ltr;
            border-bottom: 1px dotted #e2e8f0;
            padding: 2px 0 5px;
        }
        .order-summary-label,
        .order-summary-value {
            margin: 0;
            font-size: 17px;
            font-weight: 900;
            line-height: 1.75;
            overflow-wrap: anywhere;
        }
        .order-summary-label {
            direction: rtl;
            text-align: right;
        }
        .order-summary-value {
            direction: ltr;
            unicode-bidi: isolate;
            text-align: left;
        }
        html.tms-paper-receipt_80 .receipt-meta h2,
        html.tms-paper-receipt_80 .receipt-meta h3 {
            font-size: 16px;
        }
        html.tms-paper-receipt_80 .order-summary-row {
            grid-template-columns: minmax(0, 1fr) minmax(105px, auto);
            gap: 7px;
        }
        html.tms-paper-receipt_80 .order-summary-label,
        html.tms-paper-receipt_80 .order-summary-value {
            font-size: 14px;
            line-height: 1.7;
        }
        .size .col-6{
            font-weight:300;
        }

        @media print {
            body {
                background: #fff;
            }

            #invoice-POS {
                width: 88mm;
                margin: 0;
                box-shadow: none;
            }

            .btn {
                display: none !important;
            }

            @page {
                size: 88mm auto;
                margin: 0;
            }
        }

        /* .printbtn{
                position: relative;
            } */
    </style>
    @include('print.partials.document-styles')
</head>

<body>
    @include('print.partials.toolbar')
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
                    پیمائش
                </button>
                <button class="btn btn-primary print-full" style="padding: 5px 10px; font-size: 14px;">
                    مکمل
                </button>
            </div>
        </center><!--End InvoiceTop-->

        <div id="fullSection">
            <div id="orderSection" style="max-width: 350px;margin-top:10px;" class="ticket order-section">
                @if($setting->logo_url)
                    <p align="center"><img src="{{ $setting->logo_url }}" width="100" alt="{{ $setting->name }} لوگو"></p>
                @endif
                <h1 class="text-center" style="text-align: center;margin-top:-10px; ">{{ $setting->name }}
                </h1>
                <div class="pl-3 pr-3" style="margin-top: 0px">
                    <div class="receipt-meta">
                        <div>
                            <h2>رسید نمبر # {{ $orderDetail->id }}
                            </h2>
                        </div>
                        <div style="font-weight:900;">
                            <h3 class="receipt-date">{{ date('d-m-Y', strtotime($orderDetail->created_at)) }}</h3>
                        </div>
                    </div>
                    <hr>
                    <div class="order-summary">
                        <div class="order-summary-row"><p class="order-summary-value">{{ $orderDetail->customers->name }}</p><p class="order-summary-label">نام:</p></div>
                        <div class="order-summary-row"><p class="order-summary-value">{{ $orderDetail->suitQuantity }}</p><p class="order-summary-label">سوٹ کی تعداد:</p></div>
                        <div class="order-summary-row"><p class="order-summary-value">{{ is_array(json_decode((string) $orderDetail->suitNum, true)) ? implode('، ', json_decode((string) $orderDetail->suitNum, true)) : (string) $orderDetail->suitNum }}</p><p class="order-summary-label">سیریل نمبر:</p></div>
                        <div class="order-summary-row"><p class="order-summary-value">{{ number_format((float) $orderDetail->totalPayment, 2) }}</p><p class="order-summary-label">آرڈر کی رقم:</p></div>
                        <div class="order-summary-row"><p class="order-summary-value">{{ number_format((float) ($orderDetail->transactions->first()?->recivedPayment ?? 0), 2) }}</p><p class="order-summary-label">موجودہ رقم کی ادائیگی:</p></div>
                        @if ($latestBalance - $previousBalance > 0)
                            <div class="order-summary-row"><p class="order-summary-value">{{ number_format((float) ($latestBalance - $previousBalance), 2) }}</p><p class="order-summary-label">موجودہ واجب الادا:</p></div>
                        @endif
                        @if ($previousBalance > 0)
                            <div class="order-summary-row"><p class="order-summary-value">{{ number_format((float) $previousBalance, 2) }}</p><p class="order-summary-label">گزشتہ واجبات:</p></div>
                        @endif
                        @if ($latestBalance > 0)
                            <div class="order-summary-row"><p class="order-summary-value">{{ number_format((float) $latestBalance, 2) }}</p><p class="order-summary-label">کل واجب الادا:</p></div>
                        @endif
                        <div class="order-summary-row"><p class="order-summary-value">{{ $orderDetail->returnDate }}</p><p class="order-summary-label">واپسی کی تاریخ:</p></div>
                    </div>
                    <div>
                        <h3 class="text-center font-weight-900;" style="font-size: 18px; margin: 25px 0 0 0;">{{$orderDetail->remarks}}</h3>
                    </div>
                    <hr>
                    <div style="width: 100%;font-weight:900;" align="center">
                        <p><b style="font-size: 14px;padding:10px;">{{ $setting->address }}</b></p>
                        <p><b style="font-size: 14px;padding:10px;">{{ $setting->contact_no }}</b></p>
                        <p style="text-align:center;padding:5px; font-weight-900"><b></b>{{ $setting->note }}</b></p>
                    </div>
                    <hr>
                </div>

                <!--<p style="text-align:center; font-size: 10px">{{ $setting->note }}</p>-->
                <!--<hr>-->
            </div>
            <div id="sizeSection" style="max-width: 350px;margin-top:-10px;" class="ticket size-section">
                @if($setting->logo_url)
                    <p align="center"><img src="{{ $setting->logo_url }}" width="100" alt="{{ $setting->name }} لوگو"></p>
                @endif
                <h1 class="text-center" style="font-weight:800;margin-top:-5px;">{{ $setting->name }}</h1>
                <div class="pl-3 pr-3">
                    <hr>
                    <div class="desing-flex">
                        <div>
                            <p style="font-size:18px; font-weight:700;">سیریل نمبر: {{ is_array(json_decode((string) $orderDetail->suitNum, true)) ? implode('، ', json_decode((string) $orderDetail->suitNum, true)) : (string) $orderDetail->suitNum }}</p>
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

                    <div class="row size" style="display: flex; justify-content: space-between; padding: 0 10px">
                        <div class="col-6 mt-0 mb-2" style="width: 45%">
                            <div class="d-flex justify-content-between;"
                                style="display: flex;justify-content: space-between">
                                <div style="word-break: break-word; white-space: normal; min-width: 0; font-weight:900; font-size:20px; ">
                                    <p>{{ $orderDetail->customers->necktype }}</p>
                                    </div>
                                <div><p style="font-weight:900; font-size:20px;">گلہ</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-6" style="width: 45%">
                            <div class="d-flex justify-content-between"
                                style="display: flex;justify-content: space-between;font-weight:600; ">
                                <div>
                                    <p style="direction: rtl; text-align: right;word-break: break-word; white-space: normal; min-width: 0; font-weight:900; font-size:18px;">{{ optional($measurementSnapshot->get('system.length'))->value ?? $orderDetail->customers->length }}</p>
                                </div>
                                <div>
                                    <p style="font-weight:900; font-size:20px;">
                                    لمبائی</p>
                                    </div>
                            </div>
                        </div>
                    </div>

                    <div class="row" style="display: flex; justify-content: space-between; padding: 0 10px">
                        <div class="col-6" style="width: 45%">
                            <div class="d-flex justify-content-between"
                                style="display: flex;justify-content: space-between;font-weight:600;">
                                <div style="word-break: break-word; white-space: normal; min-width: 0; font-weight:900; font-size:20px; padding-right:6px">
                                   <p>{{ $orderDetail->customers->sleeve }}</p>
                                </div>
                                <div>
                                    <P style="font-weight:900; font-size:20px;">کف</P>
                                    </div>
                            </div>
                        </div>
                        <div class="col-6  mb-2" style="width: 45%">
                            <div class="d-flex justify-content-between"
                                style="display: flex;justify-content: space-between;font-weight:600;">
                                <div>
                                   <p style="word-break: break-word; white-space: normal; min-width: 0; font-weight:900; font-size:18px;"> {{ optional($measurementSnapshot->get('system.arms'))->value ?? $orderDetail->customers->arms }}</p>
                                </div>
                                <div><P style="font-weight:900; font-size:20px;">بازو</P></div>
                            </div>
                        </div>
                    </div>

                    <div class="row" style="display: flex; justify-content: space-between; padding: 0 10px">
                        <div class="col-6 mt-2 mb-2" style="width: 45%">
                        <div class="d-flex justify-content-between"
                                style="display: flex;justify-content: space-between;font-weight:600;">
                                <div style="word-break: break-word; white-space: normal; min-width: 0; font-weight:900; font-size:20px;">
                                    <p>{{ $orderDetail->customers->Daaman }}</p>
                                    </div>
                                <div>
                                <P style="font-weight:900; font-size:20px;">دامن</P></div>
                            </div>
                        </div>
                        <div class="col-6" style="width: 45%">
                        <div class="d-flex justify-content-between">
                                <div><p style="display: flex;justify-content: space-between;font-weight:900;  font-size:18px;">{{ optional($measurementSnapshot->get('system.teraa'))->value ?? $orderDetail->customers->teraa }}</p></div>
                                <div>
                                <P style="font-weight:600; font-size:20px;">تیرا</P></div>
                            </div>

                        </div>
                    </div>

                    <div class="row" style="display: flex; justify-content: space-between; padding: 0 10px">
                        <div class="col-6 mt-2 mb-2" style="width: 45%">
                            <div class="d-flex justify-content-between" style="display: flex; justify-content: space-between;font-weight:600;">
                                <div style="word-break: break-word; white-space: normal; min-width: 0; font-weight:900; font-size:20px;">
                                   <p>{{$orderDetail->customers->jeab}}</p>
                                </div>
                                <div>
                                    <p style="font-weight:900; font-size:20px;">جیب</p></div>
                            </div>
                        </div>
                        <div class="col-6" style="width: 45%">
                            <div class="d-flex justify-content-between" style="display: flex; justify-content: space-between;font-weight:600;">
                                <div>
                                   <p style="word-break: break-word; white-space: normal; min-width: 0;font-weight:900; font-size:18px;">{{ optional($measurementSnapshot->get('system.senaChorai'))->value ?? $orderDetail->customers->senaChorai }}</p>
                                </div>
                                <div><p style="font-weight:900; font-size:20px;">سینہ چوڑائی</p></div>
                            </div>
                        </div>
                    </div>

                    <div class="row" style="display: flex; justify-content: space-between; padding: 0 10px">
                        <div class="col-6 mt-2 mb-2" style="width: 45%">
                            <div class="d-flex justify-content-between mb-2" style="display: flex;font-weight:600;">
                                <div style="word-break: break-word; white-space: normal; min-width: 0; font-weight:900; font-size:20px;">
                                    <p>{{ $orderDetail->customers->swingtype }}</p>
                                </div>
                                <div><p style="font-weight:900; font-size:20px;">سلائی</p></div>
                            </div>
                        </div>
                        <div class="col-6" style="width: 45%">
                            <div class="d-flex justify-content-between"
                                style="display: flex;justify-content: space-between;font-weight:600;">
                                <div>
                                    <p style="word-break: break-word; white-space: normal; min-width: 0;font-weight:900; font-size:18px;">{{ optional($measurementSnapshot->get('system.damanchorai'))->value ?? $orderDetail->customers->damanchorai }}</p>
                                </div>
                                <div><p style="font-weight:900; font-size:20px;">دامن چوڑائی</p></div>
                            </div>
                        </div>

                    </div>

                    <div class="row" style="display: flex; justify-content: space-between; padding: 0 10px">
                        <div class="col-6 mt-2 mb-2" style="width: 45%">
                            <div class="d-flex justify-content-between mb-1"
                                style="display: flex;justify-content: space-between;font-weight:600;">
                                <div style="word-break: break-word; white-space: normal; min-width: 0; font-weight:900; font-size:20px;">
                                    <p>{{ $orderDetail->customers->button }}</p>
                                </div>
                                <div><p style="font-weight:900; font-size:20px;">بٹن</p></div>
                            </div>
                        </div>
                        <div class="col-6" style="width: 45%">
                            <div class="d-flex justify-content-between"
                                style="display: flex;justify-content: space-between;font-weight:600;">
                                <div>
                                    <p style="word-break: break-word; white-space: normal; min-width: 0; font-weight:900; font-size:18px;">{{ optional($measurementSnapshot->get('system.shalwar'))->value ?? $orderDetail->customers->shalwar }}</p>
                                </div>
                                <div><p style="font-weight:900; font-size:20px;">شلوار</p></div>
                            </div>
                        </div>
                    </div>

                    <div class="row" style="display: flex; justify-content: space-between; padding: 0 10px">
                        <div class="col-6 mt-2 mb-2" style="width: 45%">
                            <div class="d-flex justify-content-between"
                                style="display: flex;justify-content: space-between;font-weight:600;">
                                <div style="word-break: break-word; white-space: normal; min-width: 0; font-weight:900; font-size:20px;">
                                   <p> {{ $orderDetail->customers->plate_type }}</p>
                                </div>
                                <div><p style="font-weight:900; font-size:20px;">پلیٹ</p></div>
                            </div>
                        </div>
                        <div class="col-6" style="width: 45%">
                            <div class="d-flex justify-content-between"
                                style="display: flex;justify-content: space-between;font-weight:600;">
                                <div>
                                    <p style="word-break: break-word; white-space: normal; min-width: 0; font-weight:900; font-size:18px;">{{ optional($measurementSnapshot->get('system.pancha'))->value ?? $orderDetail->customers->pancha }}</p>
                                </div>
                                <div><p style="font-weight:900; font-size:20px;">پانچہ</p></div>
                            </div>
                        </div>
                    </div>

                    <div class="row" style="display: flex; justify-content: space-between; padding: 0 10px">
                        <div class="col-6 mt-2 mb-2" style="width: 45%">

                        </div>
                        <div class="col-6 mt-2 " style="width: 45%">
                            <div class="d-flex justify-content-between"
                                style="display: flex;justify-content: space-between;font-weight:600;">
                                <div>
                                    <p style="word-break: break-word; white-space: normal; min-width: 0; font-weight:900; font-size:18px;">{{ optional($measurementSnapshot->get('system.shalwarGheer'))->value ?? $orderDetail->customers->shalwarGheer }}</p>
                                </div>
                                <div><p style="font-weight:900; font-size:20px;">شلوار گھیر</p></div>
                            </div>
                        </div>
                    </div>



                    <div class="row" style="display: flex; justify-content: space-between; padding: 0 10px">
                        <div class="col-6 " style="width: 45%">

                        </div>

                        <div class="col-6 mt-2 mb-2" style="width: 45%">
                            <div class="d-flex justify-content-between"
                                style="display: flex;justify-content: space-between;font-weight:600;">
                                <div>
                                    <p style="word-break: break-word; white-space: normal; min-width: 0; font-weight:900; font-size:18px;">{{ optional($measurementSnapshot->get('system.shoulder'))->value ?? $orderDetail->customers->shoulder }}</p>
                                </div>
                                <div><p style="font-weight:900; font-size:20px;">مونڈا</p></div>
                            </div>
                        </div>

                    </div>

                    <div class="row" style="display: flex; justify-content: space-between; padding: 0 10px">
                        <div class="col-6 mt-2" style="width: 45%">

                        </div>

                        <div class="col-6 mt-2 mb-2" style="width: 45%">
                        <div class="d-flex justify-content-between"
                                style="display: flex;justify-content: space-between;font-weight:600;">
                                <div>
                                    <p style="word-break: break-word; white-space: normal; min-width: 0; font-weight:900; font-size:18px;">{{ optional($measurementSnapshot->get('system.chuta'))->value ?? $orderDetail->customers->Chuta }}</p>
                                </div>
                                <div><p style="font-weight:900; font-size:20px;">چوتا</p></div>
                            </div>
                        </div>

                    </div>

                    @php($customMeasurements = $orderDetail->measurementValues->filter(fn ($measurement) => str_starts_with($measurement->source_key, 'custom.')))
                    @if($customMeasurements->isNotEmpty())
                        <div style="border-top:1px solid #333;padding:8px 10px;direction:rtl">
                            <p style="font-weight:900;font-size:18px;text-align:right;margin-bottom:6px">اضافی پیمائش</p>
                            <div style="display:flex;flex-wrap:wrap;justify-content:space-between">
                                @foreach($customMeasurements as $measurement)
                                    <div style="width:45%;display:flex;justify-content:space-between;margin-bottom:5px">
                                        <strong>{{ $measurement->label }}</strong><span>{{ $measurement->value }} @if($measurement->unit){{ $measurement->unit === 'inch' ? 'انچ' : 'سینٹی میٹر' }}@endif</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    <hr>
                    <div>
                        <div align="center">
                            <div>
                                <h3 class="text-center font-weight-900" style="font-size: 18px; margin-top:-20px; margin-bottom:0px">{{$orderDetail->remarks}}</h3>
                            </div>
                            <p><b style="font-size: 14px;">{{ $setting->address }}</b></p>
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



    @include('print.partials.qr')
</body>

</html>
