@extends('main')
@push('styles')
<style>
@media (max-width: 767.98px) {
    .inventory-valuation-table, .inventory-valuation-table tbody, .inventory-valuation-table tr, .inventory-valuation-table td { display:block; width:100%; }
    .inventory-valuation-table thead { display:none; }
    .inventory-valuation-table tr { border:1px solid #e3e8ee; border-radius:.65rem; margin:.75rem; padding:.35rem .75rem; width:calc(100% - 1.5rem); }
    .inventory-valuation-table td { display:flex; justify-content:space-between; gap:1rem; border-top:1px solid #eef1f4; padding:.7rem 0; }
    .inventory-valuation-table td:first-child { border-top:0; }
    .inventory-valuation-table td::before { content:attr(data-label); flex:0 0 42%; color:#6c757d; font-weight:700; }
}
</style>
@endpush
@section('content')
<section class="main-content"><div class="container-fluid px-3 px-md-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3"><div><h1 class="h3 mb-1">اسٹاک کی مالیت</h1><p class="text-muted mb-0">موجودہ مقدار، اوسط لاگت اور فروختی قیمت کے مطابق تخمینہ۔</p></div><a href="{{ route('admin.inventory-ledger.index') }}" class="btn btn-outline-primary">اسٹاک کھاتہ</a></div>
    <form method="GET" class="card card-body mb-3"><div class="form-row align-items-end"><div class="form-group col-md-8 mb-md-0"><label for="valuation_search">اسٹاک تلاش کریں</label><input id="valuation_search" name="q" maxlength="100" class="form-control" value="{{ $validated['q'] ?? '' }}" placeholder="برانڈ، کپڑے کی قسم یا رنگ"></div><div class="form-group col-md-2 mb-md-0"><label for="valuation_rows">قطاریں</label><select id="valuation_rows" name="per_page" class="form-control">@foreach([15,25,50,100] as $size)<option value="{{ $size }}" @selected((int)($validated['per_page']??25)===$size)>{{ $size }}</option>@endforeach</select></div><div class="col-md-2"><button class="btn btn-primary">فلٹر کریں</button> <a class="btn btn-light" href="{{ route('admin.inventory-valuation.index') }}">صاف کریں</a></div></div></form>
    <div class="row mb-3">@foreach([['موجودہ مقدار',number_format($totals['meters'],2).' میٹر','primary'],['اسٹاک کی لاگت','روپے '.number_format($totals['cost'],2),'info'],['فروختی مالیت','روپے '.number_format($totals['retail'],2),'success'],['ممکنہ مجموعی منافع','روپے '.number_format($totals['margin'],2),$totals['margin']>=0?'success':'danger']] as [$label,$value,$color])<div class="col-6 col-lg-3 mb-2"><div class="card h-100"><div class="card-body"><small class="text-muted">{{ $label }}</small><div class="h5 text-{{ $color }} mb-0">{{ $value }}</div></div></div></div>@endforeach</div>
    <div class="alert alert-light border text-muted">ممکنہ منافع صرف موجودہ فروختی قیمت اور اوسط لاگت کا تخمینہ ہے؛ رعایت، خرچ اور اصل فروخت کے بعد رقم مختلف ہو سکتی ہے۔</div>
    <div class="card"><div class="card-header py-2 text-muted small">کل {{ $colors->total() }} اسٹاک اشیاء میں سے {{ $colors->firstItem() ?? 0 }}–{{ $colors->lastItem() ?? 0 }} دکھائی جا رہی ہیں</div><div class="table-responsive"><table class="table table-hover mb-0 inventory-valuation-table"><thead class="thead-light"><tr><th>برانڈ</th><th>قسم</th><th>رنگ</th><th>موجودہ مقدار</th><th>اوسط لاگت</th><th>کل لاگت</th><th>فروختی قیمت</th><th>فروختی مالیت</th><th>ممکنہ منافع</th></tr></thead><tbody>
        @forelse($colors as $color)@php($costValue=(float)$color->length*(float)$color->average_unit_cost) @php($retailValue=(float)$color->length*(float)($color->cloth->sale_price??0))<tr>
            <td data-label="برانڈ">{{ $color->cloth->brand->name ?? '—' }}</td><td data-label="قسم">{{ $color->cloth->type->name ?? '—' }}</td><td data-label="رنگ">{{ $color->color }}</td><td data-label="موجودہ مقدار">{{ number_format($color->length,2) }} میٹر</td><td data-label="اوسط لاگت">روپے {{ number_format($color->average_unit_cost,2) }}</td><td data-label="کل لاگت">روپے {{ number_format($costValue,2) }}</td><td data-label="فروختی قیمت">روپے {{ number_format($color->cloth->sale_price??0,2) }}</td><td data-label="فروختی مالیت">روپے {{ number_format($retailValue,2) }}</td><td data-label="ممکنہ منافع">روپے {{ number_format($retailValue-$costValue,2) }}</td>
        </tr>@empty<tr><td colspan="9" class="text-center text-muted py-5">منتخب فلٹر کے مطابق کپڑے کا کوئی اسٹاک موجود نہیں۔</td></tr>@endforelse
    </tbody></table></div>@if($colors->hasPages())<div class="card-footer">{{ $colors->links() }}</div>@endif</div>
</div></section>
@endsection
