@extends('main')
@section('content')
<section class="main-content">
    <div class="container py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-2">Roles and permissions</h1>
            <a href="{{ route('administrator.role.new') }}" class="btn btn-primary">Create role and permission</a>
        </div>
        <div class="card mb-4">
            <div class="card-header bg-white"><h2 class="h5 mb-0">Roles</h2></div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>#</th><th>Role name</th><th>Actions</th></tr></thead>
                    <tbody>
                        @forelse($roles as $role)
                            <tr><td>{{ $loop->iteration }}</td><td>{{ $role->name }}</td><td class="d-flex">
                                <a href="{{ route('administrator.role.edit', ['id' => $role->id]) }}" class="btn btn-sm btn-outline-primary mr-2">Edit</a>
                                <form action="{{ route('administrator.role.delete', ['id' => $role->id]) }}" method="POST" data-confirm="Delete this role?">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-outline-danger">Delete</button></form>
                            </td></tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted">No roles found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-header bg-white"><h2 class="h5 mb-0">Permissions</h2></div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>#</th><th>Permission name</th><th>Actions</th></tr></thead>
                    <tbody>
                        @forelse($perm as $permission)
                            <tr><td>{{ $loop->iteration }}</td><td>{{ $permission->name }}</td><td class="d-flex">
                                <a href="{{ route('administrator.perm.edit', ['id' => $permission->id]) }}" class="btn btn-sm btn-outline-primary mr-2">Edit</a>
                                <form action="{{ route('administrator.perm.delete', ['id' => $permission->id]) }}" method="POST" data-confirm="Delete this permission?">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-outline-danger">Delete</button></form>
                            </td></tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted">No permissions found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
