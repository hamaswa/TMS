@extends('main')

@section('content')
    <section class="main-content">
        <div class="container">
            <div class="card">
                <div class="table-title mb-4">
                    <h3 class="text-right">آن لائن آرڈرز کی لیسٹ</h3>
                    @if (Session::has('insert'))
                        <div class="alert alert-success">{{ Session::get('insert') }}</div>
                    @endif
                </div>
                <div class="row justify-content-center">
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table js-sortable-table" id="cc-table-data-customer-list">
                                <thead>
                                    <tr>
                                        <th width="20%" scope="col">آرڈر نمبر</th>
                                        <th width="20%" scope="col">نام</th>
                                        <th scope="col" class="no-sort">کپڑے کی قسم</th>
                                        <th scope="col" class="no-sort">کپڑے کی کمپنی</th>
                                        <th scope="col" class="no-sort">کپڑے کا رنگ</th>
                                        <th scope="col" class="no-sort">کپڑے کی لمبائی</th>
                                        <th scope="col" class="no-sort">آرڈر کی قیمت</th>
                                        <th scope="col" class="no-sort">آرڈر درجه</th>
                                        <th scope="col" class="no-sort">عمل</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($orders as $order)
                                        @php
                                            $brandName = $order->cloth?->brand?->name ?? 'برانڈ دستیاب نہیں';
                                            $typeName = $order->cloth?->type?->name ?? 'قسم دستیاب نہیں';
                                            $statusLabel = [
                                                'pending' => 'زیرِ انتظار',
                                                'complete' => 'مکمل',
                                                'cancelled' => 'منسوخ',
                                            ][$order->status] ?? $order->status;
                                        @endphp
                                        <tr>
                                            <td class="customer_id">{{ $order->id }}</td>
                                            <td style="cursor: pointer;">{{ $order->user?->name ?? 'گاہک دستیاب نہیں' }}</td>
                                            <td>{{ $typeName }}</td>
                                            <td>{{ $brandName }}</td>
                                            <td >{{ $order->color }}</td>
                                            <td >{{ $order->length }}</td>
                                            <td >{{ $order->length * $order->price }}</td>
                                            <td>{{ $statusLabel }}</td>
                                            <td>
                                                @if($order->status !== 'complete')
                                                    <button type="button" class="btn btn-success btn-action"
                                                        onclick="OrderComlete({{ $order->id }})">مکمل کریں</button>
                                                @else
                                                    <span class="badge badge-success">مکمل</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div id="orderDetail" style="display:none">
                        <div class="table-title  mb-4 mt-3">
                            <h3 class="text-right">گاہک <span id="cus_name"></span> آرڈرلیسٹ</h3>
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
                        <select class="form-control order-status" name="order_status">
                            <option value="new">نیو </option>
                            <option value="start">سلائی شروع ہے</option>
                            <option value="complete">مکمل ہوگیا</option>
                        </select>
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
    <script>
        function OrderComlete(OrderId) {
            // console.log('OrderComlete function called with OrderId:', OrderId);
            $.ajax({
                url: '/admin/order-complete/' + OrderId,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    // console.log('Server response:', response);
                    if (response.success) {
                        alert('آرڈر مکمل کر دیا گیا ہے۔');
                        location.reload();
                    } else {
                        alert(response.error || 'آرڈر مکمل نہیں ہو سکا۔');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', status, error);
                    alert('آرڈر مکمل نہیں ہو سکا۔ دوبارہ کوشش کریں۔');
                }
            });
        }
    </script>
@endsection
