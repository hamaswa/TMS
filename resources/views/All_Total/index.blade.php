@extends('main')
@section('content')
    <section class="main-content">
        <div class="container">
            <div class="card col-sm-10 mx-auto">
                <div class="row">
                    <div class="col-md-12">
                        @include('inc.message')
                        <p style="font-size: 24px;text-align:center;">پورے سال کی فروخت</p>

                        {{-- <form class="form-inline reversed-flex-direction" method="POST"
                            action="{{ route('admin.dailyexpense.specific') }}" id="date">
                            @csrf
                            <!-- Date range picker input field -->
                            <div class="form-group mr-2">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="date_range" id="date_range"
                                        placeholder="تاریخ کی حد منتخب کریں۔" autocomplete="off">
                                    <div class="input-group-append">
                                        <span class="input-group-text">
                                            <i class="fa fa-calendar"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group mr-2">
                                <button type="submit" class="btn btn-primary"> چیک کریں۔</button>
                            </div>
                        </form> --}}

                        <div class="row">
                            <div class="col-md-12">
                                <div class="table-responsive">
                                    <!-- First Table -->
                                    <table class="table js-sortable-table cc-table-data-options-history"
                                        id="cc-table-data-options-history1">
                                        <thead>
                                            <tr>
                                                {{-- <th scope="col" class="no-sort"> تاریخ </th> --}}
                                                <th scope="col" class="no-sort"> برانڈ کا نام </th>
                                                <th scope="col"> قسم</th>
                                                <th scope="col" class="no-sort">لمبائی</th>
                                                {{-- <th scope="col" class="no-sort">تھان</th> --}}
                                                <th scope="col" class="no-sort">رقم فی میٹر</th>
                                                <th scope="col" class="no-sort">کل فروخت کی رقم</th>
                                                <th scope="col" class="no-sort">منافع</th>
                                                <th scope="col" class="no-sort">نقصان</th>
                                                {{-- <th scope="col" class="no-sort" colspan="2"
                                                    style="text-align: center;">عمل</th> --}}
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $totalamount = 0;
                                                $totalprofit = 0;
                                                $totalloss = 0;
                                            @endphp
                                            @foreach ($stocks as $st)
                                                <tr>
                                                    {{-- <td>{{ $st->sellDate }}</td> --}}
                                                    <td>{{ $st->brand->name }}</td>
                                                    <td>{{ $st->type->name }}</td>
                                                    <td>{{ $st->total_length }}</td>
                                                    {{-- <td>{{ $st->clothes_rack }}</td> --}}
                                                    <td>Rs:{{ number_format($st->selling_price) }}</td>
                                                    <td>Rs:{{ number_format($st->selling_price * $st->total_length) }}</td>
                                                    <td>Rs:{{ number_format($st->total_profit * $st->total_length) }}</td>
                                                    <td>Rs:{{ number_format($st->total_loss * $st->total_length)}}</td>
                                                </tr>
                                                @php
                                                    $totalamount += $st->selling_price * $st->total_length;
                                                    $totalprofit += $st->total_profit * $st->total_length;
                                                    $totalloss += $st->total_loss * $st->total_length;
                                                @endphp
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="table-info">
                                                <td colspan="4"></td>
                                                <td colspan="1" > {{$totalamount}}</td>
                                                <td colspan="1" > {{$totalprofit}}</td>
                                                <td colspan="1" > {{$totalloss}}</td>
                                            </tr>
                                        </tfoot>

                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </section>

    @include('Expenses.modal'){{-- to show date range modal box --}}

    <!-- Include jQuery (required) and daterangepicker JS files -->
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.css" />


    <script>
    $(document).ready(function($) {
        $('#cc-table-data-options-history1').DataTable({
            footerCallback: function (row, data, start, end, display) {
                var api = this.api();

                // Initialize totals
                var totalAmount = 0;
                var totalProfit = 0;
                var totalLoss = 0;

                // Loop over each row in the filtered data to calculate sums
                api.rows({ search: 'applied' }).every(function(rowIdx, tableLoop, rowLoop) {
                    var data = this.data();
                    
                    // Ensure correct parsing of columns to retrieve the needed values
                    var sellingPrice = parseFloat(data[3].replace('Rs:', '').replace(/,/g, '')) || 0;
                    var totalLength = parseFloat(data[2]) || 0;
                    var profitRow = parseFloat(data[5].replace('Rs:', '').replace(/,/g, '')) || 0;
                    var lossRow = parseFloat(data[6].replace('Rs:', '').replace(/,/g, '')) || 0;

                    // Calculate total amount by multiplying selling price by total length
                    totalAmount += sellingPrice * totalLength;
                    totalProfit += profitRow;
                    totalLoss += lossRow;
                });

                // Update footer cells with the calculated totals, prepending "Rs: "
                $(api.column(4).footer()).html("Rs:" + totalAmount.toLocaleString(undefined, { minimumFractionDigits: 2 }));
                $(api.column(5).footer()).html("Rs:" + totalProfit.toLocaleString(undefined, { minimumFractionDigits: 2 }));
                $(api.column(6).footer()).html("Rs:" + totalLoss.toLocaleString(undefined, { minimumFractionDigits: 2 }));
            }
        });
    });
</script>



@endsection
