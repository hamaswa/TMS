@extends('main')
@section('content')
<section class="main-content"><div class="container-fluid px-3 px-md-4 py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div><h2 class="mb-1">Client accounts</h2><p class="text-muted mb-0">Review applications, control access, and inspect each client without deleting business records.</p></div>
        <div><a href="{{ route('administrator.marketplace.index') }}" class="btn btn-outline-success mr-2"><i class="fas fa-store mr-1"></i> Marketplace</a><a href="{{ route('administrator.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i> New client</a></div>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('warning'))<div class="alert alert-warning">{{ session('warning') }}</div>@endif
    <form method="GET" class="card card-body mb-3"><div class="form-row align-items-end">
        <div class="col-md-5 mb-2"><label>Search</label><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Client name or email"></div>
        <div class="col-md-2 mb-2"><label>Status</label><select name="status" class="form-control"><option value="">All statuses</option>@foreach(\App\Models\Business::STATUSES as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
        <div class="col-md-3 mb-2"><label>Module</label><select name="module" class="form-control"><option value="">All modules</option><option value="tailoring" @selected(request('module') === 'tailoring')>Tailoring</option><option value="clothing" @selected(request('module') === 'clothing')>Clothing</option><option value="both" @selected(request('module') === 'both')>Both</option></select></div>
        <div class="col-md-2 mb-2"><button class="btn btn-outline-primary btn-block">Filter</button></div>
    </div></form>
    <div class="card"><div class="table-responsive"><table class="table table-hover mb-0"><thead class="thead-light"><tr><th>Client</th><th>Status</th><th>Authorized modules</th><th>Registered</th><th class="text-right">Actions</th></tr></thead><tbody>
        @forelse($users as $user)
        @php($status = $user->ownedBusiness?->status ?? 'active')
        <tr><td><strong>{{ $user->name }}</strong><br><small class="text-muted">{{ $user->email }}</small></td><td><span class="badge badge-{{ ['active'=>'success','pending'=>'warning','suspended'=>'danger','rejected'=>'secondary'][$status] ?? 'secondary' }}">{{ ucfirst($status) }}</span></td><td>@if($user->tailoring_access)<span class="badge badge-primary mr-1">Tailoring</span>@endif @if($user->clothing_access)<span class="badge badge-info mr-1">Clothing</span>@endif</td><td>{{ optional($user->created_at)->format('d M Y') }}</td><td class="text-right"><a href="{{ route('administrator.clients.show',$user) }}" class="btn btn-sm btn-primary">View details</a> <a href="{{ route('administrator.edit',$user) }}" class="btn btn-sm btn-outline-primary">Edit access</a></td></tr>
        @empty<tr><td colspan="5" class="text-center text-muted py-5">No client accounts match these filters.</td></tr>@endforelse
    </tbody></table></div></div>
    <div class="mt-3">{{ $users->links() }}</div>
</div></section>
@endsection
