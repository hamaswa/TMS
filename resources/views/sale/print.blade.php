<!DOCTYPE html>

<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    <!-- Latest compiled JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/css/bootstrap.min.css">


     <title>Tailor Managment Sale Recipt</title>
     <style>

        #invoice-POS{
            box-shadow: 0 0 1in -0.25in rgba(0, 0, 0, 0.6);
            padding:2mm;
            margin-left: 20px !important;
            margin: 0 auto;
            width: 88mm;
            background: #FFF;
            margin-top:20px;


            ::selection {background: #f31544; color: #FFF;}
            ::moz-selection {background: #f31544; color: #FFF;}
            h1{
            font-size: 1.5em;
            color: #222;
            }
            h2{font-size: .9em;}
            h3{
            font-size: 1.2em;
            font-weight: 300;
            line-height: 2em;
            }
            p{
            font-size: .7em;
            color: #666;
            line-height: 1.2em;
            }

            #top, #mid,#bot{ /* Targets all id with 'col-' */
            border-bottom: 1px solid #EEE;
            }

            #top{min-height: 100px;}
            #mid{min-height: 80px;}
            #bot{ min-height: 50px;}

            #top .logo{
            //float: left;
                height: 60px;
                width: 60px;
                background: url(http://michaeltruong.ca/images/logo1.png) no-repeat;
                background-size: 60px 60px;
            }
            .clientlogo{
            float: left;
                height: 60px;
                width: 60px;
                background: url(http://michaeltruong.ca/images/client.jpg) no-repeat;
                background-size: 60px 60px;
            border-radius: 50px;
            }
            .info{
            display: block;
            //float:left;
            margin-left: 0;
            }
            .title{
            float: right;
            }
            .title p{text-align: right;}
            table{
            width: 100%;
            border-collapse: collapse;
            }

            .tabletitle{
            font-size: .5em;
            background: #EEE;
            }
            .service{border-bottom: 1px solid #EEE;}
            .item{width: 24mm;}
            .itemtext{font-size: .5em;}

            #legalcopy{
            margin-top: 5mm;
            }


            }
            .desing-flex{
                display:flex;
                 justify-content:space-between;
                 margin-bottom:10px;
                 }
                @media print{
                    .btn{
                        display:none;
                    }
                }
     </style>
    </head>
    <body>


  <div id="invoice-POS">
    <!--Print Button-->

    <div class="btn" style="position:relative;left:55%;top:10px;">
        <button class="btn btn-primary print">رسید پرنٹ کریں۔</button>
    </div>

    <!--Back button-->
    <!--<div class="back" style="position:relative;bottom:40px;right:10px;">-->
        <button class="btn btn-primary go" href="" style="position:relative;bottom:35px;right:2px;">فروخت پر واپس جائیں</button>
    <!--</div>-->

    <center id="top">
      <div class="info">
       <div class="naap-button my-2">
            @if($status !='default')
            <button class="btn btn-primary btn-sm naap">پمائیش</button>
            <button class="btn btn-primary btn-sm order">ارڈر تفصیل</button>
            <button class="btn btn-primary btn-sm full">مکمل </button>
            @endif
        <hr>
        </div>
      </div><!--End Info-->
    </center><!--End InvoiceTop-->

     <div class="ticket order-section mt-3">
            <p align="center"><img src="{{asset('public/images/setting/'.$setting->logo)}}" width="100"></p>
            <h5 class="text-center">{{$setting->name}}</h5>
            <br>
            <div class="pl-3 pr-3">
                <div class="d-flex justify-content-between ">
                    <div>
                        <h6>{{$sale->id}} # رسید نمبر</h6>
                    </div>
                    <div>
                        <h6>{{date('d-m-Y', strtotime($sale->created_at))}}: تاریخ</h6>
                    </div>
                </div>
                <br>
                <div class="mb-1">
                    <div style="width: 50%; display: inline-block;font-size: 14px">
                        <span style="font-weight: bold;position:relative;left:180%; "> : نام</span>
                        <b style="position:absolute;left:10%">{{ $sale->customer?->name ?? $sale->customer_name }}</b>
                    </div>
                </div>

                <div class="sale-details">
                    <table class="table">
                        <thead style="white-space: nowrap !important;">
                            <th>کل قیمت</th>
                            <th>تعداد</th>
                            <th>مصنوعات</th>
                            <th>#</th>
                        </thead>
                        <tbody>
                            @foreach ($sale->detail as $detail)
                            <tr>
                                <td>{{ $detail->price }}</td>
                                <td>{{ $detail->quantity }}</td>
                                <td>{{ $detail->product_name }}</td>
                                <td>{{ $loop->iteration }}</td>
                            </tr>

                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mb-1">
                    <div style="width: 100%; display: inline-block;font-size: 14px">
                        <span style="font-weight: bold; position:relative;left:70%;"> : موصول رقم</span>
                        <b style="position:absolute;left:7%;font-size:15px;">{{ $transaction->recivedPayment }}</b>
                    </div>

                    <div class="mb-1">
                    <div style="width: 60%; display: inline-block;font-size: 14px">
                        <span style="font-weight: bold; position:relative;left:70%;"> : موجودہ واجب الادا ادائیگی</span>
                        <b style="position:absolute;left:7%;font-size:15px;">{{ $transaction->remainingBalance }}</b>
                    </div>
                    <div style="width: 100%; display: inline-block;font-size: 14px">
                        <span style="font-weight: bold; position:relative;left:53%;"> : گزشتہ واجب ادائیگی</span>
                        <b style="position:absolute;left:7%;font-size:15px;">{{ $previousBalance }}</b>
                    </div>

                    <div style="width: 100%; display: inline-block;font-size: 14px">
                        <span style="font-weight: bold; position:relative;left:50%;"> : کل واجب الادا ادائیگی</span>
                        <b style="position:absolute;left:7%;font-size:15px;">{{ $latestBalance }}</b>
                    </div>


                    <!--<div style="width: 100%; display: inline-block;font-size: 14px">-->
                    <!--    <span style="font-weight: bold; position:relative;left:50%;"> : گزشتہ رقم واجب الادا ہے</span>-->
                    <!--</div>-->
                </div>

            <hr>
                <div style="width: 100%;"  align="center">
                        <p><b>{{ $setting->address }}</b></p>
                        <p>{{$setting->contact_no}}</p>
                </div>
            <hr>
        </div>

            <!--<p style="text-align:center">{{$setting->note}}</p>-->
        <!--<hr>-->
     </div>

  </div><!--End Invoice-->

    </body>
      <script>
    $(document).ready(function() {
        var status = $('#status').val();
        if(status =='default')
        {
        window.print();
        }else{
            $('.naap').on('click', function(){
                $('.naap-button, .order-section').css('display','none');
                window.print();
            });

             $('.order').on('click', function(){
                $('.naap-button, .size-section').css('display','none');
                window.print();
            });
              $('.full').on('click', function(){
                $('.naap-button').css('display','none');
                window.print();
            });
        }

    });

    //to print reciept
    document.querySelector('.btn .print').addEventListener('click',function(){
       window.print();
    });
    //to go back to sale
    document.querySelector('.go').addEventListener('click',function(){
       window.history.back();
    });
    </script>
</html>
