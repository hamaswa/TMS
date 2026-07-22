@extends('main')
@section('content')
<section class="main-content"><div class="container-fluid px-3 px-md-4 py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4"><div><h2 class="mb-1">Client access</h2><p class="text-muted mb-0">Authorize tailoring, clothing operations, or both for each client.</p></div><a href="{{ route('administrator.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i> New client</a></div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card"><div class="table-responsive"><table class="table table-hover mb-0"><thead class="thead-light"><tr><th>Client</th><th>Role</th><th>Authorized modules</th><th class="text-right">Actions</th></tr></thead><tbody>
        @foreach($users as $user)<tr><td><strong>{{ $user->name }}</strong><br><small class="text-muted">{{ $user->email }}</small></td><td>{{ $user->getRoleNames()->map(fn($role)=>ucwords(str_replace('_',' ',$role)))->join(', ') ?: 'No role' }}</td><td>@if($user->tailoring_access)<span class="badge badge-primary mr-1">Tailoring</span>@endif @if($user->clothing_access)<span class="badge badge-info mr-1">Clothing sales & purchases</span>@endif @if(!$user->tailoring_access&&!$user->clothing_access)<span class="badge badge-secondary">No client modules</span>@endif</td><td class="text-right"><a href="{{ route('administrator.edit',$user) }}" class="btn btn-sm btn-outline-primary">Edit access</a></td></tr>
        @endforeach
    </tbody></table></div></div>
</div></section>
@endsection
