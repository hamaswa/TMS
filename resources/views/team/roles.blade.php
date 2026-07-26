@extends('main')
@section('content')
<section class="main-content team-page" dir="rtl"><div class="container-fluid py-4 px-lg-5">
    @include('team.partials.workspace')

    <div class="card team-card mb-4"><div class="card-body p-4 p-lg-5">
        <div class="row align-items-center mb-4">
            <div class="col-lg-8"><h2 class="h4 font-weight-bold mb-1">نیا رول بنائیں</h2><p class="text-muted mb-0">تیار رول سے آغاز کریں یا ہر اجازت خود منتخب کریں۔ ملازم کو صرف اس کے کام کی ضروری رسائی دیں۔</p></div>
            <div class="col-lg-4 text-lg-left mt-3 mt-lg-0"><span class="badge badge-info px-3 py-2">کم از کم رسائی زیادہ محفوظ ہے</span></div>
        </div>
        <form method="POST" action="{{ route('admin.team.roles.store') }}">@csrf
            <div class="form-group" style="max-width:520px"><label class="font-weight-bold" for="new-role-name">رول کا نام</label><input id="new-role-name" class="form-control form-control-lg" name="name" value="{{ old('name') }}" placeholder="{{ $business->tailoring_enabled && ! $business->clothing_enabled ? 'مثلاً آرڈر منیجر' : ($business->clothing_enabled && ! $business->tailoring_enabled ? 'مثلاً سیلز پرسن' : 'مثلاً برانچ منیجر') }}" required></div>
            @include('team.partials.permission-selector', ['selectorId' => 'new-role-permissions', 'selectedPermissions' => old('permissions', []), 'roleNameTarget' => '#new-role-name'])
            <div class="d-flex justify-content-end mt-3"><button class="btn btn-primary btn-lg px-5"><i class="fas fa-user-shield ml-1"></i> رول محفوظ کریں</button></div>
        </form>
    </div></div>

    <div class="d-flex justify-content-between align-items-center mb-3"><div><h2 class="h4 font-weight-bold mb-1">کاروباری رولز</h2><p class="text-muted mb-0">ترمیم سے اس رول کے تمام ملازمین کی رسائی تبدیل ہوگی۔</p></div><span class="badge badge-secondary px-3 py-2">{{ $business->roles->count() }} رولز</span></div>
    <div class="row">
        @forelse($business->roles as $role)
            <div class="col-xl-6 mb-3"><div class="card team-card h-100"><div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start"><div><h3 class="h5 font-weight-bold mb-1">{{ $role->name }}</h3><span class="text-muted small">{{ $role->users_count }} ملازم · {{ count($role->permissions ?? []) }} اجازتیں</span></div><div class="d-flex"><a class="btn btn-sm btn-outline-primary ml-2" href="{{ route('admin.team.roles.edit', $role) }}">ترمیم</a><form method="POST" action="{{ route('admin.team.roles.destroy', $role) }}" data-confirm="کیا آپ یہ رول حذف کرنا چاہتے ہیں؟">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" {{ $role->users_count ? 'disabled' : '' }}>حذف</button></form></div></div>
                <div class="mt-3">@foreach($role->permissions as $permission)<span class="badge badge-light border mb-1">{{ \App\Models\BusinessRole::PERMISSIONS[$permission] ?? $permission }}</span>@endforeach</div>
            </div></div></div>
        @empty
            <div class="col-12"><div class="card team-card"><div class="card-body text-center text-muted py-5">ابھی کوئی رول نہیں بنایا گیا۔ اوپر تیار رول منتخب کرکے آغاز کریں۔</div></div></div>
        @endforelse
    </div>
</div></section>
@endsection
