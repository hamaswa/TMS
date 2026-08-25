@extends('main')
@section('content')
    <style>
        .order-edit-page-card {
            border: 0;
            background: transparent;
            box-shadow: none;
        }

        .order-customer-picker,
        .order-basics-card {
            direction: rtl;
            border: 1px solid #dce6f1;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 8px 24px rgba(31, 45, 61, .06);
        }

        .order-customer-picker {
            margin-bottom: 1.25rem;
            padding: 1.15rem 1.25rem;
        }

        .order-customer-picker__head {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin-bottom: .9rem;
        }

        .order-basics-card {
            margin-bottom: 1.25rem;
            padding: 1.35rem;
        }

        .order-basics-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.25rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e8eef5;
        }

        .order-basics-title {
            display: flex;
            align-items: center;
            gap: .85rem;
        }

        .order-basics-icon {
            display: grid;
            place-items: center;
            flex: 0 0 48px;
            width: 48px;
            height: 48px;
            border-radius: 14px;
            color: #fff;
            background: linear-gradient(135deg, #267bf1, #0d5bd2);
            box-shadow: 0 8px 18px rgba(23, 105, 224, .2);
        }

        .order-basics-title h2 {
            margin: 0 0 .2rem;
            color: #102a43;
            font-size: 1.45rem;
            font-weight: 900;
        }

        .order-basics-title p {
            margin: 0;
            color: #718096;
            font-size: .86rem;
        }

        .order-number-badge {
            padding: .5rem .8rem;
            border-radius: 10px;
            color: #1769aa;
            background: #e8f3fc;
            font-weight: 800;
            white-space: nowrap;
        }

        .order-basics-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        .order-field {
            min-width: 0;
            margin: 0;
        }

        .order-field label {
            display: flex;
            align-items: center;
            gap: .45rem;
            margin-bottom: .5rem;
            color: #344762;
            font-weight: 800;
        }

        .order-field label i {
            width: 20px;
            color: #1769aa;
            text-align: center;
        }

        .order-field .form-control {
            min-height: 50px;
            border-color: #d7e1ed;
            border-radius: 10px;
            background-color: #fbfdff;
        }

        .order-field .form-control:focus {
            border-color: #79abf4;
            background: #fff;
            box-shadow: 0 0 0 .15rem rgba(23, 105, 224, .1);
        }

        .order-field .form-control[readonly],
        .order-field-readonly {
            color: #36516f;
            background: #f1f5f9 !important;
            font-weight: 700;
        }

        .order-field.is-money .form-control {
            direction: ltr;
            text-align: left;
            font-family: Arial, sans-serif;
            font-weight: 800;
        }

        .order-field.is-balance .form-control {
            color: #bd2938;
            border-color: #f2c8ce;
            background: #fff7f8 !important;
        }

        .order-field.is-customer-balance .form-control {
            color: #865700;
            border-color: #f0d79c;
            background: #fffaf0 !important;
        }

        .order-field-full { grid-column: 1 / -1; }

        .order-field textarea.form-control {
            min-height: 115px;
            resize: vertical;
        }

        .order-measurement-layout {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1.25rem;
            align-items: start;
            direction: rtl;
        }

        .order-measurement-panel {
            min-width: 0;
            padding: 1.25rem;
            border: 1px solid #dce6f1;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 7px 20px rgba(31, 45, 61, .05);
        }

        .order-measurement-panel-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.15rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e8eef5;
        }

        .order-panel-title {
            display: flex;
            align-items: flex-start;
            gap: .75rem;
        }

        .order-panel-icon {
            display: grid;
            place-items: center;
            flex: 0 0 42px;
            width: 42px;
            height: 42px;
            border-radius: 12px;
            color: #1769aa;
            background: #e8f3fc;
        }

        .order-measurement-panel h5 {
            margin: 0 0 .25rem;
            font-weight: 900;
        }

        .order-measurement-panel-head p {
            margin: 0;
            color: #718096;
            font-size: .84rem;
        }

        .order-measurement-grid,
        .order-preference-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .9rem;
        }

        .order-measurement-grid .form-group,
        .order-preference-grid .form-group {
            min-width: 0;
            margin-bottom: 0;
        }

        .order-measurement-grid label,
        .order-preference-grid label {
            display: block;
            margin-bottom: .45rem;
            color: #243b53;
            font-weight: 800;
        }

        .order-preference-grid select.form-control {
            width: 100%;
            height: 54px;
            min-height: 54px;
            padding: 0 14px 7px 38px !important;
            border-radius: 10px;
            border-color: #d7e1ed;
            direction: rtl;
            text-align: right;
            font-size: 1rem;
            line-height: 2.2;
            background-position: left .75rem center;
        }

        .order-preference-grid select.form-control:focus {
            border-color: #1769aa;
            box-shadow: 0 0 0 .15rem rgba(23, 105, 170, .12);
        }

        @media (max-width: 1199px) {
            .order-preference-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 991px) {
            .order-basics-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .order-measurement-layout { grid-template-columns: 1fr; }
            .order-preference-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 575px) {
            .order-basics-grid { grid-template-columns: 1fr; }
            .order-basics-head { align-items: flex-start; }
            .order-measurement-grid,
            .order-preference-grid { grid-template-columns: 1fr; }
        }
    </style>
    <section class="main-content">
        <div class="container-fluid px-3 px-lg-5">
            <div class="card order-edit-page-card">
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
                    <input type="hidden" name="return_customer" value="{{ old('return_customer', request('return_customer')) }}">
                    <input type="hidden" name="return_search" value="{{ old('return_search', request('return_search')) }}">
                    <div class="row justify-content-center">
                        <div class="col-12">
                            <div class="order-customer-picker">
                            <div class="order-customer-picker__head">
                                <span class="order-panel-icon"><i class="fas fa-user-tag"></i></span>
                                <div><strong>محفوظ ناپ منتخب کریں</strong><small class="d-block text-muted">گاہک یا اس کے ذیلی ناپ کو تلاش کرکے منتخب کریں۔</small></div>
                            </div>
                            <div class="input-group">
                                <input class="form-control border-end-0 border rounded-pill search" type="text"
                                    placeholder="نام یا فون نمبر سے ناپ تلاش کریں" id="search" data-url="{{ route('admin.search') }}">
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
                            </div>
                            <!-- select -->
                        </div>
                    </div>

                    <!--{{-- <input type="hidden" name="user_id" value="{{$data['customer']->user_id}}"> --}}-->
                    @csrf
                    <div class="row justify-content-center">
                        <div class="col-12">
                            <section class="order-basics-card">
                                <div class="order-basics-head">
                                    <div class="order-basics-title">
                                        <span class="order-basics-icon"><i class="fas fa-edit"></i></span>
                                        <div><h2>آرڈر میں تبدیلی</h2><p>آرڈر، ادائیگی، درزی اور واپسی کی معلومات درست کریں۔</p></div>
                                    </div>
                                    <span class="order-number-badge">آرڈر #{{ $data->id }}</span>
                                </div>
                                <div class="order-basics-grid">
                                    <div class="order-field">
                                        <label for="order-suit-quantity"><i class="fas fa-tshirt"></i> کپڑوں کی تعداد</label>
                                        <input id="order-suit-quantity" type="number" min="1" value="{{ $data->suitQuantity }}" class="form-control" name="suitQuantity" required>
                                    </div>
                                    <div class="order-field">
                                        <label for="order-customer-name"><i class="fas fa-user"></i> گاہک کا نام</label>
                                        <input id="order-customer-name" type="text" class="form-control" name="CustomerName" readonly value="{{ $customer->name }}">
                                        <input type="hidden" name="customerId" value="{{ $customer->id }}">
                                    </div>
                                    <div class="order-field is-money">
                                        <label for="totalPayment"><i class="fas fa-money-bill-wave"></i> ٹوٹل قیمت</label>
                                        <input type="number" min="0" step="0.01" value="{{ $data->totalPayment }}" class="form-control" name="totalPayment" id="totalPayment" required>
                                    </div>
                                    <div class="order-field is-money">
                                        <label for="recivedPayment"><i class="fas fa-wallet"></i> وصول رقم</label>
                                        <input type="number" min="0" step="0.01" value="{{ $recivedPayment }}" class="form-control" name="recivedPayment" id="recivedPayment" required>
                                    </div>
                                    <div class="order-field is-money is-balance">
                                        <label for="orderBalance"><i class="fas fa-file-invoice-dollar"></i> اس آرڈر کا بقایا</label>
                                        <input type="number" class="form-control" id="orderBalance" readonly value="{{ $orderBalance }}">
                                    </div>
                                    <div class="order-field is-money is-customer-balance">
                                        <label><i class="fas fa-address-book"></i> گاہک کا مشترکہ بقایا</label>
                                        @if($customerBalance !== null)
                                            <input type="number" class="form-control" readonly value="{{ $customerBalance }}">
                                        @else
                                            <span class="form-control order-field-readonly text-muted">بقایا دیکھنے کی اجازت نہیں</span>
                                        @endif
                                    </div>
                                    <div class="order-field">
                                        <label for="tailor-selected"><i class="fas fa-user-tie"></i> درزی</label>
                                        <select id="tailor-selected" class="form-control" name="tailorId" required dir="rtl">
                                            <option value="0">درزی کو منتخب کریں</option>
                                            @foreach ($tailors as $tailor)
                                                <option value="{{ $tailor->id }}" @selected((int) $data->tailorId === (int) $tailor->id)>{{ $tailor->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="order-field">
                                        <label for="order-tailor-price"><i class="fas fa-tags"></i> درزی رقم</label>
                                        <div id="tailor-rates">
                                            <select id="order-tailor-price" class="form-control" name="tailor_price" required dir="rtl">
                                                <option value="">درزی کی رقم منتخب کریں</option>
                                                @foreach($tailorRates as $rate)
                                                    <option value="{{ $rate->id }}-{{ $rate->price }}" @selected((int) $rate->id === (int) $data->rateId)>{{ $rate->price }} -- {{ $rate->options?->Name ?: ($rate->type ?: 'سلائی') }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="order-field">
                                        <label for="order-return-date"><i class="fas fa-calendar-check"></i> واپسی کی تاریخ</label>
                                        <input id="order-return-date" type="date" value="{{ $data->returnDate }}" class="form-control" name="returnDate" required>
                                    </div>
                                    <div class="order-field order-field-full">
                                        <label for="order_remarks"><i class="fas fa-sticky-note"></i> نوٹ</label>
                                        <textarea id="order_remarks" class="form-control" name="remarks" dir="auto" placeholder="آرڈر سے متعلق ضروری ہدایات درج کریں">{{ $data->remarks }}</textarea>
                                    </div>
                                </div>
                            </section>
                            @php
                                $preferenceKeys = ['necktype', 'sleeve', 'Daaman', 'jeab', 'swingtype', 'button', 'plate_type'];
                                $measurementKeys = collect(array_keys(\App\Services\MeasurementService::SYSTEM_FIELDS))
                                    ->reject(fn($key) => in_array($key, $preferenceKeys, true));
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
                                    <div class="order-measurement-layout">
                                        <section class="order-measurement-panel">
                                            <div class="order-measurement-panel-head">
                                                <div class="order-panel-title">
                                                    <span class="order-panel-icon"><i class="fas fa-ruler-combined"></i></span>
                                                    <div><h5>لباس کی پیمائش</h5><p>دائیں جانب ناپ میں مطلوبہ تبدیلی کریں۔</p></div>
                                                </div>
                                            </div>
                                            <div class="order-measurement-grid">
                                                @foreach($measurementKeys as $key)
                                                    @php
                                                        $meta = \App\Services\MeasurementService::SYSTEM_FIELDS[$key];
                                                        $savedMeasurement = $savedMeasurementValues->get('system.'.$key);
                                                        $value = old('system_measurements.'.$key, $savedMeasurement?->value ?? data_get($measurementCustomer, $key));
                                                    @endphp
                                                    <div class="form-group">
                                                        <label for="order-system-measurement-{{ $key }}">
                                                            {{ $meta['label'] }}
                                                            @if(!$savedMeasurement)<span class="badge badge-info mr-1">نیا خانہ</span>@endif
                                                            <small class="text-muted">(انچ)</small>
                                                        </label>
                                                        <input id="order-system-measurement-{{ $key }}" class="form-control" name="system_measurements[{{ $key }}]" value="{{ $value }}" type="number" step="0.01" min="0">
                                                    </div>
                                                @endforeach

                                                @foreach($measurementFields as $field)
                                                    @php
                                                        $savedMeasurement = $savedMeasurementValues->get('custom.'.$field->id);
                                                        $value = old('custom_measurements.'.$field->id, $savedMeasurement?->value ?? $customerCustomValues->get($field->id));
                                                    @endphp
                                                    <div class="form-group">
                                                        <label for="order-custom-measurement-{{ $field->id }}">
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
                                        </section>

                                        <section class="order-measurement-panel">
                                            <div class="order-measurement-panel-head">
                                                <div class="order-panel-title">
                                                    <span class="order-panel-icon"><i class="fas fa-cut"></i></span>
                                                    <div><h5>سلائی کی پسند</h5><p>بائیں جانب محفوظ پسند تبدیل کریں۔</p></div>
                                                </div>
                                            </div>
                                            <div class="order-preference-grid">
                                                @foreach($preferenceKeys as $key)
                                                    @php
                                                        $meta = \App\Services\MeasurementService::SYSTEM_FIELDS[$key];
                                                        $savedMeasurement = $savedMeasurementValues->get('system.'.$key);
                                                        $value = trim((string) old('system_measurements.'.$key, $savedMeasurement?->value ?? data_get($measurementCustomer, $key)));
                                                        $options = $preferenceOptions->get($key, collect());
                                                        $hasSavedOption = $options->contains(fn($option) => trim((string) $option->Name) === $value);
                                                    @endphp
                                                    <div class="form-group">
                                                        <label for="order-system-preference-{{ $key }}">
                                                            {{ $meta['label'] }}
                                                            @if(!$savedMeasurement)<span class="badge badge-info mr-1">نیا خانہ</span>@endif
                                                        </label>
                                                        <select id="order-system-preference-{{ $key }}" class="form-control" name="system_measurements[{{ $key }}]">
                                                            <option value="">{{ $meta['label'] }} منتخب کریں</option>
                                                            @if($value !== '' && !$hasSavedOption)
                                                                <option value="{{ $value }}" selected>{{ $value }}</option>
                                                            @endif
                                                            @foreach($options as $option)
                                                                @php($optionName = trim((string) $option->Name))
                                                                <option value="{{ $optionName }}" @selected($optionName === $value)>{{ $optionName }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </section>
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
