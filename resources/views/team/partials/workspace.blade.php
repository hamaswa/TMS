<style>
    .team-page{background:#f5f7fa;min-height:calc(100vh - 70px)}
    .team-hero{background:linear-gradient(135deg,#102a43,#1769aa);color:#fff;border-radius:20px;padding:1.7rem;box-shadow:0 14px 35px rgba(16,42,67,.16)}
    .team-hero h1,.team-hero strong{color:#fff!important}.team-hero p,.team-hero small{color:rgba(255,255,255,.8)!important}
    .team-card{border:0;border-radius:18px;box-shadow:0 10px 28px rgba(31,45,61,.08)}
    .team-tabs{display:flex;flex-wrap:wrap;gap:.55rem}.team-tabs .btn{border-radius:999px;padding:.55rem 1rem;font-weight:700}
    .team-action{display:block;height:100%;padding:1.3rem;border:1px solid #dfe7f0;border-radius:16px;background:#fff;color:#243b53;transition:.18s ease}
    .team-action:hover{text-decoration:none;transform:translateY(-2px);box-shadow:0 12px 28px rgba(31,45,61,.1);color:#1769aa}
    .team-action-icon{width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;background:#eaf4fb;color:#1769aa;font-size:1.25rem;margin-bottom:1rem}
    .permission-box{border:1px solid #dfe7f0;border-radius:12px;padding:.8rem;height:100%;background:#fbfdff}
    .status-dot{display:inline-block;width:9px;height:9px;border-radius:50%;margin-left:.35rem}.status-on{background:#28a745}.status-off{background:#dc3545}
</style>

<div class="team-hero mb-4">
    <div class="row align-items-center">
        <div class="col-lg-8"><span class="badge badge-light text-primary mb-2">کاروباری ٹیم</span><h1 class="h3 font-weight-bold mb-2">ملازمین اور اجازتوں کا انتظام</h1><p class="mb-0">ملازمین، رولز اور سیکیورٹی کو الگ اور آسان حصوں میں سنبھالیں۔</p></div>
        <div class="col-lg-4 text-lg-left mt-3 mt-lg-0"><strong>{{ $business->name }}</strong><br><small>{{ $business->members->count() }} ملازمین · {{ $business->roles->count() }} رولز</small></div>
    </div>
</div>

<nav class="team-tabs mb-4" aria-label="ٹیم مینجمنٹ">
    <a class="btn {{ request()->routeIs('admin.team.index') ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('admin.team.index') }}"><i class="fas fa-th-large ml-1"></i> خلاصہ</a>
    <a class="btn {{ request()->routeIs('admin.team.employees.*') ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('admin.team.employees.index') }}"><i class="fas fa-users ml-1"></i> ملازمین</a>
    <a class="btn {{ request()->routeIs('admin.team.roles.*') ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('admin.team.roles.index') }}"><i class="fas fa-user-shield ml-1"></i> رولز اور اجازتیں</a>
    <a class="btn {{ request()->routeIs('admin.team.security') ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('admin.team.security') }}"><i class="fas fa-lock ml-1"></i> پاس ورڈ پالیسی</a>
</nav>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><strong>براہ کرم درج ذیل معلومات درست کریں:</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
