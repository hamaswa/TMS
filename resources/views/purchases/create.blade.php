@extends('main')
@push('styles')
    @include('purchases._styles')
    <style>
        .purchase-create-grid{display:grid;grid-template-columns:minmax(0,1fr) 290px;gap:20px}.purchase-order-summary{position:sticky;top:88px;height:max-content;padding:22px}.purchase-order-summary .total{direction:ltr;color:#1769ef;font:800 1.55rem Arial,sans-serif}.purchase-item-table input,.purchase-item-table select{min-width:130px}.purchase-remove{display:grid;place-items:center;width:42px;height:42px;border-radius:8px}.purchase-add-row{border-style:dashed!important}@media(max-width:991.98px){.purchase-create-grid{grid-template-columns:1fr}.purchase-order-summary{position:static}}
    </style>
@endpush
@section('content')
<section class="main-content purchase-page" dir="rtl"><div class="purchase-shell">
    <header class="purchase-page-header"><div class="purchase-title-wrap"><span class="purchase-title-icon"><i class="fas fa-cart-plus"></i></span><div><h1 class="h4 mb-0">نئی خریداری</h1><div class="purchase-breadcrumb"><a href="{{ route('admin.purchases.index') }}">خریداری کی فہرست</a> <span class="mx-2">•</span> نیا آرڈر</div></div></div><a href="{{ route('admin.purchases.index') }}" class="btn btn-outline-secondary">واپس جائیں</a></header>
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @if($suppliers->isEmpty() || $colors->isEmpty())<div class="alert alert-warning">خریداری بنانے سے پہلے کم از کم ایک فعال سپلائر اور کپڑے کا ایک رنگ شامل کریں۔</div>@endif

    <form method="POST" action="{{ route('admin.purchases.store') }}" id="purchase-create-form">@csrf
        <div class="purchase-create-grid">
            <div>
                <div class="purchase-panel purchase-form-card mb-3">
                    <h2 class="purchase-section-title"><i class="fas fa-file-invoice"></i> خریداری کی معلومات</h2>
                    <div class="form-row"><div class="form-group col-md-4"><label for="purchase_supplier_create">سپلائر</label><select id="purchase_supplier_create" name="supplier_id" class="form-control" required><option value="">سپلائر منتخب کریں</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected(old('supplier_id')==$supplier->id)>{{ $supplier->name }}</option>@endforeach</select></div><div class="form-group col-md-3"><label for="purchase_date_create">خریداری کی تاریخ</label><input id="purchase_date_create" type="date" name="purchase_date" value="{{ old('purchase_date', now()->toDateString()) }}" class="form-control" required></div><div class="form-group col-md-5"><label for="purchase_reference_create">سپلائر حوالہ / بل نمبر</label><input id="purchase_reference_create" name="reference" maxlength="255" value="{{ old('reference') }}" class="form-control" placeholder="اختیاری حوالہ"></div></div>
                </div>
                <div class="purchase-panel purchase-form-card mb-3">
                    <h2 class="purchase-section-title"><i class="fas fa-boxes"></i> خریداری کی اشیاء</h2>
                    <div class="table-responsive"><table class="table purchase-item-table" id="items-table"><thead class="thead-light"><tr><th>کپڑا / رنگ</th><th style="width:18%">میٹر</th><th style="width:18%">فی میٹر لاگت</th><th style="width:16%">کل</th><th style="width:7%"></th></tr></thead><tbody id="purchase-items">
                        <tr class="purchase-item"><td><select name="cloth_color_id[]" class="form-control" required aria-label="کپڑا اور رنگ منتخب کریں"><option value="">اسٹاک آئٹم منتخب کریں</option>@foreach($colors as $color)<option value="{{ $color->id }}">{{ $color->cloth->brand->name ?? 'برانڈ' }} / {{ $color->cloth->type->name ?? 'قسم' }} / {{ $color->color }} ({{ number_format($color->length,2) }} میٹر)</option>@endforeach</select></td><td><input type="number" step="0.01" min="0.01" name="quantity[]" class="form-control purchase-quantity" required aria-label="خریداری کی مقدار میٹر میں"></td><td><input type="number" step="0.01" min="0" name="unit_cost[]" class="form-control purchase-cost" required aria-label="فی میٹر لاگت"></td><td><strong class="purchase-line-total" dir="ltr">Rs. 0.00</strong></td><td><button type="button" class="btn btn-outline-danger purchase-remove remove-item" aria-label="یہ آئٹم ہٹائیں"><i class="fas fa-trash-alt"></i></button></td></tr>
                    </tbody></table></div>
                    <button type="button" id="add-item" class="btn btn-outline-primary purchase-add-row"><i class="fas fa-plus ml-1"></i> نئی قطار شامل کریں</button>
                </div>
                <div class="purchase-panel purchase-form-card"><div class="form-group mb-0"><label for="purchase_note_create">نوٹ</label><textarea id="purchase_note_create" name="note" class="form-control" maxlength="1000" placeholder="خریداری سے متعلق اختیاری نوٹ">{{ old('note') }}</textarea></div></div>
            </div>
            <aside class="purchase-panel purchase-order-summary"><h2 class="purchase-section-title"><i class="fas fa-calculator"></i> آرڈر خلاصہ</h2><div class="d-flex justify-content-between text-muted mb-2"><span>آئٹمز</span><strong id="purchase-item-count">1</strong></div><hr><small class="text-muted d-block mb-2">کل خریداری</small><div class="total" id="purchase-grand-total">Rs. 0.00</div><div class="purchase-action-bar"><button class="purchase-primary-btn w-100" @disabled($suppliers->isEmpty() || $colors->isEmpty())><i class="fas fa-save"></i> خریداری کا مسودہ بنائیں</button><a href="{{ route('admin.purchases.index') }}" class="btn btn-light w-100">منسوخ کریں</a></div></aside>
        </div>
    </form>
</div></section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const body = document.getElementById('purchase-items');
    const count = document.getElementById('purchase-item-count');
    const grandTotal = document.getElementById('purchase-grand-total');
    const updateTotals = function () {
        let total = 0;
        body.querySelectorAll('.purchase-item').forEach(function (row) {
            const line = (parseFloat(row.querySelector('.purchase-quantity').value) || 0) * (parseFloat(row.querySelector('.purchase-cost').value) || 0);
            row.querySelector('.purchase-line-total').textContent = 'Rs. ' + line.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}); total += line;
        });
        count.textContent = body.querySelectorAll('.purchase-item').length;
        grandTotal.textContent = 'Rs. ' + total.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
    };
    document.getElementById('add-item').addEventListener('click', function () { const row=body.querySelector('.purchase-item').cloneNode(true); row.querySelectorAll('input,select').forEach(function(element){element.value='';}); body.appendChild(row); updateTotals(); });
    body.addEventListener('click', function(event){const button=event.target.closest('.remove-item');if(button && body.querySelectorAll('.purchase-item').length>1){button.closest('tr').remove();updateTotals();}});
    body.addEventListener('input', updateTotals); updateTotals();
});
</script>
@endsection
