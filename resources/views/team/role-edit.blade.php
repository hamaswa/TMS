@extends('main')
@section('content')
<section class="main-content team-page" dir="rtl"><div class="container-fluid py-4 px-lg-5">
    @include('team.partials.workspace')
    <div class="card team-card"><div class="card-body p-4 p-lg-5">
        @php($roleUsersCount = $role->users()->count())
        <div class="mb-4"><span class="badge badge-light text-primary mb-2">رول میں ترمیم</span><h1 class="h3 font-weight-bold mb-1">{{ $role->name }}</h1><p class="text-muted mb-0">محفوظ کرنے کے بعد اس رول کے {{ $roleUsersCount }} {{ $roleUsersCount === 1 ? 'ملازم' : 'ملازمین' }} کی رسائی فوراً تبدیل ہوگی۔</p></div>
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form method="POST" action="{{ route('admin.team.roles.update', $role) }}">@csrf @method('PUT')
            <div class="form-group" style="max-width:520px"><label class="font-weight-bold" for="edit-role-name">رول کا نام</label><input id="edit-role-name" class="form-control form-control-lg" name="name" value="{{ old('name', $role->name) }}" required></div>
            @include('team.partials.permission-selector', ['selectorId' => 'edit-role-permissions', 'selectedPermissions' => old('permissions', $role->permissions), 'roleNameTarget' => '#edit-role-name'])
            <div class="d-flex justify-content-end mt-3"><a class="btn btn-outline-secondary btn-lg ml-2" href="{{ route('admin.team.roles.index') }}">منسوخ کریں</a><button class="btn btn-primary btn-lg px-4">تبدیلی محفوظ کریں</button></div>
        </form>
    </div></div>
</div></section>
@endsection
