@extends('main')
@push('styles')
<style>
@media (max-width: 767.98px) {
    .inventory-movement-table, .inventory-movement-table tbody, .inventory-movement-table tr, .inventory-movement-table td { display:block; width:100%; }
    .inventory-movement-table thead { display:none; }
    .inventory-movement-table tr { border:1px solid #e3e8ee; border-radius:.65rem; margin:.75rem; padding:.35rem .75rem; width:calc(100% - 1.5rem); }
    .inventory-movement-table td { display:flex; justify-content:space-between; gap:1rem; border-top:1px solid #eef1f4; padding:.7rem 0; }
    .inventory-movement-table td:first-child { border-top:0; }
    .inventory-movement-table td::before { content:attr(data-label); flex:0 0 38%; color:#6c757d; font-weight:700; }
}
</style>
@endpush
@section('content')
<section class="main-content"><div class="container-fluid px-3 px-md-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div><h1 class="h3 mb-1">اسٹاک کھاتہ</h1><p class="text-muted mb-0">خریداری، فروخت، واپسی اور دستی درستگی کی مکمل نقل و حرکت۔</p></div>
        <div><a href="{{ route('admin.inventory-valuation.index') }}" class="btn btn-primary">اسٹاک کی مالیت</a> <a href="{{ route('admin.purchases.index') }}" class="btn btn-outline-primary">خریداریاں</a></div>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="card card-body mb-3">
        <h2 class="h5">اسٹاک میں دستی درستگی</h2>
        <p class="text-muted">صرف جسمانی گنتی، ضائع شدہ کپڑے یا پہلے سے غلط مقدار درست کرنے کے لیے استعمال کریں۔ خریداری اور فروخت اپنے متعلقہ صفحات سے درج کریں۔</p>
        <form method="POST" action="{{ route('admin.inventory-ledger.adjust') }}" data-confirm="کیا آپ اسٹاک کی یہ دستی تبدیلی درج کرنا چاہتے ہیں؟" data-confirm-variant="warning">@csrf
            <div class="form-row align-items-end">
                <div class="form-group col-lg-4"><label for="adjustment_item">اسٹاک آئٹم</label><select id="adjustment_item" name="cloth_color_id" class="form-control" required><option value="">آئٹم منتخب کریں</option>@foreach($colors as $color)<option value="{{ $color->id }}" @selected((int)old('cloth_color_id')===$color->id)>{{ $color->cloth->brand->name ?? 'برانڈ' }} / {{ $color->cloth->type->name ?? 'قسم' }} / {{ $color->color }} ({{ number_format($color->length,2) }} میٹر)</option>@endforeach</select></div>
                <div class="form-group col-lg-2"><label for="adjustment_direction">تبدیلی</label><select id="adjustment_direction" name="direction" class="form-control" required><option value="increase" @selected(old('direction')==='increase')>اسٹاک میں اضافہ</option><option value="decrease" @selected(old('direction')==='decrease')>اسٹاک میں کمی</option></select></div>
                <div class="form-group col-lg-2"><label for="adjustment_quantity">مقدار (میٹر)</label><input id="adjustment_quantity" type="number" name="quantity" step="0.01" min="0.01" value="{{ old('quantity') }}" class="form-control" required></div>
                <div class="form-group col-lg-2" id="adjustment_cost_group"><label for="adjustment_unit_cost">فی میٹر لاگت</label><input id="adjustment_unit_cost" type="number" name="unit_cost" step="0.01" min="0" value="{{ old('unit_cost') }}" class="form-control"><small class="form-text text-muted">اضافہ کیے گئے کپڑے کی اصل لاگت۔</small></div>
                <div class="form-group col-lg-2"><label for="adjustment_note">درستگی کی وجہ</label><input id="adjustment_note" name="note" maxlength="1000" value="{{ old('note') }}" class="form-control" placeholder="مثلاً جسمانی گنتی" required></div>
            </div>
            <button class="btn btn-warning">دستی تبدیلی درج کریں</button>
        </form>
    </div>

    <form class="card card-body mb-3" method="GET">
        <h2 class="h5">کھاتہ فلٹر کریں</h2>
        <div class="form-row align-items-end">
            <div class="form-group col-lg-4"><label for="ledger_item">اسٹاک آئٹم</label><select id="ledger_item" name="cloth_color_id" class="form-control"><option value="">تمام اسٹاک اشیاء</option>@foreach($colors as $color)<option value="{{ $color->id }}" @selected((int)($validated['cloth_color_id']??0)===$color->id)>{{ $color->cloth->brand->name ?? 'برانڈ' }} / {{ $color->cloth->type->name ?? 'قسم' }} / {{ $color->color }} — موجودہ {{ number_format($color->length,2) }} میٹر</option>@endforeach</select></div>
            <div class="form-group col-lg-3"><label for="ledger_type">نقل و حرکت</label><select id="ledger_type" name="movement_type" class="form-control"><option value="">تمام نقل و حرکت</option>@foreach(['purchase_receipt','purchase_return','counter_sale','online_order','online_cancellation','online_reorder','cart_reservation','cart_release','storefront_order','storefront_cancellation','storefront_return','storefront_exchange_issue','manual_adjustment_in','manual_adjustment_out'] as $type)<option value="{{ $type }}" @selected(($validated['movement_type']??'')===$type)>{{ ['purchase_receipt'=>'خریداری وصولی','purchase_return'=>'خریداری واپسی','counter_sale'=>'کاؤنٹر فروخت','online_order'=>'آن لائن آرڈر','online_cancellation'=>'آن لائن منسوخی','online_reorder'=>'آن لائن دوبارہ آرڈر','cart_reservation'=>'کارٹ میں محفوظ','cart_release'=>'کارٹ سے واپسی','storefront_order'=>'عوامی دکان آرڈر','storefront_cancellation'=>'عوامی دکان منسوخی','storefront_return'=>'عوامی دکان واپسی','storefront_exchange_issue'=>'عوامی دکان متبادل اجرا','manual_adjustment_in'=>'دستی اضافہ','manual_adjustment_out'=>'دستی کمی'][$type] }}</option>@endforeach</select></div>
            <div class="form-group col-lg-2"><label for="ledger_from">شروع تاریخ</label><input id="ledger_from" type="date" name="from_date" value="{{ $validated['from_date'] ?? '' }}" class="form-control"></div>
            <div class="form-group col-lg-2"><label for="ledger_to">آخری تاریخ</label><input id="ledger_to" type="date" name="to_date" value="{{ $validated['to_date'] ?? '' }}" class="form-control"></div>
            <div class="form-group col-lg-1"><label for="ledger_rows">قطاریں</label><select id="ledger_rows" name="per_page" class="form-control">@foreach([15,25,50,100] as $size)<option value="{{ $size }}" @selected((int)($validated['per_page']??25)===$size)>{{ $size }}</option>@endforeach</select></div>
        </div>
        <div><button class="btn btn-primary">فلٹر کریں</button> <a href="{{ route('admin.inventory-ledger.index') }}" class="btn btn-light">صاف کریں</a></div>
    </form>

    <div class="card"><div class="card-header py-2 text-muted small">کل {{ $movements->total() }} اندراجات میں سے {{ $movements->firstItem() ?? 0 }}–{{ $movements->lastItem() ?? 0 }} دکھائے جا رہے ہیں</div><div class="table-responsive"><table class="table table-hover mb-0 inventory-movement-table"><thead class="thead-light"><tr><th>تاریخ</th><th>اسٹاک آئٹم</th><th>نقل و حرکت</th><th>مقدار</th><th>بعد کا بقایا</th><th>فی میٹر لاگت</th><th>حوالہ / وجہ</th></tr></thead><tbody>
        @forelse($movements as $movement)<tr>
            <td data-label="تاریخ">{{ $movement->occurred_at->format('d-m-Y H:i') }}</td>
            <td data-label="اسٹاک آئٹم">{{ $movement->cloth->brand->name ?? 'برانڈ' }} / {{ $movement->cloth->type->name ?? 'قسم' }} / {{ $movement->clothColor->color ?? '' }}</td>
            <td data-label="نقل و حرکت">{{ ['purchase_receipt'=>'خریداری وصولی','purchase_return'=>'خریداری واپسی','counter_sale'=>'کاؤنٹر فروخت','online_order'=>'آن لائن آرڈر','online_cancellation'=>'آن لائن منسوخی','online_reorder'=>'آن لائن دوبارہ آرڈر','cart_reservation'=>'کارٹ میں محفوظ','cart_release'=>'کارٹ سے واپسی','storefront_order'=>'عوامی دکان آرڈر','storefront_cancellation'=>'عوامی دکان منسوخی','storefront_return'=>'عوامی دکان واپسی','storefront_exchange_issue'=>'عوامی دکان متبادل اجرا','manual_adjustment_in'=>'دستی اضافہ','manual_adjustment_out'=>'دستی کمی'][$movement->movement_type] ?? $movement->movement_type }}</td>
            <td data-label="مقدار" class="{{ (float)$movement->quantity>=0?'text-success':'text-danger' }}"><strong>{{ (float)$movement->quantity>=0?'+':'' }}{{ number_format($movement->quantity,2) }} میٹر</strong></td>
            <td data-label="بعد کا بقایا">{{ $movement->balance_after===null?'—':number_format($movement->balance_after,2).' میٹر' }}</td>
            <td data-label="فی میٹر لاگت">{{ $movement->unit_cost!==null?'روپے '.number_format($movement->unit_cost,2):'—' }}</td>
            <td data-label="حوالہ / وجہ">{{ $movement->note ?: '—' }}</td>
        </tr>@empty<tr><td colspan="7" class="text-center text-muted py-5">منتخب فلٹر کے مطابق اسٹاک کی کوئی نقل و حرکت موجود نہیں۔</td></tr>@endforelse
    </tbody></table></div>@if($movements->hasPages())<div class="card-footer">{{ $movements->links() }}</div>@endif</div>
</div></section>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const direction = document.getElementById('adjustment_direction');
    const costGroup = document.getElementById('adjustment_cost_group');
    const unitCost = document.getElementById('adjustment_unit_cost');
    if (!direction || !costGroup || !unitCost) return;

    const syncAdjustmentCost = function () {
        const increasing = direction.value === 'increase';
        costGroup.hidden = !increasing;
        unitCost.required = increasing;
        unitCost.disabled = !increasing;
    };
    direction.addEventListener('change', syncAdjustmentCost);
    syncAdjustmentCost();
});
</script>
@endpush
