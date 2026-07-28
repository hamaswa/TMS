@extends('main')
@section('content')
<section class="main-content">
    <div class="container py-4">
        <div class="card mx-auto" style="max-width:760px">
            <div class="card-body p-4">
                @include('inc.message')
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">Create role and permission</h1>
                    <a href="{{ route('administrator.roles-permi') }}" class="btn btn-outline-primary">All roles and permissions</a>
                </div>
                <form method="POST" action="{{ route('administrator.role.create') }}">
                    @csrf
                    <div class="form-group">
                        <label for="role_name">Role name</label>
                        <input id="role_name" type="text" class="form-control" name="name" required value="{{ old('name') }}">
                        @error('name')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="permission_name">Permission name</label>
                        <input id="permission_name" type="text" class="form-control" name="perm" required value="{{ old('perm') }}">
                        @error('perm')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary">Create role and permission</button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
