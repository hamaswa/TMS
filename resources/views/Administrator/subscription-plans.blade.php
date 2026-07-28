@extends('main')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Subscription plans</h1>
            <p class="text-muted mb-0">Control client capacity, role permissions and important application features.</p>
        </div>
        <a href="{{ route('administrator.subscriptions.index') }}" class="btn btn-outline-primary">Subscriptions</a>
    </div>

    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="card mb-4">
        <div class="card-header bg-white"><h2 class="h5 mb-0">Create plan</h2></div>
        <div class="card-body">
            <form method="POST" action="{{ route('administrator.subscription-plans.store') }}">@csrf
                @include('Administrator.subscription-plan-fields', ['plan' => null])
                <button class="btn btn-primary">Create plan</button>
            </form>
        </div>
    </div>

    @forelse($plans as $plan)
        <div class="card mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <div><strong>{{ $plan->name }}</strong> <code>{{ $plan->code }}</code></div>
                <span class="badge badge-{{ $plan->is_active ? 'success' : 'secondary' }}">{{ $plan->is_active ? 'Active' : 'Inactive' }} · {{ $plan->subscriptions_count }} subscriptions</span>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('administrator.subscription-plans.update', $plan) }}">@csrf @method('PUT')
                    @include('Administrator.subscription-plan-fields', ['plan' => $plan])
                    <button class="btn btn-outline-primary">Save plan</button>
                </form>
            </div>
        </div>
    @empty
        <div class="alert alert-info">No plans yet. Create a plan before assigning plan-based limits.</div>
    @endforelse
</div>
@endsection
