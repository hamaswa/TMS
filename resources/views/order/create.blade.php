@extends('main')
@section('content')
<section class="main-content">
    <div class="container">
        <div class="card">
            @if($errors->any())
                <div class="alert alert-danger m-3" dir="rtl">
                    <strong>آرڈر محفوظ نہیں ہو سکا:</strong>
                    <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            @if(!$data['hasReadyTailor'])
                <div class="alert alert-warning m-3 text-right" dir="rtl" role="alert">
                    <strong>آرڈر بنانے سے پہلے درزی اور اس کی سلائی شرح مکمل کریں۔</strong>
                    <div class="mt-2">
                        @if($data['tailors']->isEmpty())
                            ابھی کوئی درزی موجود نہیں۔
                            <a class="alert-link" href="{{ route('admin.Tailor.create') }}">نیا درزی شامل کریں</a>
                        @else
                            موجودہ درزی کے لیے کم از کم ایک سلائی شرح شامل کریں۔
                            <a class="alert-link" href="{{ route('admin.Tailor.index') }}">درزیوں کی فہرست کھولیں</a>
                        @endif
                    </div>
                </div>
            @endif
            <form id="cc-form__addCustomerForm" action="{{ url('admin/order/insert')}}" class="add-customer-form mt-4"
                        method="post">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <label class="d-block text-right font-weight-bold" for="search">محفوظ ناپ تلاش کریں</label>
                        <div class="input-group">
                            <input class="form-control border-end-0 border rounded-pill search" type="text" placeholder="نام یا ناپ تلاش کریں"
                                id="search" data-url="{{url('admin/search')}}">
                            <span class="input-group-append">
                                <button class="btn btn-outline-secondary bg-white border-start-0 border rounded-pill ms-n3"
                                    type="button" aria-label="محفوظ ناپ تلاش کریں">
                                    <i class="fa fa-search" id="SearchData" aria-hidden="true"></i>
                                </button>
                            </span>
                        </div>

                        <!-- select -->
                        <div class=" mt-3" id="select">

                        </div>
                        <!-- select -->
                    </div>
                </div>

                <h1 class="h2 mb-4 mt-3 text-right">نیا آرڈر</h1>

                <input type="hidden" name="user_id" value="{{$data['customer']->user_id}}">
                @csrf
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="alert alert-light border text-right" dir="rtl">
                            <strong>رقم کی وضاحت:</strong>
                            پہلے گاہک کا پرانا مشترکہ بقایا دکھایا گیا ہے۔ اس آرڈر کی کل قیمت اور ابھی وصول ہونے والی رقم درج کریں؛
                            موجودہ آرڈر کی باقی رقم خود بخود حساب ہوگی۔
                        </div>
                        @if($data['measurementTemplates']->isNotEmpty())
                        <div class="alert alert-info text-right" dir="rtl"><div class="form-group mb-2"><label class="font-weight-bold" for="order-measurement-template">لباس کا پیمائش ٹیمپلیٹ</label><select id="order-measurement-template" class="form-control" name="measurement_template_id"><option value="">تمام محفوظ پیمائش</option>@foreach($data['measurementTemplates'] as $template)<option value="{{ $template->id }}" @selected((string) old('measurement_template_id', $data['measurementTemplateId']) === (string) $template->id)>{{ $template->name }}{{ $template->is_default ? ' — ڈیفالٹ' : '' }}</option>@endforeach</select><small class="form-text text-muted">آرڈر کے ساتھ صرف منتخب ٹیمپلیٹ کی پیمائش محفوظ ہوگی۔ گاہک کی اصل پیمائش تبدیل نہیں ہوگی۔</small></div></div>
                        @endif
                        <div class="form-group form-row">
                            <label class="col-sm-3 col-form-label" for="suitQuantity">سوٹ کی تعداد</label>
                            <div class="col-sm-9">
                                <input type="number" class="form-control" name="suitQuantity" id="suitQuantity" required>

                            </div>
                        </div>
                        <div class="form-row m-0">
                            <label class="col-sm-3 col-form-label" for="order_customer_name">گاہک کا نام</label>
                            <div class="col-sm-9">
                                <input id="order_customer_name" type="text" class="form-control" name="CustomerName" readonly
                                    value="{{$data['customer']->name}}">
                                <input type="hidden" class="form-control" name="customerId" value="{{$data['customer']->id}}">
                            </div>
                        </div>
                        <div class="form-group form-row">
                            <label class="col-sm-3 col-form-label" for="totalPayment">اس آرڈر کی کل قیمت</label>
                            <div class="col-sm-9">
                                <input type="number" class="form-control" name="totalPayment" id="totalPayment" required>
                            </div>
                        </div>
                        <div class="form-group form-row">
                            <label class="col-sm-3 col-form-label" for="recivedPayment">ابھی وصول شدہ رقم</label>
                            <div class="col-sm-9">
                                <input type="number" class="form-control" name="recivedPayment" id="recivedPayment" required>
                            </div>
                        </div>
                        <div class="card card-body bg-light border mb-3">
                            <h3 class="h6 font-weight-bold">وصول شدہ رقم کی تفصیل</h3>
                            @include('components.payment-method-fields', ['prefix' => 'tailoring_order'])
                            <div class="form-group mb-0">
                                <label for="tailoring_order_paid_on">ادائیگی کی تاریخ</label>
                                <input id="tailoring_order_paid_on" type="date" name="paid_on" value="{{ now()->toDateString() }}" class="form-control" required>
                            </div>
                        </div>
                        <div class="form-group form-row">
                            <label class="col-sm-3 col-form-label" for="customer_previous_balance">گاہک کا پچھلا مشترکہ بقایا</label>
                            <div class="col-sm-9">
                                @if($data['remainingBalance'] !== null)
                                    <input id="customer_previous_balance" type="number" class="form-control" readonly
                                        value="{{$data['remainingBalance']}}">
                                @else
                                    <span id="customer_previous_balance" class="form-control text-muted">بقایا دیکھنے کی اجازت نہیں</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group form-row">
                            <label class="col-sm-3 col-form-label" for="balance">اس آرڈر کی باقی رقم</label>
                            <div class="col-sm-9">
                                <input type="number" class="form-control" name="balance" readonly id="balance">
                                <small class="form-text text-muted">کل قیمت میں سے ابھی وصول شدہ رقم منہا کر کے حساب کیا گیا ہے۔</small>
                            </div>
                        </div>
                        <div class="form-group form-row">
                            <label class="col-sm-3 col-form-label" for="order_return_date">حوالگی کی تاریخ</label>
                            <div class="col-sm-9">
                                <input id="order_return_date" type="date" class="form-control" name="returnDate" required>
                            </div>
                        </div>
                        <div class="form-group form-row">
                            <label class="col-sm-3 col-form-label" for="order_serial_number">سیریل نمبر</label>
                            <div class="col-sm-9" id="suitNumContainer">
                                <input id="order_serial_number" type="text" class="form-control" name="serail" required value="{{$data['serialNumber'];}}" readonly>
                            </div>
                        </div>


                        <div class="form-group form-row">
                            <label class="col-sm-3 col-form-label" for="tailor-selected">درزی</label>
                            <div class="col-sm-9">
                                <select id="tailor-selected" class="form-control" name="tailorId" required dir="rtl">
                                    <option value="">درزی کو منتخب کریں</option>
                                    @foreach($data['tailors'] as $tailor)
                                    <option value="{{$tailor->id}}" @disabled($tailor->tailorsalary->isEmpty())>
                                        {{$tailor->name}}{{ $tailor->tailorsalary->isEmpty() ? ' — شرح شامل نہیں' : '' }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group form-row">
                            <span class="col-sm-3 col-form-label">درزی رقم</span>
                            {{-- new change --}}
                            <div class="col-sm-9" id="tailor-rates">
                            </div>
                        </div>
                        <!--<div class="form-group form-row">-->
                        <!--    <label class="col-sm-3 col-form-label">ڈیزائن</label>-->
                        <!--    <div class="col-sm-9">-->
                        <!--        <select id="design-selected" class="form-control" name="design" required dir="rtl">-->
                        <!--            <option value="0">ڈیزائن کو منتخب کریں</option>-->
                        <!--            @foreach($data['design'] as $design)-->
                        <!--            <option value="{{ $design->Name . ' - ' . $design->id }}">{{$design->Name}}</option>-->
                        <!--            @endforeach-->
                        <!--        </select>-->
                        <!--    </div>-->
                        <!--</div>-->
                        <!--<div class="form-group form-row">-->
                        <!--    <label class="col-sm-3 col-form-label">ڈیزائن کی قیمت</label>-->
                        <!--    <div class="col-sm-9">-->
                        <!--        <input type="number" class="form-control" name="designPrice" id="designPrice">-->
                        <!--    </div>-->
                        <!--</div>-->
                        {{-- <div class="form-group form-row">
                            <label class="col-sm-3 col-form-label">درزی رقم</label>
                            <div class="col-sm-9" id="tailor-rates">
                            </div>
                        </div> --}}
                        <div class="form-group form-row">
                            <div class="col-md-3">
                                <label for="order_remarks" style="font-size:23px; float:right">نوٹ</label>
                            </div>
                            <div class="col-md-9">
                                <textarea id="order_remarks" rows="4" cols="" class="form-control" name="remarks" dir="auto"
                              
                                >{{$data['customer']->note}}</textarea>
                            </div>
                        </div>
                        <div class="button-group mt-2">
                            <button type="submit" class="btn btn-blue mr-3" @disabled(!$data['hasReadyTailor'])>محفوظ کریں</button>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>

</section>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var total = document.getElementById('totalPayment');
        var received = document.getElementById('recivedPayment');
        var balance = document.getElementById('balance');
        function updateBalance() {
            if (!total || !received || !balance) return;
            var totalValue = parseFloat(total.value || '0');
            var receivedValue = parseFloat(received.value || '0');
            balance.value = Math.max(0, totalValue - receivedValue).toFixed(2);
        }
        total?.addEventListener('input', updateBalance);
        received?.addEventListener('input', updateBalance);
        updateBalance();
    });

    // $(document).ready(function () {
    //     $('#suitQuantity').on('input', function () {
    //         var suitQuantity = $(this).val();
    //         console.log('Suit Quantity:', suitQuantity);
    //         var suitNumContainer = $('#suitNumContainer');
    //         suitNumContainer.empty();

    //         for (var i = 1; i <= suitQuantity; i++) {
    //             var inputField = $('<input>')
    //                 .attr('type', 'text')
    //                 .attr('class', 'form-control')
    //                 .attr('name', 'suitNum[]')
    //                 .attr('placeholder', 'Enter Suit Number ' + i);

    //             suitNumContainer.append(inputField);
    //         }
    //     });
    // });
</script>


@endsection
