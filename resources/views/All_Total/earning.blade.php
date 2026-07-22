@extends('main')
@section('content')
    <section class="main-content">
        <div class="container">
            <div class="card col-sm-10 mx-auto">
                <div class="row">
                    <div class="col-md-12">
                        @include('inc.message')
                        <p style="font-size: 24px;text-align:center;"><b>پورے سال کی آمدنی </b></p>

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
                                                <th scope="col" class="no-sort"> فروخت کی آمدنی </th>
                                                <th scope="col" class="no-sort">کل اضافی اخراجات</th>
                                                <th scope="col" class="no-sort">ماہانہ بلوں کی رقم</th>
                                                <th scope="col" class="no-sort">ماہانہ کرایہ کی رقم</th>
                                                <th scope="col" class="no-sort">ملازمین کی ماہانہ تنخواہ</th>
                                                <th scope="col" class="no-sort">کل کمائی</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $totalsales = 0;
                                                $totalexpens = 0;
                                                $totalbills = 0;
                                                $totalrent = 0;
                                                $totalsalary = 0;
                                                $totalEarnings = 0;
                                            @endphp
                                            @foreach ($monthly_earnings as $monthName => $earnings)
                                                <tr>
                                                    <td>{{ $monthName }}</td>
                                                    <td>Rs:{{ number_format($earnings['sales']) }}</td>
                                                    <td>Rs:{{ number_format($earnings['extra_expense']) }}</td>
                                                    <td>Rs:{{ number_format($earnings['monthly_bills']) }}</td>
                                                    <td>Rs:{{ number_format($earnings['monthly_rent']) }}</td>
                                                    <td>Rs:{{ number_format($earnings['monthly_salary']) }}</td>
                                                    <td>
                                                        Rs:{{ number_format($earnings['sales'] - $earnings['extra_expense'] - $earnings['monthly_bills'] - $earnings['monthly_rent'] - $earnings['monthly_salary']) }}
                                                    </td>
                                                    @php
                                                        // Update the total earnings for each iteration of the loop
                                                        $totalEarnings +=
                                                            $earnings['sales'] -
                                                            $earnings['extra_expense'] -
                                                            $earnings['monthly_bills'] -
                                                            $earnings['monthly_rent'] -
                                                            $earnings['monthly_salary'];
                                                    @endphp
                                                </tr>
                                                @php
                                                    $totalsales += $earnings['sales'];
                                                    $totalexpens += $earnings['extra_expense'];
                                                    $totalbills += $earnings['monthly_bills'];
                                                    $totalrent += $earnings['monthly_rent'];
                                                    $totalsalary += $earnings['monthly_salary'];
                                                @endphp
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="table-info">
                                                <td style="text-align: center;">
                                                    <b style="font-size: 18px;">
                                                        ٹوٹل:
                                                    </b>
                                                </td>
                                                <td><b style="font-size: 18px;">Rs:{{ number_format($totalsales) }}</b></td>
                                                <td><b style="font-size: 18px;">Rs:{{ number_format($totalexpens) }}</b></td>
                                                <td><b style="font-size: 18px;">Rs:{{ number_format($totalbills) }}</b></td>
                                                <td><b style="font-size: 18px;">Rs:{{ number_format($totalrent) }}</b></td>
                                                <td><b style="font-size: 18px;">Rs:{{ number_format($totalsalary) }}</b></td>
                                                <td>
                                                    <b style="font-size: 18px;">
                                                        {{ number_format($totalEarnings) }} روپے
                                                    </b>
                                                </td>
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
@endsection
