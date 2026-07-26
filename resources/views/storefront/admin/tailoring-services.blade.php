@extends('main')
@section('content')
@php
    $depositTypes = \App\Models\StorefrontTailoringService::depositTypes();
    $measurementMethods = \App\Models\StorefrontTailoringService::measurementMethodLabels();
@endphp
<section class="main-content service-page">
<div class="container py-4" style="max-width:1150px" dir="rtl">
    <div class="service-hero mb-4">
        <div><div class="small text-white-50">آن لائن دکان</div><h1 class="h3 mb-1">ٹیلرنگ خدمات</h1><p class="mb-0 text-white-50">ہر خدمت کی دستیابی، پیمائش، پیشگی رقم اور بکنگ الگ ترتیب دیں۔</p></div>
        <div class="mt-3 mt-md-0"><a class="btn btn-light ml-2" href="{{ route('admin.storefront.module-settings.edit', 'tailoring') }}">ٹیلرنگ کی بنیادی ترتیب</a><a class="btn btn-outline-light" href="{{ route('admin.storefront.inquiries.index') }}">گاہکوں کی درخواستیں</a></div>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="card service-card mb-4">
        <div class="card-header"><h2 class="h5 mb-1">نئی خدمت شامل کریں</h2><p class="small text-muted mb-0">خدمت کو پہلے مسودے کے طور پر محفوظ کرنا بھی ممکن ہے۔</p></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.storefront.tailoring.store') }}">@csrf
                <input type="hidden" name="service_controls_present" value="1">
                @include('storefront.admin.partials.tailoring-service-fields', [
                    'service' => null,
                    'depositTypes' => $depositTypes,
                    'measurementMethods' => $measurementMethods,
                    'formKey' => 'new',
                ])
                <div class="text-left"><button class="btn btn-primary px-4"><i class="fas fa-plus ml-1"></i> خدمت شامل کریں</button></div>
            </form>
        </div>
    </div>

    @forelse($services as $service)
    <div class="card service-card mb-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <div><h2 class="h5 mb-1">{{ $service->name }}</h2><div class="small text-muted">آخری تبدیلی: {{ $service->updated_at->format('d-m-Y h:i A') }}</div></div>
            <div class="mt-2 mt-md-0">
                <span class="state {{ $service->is_published ? 'state-on' : 'state-off' }}">{{ $service->is_published ? 'عوامی' : 'مسودہ' }}</span>
                <span class="state {{ $service->is_available ? 'state-on' : 'state-off' }}">{{ $service->is_available ? 'دستیاب' : 'عارضی بند' }}</span>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.storefront.tailoring.update', $service) }}">@csrf @method('PUT')
                <input type="hidden" name="service_controls_present" value="1">
                @include('storefront.admin.partials.tailoring-service-fields', [
                    'service' => $service,
                    'depositTypes' => $depositTypes,
                    'measurementMethods' => $measurementMethods,
                    'formKey' => 'service-'.$service->id,
                ])
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">{{ $service->inquiries()->count() }} محفوظ درخواستیں — ترتیب بدلنے سے پرانا ریکارڈ تبدیل نہیں ہوگا۔</small>
                    <button class="btn btn-primary px-4"><i class="fas fa-save ml-1"></i> محفوظ کریں</button>
                </div>
            </form>
        </div>
    </div>
    @empty
    <div class="card service-card"><div class="card-body text-center py-5"><i class="fas fa-cut fa-2x text-muted mb-3"></i><h2 class="h5">ابھی کوئی ٹیلرنگ خدمت شامل نہیں</h2><p class="text-muted mb-0">اوپر موجود فارم سے پہلی خدمت شامل کریں۔</p></div></div>
    @endforelse
</div>
</section>
<style>
.service-page{background:#f3f6f8;min-height:100vh}.service-hero{background:linear-gradient(135deg,#173f5f,#206f68);color:#fff;border-radius:18px;padding:28px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 12px 30px rgba(18,62,93,.16)}.service-hero h1{color:#fff!important}.service-card{border:0;border-radius:16px;box-shadow:0 8px 24px rgba(26,54,73,.08);overflow:hidden}.service-card .card-header{background:#fff;border-bottom:1px solid #e7edf0;padding:20px 24px}.service-card .card-body{padding:24px}.control-panel{border:1px solid #dce6ea;border-radius:14px;padding:18px;background:#f9fbfc}.choice-tile{display:flex;gap:10px;align-items:flex-start;border:1px solid #d9e4e8;border-radius:11px;padding:13px;background:#fff;height:100%;cursor:pointer}.choice-tile input{margin-top:5px}.choice-tile small{display:block;color:#6c757d;margin-top:3px}.state{display:inline-block;padding:5px 10px;border-radius:999px;font-size:12px;font-weight:700}.state-on{background:#dff5e8;color:#176b43}.state-off{background:#eef1f3;color:#66727a}@media(max-width:767px){.service-hero{display:block;padding:22px}.service-card .card-body{padding:18px}}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-service-form]').forEach(function (form) {
        var type = form.querySelector('[data-deposit-type]');
        var valueWrap = form.querySelector('[data-deposit-value-wrap]');
        var valueLabel = form.querySelector('[data-deposit-value-label]');
        function refreshDeposit() {
            var enabled = type.value !== 'none';
            valueWrap.hidden = !enabled;
            valueWrap.querySelector('input').disabled = !enabled;
            valueLabel.textContent = type.value === 'percentage' ? 'پیشگی فیصد' : 'پیشگی رقم';
        }
        type.addEventListener('change', refreshDeposit);
        refreshDeposit();
    });
});
</script>
@endsection
