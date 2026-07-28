@extends('main')
@section('content')
<section class="main-content">
    <div class="container py-4">
        <div class="card mx-auto" style="max-width:900px">
            <div class="card-body p-4">
                <h1 class="h3 mb-4">Edit roles for {{ $user->name }}</h1>
                <form method="POST" action="{{ route('administrator.updateUserRoles', ['id' => $user->id]) }}">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="userName">User name</label>
                        <input type="text" class="form-control" id="userName" value="{{ $user->name }}" readonly>
                    </div>
                    <fieldset class="border rounded p-3 mb-4">
                        <legend class="h5 w-auto px-2">Roles</legend>
                        @forelse($allRoles as $role)
                            <div class="custom-control custom-checkbox mb-2">
                                <input id="role_{{ $role->id }}" class="custom-control-input" type="checkbox" name="userRoles[]" value="{{ $role->name }}" @checked(in_array($role->name, $roles->toArray(), true))>
                                <label class="custom-control-label" for="role_{{ $role->id }}">{{ $role->name }}</label>
                            </div>
                        @empty
                            <p class="text-muted mb-0">No roles are available.</p>
                        @endforelse
                    </fieldset>
                    <fieldset class="border rounded p-3 mb-4">
                        <legend class="h5 w-auto px-2">Direct permissions</legend>
                        @forelse($allPermissions as $permission)
                            <div class="custom-control custom-checkbox mb-2">
                                <input id="permission_{{ $permission->id }}" class="custom-control-input" type="checkbox" name="userPermissions[]" value="{{ $permission->name }}" @checked(in_array($permission->name, $permissions->pluck('name')->toArray(), true))>
                                <label class="custom-control-label" for="permission_{{ $permission->id }}">{{ $permission->name }}</label>
                            </div>
                        @empty
                            <p class="text-muted mb-0">No permissions are available.</p>
                        @endforelse
                    </fieldset>
                    <button type="submit" class="btn btn-primary">Save access</button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
