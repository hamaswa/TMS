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

    <title>Tailor Managment Sale Recipt</title>
    <style>
     @font-face {
    font-family: 'Noto Nastaliq Urdu';
    src: url('/public/assets/fonts/noto-nastaliq-urdu/NotoNastaliqUrdu-VariableFont_wght.woff2') format('woff2');
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
            width: 70%;
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

        <!--Print Button-->
        <div class="btn printbtn">
            <button class="btn btn-primary"
                style="position: absolute; top: 5%; left: 0; padding: 0px 5px; text-align: center;">
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

            <div id="orderSection" style="max-width: 322px; margin-top: -25px;" class="ticket order-section">
                <div class="pl-3 pr-3" style="margin-top: 10px">
                    <p align="center"><img src="{{ asset('public/images/setting/' . $setting->logo) }}" width="100">
                    </p>
                    <h1 class="text-center" style="font-size: 16px;font-weight: 600;text-align: center">
                        {{ $setting->name }}</h1>
                    <h5 style="font-size: 16px;font-weight: 600;text-align: center">
                        Invoice No : {{ $id }}</h5>
                    <table class="table" style="width: 100%; table-layout: fixed;">
                        <h4
                            style="position:relative;text-align: right; margin-bottom: 20px !important;font-size:18px;font-weight:600;">
                            {{ $customerName }} : نام </h4>
                        <h4
                            style="position:relative;text-align: right; margin-bottom:30px !important;font-size:18px;font-weight:600;">
                            {{ $phone }} : موبائل نمبر</h4>

                        <h4
                            style="position:relative;text-align: right; margin-bottom:30px !important;font-size:18px;font-weight:600;">
                            {{ $sellStock->sellDate }} : تاریخ</h4>
                        <thead>
                            <tr>
                                <th style="width: 14%;">ٹوٹل</th>
                                <th style="width: 13%;">گزانہ</th>
                                <th style="width: 13%;">تھان</th>
                                <th style="width: 18%;">فی میٹر</th>
                                <th style="width: 15%;">رنگ</th>
                                <th style="width: 15%;">قسم</th>
                                <th style="width: 13%;">برانڈ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $totalAmount = 0; // Initialize total amount variable
                            @endphp
                            @if ($saleStocks->isNotEmpty()) <!-- Check if there are any sale stocks -->
                                {{-- Loop through each sale stock --}}
                                @foreach ($saleStocks as $saleStock)
                                    <tr>
                                        <td><b>{{ $saleStock->length * $saleStock->selling_price }}</b></td>
                                        <td style="width: 1%"><b>{{ $saleStock->length }}</b></td>
                                        <td style="width: 1%"><b>{{ $saleStock->clothes_rack }}</b></td>
                                        <td><b>{{ number_format($saleStock->selling_price, 2) }}</b></td>
                                        <td><b>{{ $saleStock->color }}</b></td>
                                        <td><b>{{ $saleStock->type->name }}</b></td>
                                        <td><b>{{ $saleStock->brand->name }}</b></td>
                                    </tr>
                                    @php
                                        // Calculate total amount
                                        $totalAmount += $saleStock->length * $saleStock->selling_price;
                                    @endphp
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="7">No records found</td>
                                </tr>
                            @endif
                        </tbody>


                        <tfoot>

                            <tr>
                                <td colspan="3" style="text-align: right;font-size:20px;">
                                    <b>{{ $totalAmount }}<b>
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

                            {{-- @endif --}}
                        </tfoot>
                    </table>
                </div>

            </div>
        </div>

        <div style="width: 100%;">
            <div style="width: 100%;" align="center">
                <td><b style="font-size: 18px;">{!! $setting->address !!}</b></td><br>
                <td><b style="font-size: 18px;">{{ $setting->contact_no }}</b></td>
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
