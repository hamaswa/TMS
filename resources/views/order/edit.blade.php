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
                <form id="cc-form__addCustomerForm" method="POST" action="{{ url('admin/order/update/' . $data->id) }}"
                    class="add-customer-form mt-4">
                    @csrf
                    @method('put')
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <p class="text-right"><b>ناپ</b></p>
                            <div class="input-group">
                                <input class="form-control border-end-0 border rounded-pill search" type="text"
                                    placeholder="search" id="search" data-url="{{ route('admin.search') }}">
                                <span class="input-group-append">
                                    <button
                                        class="btn btn-outline-secondary bg-white border-start-0 border rounded-pill ms-n3"
                                        type="button">
                                        <i class="fa fa-search" id="SearchData"></i>
                                    </button>
                                </span>
                            </div>

                            <!-- select -->
                            <div class=" mt-3" id="select">
                                <select class='form-control' style="height: 50px" name='sub_id'>
                                    <option value='{{ $sub_customer->id }}'>{{ $sub_customer->name }}
                                        {{ $sub_customer->phone_number1 }}</option>
                                </select>
                            </div>
                            <!-- select -->
                        </div>
                    </div>

                    <h2 class="mb-4 mt-3 text-right">آرڈر میں تبدیلی</h2>

                    <!--{{-- <input type="hidden" name="user_id" value="{{$data['customer']->user_id}}"> --}}-->
                    @csrf
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="form-group form-row">
                                <label class="col-sm-3 col-form-label">کپڑوں کی تعداد</label>
                                <div class="col-sm-9">
                                    <input type="number" value="{{ $data->suitQuantity }}" class="form-control"
                                        name="suitQuantity" required>
                                </div>
                            </div>
                            <div class="form-row m-0">
                                <label class="col-sm-3 col-form-label">نام</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="CustomerName" readonly
                                        value="{{ $customer->name }}">
                                    <input type="hidden" class="form-control" name="customerId"
                                        value="{{ $customer->id }}">
                                </div>
                            </div>
                            <div class="form-group form-row">
                                <label class="col-sm-3 col-form-label">ٹوٹل قیمت</label>
                                <div class="col-sm-9">
                                    <input type="number" value="{{ $data->totalPayment }}" class="form-control"
                                        name="totalPayment" id="totalPayment" required>
                                </div>
                            </div>
                            <div class="form-group form-row">
                                <label class="col-sm-3 col-form-label">وصول رقم</label>
                                <div class="col-sm-9">
                                    <input type="number" value="{{ $recivedPayment }}" class="form-control"
                                        name="recivedPayment" id="recivedPayment" required>
                                </div>
                            </div>
                            <div class="form-group form-row">
                                <label class="col-sm-3 col-form-label">اس آرڈر کا بقایا</label>
                                <div class="col-sm-9">
                                    <input type="number" class="form-control" id="orderBalance" readonly
                                        value="{{ $orderBalance }}">
                                </div>
                            </div>
                            <div class="form-group form-row">
                                <label class="col-sm-3 col-form-label">گاہک کا مشترکہ بقایا</label>
                                <div class="col-sm-9">
                                    @if($customerBalance !== null)
                                        <input type="number" class="form-control" readonly value="{{ $customerBalance }}">
                                    @else
                                        <span class="form-control text-muted">بقایا دیکھنے کی اجازت نہیں</span>
                                    @endif
                                </div>
                            </div>

                            <div class="form-group form-row">
                                <label class="col-sm-3 col-form-label">درزی</label>
                                <div class="col-sm-9">
                                    <select id="tailor-selected" class="form-control" name="tailorId" required
                                        dir="rtl">
                                        <option value="0">درزی کو منتخب کریں</option>
                                        @foreach ($tailors as $tailor)
                                            <option value="{{ $tailor->id }}"
                                                {{ $data->tailorId == $tailor->id ? 'selected' : '' }}>{{ $tailor->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group form-row">
                                <label class="col-sm-3 col-form-label">درزی رقم</label>
                                <div class="col-sm-9" id="tailor-rates">
                                    <select class="form-control" name="tailor_price" required dir="rtl">
                                        <option value="">درزی کی رقم منتخب کریں</option>
                                        @foreach($tailorRates as $rate)
                                            <option value="{{ $rate->id }}-{{ $rate->price }}" @selected((int) $rate->id === (int) $data->rateId)>
                                                {{ $rate->price }} -- {{ $rate->options?->Name ?: ($rate->type ?: 'سلائی') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>


                            <!--<div class="form-group form-row">-->
                            <!--    <label class="col-sm-3 col-form-label">ڈیزائن</label>-->
                            <!--    <div class="col-sm-9">-->
                            <!--        <select id="design-selected" class="form-control" name="design" required-->
                            <!--            dir="rtl">-->
                            <!--            <option value="0">ڈیزائن کو منتخب کریں</option>-->
                            <!--            @foreach ($data['design'] as $design)-->
                            <!--                <option value="{{ $data->Name . ' - ' . $design->id }}"-->
                            <!--                    {{ $data->Name == $data->Name ? 'selected' : '' }}>{{ $design->Name }}-->
                            <!--                </option>-->
                            <!--            @endforeach-->
                            <!--        </select>-->
                            <!--    </div>-->
                            <!--</div>-->
                            <div class="form-group form-row">
                                <label class="col-sm-3 col-form-label">واپسی کی تاریخ</label>
                                <div class="col-sm-9">
                                    <input type="date" value="{{ $data->returnDate }}" class="form-control"
                                        name="returnDate" required>
                                </div>
                            </div>
                            <div class="form-group form-row">
                                <div class="col-md-3">
                                    <label style="font-size:23px; float:right">نوٹ</label>
                                </div>
                                <div class="col-md-9">
                                    <textarea rows="4" cols="" class="form-control" name="remarks" dir="rtl">{{ $data->remarks }}</textarea>
                                </div>
                            </div>
                            @if($data->measurementValues->isNotEmpty())
                                <div class="card bg-light mb-3" dir="rtl"><div class="card-body">
                                    <h5>آرڈر کے وقت محفوظ پیمائش</h5>
                                    <div class="row">@foreach($data->measurementValues as $measurement)
                                        <div class="col-md-4 mb-2"><span class="text-muted">{{ $measurement->label }}:</span> <strong>{{ $measurement->value }}</strong> @if($measurement->unit)<small>{{ $measurement->unit === 'inch' ? 'انچ' : 'سینٹی میٹر' }}</small>@endif</div>
                                    @endforeach</div>
                                    <small class="text-muted">یہ پیمائش اس آرڈر کی تاریخ کے لیے محفوظ ہے؛ گاہک کی نئی پیمائش اسے تبدیل نہیں کرے گی۔</small>
                                </div></div>
                            @endif
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
            const total = document.getElementById('totalPayment');
            const received = document.getElementById('recivedPayment');
            const orderBalance = document.getElementById('orderBalance');
            const tailor = document.getElementById('tailor-selected');
            const rates = document.getElementById('tailor-rates');

            function updateOrderBalance() {
                const totalValue = parseFloat(total?.value || '0');
                const receivedValue = parseFloat(received?.value || '0');
                if (orderBalance) orderBalance.value = Math.max(0, totalValue - receivedValue).toFixed(2);
            }

            total?.addEventListener('input', updateOrderBalance);
            received?.addEventListener('input', updateOrderBalance);

            tailor?.addEventListener('change', async function () {
                if (!this.value || this.value === '0') {
                    rates.innerHTML = '<span class="form-control text-muted">پہلے درزی منتخب کریں</span>';
                    return;
                }

                const url = @json(route('admin.tailor.salary', ['user_id' => '__TAILOR__'])).replace('__TAILOR__', this.value);
                try {
                    const response = await fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}});
                    if (!response.ok) throw new Error('rate-request-failed');
                    rates.innerHTML = await response.text();
                } catch (error) {
                    rates.innerHTML = '<span class="form-control text-danger">درزی کی رقم لوڈ نہیں ہو سکی۔</span>';
                }
            });
        });
    </script>
@endsection
