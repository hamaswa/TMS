@extends('main')

@section('content')
    <style>
        .stock-sale-card {
            max-width: 980px;
        }

        .stock-sale-item {
            border: 1px solid #e3eaf2;
            border-radius: 14px;
            padding: 1rem;
            margin-bottom: 1rem;
            background: #f8fafc;
        }

        .stock-sale-summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        .stock-sale-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .75rem;
        }

        @media (max-width: 767.98px) {
            .stock-sale-card {
                border-radius: 0;
            }

            .stock-sale-summary {
                grid-template-columns: 1fr;
            }

            .stock-sale-actions .btn {
                width: 100%;
            }
        }
    </style>

    <section class="main-content">
        <div class="container px-2 px-md-3" id="formContainer">
            <div class="card stock-sale-card mx-auto">
                <div class="card-body px-3 px-md-4">
                    @include('inc.message')

                    @if ($errors->any())
                        <div class="alert alert-danger text-right" role="alert">
                            <strong>فروخت محفوظ نہیں ہو سکی:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <h1 class="h3 mb-4 text-right">اسٹاک فروخت کریں</h1>

                    <form
                        action="{{ route('admin.sellStock') }}"
                        method="post"
                        id="sellStockForm"
                        data-customer-url="{{ url('/admin/getNmbr') }}"
                        data-types-url="{{ url('/admin/getType') }}"
                    >
                        @csrf

                        <fieldset class="mb-4">
                            <legend class="h5 text-right">گاہک کی معلومات</legend>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="c_name">کسٹمر کا نام</label>
                                    <select name="c_name" required class="form-control custom-select" id="c_name">
                                        <option value="" disabled selected>گاہک منتخب کریں</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->name . '|' . $customer->id }}" @selected(old('c_name') === $customer->name . '|' . $customer->id)>
                                                {{ $customer->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('c_name')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="nmbr">موبائل نمبر</label>
                                    <input
                                        type="tel"
                                        inputmode="tel"
                                        class="form-control"
                                        name="phone"
                                        id="nmbr"
                                        value="{{ old('phone') }}"
                                        autocomplete="tel"
                                        required
                                    >
                                    @error('phone')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend class="h5 text-right">فروخت کی اشیاء</legend>
                            <div id="stockDataContainer" aria-live="polite">
                                <div class="stock-data stock-sale-item">
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>
                                                <span class="d-block mb-2">برانڈ کا نام</span>
                                                <select class="form-control js-brand" name="brand_name[]" required>
                                                    <option value="" disabled selected>برانڈ منتخب کریں</option>
                                                    @foreach ($cloths->unique('cloth_brand_id') as $cloth)
                                                        <option value="{{ $cloth->cloth_brand_id }}" data-cloth-id="{{ $cloth->id }}">
                                                            {{ $cloth->brand->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </label>
                                        </div>

                                        <div class="form-group col-md-6">
                                            <label>
                                                <span class="d-block mb-2">کپڑے کی قسم</span>
                                                <select class="form-control js-cloth-type" name="cloth_type[]" required>
                                                    <option value="" disabled selected>پہلے برانڈ منتخب کریں</option>
                                                </select>
                                            </label>
                                        </div>

                                        <div class="form-group col-md-6">
                                            <label>
                                                <span class="d-block mb-2">کپڑے کا رنگ</span>
                                                <select class="form-control" name="color[]" required>
                                                    <option value="" disabled selected>کپڑے کا رنگ منتخب کریں</option>
                                                    @foreach ($cloths as $cloth)
                                                        @foreach ($cloth->colors as $color)
                                                            <option value="{{ $color->color }}">{{ $color->color }}</option>
                                                        @endforeach
                                                    @endforeach
                                                </select>
                                            </label>
                                        </div>

                                        <div class="form-group col-md-6">
                                            <label>
                                                <span class="d-block mb-2">ریٹ فی میٹر</span>
                                                <input type="number" class="form-control" name="per_meter[]" min="0" step="0.01" required>
                                            </label>
                                        </div>

                                        <div class="form-group col-md-6">
                                            <label>
                                                <span class="d-block mb-2">فیبرک رول / ریک</span>
                                                <input type="number" class="form-control" name="clothes_rack[]" min="0" step="1" required>
                                            </label>
                                        </div>

                                        <div class="form-group col-md-6">
                                            <label>
                                                <span class="d-block mb-2">گز / لمبائی</span>
                                                <input type="number" class="form-control" name="length[]" min="0.01" step="0.01" required>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        <button type="button" class="btn btn-outline-secondary mb-4" id="addMoreBtn">
                            <i class="fas fa-plus ml-1" aria-hidden="true"></i> مزید کپڑا شامل کریں
                        </button>

                        <div class="stock-sale-summary mb-4">
                            <div class="form-group mb-0">
                                <label for="total">کل رقم</label>
                                <input type="number" class="form-control" name="total" id="total" step="0.01" readonly>
                            </div>
                            <div class="form-group mb-0">
                                <label for="payment">رقم موصول</label>
                                <input type="number" class="form-control" name="payment" id="payment" min="0" step="0.01" required>
                            </div>
                            <div class="form-group mb-0">
                                <label for="remain">ادائیگی باقی</label>
                                <input type="number" class="form-control" name="remain" id="remain" step="0.01" readonly>
                            </div>
                        </div>

                        <div class="stock-sale-actions">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-check ml-1" aria-hidden="true"></i> فروخت محفوظ کریں
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('sellStockForm');
            const container = document.getElementById('stockDataContainer');
            const addButton = document.getElementById('addMoreBtn');
            const customer = document.getElementById('c_name');
            const phone = document.getElementById('nmbr');
            const total = document.getElementById('total');
            const payment = document.getElementById('payment');
            const remaining = document.getElementById('remain');

            const calculateTotals = function () {
                let saleTotal = 0;

                container.querySelectorAll('.stock-data').forEach(function (item) {
                    const rate = Number.parseFloat(item.querySelector('[name="per_meter[]"]').value) || 0;
                    const length = Number.parseFloat(item.querySelector('[name="length[]"]').value) || 0;
                    saleTotal += rate * length;
                });

                const received = Number.parseFloat(payment.value) || 0;
                total.value = saleTotal.toFixed(2);
                remaining.value = Math.max(0, saleTotal - received).toFixed(2);
            };

            const loadClothTypes = async function (item) {
                const brand = item.querySelector('.js-brand');
                const clothType = item.querySelector('.js-cloth-type');
                clothType.innerHTML = '<option value="" disabled selected>لوڈ ہو رہا ہے…</option>';

                try {
                    const response = await fetch(form.dataset.typesUrl + '?id=' + encodeURIComponent(brand.value), {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (!response.ok) {
                        throw new Error('Unable to load cloth types');
                    }

                    const payload = await response.json();
                    clothType.innerHTML = '<option value="" disabled selected>کپڑے کی قسم منتخب کریں</option>';
                    (payload.data || []).forEach(function (entry) {
                        const option = document.createElement('option');
                        option.value = entry.cloth_type_id;
                        option.textContent = entry.type ? entry.type.name : '';
                        clothType.appendChild(option);
                    });
                } catch (error) {
                    clothType.innerHTML = '<option value="" disabled selected>اقسام لوڈ نہیں ہو سکیں</option>';
                }
            };

            const bindItem = function (item) {
                item.querySelector('.js-brand').addEventListener('change', function () {
                    loadClothTypes(item);
                });
                item.querySelectorAll('[name="per_meter[]"], [name="length[]"]').forEach(function (input) {
                    input.addEventListener('input', calculateTotals);
                });
            };

            bindItem(container.querySelector('.stock-data'));

            customer.addEventListener('change', async function () {
                const parts = customer.value.split('|');
                const customerId = parts[1];
                if (!customerId) {
                    return;
                }

                try {
                    const response = await fetch(form.dataset.customerUrl + '?id=' + encodeURIComponent(customerId), {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (!response.ok) {
                        throw new Error('Unable to load customer');
                    }
                    const payload = await response.json();
                    phone.value = payload.data && payload.data.phone_number1 ? payload.data.phone_number1 : '';
                } catch (error) {
                    phone.value = '';
                }
            });

            addButton.addEventListener('click', function () {
                const item = container.querySelector('.stock-data').cloneNode(true);
                item.querySelectorAll('input').forEach(function (input) {
                    input.value = '';
                });
                item.querySelectorAll('select').forEach(function (select) {
                    select.selectedIndex = 0;
                });
                item.querySelector('.js-cloth-type').innerHTML =
                    '<option value="" disabled selected>پہلے برانڈ منتخب کریں</option>';

                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'btn btn-outline-danger btn-sm remove-section';
                removeButton.innerHTML = '<i class="fas fa-trash ml-1" aria-hidden="true"></i> یہ سطر ہٹائیں';
                removeButton.addEventListener('click', function () {
                    item.remove();
                    calculateTotals();
                });
                item.appendChild(removeButton);

                container.appendChild(item);
                bindItem(item);
                item.querySelector('.js-brand').focus();
            });

            payment.addEventListener('input', calculateTotals);
            calculateTotals();
        });
    </script>
@endsection
