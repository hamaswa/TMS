@extends('main')
@section('content')
    <style>
        .customer-link {
            box-shadow: 0 0 16px 2px rgb(0 0 0 / 20%);
            border-radius: 20px;
            text-align: center !important;
            font-weight: bold;
            text-transform: uppercase;
        }


    </style>
    <section class="main-content">
        <div class="container">
            <div class="card">
                <div class="table-title mb-4">
                    <h3 class="text-right">گاہکوں کی لسٹ</h3>
                    @if (Session::has('insert'))
                        <div class="alert alert-success">{{ Session::get('insert') }}</div>
                    @endif
                    @if (Session::has('customer_pin'))
                        <div class="alert alert-warning text-right" dir="rtl" role="alert">
                            <strong>{{ Session::get('customer_pin_name') }} کا موبائل پن:</strong>
                            <code class="mx-2" style="font-size:1.25rem">{{ Session::get('customer_pin') }}</code>
                            <div class="small mt-1">یہ پن صرف ایک بار دکھایا جا رہا ہے۔ اسے محفوظ طریقے سے گاہک کو دیں۔</div>
                        </div>
                    @endif
                    @if (Session::has('balanceError'))
                        <div class="alert alert-danger">
                            {{ Session::get('balanceError') }}
                        </div>
                    @endif
                </div>
                <div>
                    <a href="{{ route('admin.Customers.create') }}" class="btn btn-primary">
                        <i class="fa fa-user-plus ml-1" aria-hidden="true"></i> نیا گاہک شامل کریں
                    </a>
                    <button type="button" class="btn btn-green" data-toggle="modal" data-target="#customersCsvModal"> ایکسل فائل اپ لوڈ کریں۔ </button>
                    <button type="button" class="btn btn-blue px-2 py-3" data-toggle="modal" data-target="#myRackModal">ریک نمبر شامل
                        کریں</button>
                    <a href="{{route('admin.customercsv')}}" class="btn btn-green"> ایکسل فائل میں برآمد کریں۔ </a>
                </div>
                <div class="row justify-content-center">
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table js-sortable-table" id="cc-table-data-customer-list">
                                <thead>
                                    <tr>
                                        <th width="20%" scope="col">سیریل نمبر</th>
                                        <th width="20%" scope="col">نام</th>
                                        <th width="20%" scope="col" class="no-sort">نمبر</th>
                                        <th width="20%" scope="col" class="no-sort">بقایا جات</th>
                                        <th width="20%" scope="col" class="no-sort">رقم کی ادائیگی</th>
                                        <th width="20%" scope="col" class="no-sort">تبدیلی</th>
                                        <th width="20%" scope="col" class="no-sort">آرڈر</th>
                                        <th width="20%" scope="col" class="no-sort">مشترکہ کھاتہ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($customers as $customer)
                                        <tr>
                                            <!--<td class="customer_id">{{ $customer->id }}</td>-->
                                            <td class="customer_serial">{{ $loop->iteration }}</td>
                                            <td style="cursor: pointer;" data-url="{{ url('admin/getCustomer') }}"
                                                class="getCustomer customer-link" data-id="{{ $customer->id }}"
                                                data-name="{{ $customer->name }}">{{ $customer->name }}</td>
                                            <td>{{ $customer->phone_number1 }}</td>
                                            <td>
                                                @if($canViewBalances)
                                                    Rs: {{ number_format((float) $customer->current_balance) }}
                                                @else
                                                    <span class="text-muted">اجازت درکار ہے</span>
                                                @endif
                                            </td>
                                            <td>@if($canViewBalances)<button type="button" class="btn btn-blue customer_payment_paid"
                                                    aria-label="{{ $customer->name }} کی ادائیگی درج کریں"
                                                    data-customerid="{{ $customer->id }}" data-toggle="modal"
                                                    data-target="#myModalpayment"><i class="fa fa-wallet" aria-hidden="true"></i></button>@else — @endif</td>
                                            <td>
                                                <a href="{{ url('admin/Customers/' . $customer->id . '/edit') }}"
                                                    class="btn btn-blue">تبدیل</a>
                                            </td>
                                            <td>
                                                <a href="{{ url('admin/order', ['id' => $customer->id]) }}"
                                                    class="btn btn-blue">آرڈر</a>
                                            </td>
                                            <td><a href="{{ route('admin.customers.statement', $customer) }}" class="btn btn-outline-primary">پروفائل / کھاتہ</a></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div id="orderDetail" style="display:none">
                        <div class="table-title  mb-4 mt-3">
                            <h3 class="text-right"> گاہک <span id="cus_name"></span> کی آرڈرلسٹ </h3>
                        </div>
                        <div class="row justify-content-center">
                            <div class="col-md-12">
                                <table class="table js-sortable-table" id="cc-table-data-order-history">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>نمبر</th>
                                            <th scope="col" class="no-sort">رقم</th>
                                            <th scope="col" class="no-sort">آرڈر تاریخ</th>
                                            <th scope="col" class="no-sort">واپسی تاریخ</th>
                                            <th scope="col" class="no-sort">کپڑوں کی تعداد</th>
                                            <th scope="col" class="no-sort">درزی</th>
                                            <th scope="col" class="no-sort">درجه</th>
                                            <th scope="col" class="no-sort">ریک نمبر</th>
                                            <th scope="col" class="no-sort">تبدیل</th>
                                            <th scope="col" class="no-sort">پرنٹ کریں</th>
                                        </tr>
                                    </thead>
                                    <tbody class="tbody">

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- The Modal -->
    <div class="modal" id="myModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ url('admin/order-status') }}" method="post">
                    @csrf
                    <input type="hidden" value="" id="order_id" name="order_id">

                    <!-- Modal Header -->
                    <div class="modal-header">
                        <h4 class="modal-title">آرڈر کی درجہ منتخب کریں۔</h4>
                    </div>

                    <!-- Modal body -->
                    <div class="modal-body">
                        <p class="text-right"><label>درجہ منتخب کریں</label></p>
                        <select class="form-control order-status" name="order_status" style="padding:0px" required></select>
                        <small class="form-text text-muted">صرف موجودہ مرحلے کے بعد جائز اگلا مرحلہ دکھایا جائے گا۔</small>
                    </div>

                    <!-- Modal footer -->
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary  ml-2" id="submit-button">محفوظ کریں</button>
                        <button type="button" class="btn btn-danger" data-dismiss="modal">بند کریں</button>

                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- payment model -->
    <!-- The Modal -->
    <div class="modal" id="myModalpayment">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ url('admin/DirectPayment') }}" method="post">
                    @csrf
                    <input type="hidden" value="" id="customer_id" name="customer_id">

                    <!-- Modal Header -->
                    <div class="modal-header">
                        <h4 class="modal-title">رقم کی ادائیگی</h4>
                    </div>

                    <!-- Modal body -->
                    <div class="modal-body">
                        <div class="form-group">
                            <p class="text-right">رقم</p>
                            <input type="number" name="DirectPayment" value="" class="form-control">
                            <br>
                            <textarea class="form-control" rows="3" name="comment"></textarea>
                        </div>
                    </div>

                    <!-- Modal footer -->
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary  ml-2">محفوظ کریں</button>
                        <button type="button" class="btn btn-danger" data-dismiss="modal">بند کریں</button>

                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- Rack Modal --}}
    <div class="modal" id="myRackModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ url('admin/RackNo') }}" method="post">
                    @csrf

                    <!-- Modal Header -->
                    <div class="modal-header">
                        <h4 class="modal-title">ریک نمبر شامل کریں</h4>
                    </div>

                    <!-- Modal body -->
                    <div class="modal-body">
                        <div class="form-group">
                            <p class="text-right">ریک نمبر</p>
                            <input type="text" name="RackNo" value="" class="form-control">
                        </div>
                    </div>

                    <!-- Modal footer -->
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary  ml-2">محفوظ کریں</button>
                        <button type="button" class="btn btn-danger" data-dismiss="modal">بند کریں</button>

                    </div>
                </form>
            </div>
        </div>
    </div>


    {{-- Csv Modal --}}
    <div class="modal" id="customersCsvModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{route("admin.customerscsv")}}" method="post" enctype="multipart/form-data">
                    @csrf

                    <!-- Modal Header -->
                    <div class="modal-header">
                        <h4 class="modal-title">ایکسل فائل اپ لوڈ کریں۔</h4>
                    </div>

                    <!-- Modal body -->
                    <div class="modal-body">
                        <div class="form-group">
                            <p class="text-right">ایکسل فائل</p>
                            <input type="file" name="csvFile" value="" class="form-control" required>
                        </div>
                    </div>

                    <!-- Modal footer -->
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary  ml-2">محفوظ کریں</button>
                        <button type="button" class="btn btn-danger" data-dismiss="modal">بند کریں</button>

                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {

        });
    </script>
@endsection
