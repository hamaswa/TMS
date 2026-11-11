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
    /* New supplier popup */
    .sup-modal { border: 0; border-radius: 1.1rem; overflow: hidden; box-shadow: 0 24px 60px rgba(15, 23, 42, .3); }
    .sup-modal-head { display: flex; align-items: center; gap: .9rem; padding: 1.1rem 1.5rem; border-bottom: 1px solid #e5eaf2; }
    .sup-modal-icon { flex: 0 0 auto; width: 3.4rem; height: 3.4rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #1769e0, #2f80ff); color: #fff; font-size: 1.3rem; box-shadow: 0 6px 16px rgba(23, 105, 224, .3); }
    .sup-modal-titles { flex: 1 1 auto; min-width: 0; }
    .sup-modal-titles h5 { font-weight: 800; color: #0f172a; font-size: 1.35rem; }
    .sup-modal-titles small { color: #64748b; font-size: .9rem; }
    .sup-modal-close { flex: 0 0 auto; width: 2.6rem; height: 2.6rem; border: 1px solid #e2e8f0; border-radius: 50%; background: #fff; color: #334155; font-size: 1.05rem; display: flex; align-items: center; justify-content: center; }
    .sup-modal-close:hover { background: #f1f5f9; }
    .sup-modal-body { padding: 1.25rem 1.5rem; }
    .sup-modal-body label { font-weight: 700; color: #1f2937; margin-bottom: .4rem; }
    .sup-modal-body label > i { color: #1769e0; margin-left: .35rem; width: 1.1rem; text-align: center; }
    .sup-modal-body .form-control { min-height: 46px; border-radius: .6rem; border-color: #d6dee9; }
    .sup-modal-body textarea.form-control { min-height: 90px; }
    .sup-modal-body .form-control:focus { border-color: #1769e0; box-shadow: 0 0 0 .2rem rgba(23, 105, 224, .15); }
    .sup-modal-body .form-control::placeholder { color: #9aa7b8; }
    .sup-field { display: flex; }
    .sup-field .form-control { border-top-right-radius: 0; border-bottom-right-radius: 0; }
    .sup-field-icon { display: flex; align-items: center; justify-content: center; width: 3rem; border: 1px solid #d6dee9; border-left: 0; border-radius: 0 .6rem .6rem 0; background: #f1f5fa; color: #334155; }
    .sup-money .input-group-text { min-width: 4.5rem; justify-content: center; border-color: #d6dee9; border-radius: .6rem 0 0 .6rem; background: #f1f5fa; font-weight: 700; color: #1f2937; }
    .sup-money .form-control { border-radius: 0 .6rem .6rem 0; }
    .sup-modal-foot { display: flex; justify-content: flex-start; gap: .6rem; padding: 1rem 1.5rem 1.25rem; border-top: 1px solid #e5eaf2; }
    .sup-save { min-width: 9rem; padding: .65rem 1.25rem; border: 0; border-radius: .6rem; background: #1565e0; color: #fff; font-weight: 700; }
    .sup-save:hover, .sup-save:focus { background: #0f56c4; color: #fff; }
    .sup-cancel { padding: .65rem 1.1rem; border: 0; border-radius: .6rem; background: #eef2f6; color: #1f2937; }
</style>
@endpush

@section('content')
@php
    $activeSuppliers = $suppliers->where('active', true)->count();
    $openingBalance = $suppliers->sum(fn ($supplier) => (float) $supplier->opening_balance);
    $purchaseBalance = $suppliers->sum(fn ($supplier) => (float) ($supplier->purchase_total ?? 0));
    $allPayments = $suppliers->sum(fn ($supplier) => (float) ($supplier->total_paid ?? 0));
    $totalOutstanding = $suppliers->sum(fn ($supplier) => max(0, (float) ($supplier->purchase_total ?? 0) + (float) $supplier->opening_balance - (float) ($supplier->total_paid ?? 0)));
@endphp
<section class="main-content supplier-page" dir="rtl">
<div class="supplier-shell">
    <div class="supplier-breadcrumb"><a href="{{ route('admin.home') }}">ڈیش بورڈ</a><span class="mx-2">‹</span>سپلائرز</div>
    <header class="supplier-header">
        <div class="supplier-heading"><span class="supplier-heading-icon"><i class="fas fa-truck"></i></span><div><h1 class="h3 mb-1">سپلائرز</h1><p>سپلائر کی معلومات، خریداری اور واجب الادا رقوم ایک جگہ منظم کریں</p></div></div>
        <div class="supplier-header-actions"><button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createSupplier"><i class="fas fa-user-plus ml-1"></i> نیا سپلائر</button><a href="{{ route('admin.purchases.index') }}" class="btn btn-outline-primary"><i class="fas fa-shopping-cart ml-1"></i> خریداریاں دیکھیں</a></div>
    </header>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="supplier-stats">
        <article class="supplier-stat"><div><small>کل سپلائرز</small><strong>{{ $suppliers->count() }}</strong></div><span class="supplier-stat-icon"><i class="fas fa-truck"></i></span></article>
        <article class="supplier-stat"><div><small>فعال سپلائرز</small><strong>{{ $activeSuppliers }}</strong></div><span class="supplier-stat-icon"><i class="fas fa-user-check"></i></span></article>
        <article class="supplier-stat"><div><small>کل خریداریاں</small><strong>Rs. {{ number_format($purchaseBalance, 2) }}</strong></div><span class="supplier-stat-icon"><i class="fas fa-file-invoice-dollar"></i></span></article>
        <article class="supplier-stat"><div><small>کل ادائیگیاں</small><strong>Rs. {{ number_format($allPayments, 2) }}</strong></div><span class="supplier-stat-icon"><i class="fas fa-wallet"></i></span></article>
    </div>

    <section class="supplier-panel supplier-list-panel">
            <div class="supplier-panel-head"><div><h2 class="supplier-panel-title"><i class="fas fa-list-ul"></i> سپلائرز کی فہرست</h2><div class="supplier-list-meta mt-1">کل {{ $suppliers->count() }} سپلائرز</div></div></div>
            <div class="supplier-toolbar"><div class="supplier-search"><i class="fas fa-search"></i><input id="supplierSearch" type="search" class="form-control" placeholder="نام، فون یا ای میل سے تلاش کریں"></div><select id="supplierStatusFilter" class="form-control" aria-label="سپلائر حالت"><option value="all">تمام سپلائرز</option><option value="active">صرف فعال</option><option value="inactive">صرف غیر فعال</option></select></div>
            <div class="table-responsive"><table class="table table-hover supplier-table"><thead><tr><th>سپلائر</th><th>رابطہ</th><th>حالت</th><th>کل خریداریاں</th><th>کل ادائیگیاں</th><th>کل واجب الادا</th><th>عمل</th></tr></thead><tbody>
                @forelse($suppliers as $supplier)
                    @php($outstanding = max(0, (float) ($supplier->purchase_total ?? 0) + (float) $supplier->opening_balance - (float) ($supplier->total_paid ?? 0)))
                    <tr data-supplier-row data-status="{{ $supplier->active ? 'active' : 'inactive' }}" data-search="{{ Illuminate\Support\Str::lower($supplier->name.' '.$supplier->contact_person.' '.$supplier->phone.' '.$supplier->email) }}">
                        <td data-label="سپلائر"><div class="supplier-name"><span class="supplier-avatar"><i class="fas fa-building"></i></span><div><strong>{{ $supplier->name }}</strong><small>{{ $supplier->contact_person ?: 'رابطہ شخص درج نہیں' }}</small></div></div></td>
                        <td data-label="رابطہ"><div class="supplier-contact"><span>{{ $supplier->phone ?: '—' }}</span><small>{{ $supplier->email ?: 'ای میل درج نہیں' }}</small></div></td>
                        <td data-label="حالت"><span class="supplier-status {{ $supplier->active ? '' : 'is-inactive' }}">{{ $supplier->active ? 'فعال' : 'غیر فعال' }}</span></td>
                        <td data-label="کل خریداریاں"><span class="supplier-balance">Rs. {{ number_format($supplier->purchase_total ?? 0, 2) }}</span></td>
                        <td data-label="کل ادائیگیاں"><span class="supplier-balance">Rs. {{ number_format($supplier->total_paid ?? 0, 2) }}</span></td>
                        <td data-label="کل واجب الادا"><span class="supplier-balance {{ $outstanding > 0 ? 'is-due' : '' }}">Rs. {{ number_format($outstanding, 2) }}</span></td>
                        <td class="supplier-actions" data-label="عمل">@if($supplier->active)<button type="button" class="supplier-action" data-toggle="modal" data-target="#paySupplier{{ $supplier->id }}" title="ادائیگی کریں" aria-label="{{ $supplier->name }} کو ادائیگی کریں"><i class="fas fa-money-bill-wave"></i></button>@endif<button type="button" class="supplier-action" data-toggle="modal" data-target="#editSupplier{{ $supplier->id }}" title="سپلائر تبدیل کریں" aria-label="سپلائر تبدیل کریں"><i class="fas fa-pen"></i></button><a href="{{ route('admin.suppliers.edit', $supplier) }}" class="supplier-action" title="ادائیگیوں کی تاریخ" aria-label="ادائیگیوں کی تاریخ"><i class="fas fa-list-alt"></i></a><form method="POST" action="{{ route('admin.suppliers.destroy', $supplier) }}" data-confirm="کیا آپ یہ سپلائر حذف کرنا چاہتے ہیں؟">@csrf @method('DELETE')<button class="supplier-action is-danger" title="حذف کریں" aria-label="سپلائر حذف کریں"><i class="fas fa-trash"></i></button></form></td>
                    </tr>
                @empty<tr><td colspan="7" class="supplier-empty"><i class="fas fa-truck"></i>ابھی کوئی سپلائر شامل نہیں کیا گیا۔</td></tr>@endforelse
            </tbody></table></div>
            <div id="supplierNoResults" class="supplier-no-results"><i class="fas fa-search ml-1"></i> تلاش کے مطابق کوئی سپلائر نہیں ملا۔</div>
    </section>
</div>

    @foreach($suppliers as $supplier)
        @php($due = max(0, (float) ($supplier->purchase_total ?? 0) + (float) $supplier->opening_balance - (float) ($supplier->total_paid ?? 0)))
        <div class="modal fade" id="editSupplier{{ $supplier->id }}" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered" role="document"><div class="modal-content" dir="rtl"><div class="modal-header"><h5 class="modal-title">{{ $supplier->name }} — ترمیم</h5><button type="button" class="close ml-0 mr-auto" data-dismiss="modal" aria-label="بند کریں">&times;</button></div><form method="POST" action="{{ route('admin.suppliers.update', $supplier) }}">@csrf @method('PUT')<div class="modal-body"><div class="form-row"><div class="form-group col-md-6"><label>نام</label><input class="form-control" name="name" value="{{ $supplier->name }}" required maxlength="255"></div><div class="form-group col-md-6"><label>رابطہ شخص</label><input class="form-control" name="contact_person" value="{{ $supplier->contact_person }}"></div><div class="form-group col-md-6"><label>فون</label><input class="form-control" name="phone" value="{{ $supplier->phone }}"></div><div class="form-group col-md-6"><label>ای میل</label><input type="email" class="form-control" name="email" value="{{ $supplier->email }}"></div><div class="form-group col-md-6"><label>ابتدائی بقایا</label><input type="number" step="0.01" min="0" class="form-control" name="opening_balance" value="{{ $supplier->opening_balance }}"></div><div class="form-group col-md-6"><label>پتہ</label><textarea class="form-control" name="address">{{ $supplier->address }}</textarea></div></div><input type="hidden" name="active" value="0"><label><input type="checkbox" name="active" value="1" @checked($supplier->active)> فعال سپلائر</label></div><div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">منسوخ</button><button class="btn btn-primary">محفوظ کریں</button></div></form></div></div></div>
        @if($supplier->active)
        <div class="modal fade" id="paySupplier{{ $supplier->id }}" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered" role="document"><div class="modal-content" dir="rtl"><div class="modal-header"><h5 class="modal-title">{{ $supplier->name }} — ادائیگی · بقایا Rs. {{ number_format($due,2) }}</h5><button type="button" class="close ml-0 mr-auto" data-dismiss="modal" aria-label="بند کریں">&times;</button></div><form method="POST" action="{{ route('admin.suppliers.payment', $supplier) }}" data-confirm="کیا آپ یہ ادائیگی درج کرنا چاہتے ہیں؟" data-confirm-variant="success">@csrf<div class="modal-body"><div class="form-row"><div class="form-group col-md-6"><label>رقم</label><input class="form-control" type="number" name="amount" min="0.01"  step="0.01" required></div><div class="form-group col-md-6"><label>تاریخ</label><input class="form-control" type="date" name="payment_date" value="{{ now()->toDateString() }}" required></div><x-payment-method-fields prefix="supplier_list_payment_{{ $supplier->id }}" method-name="payment_method" reference-name="reference" method-group-class="form-group col-md-6" reference-group-class="form-group col-md-6" /><div class="form-group col-12"><label>خریداری آرڈر (اختیاری)</label><select class="form-control" name="purchase_id"><option value="">عمومی ادائیگی — کوئی آرڈر نہیں</option>@foreach($purchaseOptions->where('supplier_id', $supplier->id) as $purchase)<option value="{{ $purchase->id }}">{{ $purchase->purchase_number }}</option>@endforeach</select></div><div class="form-group col-12"><label>نوٹ</label><input class="form-control" name="note" maxlength="1000"></div></div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">منسوخ</button><button class="btn btn-success">ادائیگی درج کریں</button></div></form></div></div></div>
        @endif
    @endforeach
</section>
        @php($createErrors = $errors->getBag('createSupplier'))
        @php($createOld = fn (string $field, $default = '') => $createErrors->any() ? old($field, $default) : $default)
        <div class="modal fade" id="createSupplier" tabindex="-1" role="dialog" aria-labelledby="createSupplierTitle" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered" role="document"><div class="modal-content sup-modal" dir="rtl">
            <div class="sup-modal-head">
                <span class="sup-modal-icon" aria-hidden="true"><i class="fas fa-users"></i></span>
                <div class="sup-modal-titles">
                    <h5 class="mb-0" id="createSupplierTitle">نیا سپلائر</h5>
                    <small>سپلائر کی معلومات درج کریں</small>
                </div>
                <button type="button" class="sup-modal-close" data-dismiss="modal" aria-label="بند کریں"><i class="fas fa-times" aria-hidden="true"></i></button>
            </div>
            <form method="POST" action="{{ route('admin.suppliers.store') }}">@csrf
                <div class="modal-body sup-modal-body">
                    @if($createErrors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($createErrors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="new_supplier_name"><i class="fas fa-user-tag" aria-hidden="true"></i> سپلائر کا نام <span class="text-danger">*</span></label>
                            <div class="sup-field">
                                <span class="sup-field-icon" aria-hidden="true"><i class="far fa-user"></i></span>
                                <input id="new_supplier_name" name="name" value="{{ $createOld('name') }}" class="form-control {{ $createErrors->has('name') ? 'is-invalid' : '' }}" required maxlength="255" placeholder="مثال: علی کمپنی">
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="new_supplier_contact"><i class="far fa-id-card" aria-hidden="true"></i> رابطہ شخص</label>
                            <input id="new_supplier_contact" name="contact_person" value="{{ $createOld('contact_person') }}" class="form-control" maxlength="255" placeholder="مثال: احمد علی">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="new_supplier_phone"><i class="fas fa-phone-alt" aria-hidden="true"></i> فون نمبر</label>
                            <input id="new_supplier_phone" name="phone" value="{{ $createOld('phone') }}" class="form-control" maxlength="50" dir="ltr" inputmode="tel" placeholder="0300-1234567">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="new_supplier_email"><i class="fas fa-envelope" aria-hidden="true"></i> ای میل</label>
                            <input id="new_supplier_email" type="email" name="email" value="{{ $createOld('email') }}" class="form-control {{ $createErrors->has('email') ? 'is-invalid' : '' }}" maxlength="255" dir="ltr" placeholder="info@company.com">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="new_supplier_opening_balance"><i class="fas fa-coins" aria-hidden="true"></i> ابتدائی بقایا</label>
                        <div class="input-group sup-money" dir="ltr"><div class="input-group-prepend"><span class="input-group-text">Rs.</span></div><input id="new_supplier_opening_balance" type="number" step="0.01" min="0" name="opening_balance" value="{{ $createOld('opening_balance', 0) }}" class="form-control"></div>
                        <small class="form-text text-muted">صرف پہلے سے واجب رقم؛ نئی خریداری کا بقایا خود شامل ہوگا۔</small>
                    </div>
                    <div class="form-group mb-0">
                        <label for="new_supplier_address"><i class="far fa-edit" aria-hidden="true"></i> پتہ</label>
                        <textarea id="new_supplier_address" name="address" rows="3" class="form-control" maxlength="1000" placeholder="دکان یا گودام کا پتہ درج کریں…">{{ $createOld('address') }}</textarea>
                    </div>
                </div>
                <div class="sup-modal-foot">
                    <button class="btn sup-save" type="submit"><i class="far fa-save" aria-hidden="true"></i> محفوظ کریں</button>
                    <button type="button" class="btn sup-cancel" data-dismiss="modal"><i class="fas fa-times" aria-hidden="true"></i> منسوخ</button>
                </div>
            </form>
        </div></div></div>
@endsection

@push('scripts')
<script>
// Open the new-supplier popup from ?create=1 links or when its form came back with errors.
@if(request()->boolean('create') || $errors->getBag('createSupplier')->any())
$(function () { $('#createSupplier').modal('show'); });
@endif
$(document).on('shown.bs.modal', '#createSupplier', function () { $('#new_supplier_name').trigger('focus'); });
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
