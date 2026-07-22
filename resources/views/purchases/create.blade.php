@extends('main')
@section('content')
<section class="main-content"><div class="container-fluid px-3 px-md-4"><div class="card"><div class="card-header"><h4 class="mb-0">نیا خریداری آرڈر</h4></div><div class="card-body">
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @if($suppliers->isEmpty() || $colors->isEmpty())<div class="alert alert-warning">خریداری بنانے سے پہلے کم از کم ایک فعال سپلائر اور کپڑے کا ایک رنگ شامل کریں۔</div>@endif
    <form method="POST" action="{{ route('admin.purchases.store') }}">@csrf
        <div class="form-row"><div class="form-group col-md-4"><label>سپلائر</label><select name="supplier_id" class="form-control" required><option value="">سپلائر منتخب کریں</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected(old('supplier_id')==$supplier->id)>{{ $supplier->name }}</option>@endforeach</select></div><div class="form-group col-md-3"><label>خریداری کی تاریخ</label><input type="date" name="purchase_date" value="{{ old('purchase_date', now()->toDateString()) }}" class="form-control" required></div><div class="form-group col-md-5"><label>سپلائر حوالہ</label><input name="reference" value="{{ old('reference') }}" class="form-control"></div></div>
        <div class="table-responsive"><table class="table" id="items-table"><thead class="thead-light"><tr><th>کپڑا / رنگ</th><th style="width:18%">میٹر</th><th style="width:18%">فی میٹر لاگت</th><th style="width:8%"></th></tr></thead><tbody id="purchase-items">
            <tr class="purchase-item"><td><select name="cloth_color_id[]" class="form-control" required><option value="">اسٹاک آئٹم منتخب کریں</option>@foreach($colors as $color)<option value="{{ $color->id }}">{{ $color->cloth->brand->name ?? 'برانڈ' }} / {{ $color->cloth->type->name ?? 'قسم' }} / {{ $color->color }} ({{ number_format($color->length,2) }} میٹر)</option>@endforeach</select></td><td><input type="number" step="0.01" min="0.01" name="quantity[]" class="form-control" required></td><td><input type="number" step="0.01" min="0" name="unit_cost[]" class="form-control" required></td><td><button type="button" class="btn btn-outline-danger remove-item">×</button></td></tr>
        </tbody></table></div>
        <button type="button" id="add-item" class="btn btn-outline-secondary mb-3">نئی قطار</button>
        <div class="form-group"><label>نوٹ</label><textarea name="note" class="form-control" maxlength="1000">{{ old('note') }}</textarea></div>
        <button class="btn btn-primary" @disabled($suppliers->isEmpty() || $colors->isEmpty())>خریداری کا مسودہ بنائیں</button> <a href="{{ route('admin.purchases.index') }}" class="btn btn-light">منسوخ کریں</a>
    </form>
</div></div></div></section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const body = document.getElementById('purchase-items');
    document.getElementById('add-item').addEventListener('click', function () {
        const row = body.querySelector('.purchase-item').cloneNode(true);
        row.querySelectorAll('input,select').forEach(element => element.value = '');
        body.appendChild(row);
    });
    body.addEventListener('click', function (event) {
        if (event.target.classList.contains('remove-item') && body.querySelectorAll('.purchase-item').length > 1) event.target.closest('tr').remove();
    });
});
</script>
@endsection
