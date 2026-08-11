@extends('main')
@section('content')
    <section class="main-content">
        <div class="container">
            <div class="card">
                <div class="row">
                    <div class="col-md-12">

                        @include('inc.message')

                        <div class="bg-white px-3 py-4">
                            <div class="table-title  mb-4 mt-2">
                                <h5 class="text-right">فروخت ریکارڈ</h5>
                            </div>
                            <div class="row justify-content-center">
                                <div class="col-md-12">
                                    <div class="table-responsive">
                                        <table class="table js-sortable-table" id="cc-table-data-customer-list">
                                            <thead>
                                                <tr>
                                                    <th scope="col" class="no-sort"></th>
                                                    <th scope="col" class="no-sort">سیریل نمبر</th>
                                                    <th scope="col" class="no-sort">گاہک کا نام</th>
                                                    <th scope="col" class="no-sort">بقایا جات</th>
                                                    <th width="20%" scope="col" class="no-sort">رقم کی ادائیگی</th>
                                                    <th scope="col" class="no-sort">مزید تفصیلات </th>
                                                    <th scope="col" class="no-sort">عمل</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($customers as $customer)
                                                    <tr>
                                                        <td></td>
                                                        <td class="customer_id">{{ $customer->id }}
                                                        </td>
                                                        <td style="cursor: pointer;" data-url="{{ url('admin/getSale') }}"
                                                            class="GetCustomer customer-link sale"
                                                            data-id="{{ $customer->id }}" data-name="{{ $customer->name }}">
                                                            {{ $customer->name }}</td>
                                                        <td style="font-size: 18px;">@if($canViewBalances)<b>Rs: {{ $customerTransactions[$customer->id] }}</b>@else<span class="text-muted">اجازت درکار ہے</span>@endif</td>
                                                        <td>@if($canViewBalances)<button type="button" class="btn btn-blue customer_payment"
                                                                aria-label="{{ $customer->name }} کی ادائیگی درج کریں"
                                                                data-customerid="{{ $customer->id }}" data-toggle="modal"
                                                                data-target="#myModalpayment"><i
                                                                    class="fa fa-wallet" aria-hidden="true"></i></button>@else — @endif</td>
                                                        <td><a href="{{ route('admin.customers.statement', $customer) }}"
                                                                class="btn btn-sm btn-outline-primary">مشترکہ کھاتہ</a></td>
                                                        <td>


                                                            <form action="{{ route('admin.dlt', $customer->id) }}" method="POST" class="d-inline" data-confirm="کیا آپ واقعی یہ ریکارڈ حذف کرنا چاہتے ہیں؟">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-link p-0 delete-tr" aria-label="Delete record"><i class="fa fa-trash-alt" aria-hidden="true"></i></button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div id="orderDetail" style="display:none">
                                <div class="table-title  mb-4 mt-3">
                                    <h3 class="text-right">گاہک <span id="cus_name"></span> فروخت لسٹ</h3>
                                </div>
                                <div class="row justify-content-center">
                                    <div class="col-md-12">
                                        <table class="table js-sortable-table" id="cc-table-data-order-history">
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th>نمبر</th>
                                                    <th scope="col" class="no-sort">رقم</th>
                                                    <th scope="col" class="no-sort">فروخت تاریخ</th>
                                                    <th scope="col" class="no-sort">برانڈ</th>
                                                    <th scope="col" class="no-sort">کپڑے کی قسم</th>
                                                    <th scope="col" class="no-sort">رنگ</th>
                                                    <th scope="col" class="no-sort">میٹر / گزانہ</th>
                                                    <th scope="col" class="no-sort">ریٹ فی میٹر</th>
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
            </div>
    </section>
    <!-- payment model -->
    <!-- The Modal -->
    <div class="modal" id="myModalpayment">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ url('admin/SaleDirectPayment') }}" method="post">
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
                            <select name="comment" id="" class="form-control" style="padding: 0px">
                                <option value="Sale">Sale</option>
                                <option value="Tailor" class="form-control">Tailor</option>
                            </select>
                            {{-- <textarea class="form-control" rows="3" name="comment"></textarea> --}}
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
@endsection
