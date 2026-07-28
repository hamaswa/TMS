@extends('main')
@section('content')
<section class="main-content">
    <div class="container-fluid px-3 px-md-4 py-4">
        <h1 class="h3 mb-4">User roles and permissions</h1>
        <div class="card">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>User</th><th>Roles</th><th>Role permissions</th><th>Action</th></tr></thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->roles->pluck('name')->join(', ') ?: 'None' }}</td>
                                <td>{{ $user->roles->flatMap->permissions->pluck('name')->unique()->join(', ') ?: 'None' }}</td>
                                <td><a href="{{ route('administrator.editUserRoles', ['id' => $user->id]) }}" class="btn btn-sm btn-outline-primary">Edit access</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">No users found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
