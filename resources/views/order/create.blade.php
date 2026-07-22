@extends('main')
@section('content')
<section class="main-content">
    <div class="container">
        <div class="card">
            <form id="cc-form__addCustomerForm" action="{{ url('admin/order/insert')}}" class="add-customer-form mt-4"
                        method="post">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <p class="text-right"><b >ناپ</b></p>
                        <div class="input-group">
                            <input class="form-control border-end-0 border rounded-pill search" type="text" placeholder="search"
                                id="search" data-url="{{url('admin/search')}}">
                            <span class="input-group-append">
                                <button class="btn btn-outline-secondary bg-white border-start-0 border rounded-pill ms-n3"
                                    type="button">
                                    <i class="fa fa-search" id="SearchData"></i>
                                </button>
                            </span>
                        </div>

                        <!-- select -->
                        <div class=" mt-3" id="select">

                        </div>
                        <!-- select -->
                    </div>
                </div>

                <h2 class="mb-4 mt-3 text-right">نیا آرڈر</h2>

                <input type="hidden" name="user_id" value="{{$data['customer']->user_id}}">
                @csrf
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        @if($data['measurementTemplates']->isNotEmpty())
                        <div class="alert alert-info text-right" dir="rtl"><div class="form-group mb-2"><label class="font-weight-bold" for="order-measurement-template">لباس کا پیمائش ٹیمپلیٹ</label><select id="order-measurement-template" class="form-control" name="measurement_template_id"><option value="">تمام محفوظ پیمائش</option>@foreach($data['measurementTemplates'] as $template)<option value="{{ $template->id }}" @selected((string) old('measurement_template_id', $data['measurementTemplateId']) === (string) $template->id)>{{ $template->name }}{{ $template->is_default ? ' — ڈیفالٹ' : '' }}</option>@endforeach</select><small class="form-text text-muted">آرڈر کے ساتھ صرف منتخب ٹیمپلیٹ کی پیمائش محفوظ ہوگی۔ گاہک کی اصل پیمائش تبدیل نہیں ہوگی۔</small></div></div>
                        @endif
                        <div class="form-group form-row">
                            <label class="col-sm-3 col-form-label">کپڑوں کی تعداد</label>
                            <div class="col-sm-9">
                                <input type="number" class="form-control" name="suitQuantity" id="suitQuantity" required>

                            </div>
                        </div>
                        <div class="form-row m-0">
                            <label class="col-sm-3 col-form-label">نام</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" name="CustomerName" readonly
                                    value="{{$data['customer']->name}}">
                                <input type="hidden" class="form-control" name="customerId" value="{{$data['customer']->id}}">
                            </div>
                        </div>
                        <div class="form-group form-row">
                            <label class="col-sm-3 col-form-label">ٹوٹل قیمت</label>
                            <div class="col-sm-9">
                                <input type="number" class="form-control" name="totalPayment" id="totalPayment" required>
                            </div>
                        </div>
                        <div class="form-group form-row">
                            <label class="col-sm-3 col-form-label">وصول رقم</label>
                            <div class="col-sm-9">
                                <input type="number" class="form-control" name="recivedPayment" id="recivedPayment" required>
                            </div>
                        </div>
                        <div class="form-group form-row">
                            <label class="col-sm-3 col-form-label">بقیہ</label>
                            <div class="col-sm-9">
                                @if($data['remainingBalance'] !== null)
                                    <input type="number" class="form-control" name="totalBalance" readonly
                                        value="{{$data['remainingBalance']}}">
                                @else
                                    <span class="form-control text-muted">بقایا دیکھنے کی اجازت نہیں</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group form-row">
                            <label class="col-sm-3 col-form-label">بیلنس</label>
                            <div class="col-sm-9">
                                <input type="number" class="form-control" name="balance" readonly id="balance">
                            </div>
                        </div>
                        <div class="form-group form-row">
                            <label class="col-sm-3 col-form-label">واپسی کی تاریخ</label>
                            <div class="col-sm-9">
                                <input type="date" class="form-control" name="returnDate" required>
                            </div>
                        </div>
                        <div class="form-group form-row">
                            <label class="col-sm-3 col-form-label">سیریل نمبر</label>
                            <div class="col-sm-9" id="suitNumContainer">
                                <input type="text" class="form-control" name="serail" required value="{{$data['serialNumber'];}}" readonly>
                            </div>
                        </div>


                        <div class="form-group form-row">
                            <label class="col-sm-3 col-form-label">درزی</label>
                            <div class="col-sm-9">
                                <select id="tailor-selected" class="form-control" name="tailorId" required dir="rtl">
                                    <option value="0">درزی کو منتخب کریں</option>
                                    @foreach($data['tailors'] as $tailor)
                                    <option value="{{$tailor->id}}">{{$tailor->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group form-row">
                            <label class="col-sm-3 col-form-label">درزی رقم</label>
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
                                <lable style="font-size:23px; float:right">نوٹ</lable>
                            </div>
                            <div class="col-md-9">
                                <textarea rows="4" cols="" class="form-control" name="remarks" dir="rtl"
                              
                                >{{$data['customer']->note}}</textarea>
                            </div>
                        </div>
                        <div class="button-group mt-2">
                            <button type="submit" class="btn btn-blue mr-3">محفوظ کریں</button>
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
