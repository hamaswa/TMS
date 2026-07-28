@extends('main')

@section('content')
@php
    $currentTransaction = $transaction->first();
    $itemRows = collect(old('name', []))->isNotEmpty()
        ? collect(old('name'))->map(fn ($name, $index) => (object) [
            'product_name' => $name,
            'quantity' => old('quantity.'.$index),
            'price' => old('price.'.$index),
        ])
        : $sales->detail;
@endphp
<section class="main-content">
    <div class="container py-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h1 class="h4 text-right mb-4">فروخت تبدیل کریں</h1>

                @if($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <strong>فروخت محفوظ نہیں ہو سکی:</strong>
                        <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif

                <form id="sale-edit-form" action="{{ route('admin.sale.update', $sales) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="sale_edit_customer" class="font-weight-bold">گاہک کا نام</label>
                        <select id="sale_edit_customer" required class="form-control" name="customer_id">
                            <option value="">گاہک منتخب کریں</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" @selected(old('customer_id', $sales->customer_id) == $customer->id)>
                                    {{ $customer->name }} — {{ $customer->phone_number1 }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <fieldset class="mt-4">
                        <legend class="h5 font-weight-bold">فروخت کے آئٹمز</legend>
                        <div class="sale-edit-items">
                            @foreach($itemRows as $item)
                                <div class="row sale-edit-item">
                                    <div class="col-md-4 mb-3">
                                        <label class="font-weight-bold d-block">پروڈکٹ کا نام
                                            <input type="text" class="form-control mt-1" required name="name[]" value="{{ $item->product_name }}" aria-label="پروڈکٹ کا نام">
                                        </label>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="font-weight-bold d-block">مصنوعات کی تعداد
                                            <input type="number" class="form-control mt-1 sale-edit-quantity" required name="quantity[]" min="1" step="1" value="{{ $item->quantity }}" aria-label="مصنوعات کی تعداد">
                                        </label>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="font-weight-bold d-block">فی عدد قیمت
                                            <input type="number" class="form-control mt-1 sale-edit-unit-price" required name="price[]" min="0" step="0.01" value="{{ $item->price }}" aria-label="فی عدد قیمت">
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </fieldset>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="sale_edit_received" class="font-weight-bold">موصول شدہ رقم</label>
                            <input id="sale_edit_received" type="number" class="form-control" required name="received_payment" min="0" step="0.01" value="{{ old('received_payment', $currentTransaction?->recivedPayment ?? 0) }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="sale_edit_total" class="font-weight-bold">کل قیمت</label>
                            <input id="sale_edit_total" type="number" class="form-control" readonly aria-readonly="true">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="sale_edit_remaining" class="font-weight-bold">اس فروخت کا بقایا</label>
                            <input id="sale_edit_remaining" type="number" class="form-control" name="remaining_balance" readonly aria-readonly="true">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="sale_edit_previous" class="font-weight-bold">پہلے کا مجموعی بقایا</label>
                            <input id="sale_edit_previous" type="number" class="form-control" value="{{ $latestBalance }}" readonly aria-readonly="true">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            @include('components.payment-method-fields', [
                                'prefix' => 'sale_edit_payment',
                                'currentMethod' => $currentTransaction?->payment_method ?? 'cash',
                                'currentReference' => $currentTransaction?->payment_reference,
                            ])
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="sale_edit_paid_on">ادائیگی کی تاریخ</label>
                                <input id="sale_edit_paid_on" type="date" name="paid_on" class="form-control" value="{{ old('paid_on', $currentTransaction?->paid_on?->format('Y-m-d') ?? now()->toDateString()) }}">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap justify-content-between mt-4" style="gap:.75rem">
                        <button type="button" class="btn btn-outline-danger sale-edit-remove-item">آخری آئٹم ہٹائیں</button>
                        <button type="button" class="btn btn-outline-primary sale-edit-add-item">مزید آئٹم شامل کریں</button>
                        <button type="submit" class="btn btn-primary">تبدیلی محفوظ کریں</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    function refreshSaleEditTotals() {
        var total = 0;

        $('.sale-edit-item').each(function () {
            var quantity = Number($(this).find('.sale-edit-quantity').val()) || 0;
            var unitPrice = Number($(this).find('.sale-edit-unit-price').val()) || 0;
            total += quantity * unitPrice;
        });

        var received = Number($('#sale_edit_received').val()) || 0;
        $('#sale_edit_total').val(total.toFixed(2));
        $('#sale_edit_remaining').val(Math.max(total - received, 0).toFixed(2));
        $('#sale_edit_received').attr('max', total.toFixed(2));
        $('.sale-edit-remove-item').prop('disabled', $('.sale-edit-item').length <= 1);
    }

    $(document).on('input', '.sale-edit-quantity, .sale-edit-unit-price, #sale_edit_received', refreshSaleEditTotals);
    $('.sale-edit-add-item').on('click', function () {
        var item = $('.sale-edit-item:first').clone();
        item.find('input').val('');
        item.find('.sale-edit-quantity').val(1);
        $('.sale-edit-items').append(item);
        refreshSaleEditTotals();
    });
    $('.sale-edit-remove-item').on('click', function () {
        if ($('.sale-edit-item').length > 1) {
            $('.sale-edit-item:last').remove();
            refreshSaleEditTotals();
        }
    });
    refreshSaleEditTotals();
});
</script>
@endpush
