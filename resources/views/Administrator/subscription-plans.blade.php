@extends('main')

@push('styles')
<style>
    .plan-table th { font-size: .78rem; text-transform: uppercase; letter-spacing: .03em; color: #6c757d; white-space: nowrap; }
    .plan-table td { vertical-align: middle; }
    .plan-table .plan-name { font-weight: 600; }
    .plan-yes { color: #28a745; }
    .plan-no { color: #ced4da; }
    .plan-form-heading { font-weight: 600; margin: 1.25rem 0 .75rem; padding-bottom: .35rem; border-bottom: 1px solid #e9ecef; }
    .plan-form-heading:first-of-type { margin-top: 0; }
    .plan-choice-group { display: flex; flex-wrap: wrap; gap: .5rem; }
    .plan-choice { flex: 1 1 140px; margin: 0; cursor: pointer; }
    .plan-choice input { position: absolute; opacity: 0; }
    .plan-choice span { display: block; text-align: center; padding: .6rem .75rem; border: 1px solid #ced4da; border-radius: .4rem; }
    .plan-choice input:checked + span { border-color: #007bff; background: #e7f1ff; color: #0056b3; font-weight: 600; }
    .plan-choice input:focus-visible + span { outline: 2px solid #80bdff; outline-offset: 2px; }
    .plan-advanced summary { cursor: pointer; font-weight: 600; color: #495057; }
</style>
@endpush

@section('content')
@php
    $tick = fn (bool $on) => $on
        ? '<i class="fas fa-check plan-yes" aria-label="Included"></i>'
        : '<i class="fas fa-minus plan-no" aria-label="Not included"></i>';
    $limit = fn (?int $value) => $value === null ? '∞' : number_format($value);
@endphp
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Subscription plans</h1>
            <p class="text-muted mb-0">Changes apply to new subscriptions. Shops already subscribed keep what they signed up for.</p>
        </div>
        <div class="mt-2 mt-md-0">
            <a href="{{ route('administrator.subscriptions.index') }}" class="btn btn-outline-secondary">Subscriptions</a>
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#plan-modal-new"><i class="fas fa-plus"></i> New plan</button>
        </div>
    </div>

    @if($errors->any())<div class="alert alert-danger">The plan was not saved. Please check the form.</div>@endif
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    @if($plans->isEmpty())
        <div class="alert alert-info">No plans yet. Click <strong>New plan</strong> to create one.</div>
    @else
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover plan-table mb-0">
                    <thead>
                        <tr>
                            <th>Plan</th>
                            <th>Price</th>
                            <th>Workspaces</th>
                            <th class="text-center">Customers</th>
                            <th class="text-center">Employees</th>
                            <th class="text-center">Storefront</th>
                            <th class="text-center">Reports</th>
                            <th class="text-center">Activity log</th>
                            <th>Signup</th>
                            <th class="text-center">Shops</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($plans as $plan)
                            <tr @class(['text-muted' => ! $plan->is_active])>
                                <td>
                                    <span class="plan-name">{{ $plan->name }}</span>
                                    @if($plan->is_recommended)<span class="badge badge-primary ml-1">Recommended</span>@endif
                                    @if($plan->lifecycle_status !== 'active')<span class="badge badge-secondary ml-1">{{ ucfirst($plan->lifecycle_status) }}</span>@endif
                                </td>
                                <td class="text-nowrap">Rs {{ number_format((float) $plan->price) }} <small class="text-muted">/ {{ $plan->billingPeriodLabel() }}</small></td>
                                <td class="text-nowrap">{{ \App\Models\SubscriptionPlan::WORKSPACES[$plan->workspace()] ?? 'None' }}</td>
                                <td class="text-center">{{ $limit($plan->max_customers) }}</td>
                                <td class="text-center">{{ $limit($plan->max_employees) }}</td>
                                <td class="text-center">{!! $tick($plan->allow_storefront) !!}</td>
                                <td class="text-center">{!! $tick($plan->allow_financial_reports) !!}</td>
                                <td class="text-center">{!! $tick($plan->allow_activity_log) !!}</td>
                                <td class="text-nowrap">
                                    @if($plan->is_public)
                                        <span class="badge badge-success">Public</span> <small class="text-muted">{{ $plan->trial_days }}-day trial</small>
                                    @else
                                        <span class="badge badge-light">Assigned by you</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $plan->subscriptions_count }}</td>
                                <td class="text-right">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#plan-modal-{{ $plan->id }}">Edit</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

@foreach([null, ...$plans] as $plan)
    @php($formKey = $plan ? 'plan-'.$plan->id : 'new')
    <div class="modal fade" id="plan-modal-{{ $plan?->id ?? 'new' }}" tabindex="-1" role="dialog" aria-labelledby="plan-modal-{{ $formKey }}-title" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <form class="modal-content" method="POST" action="{{ $plan ? route('administrator.subscription-plans.update', $plan) : route('administrator.subscription-plans.store') }}">
                @csrf
                @if($plan) @method('PUT') @endif
                <div class="modal-header">
                    <h5 class="modal-title" id="plan-modal-{{ $formKey }}-title">{{ $plan ? 'Edit '.$plan->name : 'New plan' }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    @if(old('_form') === $formKey && $errors->any())
                        <div class="alert alert-danger"><ul class="mb-0 pl-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                    @endif
                    @if($plan && $plan->subscriptions_count)
                        <div class="alert alert-info small mb-3">{{ $plan->subscriptions_count }} {{ \Illuminate\Support\Str::plural('shop', $plan->subscriptions_count) }} already use this plan. They keep their current limits until their next subscription.</div>
                    @endif
                    @include('Administrator.subscription-plan-fields', ['plan' => $plan, 'formKey' => $formKey])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary">{{ $plan ? 'Save changes' : 'Create plan' }}</button>
                </div>
            </form>
        </div>
    </div>
@endforeach
@endsection

@push('scripts')
<script>
    $(function () {
        $(document).on('change', '.js-permissions-mode', function () {
            $(this).closest('details').find('.js-permission-list').prop('hidden', this.value !== 'custom');
        });

        @if(old('_form') && $errors->any())
            $('#plan-modal-{{ old('_form') === 'new' ? 'new' : (int) str_replace('plan-', '', old('_form')) }}').modal('show');
        @endif
    });
</script>
@endpush
