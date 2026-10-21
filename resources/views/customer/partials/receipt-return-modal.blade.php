{{--
    Return / cancel popup for one shop receipt on the customer statement.
    Step 1: pick the item(s) that came back. Step 2 (shown after picking): the customer's dues, cash given back, reason.
    Expects: $sale (statement shop row), $customer, $modalId, $printUrl.
--}}
@php
    $lines = collect($sale->items ?? [])->map->returnLine();
    $canReturn = ($sale->receipt ?? null) && $sale->status !== 'cancelled';
    $formId = $modalId.'Form';
    $shopName = once(fn () => \App\Models\Setting::where('user_id', auth()->user()->businessOwnerId())->value('name')
        ?: (auth()->user()->business?->name ?: auth()->user()->name));
    // The customer's dues on other orders, so the return can come off them instead of being paid out.
    $canSeeDues = auth()->user()->hasBusinessPermission(\App\Models\BusinessRole::CUSTOMER_BALANCES);
    $receiptDues = max(0, round((float) $sale->balance, 2));
    $otherDues = $canSeeDues
        ? max(0, round((float) \App\Models\Transaction::where('userId', auth()->user()->businessOwnerId())
            ->where('customerId', $customer->id)->sum('remainingBalance') - max(0, (float) $sale->balance), 2))
        : 0;
@endphp
<div class="modal fade rr-modal" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content" dir="rtl">
            <div class="rr-header">
                <span class="rr-header-icon"><i class="fas fa-undo-alt"></i></span>
                <div class="flex-grow-1">
                    <h5 class="mb-0">آئٹم واپسی · <span dir="ltr">{{ $sale->display_number }}</span></h5>
                    <small>جو آئٹم واپس آیا ہے اسے منتخب کریں</small>
                </div>
                <button type="button" class="close rr-close" data-dismiss="modal" aria-label="بند کریں">&times;</button>
            </div>

            <div class="rr-body">
                <div class="row">
                    {{-- Step 1 + 2 (right side in RTL) --}}
                    <div class="col-lg-7 mb-3 mb-lg-0">
                        <div class="rr-card">
                            @if ($canReturn)
                                <form method="POST" id="{{ $formId }}" action="{{ route('admin.counter-sales.returns.store', $sale->receipt) }}"
                                    class="receipt-return-form" data-outstanding="{{ $receiptDues }}" data-other-dues="{{ $otherDues }}"
                                    data-confirm="کیا آپ منتخب آئٹم واپس کرنا چاہتے ہیں؟">
                                    @csrf
                                    <div class="rr-step"><span>1</span> کون سا آئٹم واپس آیا؟</div>
                                    @foreach ($lines as $line)
                                        <div class="rr-row {{ $line->remaining <= 0 ? 'rr-row--done' : '' }}" data-return-row>
                                            <div class="rr-row-info">
                                                <strong>{{ $line->name }}</strong>
                                                <small>{{ $line->soldLabel }} · <span dir="ltr">Rs {{ number_format($line->lineTotal, 2) }}</span></small>
                                                @if ($line->returned > 0 && $line->remaining > 0)
                                                    <small class="text-danger">پہلے واپس: {{ $line->returnedLabel }} {{ $line->unit }}</small>
                                                @endif
                                            </div>
                                            @if ($line->remaining > 0)
                                                <div class="rr-qty {{ $line->remaining > 1 || ! $line->perSuit ? '' : 'd-none' }}">
                                                    @if ($line->perSuit)<button type="button" class="btn btn-sm btn-light rr-step-btn" data-step="-1" aria-label="ایک کم">−</button>@endif
                                                    <input type="number" class="form-control form-control-sm return-qty" name="items[{{ $line->id }}]" value="0"
                                                        min="0" max="{{ $line->remaining }}" step="{{ $line->perSuit ? 1 : 0.01 }}"
                                                        data-unit-price="{{ $line->unitPrice }}" data-remaining="{{ $line->remaining }}" dir="ltr"
                                                        aria-label="واپسی {{ $line->unit }}">
                                                    @if ($line->perSuit)<button type="button" class="btn btn-sm btn-light rr-step-btn" data-step="1" aria-label="ایک زیادہ">+</button>@endif
                                                    <small>{{ $line->unit }} <span class="text-muted">/ {{ rtrim(rtrim(number_format($line->remaining, 2), '0'), '.') }}</span></small>
                                                </div>
                                                <label class="rr-pick mb-0">
                                                    <input type="checkbox" class="return-pick">
                                                    <span class="rr-pick-off"><i class="fas fa-undo-alt ml-1"></i> واپس کریں</span>
                                                    <span class="rr-pick-on"><i class="fas fa-check ml-1"></i> منتخب</span>
                                                </label>
                                            @else
                                                <span class="rr-done-badge">واپس ہو چکا</span>
                                            @endif
                                        </div>
                                    @endforeach

                                    <div class="return-details" hidden>
                                        <div class="rr-step mt-3"><span>2</span> رقم کا حساب</div>
                                        <div class="form-group mb-2">
                                            <label for="{{ $formId }}Cash" class="font-weight-bold mb-1">گاہک کو کتنے پیسے واپس دے رہے ہیں؟</label>
                                            <input type="number" id="{{ $formId }}Cash" name="refund_amount" class="form-control return-cash"
                                                min="0" step="0.01" placeholder="رقم لکھیں، کچھ نہیں دے رہے تو 0" dir="ltr">
                                            <div class="rr-cash-note mt-1" data-cash-note hidden></div>
                                        </div>

                                        <div class="return-settlement mb-2" hidden>
                                            <div class="form-row return-refund-fields">
                                                <div class="col-6 form-group mb-2">
                                                    <select name="payment_method" class="form-control form-control-sm" aria-label="رقم واپسی کا طریقہ">@foreach (\App\Support\PaymentMethods::LABELS as $method => $label)<option value="{{ $method }}" @selected($method === 'cash')>{{ $label }}</option>@endforeach</select>
                                                </div>
                                                <div class="col-6 form-group mb-2"><input name="payment_reference" class="form-control form-control-sm" maxlength="255" placeholder="حوالہ (اگر ہو)"></div>
                                            </div>
                                        </div>

                                        <input name="reason" class="form-control mb-2" minlength="5" maxlength="1000" required placeholder="واپسی کی وجہ، مثلاً رنگ پسند نہیں آیا">
                                        <input type="hidden" name="restock" value="0">
                                        <div class="custom-control custom-checkbox small">
                                            <input type="checkbox" class="custom-control-input" id="{{ $formId }}Restock" name="restock" value="1" checked>
                                            <label class="custom-control-label" for="{{ $formId }}Restock">کپڑا اسٹاک میں واپس رکھیں (خراب ہو تو نشان ہٹا دیں)</label>
                                        </div>
                                    </div>
                                </form>
                            @else
                                <div class="alert alert-info mb-0">اس رسید سے الگ الگ آئٹم واپس نہیں ہو سکتے؛ پوری رسید منسوخ کریں۔</div>
                            @endif

                            @if ($sale->status !== 'cancelled')
                                <div class="mt-3 pt-2 border-top">
                                    <a class="small text-muted" data-toggle="collapse" href="#{{ $modalId }}Cancel" role="button" aria-expanded="{{ $canReturn ? 'false' : 'true' }}">
                                        <i class="fas fa-ban ml-1"></i> پوری رسید منسوخ کرنی ہے؟
                                    </a>
                                    <div class="collapse {{ $canReturn ? '' : 'show' }}" id="{{ $modalId }}Cancel">
                                        <form method="POST" action="{{ route('admin.counter-sales.cancel', $sale->id) }}" class="border rounded p-3 mt-2"
                                            data-confirm="کیا آپ یہ پوری رسید منسوخ کر کے اسٹاک اور کھاتا واپس کرنا چاہتے ہیں؟">
                                            @csrf @method('PATCH')
                                            <div class="form-group"><label>منسوخی کی وجہ</label><textarea class="form-control" name="cancellation_reason" rows="2" minlength="5" maxlength="1000" required></textarea></div>
                                            @include('customer.partials.cancellation-settlement-fields', ['paidAmount' => max(0, (float) $sale->received), 'allowCompensation' => false, 'methodName' => 'refund_method', 'referenceName' => 'refund_reference'])
                                            <button type="submit" class="btn btn-outline-danger btn-sm mt-2">پوری رسید منسوخ کریں</button>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Receipt (left side in RTL) --}}
                    <div class="col-lg-5">
                        <div class="rr-card rr-receipt">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <strong>{{ $shopName }}</strong><br>
                                    <small class="text-muted" dir="ltr">{{ $sale->created_at?->format('d-m-Y') }}</small>
                                </div>
                                <div class="text-left small">
                                    <strong>{{ $customer->name }}</strong><br>
                                    @if ($customer->phone_number1)<span dir="ltr" class="text-muted">{{ $customer->phone_number1 }}</span>@endif
                                </div>
                            </div>
                            @foreach ($lines as $line)
                                <div class="rr-receipt-line {{ $line->remaining <= 0 ? 'rr-receipt-line--returned' : '' }}">
                                    <span>{{ $line->name }} @if ($line->returned > 0)<small class="text-danger">↩ واپس</small>@endif</span>
                                    <span dir="ltr">Rs {{ number_format($line->lineTotal, 2) }}</span>
                                </div>
                            @endforeach
                            <div class="rr-receipt-total mt-2">
                                <div><span>کل رقم</span><strong dir="ltr">Rs {{ number_format((float) $sale->amount, 2) }}</strong></div>
                                <div><span>وصول شدہ</span><strong dir="ltr">Rs {{ number_format((float) $sale->received, 2) }}</strong></div>
                                @if ($canSeeDues)
                                    <div><span>اس رسید کا بقایا</span><strong dir="ltr">Rs {{ number_format($receiptDues, 2) }}</strong></div>
                                    <div><span>پچھلا بقایا</span><strong dir="ltr">Rs {{ number_format($otherDues, 2) }}</strong></div>
                                    <div class="rr-due"><span>کل بقایا</span><strong dir="ltr">Rs {{ number_format($receiptDues + $otherDues, 2) }}</strong></div>
                                @else
                                    <div class="rr-due"><span>بقایا</span><strong dir="ltr">Rs {{ number_format($receiptDues, 2) }}</strong></div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rr-footer">
                <div class="d-flex flex-wrap" style="gap:.5rem">
                    @if ($canReturn)
                        <button type="submit" form="{{ $formId }}" class="btn btn-danger return-submit" disabled>
                            <i class="fas fa-undo-alt ml-1"></i> واپس کریں (<span data-count-for="{{ $formId }}">0</span>)
                        </button>
                    @endif
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">بند کریں</button>
                </div>
                <a href="{{ $printUrl }}" target="_blank" class="btn btn-outline-primary"><i class="fas fa-print ml-1"></i> رسید پرنٹ کریں</a>
            </div>
        </div>
    </div>
</div>

@once
<style>
    .rr-modal .modal-content { border: 0; border-radius: 14px; overflow: hidden; }
    .rr-header { display: flex; align-items: center; gap: .9rem; padding: .9rem 1.3rem; background: linear-gradient(135deg, #17406b, #1f5a8f); }
    .rr-header h5, .rr-header .rr-header-icon { color: #fff; }
    .rr-header small { color: rgba(255, 255, 255, .8); }
    .rr-header-icon { width: 40px; height: 40px; border-radius: 10px; background: rgba(255, 255, 255, .15); display: inline-flex; align-items: center; justify-content: center; }
    .rr-close { color: #fff; opacity: .9; text-shadow: none; margin: 0; }
    .rr-close:hover { color: #fff; }
    .rr-body { background: #f3f6fa; padding: 1.1rem; }
    .rr-card { background: #fff; border: 1px solid #e1e8f0; border-radius: 12px; padding: 1rem; }
    .rr-step { font-weight: 700; margin-bottom: .6rem; }
    .rr-step span { display: inline-flex; width: 1.5rem; height: 1.5rem; border-radius: 50%; background: #1f5a8f; color: #fff; align-items: center; justify-content: center; font-size: .8rem; margin-left: .35rem; }
    .rr-row { display: flex; align-items: center; gap: .75rem; padding: .65rem .75rem; border: 1px solid #e1e8f0; border-radius: 10px; margin-bottom: .5rem; transition: background .15s, border-color .15s; }
    .rr-row:has(.return-pick:checked) { background: #fff4f4; border-color: #f1aeb5; }
    .rr-row--done { background: #f7f8fa; color: #8a94a3; }
    .rr-row-info { flex: 1; display: flex; flex-direction: column; line-height: 1.6; }
    .rr-row-info small { color: #6c757d; }
    .rr-qty { display: none; align-items: center; gap: .3rem; }
    .rr-qty input { width: 4.5rem; }
    .rr-step-btn { width: 2rem; font-weight: 700; padding: .15rem 0; }
    .rr-row:has(.return-pick:checked) .rr-qty:not(.d-none) { display: flex; }
    .rr-pick { position: relative; cursor: pointer; border: 1px solid #dc3545; color: #dc3545; border-radius: 8px; padding: .3rem .8rem; white-space: nowrap; font-size: .9rem; }
    .rr-pick input { position: absolute; opacity: 0; pointer-events: none; }
    .rr-pick-on { display: none; }
    .rr-pick:has(input:checked) { background: #dc3545; color: #fff; }
    .rr-pick:has(input:checked) .rr-pick-on { display: inline; }
    .rr-pick:has(input:checked) .rr-pick-off { display: none; }
    .rr-done-badge { font-size: .8rem; background: #e9ecef; border-radius: 6px; padding: .2rem .6rem; }
    .rr-receipt-line { display: flex; justify-content: space-between; gap: .5rem; padding: .35rem 0; border-bottom: 1px dashed #e1e8f0; font-size: .9rem; }
    .rr-receipt-line--returned [dir="ltr"] { text-decoration: line-through; color: #8a94a3; }
    .rr-receipt-total > div { display: flex; justify-content: space-between; padding: .2rem 0; }
    .rr-cash-note { font-weight: 700; color: #17406b; }
    .rr-cash-note.is-error { color: #dc3545; }
    .rr-due { background: #eef4ff; border-radius: 8px; padding: .45rem .6rem !important; margin-top: .3rem; font-size: 1.05rem; }
    .rr-footer { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: .5rem; padding: .8rem 1.1rem; border-top: 1px solid #e1e8f0; background: #fff; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Mirror each return form's selected-item count onto its footer button.
    document.querySelectorAll('[data-count-for]').forEach(function (target) {
        var form = document.getElementById(target.dataset.countFor);
        if (!form) return;
        var sync = function () { target.textContent = form.querySelectorAll('.return-pick:checked').length; };
        form.addEventListener('input', sync);
        form.addEventListener('change', sync);
    });
});
</script>
@endonce
@include('customer.partials.receipt-return-script')
