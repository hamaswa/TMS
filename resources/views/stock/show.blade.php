@extends('main')
@section('content')
    <style>
        .dataTables_wrapper {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 20px;
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            margin-top: 20px;
        }

        .dataTables_wrapper .dataTables_paginate .pagination {
            margin: 0;
        }

        .dataTables_wrapper .table {
            border-collapse: collapse;
            width: 100%;
        }

        .dataTables_wrapper .table th,
        .dataTables_wrapper .table td {
            border: 1px solid #ddd;
            padding: 8px 15px;
        }

        .dataTables_wrapper .table th {
            background-color: #f2f2f2;
        }
    </style>
    <section class="main-content">
        <div class="container">
            <div class="card">
                <div class="row">
                    <div class="col-md-8">
                        @if (Session::has('insert'))
                            <div class="alert alert-success">{{ Session::get('insert') }}</div>
                        @endif

                        @if (Session::has('update'))
                            <div class="alert alert-warning">{{ Session::get('update') }}</div>
                        @endif

                        @if (Session::has('delete'))
                            <div class="alert alert-danger">{{ Session::get('delete') }}</div>
                        @endif

                        <div class="bg-white px-3 py-4">
                            <div class="table-title mb-4 mt-2">
                                <h5 class="text-center">فروخت کی تفصیلات</h5>
                            </div>

                            <!-- Sale details -->
                            <div class="table-responsive">
                                <table class="table js-sortable-table cc-table-data-options-history" id="sale">
                                    <thead>
                                        <tr>
                                            <th scope="col">تاریخ</th>
                                            <th scope="col">گاہک کا نام</th>
                                            <th scope="col">برانڈ کا نام</th>
                                            <th scope="col"> قسم</th>
                                            <th scope="col">لمبائی</th>
                                            <th scope="col">ریٹ فی میٹر</th>
                                            <th scope="col">کل رقم۔</th>
                                            {{-- <th scope="col">Payment</th>
                                            <th scope="col">Balance</th> --}}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($stocks as $stock)
                                            <tr>
                                                <td>{{ $stock->sellDate }}</td>
                                                <td>{{ $stock->c_name }}</td>
                                                <td>{{ $stock->brand->name }}</td>
                                                <td>{{ $stock->type->name }}</td>
                                                <td>{{ $stock->total_length }}</td>
                                                <td>Rs: {{ $stock->selling_price }}</td>
                                                <td>Rs: {{ $stock->selling_price * $stock->total_length }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="table-responsive">
                            <!-- Sale details -->
                            <table class="table table-bordered js-sortable-table cc-table-data-options-history">
                                <thead>
                                    <tr>
                                    <tr>
                                        <th>فروخت کی کل رقم </th>
                                        <th scope="col">ادائیگی موصول ہوئی۔</th>
                                        <th scope="col">بقیہ رقم</th>
                                    </tr>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $totalBalance = 0;
                                    @endphp
                                    @foreach ($transactions as $transaction)
                                        <tr>
                                            <td>{{ $transaction->Payment + $transaction->Balance }}</td>
                                            <td>Rs:{{ $transaction->Payment }}</td>
                                            <td>Rs:{{ $transaction->Balance }}</td>
                                        </tr>
                                        @php
                                            $totalBalance += $transaction->Balance;
                                        @endphp
                                    @endforeach

                                <tfoot>
                                    <td colspan="3"> فروخت کی بقایا ادائیگی Rs : {{ $totalBalance }}</td>
                                </tfoot>

                            </table>
                            <br>

                            <!-- Tailor details -->
                            @if (count($tailortransactions) > 0)
                                <div class="table-responsive">
                                    <table class="table table-bordered js-sortable-table cc-table-data-options-history"
                                        id="tailor">
                                        <thead>
                                            <tr>
                                                <th>ٹیلرنگ کی کی کل رقم </th>
                                                <th scope="col">ادائیگی موصول ہوئی۔</th>
                                                <th scope="col">بقیہ رقم</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $tailorBalance = 0;
                                            @endphp
                                            @foreach ($tailortransactions as $tailortransaction)
                                                <tr>
                                                    <td>Rs:{{ $tailortransaction->Payment + $tailortransaction->Balance }}
                                                    </td>
                                                    <td>Rs:{{ $tailortransaction->Payment }}</td>
                                                    <td>Rs:{{ $tailortransaction->Balance }}</td>
                                                </tr>
                                                @php
                                                    $tailorBalance += $tailortransaction->Balance;
                                                @endphp
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <td colspan="3">  ٹیلرنگ کی باقی ادائیگی Rs : {{ $tailorBalance }}</td>
                                        </tfoot>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script>
        $(document).ready(function() {
            $("#sale").DataTable();
            // $("#tailor").DataTable();
        });
    </script>
@endsection
