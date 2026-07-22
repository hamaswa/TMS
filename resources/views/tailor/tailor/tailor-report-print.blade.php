<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="style.css">
    <title>Tailor Managment Order Recipt</title>
    <!-- jQuery library -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">

    <!-- Latest compiled JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <style>
        body {
            font-family: 'Noto Nastaliq Urdu', serif;
        }

        #invoice-POS {
            box-shadow: 0 0 1in -0.25in rgba(0, 0, 0, 0.5);
            padding: 2mm;
            margin-left: 10px !important;
            width: 88mm;
            background: #FFF;
            margin-top: 20px;

        }

        ::selection {
            background: #f31544;
            color: #FFF;
        }

        ::moz-selection {
            background: #f31544;
            color: #FFF;
        }

        h1 {
            font-size: 18px;
            color: #222;
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

        .side {
            position: relative;
            margin-right: 49px;
        }

        .side-top {
            margin-top: 14px;
        }

        .ticket {
            width: 240px;

        }

        img {
            max-width: inherit;
            width: 100px;
        }

        @media print {

            .hidden-print,
            .hidden-print * {
                display: none !important;
            }

            body {
                -webkit-print-color-adjust: exact;
                width: 40%;
            }

            td,
            th,
            tr,
            table {
                font-size: 14px;
                /* Adjust font size as needed */
                border-collapse: collapse;
                border-top: 1px solid #b6a7a7;
            }

            .ticket {
                width: auto;
                /* Adjust width as needed */
            }

            .col-md-10,
            .col-md-2 {
                width: 50%;
                /* Adjust column widths */
                float: left;
            }

            .row::after {
                content: "";
                clear: both;
                display: table;
            }

            .table-title {
                margin-bottom: 10px;
                /* Adjust margin for better spacing */
            }

            .table-title h5 {
                margin: 0;
                /* Remove margin for better spacing */
            }
        }


        td,
        th,
        tr,
        table {
            border-top: 1px solid #b6a7a7;
            border-collapse: collapse;
            font-size: 11px;
        }

        @media print {
            .btn {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div id="invoice-POS">
        <!--Print Button-->
        <div class="btn printbtn">
            <button class="btn btn-primary"
                style="position: absolute; top: 5%; left: 1.5%; padding: 5px 10px; text-align: center;">
                <span style="display: inline-block; vertical-align: middle; line-height: normal;font-size:18px;">رسید
                    پرنٹ کریں۔</span>
            </button>
            <button class="btn btn-primary go"
                style="position: absolute; top: 5%; left: 11%; padding: 5px 10px; text-align: center;">
                <span style="display: inline-block; vertical-align: middle; line-height: normal;font-size:18px;"> آرڈر
                    پر واپس جائیں</span>
            </button>

        </div>
        <div class="ticket" style="margin-top: 40px">
            <p align="center"><img src="{{ asset('public/images/setting/' . $setting->logo) }}"></p>
            <h5 class="text-center" style="font-size: 16px;font-weight: 600;text-align: center">{{ $setting->name }}
                <br>
                <br>
                <div style="width: 100%; margin-bottom: 6px">
                    <div style="width: 100%;display: inline-block;font-size: 14px"> <span
                            style="font-weight: bold;font-size: 16px;float: right;">{{ $tailor->name }} : درزی کا نام
                        </span>
                    </div>
                </div>
        </div>

        <!-- naap print desing -->
        <div class="ticket" style="margin-top: 10px">

            <div style="width: 100%; margin-bottom: 6px">
                <div style="width: 100%; display: inline-block; font-size: 14px">
                    <div class="row">
                        <div class="col-md-12 mr-1" style="margin-bottom: 20px;">
                            <table style="width: 100%;position: relative;left:10%;">
                                <thead>
                                    <tr>
                                        <th scope="col" class="no-sort" style=" font-size: 16px; padding:6px">درزی کی
                                            رقم</th>
                                        <th scope="col" class="no-sort" style=" font-size: 16px; padding:6px">سوٹ
                                            نمبرز</th>
                                        <th scope="col" class="no-sort" style="font-size: 16px; padding:6px">سوٹ کی
                                            تعداد</th>
                                        <th scope="col" class="no-sort" style=" font-size: 16px; padding:6px">ڈیزائن
                                        </th>
                                        <th scope="col" class="no-sort" style=" font-size: 16px; padding:6px">تاریخ
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($tailor_report as $report)
                                        <tr class="f">
                                            {{-- Check if there are salary records --}}
                                            @php
                                                $salaryRecord = $tailor_records->where('comment', 'salary')->first();
                                            @endphp
                                            {{-- If salary record exists, display salary, otherwise display tailor_price --}}
                                            <td style="font-size: 15px;padding:3px 4px;font-weight:600;">
                                                @if ($salaryRecord)
                                                    {{ $salaryRecord->amount }}
                                                @else
                                                    {{ $salaryRecord = 0 }}
                                                @endif
                                            </td>
                                            <td style="font-size: 15px; padding: 3px 4px;font-weight:600;">
                                                {{-- Decode JSON array and implode the values --}}
                                                {{ $report->suitNum }}
                                            </td>
                                            <td style="font-size: 15px;padding:3px 4px;font-weight:600;">
                                                {{ $report->suitQuantity }}</td>
                                            <td style="font-size: 15px;padding:3px 4px;font-weight:600;">
                                                {{ $report->designPrice }}</td>
                                            <td style="font-size: 15px;padding:3px 4px;font-weight:600;">
                                                {{ date('d-m-Y', strtotime($report->created_at)) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr style="position: relative; margin-top: 30px;" class="bg-own mt-2"
                                        id="totalAmount">
                                        <td style="font-size: 15px; padding: 3px 4px;font-weight:600;">
                                            {{-- Calculate the total sum of salary records --}}
                                            @php
                                                $totalSalary = $tailor_records->where('comment', 'salary')->sum('amount');
                                            @endphp

                                            {{-- Output the total sum --}}
                                            {{ $totalSalary }}
                                        </td>
                                        <td></td>
                                        <td style="font-size: 15px; padding: 3px 4px;font-weight:600;">
                                            {{ $tailor_report->sum(function ($order) {
                                                return $order->suitQuantity;
                                            }) }}
                                        </td>
                                        {{-- <td></td> --}}
                                        <td style="font-size: 15px; padding: 3px 4px;font-weight:600;">
                                            {{ $tailor_report->sum(function ($order) {
                                                return $order->designPrice;
                                            }) }}
                                        </td>
                                        <td style="font-size: 13px; padding: 3px 4px;font-weight:600;">ٹوٹل</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="col-md-12 ">
                            <table style="width: 40%">

                                <tbody>
                                    @foreach ($tailor_records as $tailor_record)
                                        <tr class="f colspan-6">
                                            {{-- <td style="font-size: 13px;padding: 1px 67px;"> --}}
                                            @if ($tailor_record->comment === 'advance')
                                                <div style="text-align: right;font-weight:600;font-size:16px;">ایڈوانس:
                                                    {{ (int) $tailor_record->amount }}
                                                </div>
                                            @elseif ($tailor_record->comment === 'chai')
                                                <div style="text-align: right;font-weight:600;font-size:16px;">چائے کا
                                                    خرچہ :
                                                    {{ (int) $tailor_record->amount }}</div>
                                            @endif
                                            {{-- </td> --}}
                                        </tr>
                                    @endforeach
                                </tbody>

                                {{-- <tfoot>
                                <tr class="bg-own mt-2" id="totalAmount">
                                    <td style="border: 1px solid #808080; font-size: 13px; padding: 3px 4px">
                                        {{ $tailor_records->where('comment', 'advance')->sum('amount') }}-ٹوٹل
                                    </td>
                                    <td style="border: 1px solid #808080; font-size: 13px; padding: 3px 4px">
                                        {{ $tailor_records->where('comment', 'salary')->sum('amount') }}-ٹوٹل
                                    </td>
                                    <td style="border: 1px solid #808080; font-size: 13px; padding: 3px 4px">
                                        {{ $tailor_records->where('comment', 'chai')->sum('amount') }}-ٹوٹل
                                    </td>
                                </tr>
                            </tfoot> --}}
                            </table>
                        </div>


                    </div>
                    <br>
                    <div style="text-align:right; font-size: 20px;">
                        <span style="font-size: 10px;font-weight:600;"> &nbsp;&nbsp;(ڈیزائن کے اخراجات شامل کیے گئے
                            ہیں۔) </span>Total:
                            {{ $tailor_report->sum(function ($report) use ($tailor_records) {
                                $totalSalary = $tailor_records->where('comment', 'salary')->sum('amount');
                                return $report->designPrice + $totalSalary;
                            }) +
                            $tailor_records->where('comment', 'chai')->sum('amount') -
                            $tailor_records->where('comment', 'advance')->sum('amount') }}
                    </div>

                    <br>
                </div>
            </div>


            <div style="width: 100%;">
                <div style="width: 100%;text-align:center;font-weight:600;">
                    <p style="font-size:13px;margin-bottom:15px;">{!! $setting->address !!}</p>
                    <p style="font-size:14px;letter-spacing:1px;">{{ $setting->contact_no }}</p>
                </div>
            </div>
            {{-- <p style="text-align:center;font-weight:600;font-size:14px;">{{ $setting->note }}</p> --}}
        </div>


    </div>

    <!-- end nap print design -->
    </div><!--End Invoice-->
    <script>
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
