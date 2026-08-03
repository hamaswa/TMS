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
        .size .col-6{
            font-weight:300;
        }

        @media print {
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
                    <!--//customer name-->
                    <div class="" style="text-align: right;">
                        <div style="width: 50%; display: inline-block; font-size:20px; font-weight:600;">
                            {!! '<p style="font-size:18px; position:absolute;left:40px;">' . $orderDetail->customers->name . '</p>' !!}
                            <p
                                style="font-weight: 900; font-size:18px margin:0px">:نام</p>
                        </div>
                    </div>

                    <!--//number of suits-->
                    <div class="" style="text-align: right;">
                        <div style="width: 50%; display: inline-block; font-size:18px; font-weight:600;">
                            {!! '<p style="font-size:18px;position:absolute;left:60px;">' . $orderDetail->suitQuantity . '</p>' !!}
                            <p style="font-weight: 900; font-size:18px; margin:0px">:سوٹ کی تعداد</p>
                        </div>
                    </div>


                    <div class="mb-3" style="text-align: right;">
                        <div
                            style="width: 50%; display: inline-block; font-size:18px; font-weight:400; max-width:400px; word-wrap:break-word; word-break:break-all;">
                            {!! '<p style="font-size:18px; font-weight:900; position:absolute; left:60px;">' . $orderDetail->suitNum . '</p>' !!}
                            <p style="font-size:18px; font-weight:900;margin:0px">:سیریل نمبر</p>
                        </div>
                    </div>


                    <!--//order payment-->
                    <div class="mb-3" style="text-align: right;">
                        <div style="width: 100%; display: inline-block; font-size:18px;">
                            {!! '<p style="font-size:18px; font-weight:900; position:absolute; left:40px;">' . $orderDetail->totalPayment .'</p>' !!}
                            <p style="font-size:18px; font-weight:900;margin:0px">:آرڈر کی رقم
                                </p>
                        </div>
                    </div>

                    <!--//recieved payment-->
                    <div class="mb-3" style="text-align: right;padding:0px 0px;">
                        <div style="width: 100%; display: inline-block; font-size:18px; ">
                            {!! '<p style="font-size:18px; font-weight:900; position:absolute;left:40px;">' . $orderDetail->transactions[0]->recivedPayment .'</p>' !!}
                            <p style="font-weight:900; font-size:18px;margin:0px">:موجودہ رقم کی
                            ادائیگی</p>
                        </div>
                    </div>
                    <!-- Current due payments -->
                    @if ($latestBalance - $previousBalance > 0)
                        <div class="mb-3" style="text-align: right; padding: 5px 0px;">
                            <div style="width: 100%; display: inline-block; font-size:18px;">
                                {!! '<p style="font-size:18px; font-weight:900; position:absolute;left:40px;">' . ($latestBalance - $previousBalance) . '</p>' !!}
                                <p style="font-weight:900; font-size:18px;margin:0px">:موجودہ ادائیگی واجب
                                    الادا</p>
                            </div>
                        </div>
                    @endif

                    <!-- Previous payments due -->
                    @if ($previousBalance > 0)
                        <div class="mb-3" style="text-align: right; padding: 5px 0px;">
                            <div style="width: 100%; display: inline-block; font-size:18px; ">
                                {!! '<p style="font-size:18px; font-weight:900; position:absolute;left:40px;">' . $previousBalance . '</p>' !!}
                                <p style="font-weight:900; font-size:18px;margin:0px">:گزشتہ ادائیگی کے
                                    واجبات</p>
                            </div>
                        </div>
                    @endif

                    <!-- Latest balance or total balance -->
                    @if ($latestBalance > 0)
                        <div class="mb-3" style="text-align: right;">
                            <div style="width: 100%; display: inline-block; font-size:18px; ">
                                {!! '<p style="font-size:18px; font-weight: 900; position:absolute;left:40px;">' . $latestBalance . '</p>' !!}
                                <p style="font-weight:900; font-size:18px; margin:0px">:کل ادائیگی واجب
                                    الادا</p>
                            </div>
                        </div>
                    @endif

                    <!--//return date-->
                    <div class="mt-3" style="text-align: right; font-size:18px; ">
                        {!! '<p style="font-size:18px; font-weight:900; position:absolute;left:40px;">' . $orderDetail->returnDate . '</p>' !!}
                        <p style="font-Weight:900; font-size:18px; margin:0px;">:واپسی کی
                            تاریخ</p>
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
                            <p style="font-size:18px; font-weight:700;">Serial num: {{$orderDetail->suitNum}}</p>
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
                                    <p style="direction: rtl; text-align: right;word-break: break-word; white-space: normal; min-width: 0; font-weight:900; font-size:18px;">{{ $orderDetail->customers->length }}</p>
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
                                   <p style="word-break: break-word; white-space: normal; min-width: 0; font-weight:900; font-size:18px;"> {{ $orderDetail->customers->arms }}</p>
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
                                <div><p style="display: flex;justify-content: space-between;font-weight:900;  font-size:18px;">{{ $orderDetail->customers->teraa }}</p></div>
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
                                   <p style="word-break: break-word; white-space: normal; min-width: 0;font-weight:900; font-size:18px;">{{$orderDetail->customers->senaChorai}}</p>
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
                                    <p style="word-break: break-word; white-space: normal; min-width: 0;font-weight:900; font-size:18px;">{{ $orderDetail->customers->damanchorai }}</p>
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
                                    <p style="word-break: break-word; white-space: normal; min-width: 0; font-weight:900; font-size:18px;">{{ $orderDetail->customers->shalwar }}</p>
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
                                    <p style="word-break: break-word; white-space: normal; min-width: 0; font-weight:900; font-size:18px;">{{ $orderDetail->customers->pancha }}</p>
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
                                    <p style="word-break: break-word; white-space: normal; min-width: 0; font-weight:900; font-size:18px;">{{ $orderDetail->customers->shalwarGheer }}</p>
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
                                    <p style="word-break: break-word; white-space: normal; min-width: 0; font-weight:900; font-size:18px;">{{ $orderDetail->customers->shoulder }}</p>
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
                                    <p style="word-break: break-word; white-space: normal; min-width: 0; font-weight:900; font-size:18px;">{{ $orderDetail->customers->Chuta }}</p>
                                </div>
                                <div><p style="font-weight:900; font-size:20px;">چوتا</p></div>
                            </div>
                        </div>

                    </div>

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