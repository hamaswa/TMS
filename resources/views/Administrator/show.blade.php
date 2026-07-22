@extends('main')
@section('content')
<section class="main-content"><div class="container-fluid px-3 px-md-4 py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start mb-4"><div><a href="{{ route('administrator.index') }}" class="text-muted">&larr; Client accounts</a><h2 class="mb-1 mt-2">{{ $user->name }}</h2><p class="text-muted mb-0">{{ $user->email }}</p></div><a href="{{ route('administrator.edit', $user) }}" class="btn btn-outline-primary">Edit client and modules</a></div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4"><div class="card-body"><div class="d-flex justify-content-between align-items-center mb-3"><h5 class="mb-0">Account status</h5>@php($status=$business->status)<span class="badge badge-{{ ['active'=>'success','pending'=>'warning','suspended'=>'danger','rejected'=>'secondary'][$status] ?? 'secondary' }} px-3 py-2">{{ ucfirst($status) }}</span></div>
                <dl class="row mb-0"><dt class="col-sm-4">Business</dt><dd class="col-sm-8">{{ $business->name }}</dd><dt class="col-sm-4">Tailor shop code</dt><dd class="col-sm-8"><code>{{ $business->shop_code }}</code></dd><dt class="col-sm-4">Modules</dt><dd class="col-sm-8">{{ collect([$business->tailoring_enabled ? 'Tailoring' : null, $business->clothing_enabled ? 'Clothing sales & purchases' : null])->filter()->join(', ') ?: 'None' }}</dd><dt class="col-sm-4">Approved</dt><dd class="col-sm-8">{{ $business->approved_at?->format('d M Y, h:i A') ?? 'Not yet approved' }}</dd>@if($business->status_reason)<dt class="col-sm-4">Latest reason</dt><dd class="col-sm-8">{{ $business->status_reason }}</dd>@endif</dl>
            </div></div>
            <div class="card mb-4"><div class="card-header bg-white"><h5 class="mb-0">Business data summary</h5></div><div class="card-body"><div class="row text-center">@foreach($metrics as $label=>$value)<div class="col-6 col-md-3 mb-3"><div class="border rounded p-3 h-100"><div class="h3 mb-1">{{ number_format($value) }}</div><small class="text-muted">{{ ucwords(str_replace('_',' ',$label)) }}</small></div></div>@endforeach</div></div></div>
            <div class="card"><div class="card-header bg-white"><h5 class="mb-0">Status history</h5></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Date</th><th>Change</th><th>Changed by</th><th>Reason</th></tr></thead><tbody>@forelse($business->statusHistory as $entry)<tr><td>{{ $entry->created_at?->format('d M Y, h:i A') }}</td><td>{{ ucfirst($entry->from_status ?? 'new') }} &rarr; {{ ucfirst($entry->to_status) }}</td><td>{{ $entry->changedBy?->name ?? 'System' }}</td><td>{{ $entry->reason ?: '—' }}</td></tr>@empty<tr><td colspan="4" class="text-muted text-center">No status changes recorded.</td></tr>@endforelse</tbody></table></div></div>
        </div>
        <div class="col-lg-4"><div class="card"><div class="card-header bg-white"><h5 class="mb-0">Account controls</h5></div><div class="card-body">
            @if($status === 'pending')
                <form method="POST" action="{{ route('administrator.clients.status',$user) }}" class="mb-3">@csrf @method('PATCH')<input type="hidden" name="status" value="active"><button class="btn btn-success btn-block">Approve and activate</button></form>
                <form method="POST" action="{{ route('administrator.clients.status',$user) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="rejected"><label>Reason for rejection</label><textarea name="reason" class="form-control mb-2" required maxlength="1000"></textarea><button class="btn btn-outline-danger btn-block">Reject application</button></form>
            @elseif($status === 'active')
                <form method="POST" action="{{ route('administrator.clients.status',$user) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="suspended"><label>Reason for suspension</label><textarea name="reason" class="form-control mb-2" required maxlength="1000"></textarea><button class="btn btn-danger btn-block">Deactivate account</button></form>
            @else
                <form method="POST" action="{{ route('administrator.clients.status',$user) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="active"><button class="btn btn-success btn-block">Activate account</button></form>
            @endif
            <p class="small text-muted mt-3 mb-0">Deactivation blocks client and employee access immediately. All customers, orders, stock and transaction records remain unchanged.</p>
        </div></div></div>
    </div>
</div></section>
@endsection
