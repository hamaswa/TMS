{{--
    Return some (or all) items of one cloth receipt.
    Expects: $receipt (CounterSaleReceipt), $items (its SaleStock rows with type/brand and returned_quantity),
    $outstanding (unpaid balance still on this receipt), $paid (amount the customer has paid on it).
--}}
@php
    $returnRows = $items->map->returnLine();
    $formKey = 'receiptReturn'.$receipt->id;
@endphp
<form method="POST" action="{{ route('admin.counter-sales.returns.store', $receipt) }}" class="receipt-return-form text-right" dir="rtl"
    data-outstanding="{{ round((float) $outstanding, 2) }}" data-paid="{{ round((float) $paid, 2) }}"
    data-confirm="کیا آپ منتخب آئٹمز واپس کرنا چاہتے ہیں؟">
    @csrf
    <p class="text-muted small mb-2">جو آئٹم واپس آیا ہے اسے منتخب کریں۔ باقی آئٹمز گاہک کے پاس فروخت شدہ رہیں گے۔</p>
    <div class="table-responsive">
        <table class="table table-sm table-bordered mb-2 receipt-return-table">
            <thead class="thead-light"><tr><th style="width:2.5rem"></th><th>آئٹم</th><th>خریدا</th><th>قیمت فی یونٹ</th><th>کل قیمت</th><th style="width:7.5rem">واپسی مقدار</th></tr></thead>
            <tbody>
            @foreach ($returnRows as $row)
                <tr class="{{ $row->remaining <= 0 ? 'text-muted' : '' }}">
                    <td class="text-center align-middle">
                        <input type="checkbox" class="return-pick" id="{{ $formKey }}-pick-{{ $row->id }}" @disabled($row->remaining <= 0)>
                    </td>
                    <td class="align-middle">
                        <label for="{{ $formKey }}-pick-{{ $row->id }}" class="mb-0 font-weight-bold">{{ $row->name }}</label>
                        @if ($row->returned > 0)
                            <br><small class="text-danger">پہلے واپس: {{ $row->returnedLabel }} {{ $row->unit }}</small>
                        @endif
                    </td>
                    <td class="align-middle" dir="rtl">{{ $row->soldLabel }}</td>
                    <td class="align-middle" dir="ltr">Rs {{ number_format($row->unitPrice, 2) }} <small class="text-muted">/ {{ $row->unit }}</small></td>
                    <td class="align-middle" dir="ltr">Rs {{ number_format($row->lineTotal, 2) }}</td>
                    <td class="align-middle">
                        @if ($row->remaining > 0)
                            <input type="number" class="form-control form-control-sm return-qty" name="items[{{ $row->id }}]" value="0"
                                min="0" max="{{ $row->remaining }}" step="{{ $row->perSuit ? 1 : 0.01 }}"
                                data-unit-price="{{ $row->unitPrice }}" data-remaining="{{ $row->remaining }}" dir="ltr">
                        @else
                            <span class="badge badge-secondary">مکمل واپس</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    @if ((float) $outstanding > 0)
                                    <div class="return-balance-choice mb-2">
                                        <label class="d-block font-weight-bold mb-1">اس رسید پر Rs {{ number_format((float) $outstanding, 2) }} بقایا ہے۔ کیا واپسی کی رقم سے کاٹ لیں؟</label>
                                        <div class="custom-control custom-radio">
                                            <input type="radio" class="custom-control-input" id="{{ $formKey }}Apply" name="apply_to_balance" value="1" checked>
                                            <label class="custom-control-label" for="{{ $formKey }}Apply">ہاں، بقایا کاٹ کر باقی رقم واپس کریں</label>
                                        </div>
                                        <div class="custom-control custom-radio">
                                            <input type="radio" class="custom-control-input" id="{{ $formKey }}Keep" name="apply_to_balance" value="0">
                                            <label class="custom-control-label" for="{{ $formKey }}Keep">نہیں، بقایا گاہک کے ذمے رہے اور پوری رقم واپس کریں</label>
                                        </div>
                                    </div>
                                    @endif
    <div class="alert alert-light border return-summary mb-3">
        <div class="d-flex justify-content-between"><span>واپس ہونے والے آئٹمز کی رقم</span><strong dir="ltr" data-out="total">Rs 0.00</strong></div>
        <div class="d-flex justify-content-between"><span>اس رسید کے بقایا سے کم ہوگی <small class="text-muted">(بقایا Rs {{ number_format((float) $outstanding, 2) }})</small></span><strong dir="ltr" data-out="reduction">Rs 0.00</strong></div>
        <div class="d-flex justify-content-between text-success"><span>گاہک کو واپس دینی ہے</span><strong dir="ltr" data-out="extra">Rs 0.00</strong></div>
    </div>

    <div class="return-settlement" hidden>
        <div class="form-group mb-2">
            <label class="d-block">گاہک کو یہ رقم کیسے دیں؟</label>
            <div class="custom-control custom-radio custom-control-inline">
                <input type="radio" class="custom-control-input" id="{{ $formKey }}-refund" name="settlement_type" value="refund" checked>
                <label class="custom-control-label" for="{{ $formKey }}-refund">نقد / بینک واپسی</label>
            </div>
            <div class="custom-control custom-radio custom-control-inline">
                <input type="radio" class="custom-control-input" id="{{ $formKey }}-credit" name="settlement_type" value="credit">
                <label class="custom-control-label" for="{{ $formKey }}-credit">کھاتے میں کریڈٹ رکھیں</label>
            </div>
        </div>
        <div class="form-row return-refund-fields">
            <div class="col-md-6 form-group"><label>رقم واپسی کا طریقہ</label>
                <select name="payment_method" class="form-control"><option value="">منتخب کریں</option>@foreach (\App\Support\PaymentMethods::LABELS as $method => $label)<option value="{{ $method }}">{{ $label }}</option>@endforeach</select>
            </div>
            <div class="col-md-6 form-group"><label>ادائیگی کا حوالہ</label><input name="payment_reference" class="form-control" maxlength="255"></div>
        </div>
    </div>

    <div class="form-group"><label>واپسی کی وجہ</label><textarea name="reason" class="form-control" rows="2" minlength="5" maxlength="1000" required></textarea></div>
    <input type="hidden" name="restock" value="0">
    <div class="custom-control custom-checkbox mb-3">
        <input type="checkbox" class="custom-control-input" id="{{ $formKey }}-restock" name="restock" value="1" checked>
        <label class="custom-control-label" for="{{ $formKey }}-restock">واپس آیا کپڑا اسٹاک میں شامل کریں <small class="text-muted">(خراب ہو تو نشان ہٹا دیں)</small></label>
    </div>
    <button type="submit" class="btn btn-danger return-submit" disabled>منتخب آئٹمز واپس کریں</button>
</form>

@include('customer.partials.receipt-return-script')
