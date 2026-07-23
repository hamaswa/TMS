<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="style.css">
    <title>Tailor Managment Order Recipt</title>
    <!-- jQuery library -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    <!-- Latest compiled JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <style>
    * {
        font-size: 12px;
        font-family: 'Times New Roman';
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
        width: inherit;
    }

    @media print {

        .hidden-print,
        .hidden-print * {
            display: none !important;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
            }
        }
    }


    td,
    th,
    tr,
    table {
        border-top: 1px solid black;
        border-collapse: collapse;
        font-size: 11px;
    }

    tr {}
    </style>
</head>

<body>
    <div class="ticket" style="margin-top: 40px">
        @if($setting->logo_url)
            <img src="{{ $setting->logo_url }}" alt="{{ $setting->name }} لوگو">
        @endif
        <br>
        <br>
        <div style="width: 100%; margin-bottom: 6px">
            <div style="width: 100%;display: inline-block;font-size: 14px"> <span
                    style="font-weight: bold;font-size: 14px;">Tailor Name : {{$tailor->name}}</span>
            </div>
        </div>
    </div>

    <!-- naap print desing -->
    <div class="ticket" style="margin-top: 10px">

        <div style="width: 100%; margin-bottom: 6px">
            <div style="width: 100%; display: inline-block;font-size: 14px">
                <table style="width:100%">
                    <thead>
                        <tr>
                            <th style="border: 1px solid #000000;font-size: 16px;padding:9px">Sute</th>
                            <th style="border: 1px solid #000000;font-size: 16px;padding:9px">Sute Payment</th>
                            <th style="border: 1px solid #000000;font-size: 16px;padding:9px">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $total=0; @endphp
                        @foreach($orders as $order)
                          @php $total += $order->t_payment; @endphp
                        <tr>
                            <td class="description" style="border: 1px solid #000000; font-size: 13px;padding:4px 7px">{{$order->suit}}</td>
                            <td class="description" style="border: 1px solid #000000; font-size: 13px;padding:4px 7px">
                                {{$order->t_payment}}</td>
                            <td class="quantity" style="border: 1px solid #000000;font-size: 13px;padding:3px 4px">
                                {{date('d-m-Y', strtotime($order->date))}} </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <br>
                <div style="text-align:right;font-size: 22px;">Total: {{ $total}} </div>
                <br>
            </div>

        </div>

        <div style="width: 100%;">
            <div style="width: 100%;" align="center">
                <p>{{ $setting->address }}</p>
                <p>{{$setting->contact_no}}</p>
            </div>
        </div>
        <p style="text-align:center">{{$setting->note}}</p>
    </div>


    </div>

    <!-- end nap print design -->

    <script>
    $(document).ready(function() {
        window.print();
    });
    </script>
</body>

</html>
