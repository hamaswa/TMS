@extends('main')

@push('styles')
<style>
    .admin-password { max-width: 520px; margin: 2.5rem auto; }
    .admin-password .card { border: 1px solid #e2e8f0; border-radius: 1rem; box-shadow: 0 12px 32px rgba(15, 23, 42, .06); }
    .admin-password .lock { width: 3rem; height: 3rem; border-radius: .9rem; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #1769e0, #20a3c4); color: #fff; font-size: 1.2rem; }
    .admin-password .form-control { min-height: 46px; border-radius: .6rem; }
    .admin-password .hint { padding: .75rem 1rem; border-radius: .6rem; background: #eef7ff; color: #31536f; font-size: .9rem; }
</style>
@endpush

@section('content')
<section class="main-content"><div class="container-fluid px-3">
    <div class="admin-password">
        <div class="card"><div class="card-body p-4">
            <div class="d-flex align-items-center mb-3" style="gap:.75rem">
                <div class="lock" aria-hidden="true"><i class="fas fa-lock"></i></div>
                <div><h1 class="h4 mb-0">Change password</h1><div class="text-muted small">Super admin account · {{ Auth::user()->email }}</div></div>
            </div>

            @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0 pl-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

            <div class="hint mb-3">Use at least 8 characters with an uppercase letter, a lowercase letter, a number and a symbol. Other browsers signed in to this account will be signed out.</div>

            <form method="POST" action="{{ route('administrator.password.update') }}">@csrf @method('PUT')
                <div class="form-group"><label for="current-password">Current password</label><input id="current-password" type="password" class="form-control @error('current_password') is-invalid @enderror" name="current_password" required autocomplete="current-password"></div>
                <div class="form-group"><label for="new-password">New password</label><input id="new-password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" minlength="8" required autocomplete="new-password"></div>
                <div class="form-group"><label for="new-password-confirmation">Confirm new password</label><input id="new-password-confirmation" type="password" class="form-control" name="password_confirmation" minlength="8" required autocomplete="new-password"></div>
                <button class="btn btn-primary btn-block">Save new password</button>
            </form>
        </div></div>
    </div>
</div></section>
@endsection
