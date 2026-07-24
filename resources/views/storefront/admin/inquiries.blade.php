@extends('main')

@section('content')
<section class="main-content inquiry-admin">
<style>
    .inquiry-admin{background:#f3f7f8;min-height:calc(100vh - 70px)}.inquiry-hero{background:linear-gradient(135deg,#6a3d24,#a86535);color:#fff;border-radius:20px;padding:1.5rem 1.7rem}.inquiry-hero h1{color:#fff!important}.inquiry-card{border:0;border-radius:17px;box-shadow:0 8px 25px rgba(31,45,61,.08)}.ref{font-family:monospace;direction:ltr;display:inline-block;background:#edf2ef;border-radius:8px;padding:.25rem .55rem}@media(max-width:767px){.inquiry-hero{border-radius:14px}}
</style>
<div class="container-fluid px-3 px-md-4 py-4" dir="rtl">
    <div class="inquiry-hero mb-4 d-flex flex-wrap justify-content-between align-items-center"><div><div class="small">آن لائن دکان</div><h1 class="h3 mb-1">گاہکوں کی درخواستیں</h1><p class="mb-0 text-white-50">عوامی ویب سائٹ سے موصول ہونے والے سوالات اور سلائی کی دلچسپی۔</p></div>@if(auth()->user()->business->tailoring_enabled)<a class="btn btn-light mt-3 mt-md-0" href="{{ route('admin.storefront.tailoring.services') }}">ٹیلرنگ خدمات</a>@endif</div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="alert alert-info">درخواست خودکار طور پر گاہک، آرڈر یا مالی لین دین نہیں بناتی۔ تصدیق کے بعد متعلقہ ریکارڈ الگ بنائیں۔</div>
    <form class="card inquiry-card card-body mb-4" method="GET"><div class="form-row align-items-end"><div class="form-group col-md-5"><label>نام، فون یا ای میل</label><input name="q" class="form-control" value="{{ $filters['q']??'' }}"></div><div class="form-group col-md-4"><label>حالت</label><select name="status" class="form-control"><option value="">تمام حالتیں</option>@foreach($statuses as $value=>$label)<option value="{{ $value }}" @selected(($filters['status']??'')===$value)>{{ $label }}</option>@endforeach</select></div><div class="form-group col-md-3"><button class="btn btn-primary btn-block">فلٹر کریں</button></div></div></form>
    @forelse($inquiries as $inquiry)
    <div class="card inquiry-card mb-4"><div class="card-body">
        <div class="d-flex flex-wrap justify-content-between"><div><span class="ref">{{ $inquiry->reference }}</span><h2 class="h5 mt-2 mb-1">{{ $inquiry->customer_name }}</h2><div dir="ltr" class="text-right">{{ $inquiry->phone }} @if($inquiry->email) · {{ $inquiry->email }}@endif</div></div><div class="text-muted">{{ $inquiry->created_at->format('d-m-Y h:i A') }}</div></div>
        <hr><div class="row"><div class="col-md-4"><strong>خدمت:</strong> {{ $inquiry->service->name ?? 'عمومی درخواست' }}</div><div class="col-md-4"><strong>شہر:</strong> {{ $inquiry->city ?: '—' }}</div><div class="col-md-4"><strong>پسندیدہ تاریخ:</strong> {{ $inquiry->preferred_date?->format('d-m-Y') ?: '—' }}</div></div>
        <div class="mt-2"><strong>ادائیگی کی ترجیح:</strong> {{ \App\Models\StorefrontInquiry::paymentMethods()[$inquiry->payment_method] ?? $inquiry->payment_method }}
            @if($inquiry->payment_method === \App\Models\StorefrontInquiry::PAYMENT_EASYPAISA)
                · <span dir="ltr">{{ $inquiry->payment_sender_phone }}</span>
                · <code>{{ $inquiry->payment_reference }}</code>
                <span class="badge badge-info">دستی تصدیق درکار</span>
            @endif
        </div>
        @if($inquiry->message)<div class="bg-light rounded p-3 mt-3">{{ $inquiry->message }}</div>@endif
        <form method="POST" action="{{ route('admin.storefront.inquiries.update',$inquiry) }}" class="mt-3">@csrf @method('PATCH')
            <div class="form-row align-items-end"><div class="form-group col-md-3"><label>حالت</label><select name="status" class="form-control">@foreach($statuses as $value=>$label)<option value="{{ $value }}" @selected($inquiry->status===$value)>{{ $label }}</option>@endforeach</select></div><div class="form-group col-md-7"><label>اندرونی نوٹ</label><textarea name="admin_notes" rows="2" maxlength="3000" class="form-control">{{ $inquiry->admin_notes }}</textarea></div><div class="form-group col-md-2"><button class="btn btn-primary btn-block">محفوظ کریں</button></div></div>
        </form>
    </div></div>
    @empty<div class="card inquiry-card"><div class="card-body text-center py-5"><h2 class="h5">کوئی درخواست موجود نہیں</h2><p class="text-muted">نئی عوامی درخواست یہاں نظر آئے گی۔</p></div></div>@endforelse
    @if($inquiries->hasPages())<div class="mt-3">{{ $inquiries->links() }}</div>@endif
</div>
</section>
@endsection
