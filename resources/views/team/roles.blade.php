@extends('main')
@section('content')
<section class="main-content team-page" dir="rtl"><div class="container-fluid py-4 px-lg-5">
    @include('team.partials.workspace')

    <div class="row">
        <div class="col-xl-5 mb-4"><div class="card team-card"><div class="card-body p-4"><h2 class="h5 font-weight-bold mb-1">نیا رول بنائیں</h2><p class="text-muted">رول کام کی ذمہ داری ہے؛ اجازتیں بتاتی ہیں کہ ملازم کیا دیکھ اور کر سکتا ہے۔</p><form method="POST" action="{{ route('admin.team.roles.store') }}">@csrf
            <div class="form-group"><label>رول کا نام</label><input class="form-control" name="name" value="{{ old('name') }}" placeholder="مثلاً سیلز پرسن" required></div>
            <label class="d-block font-weight-bold">اجازتیں منتخب کریں</label><div class="row">@foreach($permissions as $key => $label)<div class="col-12 mb-2"><label class="permission-box d-flex align-items-center mb-0"><input type="checkbox" name="permissions[]" value="{{ $key }}" class="ml-2" @checked(in_array($key, old('permissions', []), true))><span>{{ $label }}</span></label></div>@endforeach</div>
            <button class="btn btn-primary btn-block mt-2"><i class="fas fa-user-shield ml-1"></i> رول محفوظ کریں</button>
        </form></div></div></div>
        <div class="col-xl-7 mb-4"><div class="card team-card h-100"><div class="card-body p-4"><div class="d-flex justify-content-between align-items-center mb-3"><div><h2 class="h5 font-weight-bold mb-1">کاروباری رولز</h2><p class="text-muted mb-0">ترمیم سے اس رول کے تمام ملازمین کی رسائی تبدیل ہو گی۔</p></div><span class="badge badge-secondary">{{ $business->roles->count() }}</span></div>
            @forelse($business->roles as $role)<div class="permission-box mb-3"><div class="d-flex justify-content-between align-items-center"><div><strong>{{ $role->name }}</strong><span class="badge badge-light mr-2">{{ $role->users_count }} ملازم</span></div><div class="d-flex"><a class="btn btn-sm btn-outline-primary ml-2" href="{{ route('admin.team.roles.edit', $role) }}">ترمیم</a><form method="POST" action="{{ route('admin.team.roles.destroy', $role) }}" onsubmit="return confirm('کیا آپ یہ رول حذف کرنا چاہتے ہیں؟')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" {{ $role->users_count ? 'disabled' : '' }}>حذف</button></form></div></div><div class="mt-3">@foreach($role->permissions as $permission)<span class="badge badge-info mb-1">{{ \App\Models\BusinessRole::PERMISSIONS[$permission] ?? $permission }}</span>@endforeach</div></div>
            @empty<div class="text-center text-muted py-5">ابھی کوئی رول نہیں بنایا گیا۔</div>@endforelse
        </div></div></div>
    </div>
</div></section>
@endsection
