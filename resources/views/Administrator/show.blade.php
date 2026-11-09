@extends('main')

@push('styles')
<style>
    .sub-current { border-radius: 1rem; padding: 1.1rem 1.25rem; margin-bottom: 1.25rem; background: linear-gradient(120deg, #ecfdf5 0%, #f0f9ff 100%); border: 1px solid #bbf7d0; }
    .sub-current.is-expiring { background: linear-gradient(120deg, #fffbeb, #fff7ed); border-color: #fde68a; }
    .sub-current.is-expired, .sub-current.is-cancelled { background: #f8fafc; border-color: #e2e8f0; }
    .sub-current-kicker { color: #64748b; font-weight: 700; letter-spacing: .06em; font-size: .7rem; }
    .sub-current-name { font-size: 1.35rem; font-weight: 800; color: #0f172a; }
    .sub-dates { color: #475569; font-size: .88rem; }
    .sub-money { display: flex; gap: .5rem; flex-wrap: wrap; }
    .sub-money > div { min-width: 92px; padding: .4rem .7rem; border-radius: .6rem; background: rgba(255, 255, 255, .8); border: 1px solid #e2e8f0; text-align: right; }
    .sub-money small { display: block; color: #64748b; font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; }
    .sub-money strong { font-size: .95rem; }
    .sub-progress { height: .45rem; border-radius: 999px; background: #e2e8f0; overflow: hidden; }
    .sub-progress span { display: block; height: 100%; border-radius: 999px; }
    .badge-upgrade { background: linear-gradient(90deg, #7c3aed, #2563eb); color: #fff; }
    .sub-history-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: .5rem; margin: 1.5rem 0 .75rem; }
    .sub-legend { display: flex; flex-wrap: wrap; gap: .75rem; }
    .sub-legend .dot { display: inline-block; width: .6rem; height: .6rem; border-radius: 50%; margin-right: .3rem; vertical-align: middle; }
    .sub-timeline { position: relative; padding-left: 2.25rem; }
    .sub-timeline::before { content: ""; position: absolute; left: .9rem; top: .5rem; bottom: .5rem; width: 2px; background: #e2e8f0; }
    .sub-item { position: relative; margin-bottom: 1rem; }
    .sub-dot { position: absolute; left: -2.25rem; top: .9rem; width: 1.85rem; height: 1.85rem; border-radius: 50%; color: #fff; display: flex; align-items: center; justify-content: center; font-size: .8rem; box-shadow: 0 0 0 4px #fff; }
    .sub-card { border: 1px solid #e2e8f0; border-radius: .85rem; padding: .9rem 1rem; background: #fff; transition: box-shadow .15s; }
    .sub-card:hover { box-shadow: 0 6px 18px rgba(15, 23, 42, .06); }
    .sub-item.is-active .sub-card, .sub-item.is-expiring .sub-card { border-left: 4px solid #16a34a; }
    .sub-item.is-expiring .sub-card { border-left-color: #f59e0b; }
    .sub-item.is-upcoming .sub-card { border-left: 4px solid #2563eb; }
    .sub-item.is-upgraded .sub-card { border-left: 4px solid #0ea5e9; background: #f8fbff; }
    .sub-item.is-cancelled .sub-card { border-left: 4px solid #dc2626; background: #fffafa; }
    .sub-item.is-expired .sub-card { border-left: 4px solid #94a3b8; background: #fafafa; }
    .sub-item.is-upgraded .sub-plan, .sub-item.is-cancelled .sub-plan, .sub-item.is-expired .sub-plan { color: #64748b; }
    .sub-item.is-cancelled .sub-plan { text-decoration: line-through; }
    .sub-card-top { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-start; gap: .75rem; }
    .sub-plan { font-size: 1.05rem; }
    .sub-note { margin-top: .6rem; padding: .45rem .7rem; border-radius: .5rem; font-size: .85rem; }
    .sub-note-upgrade { background: #f5f3ff; color: #5b21b6; }
    .sub-note-info { background: #e0f2fe; color: #075985; }
    .sub-note-ending { background: #fef3c7; color: #92400e; }
    .sub-item.is-ending .sub-card { border-left: 4px solid #f59e0b; }
    .sub-note-danger { background: #fee2e2; color: #991b1b; }
    .sub-limits { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .6rem; }
    .sub-limits span { padding: .15rem .55rem; border-radius: 999px; background: #f1f5f9; color: #475569; font-size: .78rem; }
    .sub-payments th { font-size: .72rem; text-transform: uppercase; color: #64748b; border-top: 0; }
    .sub-actions { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .75rem; }
    .sub-actions details { flex: 0 0 auto; }
    .sub-actions details[open] { flex: 1 1 100%; }
    .sub-actions summary, .sub-inline summary { list-style: none; cursor: pointer; }
    .sub-actions summary::-webkit-details-marker, .sub-inline summary::-webkit-details-marker { display: none; }
    @media (max-width: 575.98px) { .sub-money > div { min-width: 0; flex: 1 1 30%; } }
</style>
@endpush
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
            @php($metricLabels = ['production_workers' => 'Production workers (includes linked tailors)'])
            <div class="card mb-4"><div class="card-header bg-white"><h5 class="mb-0">Business data summary</h5></div><div class="card-body"><div class="row text-center">@foreach($metrics as $label=>$value)<div class="col-6 col-md-3 mb-3"><div class="border rounded p-3 h-100"><div class="h3 mb-1">{{ number_format($value) }}</div><small class="text-muted">{{ $metricLabels[$label] ?? ucwords(str_replace('_',' ',$label)) }}</small></div></div>@endforeach</div></div></div>
            <div class="card mb-4"><div class="card-header bg-white d-flex justify-content-between align-items-center"><h5 class="mb-0">Public storefront</h5><div>@if($business->storefront?->is_published)<span class="badge badge-primary">Published by client</span>@else<span class="badge badge-secondary">Not published</span>@endif @if($business->storefront)<span class="badge badge-{{ $business->storefront->moderation_status==='active'?'success':'danger' }}">{{ $business->storefront->moderation_status==='active'?'Allowed publicly':'Moderation paused' }}</span>@endif</div></div><div class="card-body">
                @if($business->storefront)
                    <dl class="row mb-0"><dt class="col-sm-4">Display name</dt><dd class="col-sm-8">{{ $business->storefront->display_name }}</dd><dt class="col-sm-4">Public URL</dt><dd class="col-sm-8"><a target="_blank" rel="noopener" href="{{ $business->storefront->is_published && $business->storefront->isModerationActive() && $business->isActive() ? route('storefront.show',$business->storefront) : '#' }}">/shops/{{ $business->storefront->slug }}</a></dd><dt class="col-sm-4">Public modules</dt><dd class="col-sm-8">{{ collect([$business->storefront->show_clothing ? 'Clothing' : null,$business->storefront->show_tailoring ? 'Tailoring' : null])->filter()->join(', ') ?: 'None' }}</dd><dt class="col-sm-4">Published</dt><dd class="col-sm-8">{{ $business->storefront->published_at?->format('d M Y, h:i A') ?? 'No' }}</dd><dt class="col-sm-4">Marketplace moderation</dt><dd class="col-sm-8">{{ ucfirst($business->storefront->moderation_status) }}@if($business->storefront->moderation_reason)<br><span class="text-danger">{{ $business->storefront->moderation_reason }}</span>@endif @if($business->storefront->moderated_at)<br><small class="text-muted">{{ $business->storefront->moderated_at->format('d M Y, h:i A') }} by {{ $business->storefront->moderatedBy?->name ?? 'System' }}</small>@endif</dd></dl>
                    <a class="btn btn-sm btn-outline-success mt-3" href="{{ route('administrator.marketplace.index',['search'=>$business->storefront->slug]) }}">Open marketplace oversight</a>
                    @if($business->storefront->moderationHistory->isNotEmpty())
                        <h6 class="mt-4">Moderation history</h6>
                        <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Date</th><th>Change</th><th>Actor</th><th>Reason</th></tr></thead><tbody>@foreach($business->storefront->moderationHistory->take(10) as $entry)<tr><td>{{ $entry->created_at?->format('d M Y, h:i A') }}</td><td>{{ ucfirst($entry->from_status ?? 'new') }} &rarr; {{ ucfirst($entry->to_status) }}</td><td>{{ $entry->changedBy?->name ?? 'System' }}</td><td>{{ $entry->reason ?: '—' }}</td></tr>@endforeach</tbody></table></div>
                    @endif
                @else
                    <p class="text-muted mb-0">The client has not configured a public storefront.</p>
                @endif
            </div></div>
            <div class="card mb-4"><div class="card-header bg-white d-flex justify-content-between align-items-center"><h5 class="mb-0">Subscription and payments</h5><a href="{{ route('administrator.subscriptions.index') }}" class="btn btn-sm btn-outline-primary">All subscriptions</a></div><div class="card-body">
                @if($currentSubscription)
                    @php([$curKey, $curLabel, $curColor, $curIcon] = $currentSubscription->historyStatus())
                    @php($totalDays = max(1, $currentSubscription->starts_on->diffInDays($currentSubscription->ends_on) + 1))
                    @php($usedDays = (int) min($totalDays, max(0, $currentSubscription->starts_on->diffInDays(now()->startOfDay(), false) + 1)))
                    <div class="sub-current is-{{ $curKey }}">
                        <div class="d-flex flex-wrap justify-content-between align-items-start" style="gap:1rem">
                            <div>
                                <div class="small text-uppercase sub-current-kicker">Current plan</div>
                                <div class="d-flex flex-wrap align-items-center" style="gap:.5rem">
                                    <span class="sub-current-name">{{ $currentSubscription->plan_name }}</span>
                                    <span class="badge badge-pill badge-{{ $curColor }}"><i class="fas {{ $curIcon }}"></i> {{ $curLabel }}</span>
                                    @if($currentSubscription->isUpgrade())<span class="badge badge-pill badge-upgrade"><i class="fas fa-arrow-up"></i> Upgraded</span>@endif
                                </div>
                                <div class="sub-dates mt-1">{{ $currentSubscription->starts_on->format('d M Y') }} &rarr; {{ $currentSubscription->ends_on->format('d M Y') }}@if(in_array($curKey, ['active', 'expiring'], true)) · <strong>{{ $currentSubscription->daysRemaining() }} days left</strong>@endif</div>
                            </div>
                            <div class="sub-money">
                                <div><small>Fee</small><strong>Rs {{ number_format((float)$currentSubscription->fee) }}</strong></div>
                                <div><small>Paid</small><strong class="text-success">Rs {{ number_format($currentSubscription->amountPaid()) }}</strong></div>
                                <div><small>Balance</small><strong class="{{ $currentSubscription->balanceDue() > 0 ? 'text-danger' : '' }}">Rs {{ number_format($currentSubscription->balanceDue()) }}</strong></div>
                            </div>
                        </div>
                        @if(in_array($curKey, ['active', 'expiring'], true))
                            <div class="sub-progress mt-3" role="progressbar" aria-label="Period used" aria-valuemin="0" aria-valuemax="{{ $totalDays }}" aria-valuenow="{{ $usedDays }}"><span class="bg-{{ $curColor }}" style="width: {{ round($usedDays / $totalDays * 100) }}%"></span></div>
                            <div class="small text-muted mt-1">Day {{ $usedDays }} of {{ $totalDays }}</div>
                        @endif
                    </div>
                @else
                    <div class="alert alert-info">No subscription is configured. Existing access remains unchanged until a subscription is added.</div>
                @endif
                <h6>Create subscription or renewal</h6>
                @php($planRequest = $business->pendingPlanRequest)
                @php($formPlan = old('subscription_plan_id') ? $subscriptionPlans->firstWhere('id', (int) old('subscription_plan_id')) : $planRequest?->plan)
                @php($formStart = old('starts_on', $currentSubscription?->ends_on?->copy()->addDay()->toDateString() ?? now()->toDateString()))
                @if($planRequest)
                    @php($rejectBag = $errors->getBag('plan_request_'.$planRequest->id))
                    <div id="plan-request" class="border border-warning rounded p-3 mb-4" style="background:#fffbea">
                        <div class="d-flex flex-wrap justify-content-between align-items-start" style="gap:1rem">
                            <div>
                                <h6 class="mb-2"><i class="fas fa-receipt text-warning"></i> Payment request waiting for you</h6>
                                <dl class="row mb-0 small">
                                    <dt class="col-5">Plan</dt><dd class="col-7">{{ $planRequest->plan?->name }}@if($planRequest->is_upgrade) <span class="badge badge-success"><i class="fas fa-arrow-up"></i> Upgrade</span>@endif</dd>
                                    @if($planRequest->upgrade_reason)<dt class="col-5">Upgrade reason</dt><dd class="col-7" style="white-space:pre-line">{{ $planRequest->upgrade_reason }}</dd>@endif
                                    <dt class="col-5">Amount</dt><dd class="col-7"><strong>Rs {{ number_format((float) $planRequest->amount) }}</strong></dd>
                                    <dt class="col-5">Easypaisa TID</dt><dd class="col-7">@if($planRequest->payment_reference)<code class="h6">{{ $planRequest->payment_reference }}</code>@else<span class="text-muted">Not paid yet (plan request only)</span>@endif</dd>
                                    <dt class="col-5">Sent</dt><dd class="col-7">{{ $planRequest->created_at->format('d M Y, h:i A') }} by {{ $planRequest->requestedBy?->name ?? 'shop owner' }}</dd>
                                </dl>
                            </div>
                            @if($planRequest->proof_path)
                                <a href="{{ route('administrator.plan-requests.proof', $planRequest) }}" target="_blank" rel="noopener" class="text-center small">
                                    @if(str_starts_with((string) $planRequest->proof_mime_type, 'image/'))<img src="{{ route('administrator.plan-requests.proof', $planRequest) }}" alt="Payment screenshot" class="border rounded d-block mb-1" style="width:120px;height:160px;object-fit:cover">@else<i class="fas fa-file-alt fa-3x d-block mb-1"></i>@endif
                                    Open screenshot
                                </a>
                            @endif
                        </div>
                        <p class="small text-muted mt-3 mb-2">Check the TID against your Easypaisa SMS or app before approving. Approving starts the plan and records the payment.</p>
                        @if($rejectBag->any())<div class="alert alert-danger py-2 small">{{ $rejectBag->first() }}</div>@endif
                        <div class="d-flex flex-wrap align-items-start" style="gap:.5rem">
                            <form method="POST" action="{{ route('administrator.plan-requests.approve', $planRequest) }}" data-confirm="Approve this payment and start {{ $planRequest->plan?->name }}?">@csrf<button class="btn btn-success"><i class="fas fa-check"></i> Approve and activate</button></form>
                            <form method="POST" action="{{ route('administrator.plan-requests.reject', $planRequest) }}" class="flex-grow-1">@csrf
                                <div class="input-group"><input name="reason" class="form-control" required maxlength="1000" placeholder="Reason, e.g. TID not found"><div class="input-group-append"><button class="btn btn-outline-danger">Reject</button></div></div>
                            </form>
                        </div>
                    </div>
                @endif
                <form method="POST" action="{{ route('administrator.subscriptions.store',$user) }}" class="border rounded p-3 mb-4">@csrf
                    <div class="form-row">
                        <div class="form-group col-md-4"><label for="subscription_plan_id">Plan with limits</label><select id="subscription_plan_id" name="subscription_plan_id" class="form-control"><option value="">Custom / unlimited legacy terms</option>@foreach($subscriptionPlans as $plan)<option value="{{ $plan->id }}" data-price="{{ (float) $plan->price }}" data-days="{{ $plan->billing_period_days }}" @selected($formPlan?->id === $plan->id)>{{ $plan->name }} · Rs {{ number_format((float)$plan->price,2) }} · {{ $plan->billing_period_days }} days</option>@endforeach</select><small class="form-text text-muted"><a href="{{ route('administrator.subscription-plans.index') }}">Create or edit plans</a></small></div>
                        <div class="form-group col-md-2"><label for="plan_name">Custom plan name</label><input id="plan_name" name="plan_name" value="{{ old('plan_name') }}" class="form-control" maxlength="100" placeholder="Only without a plan"></div>
                        <div class="form-group col-md-2"><label for="starts_on">Starts on</label><input id="starts_on" type="date" name="starts_on" value="{{ $formStart }}" class="form-control" required></div>
                        <div class="form-group col-md-2"><label for="ends_on">Ends on</label><input id="ends_on" type="date" name="ends_on" value="{{ old('ends_on', $formPlan ? \Illuminate\Support\Carbon::parse($formStart)->addDays($formPlan->billing_period_days - 1)->toDateString() : ($currentSubscription?->ends_on?->copy()->addYear()->toDateString() ?? now()->addYear()->toDateString())) }}" class="form-control" required></div>
                        <div class="form-group col-md-2"><label for="subscription_fee">Fee</label><input id="subscription_fee" type="number" name="fee" value="{{ old('fee', $formPlan ? (float) $formPlan->price : null) }}" class="form-control" min="0" step="0.01" required></div>
                    </div>
                    <div class="form-group"><label for="subscription_notes">Notes</label><textarea id="subscription_notes" name="notes" class="form-control" rows="2" maxlength="2000">{{ old('notes') }}</textarea></div>
                    <button class="btn btn-primary">Create subscription</button>
                </form>
                <div class="sub-history-head"><h6 class="mb-0">Subscription history</h6>
                    <div class="sub-legend small text-muted"><span><i class="dot bg-success"></i>Active</span><span><i class="dot bg-primary"></i>Upcoming</span><span><i class="dot bg-warning"></i>Cancelled by shop (ending)</span><span><i class="dot bg-info"></i>Upgraded away</span><span><i class="dot bg-danger"></i>Cancelled</span><span><i class="dot bg-secondary"></i>Expired</span></div>
                </div>
                <div class="sub-timeline">
                @forelse($subscriptions as $subscription)
                    @php([$statusKey, $statusLabel, $statusColor, $statusIcon] = $subscription->historyStatus())
                    @php($paymentErrors = $errors->getBag('subscription_payment_'.$subscription->id))
                    <div class="sub-item is-{{ $statusKey }}">
                        <span class="sub-dot bg-{{ $statusColor }}"><i class="fas {{ $statusIcon }}" aria-hidden="true"></i></span>
                        <div class="sub-card">
                            <div class="sub-card-top">
                                <div class="min-w-0">
                                    <div class="d-flex flex-wrap align-items-center" style="gap:.4rem">
                                        <strong class="sub-plan">{{ $subscription->plan_name }}</strong>
                                        <span class="badge badge-pill badge-{{ $statusColor }}"><i class="fas {{ $statusIcon }}" aria-hidden="true"></i> {{ $statusLabel }}</span>
                                        @if($subscription->isUpgrade())<span class="badge badge-pill badge-upgrade"><i class="fas fa-arrow-up" aria-hidden="true"></i> Upgrade</span>@endif
                                        @if($currentSubscription?->id === $subscription->id && in_array($statusKey, ['active', 'expiring'], true))<span class="badge badge-pill badge-light border">Current</span>@endif
                                    </div>
                                    <div class="sub-dates"><i class="far fa-calendar" aria-hidden="true"></i> {{ $subscription->starts_on->format('d M Y') }} &rarr; {{ $subscription->ends_on->format('d M Y') }} <span class="text-muted">· {{ $subscription->starts_on->diffInDays($subscription->ends_on) + 1 }} days</span>@if($subscription->plan_code) <code class="ml-1">{{ $subscription->plan_code }}</code>@endif</div>
                                </div>
                                <div class="sub-money">
                                    <div><small>Fee</small><strong>Rs {{ number_format((float)$subscription->fee) }}</strong></div>
                                    <div><small>Paid</small><strong class="text-success">Rs {{ number_format($subscription->amountPaid()) }}</strong></div>
                                    <div><small>Balance</small><strong class="{{ $subscription->balanceDue() > 0 ? 'text-danger' : '' }}">Rs {{ number_format($subscription->balanceDue()) }}</strong></div>
                                </div>
                            </div>

                            @if($subscription->isUpgrade() && $subscription->planRequest?->upgrade_reason)
                                <div class="sub-note sub-note-upgrade"><i class="fas fa-arrow-up" aria-hidden="true"></i> <strong>Upgrade reason:</strong> {{ $subscription->planRequest->upgrade_reason }}</div>
                            @endif
                            @if($subscription->isEndingByShopRequest())
                                <div class="sub-note sub-note-ending"><i class="fas fa-door-open" aria-hidden="true"></i> <strong>Cancelled by the shop</strong> on {{ $subscription->cancel_requested_at->format('d M Y') }}@if($subscription->cancelRequestedBy) by {{ $subscription->cancelRequestedBy->name }}@endif — runs until {{ $subscription->ends_on->format('d M Y') }}, then stops. @if($subscription->cancel_request_reason)<br><strong>Reason:</strong> {{ $subscription->cancel_request_reason }}@endif</div>
                            @endif
                            @if($statusKey === 'upgraded')
                                <div class="sub-note sub-note-info"><i class="fas fa-level-up-alt" aria-hidden="true"></i> {{ $subscription->upgradeReplacedNote() }}</div>
                            @elseif($subscription->cancelled_at)
                                <div class="sub-note sub-note-danger"><i class="fas fa-ban" aria-hidden="true"></i> <strong>Cancelled</strong> {{ $subscription->cancelled_at->format('d M Y') }}@if($subscription->cancelledBy) by {{ $subscription->cancelledBy->name }}@endif @if($subscription->cancellation_reason)— {{ $subscription->cancellation_reason }}@endif</div>
                            @endif

                            @if($subscription->subscription_plan_id)<div class="sub-limits"><span><i class="fas fa-user-tie" aria-hidden="true"></i> {{ $subscription->max_employees ?? '∞' }} employees</span><span><i class="fas fa-users" aria-hidden="true"></i> {{ $subscription->max_customers === null ? '∞' : number_format($subscription->max_customers) }} customers</span><span><i class="fas fa-user-shield" aria-hidden="true"></i> {{ $subscription->max_business_roles ?? '∞' }} roles</span><span><i class="fas fa-cut" aria-hidden="true"></i> {{ $subscription->max_tailors ?? '∞' }} tailors</span></div>@endif

                            @if($subscription->payments->isNotEmpty())<div class="table-responsive mt-2"><table class="table table-sm sub-payments mb-0"><thead><tr><th>Date</th><th>Amount</th><th>Method / reference</th><th>Recorded by</th><th>State / action</th></tr></thead><tbody>@foreach($subscription->payments as $payment)<tr class="{{ $payment->reversed_at ? 'text-muted' : '' }}"><td>{{ $payment->paid_on->format('d M Y') }}</td><td><strong>Rs {{ number_format((float)$payment->amount,2) }}</strong></td><td>{{ \App\Models\SubscriptionPayment::METHODS[$payment->payment_method] ?? $payment->payment_method }} @if($payment->reference)<br><code>{{ $payment->reference }}</code>@endif</td><td>{{ $payment->recordedBy?->name ?? 'System' }}</td><td>@if($payment->reversed_at)<span class="badge badge-secondary">Reversed</span><br><small>{{ $payment->reversal_reason }}</small>@else<span class="badge badge-success">Received</span> <details class="d-inline-block sub-inline"><summary class="small text-danger">Reverse</summary><form method="POST" action="{{ route('administrator.subscription-payments.reverse',[$user,$payment]) }}" data-confirm="Reverse this subscription payment? The original record will be retained." class="mt-1">@csrf @method('PATCH')<input name="reversal_reason" class="form-control form-control-sm mb-1" required maxlength="1000" placeholder="Reason required"><button class="btn btn-sm btn-outline-danger">Reverse payment</button></form></details>@endif</td></tr>@endforeach</tbody></table></div>@endif

                            @if(!$subscription->cancelled_at)
                                <div class="sub-actions">
                                    @if($subscription->balanceDue()>0)
                                        <details @if($paymentErrors->any()) open @endif><summary class="btn btn-sm btn-outline-success"><i class="fas fa-plus" aria-hidden="true"></i> Record payment</summary>
                                            <form method="POST" action="{{ route('administrator.subscription-payments.store',[$user,$subscription]) }}" class="bg-light rounded p-3 mt-2">
                                                @csrf
                                                @if($paymentErrors->any())
                                                    <div class="alert alert-danger" role="alert"><strong>Payment was not recorded.</strong><ul class="mb-0">@foreach($paymentErrors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                                                @endif
                                                <div class="form-row align-items-end">
                                                    <div class="form-group col-md-2"><label for="paid_on_{{ $subscription->id }}">Paid on</label><input id="paid_on_{{ $subscription->id }}" type="date" name="paid_on" value="{{ $paymentErrors->any() ? old('paid_on', now()->toDateString()) : now()->toDateString() }}" class="form-control" required></div>
                                                    <div class="form-group col-md-2"><label for="payment_amount_{{ $subscription->id }}">Amount</label><input id="payment_amount_{{ $subscription->id }}" type="number" name="amount" value="{{ $paymentErrors->any() ? old('amount') : '' }}" step="0.01" min="0.01" class="form-control" aria-describedby="payment_amount_help_{{ $subscription->id }}" required><small id="payment_amount_help_{{ $subscription->id }}" class="form-text text-muted">Maximum outstanding balance: Rs {{ number_format($subscription->balanceDue(), 2) }}</small></div>
                                                    <div class="form-group col-md-2"><label for="payment_method_{{ $subscription->id }}">Method</label><select id="payment_method_{{ $subscription->id }}" name="payment_method" class="form-control" required>@foreach(\App\Models\SubscriptionPayment::METHODS as $value=>$label)<option value="{{ $value }}" @selected($paymentErrors->any() && old('payment_method') === $value)>{{ $label }}</option>@endforeach</select></div>
                                                    <div class="form-group col-md-3"><label for="payment_reference_{{ $subscription->id }}">Reference</label><input id="payment_reference_{{ $subscription->id }}" name="reference" value="{{ $paymentErrors->any() ? old('reference') : '' }}" class="form-control" maxlength="150" placeholder="Required except cash"></div>
                                                    <div class="form-group col-md-3"><label for="payment_notes_{{ $subscription->id }}">Notes</label><input id="payment_notes_{{ $subscription->id }}" name="notes" value="{{ $paymentErrors->any() ? old('notes') : '' }}" class="form-control" maxlength="2000"></div>
                                                </div>
                                                <button class="btn btn-success">Record payment</button>
                                            </form>
                                        </details>
                                    @endif
                                    <details><summary class="btn btn-sm btn-outline-danger"><i class="fas fa-ban" aria-hidden="true"></i> Cancel period</summary>
                                        <form method="POST" action="{{ route('administrator.subscriptions.cancel',[$user,$subscription]) }}" class="mt-2" data-confirm="Cancel this subscription period? Payment history will be retained.">@csrf @method('PATCH')<div class="input-group"><input name="cancellation_reason" class="form-control" required maxlength="1000" placeholder="Cancellation reason"><div class="input-group-append"><button class="btn btn-danger">Cancel period</button></div></div></form>
                                    </details>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty<p class="text-muted">No subscription history.</p>@endforelse
                </div>
            </div></div>
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

@push('scripts')
<script>
    // Picking a plan fills its fee and an end date one billing period after the start date.
    $(function () {
        var fill = function () {
            var option = $('#subscription_plan_id option:selected');
            var days = parseInt(option.data('days'), 10);
            if (!days) { return; }
            $('#subscription_fee').val(option.data('price'));
            var start = $('#starts_on').val();
            if (start) {
                var end = new Date(start + 'T00:00:00');
                end.setDate(end.getDate() + days - 1);
                var pad = function (n) { return String(n).padStart(2, '0'); };
                $('#ends_on').val(end.getFullYear() + '-' + pad(end.getMonth() + 1) + '-' + pad(end.getDate()));
            }
        };
        $('#subscription_plan_id, #starts_on').on('change', fill);
    });
</script>
@endpush
