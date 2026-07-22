@extends('main')
@section('content')
<style>.employee-edit-page{min-height:calc(100vh - 70px);background:#f5f7fa}.employee-card{border:0;border-radius:18px;box-shadow:0 10px 30px rgba(31,45,61,.08)}.security-card{border:1px solid #f4d58d;background:#fffaf0}</style>
<section class="main-content employee-edit-page"><div class="container py-5">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="card employee-card mx-auto mb-4" style="max-width:760px"><div class="card-body p-4 p-lg-5">
        <h1 class="h4 font-weight-bold">ملازم کی معلومات میں ترمیم</h1><p class="text-muted">رول تبدیل کرنے سے اگلی درخواست پر نئی اجازتیں نافذ ہو جائیں گی۔</p>
        <form method="POST" action="{{ route('admin.team.employees.update', $employee) }}">@csrf @method('PUT')<div class="row">
            <div class="col-md-6 form-group"><label for="employee-name">نام</label><input id="employee-name" class="form-control" name="name" value="{{ old('name', $employee->name) }}" required></div>
            <div class="col-md-6 form-group"><label for="employee-title">عہدہ</label><input id="employee-title" class="form-control" name="job_title" value="{{ old('job_title', $employee->job_title) }}"></div>
            <div class="col-md-6 form-group"><label for="employee-username">یوزر نیم</label><input id="employee-username" class="form-control" name="username" dir="ltr" value="{{ old('username', $employee->username) }}" required></div>
            <div class="col-md-6 form-group"><label for="employee-email">ای میل</label><input id="employee-email" type="email" class="form-control text-left" dir="ltr" name="email" value="{{ old('email', $employee->email) }}" required></div>
            <div class="col-md-6 form-group"><label for="employee-phone">فون نمبر</label><input id="employee-phone" class="form-control" name="phone" dir="ltr" value="{{ old('phone', $employee->phone) }}"></div>
            <div class="col-12 form-group"><label for="employee-address">پتہ</label><textarea id="employee-address" class="form-control" name="address" rows="2">{{ old('address', $employee->address) }}</textarea></div>
            <div class="col-md-6 form-group"><label for="employee-role">رول</label><select id="employee-role" class="form-control" name="business_role_id" required>@foreach($roles as $role)<option value="{{ $role->id }}" {{ (string) old('business_role_id', $employee->business_role_id) === (string) $role->id ? 'selected' : '' }}>{{ $role->name }}</option>@endforeach</select></div>
            <div class="col-md-6 form-group d-flex align-items-end"><label class="border rounded p-2 w-100 mb-0"><input type="hidden" name="employee_active" value="0"><input type="checkbox" name="employee_active" value="1" class="ml-2" {{ old('employee_active', $employee->employee_active) ? 'checked' : '' }}>اکاؤنٹ فعال ہے</label></div>
        </div><div class="d-flex mt-3"><button class="btn btn-primary ml-2">معلومات محفوظ کریں</button><a class="btn btn-outline-secondary" href="{{ route('admin.team.employees.index') }}">واپس</a></div></form>
    </div></div>
    <div class="card employee-card security-card mx-auto" style="max-width:760px"><div class="card-body p-4 p-lg-5">
        <div class="d-flex align-items-start"><i class="fas fa-key text-warning fa-2x ml-3"></i><div><h2 class="h5 font-weight-bold">عارضی پاس ورڈ جاری کریں</h2><p class="text-muted">ملازم کی موجودہ رسائی ختم ہو جائے گی اور اگلے لاگ اِن پر اسے لازماً اپنا نیا پاس ورڈ بنانا ہوگا۔</p></div></div>
        @if($employee->must_change_password)<div class="alert alert-warning">یہ ملازم ابھی عارضی پاس ورڈ استعمال کر رہا ہے اور پاس ورڈ تبدیل کیے بغیر کاروباری صفحات نہیں کھول سکتا۔</div>@endif
        <form method="POST" action="{{ route('admin.team.employees.password', $employee) }}">@csrf @method('PATCH')<div class="row"><div class="col-md-6 form-group"><label for="temporary-password">عارضی پاس ورڈ</label><input id="temporary-password" type="password" class="form-control" name="temporary_password" minlength="8" required autocomplete="new-password">@include('team.partials.password-strength', ['inputId' => 'temporary-password'])</div><div class="col-md-6 form-group"><label for="temporary-password-confirmation">عارضی پاس ورڈ کی تصدیق</label><input id="temporary-password-confirmation" type="password" class="form-control" name="temporary_password_confirmation" minlength="8" required autocomplete="new-password"></div></div><button class="btn btn-warning"><i class="fas fa-shield-alt ml-1"></i> عارضی پاس ورڈ محفوظ کریں</button></form>
    </div></div>
</div></section>
@endsection
