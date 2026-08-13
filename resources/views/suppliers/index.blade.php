@extends('main')

@push('styles')
<style>
    .supplier-page{--supplier-blue:#1769ef;--supplier-ink:#14213d;--supplier-muted:#718096;--supplier-line:#e1e8f2;min-height:calc(100vh - 65px);padding:27px 0 48px;background:#f7f9fc;color:var(--supplier-ink)}
    .supplier-shell{max-width:1560px;margin:auto;padding:0 24px}.supplier-breadcrumb{margin-bottom:12px;color:var(--supplier-muted);font-size:.84rem}.supplier-breadcrumb a{color:inherit}.supplier-header{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:20px}.supplier-heading{display:flex;align-items:center;gap:14px}.supplier-heading-icon{display:grid;place-items:center;width:52px;height:52px;border:1px solid var(--supplier-line);border-radius:13px;background:#fff;color:var(--supplier-blue);font-size:21px;box-shadow:0 5px 18px rgba(25,67,120,.06)}.supplier-heading h1{margin:0 0 4px;font-size:1.6rem;font-weight:800}.supplier-heading p{margin:0;color:var(--supplier-muted)}.supplier-header-actions{display:flex;gap:10px}.supplier-header-actions .btn{min-height:44px;padding:9px 16px;border-radius:8px;font-weight:700}
    .supplier-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:15px;margin-bottom:18px}.supplier-stat{display:flex;align-items:center;justify-content:space-between;min-height:112px;padding:20px;border:1px solid var(--supplier-line);border-radius:13px;background:#fff;box-shadow:0 5px 20px rgba(28,63,105,.045)}.supplier-stat small{display:block;color:var(--supplier-muted);font-weight:700}.supplier-stat strong{display:block;margin-top:7px;color:var(--supplier-ink);font:800 1.18rem/1.3 Arial,sans-serif;direction:ltr}.supplier-stat-icon{display:grid;place-items:center;width:54px;height:54px;border-radius:50%;font-size:21px}.supplier-stat:nth-child(1) .supplier-stat-icon{background:#eaf2ff;color:#1769ef}.supplier-stat:nth-child(2) .supplier-stat-icon{background:#e9f9f0;color:#18a866}.supplier-stat:nth-child(3) .supplier-stat-icon{background:#fff3e8;color:#e77817}.supplier-stat:nth-child(4) .supplier-stat-icon{background:#f1eaff;color:#7e48df}
    .supplier-grid{display:grid;grid-template-columns:minmax(330px,.75fr) minmax(0,2fr);gap:18px;align-items:start}.supplier-panel{border:1px solid var(--supplier-line);border-radius:13px;background:#fff;box-shadow:0 5px 20px rgba(28,63,105,.045)}.supplier-panel-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:17px 20px;border-bottom:1px solid var(--supplier-line)}.supplier-panel-title{display:flex;align-items:center;gap:9px;margin:0;color:var(--supplier-ink);font-size:1.08rem;font-weight:800}.supplier-panel-title i{color:var(--supplier-blue)}.supplier-form{padding:19px}.supplier-form label{margin-bottom:7px;color:#53647d;font-weight:700}.supplier-form .form-control,.supplier-toolbar .form-control{min-height:45px;border-color:#d8e1ed;border-radius:7px}.supplier-form .form-control:focus,.supplier-toolbar .form-control:focus{border-color:#7aafff;box-shadow:0 0 0 3px rgba(23,105,239,.1)}.supplier-form textarea{min-height:82px;resize:vertical}.supplier-input{position:relative}.supplier-input i{position:absolute;z-index:2;top:50%;right:14px;transform:translateY(-50%);color:#8a98ac}.supplier-input .form-control{padding-right:41px}.supplier-money-input .form-control{padding-left:50px}.supplier-input-suffix{position:absolute;z-index:2;top:1px;bottom:1px;left:1px;display:flex;align-items:center;padding:0 12px;border-right:1px solid var(--supplier-line);border-radius:7px 0 0 7px;background:#f8fafc;color:#68778c;font:700 .82rem Arial,sans-serif}.supplier-save{width:100%;min-height:46px;border:0;border-radius:8px;background:linear-gradient(135deg,#1769ef,#287fff);color:#fff;font-weight:800;box-shadow:0 8px 20px rgba(23,105,239,.2)}
    .supplier-list-panel{overflow:hidden}.supplier-list-meta{color:var(--supplier-muted);font-size:.84rem}.supplier-toolbar{display:grid;grid-template-columns:minmax(250px,1fr) 190px;gap:10px;padding:14px 20px;border-bottom:1px solid var(--supplier-line);background:#fbfcfe}.supplier-search{position:relative}.supplier-search i{position:absolute;z-index:2;top:50%;right:14px;transform:translateY(-50%);color:#8492a7}.supplier-search .form-control{padding-right:42px}.supplier-table{margin:0}.supplier-table thead th{padding:13px 15px;border:0;border-bottom:1px solid var(--supplier-line);background:#f8fafd;color:#52627b;font-weight:800;white-space:nowrap}.supplier-table td{padding:15px;vertical-align:middle;border-color:#edf1f6}.supplier-name{display:flex;align-items:center;gap:11px}.supplier-avatar{display:grid;place-items:center;flex:0 0 40px;height:40px;border-radius:10px;background:#eaf2ff;color:#1769ef;font-size:17px}.supplier-name strong{display:block;color:var(--supplier-ink)}.supplier-name small,.supplier-contact small{display:block;margin-top:3px;color:var(--supplier-muted)}.supplier-contact span{display:block;direction:ltr;text-align:right}.supplier-balance{display:inline-block;color:#14213d;font:800 .88rem Arial,sans-serif;direction:ltr}.supplier-balance.is-due{color:#d86a11}.supplier-status{display:inline-flex;align-items:center;gap:6px;padding:5px 9px;border-radius:999px;background:#e9f9f0;color:#15844c;font-size:.75rem;font-weight:800}.supplier-status::before{content:'';width:7px;height:7px;border-radius:50%;background:currentColor}.supplier-status.is-inactive{background:#eef1f5;color:#6b7587}.supplier-actions{display:flex;justify-content:flex-start;gap:7px}.supplier-action{display:grid;place-items:center;width:37px;height:37px;border:1px solid #d7e1ee;border-radius:8px;background:#fff;color:#415775!important}.supplier-action:hover{border-color:#1769ef;color:#1769ef!important;text-decoration:none}.supplier-action.is-danger{color:#dc3545!important}.supplier-action.is-danger:hover{border-color:#dc3545;background:#fff5f6}.supplier-empty{padding:52px 20px!important;text-align:center;color:#8b98aa}.supplier-empty i{display:block;margin-bottom:10px;color:#c3cedb;font-size:34px}.supplier-no-results{display:none;padding:38px 20px;text-align:center;color:#8b98aa}
    @media(max-width:1199.98px){.supplier-stats{grid-template-columns:repeat(2,1fr)}.supplier-grid{grid-template-columns:1fr}.supplier-form .row .form-group{margin-bottom:1rem}}
    @media(max-width:767.98px){.supplier-shell{padding:0 12px}.supplier-header{align-items:stretch;flex-direction:column}.supplier-header-actions{flex-direction:column}.supplier-header-actions .btn{width:100%}.supplier-stats{grid-template-columns:1fr}.supplier-stat{min-height:98px}.supplier-toolbar{grid-template-columns:1fr}.supplier-table,.supplier-table tbody,.supplier-table tr,.supplier-table td{display:block;width:100%}.supplier-table thead{display:none}.supplier-table tr{width:calc(100% - 20px);margin:10px;border:1px solid var(--supplier-line);border-radius:10px;padding:7px}.supplier-table td{display:flex;align-items:center;justify-content:space-between;gap:13px;padding:10px;border-top:1px solid #edf1f6}.supplier-table td:first-child{border-top:0}.supplier-table td::before{content:attr(data-label);flex:0 0 35%;color:var(--supplier-muted);font-weight:800}.supplier-table td:first-child::before,.supplier-table .supplier-actions::before{display:none}.supplier-table td:first-child{display:block}.supplier-actions{display:grid!important;grid-template-columns:1fr 1fr}.supplier-action{width:100%}.supplier-grid{display:block}.supplier-panel{margin-bottom:16px}}
</style>
@endpush

@section('content')
@php
    $activeSuppliers = $suppliers->where('active', true)->count();
    $openingBalance = $suppliers->sum(fn ($supplier) => (float) $supplier->opening_balance);
    $purchaseBalance = $suppliers->sum(fn ($supplier) => (float) ($supplier->purchase_balance ?? 0));
    $totalOutstanding = $suppliers->sum(fn ($supplier) => max(0, (float) ($supplier->purchase_balance ?? 0) + (float) $supplier->opening_balance - (float) ($supplier->unallocated_payments ?? 0)));
@endphp
<section class="main-content supplier-page" dir="rtl">
<div class="supplier-shell">
    <div class="supplier-breadcrumb"><a href="{{ route('admin.home') }}">ڈیش بورڈ</a><span class="mx-2">‹</span>سپلائرز</div>
    <header class="supplier-header">
        <div class="supplier-heading"><span class="supplier-heading-icon"><i class="fas fa-truck"></i></span><div><h1 class="h3 mb-1">سپلائرز</h1><p>سپلائر کی معلومات، خریداری اور واجب الادا رقوم ایک جگہ منظم کریں</p></div></div>
        <div class="supplier-header-actions"><a href="{{ route('admin.purchases.create') }}" class="btn btn-primary"><i class="fas fa-plus ml-1"></i> نئی خریداری</a><a href="{{ route('admin.purchases.index') }}" class="btn btn-outline-primary"><i class="fas fa-shopping-cart ml-1"></i> خریداریاں دیکھیں</a></div>
    </header>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="supplier-stats">
        <article class="supplier-stat"><div><small>کل سپلائرز</small><strong>{{ $suppliers->count() }}</strong></div><span class="supplier-stat-icon"><i class="fas fa-truck"></i></span></article>
        <article class="supplier-stat"><div><small>فعال سپلائرز</small><strong>{{ $activeSuppliers }}</strong></div><span class="supplier-stat-icon"><i class="fas fa-user-check"></i></span></article>
        <article class="supplier-stat"><div><small>خریداری بقایا</small><strong>Rs. {{ number_format($purchaseBalance, 2) }}</strong></div><span class="supplier-stat-icon"><i class="fas fa-file-invoice-dollar"></i></span></article>
        <article class="supplier-stat"><div><small>کل واجب الادا</small><strong>Rs. {{ number_format($totalOutstanding, 2) }}</strong></div><span class="supplier-stat-icon"><i class="fas fa-wallet"></i></span></article>
    </div>

    <div class="supplier-grid">
        <section class="supplier-panel">
            <div class="supplier-panel-head"><h2 class="supplier-panel-title"><i class="fas fa-user-plus"></i> نیا سپلائر شامل کریں</h2></div>
            <form class="supplier-form" method="POST" action="{{ route('admin.suppliers.store') }}">@csrf
                <div class="form-group"><label for="supplier_name">سپلائر کا نام <span class="text-danger">*</span></label><div class="supplier-input"><i class="fas fa-building"></i><input id="supplier_name" name="name" value="{{ old('name') }}" class="form-control" placeholder="کاروبار یا سپلائر کا نام" required maxlength="255"></div></div>
                <div class="form-group"><label for="supplier_contact">رابطہ شخص</label><div class="supplier-input"><i class="fas fa-user"></i><input id="supplier_contact" name="contact_person" value="{{ old('contact_person') }}" class="form-control" placeholder="رابطہ شخص کا نام" maxlength="255"></div></div>
                <div class="form-row"><div class="form-group col-md-6"><label for="supplier_phone">فون نمبر</label><div class="supplier-input"><i class="fas fa-phone"></i><input id="supplier_phone" name="phone" value="{{ old('phone') }}" class="form-control" placeholder="03xx-xxxxxxx" maxlength="50"></div></div><div class="form-group col-md-6"><label for="supplier_email">ای میل</label><div class="supplier-input"><i class="fas fa-envelope"></i><input id="supplier_email" type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="name@example.com" maxlength="255"></div></div></div>
                <div class="form-group"><label for="supplier_opening_balance">ابتدائی بقایا</label><div class="supplier-input supplier-money-input"><i class="fas fa-wallet"></i><input id="supplier_opening_balance" type="number" step="0.01" min="0" name="opening_balance" value="{{ old('opening_balance', 0) }}" class="form-control"><span class="supplier-input-suffix">Rs.</span></div><small class="form-text text-muted">اگر پہلے سے کوئی رقم واجب الادا ہے تو یہاں درج کریں۔</small></div>
                <div class="form-group"><label for="supplier_address">پتہ</label><textarea id="supplier_address" name="address" class="form-control" placeholder="سپلائر کا مکمل پتہ" maxlength="1000">{{ old('address') }}</textarea></div>
                <button class="supplier-save" type="submit"><i class="far fa-save ml-1"></i> سپلائر محفوظ کریں</button>
            </form>
        </section>

        <section class="supplier-panel supplier-list-panel">
            <div class="supplier-panel-head"><div><h2 class="supplier-panel-title"><i class="fas fa-list-ul"></i> سپلائرز کی فہرست</h2><div class="supplier-list-meta mt-1">کل {{ $suppliers->count() }} سپلائرز</div></div></div>
            <div class="supplier-toolbar"><div class="supplier-search"><i class="fas fa-search"></i><input id="supplierSearch" type="search" class="form-control" placeholder="نام، فون یا ای میل سے تلاش کریں"></div><select id="supplierStatusFilter" class="form-control" aria-label="سپلائر حالت"><option value="all">تمام سپلائرز</option><option value="active">صرف فعال</option><option value="inactive">صرف غیر فعال</option></select></div>
            <div class="table-responsive"><table class="table table-hover supplier-table"><thead><tr><th>سپلائر</th><th>رابطہ</th><th>حالت</th><th>ابتدائی بقایا</th><th>خریداری بقایا</th><th>کل واجب الادا</th><th>عمل</th></tr></thead><tbody>
                @forelse($suppliers as $supplier)
                    @php($outstanding = max(0, (float) ($supplier->purchase_balance ?? 0) + (float) $supplier->opening_balance - (float) ($supplier->unallocated_payments ?? 0)))
                    <tr data-supplier-row data-status="{{ $supplier->active ? 'active' : 'inactive' }}" data-search="{{ Illuminate\Support\Str::lower($supplier->name.' '.$supplier->contact_person.' '.$supplier->phone.' '.$supplier->email) }}">
                        <td data-label="سپلائر"><div class="supplier-name"><span class="supplier-avatar"><i class="fas fa-building"></i></span><div><strong>{{ $supplier->name }}</strong><small>{{ $supplier->contact_person ?: 'رابطہ شخص درج نہیں' }}</small></div></div></td>
                        <td data-label="رابطہ"><div class="supplier-contact"><span>{{ $supplier->phone ?: '—' }}</span><small>{{ $supplier->email ?: 'ای میل درج نہیں' }}</small></div></td>
                        <td data-label="حالت"><span class="supplier-status {{ $supplier->active ? '' : 'is-inactive' }}">{{ $supplier->active ? 'فعال' : 'غیر فعال' }}</span></td>
                        <td data-label="ابتدائی بقایا"><span class="supplier-balance">Rs. {{ number_format($supplier->opening_balance, 2) }}</span></td>
                        <td data-label="خریداری بقایا"><span class="supplier-balance">Rs. {{ number_format($supplier->purchase_balance ?? 0, 2) }}</span></td>
                        <td data-label="کل واجب الادا"><span class="supplier-balance {{ $outstanding > 0 ? 'is-due' : '' }}">Rs. {{ number_format($outstanding, 2) }}</span></td>
                        <td class="supplier-actions" data-label="عمل"><a href="{{ route('admin.suppliers.edit', $supplier) }}" class="supplier-action" title="تفصیل اور ترمیم" aria-label="سپلائر کی تفصیل اور ترمیم"><i class="fas fa-pen"></i></a><form method="POST" action="{{ route('admin.suppliers.destroy', $supplier) }}" data-confirm="کیا آپ یہ سپلائر حذف کرنا چاہتے ہیں؟">@csrf @method('DELETE')<button class="supplier-action is-danger" title="حذف کریں" aria-label="سپلائر حذف کریں"><i class="fas fa-trash"></i></button></form></td>
                    </tr>
                @empty<tr><td colspan="7" class="supplier-empty"><i class="fas fa-truck"></i>ابھی کوئی سپلائر شامل نہیں کیا گیا۔</td></tr>@endforelse
            </tbody></table></div>
            <div id="supplierNoResults" class="supplier-no-results"><i class="fas fa-search ml-1"></i> تلاش کے مطابق کوئی سپلائر نہیں ملا۔</div>
        </section>
    </div>
</div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const search = document.getElementById('supplierSearch');
    const status = document.getElementById('supplierStatusFilter');
    const rows = Array.from(document.querySelectorAll('[data-supplier-row]'));
    const empty = document.getElementById('supplierNoResults');
    const filterSuppliers = function () {
        const query = search.value.trim().toLocaleLowerCase();
        let visible = 0;
        rows.forEach(function (row) {
            const matchesText = !query || row.dataset.search.includes(query);
            const matchesStatus = status.value === 'all' || row.dataset.status === status.value;
            const show = matchesText && matchesStatus;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        empty.style.display = rows.length && !visible ? 'block' : 'none';
    };
    search.addEventListener('input', filterSuppliers);
    status.addEventListener('change', filterSuppliers);
});
</script>
@endpush
