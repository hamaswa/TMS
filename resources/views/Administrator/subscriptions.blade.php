@extends('main')
@section('content')
<section class="main-content"><div class="container-fluid px-3 px-md-4 py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div><h1 class="h2 mb-1">Subscriptions and payments</h1><p class="text-muted mb-0">Track client terms, upcoming expiry and received subscription payments without deleting financial history.</p></div>
        <div><a href="{{ route('administrator.subscription-plans.index') }}" class="btn btn-primary">Manage plans</a> <a href="{{ route('administrator.index') }}" class="btn btn-outline-secondary">Client accounts</a></div>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="row mb-4">
        @foreach([
            ['label'=>'Clients','value'=>$metrics['clients'],'class'=>'primary'],
            ['label'=>'Expiring in 14 days','value'=>$metrics['expiring'],'class'=>'warning'],
            ['label'=>'Expired','value'=>$metrics['expired'],'class'=>'danger'],
            ['label'=>'Payments received','value'=>'Rs '.number_format($metrics['received'],2),'class'=>'success'],
        ] as $metric)
        <div class="col-sm-6 col-xl-3 mb-3"><div class="card border-{{ $metric['class'] }} h-100"><div class="card-body"><div class="text-muted small">{{ $metric['label'] }}</div><div class="h3 mb-0">{{ $metric['value'] }}</div></div></div></div>
        @endforeach
    </div>
    <form method="GET" class="card card-body mb-3"><div class="form-row align-items-end">
        <div class="col-md-6 mb-2"><label for="subscription_search">Search</label><input id="subscription_search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Business, client or email"></div>
        <div class="col-md-3 mb-2"><label for="subscription_state">Subscription state</label><select id="subscription_state" name="state" class="form-control"><option value="">All states</option>@foreach(['active'=>'Active','expiring'=>'Expiring','expired'=>'Expired','unconfigured'=>'Not configured'] as $value=>$label)<option value="{{ $value }}" @selected(request('state')===$value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-3 mb-2"><button class="btn btn-primary">Apply filters</button> <a href="{{ route('administrator.subscriptions.index') }}" class="btn btn-light">Clear</a></div>
    </div></form>
    <div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
        <thead class="thead-light"><tr><th>Client</th><th>Current term</th><th>State</th><th>Fee</th><th>Paid</th><th>Balance</th><th class="text-right">Action</th></tr></thead>
        <tbody>@forelse($businesses as $business)
            @php($subscription=$business->latestSubscription)
            <tr>
                <td><strong>{{ $business->name }}</strong><br><small class="text-muted">{{ $business->owner?->name }} · {{ $business->owner?->email }}</small></td>
                <td>@if($subscription){{ $subscription->starts_on->format('d M Y') }} &ndash; {{ $subscription->ends_on->format('d M Y') }}<br><small>{{ $subscription->plan_name }}</small>@else<span class="text-muted">Not configured</span>@endif</td>
                <td>@if($subscription)@php($state=$subscription->state())<span class="badge badge-{{ ['active'=>'success','expiring'=>'warning','expired'=>'danger'][$state] ?? 'secondary' }}">{{ ucfirst($state) }}</span>@else<span class="badge badge-secondary">Unconfigured</span>@endif</td>
                <td>{{ $subscription ? 'Rs '.number_format((float)$subscription->fee,2) : '—' }}</td>
                <td>{{ $subscription ? 'Rs '.number_format($subscription->amountPaid(),2) : '—' }}</td>
                <td>{{ $subscription ? 'Rs '.number_format($subscription->balanceDue(),2) : '—' }}</td>
                <td class="text-right"><a class="btn btn-sm btn-primary" href="{{ route('administrator.clients.show',$business->owner_user_id) }}">Manage</a></td>
            </tr>
        @empty<tr><td colspan="7" class="text-center text-muted py-5">No clients match these filters.</td></tr>@endforelse</tbody>
    </table></div></div>
    <div class="mt-3">{{ $businesses->links() }}</div>
</div></section>
@endsection
