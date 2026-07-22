@extends('main')
@section('content')
<section class="main-content team-page" dir="rtl"><div class="container-fluid py-4 px-lg-5">
    @include('team.partials.workspace')

    <div class="row mb-4">
        <div class="col-md-4 mb-3"><a class="team-action" href="{{ route('admin.team.employees.index') }}"><span class="team-action-icon"><i class="fas fa-users"></i></span><h2 class="h5 font-weight-bold">ملازمین</h2><p class="text-muted mb-2">نیا ملازم شامل کریں، رابطہ معلومات، رول اور اکاؤنٹ کی حالت سنبھالیں۔</p><strong>{{ $business->members->count() }} ملازمین <i class="fas fa-arrow-left mr-1"></i></strong></a></div>
        <div class="col-md-4 mb-3"><a class="team-action" href="{{ route('admin.team.roles.index') }}"><span class="team-action-icon"><i class="fas fa-user-shield"></i></span><h2 class="h5 font-weight-bold">رولز اور اجازتیں</h2><p class="text-muted mb-2">سیلز، ٹیلرنگ، اسٹاک اور دیگر کاموں کی رسائی الگ مقرر کریں۔</p><strong>{{ $business->roles->count() }} رولز <i class="fas fa-arrow-left mr-1"></i></strong></a></div>
        <div class="col-md-4 mb-3"><a class="team-action" href="{{ route('admin.team.security') }}"><span class="team-action-icon"><i class="fas fa-lock"></i></span><h2 class="h5 font-weight-bold">پاس ورڈ پالیسی</h2><p class="text-muted mb-2">ملازمین کے پاس ورڈ کی میعاد اور اکاؤنٹ سیکیورٹی مقرر کریں۔</p><strong>{{ $business->password_expiry_days ? $business->password_expiry_days.' دن' : 'میعاد بند ہے' }} <i class="fas fa-arrow-left mr-1"></i></strong></a></div>
    </div>

    <div class="card team-card"><div class="card-body p-4"><div class="d-flex justify-content-between align-items-center mb-3"><div><h2 class="h5 font-weight-bold mb-1">حالیہ ٹیم</h2><p class="text-muted mb-0">فوری جائزہ؛ مکمل انتظام ملازمین کے صفحے پر دستیاب ہے۔</p></div><a class="btn btn-outline-primary" href="{{ route('admin.team.employees.index') }}">تمام ملازمین</a></div><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>ملازم</th><th>عہدہ</th><th>رول</th><th>حالت</th></tr></thead><tbody>
        @forelse($business->members->take(5) as $employee)<tr><td><strong>{{ $employee->name }}</strong><br><small class="text-muted" dir="ltr">{{ '@'.$employee->username }}</small></td><td>{{ $employee->job_title ?: '—' }}</td><td>{{ $employee->businessRole?->name ?: 'رول مقرر نہیں' }}</td><td><span class="status-dot {{ $employee->employee_active ? 'status-on' : 'status-off' }}"></span>{{ $employee->employee_active ? ($employee->must_change_password ? 'عارضی پاس ورڈ' : ($employee->employeePasswordExpired() ? 'پاس ورڈ کی میعاد ختم' : 'فعال')) : 'غیر فعال' }}</td></tr>
        @empty<tr><td colspan="4" class="text-center text-muted py-4">ابھی کوئی ملازم شامل نہیں کیا گیا۔</td></tr>@endforelse
    </tbody></table></div></div></div>
</div></section>
@endsection
