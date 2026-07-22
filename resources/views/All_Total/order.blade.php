@extends('main')
@section('content')
    <section class="main-content">
        <div class="container">
            <div class="card col-sm-10 mx-auto">
                <div class="row">
                    <div class="col-md-12">
                        @include('inc.message')
                        <p style="font-size: 24px;text-align:center;">پورے سال کے آرڈرز</p>

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
                                                <th scope="col" class="no-sort"> مہینہ </th>
                                                <th scope="col" class="no-sort"> کل آرڈرز</th>
                                                <th scope="col" class="no-sort"> سوٹ کی تعداد </th>
                                                <th scope="col" class="no-sort">کل سلائی</th>
                                                <th scope="col" class="no-sort">نئے آرڈرز</th>
                                                <th scope="col" class="no-sort">سلائی شروع ہے</th>
                                                <th scope="col" class="no-sort">آرڈر مکمل</th>
                                                {{-- <th scope="col" class="no-sort">نقصان</th> --}}
                                                {{-- <th scope="col" class="no-sort" colspan="2"
                                                    style="text-align: center;">عمل</th> --}}
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $totalorders = 0;
                                                $totalsuits = 0;
                                                $totalpayment = 0;
                                                $totalnew = 0;
                                                $totalinprocess = 0;
                                                $totalcomplete = 0;
                                            @endphp
                                            @foreach ($monthly_orders as $monthname => $orders)
                                                <tr>
                                                    <td>{{$monthname}}</td>
                                                    <td>{{$orders['orders']}}</td>
                                                    <td>{{$orders['suits']}}</td>
                                                    <td>Rs:{{number_format($orders['payment'])}}</td>
                                                    <td>{{$orders['neworders']}}</td>
                                                    <td>{{$orders['inprocessorders']}}</td>
                                                    <td>{{$orders['completed']}}</td>
                                                </tr>
                                                @php
                                                    $totalorders += $orders['orders'];
                                                    $totalsuits += $orders['suits'];
                                                    $totalpayment += $orders['payment'];
                                                    $totalnew += $orders['neworders'];
                                                    $totalinprocess += $orders['inprocessorders'];
                                                    $totalcomplete += $orders['completed'];
                                                @endphp
                                            @endforeach

                                        </tbody>
                                        <tfoot>
                                            <tr class="table-info">
                                                <td><b style="font-size: 18px;">
                                                    ٹوٹل:&nbsp;
                                                    </b></td>
                                                    <td><b style="font-size: 18px;">{{ $totalorders }}</b></td>
                                                <td><b style="font-size: 18px;">{{ $totalsuits }}</b></td>
                                                <td><b style="font-size: 18px;">Rs:{{ number_format($totalpayment) }}</b></td>
                                                <td><b style="font-size: 18px;">{{ $totalnew }}</b></td>
                                                <td><b style="font-size: 18px;">{{ $totalinprocess }}</b></td>
                                                <td><b style="font-size: 18px;">{{ $totalcomplete }}</b></td>
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

    <script>
        // $(document).ready(function() {
        //     $('#cc-table-data-options-history1').DataTable();
        // });
    </script>
@endsection
