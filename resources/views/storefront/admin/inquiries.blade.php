@extends('main')

@section('content')
<section class="main-content inquiry-admin">
<style>
    .inquiry-admin{background:#f3f7f8;min-height:calc(100vh - 70px)}.inquiry-hero{background:linear-gradient(135deg,#6a3d24,#a86535);color:#fff;border-radius:20px;padding:1.5rem 1.7rem}.inquiry-hero h1{color:#fff!important}.inquiry-card{border:0;border-radius:17px;box-shadow:0 8px 25px rgba(31,45,61,.08)}.ref{font-family:monospace;direction:ltr;display:inline-block;background:#edf2ef;border-radius:8px;padding:.25rem .55rem}@media(max-width:767px){.inquiry-hero{border-radius:14px}}
</style>
<div class="container-fluid px-3 px-md-4 py-4" dir="rtl">
    <div class="inquiry-hero mb-4 d-flex flex-wrap justify-content-between align-items-center"><div><div class="small">آن لائن دکان</div><h1 class="h3 mb-1">آن لائن ٹیلرنگ بکنگ</h1><p class="mb-0 text-white-50">گاہک کی بکنگ کا جائزہ لیں، ادائیگی تصدیق کریں، آرڈر منظور یا مسترد کریں اور جاب شیٹ پرنٹ کریں۔</p></div>@if(auth()->user()->business->tailoring_enabled)<a class="btn btn-light mt-3 mt-md-0" href="{{ route('admin.storefront.tailoring.services') }}">ٹیلرنگ خدمات</a>@endif</div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="alert alert-info">نئی آن لائن بکنگ صرف درخواست محفوظ کرتی ہے۔ منظوری کے وقت نظام گاہک، ٹیلرنگ آرڈر اور مالی لین دین ایک ساتھ بناتا ہے۔ پرانی عمومی درخواستیں سابقہ طریقے سے دستیاب رہیں گی۔</div>
    <form class="card inquiry-card card-body mb-4" method="GET"><div class="form-row align-items-end"><div class="form-group col-md-5"><label>نام، فون یا ای میل</label><input name="q" class="form-control" value="{{ $filters['q']??'' }}"></div><div class="form-group col-md-4"><label>حالت</label><select name="status" class="form-control"><option value="">تمام حالتیں</option>@foreach($statuses as $value=>$label)<option value="{{ $value }}" @selected(($filters['status']??'')===$value)>{{ $label }}</option>@endforeach</select></div><div class="form-group col-md-3"><button class="btn btn-primary btn-block">فلٹر کریں</button></div></div></form>
    @forelse($inquiries as $inquiry)
    <div class="card inquiry-card mb-4"><div class="card-body">
        <div class="d-flex flex-wrap justify-content-between"><div><span class="ref">{{ $inquiry->reference }}</span><h2 class="h5 mt-2 mb-1">{{ $inquiry->customer_name }}</h2><div dir="ltr" class="text-right">{{ $inquiry->phone }} @if($inquiry->email) · {{ $inquiry->email }}@endif</div></div><div class="text-muted">{{ $inquiry->created_at->format('d-m-Y h:i A') }}</div></div>
        <hr><div class="row"><div class="col-md-3"><strong>قسم:</strong> {{ $inquiry->isBooking() ? 'آن لائن بکنگ' : 'پرانی عمومی درخواست' }}</div><div class="col-md-3"><strong>خدمت:</strong> {{ $inquiry->service->name ?? 'عمومی درخواست' }}</div><div class="col-md-3"><strong>تعداد:</strong> {{ $inquiry->isBooking() ? $inquiry->suit_quantity : '—' }}</div><div class="col-md-3"><strong>پسندیدہ تاریخ:</strong> {{ $inquiry->preferred_date?->format('d-m-Y') ?: '—' }}</div></div>
        @if($inquiry->measurement_method || $inquiry->service_deposit_type)
        <div class="row mt-2">
            <div class="col-md-6"><strong>پیمائش:</strong> {{ \App\Models\StorefrontTailoringService::measurementMethodLabels()[$inquiry->measurement_method] ?? '—' }}</div>
            <div class="col-md-6"><strong>درخواست کے وقت پیشگی پالیسی:</strong>
                @if($inquiry->service_deposit_type === \App\Models\StorefrontTailoringService::DEPOSIT_PERCENTAGE)
                    {{ rtrim(rtrim(number_format((float)$inquiry->service_deposit_value,2),'0'),'.') }}%
                @elseif($inquiry->service_deposit_amount !== null)
                    Rs {{ number_format((float)$inquiry->service_deposit_amount,2) }}
                @else —
                @endif
            </div>
        </div>
        @endif
        <div class="mt-2"><strong>ادائیگی:</strong> {{ \App\Models\StorefrontInquiry::paymentMethods()[$inquiry->payment_method] ?? $inquiry->payment_method }} @if($inquiry->isBooking()) · <strong>گاہک کا بتایا ہوا پیشگی:</strong> Rs {{ number_format((float)$inquiry->payment_claimed_amount,2) }} @endif
            @if(\App\Models\StorefrontInquiry::requiresManualVerification($inquiry->payment_method))
                @if($inquiry->payment_sender_phone) · <span dir="ltr">{{ $inquiry->payment_sender_phone }}</span>@endif
                · <code>{{ $inquiry->payment_reference }}</code>
                <span class="badge badge-{{ $inquiry->payment_verification_status === \App\Models\StorefrontInquiry::VERIFICATION_VERIFIED ? 'success' : ($inquiry->payment_verification_status === \App\Models\StorefrontInquiry::VERIFICATION_REJECTED ? 'danger' : 'info') }}">{{ \App\Models\StorefrontInquiry::verificationStatuses()[$inquiry->payment_verification_status] ?? 'دستی تصدیق درکار' }}</span>
            @endif
        </div>
        @if(\App\Models\StorefrontInquiry::requiresManualVerification($inquiry->payment_method))
            @php
                $verificationLabels = \App\Models\StorefrontInquiry::verificationStatuses();
                $verificationClass = match($inquiry->payment_verification_status) {
                    \App\Models\StorefrontInquiry::VERIFICATION_VERIFIED => 'success',
                    \App\Models\StorefrontInquiry::VERIFICATION_REJECTED => 'danger',
                    default => 'warning',
                };
            @endphp
            <div class="border rounded bg-light p-3 mt-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center">
                    <strong>{{ \App\Models\StorefrontInquiry::paymentMethods()[$inquiry->payment_method] ?? $inquiry->payment_method }} حوالہ کی تصدیق</strong>
                    <span class="badge badge-{{ $verificationClass }}">{{ $verificationLabels[$inquiry->payment_verification_status] ?? $inquiry->payment_verification_status }}</span>
                </div>
                @if($inquiry->paymentVerifier)
                    <small class="text-muted d-block mt-2">
                        کارروائی: {{ $inquiry->paymentVerifier->name ?: $inquiry->paymentVerifier->username }}
                        · {{ ($inquiry->payment_verified_at ?: $inquiry->payment_rejected_at)?->format('d-m-Y h:i A') }}
                    </small>
                @endif
                @if($inquiry->payment_verification_notes)
                    <div class="small mt-2">{{ $inquiry->payment_verification_notes }}</div>
                @endif
                @if($inquiry->payment_evidence_path)
                    <div class="mt-2">
                        <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="{{ route('admin.storefront.inquiries.payment-evidence', $inquiry) }}">
                            <i class="fas fa-paperclip ml-1"></i> ادائیگی کا ثبوت دیکھیں
                        </a>
                        <small class="text-muted mr-2">{{ $inquiry->payment_evidence_original_name }} · {{ number_format(($inquiry->payment_evidence_size ?? 0) / 1024, 1) }} KB</small>
                    </div>
                @else
                    <div class="small text-muted mt-2">گاہک نے ادائیگی کا ثبوت منسلک نہیں کیا۔</div>
                @endif
                @if($inquiry->payment_verification_status !== \App\Models\StorefrontInquiry::VERIFICATION_VERIFIED)
                    <form method="POST" action="{{ route('admin.storefront.inquiries.payment-verification', $inquiry) }}" class="mt-3">
                        @csrf @method('PATCH')
                        <label for="inquiry_payment_notes_{{ $inquiry->id }}">تصدیقی نوٹ <small class="text-muted">(مسترد کرنے پر ضروری)</small></label>
                        <textarea id="inquiry_payment_notes_{{ $inquiry->id }}" name="payment_verification_notes" class="form-control mb-2" rows="2" maxlength="1000"></textarea>
                        <button name="decision" value="verified" class="btn btn-success">حوالہ تصدیق کریں</button>
                        <button name="decision" value="rejected" class="btn btn-outline-danger">حوالہ مسترد کریں</button>
                    </form>
                @endif
            </div>
        @endif
        @if($inquiry->message)<div class="bg-light rounded p-3 mt-3">{{ $inquiry->message }}</div>@endif
        @if($inquiry->isBooking() && in_array($inquiry->status,[\App\Models\StorefrontInquiry::STATUS_NEW,\App\Models\StorefrontInquiry::STATUS_CONTACTED],true))
        <div class="border rounded p-3 mt-3"><h3 class="h6">بکنگ پر فیصلہ</h3><form method="POST" action="{{ route('admin.storefront.inquiries.confirm',$inquiry) }}">@csrf @method('PATCH')<div class="form-row"><div class="form-group col-md-3"><label>حتمی قیمت</label><input name="final_price" type="number" min="0" step="0.01" required class="form-control" value="{{ old('final_price',$inquiry->estimated_price) }}"></div><div class="form-group col-md-2"><label>تعداد</label><input name="suit_quantity" type="number" min="1" max="20" required class="form-control" value="{{ old('suit_quantity',$inquiry->suit_quantity) }}"></div><div class="form-group col-md-3"><label>تیاری کی تاریخ</label><input name="promised_date" type="date" min="{{ now()->toDateString() }}" required class="form-control" value="{{ old('promised_date',$inquiry->preferred_date?->format('Y-m-d') ?: now()->addDays($inquiry->service?->estimated_days ?: 7)->format('Y-m-d')) }}"></div><div class="form-group col-md-4"><label>اندرونی نوٹ</label><input name="admin_notes" maxlength="3000" class="form-control" value="{{ old('admin_notes',$inquiry->admin_notes) }}"></div></div><button class="btn btn-success">بکنگ منظور اور آرڈر بنائیں</button></form><form method="POST" action="{{ route('admin.storefront.inquiries.reject',$inquiry) }}" class="mt-3">@csrf @method('PATCH')<div class="input-group"><input name="rejection_reason" required maxlength="1000" class="form-control" placeholder="مسترد کرنے کی وجہ"><div class="input-group-append"><button class="btn btn-outline-danger">بکنگ مسترد کریں</button></div></div></form></div>
        @elseif($inquiry->isBooking() && $inquiry->status===\App\Models\StorefrontInquiry::STATUS_CONFIRMED)
        <div class="alert alert-success mt-3 mb-0"><strong>آرڈر #{{ $inquiry->order_id }} بن گیا ہے۔</strong> حتمی قیمت Rs {{ number_format((float)$inquiry->final_price,2) }} · تیاری {{ $inquiry->promised_date?->format('d-m-Y') }} <a target="_blank" class="btn btn-sm btn-dark mr-2" href="{{ route('admin.storefront.inquiries.job-sheet',$inquiry) }}">جاب شیٹ پرنٹ کریں</a> @if($inquiry->order)<a class="btn btn-sm btn-outline-dark" href="{{ route('admin.order-print',$inquiry->order) }}" target="_blank">مکمل آرڈر پرنٹ</a>@endif</div>
        @elseif($inquiry->isBooking() && $inquiry->status===\App\Models\StorefrontInquiry::STATUS_REJECTED)
        <div class="alert alert-danger mt-3 mb-0"><strong>بکنگ مسترد:</strong> {{ $inquiry->rejection_reason }}</div>
        @else
        <form method="POST" action="{{ route('admin.storefront.inquiries.update',$inquiry) }}" class="mt-3">@csrf @method('PATCH')
            <div class="form-row align-items-end"><div class="form-group col-md-3"><label>حالت</label><select name="status" class="form-control">@foreach($statuses as $value=>$label)<option value="{{ $value }}" @selected($inquiry->status===$value)>{{ $label }}</option>@endforeach</select></div><div class="form-group col-md-7"><label>اندرونی نوٹ</label><textarea name="admin_notes" rows="2" maxlength="3000" class="form-control">{{ $inquiry->admin_notes }}</textarea></div><div class="form-group col-md-2"><button class="btn btn-primary btn-block">محفوظ کریں</button></div></div>
        </form>
        @endif
    </div></div>
    @empty<div class="card inquiry-card"><div class="card-body text-center py-5"><h2 class="h5">کوئی درخواست موجود نہیں</h2><p class="text-muted">نئی عوامی درخواست یہاں نظر آئے گی۔</p></div></div>@endforelse
    @if($inquiries->hasPages())<div class="mt-3">{{ $inquiries->links() }}</div>@endif
</div>
</section>
@endsection
