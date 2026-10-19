{{--
    Receipt + return/cancel actions for one shop sale row.
    Expects: $sale (CustomerSalesHistoryService row), $customer.
    Optional: $asMenu (true = a ⋮ dropdown menu instead of inline buttons),
              $canPay (menu only: also offer "receive payment" for this sale's receipt).
--}}
@php
    $asMenu = $asMenu ?? false;
    $canPay = ($canPay ?? false) && ($sale->receipt ?? null) && $sale->status !== 'cancelled' && $sale->balance > 0;
    $canReturn = $sale->status !== 'cancelled';
    if ($sale->counter_order_id) {
        $receiptUrl = route('admin.counter-orders.print', $sale->counter_order_id);
        $receiptInNewTab = true;
        $returnModalId = 'shopCancelCounter'.$sale->id;
    } elseif ($sale->source === 'stock') {
        $receiptUrl = route('admin.printStock', ['id' => $sale->id, 'customerId' => $customer->id]);
        $receiptInNewTab = true;
        $returnModalId = 'shopCancelStock'.$sale->id;
    } elseif ($sale->source === 'legacy') {
        $receiptUrl = route('admin.sale.show', $sale->id);
        $receiptInNewTab = false;
        $returnModalId = 'shopCancelLegacy'.$sale->id;
    } else {
        $receiptUrl = null;
    }
@endphp
@if (! $receiptUrl)
    <span class="text-muted">—</span>
@else
    @if ($asMenu)
        <div class="sale-actions-dropdown">
            <button type="button" class="btn btn-light btn-sm sale-actions-toggle" aria-haspopup="true" aria-expanded="false"
                aria-label="فروخت {{ $sale->display_number }} کے اختیارات"><i class="fas fa-ellipsis-v"></i></button>
            <div class="sale-actions-menu" role="menu" dir="rtl">
                @if ($canPay)
                    <button type="button" class="sale-action sale-payment-button" role="menuitem"
                        data-toggle="modal" data-target="#myModalpayment"
                        data-customerid="{{ $customer->id }}" data-receiptid="{{ $sale->receipt->id }}"
                        data-number="{{ $sale->display_number }}" data-remaining="{{ round($sale->balance, 2) }}">
                        <i class="fas fa-wallet"></i> رقم وصول کریں
                    </button>
                @endif
                <a class="sale-action" role="menuitem" href="{{ $receiptUrl }}" @if ($receiptInNewTab) target="_blank" @endif>
                    <i class="fas fa-print"></i> رسید دیکھیں / پرنٹ کریں
                </a>
                @if ($canReturn)
                    <button type="button" class="sale-action sale-action--return" role="menuitem" data-toggle="modal" data-target="#{{ $returnModalId }}">
                        <i class="fas fa-undo-alt"></i> واپسی / منسوخی
                    </button>
                @endif
            </div>
        </div>
    @else
        <div class="d-flex flex-wrap" style="gap:.35rem">
            <a class="btn btn-sm btn-outline-primary" href="{{ $receiptUrl }}" @if ($receiptInNewTab) target="_blank" @endif>رسید</a>
            @if ($canReturn)
                <button type="button" class="btn btn-sm btn-outline-danger" data-toggle="modal" data-target="#{{ $returnModalId }}">واپسی / منسوخی</button>
            @endif
        </div>
    @endif

    @if ($canReturn)
        @if ($sale->source === 'legacy')
            <div class="modal fade" id="{{ $returnModalId }}" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog" role="document"><div class="modal-content text-right" dir="rtl"><div class="modal-header"><h5 class="modal-title">فروخت {{ $sale->display_number }} واپس / منسوخ کریں</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div><form method="POST" action="{{ route('admin.sale.destroy', $sale->id) }}">@csrf @method('DELETE')<div class="modal-body"><div class="form-group"><label>واپسی / منسوخی کی وجہ</label><textarea class="form-control" name="cancellation_reason" minlength="5" maxlength="1000" required></textarea></div>@if($sale->received > 0)<div class="form-group"><label>رقم واپسی کا طریقہ</label><select name="refund_method" class="form-control" required><option value="">منتخب کریں</option>@foreach(\App\Support\PaymentMethods::LABELS as $method => $label)<option value="{{ $method }}">{{ $label }}</option>@endforeach</select></div><div class="form-group"><label>ادائیگی کا حوالہ</label><input class="form-control" name="refund_reference" maxlength="255"></div>@endif</div><div class="modal-footer"><button class="btn btn-danger">واپسی / منسوخی کی تصدیق</button></div></form></div></div></div>
        @else
            @include('customer.partials.receipt-return-modal', ['sale' => $sale, 'customer' => $customer, 'modalId' => $returnModalId, 'printUrl' => $receiptUrl])
        @endif
    @endif
@endif
