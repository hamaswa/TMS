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
                                    <option value='{{ $measurementCustomer->id }}'>{{ $measurementCustomer->name }}
                                        {{ $measurementCustomer->phone_number1 }}</option>
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
                                    <label for="order_remarks" style="font-size:23px; float:right">نوٹ</label>
                                </div>
                                <div class="col-md-9">
                                    <textarea id="order_remarks" rows="4" cols="" class="form-control" name="remarks" dir="auto">{{ $data->remarks }}</textarea>
                                </div>
                            </div>
                            @php
                                $editableSourceKeys = collect(array_keys(\App\Services\MeasurementService::SYSTEM_FIELDS))
                                    ->map(fn($key) => 'system.'.$key)
                                    ->merge($measurementFields->map(fn($field) => 'custom.'.$field->id));
                                $archivedMeasurements = $data->measurementValues
                                    ->reject(fn($measurement) => $editableSourceKeys->contains($measurement->source_key));
                            @endphp
                            <div class="card bg-light mb-3" dir="rtl">
                                <div class="card-body">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                                        <div>
                                            <h5 class="mb-1">اس آرڈر کی پیمائش میں تبدیلی</h5>
                                            <small class="text-muted">نئے پیمائش خانے بھی یہاں شامل ہیں۔ تبدیلی صرف اسی آرڈر اور اس کی رسید پر لاگو ہوگی۔</small>
                                        </div>
                                        <span class="badge badge-primary mt-2 mt-md-0">{{ count(\App\Services\MeasurementService::SYSTEM_FIELDS) + $measurementFields->count() }} خانے</span>
                                    </div>
                                    <div class="row">
                                        @foreach(\App\Services\MeasurementService::SYSTEM_FIELDS as $key => $meta)
                                            @php
                                                $savedMeasurement = $savedMeasurementValues->get('system.'.$key);
                                                $value = old('system_measurements.'.$key, $savedMeasurement?->value ?? data_get($measurementCustomer, $key));
                                            @endphp
                                            <div class="col-sm-6 col-lg-4 form-group">
                                                <label for="order-system-measurement-{{ $key }}" class="font-weight-bold">
                                                    {{ $meta['label'] }}
                                                    @if(!$savedMeasurement)<span class="badge badge-info mr-1">نیا خانہ</span>@endif
                                                    @if($meta['unit'] === 'inch')<small class="text-muted">(انچ)</small>@endif
                                                </label>
                                                <input id="order-system-measurement-{{ $key }}" class="form-control" name="system_measurements[{{ $key }}]" value="{{ $value }}" type="{{ $meta['unit'] === 'inch' ? 'number' : 'text' }}" @if($meta['unit'] === 'inch') step="0.01" min="0" @endif>
                                            </div>
                                        @endforeach

                                        @foreach($measurementFields as $field)
                                            @php
                                                $savedMeasurement = $savedMeasurementValues->get('custom.'.$field->id);
                                                $value = old('custom_measurements.'.$field->id, $savedMeasurement?->value ?? $customerCustomValues->get($field->id));
                                            @endphp
                                            <div class="col-sm-6 col-lg-4 form-group">
                                                <label for="order-custom-measurement-{{ $field->id }}" class="font-weight-bold">
                                                    {{ $field->label }}
                                                    @if(!$savedMeasurement)<span class="badge badge-info mr-1">نیا خانہ</span>@endif
                                                    @if($field->unit && $field->unit !== 'none')<small class="text-muted">({{ $field->unit === 'inch' ? 'انچ' : 'سینٹی میٹر' }})</small>@endif
                                                </label>
                                                @if($field->field_type === 'select')
                                                    <select id="order-custom-measurement-{{ $field->id }}" class="form-control" name="custom_measurements[{{ $field->id }}]">
                                                        <option value="">منتخب کریں</option>
                                                        @foreach($field->options ?? [] as $option)<option value="{{ $option }}" @selected((string)$value === (string)$option)>{{ $option }}</option>@endforeach
                                                    </select>
                                                @else
                                                    <input id="order-custom-measurement-{{ $field->id }}" class="form-control" name="custom_measurements[{{ $field->id }}]" value="{{ $value }}" type="{{ $field->field_type === 'number' ? 'number' : 'text' }}" @if($field->field_type === 'number') step="0.01" min="0" @endif>
                                                @endif
                                                @error('custom_measurements.'.$field->id)<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                            </div>
                                        @endforeach
                                    </div>

                                    @if($archivedMeasurements->isNotEmpty())
                                        <div class="border-top pt-3 mt-1">
                                            <h6 class="font-weight-bold">پرانے غیر فعال خانے</h6>
                                            <div class="row">@foreach($archivedMeasurements as $measurement)
                                                <div class="col-md-4 mb-2"><span class="text-muted">{{ $measurement->label }}:</span> <strong>{{ $measurement->value }}</strong></div>
                                            @endforeach</div>
                                        </div>
                                    @endif
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
