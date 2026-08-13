@extends('main')

@push('styles')
<style>
    .cloth-catalog-page{--cloth-blue:#1769ef;--cloth-ink:#14213d;--cloth-muted:#718096;--cloth-line:#e1e8f2;min-height:calc(100vh - 65px);padding:27px 0 48px;background:#f7f9fc;color:var(--cloth-ink)}
    .cloth-catalog-shell{max-width:1560px;margin:auto;padding:0 24px}.cloth-breadcrumb{margin-bottom:12px;color:var(--cloth-muted);font-size:.84rem}.cloth-breadcrumb a{color:inherit}.cloth-header{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:20px}.cloth-heading{display:flex;align-items:center;gap:14px}.cloth-heading-icon{display:grid;place-items:center;width:52px;height:52px;border:1px solid var(--cloth-line);border-radius:13px;background:#fff;color:var(--cloth-blue);font-size:21px;box-shadow:0 5px 18px rgba(25,67,120,.06)}.cloth-heading h1{margin:0 0 4px;font-size:1.6rem;font-weight:800}.cloth-heading p{margin:0;color:var(--cloth-muted)}.cloth-header-actions{display:flex;gap:10px;flex-wrap:wrap}.cloth-header-actions .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:44px;padding:9px 16px;border-radius:8px;font-weight:700}.cloth-primary{border:0;background:linear-gradient(135deg,#1769ef,#287fff);color:#fff!important;box-shadow:0 8px 20px rgba(23,105,239,.2)}
    .cloth-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:15px;margin-bottom:18px}.cloth-stat{display:flex;align-items:center;justify-content:space-between;min-height:112px;padding:20px;border:1px solid var(--cloth-line);border-radius:13px;background:#fff;box-shadow:0 5px 20px rgba(28,63,105,.045)}.cloth-stat small{display:block;color:var(--cloth-muted);font-weight:700}.cloth-stat strong{display:block;margin-top:7px;color:var(--cloth-ink);font:800 1.18rem/1.3 Arial,sans-serif;direction:ltr}.cloth-stat-icon{display:grid;place-items:center;width:54px;height:54px;border-radius:50%;font-size:21px}.cloth-stat:nth-child(1) .cloth-stat-icon{background:#eaf2ff;color:#1769ef}.cloth-stat:nth-child(2) .cloth-stat-icon{background:#e8f9f0;color:#18a866}.cloth-stat:nth-child(3) .cloth-stat-icon{background:#fff3e8;color:#e77817}.cloth-stat:nth-child(4) .cloth-stat-icon{background:#f1eaff;color:#7e48df}
    .cloth-panel{overflow:hidden;border:1px solid var(--cloth-line);border-radius:13px;background:#fff;box-shadow:0 5px 20px rgba(28,63,105,.045)}.cloth-panel-head{display:flex;align-items:center;justify-content:space-between;gap:15px;padding:17px 20px;border-bottom:1px solid var(--cloth-line)}.cloth-panel-title{display:flex;align-items:center;gap:9px;margin:0;color:var(--cloth-ink);font-size:1.08rem;font-weight:800}.cloth-panel-title i{color:var(--cloth-blue)}.cloth-panel-meta{margin-top:4px;color:var(--cloth-muted);font-size:.84rem}.cloth-toolbar{display:grid;grid-template-columns:minmax(260px,1fr) 220px 190px;gap:10px;padding:14px 20px;border-bottom:1px solid var(--cloth-line);background:#fbfcfe}.cloth-toolbar .form-control{min-height:45px;border-color:#d8e1ed;border-radius:7px}.cloth-search{position:relative}.cloth-search i{position:absolute;z-index:2;top:50%;right:14px;transform:translateY(-50%);color:#8492a7}.cloth-search .form-control{padding-right:42px}.cloth-table{margin:0}.cloth-table thead th{padding:13px 14px;border:0;border-bottom:1px solid var(--cloth-line);background:#f8fafd;color:#52627b;font-weight:800;white-space:nowrap}.cloth-table td{padding:14px;vertical-align:middle;border-color:#edf1f6}.cloth-index{color:#8794a7;font:700 .84rem Arial,sans-serif}.cloth-identity{display:flex;align-items:center;gap:11px}.cloth-thumb{display:grid;place-items:center;flex:0 0 48px;height:48px;overflow:hidden;border:1px solid #e1e8f2;border-radius:10px;background:#f2f5fa;color:#9cabbd}.cloth-thumb img{width:100%;height:100%;object-fit:cover}.cloth-identity strong{display:block;color:var(--cloth-ink)}.cloth-identity small{display:block;margin-top:3px;color:var(--cloth-muted)}.cloth-color{display:inline-flex;align-items:center;gap:7px;font-weight:700}.cloth-color::before{content:'';width:10px;height:10px;border:2px solid #d9e1ec;border-radius:50%;background:var(--color-preview,#fff)}.cloth-number{display:inline-block;font:800 .88rem Arial,sans-serif;direction:ltr}.cloth-meter{color:#168452}.cloth-cost{color:#1769ef}.cloth-value{color:#d86a11}.cloth-stock-badge{display:inline-flex;padding:5px 9px;border-radius:999px;background:#e9f9f0;color:#15844c;font-size:.75rem;font-weight:800}.cloth-stock-badge.is-low{background:#fff3e4;color:#c46b0b}.cloth-stock-badge.is-empty{background:#fff0f1;color:#c93643}.cloth-actions{display:flex;gap:7px}.cloth-action{display:grid;place-items:center;width:37px;height:37px;border:1px solid #d7e1ee;border-radius:8px;background:#fff;color:#415775!important}.cloth-action:hover{border-color:#1769ef;color:#1769ef!important;text-decoration:none}.cloth-action.is-danger{color:#dc3545!important}.cloth-action.is-danger:hover{border-color:#dc3545;background:#fff5f6}.cloth-empty{padding:52px 20px!important;text-align:center;color:#8b98aa}.cloth-empty i{display:block;margin-bottom:10px;color:#c3cedb;font-size:34px}.cloth-no-results{display:none;padding:40px;text-align:center;color:#8b98aa}.cloth-import-drop{padding:24px;border:2px dashed #cdd8e7;border-radius:11px;background:#f8fbff;text-align:center}.cloth-import-drop i{display:block;margin-bottom:10px;color:#1769ef;font-size:30px}.cloth-import-drop .form-control{height:auto;padding:9px;background:#fff}.cloth-modal .modal-content{border:0;border-radius:13px;box-shadow:0 18px 50px rgba(20,45,75,.22)}.cloth-modal .modal-header,.cloth-modal .modal-footer{border-color:var(--cloth-line)}
    @media(max-width:1100px){.cloth-stats{grid-template-columns:repeat(2,1fr)}.cloth-toolbar{grid-template-columns:1fr 1fr}.cloth-search{grid-column:1/-1}}
    @media(max-width:767.98px){.cloth-catalog-shell{padding:0 12px}.cloth-header{align-items:stretch;flex-direction:column}.cloth-header-actions{flex-direction:column}.cloth-header-actions .btn{width:100%}.cloth-stats{grid-template-columns:1fr}.cloth-stat{min-height:98px}.cloth-toolbar{grid-template-columns:1fr}.cloth-search{grid-column:auto}.cloth-table,.cloth-table tbody,.cloth-table tr,.cloth-table td{display:block;width:100%}.cloth-table thead{display:none}.cloth-table tr{width:calc(100% - 20px);margin:10px;border:1px solid var(--cloth-line);border-radius:10px;padding:7px}.cloth-table td{display:flex;align-items:center;justify-content:space-between;gap:13px;padding:10px;border-top:1px solid #edf1f6}.cloth-table td:first-child{display:none}.cloth-table td::before{content:attr(data-label);flex:0 0 35%;color:var(--cloth-muted);font-weight:800}.cloth-table .cloth-main-cell::before,.cloth-table .cloth-actions::before{display:none}.cloth-table .cloth-main-cell{display:block;border-top:0}.cloth-actions{display:grid!important;grid-template-columns:1fr 1fr}.cloth-action{width:100%}}
</style>
@endpush

@section('content')
@php
    $colorRows = $cloths->flatMap(fn ($cloth) => $cloth->colors->map(function ($color) use ($cloth) {
        $addition = $color->latestCostedStockAddition;
        $cost = $addition ? (float) $addition->unit_cost : (float) $cloth->price;
        return ['cloth' => $cloth, 'color' => $color, 'cost' => $cost, 'value' => $cost * (float) $color->length];
    }));
    $totalMeters = $colorRows->sum(fn ($row) => (float) $row['color']->length);
    $totalValue = $colorRows->sum('value');
    $lowStock = $colorRows->filter(fn ($row) => (float) $row['color']->length <= 10)->count();
    $brands = $cloths->pluck('brand')->filter()->unique('id')->sortBy('name');
@endphp
<section class="main-content cloth-catalog-page" dir="rtl">
<div class="cloth-catalog-shell">
    <div class="cloth-breadcrumb"><a href="{{ route('admin.home') }}">ڈیش بورڈ</a><span class="mx-2">‹</span>انوینٹری<span class="mx-2">‹</span>کپڑوں کی فہرست</div>
    <header class="cloth-header">
        <div class="cloth-heading"><span class="cloth-heading-icon"><i class="fas fa-layer-group"></i></span><div><h1>کپڑوں کی فہرست</h1><p>کپڑے، رنگ، دستیاب مقدار اور تازہ قیمت ایک جگہ دیکھیں</p></div></div>
        <div class="cloth-header-actions"><a href="{{ route('admin.cloth.create') }}" class="btn cloth-primary"><i class="fas fa-plus"></i> نیا کپڑا شامل کریں</a><button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#clothesCsvModal"><i class="fas fa-file-import"></i> ایکسل درآمد</button><a href="{{ route('admin.clothscsv') }}" class="btn btn-outline-success"><i class="fas fa-file-excel"></i> ایکسل برآمد</a></div>
    </header>

    @include('inc.message')

    <div class="cloth-stats">
        <article class="cloth-stat"><div><small>کپڑے اور رنگ</small><strong>{{ $colorRows->count() }}</strong></div><span class="cloth-stat-icon"><i class="fas fa-swatchbook"></i></span></article>
        <article class="cloth-stat"><div><small>کل دستیاب اسٹاک</small><strong>{{ number_format($totalMeters, 2) }} m</strong></div><span class="cloth-stat-icon"><i class="fas fa-ruler-combined"></i></span></article>
        <article class="cloth-stat"><div><small>کم یا ختم اسٹاک</small><strong>{{ $lowStock }}</strong></div><span class="cloth-stat-icon"><i class="fas fa-exclamation-triangle"></i></span></article>
        <article class="cloth-stat"><div><small>اسٹاک کی مالیت</small><strong>Rs. {{ number_format($totalValue, 2) }}</strong></div><span class="cloth-stat-icon"><i class="fas fa-coins"></i></span></article>
    </div>

    <section class="cloth-panel">
        <div class="cloth-panel-head"><div><h2 class="cloth-panel-title"><i class="fas fa-list-ul"></i> انوینٹری کی تفصیل</h2><div class="cloth-panel-meta">کل {{ $colorRows->count() }} ریکارڈز</div></div></div>
        <div class="cloth-toolbar"><div class="cloth-search"><i class="fas fa-search"></i><input id="clothSearch" type="search" class="form-control" placeholder="قسم، برانڈ یا رنگ سے تلاش کریں"></div><select id="clothBrandFilter" class="form-control"><option value="all">تمام برانڈز</option>@foreach($brands as $brand)<option value="{{ $brand->id }}">{{ $brand->name }}</option>@endforeach</select><select id="clothStockFilter" class="form-control"><option value="all">تمام اسٹاک</option><option value="available">دستیاب اسٹاک</option><option value="low">کم اسٹاک (10 یا کم)</option><option value="empty">ختم اسٹاک</option></select></div>
        <div class="table-responsive"><table class="table table-hover cloth-table" id="clothInventoryTable"><thead><tr><th>#</th><th>کپڑا</th><th>رنگ</th><th>دستیاب مقدار</th><th>اسٹاک حالت</th><th>تازہ قیمت</th><th>کل مالیت</th><th>عمل</th></tr></thead><tbody>
            @forelse($colorRows as $row)
                @php
                    $cloth = $row['cloth']; $color = $row['color']; $latestCost = $row['cost']; $length = (float) $color->length;
                    $image = $cloth->images->firstWhere('image_color', $color->color);
                    $stockStatus = $length <= 0 ? 'empty' : ($length <= 10 ? 'low' : 'available');
                    $stockLabel = $stockStatus === 'empty' ? 'ختم' : ($stockStatus === 'low' ? 'کم اسٹاک' : 'دستیاب');
                @endphp
                <tr data-cloth-row data-brand="{{ $cloth->cloth_brand_id }}" data-stock="{{ $stockStatus }}" data-search="{{ Illuminate\Support\Str::lower(($cloth->type->name ?? '').' '.($cloth->brand->name ?? '').' '.$color->color) }}">
                    <td data-label="#"><span class="cloth-index">{{ $loop->iteration }}</span></td>
                    <td data-label="کپڑا" class="cloth-main-cell"><div class="cloth-identity"><span class="cloth-thumb">@if($image)<img src="{{ asset('/'.$image->images) }}" alt="{{ $color->color }}">@else<i class="fas fa-image"></i>@endif</span><div><strong>{{ $cloth->brand->name ?? 'برانڈ' }}</strong><small>{{ $cloth->type->name ?? 'قسم درج نہیں' }}</small></div></div></td>
                    <td data-label="رنگ"><span class="cloth-color">{{ $color->color }}</span></td>
                    <td data-label="دستیاب مقدار"><span class="cloth-number cloth-meter">{{ number_format($length, 2) }} میٹر</span></td>
                    <td data-label="اسٹاک حالت"><span class="cloth-stock-badge {{ $stockStatus === 'low' ? 'is-low' : ($stockStatus === 'empty' ? 'is-empty' : '') }}">{{ $stockLabel }}</span></td>
                    <td data-label="تازہ قیمت"><span class="cloth-number cloth-cost">Rs. {{ number_format($latestCost, 2) }}</span></td>
                    <td data-label="کل مالیت"><span class="cloth-number cloth-value">Rs. {{ number_format($row['value'], 2) }}</span></td>
                    <td class="cloth-actions" data-label="عمل"><a href="{{ route('admin.edit-cloths', ['id' => $cloth->id, 'color' => $color->color]) }}" class="cloth-action" title="ترمیم کریں" aria-label="کپڑے میں ترمیم کریں"><i class="fas fa-pen"></i></a><button class="cloth-action is-danger delete-selected" type="button" data-id="{{ $cloth->id }}" data-color="{{ $color->color }}" title="حذف کریں" aria-label="کپڑے کا رنگ حذف کریں"><i class="fas fa-trash"></i></button></td>
                </tr>
            @empty<tr><td colspan="8" class="cloth-empty"><i class="fas fa-layer-group"></i>ابھی کوئی کپڑا شامل نہیں کیا گیا۔</td></tr>@endforelse
        </tbody></table></div>
        <div id="clothNoResults" class="cloth-no-results"><i class="fas fa-search ml-1"></i> تلاش کے مطابق کوئی کپڑا نہیں ملا۔</div>
    </section>
</div>

<div class="modal fade cloth-modal" id="clothesCsvModal" tabindex="-1" role="dialog" aria-labelledby="clothesCsvModalTitle" aria-hidden="true"><div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content"><form action="{{ route('admin.clothescsv') }}" method="post" enctype="multipart/form-data">@csrf<div class="modal-header"><h2 class="modal-title h5 mb-0" id="clothesCsvModalTitle">ایکسل فائل درآمد کریں</h2><button type="button" class="close mx-0" data-dismiss="modal" aria-label="بند کریں"><span aria-hidden="true">&times;</span></button></div><div class="modal-body"><div class="cloth-import-drop"><i class="fas fa-file-excel"></i><strong class="d-block mb-2">کپڑوں کی ایکسل فائل منتخب کریں</strong><small class="text-muted d-block mb-3">درست ٹیمپلیٹ والی فائل استعمال کریں۔</small><input type="file" name="csvFile" class="form-control" accept=".csv,.xls,.xlsx" required></div></div><div class="modal-footer"><button type="button" class="btn btn-light border" data-dismiss="modal">منسوخ</button><button type="submit" class="btn btn-primary"><i class="fas fa-upload ml-1"></i> فائل درآمد کریں</button></div></form></div></div></div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const search = document.getElementById('clothSearch');
    const brand = document.getElementById('clothBrandFilter');
    const stock = document.getElementById('clothStockFilter');
    const rows = Array.from(document.querySelectorAll('[data-cloth-row]'));
    const noResults = document.getElementById('clothNoResults');
    const applyFilters = function () {
        const query = search.value.trim().toLocaleLowerCase(); let visible = 0;
        rows.forEach(function (row) {
            const matchesText = !query || row.dataset.search.includes(query);
            const matchesBrand = brand.value === 'all' || row.dataset.brand === brand.value;
            const matchesStock = stock.value === 'all' || row.dataset.stock === stock.value || (stock.value === 'low' && row.dataset.stock === 'empty');
            const show = matchesText && matchesBrand && matchesStock; row.style.display = show ? '' : 'none'; if (show) visible++;
        });
        noResults.style.display = rows.length && !visible ? 'block' : 'none';
    };
    search.addEventListener('input', applyFilters); brand.addEventListener('change', applyFilters); stock.addEventListener('change', applyFilters);

    document.querySelectorAll('.delete-selected').forEach(function (button) {
        button.addEventListener('click', async function () {
            const row = this.closest('tr');
            if (!await window.TmsConfirm.ask('کیا آپ واقعی اس کپڑے کے رنگ کا ریکارڈ حذف کرنا چاہتے ہیں؟', {trigger:this})) return;
            $.ajax({url:'{{ route('admin.delete-cloths') }}',type:'POST',data:{_token:'{{ csrf_token() }}',id:this.dataset.id,color:this.dataset.color},success:function (response) {if (response.success) $(row).fadeOut(250,function(){row.remove();});}});
        });
    });
});
</script>
@endpush
