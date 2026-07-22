@extends('main')
@section('content')
<section class="main-content">
    <div class="container">
        <div class="card">
            <div class="row">
                <div class="col-md-12">
                    @if(Session::has('insert'))
                    <div class="alert alert-success">{{Session::get('insert')}}</div>
                    @endif

                    @if(Session::has('update'))
                    <div class="alert alert-warning">{{Session::get('update')}}</div>
                    @endif

                    @if(Session::has('delete'))
                    <div class="alert alert-danger">{{Session::get('delete')}}</div>
                    @endif

                    <div class="bg-white px-3 py-4">
                        <div class="table-title mb-4 mt-2">
                            <h5 class="text-right"> {{ $sale->customer_name }} کی فروخت کی تفصیلات </h5>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="table-responsive">
                                    <table class="table js-sortable-table cc-table-data-options-history" id="cc-table-data-options-history">
                                        <thead>
                                            <tr>
                                                <th scope="col" class="no-sort"></th>
                                                <th scope="col" class="no-sort"> پروڈکٹ کا نام </th>
                                                <th scope="col" class="no-sort">مصنوعات کی مقدار</th>
                                                <th scope="col" class="no-sort"> مصنوعات کی کل قیمت </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($sale->detail as $detail)
                                            <tr align="center">
                                                <td></td>
                                                <td>{{$detail->product_name}}</td>
                                                <td>{{$detail->quantity}}</td>
                                                <td>{{$detail->price}}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Transaction data -->
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr align="center">
                                        <th scope="col"> رقم موصول ہوئی </th>
                                        <th scope="col"> واجب الادا رقم </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($transaction as $data)
                                    <tr align="center">
                                        <td>{{$data->recivedPayment}}</td>
                                        <td>{{$data->remainingBalance}}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <!-- End Transaction data -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
