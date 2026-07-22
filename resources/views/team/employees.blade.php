@extends('main')
@section('content')
<section class="main-content team-page" dir="rtl"><div class="container-fluid py-4 px-lg-5">
    @include('team.partials.workspace')

    <div class="card team-card mb-4"><div class="card-body p-4"><div class="d-flex justify-content-between align-items-center mb-3"><div><h2 class="h5 font-weight-bold mb-1">نیا ملازم شامل کریں</h2><p class="text-muted mb-0">ملازم کی لاگ اِن معلومات اور کام کا رول مقرر کریں۔</p></div>@if($business->roles->isNotEmpty())<span class="badge badge-primary px-3 py-2">{{ $business->roles->count() }} رولز دستیاب</span>@endif</div>
        @if($business->roles->isEmpty())
            <div class="alert alert-info mb-0">ملازم شامل کرنے سے پہلے کم از کم ایک رول بنائیں۔ <a href="{{ route('admin.team.roles.index') }}">رول بنائیں</a></div>
        @else
            <form method="POST" action="{{ route('admin.team.employees.store') }}">@csrf<div class="row">
                <div class="col-md-6 form-group"><label>ملازم کا نام</label><input class="form-control" name="name" value="{{ old('name') }}" required></div>
                <div class="col-md-6 form-group"><label>عہدہ</label><input class="form-control" name="job_title" value="{{ old('job_title') }}" placeholder="مثلاً کاؤنٹر سیلز"></div>
                <div class="col-md-6 form-group"><label>یوزر نیم</label><input class="form-control" name="username" value="{{ old('username') }}" dir="ltr" placeholder="مثلاً sale.ahmad" required></div>
                <div class="col-md-6 form-group"><label>ای میل</label><input type="email" class="form-control text-left" dir="ltr" name="email" value="{{ old('email') }}" required></div>
                <div class="col-md-6 form-group"><label>فون نمبر</label><input class="form-control" name="phone" value="{{ old('phone') }}" dir="ltr"></div>
                <div class="col-md-6 form-group"><label>رول</label><select class="form-control" name="business_role_id" required><option value="">رول منتخب کریں</option>@foreach($business->roles as $role)<option value="{{ $role->id }}" @selected((string) old('business_role_id') === (string) $role->id)>{{ $role->name }}</option>@endforeach</select></div>
                <div class="col-md-6 form-group"><label for="employee-temporary-password">عارضی پاس ورڈ</label><input id="employee-temporary-password" type="password" class="form-control" name="password" minlength="8" required autocomplete="new-password"><small class="form-text text-muted">ملازم پہلے لاگ اِن پر اپنا نیا پاس ورڈ بنائے گا۔</small>@include('team.partials.password-strength', ['inputId' => 'employee-temporary-password'])</div>
                <div class="col-md-6 form-group"><label>پتہ</label><textarea class="form-control" name="address" rows="4">{{ old('address') }}</textarea></div>
                <div class="col-12 text-left"><button class="btn btn-success px-4"><i class="fas fa-user-plus ml-1"></i> ملازم شامل کریں</button></div>
            </div></form>
        @endif
    </div></div>

    <div class="card team-card"><div class="card-body p-4"><div class="d-flex justify-content-between align-items-center mb-3"><div><h2 class="h5 font-weight-bold mb-1">موجودہ ملازمین</h2><p class="text-muted mb-0">ہر ملازم کا رول، حالت اور لاگ اِن شناخت۔</p></div><span class="badge badge-secondary">{{ $business->members->count() }}</span></div><div class="table-responsive"><table class="table table-hover"><thead><tr><th>ملازم</th><th>رابطہ</th><th>عہدہ</th><th>رول</th><th>حالت</th><th></th></tr></thead><tbody>
        @forelse($business->members as $employee)<tr><td><strong>{{ $employee->name }}</strong><br><small class="text-muted" dir="ltr">{{ '@'.$employee->username }}</small></td><td><small dir="ltr">{{ $employee->email }}</small>@if($employee->phone)<br><small dir="ltr">{{ $employee->phone }}</small>@endif</td><td>{{ $employee->job_title ?: '—' }}</td><td>{{ $employee->businessRole?->name ?: 'رول مقرر نہیں' }}</td><td><span class="status-dot {{ $employee->employee_active ? 'status-on' : 'status-off' }}"></span>{{ $employee->employee_active ? ($employee->must_change_password ? 'عارضی پاس ورڈ' : ($employee->employeePasswordExpired() ? 'پاس ورڈ کی میعاد ختم' : 'فعال')) : 'غیر فعال' }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.team.employees.edit', $employee) }}">ترمیم</a></td></tr>
        @empty<tr><td colspan="6" class="text-center text-muted py-4">ابھی کوئی ملازم شامل نہیں کیا گیا۔</td></tr>@endforelse
    </tbody></table></div></div></div>
</div></section>
@endsection
