@extends('main')

@push('styles')
    @include('purchases._styles')
    <style>
        .purchase-create-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 290px;
            gap: 18px
        }

        .purchase-order-summary {
            position: sticky;
            top: 88px;
            height: max-content;
            padding: 20px
        }

        .purchase-order-summary .total {
            direction: ltr;
            color: #1769ef;
            font: 800 1.5rem Arial, sans-serif
        }

        .purchase-supplier-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px
        }

        .purchase-supplier-label a {
            font-size: .75rem
        }

        .purchase-sheet-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px
        }

        .purchase-sheet-toolbar p {
            margin: 0;
            color: #718096;
            font-size: .82rem
        }

        .purchase-sheet-toolbar .btn {
            white-space: nowrap;
            font-weight: 800
        }

        .purchase-sheet-scroll {
            border: 1px solid #dfe7f2;
            border-radius: 11px;
            background: #f7f9fc;
            overflow: hidden
        }

        .purchase-sheet-head {
            display: none
        }

        .purchase-sheet-item {
            margin: 12px;
            border: 1px solid #dce5f0;
            border-radius: 10px;
            background: #fff;
            overflow: hidden
        }

        .purchase-item-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 9px 12px;
            border-bottom: 1px solid #e5ebf3;
            background: #f7f9fc
        }

        .purchase-item-title {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #34445c;
            font-weight: 900
        }

        .purchase-row-number {
            display: grid;
            place-items: center;
            width: 26px;
            height: 26px;
            border-radius: 7px;
            background: #e7f0ff;
            color: #1769ef;
            font: 800 .8rem Arial, sans-serif
        }

        .purchase-item-actions {
            display: flex;
            align-items: center;
            gap: 8px
        }

        .purchase-item-actions .purchase-row-kind {
            width: 150px
        }

        .purchase-item-actions .purchase-remove {
            display: grid;
            place-items: center;
            width: 46px;
            height: 46px;
            border-radius: 7px
        }

        .purchase-sheet-row {
            display: grid;
            grid-template-columns: repeat(4, minmax(145px, 1fr));
            gap: 12px;
            padding: 14px
        }

        .purchase-field {
            min-width: 0
        }

        .purchase-field.span-2 {
            grid-column: span 2
        }

        .purchase-sheet-row label {
            display: flex;
            align-items: flex-end;
            min-height: 32px;
            margin: 0 0 5px;
            color: #52627b;
            font-size: .74rem;
            font-weight: 900
        }

        .purchase-sheet-row .form-control,
        .purchase-sheet-row .purchase-control,
        .purchase-item-actions .form-control {
            height: 46px !important;
            min-height: 46px !important;
            padding: .48rem .65rem
        }

        .purchase-existing-meta {
            display: block;
            margin-top: 5px;
            color: #718096;
            font-size: .7rem
        }

        .purchase-stock-cell .btn {
            width: 100%;
            height: 46px;
            min-height: 46px
        }

        .purchase-not-applicable {
            display: flex;
            align-items: center;
            height: 46px;
            padding: 0 10px;
            border: 1px dashed #d7dee9;
            border-radius: 7px;
            background: #fafbfd;
            color: #99a3b2
        }

        .purchase-row-details {
            display: none;
            margin: 0 14px 12px;
            padding: 12px;
            border: 1px solid #cfe0f4;
            border-radius: 9px;
            background: #f8fbff
        }

        .purchase-row-details.is-open {
            display: block
        }

        .purchase-color-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 9px
        }

        .purchase-color-head strong {
            color: #52627b
        }

        .purchase-color-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(330px, 1fr));
            gap: 8px
        }

        .purchase-existing-color-grid {
            grid-template-columns: repeat(3, minmax(170px, 1fr))
        }

        .purchase-color-entry {
            display: grid;
            grid-template-columns: minmax(95px, 1fr) 46px 82px minmax(155px, 1.2fr) 34px;
            gap: 6px;
            align-items: end;
            padding: 8px;
            border: 1px solid #e0e8f2;
            border-radius: 8px;
            background: #fff
        }

        .purchase-color-entry.existing {
            grid-template-columns: minmax(110px, 1fr) 95px
        }

        .purchase-color-entry label {
            display: block;
            margin-bottom: 3px;
            color: #52627b;
            font-size: .7rem;
            font-weight: 800
        }

        .purchase-color-entry small {
            display: block;
            color: #8793a5;
            font-weight: 500
        }

        .purchase-color-entry input,
        .purchase-color-entry button {
            height: 42px !important;
            min-height: 42px !important
        }

        .purchase-color-entry input[type=color] {
            padding: 4px
        }

        .purchase-color-photo {
            min-width: 0
        }

        .purchase-image-picker {
            display: flex !important;
            align-items: center !important;
            gap: 7px !important;
            height: 42px;
            min-height: 42px !important;
            margin: 0 !important;
            padding: 3px 8px !important;
            border: 1px solid #d8e1ed;
            border-radius: 7px;
            background: #fff;
            color: #52627b !important;
            cursor: pointer;
            overflow: hidden
        }

        .purchase-image-picker input {
            position: absolute;
            width: 1px !important;
            height: 1px !important;
            min-height: 1px !important;
            opacity: 0;
            pointer-events: none
        }

        .purchase-image-name {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: .68rem
        }

        .purchase-color-preview {
            display: none;
            flex: 0 0 34px;
            width: 34px;
            height: 34px;
            border: 1px solid #ccd7e5;
            border-radius: 6px;
            background: #fff;
            object-fit: cover
        }

        .purchase-color-preview.is-visible {
            display: block
        }

        .purchase-color-delete {
            display: grid;
            place-items: center;
            padding: 0;
            border-radius: 7px
        }

        .purchase-row-summary {
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
            padding: 0 14px 10px;
            color: #52627b;
            font-size: .76rem
        }

        .purchase-row-summary strong {
            color: #17243d
        }

        .purchase-scan-tools {
            margin-top: 12px;
            border: 1px solid #d8e5f4;
            border-radius: 9px;
            background: #f8fbff
        }

        .purchase-scan-tools>summary {
            padding: 10px 12px;
            color: #315f92;
            font-weight: 800;
            cursor: pointer
        }

        .purchase-scan-box {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 9px;
            align-items: end;
            padding: 0 12px 12px
        }

        .purchase-scan-feedback {
            grid-column: 1/-1;
            min-height: 20px;
            margin: 0;
            font-weight: 700
        }

        .purchase-scan-feedback.is-error {
            color: #c0392b
        }

        .purchase-scan-feedback.is-warning {
            color: #a56a00
        }

        .purchase-scan-feedback.is-success {
            color: #138a53
        }

        .purchase-payment-box {
            margin: 16px 0;
            padding: 14px;
            border: 1px solid #e0e9f3;
            border-radius: 12px;
            background: #f9fbfe;
        }

        .purchase-payment-title {
            margin-bottom: 12px;
            color: #102a52;
            font-size: 1rem;
            font-weight: 800;
        }

        .purchase-payment-box .form-group {
            margin-bottom: 10px;
        }

        .purchase-choice {
            display: grid;
            gap: 5px;
            margin-top: 13px;
            padding-top: 13px;
            border-top: 1px solid #e2e9f3
        }

        .purchase-choice button {
            width: 100%
        }

        .purchase-choice small {
            color: #718096
        }

        .purchase-secondary-btn {
            min-height: 43px;
            border: 1px solid #b9c9dc;
            border-radius: 8px;
            background: #fff;
            color: #38536f;
            font-weight: 800
        }

        .purchase-optional {
            margin-top: 12px;
            border-top: 1px solid #e2e9f3
        }

        .purchase-optional summary {
            padding-top: 12px;
            color: #52627b;
            font-weight: 800;
            cursor: pointer
        }

        .purchase-optional textarea {
            margin-top: 9px
        }

        .purchase-sheet-row .form-control,
        .purchase-sheet-row .purchase-control,
        .purchase-sheet-row .purchase-stock-cell .btn,
        .purchase-sheet-row .purchase-not-applicable,
        .purchase-item-actions .form-control {
            height: 54px !important;
            min-height: 54px !important;
            padding: .1rem .7rem !important;
            line-height: 2.25 !important
        }

        @media(max-width:1100px) {
            .purchase-sheet-row {
                grid-template-columns: repeat(2, minmax(145px, 1fr))
            }

            .purchase-color-grid {
                grid-template-columns: 1fr
            }

            .purchase-existing-color-grid {
                grid-template-columns: repeat(2, minmax(170px, 1fr))
            }
        }

        @media(max-width:991.98px) {
            .purchase-create-grid {
                grid-template-columns: 1fr
            }

            .purchase-order-summary {
                position: static
            }
        }

        @media(max-width:650px) {
            .purchase-sheet-toolbar {
                align-items: stretch;
                flex-direction: column
            }

            .purchase-sheet-toolbar .btn {
                width: 100%
            }

            .purchase-item-head {
                align-items: stretch;
                flex-direction: column
            }

            .purchase-item-actions .purchase-row-kind {
                width: 100%
            }

            .purchase-sheet-row {
                grid-template-columns: 1fr
            }

            .purchase-field.span-2 {
                grid-column: auto
            }

            .purchase-color-grid,
            .purchase-existing-color-grid,
            .purchase-scan-box {
                grid-template-columns: 1fr
            }

            .purchase-color-entry {
                grid-template-columns: minmax(90px, 1fr) 46px 78px minmax(130px, 1fr) 34px;
                min-width: 420px
            }

            .purchase-row-details {
                overflow-x: auto
            }

            .purchase-scan-feedback {
                grid-column: auto
            }
        }
    </style>
@endpush


@section('content')
    <section class="main-content purchase-page" dir="rtl">
        <div class="purchase-shell">
            <header class="purchase-page-header">
                <div class="purchase-title-wrap">
                    <span class="purchase-title-icon"><i class="fas fa-cart-plus"></i></span>
                    <div>
                        <h1 class="h4 mb-0">سپلائر سے نئی خریداری</h1>
                        <div class="purchase-breadcrumb">ہر خریدا گیا سیٹ ایک قطار میں درج کریں۔</div>
                    </div>
                </div>

                <div class="purchase-header-actions">
                    <a href="{{ route('admin.purchases.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-list ml-1"></i> خریداری کی فہرست
                    </a>
                    <a href="{{ route('admin.purchases.create') }}" class="purchase-primary-btn">
                        <i class="fas fa-plus"></i> نئی خریداری
                    </a>
                </div>
            </header>

            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                    @if ($savedPurchase)
                        <a href="{{ route('admin.purchases.show', $savedPurchase) }}" class="alert-link mr-2">
                            {{ $savedPurchase->purchase_number }} دیکھیں
                        </a>
                    @endif
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($suppliers->isEmpty())
                <div class="alert alert-warning">
                    خریداری بنانے سے پہلے کم از کم ایک فعال سپلائر شامل کریں۔
                </div>
            @endif

            <div class="purchase-create-grid">
                {{-- Purchase form: contains only purchase fields --}}
                <div>
                    <form method="POST"
                        action="{{ route('admin.purchases.store') }}"
                        id="purchase-create-form"
                        enctype="multipart/form-data">
                        @csrf

                        <div id="purchase-flat-fields"></div>

                        <div class="purchase-panel purchase-form-card mb-3">
                            <h2 class="purchase-section-title">
                                <i class="fas fa-file-invoice"></i>
                                خریداری کی معلومات
                            </h2>

                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label class="purchase-supplier-label" for="purchase_supplier_create">
                                        <span>سپلائر</span>
                                        <a href="{{ route('admin.suppliers.create') }}" target="_blank">
                                            نیا سپلائر
                                        </a>
                                    </label>

                                    <select id="purchase_supplier_create"
                                        name="supplier_id"
                                        class="form-control"
                                        required>
                                        <option value="">سپلائر منتخب کریں</option>
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}"
                                                @selected(old('supplier_id') == $supplier->id)>
                                                {{ $supplier->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group col-md-3">
                                    <label for="purchase_date_create">
                                        بل / خریداری کی تاریخ
                                    </label>
                                    <input id="purchase_date_create"
                                        type="date"
                                        name="purchase_date"
                                        value="{{ old('purchase_date', now()->toDateString()) }}"
                                        class="form-control"
                                        required>
                                </div>

                                <div class="form-group col-md-5">
                                    <label for="purchase_reference_create">
                                        سپلائر بل نمبر
                                    </label>
                                    <input id="purchase_reference_create"
                                        name="reference"
                                        maxlength="255"
                                        value="{{ old('reference') }}"
                                        class="form-control"
                                        placeholder="اختیاری بل یا حوالہ نمبر">
                                </div>
                            </div>
                        </div>

                        <div class="purchase-panel purchase-form-card mb-3">
                            <div class="purchase-sheet-toolbar">
                                <div>
                                    <h2 class="purchase-section-title mb-1">
                                        <i class="fas fa-table"></i>
                                        خریدے گئے سیٹ
                                    </h2>
                                    <p>
                                        نئی قطار شامل کریں، پھر پہلی فہرست سے نیا یا موجودہ سیٹ منتخب کریں۔
                                    </p>
                                </div>


                            </div>

                            <div class="purchase-sheet-scroll">
                                <div class="purchase-sheet">
                                    <div class="purchase-sheet-head" aria-hidden="true"></div>
                                    <div id="purchase-sets"></div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-start mt-3 mb-3">
                                <button type="button"
                                    id="add-purchase-row"
                                    class="btn btn-primary">
                                    <i class="fas fa-plus ml-1"></i>
                                    نئی قطار شامل کریں
                                </button>
                            </div>

                            <details class="purchase-scan-tools">
                                <summary>
                                    <i class="fas fa-qrcode ml-1"></i>
                                    موجودہ سیٹ QR سے شامل کریں
                                </summary>

                                <div class="purchase-scan-box">
                                    <div>
                                        <label for="purchase-stock-scan">QR / اسٹاک کوڈ</label>
                                        <input id="purchase-stock-scan"
                                            class="form-control"
                                            autocomplete="off"
                                            placeholder="QR اسکین کریں">
                                    </div>

                                    <button type="button"
                                        id="purchase-scan-add"
                                        class="btn btn-primary">
                                        قطار شامل کریں
                                    </button>

                                    <p id="purchase-scan-feedback"
                                        class="purchase-scan-feedback text-muted">
                                        اسکین صرف موجودہ سیٹ دوبارہ خریدنے کے لیے ہے۔
                                    </p>
                                </div>
                            </details>

                            <details class="purchase-optional">
                                <summary>اختیاری خریداری نوٹ</summary>
                                <textarea name="note"
                                    class="form-control"
                                    maxlength="1000"
                                    placeholder="صرف خریداری سے متعلق ضروری نوٹ">{{ old('note') }}</textarea>
                            </details>
                        </div>
                    </form>
                </div>

                {{-- Sidebar: purchase buttons and a separate payment form --}}
                <aside class="purchase-panel purchase-order-summary">
                    <h2 class="purchase-section-title">
                        <i class="fas fa-calculator"></i>
                        خریداری خلاصہ
                    </h2>

                    <div class="d-flex justify-content-between text-muted mb-2">
                        <span>منتخب سیٹ</span>
                        <strong id="purchase-item-count">0</strong>
                    </div>

                    <div class="d-flex justify-content-between text-muted mb-2">
                        <span>کل میٹر</span>
                        <strong id="purchase-meter-count" dir="ltr">0.00 m</strong>
                    </div>

                    <div class="d-flex justify-content-between text-muted mb-2">
                        <span>کل سوٹ</span>
                        <strong id="purchase-suit-count">0</strong>
                    </div>

                    <hr>

                    <small class="text-muted d-block mb-2">کل خریداری</small>
                    <div class="total" id="purchase-grand-total">Rs. 0.00</div>

                    {{-- Optional supplier payment, saved together with the purchase when it is received --}}
                    <div class="purchase-payment-box">
                        <h3 class="purchase-payment-title">
                            <i class="fas fa-money-bill-wave ml-1"></i>
                            سپلائر کو ادائیگی
                        </h3>
                        <small class="text-muted d-block mb-2">ابھی کچھ نہیں دیا تو خالی چھوڑ دیں، پوری رقم ادھار رہے گی۔</small>

                        <div class="form-group">
                            <label for="purchase-paid-amount" class="d-flex justify-content-between align-items-center">
                                <span>سپلائر کو ابھی کتنے پیسے دیے؟</span>
                                <button type="button" class="btn btn-link btn-sm p-0" id="purchase-pay-full">پوری رقم دے دی</button>
                            </label>
                            <input id="purchase-paid-amount"
                                form="purchase-create-form"
                                type="number"
                                name="paid_amount"
                                step="0.01"
                                min="0"
                                value="{{ old('paid_amount') }}"
                                class="form-control @error('paid_amount') is-invalid @enderror"
                                dir="ltr"
                                placeholder="0.00">
                        </div>

                        <div class="purchase-payment-fields">
                            <div class="form-group">
                                <label for="purchase-payment-method">پیسے کیسے دیے؟</label>
                                <select id="purchase-payment-method"
                                    form="purchase-create-form"
                                    name="payment_method"
                                    class="form-control">
                                    @foreach (\App\Support\PaymentMethods::LABELS as $value => $label)
                                        <option value="{{ $value }}" @selected(old('payment_method', 'cash') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="purchase-payment-reference">حوالہ / ٹرانزیکشن نمبر</label>
                                <input id="purchase-payment-reference"
                                    form="purchase-create-form"
                                    type="text"
                                    name="payment_reference"
                                    maxlength="255"
                                    value="{{ old('payment_reference') }}"
                                    class="form-control @error('payment_reference') is-invalid @enderror"
                                    placeholder="بینک یا موبائل والٹ ہو تو نمبر لازمی لکھیں">
                            </div>
                        </div>

                        <div class="d-flex justify-content-between text-muted">
                            <span>سپلائر کو ابھی دینے باقی</span>
                            <strong id="purchase-balance-preview" dir="ltr">Rs. 0.00</strong>
                        </div>
                        <small class="text-muted d-block mt-1">یہ ادائیگی "محفوظ اور انوینٹری میں شامل کریں" دبانے پر درج ہوگی، "بعد کے لیے محفوظ کریں" پر نہیں۔</small>
                    </div>

                    <div class="purchase-choice">
                        <button type="submit"
                            form="purchase-create-form"
                            name="submit_action"
                            value="receive"
                            class="purchase-primary-btn"
                            @disabled($suppliers->isEmpty())>
                            <i class="fas fa-box-open"></i>
                            محفوظ اور انوینٹری میں شامل کریں
                        </button>

                        <small>
                            تمام مکمل قطاریں ایک خریداری میں محفوظ ہوں گی۔
                        </small>

                        <button type="submit"
                            form="purchase-create-form"
                            name="submit_action"
                            value="draft"
                            class="purchase-secondary-btn"
                            @disabled($suppliers->isEmpty())>
                            <i class="fas fa-save ml-1"></i>
                            بعد کے لیے محفوظ کریں
                        </button>
                        <small>
                            مال ابھی نہیں آیا تو یہ دبائیں؛ اسٹاک میں شامل نہیں ہوگا، بعد میں کھول کر شامل کریں۔
                        </small>
                    </div>


                    <div class="purchase-action-bar">
                        <a href="{{ route('admin.purchases.index') }}"
                            class="btn btn-light w-100">
                            واپس جائیں
                        </a>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    <template id="purchase-new-set-template">
        <article class="purchase-sheet-item purchase-set-card" data-kind="new">
            <div class="purchase-item-head">
                <div class="purchase-item-title">
                    <span class="purchase-row-number">1</span>
                    <span>خریداری آئٹم</span>
                </div>
                <div class="purchase-item-actions">
                    <select class="form-control purchase-row-kind" aria-label="قطار کی قسم">
                        <option value="new" selected>نیا سیٹ</option>
                        <option value="existing">موجودہ سیٹ</option>
                    </select>
                    <button type="button"
                        class="btn btn-outline-danger purchase-remove remove-item"
                        aria-label="یہ سیٹ ہٹائیں">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>

            <div class="purchase-sheet-row">
                <div class="purchase-field">
                    <label>کیٹیگری</label>
                    <select class="form-control new-category" aria-label="کیٹیگری" required>
                        @if ($categories->count() !== 1)
                            <option value="">کیٹیگری منتخب کریں</option>
                        @endif
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" data-unit="{{ $category->stock_unit }}">{{ $category->fullName() }} ({{ $category->unitLabel() }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="purchase-field">
                    <label>سیٹ کا نام</label>
                    <input class="form-control new-name"
                        maxlength="100"
                        placeholder="مثلاً دلبر"
                        required>
                </div>

                <div class="purchase-field">
                    <label>برانڈ</label>
                    <select class="form-control new-brand" aria-label="برانڈ" required>
                        <option value="">برانڈ منتخب کریں</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                        <option value="__quick_add__">＋ نیا برانڈ شامل کریں</option>
                    </select>
                </div>

                <div class="purchase-field">
                    <label>کپڑے کی قسم</label>
                    <select class="form-control new-type" aria-label="کپڑے کی قسم" required>
                        <option value="">قسم منتخب کریں</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->id }}">{{ $type->localizedName() }}</option>
                        @endforeach
                        <option value="__quick_add__">＋ نئی قسم شامل کریں</option>
                    </select>
                </div>

                <div class="purchase-field">
                    <label>اسٹاک کا طریقہ</label>
                    <select class="form-control new-tracking">
                        <option value="none" class="purchase-shared-option">مشترکہ میٹر</option>
                        <option value="per_color">رنگ الگ الگ</option>
                    </select>
                </div>

                <div class="purchase-field purchase-stock-cell">
                    <label>خریدے گئے میٹر</label>
                    <input type="number"
                        step="0.01"
                        min="0.01"
                        class="form-control purchase-color-quantity new-shared-quantity"
                        placeholder="0.00"
                        required>
                </div>

                <div class="purchase-field">
                    <label>اصل کل خرید قیمت</label>
                    <input type="number"
                        step="0.01"
                        min="0.01"
                        class="form-control purchase-set-total new-total"
                        placeholder="0.00"
                        required>
                </div>

                <div class="purchase-field new-length-field">
                    <label>ڈیفالٹ سوٹ لمبائی</label>
                    <input type="number"
                        step="0.01"
                        min="0.01"
                        class="form-control new-default-length"
                        placeholder="4.50"
                        required>
                </div>

                <div class="purchase-field">
                    <label>فی سوٹ فروختی قیمت</label>
                    <input type="number"
                        step="0.01"
                        min="0"
                        class="form-control new-suit-price"
                        placeholder="مثلاً 2200"
                        required>
                    <small class="text-muted new-sale-rate" dir="ltr"></small>
                </div>
            </div>

            <div class="purchase-row-details"></div>

            <div class="purchase-row-summary">
                <span>مقدار: <strong class="purchase-set-meters" dir="ltr">0.00 m</strong></span>
                <span>حسابی لاگت: <strong class="purchase-unit-cost-preview" dir="ltr">Rs. 0.0000 / m</strong></span>
            </div>
        </article>
    </template>

    <template id="purchase-set-template">
        <article class="purchase-sheet-item purchase-set-card" data-kind="existing">
            <div class="purchase-item-head">
                <div class="purchase-item-title">
                    <span class="purchase-row-number">1</span>
                    <span>خریداری آئٹم</span>
                </div>
                <div class="purchase-item-actions">
                    <select class="form-control purchase-row-kind" aria-label="قطار کی قسم">
                        <option value="new">نیا سیٹ</option>
                        <option value="existing" selected>موجودہ سیٹ</option>
                    </select>
                    <button type="button"
                        class="btn btn-outline-danger purchase-remove remove-item"
                        aria-label="یہ سیٹ ہٹائیں">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>

            <div class="purchase-sheet-row">
                <div class="purchase-field span-2">
                    <label>موجودہ سیٹ منتخب کریں</label>
                    <select class="form-control purchase-set-select"
                        required
                        aria-label="کپڑے کا سیٹ منتخب کریں">
                        <option value="">سیٹ منتخب کریں</option>
                        @foreach ($cloths as $cloth)
                            <option value="{{ $cloth->id }}"
                                data-stock-code="{{ $cloth->stock_code }}"
                                data-brand-code="{{ $cloth->brand?->bundle_code }}"
                                data-default-cost="{{ (float) ($cloth->colors->first()?->average_unit_cost ?: $cloth->price) }}">
                                {{ $cloth->name }} — {{ $cloth->brand->name ?? 'برانڈ' }}
                            </option>
                        @endforeach
                    </select>
                    <small class="purchase-existing-meta">
                        منتخب کرنے پر مقدار درج کریں۔
                    </small>
                </div>

                <div class="purchase-field purchase-stock-cell">
                    <label>خریدے گئے میٹر</label>
                    <span class="purchase-not-applicable">پہلے سیٹ منتخب کریں</span>
                </div>

                <div class="purchase-field">
                    <label>اصل کل خرید قیمت</label>
                    <input type="number"
                        step="0.01"
                        min="0.01"
                        class="form-control purchase-set-total"
                        aria-label="اصل کل خرید قیمت"
                        placeholder="0.00"
                        required>
                </div>
            </div>

            <div class="purchase-row-details"></div>

            <div class="purchase-row-summary">
                <span>مقدار: <strong class="purchase-set-meters" dir="ltr">0.00 m</strong></span>
                <span>حسابی لاگت: <strong class="purchase-unit-cost-preview" dir="ltr">Rs. 0.0000 / m</strong></span>
            </div>
        </article>
    </template>

    <template id="purchase-new-color-template">
        <div class="purchase-color-entry">
            <div>
                <label>رنگ کا نام</label>
                <input class="form-control new-color-name"
                    maxlength="100"
                    placeholder="مثلاً نیوی"
                    required>
            </div>

            <div>
                <label>رنگ</label>
                <input type="color" class="form-control new-color-hex" value="#243b53">
            </div>

            <div>
                <label class="purchase-unit-name">میٹر</label>
                <input type="number"
                    step="0.01"
                    min="0.01"
                    class="form-control purchase-color-quantity new-color-quantity"
                    placeholder="0.00"
                    required>
            </div>

            <div class="purchase-color-photo">
                <label>تصویر (اختیاری)</label>
                <label class="purchase-image-picker">
                    <img class="purchase-color-preview" alt="رنگ کی تصویر">
                    <i class="fas fa-image"></i>
                    <span class="purchase-image-name">تصویر منتخب کریں</span>
                    <input type="file"
                        class="new-color-image"
                        accept="image/png,image/jpeg,image/webp">
                </label>
            </div>

            <button type="button"
                class="btn btn-outline-danger purchase-color-delete"
                aria-label="رنگ ہٹائیں">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </template>

    <script type="application/json" id="purchase-catalog">@json($catalog)</script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const catalog = JSON.parse(document.getElementById('purchase-catalog').textContent),
                sets = document.getElementById('purchase-sets'),
                existingTemplate = document.getElementById('purchase-set-template'),
                newTemplate = document.getElementById('purchase-new-set-template'),
                colorTemplate = document.getElementById('purchase-new-color-template'),
                flat = document.getElementById('purchase-flat-fields'),
                feedback = document.getElementById('purchase-scan-feedback'),
                scan = document.getElementById('purchase-stock-scan');

            const money = value => 'Rs. ' + value.toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            // Optional payment entered with the purchase.
            const paidInput = document.getElementById('purchase-paid-amount'),
                paymentMethod = document.getElementById('purchase-payment-method'),
                paymentReference = document.getElementById('purchase-payment-reference');
            let purchaseGrandTotal = 0;

            const updatePaymentPreview = () => {
                const paid = parseFloat(paidInput.value) || 0;
                paidInput.max = purchaseGrandTotal.toFixed(2);
                paidInput.setCustomValidity(
                    paid > purchaseGrandTotal + 0.001 ? 'ادائیگی کی رقم کل خریداری سے زیادہ نہیں ہو سکتی۔' : ''
                );
                paymentReference.required = paid > 0 && !['cash', 'other'].includes(paymentMethod.value);
                document.getElementById('purchase-balance-preview').textContent =
                    money(Math.max(0, purchaseGrandTotal - paid));
            };

            paidInput.addEventListener('input', updatePaymentPreview);
            paymentMethod.addEventListener('change', updatePaymentPreview);
            document.getElementById('purchase-pay-full').addEventListener('click', () => {
                paidInput.value = purchaseGrandTotal.toFixed(2);
                updatePaymentPreview();
            });

            // A draft never posts a payment, so don't let payment checks block saving it.
            document.querySelectorAll('[form="purchase-create-form"][name="submit_action"]').forEach(button => {
                button.addEventListener('click', () => {
                    const draft = button.value === 'draft';
                    paidInput.disabled = draft;
                    paymentMethod.disabled = draft;
                    paymentReference.disabled = draft;
                });
            });

            // Meter stock is cut from a roll; women's packed suits are counted whole.
            const unitInfo = row => row && row.dataset.unit === 'suit'
                ? { name: 'سوٹ', short: ' سوٹ', step: '1', min: '1', placeholder: '0', digits: 0 }
                : { name: 'میٹر', short: ' m', step: '0.01', min: '0.01', placeholder: '0.00', digits: 2 };
            const formatQty = (row, value) => {
                const unit = unitInfo(row);
                return Number(value || 0).toFixed(unit.digits) + unit.short;
            };

            // Apply the row's unit to the fields that renderNewStock/renderExisting don't rebuild.
            const applyUnit = row => {
                const unit = unitInfo(row),
                    suit = row.dataset.unit === 'suit';

                row.querySelectorAll('.purchase-color-quantity').forEach(input => {
                    input.step = unit.step;
                    input.min = input.closest('.existing') ? '0' : unit.min;
                    input.placeholder = unit.placeholder;
                });
                row.querySelectorAll('.purchase-unit-name').forEach(label => label.textContent = unit.name);

                const shared = row.querySelector('.purchase-shared-option');
                if (shared) shared.textContent = 'مشترکہ ' + unit.name;

                const lengthField = row.querySelector('.new-length-field');
                if (lengthField) {
                    const lengthInput = lengthField.querySelector('.new-default-length');
                    lengthField.hidden = suit;
                    lengthInput.required = !suit;
                    if (suit) lengthInput.value = '';
                }
            };

            const addColor = row => {
                row.querySelector('.purchase-color-grid').appendChild(
                    colorTemplate.content.firstElementChild.cloneNode(true)
                );
                applyUnit(row);
            };

            // Keep options added during this purchase available to rows created later.
            const quickAddedOptions = { brand: new Map(), type: new Map() };
            const insertQuickOption = (select, id, name) => {
                if (!Array.from(select.options).some(option => option.value === String(id))) {
                    const option = new Option(name, String(id));
                    const addChoice = select.querySelector('option[value="__quick_add__"]');
                    select.add(option, addChoice || null);
                }
            };
            const populateQuickOptions = row => {
                [['brand', '.new-brand'], ['type', '.new-type']].forEach(([kind, selector]) => {
                    const select = row.querySelector(selector);
                    if (select) quickAddedOptions[kind].forEach((name, id) => insertQuickOption(select, id, name));
                });
            };
            const addNewRow = (before = null) => {
                const row = newTemplate.content.firstElementChild.cloneNode(true);
                populateQuickOptions(row);
                row.dataset.unit = row.querySelector('.new-category').selectedOptions[0]?.dataset.unit || 'meter';
                applyUnit(row);
                before ? sets.insertBefore(row, before) : sets.appendChild(row);
                return row;
            };

            const addExistingRow = (clothId = '', before = null) => {
                const row = existingTemplate.content.firstElementChild.cloneNode(true);
                before ? sets.insertBefore(row, before) : sets.appendChild(row);

                if (clothId) {
                    row.querySelector('.purchase-set-select').value = clothId;
                    renderExisting(row);
                }

                return row;
            };

            const replaceRow = (row, kind) => {
                const next = row.nextSibling,
                    newRow = kind === 'existing' ? addExistingRow('', next) : addNewRow(next);

                row.remove();
                updateTotals();
                newRow.querySelector(kind === 'existing' ? '.purchase-set-select' : '.new-name').focus();
            };

            const selectedExistingIds = () => new Set(
                Array.from(sets.querySelectorAll('[data-kind="existing"] .purchase-set-select'))
                    .map(select => select.value)
                    .filter(Boolean)
            );

            const renderNewStock = row => {
                const stock = row.querySelector('.purchase-stock-cell'),
                    details = row.querySelector('.purchase-row-details'),
                    tracked = row.querySelector('.new-tracking').value === 'per_color';

                const unit = unitInfo(row);

                if (tracked) {
                    stock.innerHTML =
                        '<label>رنگوں کی مقدار</label><button type="button" class="btn btn-outline-primary toggle-colors">رنگ اور ' + unit.name + ' درج کریں</button>';

                    details.innerHTML =
                        '<div class="purchase-color-head"><strong>اس سیٹ کے رنگ اور خریدے گئے ' + unit.name + '</strong><button type="button" class="btn btn-sm btn-outline-primary add-color"><i class="fas fa-plus ml-1"></i> رنگ شامل کریں</button></div><div class="purchase-color-grid"></div>';

                    details.classList.add('is-open');
                    addColor(row);
                } else {
                    stock.innerHTML =
                        '<label>خریدے گئے ' + unit.name + '</label><input type="number" step="' + unit.step + '" min="' + unit.min +
                        '" class="form-control purchase-color-quantity new-shared-quantity" placeholder="' + unit.placeholder + '" required>';

                    details.innerHTML = '';
                    details.classList.remove('is-open');
                }

                applyUnit(row);
                updateTotals();
            };

            const renderExisting = row => {
                const item = catalog[row.querySelector('.purchase-set-select').value],
                    stock = row.querySelector('.purchase-stock-cell'),
                    details = row.querySelector('.purchase-row-details'),
                    meta = row.querySelector('.purchase-existing-meta');

                stock.innerHTML =
                    '<label>مقدار</label><span class="purchase-not-applicable">پہلے سیٹ منتخب کریں</span>';

                details.innerHTML = '';
                details.classList.remove('is-open');

                if (!item) {
                    meta.textContent = 'منتخب کرنے پر مقدار درج کریں۔';
                    updateTotals();
                    return;
                }

                row.dataset.unit = item.unit || 'meter';
                const unit = unitInfo(row);
                meta.textContent = (item.brand || 'برانڈ') + ' / ' + (item.type || 'قسم');

                if (item.tracks_colors) {
                    stock.innerHTML =
                        '<label>مقدار</label><button type="button" class="btn btn-outline-primary toggle-colors">' +
                        item.colors.length + ' رنگ — ' + unit.name + ' درج کریں</button>';

                    const grid = document.createElement('div');
                    grid.className = 'purchase-color-grid purchase-existing-color-grid';

                    item.colors.forEach(color => {
                        const cell = document.createElement('div');
                        cell.className = 'purchase-color-entry existing';

                        cell.innerHTML =
                            '<label>' + color.name + '<small>موجودہ ' + formatQty(row, color.stock) +
                            '</small></label><input type="number" step="' + unit.step + '" min="0" class="form-control purchase-color-quantity" data-color-id="' +
                            color.id + '" aria-label="' + color.name +
                            ' کی خریداری ' + unit.name + ' میں" placeholder="' + unit.placeholder + '">';

                        grid.appendChild(cell);
                    });

                    details.appendChild(grid);
                    details.classList.add('is-open');
                } else {
                    const color = item.colors[0];

                    stock.innerHTML =
                        '<label>خریدے گئے ' + unit.name + '</label><input type="number" step="' + unit.step + '" min="' + unit.min +
                        '" class="form-control purchase-color-quantity" data-color-id="' + color.id +
                        '" aria-label="خریداری کی مقدار ' + unit.name + ' میں" placeholder="' + unit.placeholder +
                        '" required><small class="purchase-existing-meta">موجودہ ' + formatQty(row, color.stock) + '</small>';
                }

                updateTotals();
            };

            const updateTotals = () => {
                let grand = 0,
                    meters = 0,
                    suits = 0,
                    selected = 0;

                sets.querySelectorAll('.purchase-set-card').forEach((row, index) => {
                    row.querySelector('.purchase-row-number').textContent = index + 1;

                    if (
                        row.dataset.kind === 'existing'
                            ? row.querySelector('.purchase-set-select').value
                            : row.querySelector('.new-name').value.trim()
                    ) {
                        selected++;
                    }

                    const qty = Array.from(row.querySelectorAll('.purchase-color-quantity'))
                            .reduce((sum, input) => sum + (parseFloat(input.value) || 0), 0),
                        total = parseFloat(row.querySelector('.purchase-set-total').value) || 0;

                    const suitRow = row.dataset.unit === 'suit';
                    row.querySelector('.purchase-set-meters').textContent = formatQty(row, qty);

                    row.querySelector('.purchase-unit-cost-preview').textContent =
                        'Rs. ' + (qty ? total / qty : 0).toLocaleString(undefined, {
                            minimumFractionDigits: suitRow ? 2 : 4,
                            maximumFractionDigits: suitRow ? 2 : 4
                        }) + (suitRow ? ' / سوٹ' : ' / m');

                    // Meter sets are priced per suit; show the per-meter rate it works out to.
                    const saleRate = row.querySelector('.new-sale-rate');
                    if (saleRate && suitRow) {
                        saleRate.textContent = '';
                    } else if (saleRate) {
                        const suitPrice = parseFloat(row.querySelector('.new-suit-price').value) || 0,
                            suitLength = parseFloat(row.querySelector('.new-default-length').value) || 0;
                        saleRate.textContent = suitPrice && suitLength
                            ? '= ' + money(suitPrice / suitLength) + ' / m'
                            : '';
                    }

                    if (suitRow) {
                        suits += qty;
                    } else {
                        meters += qty;
                    }
                    grand += total;
                });

                document.getElementById('purchase-item-count').textContent = selected;
                document.getElementById('purchase-meter-count').textContent = meters.toFixed(2) + ' m';
                document.getElementById('purchase-suit-count').textContent = suits.toFixed(0);
                document.getElementById('purchase-grand-total').textContent = money(grand);
                purchaseGrandTotal = grand;
                updatePaymentPreview();
            };

            document.getElementById('add-purchase-row').addEventListener('click', () => {
                const row = addNewRow();
                updateTotals();
                row.querySelector('.new-name').focus();
            });

            sets.addEventListener('change', event => {
                const row = event.target.closest('.purchase-set-card');

                if (event.target.matches('.purchase-row-kind')) {
                    replaceRow(row, event.target.value);
                    return;
                }

                if (event.target.matches('.new-tracking')) {
                    renderNewStock(row);
                    return;
                }

                if (event.target.matches('.new-category')) {
                    row.dataset.unit = event.target.selectedOptions[0]?.dataset.unit || 'meter';
                    renderNewStock(row);
                    return;
                }

                if (event.target.matches('.purchase-set-select')) {
                    const duplicate = event.target.value && Array.from(
                        sets.querySelectorAll('.purchase-set-select')
                    ).some(select => select !== event.target && select.value === event.target.value);

                    if (duplicate) {
                        event.target.value = '';
                        feedback.textContent = 'یہ موجودہ سیٹ پہلے ہی شامل ہے۔';
                        feedback.className = 'purchase-scan-feedback is-warning';
                        document.querySelector('.purchase-scan-tools').open = true;
                    }

                    renderExisting(row);
                }
            });

            sets.addEventListener('input', updateTotals);

            sets.addEventListener('click', event => {
                const row = event.target.closest('.purchase-set-card');

                if (event.target.closest('.remove-item')) {
                    row.remove();

                    if (!sets.children.length) {
                        addNewRow();
                    }

                    updateTotals();
                    return;
                }

                if (event.target.closest('.toggle-colors')) {
                    row.querySelector('.purchase-row-details').classList.toggle('is-open');
                    return;
                }

                if (event.target.closest('.add-color')) {
                    addColor(row);
                    updateTotals();
                    return;
                }

                const colorDelete = event.target.closest('.purchase-color-delete');

                if (colorDelete) {
                    const grid = colorDelete.closest('.purchase-color-grid');
                    colorDelete.closest('.purchase-color-entry').remove();

                    if (!grid.children.length) {
                        addColor(row);
                    }

                    updateTotals();
                }
            });

            sets.addEventListener('change', event => {
                if (!event.target.matches('.new-color-image')) {
                    return;
                }

                const wrap = event.target.closest('.purchase-color-photo'),
                    preview = wrap.querySelector('.purchase-color-preview'),
                    name = wrap.querySelector('.purchase-image-name'),
                    file = event.target.files[0];

                if (preview.dataset.previewUrl) {
                    URL.revokeObjectURL(preview.dataset.previewUrl);
                }

                if (file) {
                    const url = URL.createObjectURL(file);
                    preview.src = url;
                    preview.dataset.previewUrl = url;
                    preview.classList.add('is-visible');
                    name.textContent = file.name;
                } else {
                    preview.removeAttribute('src');
                    preview.classList.remove('is-visible');
                    name.textContent = 'تصویر منتخب کریں';
                }
            });

            const addScanned = () => {
                const code = scan.value.trim().toUpperCase(),
                    item = Object.values(catalog).find(row =>
                        String(row.stock_code || '').toUpperCase() === code ||
                        String(row.brand_code || '').toUpperCase() === code
                    );

                if (!code || !item) {
                    feedback.textContent = code
                        ? 'کوڈ سے کوئی موجودہ سیٹ نہیں ملا۔'
                        : 'پہلے QR اسکین کریں۔';
                    feedback.className = 'purchase-scan-feedback is-error';
                    return;
                }

                if (selectedExistingIds().has(String(item.id))) {
                    feedback.textContent = 'یہ موجودہ سیٹ پہلے ہی شامل ہے۔';
                    feedback.className = 'purchase-scan-feedback is-warning';
                    return;
                }

                addExistingRow(String(item.id));
                updateTotals();
                scan.value = '';
                feedback.textContent = 'موجودہ سیٹ کی نئی قطار شامل ہو گئی۔';
                feedback.className = 'purchase-scan-feedback is-success';
            };

            document.getElementById('purchase-scan-add').addEventListener('click', addScanned);

            scan.addEventListener('keydown', event => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    addScanned();
                }
            });

            document.getElementById('purchase-create-form').addEventListener('submit', function(event) {
                flat.innerHTML = '';

                let invalid = false,
                    newIndex = 0;

                sets.querySelectorAll('.purchase-set-card').forEach(row => {
                    const quantities = Array.from(row.querySelectorAll('.purchase-color-quantity'))
                            .map(input => ({
                                id: input.dataset.colorId,
                                quantity: parseFloat(input.value) || 0
                            }))
                            .filter(entry => entry.quantity > 0),
                        setTotal = parseFloat(row.querySelector('.purchase-set-total').value) || 0,
                        totalQty = quantities.reduce((sum, entry) => sum + entry.quantity, 0);

                    if (!totalQty || !setTotal) {
                        invalid = true;
                        return;
                    }

                    if (row.dataset.kind === 'existing') {
                        let allocated = 0;

                        quantities.forEach((entry, index) => {
                            const line = index === quantities.length - 1
                                ? Math.round((setTotal - allocated) * 100) / 100
                                : Math.round((setTotal * entry.quantity / totalQty) * 100) / 100;

                            allocated += line;

                            [
                                ['cloth_color_id[]', entry.id],
                                ['quantity[]', entry.quantity],
                                ['line_total[]', line]
                            ].forEach(([name, value]) => {
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = name;
                                input.value = value;
                                flat.appendChild(input);
                            });
                        });
                    } else {
                        const prefix = 'new_sets[' + (newIndex++) + ']';

                        [
                            ['.new-name', 'name'],
                            ['.new-brand', 'cloth_brand_id'],
                            ['.new-type', 'cloth_type_id'],
                            ['.new-category', 'cloth_category_id'],
                            ['.new-suit-price', 'suit_sale_price'],
                            ['.new-default-length', 'default_sale_length'],
                            ['.new-tracking', 'tracking_mode'],
                            ['.new-total', 'total']
                        ].forEach(([selector, key]) => {
                            const field = row.querySelector(selector);
                            // Packed suits have no cutting length, so the hidden field is not sent.
                            field.name = field.closest('[hidden]') ? '' : prefix + '[' + key + ']';
                        });

                        if (row.querySelector('.new-tracking').value === 'none') {
                            row.querySelector('.new-shared-quantity').name = prefix + '[quantity]';
                        } else {
                            row.querySelectorAll('.purchase-color-entry').forEach((colorRow, colorIndex) => {
                                colorRow.querySelector('.new-color-name').name =
                                    prefix + '[colors][' + colorIndex + '][name]';

                                colorRow.querySelector('.new-color-hex').name =
                                    prefix + '[colors][' + colorIndex + '][hex]';

                                colorRow.querySelector('.new-color-quantity').name =
                                    prefix + '[colors][' + colorIndex + '][quantity]';

                                colorRow.querySelector('.new-color-image').name =
                                    prefix + '[colors][' + colorIndex + '][image]';
                            });
                        }
                    }
                });

                if (!sets.children.length || invalid) {
                    event.preventDefault();
                    feedback.textContent = 'ہر قطار کی مقدار اور اصل کل خرید قیمت مکمل کریں۔';
                    feedback.className = 'purchase-scan-feedback is-error';
                    document.querySelector('.purchase-scan-tools').open = true;
                }
            });

            // One select handles existing values and inline creation for every purchase row.
            document.addEventListener('focusin', function(event) {
                if (event.target.matches('.new-brand, .new-type')) {
                    event.target.dataset.previousValue = event.target.value;
                }
            });
            document.addEventListener('change', async function(event) {
                const select = event.target;
                if (!select.matches('.new-brand, .new-type')) return;
                if (select.value !== '__quick_add__') {
                    select.dataset.previousValue = select.value;
                    return;
                }
                select.value = select.dataset.previousValue || '';
                const isBrand = select.classList.contains('new-brand');
                const name = window.prompt(isBrand ? 'نئے برانڈ کا نام لکھیں:' : 'کپڑے کی نئی قسم کا اردو نام لکھیں:');
                if (!name || !name.trim()) return;
                const data = new FormData();
                if (isBrand) {
                    data.append('name', name.trim());
                } else {
                    data.append('name_ur', name.trim());
                    const english = window.prompt('کپڑے کی قسم کا انگریزی نام لکھیں:');
                    if (!english || !english.trim()) return;
                    data.append('name_en', english.trim());
                }
                data.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]').value);
                select.disabled = true;
                try {
                    const response = await fetch(isBrand ? @json(route('admin.clothbrand.store')) : @json(route('admin.clothtype.store')), {
                        method: 'POST', body: data, credentials: 'same-origin',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const result = await response.json();
                    if (!response.ok) throw new Error(result.message || Object.values(result.errors || {}).flat().join(' ') || 'محفوظ نہیں ہوا');
                    const kind = isBrand ? 'brand' : 'type';
                    quickAddedOptions[kind].set(String(result.id), result.name);
                    const cls = isBrand ? '.new-brand' : '.new-type';
                    document.querySelectorAll(cls).forEach(other => insertQuickOption(other, result.id, result.name));
                    select.value = String(result.id);
                    select.dataset.previousValue = select.value;
                } catch (error) {
                    window.alert(error.message || 'نیا انتخاب محفوظ نہیں ہوا۔');
                } finally { select.disabled = false; }
            });


            addNewRow();
            updateTotals();
        });
    </script>
@endsection
