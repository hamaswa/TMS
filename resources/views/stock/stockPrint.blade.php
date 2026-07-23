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
                    @if($setting?->logo)
                        @if($setting->logo_url)
                            <p align="center"><img src="{{ $setting->logo_url }}" width="100" alt="{{ $setting->name }} لوگو"></p>
                        @endif
                    @endif
                    <h1 class="text-center" style="font-size: 16px;font-weight: 600;text-align: center">
                        {{ $setting?->name ?: (auth()->user()->business?->name ?: auth()->user()->name) }}</h1>
                        <h5 style="font-size: 16px;font-weight: 600;text-align: center">
                            رسید نمبر: {{ $id }}</h5>
                    <table class="table" style="width: 100%; table-layout: fixed;">

                        <h4 style="position:relative; margin-bottom: 20px !important;font-size:20px;font-weight:700;" class="text-right">
                            {{ $customerName }} : نام </h4>
                        <h4 style="position:relative;text-align: right; margin-bottom:30px !important;font-size:20px;font-weight:700;">
                            {{ $phone }} : موبائل نمبر</h4>
                        <thead>
                            <tr>
                                <th style="width: 34%;">کپڑے کی تفصیل</th>
                                <th style="width: 14%;">میٹر</th>
                                <th style="width: 20%;">فی میٹر</th>
                                <th style="width: 20%;">ٹوٹل</th>
                                <th style="width: 12%;">ریک</th>
                            </tr>
                        </thead>

                        <tbody>
                            @php
                                $totalAmount = 0; // Initialize total amount variable
                            @endphp
                            @foreach ($sellStock as $sellstock)
                                {{-- in reverse order --}}
                                <tr>
                                    @php
                                        $itemTotal = $sellstock->length * $sellstock->selling_price;
                                        $totalAmount += $itemTotal; // Accumulate the total amount
                                    @endphp
                                    <td><b>{{ $sellstock->brand->name }} / {{ $sellstock->type->name }} / {{ $sellstock->color }}</b></td>
                                    <td><b>{{ $sellstock->length }}</b></td>
                                    <td><b>{{ number_format($sellstock->selling_price, 2) }}</b></td>
                                    <td><b>{{ number_format($itemTotal, 2) }}</b></td>
                                    <td><b>{{ $sellstock->clothes_rack }}</b></td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            @if ($tailortransactions && $previousBalance > 0)
                            <tr>
                                <td colspan="4" style="text-align: right;font-size:20px;">
                                    <span style="font-size: 12px;"> </span>
                                    <b>{{ $totalAmount }}</b>
                                </td>

                                <td colspan="4" style="text-align: right;font-size:18px;"> : ٹوٹل</td>
                            </tr>
                            <tr>
                                <td colspan="4" style="text-align: right;font-size:20px;">
                                    <b>{{ $payment }}<b>
                                </td>

                                <td colspan="4" style="text-align: right;font-size:18px;"> : ادائیگی</td>
                            </tr>

                            <tr>
                                <td colspan="2" style="text-align: right;font-size:20px;">
                                    <b>{{ $remaining }}<b>
                                </td>

                                <td colspan="4" style="text-align: right;font-size:18px;"> : ادائیگی واجب الادا</td>
                            </tr>
                                <tr>
                                    <td colspan="4" style="text-align: right;font-size:20px;">
                                        <span style="font-size: 12px;"></span>

                                        <b>{{$previousBalance + $tailortransactions->Balance }}</b>
                                    </td>

                                    <td colspan="4" style="text-align: right;font-size:18px;"> : ٹیلرنگ اور فروخت</td>
                                </tr>

                                @elseif ($tailortransactions)
                                <tr>
                                    <td colspan="4" style="text-align: right;font-size:20px;">
                                        <span style="font-size: 12px;"> </span>
                                        <b>{{ $totalAmount }}</b>

                                    </td>

                                    <td colspan="4" style="text-align: right;font-size:18px;"> : ٹوٹل</td>
                                </tr>
                                <tr>
                                    <td colspan="4" style="text-align: right;font-size:20px;">
                                        <b>{{ $payment }}<b>
                                    </td>

                                    <td colspan="4" style="text-align: right;font-size:18px;"> : ادائیگی</td>
                                </tr>

                                <tr>
                                    <td colspan="4" style="text-align: right;font-size:20px;">
                                        <b>{{ $remaining }}<b>
                                    </td>

                                    <td colspan="5" style="text-align: right;font-size:18px;"> : ادائیگی واجب الادا</td>
                                </tr>
                                <tr>
                                    <td colspan="4" style="text-align: right;font-size:20px;">
                                        <span style="font-size: 12px;"></span>

                                        {{ $tailortransactions->Balance }}
                                    </td>

                                    <td colspan="4" style="text-align: right;font-size:18px;"> : ٹیلرنگ</td>
                                </tr>
                            @elseif ($previousBalance > 0)
                                <tr>
                                    <td colspan="3" style="text-align: right;font-size:20px;">
                                        <span style="font-size: 12px;"> </span>
                                        <b>{{ $totalAmount }}</b>

                                    </td>

                                    <td colspan="3" style="text-align: right;font-size:18px;"> : ٹوٹل</td>
                                </tr>
                                <tr>
                                    <td colspan="3" style="text-align: right;font-size:20px;">
                                        <b>{{ $payment }}<b>
                                    </td>

                                    <td colspan="3" style="text-align: right;font-size:18px;"> : ادائیگی</td>
                                </tr>

                                <tr>
                                    <td colspan="2" style="text-align: right;font-size:20px;">
                                        <b>{{ $remaining }}<b>
                                    </td>

                                    <td colspan="4" style="text-align: right;font-size:18px;"> : ادائیگی واجب الادا</td>
                                </tr>

                                <tr>
                                    <td colspan="2" style="text-align: right;font-size:20px;">
                                        <span style="font-size: 12px;"> </span>
                                        <b>{{$previousBalance}}</b>
                                    </td>
                                    <td colspan="4" style="text-align: right;font-size:18px;"> : سابقہ ​​ادائیگیاں</td>
                                </tr>

                            @else
                                <tr>
                                    <td colspan="3" style="text-align: right;font-size:20px;">
                                        <b>{{ $totalAmount }}</b>
                                    </td>

                                    <td colspan="3" style="text-align: right;font-size:18px;"> : ٹوٹل</td>
                                </tr>
                                <tr>
                                    <td colspan="3" style="text-align: right;font-size:20px;">
                                        <b>{{ $payment }}<b>
                                    </td>

                                    <td colspan="3" style="text-align: right;font-size:18px;"> : ادائیگی</td>
                                </tr>

                                <tr>
                                    <td colspan="3" style="text-align: right;font-size:20px;">
                                        <b>{{ $remaining }}<b>
                                    </td>

                                    <td colspan="3" style="text-align: right;font-size:18px;"> : ادائیگی واجب الادا</td>
                                </tr>
                            {{-- @else
                                <tr>
                                    <td colspan="4" style="text-align: right;font-size:18px;">
                                        {{ $totalAmount }}</td>
                                    <td colspan="4" style="text-align: right;font-size:18px;">:ٹوٹل</td>
                                </tr> --}}
                            @endif
                        </tfoot>
                    </table>
                </div>

            </div>
        </div>

        <div style="width: 100%;">
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
        $(document).ready(function() {
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
                $('.naap').on('click', function() {
                    $('.naap-button, .order-section').css('display', 'none');
                    //                window.print();
                    //                $("#myDiv").printElement();
                    PrintElem("orderSection");
                });

                $('.order').on('click', function() {
                    $('.naap-button, .size-section').css('display', 'none');
                    //                window.print();
                    //                 $("#myDiv").printElement();
                    PrintElem("sizeSection");
                });
                $('.full').on('click', function() {
                    $('.naap-button').css('display', 'none');
                    //                window.print();
                    //                  $("#myDiv").printElement();
                    PrintElem("fullSection");
                });
            }

        });

        //to print reciept
        var printpage = document.querySelector(".btn button");
        printpage.addEventListener('click', function() {
            window.print();
        });
        //to go back to order
        document.querySelector('.go').addEventListener('click', function() {
            window.history.back();
        });
    </script>



</body>

</html>
