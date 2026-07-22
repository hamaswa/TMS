@extends('layouts.app')
@section('body_class', 'employee-password-page')
@section('html_lang', 'ur')
@section('html_dir', 'rtl')
@push('styles')
<style>
    .employee-password-page{min-height:100vh;background:radial-gradient(circle at 80% 5%,#dfefff 0,transparent 32%),#f4f7fb;direction:rtl;text-align:right;font-family:"Noto Nastaliq Urdu","Noto Sans Arabic",Tahoma,Arial,sans-serif}.employee-password-page .app-navbar{display:none}.employee-password-page .app-main{padding:0!important}.password-shell{min-height:100vh;display:grid;place-items:center;padding:1.25rem}.password-card{width:100%;max-width:560px;padding:clamp(1.5rem,5vw,3rem);border:1px solid #dfe7f0;border-radius:24px;background:#fff;box-shadow:0 24px 70px rgba(31,61,93,.13)}.security-icon{display:grid;place-items:center;width:64px;height:64px;margin-bottom:1.25rem;border-radius:18px;background:linear-gradient(135deg,#1769e0,#20a3c4);color:#fff;font-size:1.5rem}.password-card h1{color:#102a43;font-weight:800}.password-card .form-control{min-height:52px;border-radius:12px}.security-note{padding:1rem;border-radius:12px;background:#eef7ff;color:#31536f}.password-card .btn{min-height:50px;border-radius:12px;font-weight:800}
</style>
@endpush
@section('content')
<main class="password-shell"><div class="password-card"><div class="security-icon" aria-hidden="true">🔒</div><span class="badge badge-primary mb-2">اکاؤنٹ سیکیورٹی</span><h1 class="h3">اپنا نیا پاس ورڈ بنائیں</h1><p class="text-muted">{{ $forced ? 'کلائنٹ نے آپ کے لیے عارضی پاس ورڈ جاری کیا ہے۔ کام شروع کرنے سے پہلے اسے تبدیل کرنا ضروری ہے۔' : ($expired ? 'آپ کے پاس ورڈ کی مقررہ مدت مکمل ہو گئی ہے۔ کام جاری رکھنے کے لیے نیا پاس ورڈ بنائیں۔' : 'اپنے اکاؤنٹ کے لیے مضبوط اور منفرد پاس ورڈ منتخب کریں۔') }}</p>
    <div class="security-note mb-4">کم از کم 8 حروف کے ساتھ بڑا حرف، چھوٹا حرف، عدد اور علامت استعمال کریں۔ اپنا پاس ورڈ کسی دوسرے شخص کے ساتھ شیئر نہ کریں۔</div>
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('employee.password.update') }}">@csrf @method('PUT')
        <div class="form-group"><label for="current-password">عارضی یا موجودہ پاس ورڈ</label><input id="current-password" type="password" class="form-control" name="current_password" required autocomplete="current-password"></div>
        <div class="form-group"><label for="new-password">نیا پاس ورڈ</label><input id="new-password" type="password" class="form-control" name="password" minlength="8" required autocomplete="new-password">@include('team.partials.password-strength', ['inputId' => 'new-password'])</div>
        <div class="form-group"><label for="new-password-confirmation">نئے پاس ورڈ کی تصدیق</label><input id="new-password-confirmation" type="password" class="form-control" name="password_confirmation" minlength="8" required autocomplete="new-password"></div>
        <button class="btn btn-primary btn-block">نیا پاس ورڈ محفوظ کریں</button>
    </form>
    <form method="POST" action="{{ route('logout') }}" class="text-center mt-3">@csrf<button class="btn btn-link text-muted">لاگ آؤٹ</button></form>
</div></main>
@endsection
