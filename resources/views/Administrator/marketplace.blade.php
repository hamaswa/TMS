@extends('main')

@section('content')
<style>
    .marketplace-metrics .card{border:0;box-shadow:0 6px 20px rgba(20,55,44,.08)}
    .marketplace-table td{vertical-align:middle}
    .marketplace-controls{min-width:220px}
    @media(max-width:767px){.marketplace-head{display:block!important}.marketplace-head .btn{margin-top:12px}.marketplace-controls{min-width:190px}}
</style>
<section class="main-content">
<div class="container-fluid px-3 px-md-4 py-4">
    <div class="marketplace-head d-flex justify-content-between align-items-start mb-4">
        <div><a href="{{ route('administrator.index') }}" class="text-muted">&larr; Client accounts</a><h2 class="mb-1 mt-2">Marketplace oversight</h2><p class="text-muted mb-0">Review public storefronts, commercial activity, and publication safety without deleting client records.</p></div>
        <a class="btn btn-outline-primary" target="_blank" rel="noopener" href="{{ route('storefront.index') }}">Open public directory</a>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <div class="row marketplace-metrics mb-3">
        <div class="col-6 col-lg mb-3"><div class="card h-100"><div class="card-body"><div class="h3 mb-0">{{ number_format($metrics['configured']) }}</div><small class="text-muted">Configured</small></div></div></div>
        <div class="col-6 col-lg mb-3"><div class="card h-100"><div class="card-body"><div class="h3 text-success mb-0">{{ number_format($metrics['public']) }}</div><small class="text-muted">Public now</small></div></div></div>
        <div class="col-6 col-lg mb-3"><div class="card h-100"><div class="card-body"><div class="h3 text-danger mb-0">{{ number_format($metrics['paused']) }}</div><small class="text-muted">Moderation paused</small></div></div></div>
        <div class="col-6 col-lg mb-3"><div class="card h-100"><div class="card-body"><div class="h3 text-warning mb-0">{{ number_format($metrics['pending_orders']) }}</div><small class="text-muted">Pending orders</small></div></div></div>
        <div class="col-12 col-lg mb-3"><div class="card h-100"><div class="card-body"><div class="h3 mb-0">Rs {{ number_format($metrics['order_value'],2) }}</div><small class="text-muted">Non-cancelled order value</small></div></div></div>
    </div>
    <form method="GET" class="card card-body mb-3"><div class="form-row align-items-end">
        <div class="col-lg-4 col-md-6 mb-2"><label for="market_search">Search</label><input id="market_search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Storefront, client, slug, or email"></div>
        <div class="col-lg-2 col-md-3 mb-2"><label for="publication">Publication</label><select id="publication" name="publication" class="form-control"><option value="">All</option><option value="published" @selected(request('publication')==='published')>Published</option><option value="draft" @selected(request('publication')==='draft')>Draft</option></select></div>
        <div class="col-lg-2 col-md-3 mb-2"><label for="moderation">Moderation</label><select id="moderation" name="moderation" class="form-control"><option value="">All</option><option value="active" @selected(request('moderation')==='active')>Allowed</option><option value="paused" @selected(request('moderation')==='paused')>Paused</option></select></div>
        <div class="col-lg-2 col-md-3 mb-2"><label for="business_status">Client status</label><select id="business_status" name="business_status" class="form-control"><option value="">All</option>@foreach(\App\Models\Business::STATUSES as $status)<option value="{{ $status }}" @selected(request('business_status')===$status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
        <div class="col-lg-2 col-md-3 mb-2"><label for="market_module">Module</label><select id="market_module" name="module" class="form-control"><option value="">All</option><option value="clothing" @selected(request('module')==='clothing')>Clothing</option><option value="tailoring" @selected(request('module')==='tailoring')>Tailoring</option><option value="both" @selected(request('module')==='both')>Both</option></select></div>
        <div class="col-12"><button class="btn btn-primary">Apply filters</button> <a class="btn btn-light" href="{{ route('administrator.marketplace.index') }}">Clear</a></div>
    </div></form>
    <div class="card"><div class="table-responsive"><table class="table table-hover marketplace-table mb-0">
        <thead class="thead-light"><tr><th>Storefront</th><th>Visibility</th><th>Content</th><th>Customer activity</th><th>Order value</th><th>Moderation</th></tr></thead>
        <tbody>
        @forelse($storefronts as $storefront)
            @php($businessStatus=$storefront->business?->status ?? 'unknown')
            <tr>
                <td><strong>{{ $storefront->display_name }}</strong><br><small class="text-muted">{{ $storefront->business?->owner?->name }} · {{ $storefront->business?->owner?->email }}</small><br><code>/shops/{{ $storefront->slug }}</code><div class="mt-2"><a href="{{ route('administrator.clients.show',$storefront->business->owner_user_id) }}">Client details</a>@if($storefront->is_published && $storefront->moderation_status==='active' && $businessStatus==='active') · <a target="_blank" rel="noopener" href="{{ route('storefront.show',$storefront) }}">Public page</a>@endif</div></td>
                <td><span class="badge badge-{{ $businessStatus==='active'?'success':($businessStatus==='pending'?'warning':'secondary') }}">{{ ucfirst($businessStatus) }} client</span><br><span class="badge badge-{{ $storefront->is_published?'primary':'secondary' }} mt-1">{{ $storefront->is_published?'Published':'Draft' }}</span><br><span class="badge badge-{{ $storefront->moderation_status==='active'?'success':'danger' }} mt-1">{{ $storefront->moderation_status==='active'?'Allowed':'Paused' }}</span></td>
                <td>{{ number_format($storefront->published_clothing_count) }} clothes<br>{{ number_format($storefront->published_services_count) }} services</td>
                <td>{{ number_format($storefront->inquiries_count) }} inquiries<br>{{ number_format($storefront->orders_count) }} orders @if($storefront->pending_orders_count)<span class="badge badge-warning">{{ $storefront->pending_orders_count }} pending</span>@endif</td>
                <td>Rs {{ number_format($storefront->order_revenue ?? 0,2) }}</td>
                <td class="marketplace-controls">
                    @if($storefront->moderation_status==='active')
                        <form method="POST" action="{{ route('administrator.marketplace.moderation',$storefront) }}">@csrf @method('PATCH')<input type="hidden" name="moderation_status" value="paused"><label class="small" for="reason_{{ $storefront->id }}">Reason to hide publicly</label><textarea id="reason_{{ $storefront->id }}" name="reason" class="form-control form-control-sm mb-2" required maxlength="1000"></textarea><button class="btn btn-sm btn-outline-danger btn-block">Pause public storefront</button></form>
                    @else
                        <div class="alert alert-danger py-2 px-2 small">{{ $storefront->moderation_reason }}</div><form method="POST" action="{{ route('administrator.marketplace.moderation',$storefront) }}">@csrf @method('PATCH')<input type="hidden" name="moderation_status" value="active"><button class="btn btn-sm btn-success btn-block">Allow public storefront</button></form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-5">No storefronts match these filters.</td></tr>
        @endforelse
        </tbody>
    </table></div></div>
    <div class="mt-3">{{ $storefronts->links() }}</div>
</div>
</section>
@endsection
