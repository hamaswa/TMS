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
    src: url('/public/assets/fonts/noto-nastaliq-urdu/NotoNastaliqUrdu-VariableFont_wght.woff2') format('woff2');
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

        @media print {
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
                    @if ($status != 'default')
                        <button class="btn btn-primary btn-sm naap">پمائیش</button>
                        <button class="btn btn-primary btn-sm order">ارڈر تفصیل</button>
                        <button class="btn btn-primary btn-sm full">مکمل </button>
                    @endif
                    <hr>
                </div>
            </div><!--End Info-->
        </center><!--End InvoiceTop-->

        <!--Print Button-->
        <div class="btn printbtn">
            <button class="btn btn-primary"
                style="position: absolute; top: 10%; left: 1%; padding: 0px 10px; text-align: center;">
                <span style="display: inline-block; vertical-align: middle; line-height: normal;font-size:22px;">رسید
                    پرنٹ کریں۔</span>
            </button>
            <button class="btn btn-primary go"
                style="position: absolute; top: 10%; left: 12%; padding: 0px 10px; text-align: center;">
                <span style="display: inline-block; vertical-align: middle; line-height: normal;font-size:18px;"> آرڈر
                    پر واپس جائیں</span>
            </button>
            {{-- <button class="btn btn-primary go" style="position:relative;bottom:80px;right:20px;padding: 10px;">آرڈر
                پر واپس جائیں</button> --}}
        </div>



        <div id="fullSection">
            <div id="orderSection" style="max-width: 350px; margin-top:-30px;" class="ticket order-section">
                <p align="center"><img src="{{ asset('public/images/setting/' . $setting->logo) }}" width="100"></p>
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
                    <!--//customer name-->
                    <div class="mb-3" style="text-align: right;">
                        <div style="width: 50%; display: inline-block; font-size:20px; font-weight:600;">
                            {!! '<b style="font-size:18px; position:absolute;left:80px;">' . $orderDetail->customers->name . '</b>' !!}
                            <span
                                style="font-weight:600;">:نام</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        </div>
                    </div>
                    <br>

                    <!--//number of suits-->
                    <div class="mb-3 mt-2" style="text-align: right;">
                        <div style="width: 50%; display: inline-block;font-size:20px; font-weight:600;">
                            <span style="font-weight:600;">:سوٹ کی تعداد</span>
                            {!! '<b style="font-size:18px;position:absolute;left:100px;">' . $orderDetail->suitQuantity . '</b>' !!}
                        </div>
                    </div>


                    <!--//suits number-->
                    <div class="mb-3 mt-2" style="text-align: right;">
                        <div
                            style="width: 50%; display: inline-block; font-size:20px; font-weight:600; max-width: 400px; word-wrap: break-word; word-break: break-all;">
                            <span style="font-size:18px; font-weight:600;">:سیریل نمبر</span>
                            {!! '<b style="font-size:18px;position:absolute;left:100px;">' . $orderDetail->suitNum . '</b>' !!}
                        </div>
                    </div>


                    <!--//order payment-->
                    <div class="mb-3 mt-2" style="text-align: right;padding:0px 0px;">
                        <div style="width: 100%; display: inline-block; font-size:18px;">
                            <span style="font-weight:600; ">آرڈر کی رقم
                                :</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            {!! '<b style="font-size:18px;">' . $orderDetail->totalPayment . '</b>' !!}
                        </div>
                    </div>

                    <!--//recieved payment-->
                    <div class="mb-3 mt-2" style="text-align: right;padding:0px 0px;">
                        <div style="width: 100%; display: inline-block; font-size:18px;">
                            <span style="font-weight:600;">موجودہ رقم کی
                                ادائیگی:</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            {!! '<b style="font-size:18px;">' . $orderDetail->transactions[0]->recivedPayment . '</b>' !!}
                        </div>
                    </div>
                    <!-- Current due payments -->
                    @if ($latestBalance - $previousBalance > 0)
                        <div class="mb-3 mt-2" style="text-align: right; padding: 5px 0px;">
                            <div style="width: 100%; display: inline-block; font-size:18px;">
                                <span style="font-weight:600;">موجودہ ادائیگی واجب
                                    الادا:</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                {!! '<b style="font-size:18px;">' . ($latestBalance - $previousBalance) . '</b>' !!}
                            </div>
                        </div>
                    @endif

                    <!-- Previous payments due -->
                    @if ($previousBalance > 0)
                        <div class="mb-3 mt-2" style="text-align: right; padding: 5px 0px;">
                            <div style="width: 100%; display: inline-block; font-size:18px;">
                                <span style="font-weight:600;">گزشتہ ادائیگی کے
                                    واجبات:</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                {!! '<b style="font-size:18px;">' . $previousBalance . '</b>' !!}
                            </div>
                        </div>
                    @endif

                    <!-- Latest balance or total balance -->
                    @if ($latestBalance > 0)
                        <div class="mb-3 mt-2" style="text-align: right;">
                            <div style="width: 100%; display: inline-block; font-size:18px;">
                                <span style="font-weight:600;">کل ادائیگی واجب
                                    الادا:</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                {!! '<b style="font-size:18px;">' . $latestBalance . '</b>' !!}
                            </div>
                        </div><br>
                    @endif

                    <!--//return date-->
                    <div style="text-align: right; font-size:18px;">
                        <span style="font-weight:600; ">واپسی کی
                            تاریخ:</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        {!! '<b style="font-size:18px;">' . $orderDetail->returnDate . '</b>' !!}
                    </div>
                    <div>
                        <h3 class="text-center font-weight-bold mt-2" style="font-size:18px;">{{$orderDetail->remarks}}
                        </h3>
                    </div>
                    <hr>
                    <div style="width: 100%;" align="center">
                        <p><b>{!! $setting->address !!}</b></p>
                        <p>{{ $setting->contact_no }}</p>
                        <p style="text-align:center;padding:5px;"><b></b>{{ $setting->note }}</b></p>
                    </div>
                    <hr>
                </div>

                <!--<p style="text-align:center; font-size: 10px">{{ $setting->note }}</p>-->
                <!--<hr>-->
            </div>
            <div id="sizeSection" style="max-width: 350px; margin-top:-30px;" class="ticket size-section">
                <p align="center"> <img src="{{asset('public/images/setting/' . $setting->logo)}}" width="100"></p>
                <h1 class="text-center" style="font-weight:800;margin-top:-20px;">{{$setting->name}}</h1>
                <div align="center"> <span style="font-weight: bold;font-size: 14px;"></span>
                </div>
                <br>
                <div class="pl-3 pr-3">
                    <hr>
                    <div class="desing-flex">
                        <div>
                            <b>Serial num: {{$orderDetail->suitNum}}</b>
                        </div>
                        <div>
                            <b>{{$orderDetail->customers->name}}</b>
                        </div>
                    </div>
                    <div class="desing-flex">
                        <div style="font-size:19px;">
                            <b> {{$tailor->name}}</b>
                        </div>
                        <div>
                            <b> درزی کا نام </b>
                        </div>
                    </div>
                    <div class="desing-flex">
                        <div>
                            {{date('d-m-Y h:m A', strtotime($orderDetail->created_at))}}
                        </div>
                        <div>
                            {{$orderDetail->customers->phone_number1}}
                        </div>
                    </div>
                    <hr>

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
                                    {{ $orderDetail->customers->Chuta }}
                                </div>
                                <div style="font-weight:600; font-size:20px;">چوتا</div>
                            </div>
                        </div>

                    </div>
                    <hr>
                    <div>
                        <div align="center">
                            <div>
                                <h3 class="text-center font-weight-400 mt-2" style="font-size: 16px;">{{$orderDetail->remarks}}</h3>
                            </div>
                            <p>{!! $setting->address !!}</p>
                            <p>{{$setting->contact_no}}</p>
                        </div>
                    </div>
                    <!--<p style="text-align:center">{{$setting->note}}</p>-->
                </div>
            </div>
        </div>



    </div><!--End Invoice-->
    <script>
        $(document).ready(function () {
            function PrintElem(id) {
                var divContents = document.getElementById(id).innerHTML;

                var mywindow = window.open('', 'PRINT', 'height=500,width=322');

                mywindow.document.write('</head><body >');
                //              mywindow.document.write('<h1>' + document.title  + '</h1>');
                mywindow.document.write(divContents);
                mywindow.document.write('</body></html>');

                mywindow.document.close(); // necessary for IE >= 10
                mywindow.focus(); // necessary for IE >= 10*/

                mywindow.print();
                //              mywindow.close();

                return true;
            }

            var status = $('#status').val();
            //          var elemTarget = $("#myDiv");
            //          var elemId = 'myDiv';
            if (status == 'default') {
                //        window.print();
                //            $("#myDiv").printElement();
                PrintElem("myDiv");
            } else {
                $('.naap').on('click', function () {
                    $('.naap-button, .order-section').css('display', 'none');
                    //                window.print();
                    //                $("#myDiv").printElement();
                    PrintElem("orderSection");
                });

                $('.order').on('click', function () {
                    $('.naap-button, .size-section').css('display', 'none');
                    //                window.print();
                    //                 $("#myDiv").printElement();
                    PrintElem("sizeSection");
                });
                $('.full').on('click', function () {
                    $('.naap-button').css('display', 'none');
                    //                window.print();
                    //                  $("#myDiv").printElement();
                    PrintElem("fullSection");
                });
            }

        });

        //to print reciept
        var printpage = document.querySelector(".btn button");
        printpage.addEventListener('click', function () {
            window.print();
        });
        //to go back to order
        document.querySelector('.go').addEventListener('click', function () {
            window.history.back();
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
