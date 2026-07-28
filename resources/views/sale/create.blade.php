@extends('main')

@section('content')
<section class="main-content">
    <div class="container py-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h1 class="h4 text-right mb-4">نئی فروخت</h1>

                <form id="sale-create-form" action="{{ route('admin.sale.store') }}" method="POST">
                    @csrf

                    <div class="form-group">
                        <label for="sale_customer" class="font-weight-bold">گاہک کا نام</label>
                        <select id="sale_customer" required class="form-control" name="customer_id">
                            <option value="">گاہک منتخب کریں</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>
                                    {{ $customer->name }} — {{ $customer->phone_number1 }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">ٹیلرنگ اور دکان دونوں کے لیے یہی مشترکہ گاہک کھاتہ استعمال ہوگا۔</small>
                    </div>

                    <fieldset class="mt-4">
                        <legend class="h5 font-weight-bold">فروخت کے آئٹمز</legend>
                        <div class="addmore">
                            <div class="row record">
                                <div class="col-md-4 mb-3">
                                    <label class="font-weight-bold d-block">پروڈکٹ کا نام
                                        <input type="text" class="form-control mt-1" required name="name[]" value="{{ old('name.0') }}" aria-label="پروڈکٹ کا نام">
                                    </label>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="font-weight-bold d-block">مصنوعات کی تعداد
                                        <input type="number" class="form-control mt-1 sale-quantity" required name="quantity[]" min="1" step="1" value="{{ old('quantity.0', 1) }}" aria-label="مصنوعات کی تعداد">
                                    </label>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="font-weight-bold d-block">فی عدد قیمت
                                        <input type="number" class="form-control mt-1 sale-unit-price" required name="price[]" min="0" step="0.01" value="{{ old('price.0') }}" aria-label="فی عدد قیمت">
                                    </label>
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="received_payment" class="font-weight-bold">موصول شدہ رقم</label>
                            <input type="number" class="form-control" required name="received_payment" id="received_payment" min="0" step="0.01" value="{{ old('received_payment', 0) }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="sale_total" class="font-weight-bold">کل قیمت</label>
                            <input type="number" id="sale_total" class="form-control" readonly aria-readonly="true">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="remaining_balance" class="font-weight-bold">بقیہ رقم</label>
                            <input type="number" id="remaining_balance" class="form-control" name="remaining_balance" readonly aria-readonly="true">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            @include('components.payment-method-fields', ['prefix' => 'sale_payment'])
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="sale_paid_on">ادائیگی کی تاریخ</label>
                                <input id="sale_paid_on" type="date" name="paid_on" class="form-control" value="{{ old('paid_on', now()->toDateString()) }}">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap justify-content-between mt-4" style="gap:.75rem">
                        <button type="button" class="btn btn-outline-danger sale-remove-item">آخری آئٹم ہٹائیں</button>
                        <button type="button" class="btn btn-outline-primary sale-add-item">مزید آئٹم شامل کریں</button>
                        <button type="submit" class="btn btn-primary">فروخت محفوظ کریں</button>
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
    function refreshSaleTotals() {
        var total = 0;

        $('.record').each(function () {
            var quantity = Number($(this).find('.sale-quantity').val()) || 0;
            var unitPrice = Number($(this).find('.sale-unit-price').val()) || 0;
            total += quantity * unitPrice;
        });

        var received = Number($('#received_payment').val()) || 0;
        $('#sale_total').val(total.toFixed(2));
        $('#remaining_balance').val(Math.max(total - received, 0).toFixed(2));
        $('#received_payment').attr('max', total.toFixed(2));
        $('.sale-remove-item').prop('disabled', $('.record').length <= 1);
    }

    $(document).on('input', '.sale-quantity, .sale-unit-price, #received_payment', refreshSaleTotals);
    $('.sale-add-item').on('click', function () {
        var item = $('.record:first').clone();
        item.find('input').val('');
        item.find('.sale-quantity').val(1);
        $('.addmore').append(item);
        refreshSaleTotals();
    });
    $('.sale-remove-item').on('click', function () {
        if ($('.record').length > 1) {
            $('.record:last').remove();
            refreshSaleTotals();
        }
    });
    refreshSaleTotals();
});
</script>
@endpush
