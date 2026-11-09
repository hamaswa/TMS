@extends('main')

@push('styles')
<style>
    .plans-hero { position: relative; overflow: hidden; border-radius: 1rem; padding: 2rem 1.75rem; background: linear-gradient(135deg, #e8f1ff 0%, #f5f9ff 60%, #eaf3ff 100%); border: 1px solid #dbe7fb; }
    .plans-hero h1 { font-weight: 700; color: #102a43; }
    .plans-hero .plans-hero-icon { position: absolute; inset-inline-end: 1.75rem; top: 50%; transform: translateY(-50%); font-size: 4.5rem; color: #3b82f6; opacity: .18; }
    .plans-panel { background: #fff; border: 1px solid #e6edf5; border-radius: 1rem; padding: 1.25rem; }
    .period-toggle { display: flex; flex-wrap: wrap; gap: .35rem; padding: .35rem; background: #f1f5fa; border-radius: 999px; }
    .period-toggle button { flex: 1 1 120px; border: 0; background: transparent; border-radius: 999px; padding: .6rem 1rem; font-weight: 600; color: #486581; }
    .period-toggle button.is-active { background: #1d6fe0; color: #fff; box-shadow: 0 4px 12px rgba(29, 111, 224, .3); }
    .period-toggle button:focus-visible { outline: 2px solid #1d6fe0; outline-offset: 2px; }
    .period-toggle .save-chip { display: inline-block; margin-inline-start: .35rem; padding: 0 .5rem; border-radius: 999px; background: #dcfce7; color: #166534; font-size: .75rem; }
    .period-toggle button.is-active .save-chip { background: rgba(255, 255, 255, .25); color: #fff; }
    .plan-card { position: relative; height: 100%; display: flex; flex-direction: column; border: 1px solid #e6edf5; border-radius: 1rem; background: #fff; padding: 1.5rem 1.25rem 1.25rem; transition: box-shadow .15s, transform .15s; }
    .plan-card:hover { box-shadow: 0 10px 28px rgba(16, 42, 67, .08); transform: translateY(-2px); }
    .plan-card.is-popular { border: 2px solid #1d6fe0; box-shadow: 0 10px 28px rgba(29, 111, 224, .12); }
    .plan-card.is-current { border: 2px solid #16a34a; }
    .plan-card .plan-ribbon { position: absolute; top: -.8rem; inset-inline-end: 1rem; padding: .2rem .75rem; border-radius: .5rem; background: #1d6fe0; color: #fff; font-size: .8rem; font-weight: 700; }
    .plan-card.is-current .plan-ribbon { background: #16a34a; }
    .plan-card h3 { font-size: 1.15rem; font-weight: 700; text-align: center; margin-bottom: .5rem; color: #102a43; }
    .plan-card .plan-price { text-align: center; margin-bottom: .25rem; }
    .plan-card .plan-price strong { font-size: 2rem; color: #102a43; }
    .plan-card .plan-save { text-align: center; min-height: 1.5rem; }
    .plan-card ul { list-style: none; padding: 0; margin: 1rem 0 1.25rem; }
    .plan-card li { display: flex; align-items: center; gap: .5rem; padding: .3rem 0; font-size: .92rem; }
    .plan-card li .fa-check-circle { color: #1d6fe0; }
    .plan-card li.is-off { color: #9fb3c8; text-decoration: line-through; }
    .plan-card .plan-action { margin-top: auto; }
    .plan-card .plan-action .btn { border-radius: 999px; font-weight: 600; }

    /* Payment popup (landscape: plan summary beside the payment details) */
    #pay-modal .modal-dialog { max-width: 860px; }
    #pay-modal .modal-content { border: 0; border-radius: 1.25rem; overflow: hidden; box-shadow: 0 24px 60px rgba(16, 42, 67, .35); background: #fff; }
    #pay-modal .modal-content:not(.is-online) { max-width: 480px; margin-inline: auto; }
    .pay-head { position: relative; flex: 0 0 auto; padding: 1rem 1.5rem 1.9rem; background: linear-gradient(120deg, #1546b8 0%, #2563eb 55%, #3b82f6 100%); color: #fff; overflow: hidden; }
    .pay-head::after { content: ""; position: absolute; inset-inline: -5%; bottom: -26px; height: 44px; background: #fff; border-radius: 50% 50% 0 0 / 100% 100% 0 0; }
    .pay-head .pay-rocket { position: absolute; inset-inline-end: 1.75rem; top: .7rem; font-size: 2.2rem; color: #fff; transform: rotate(-12deg); text-shadow: 0 6px 16px rgba(0, 0, 0, .25); }
    .pay-head h5 { position: relative; z-index: 1; margin: 0; color: #fff; font-size: 1.5rem; font-weight: 700; text-align: center; padding-inline: 3rem; }
    .pay-head .pay-close { position: absolute; z-index: 2; inset-inline-start: .9rem; top: .75rem; width: 2.3rem; height: 2.3rem; border: 0; border-radius: 50%; background: #fff; color: #1d4ed8; font-size: 1.05rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0, 0, 0, .2); }
    .pay-head .pay-close:focus-visible { outline: 3px solid #bfdbfe; outline-offset: 2px; }
    #pay-modal .modal-body { padding: .25rem 1.5rem 1rem; }
    #pay-modal .is-online .pay-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.45fr); gap: 1.5rem; align-items: start; }
    .pay-plan { text-align: center; }
    .is-online .pay-plan { padding-inline-end: 1.5rem; border-inline-end: 1px solid #e2e8f0; height: 100%; }
    .pay-chip { display: inline-block; margin-bottom: .5rem; padding: .3rem 1.2rem; border-radius: 999px; background: #e8f0fe; color: #1e3a8a; font-weight: 700; font-size: 1.05rem; }
    .pay-chip .fa-crown { color: #f5b301; margin-inline-end: .35rem; }
    .pay-price { display: flex; justify-content: center; align-items: baseline; gap: .5rem; color: #1e3a8a; padding-bottom: .75rem; margin-bottom: .75rem; border-bottom: 1px solid #e2e8f0; }
    .pay-price strong { font-size: 2.5rem; line-height: 1; font-weight: 800; }
    .pay-price span { font-size: 1.15rem; font-weight: 600; }
    .pay-features { list-style: none; padding: 0; margin: 0; text-align: start; }
    .pay-features li { display: flex; align-items: center; gap: .55rem; padding: .15rem 0; font-size: .95rem; color: #1f2937; }
    .pay-features li .fa-check-circle { color: #2563eb; font-size: 1.05rem; flex: 0 0 auto; }
    .pay-upgrade { margin-top: .9rem; text-align: start; }
    .pay-upgrade label { font-weight: 700; color: #166534; font-size: .92rem; margin-bottom: .3rem; }
    .pay-upgrade .form-control { border-radius: .75rem; font-size: .92rem; resize: vertical; }
    .pay-error { margin-top: .35rem; color: #b91c1c; font-size: .85rem; font-weight: 600; }
    .pay-account { display: flex; align-items: center; gap: 1rem; padding: .8rem; margin-bottom: .8rem; border: 1px solid #dbe7fb; border-radius: 1rem; background: #eff5ff; }
    .pay-account .pay-qr { flex: 0 0 auto; padding: .35rem; border-radius: .75rem; background: #fff; text-align: center; box-shadow: 0 2px 8px rgba(16, 42, 67, .08); }
    .pay-account .pay-qr img { display: block; width: 104px; height: 104px; object-fit: contain; }
    .pay-account .pay-qr small { display: block; margin-top: .15rem; font-weight: 700; font-size: .68rem; color: #1f2937; letter-spacing: .02em; }
    .pay-account .pay-details { flex: 1 1 auto; min-width: 0; padding-inline-start: 1rem; border-inline-start: 1px solid #c7d7f0; }
    .pay-account .pay-name { font-weight: 700; font-size: 1.1rem; color: #1e3a8a; }
    .pay-account .pay-number { font-size: 1.35rem; font-weight: 800; color: #1e3a8a; white-space: nowrap; }
    .pay-account .pay-number .fa-phone-alt { font-size: 1rem; margin-right: .35rem; }
    .pay-account .btn-copy { border: 1px solid #93b4ea; background: #fff; color: #1e3a8a; border-radius: .6rem; padding: .3rem .8rem; }
    .pay-hint { color: #374151; font-size: .88rem; margin-bottom: .4rem; }
    .pay-hint .fa-info-circle { color: #2563eb; }
    .pay-tid { position: relative; }
    .pay-tid .form-control { height: 2.9rem; border-radius: .75rem; font-size: 1.05rem; padding-right: 2.6rem; padding-left: .9rem; }
    .pay-tid .fa-mobile-alt { position: absolute; top: 50%; right: 1rem; transform: translateY(-50%); color: #1e3a8a; font-size: 1.15rem; pointer-events: none; }
    .pay-proof { display: flex; align-items: center; gap: .6rem; margin-top: .6rem; font-size: .85rem; color: #6b7280; min-width: 0; }
    .pay-proof-btn { flex: 0 0 auto; padding: .3rem .8rem; border: 1px dashed #93b4ea; border-radius: .6rem; background: #f8fbff; color: #1e3a8a; cursor: pointer; }
    #payment_proof:focus-visible + .pay-proof-btn { outline: 2px solid #2563eb; outline-offset: 2px; }
    .pay-proof-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    #pay-modal .modal-footer { justify-content: center; gap: .75rem; border-top: 0; padding: .4rem 1.5rem 1.2rem; background: linear-gradient(180deg, #fff 0%, #eef4ff 100%); }
    #pay-modal .modal-footer > * { margin: 0; }
    .pay-submit { flex: 0 1 320px; padding: .7rem 1.25rem; border: 0; border-radius: .75rem; font-size: 1.1rem; font-weight: 700; background: linear-gradient(180deg, #2fb344 0%, #16a34a 100%); box-shadow: 0 6px 16px rgba(22, 163, 74, .35); }
    .pay-cancel { padding: .7rem 1.25rem; border: 1px solid #e2e8f0; border-radius: .75rem; background: #f1f5f9; color: #1f2937; font-size: 1rem; }
    .pay-cancel .fa-times-circle { color: #64748b; }
    @media (max-width: 767.98px) {
        #pay-modal .is-online .pay-grid { display: block; }
        .is-online .pay-plan { padding-inline-end: 0; border-inline-end: 0; margin-bottom: 1rem; }
        .pay-features { display: table; margin: 0 auto; }
    }
    @media (max-width: 575.98px) {
        .pay-head .pay-rocket { display: none; }
        .pay-head h5 { font-size: 1.25rem; }
        .pay-price strong { font-size: 2.1rem; }
        .pay-account { flex-direction: column; text-align: center; }
        .pay-account .pay-details { padding-inline-start: 0; border-inline-start: 0; }
    }
    /* Cancel subscription popup */
    #cancel-plan-modal .modal-dialog { max-width: 600px; }
    #cancel-plan-modal .modal-content { border: 0; border-radius: 1.25rem; overflow: hidden; box-shadow: 0 24px 60px rgba(16, 42, 67, .35); background: #fff; }
    #cancel-plan-modal .pay-head h5 { font-size: 1.45rem; }
    #cancel-plan-modal .cancel-subtitle { position: relative; z-index: 1; margin: .35rem 0 0; padding-inline: 3rem; text-align: center; font-size: .92rem; color: #dbeafe; }
    #cancel-plan-modal .modal-body { padding: .25rem 1.5rem .75rem; }
    .cancel-plan-card { padding: .6rem 1rem; margin-bottom: .75rem; border-radius: 1rem; background: #e8f0fe; text-align: center; }
    .cancel-plan-name { font-size: 1.15rem; font-weight: 700; color: #1e3a8a; }
    .cancel-plan-name .fa-crown { color: #f5b301; margin-inline-end: .25rem; }
    .cancel-plan-card .pay-price { padding: 0; margin: .15rem 0 0; border: 0; }
    .cancel-plan-card .pay-price strong { font-size: 2.1rem; }
    #cancel-plan-modal .cancel-points { margin-bottom: .75rem; }
    #cancel-plan-modal .cancel-points li { font-size: .9rem; align-items: flex-start; }
    #cancel-plan-modal .cancel-points li i { margin-top: .3rem; }
    .cancel-label { margin-bottom: .4rem; font-weight: 700; font-size: .95rem; color: #1f2937; }
    .cancel-reasons { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .4rem; margin-bottom: .5rem; }
    .cancel-reason { position: relative; display: flex; align-items: center; justify-content: space-between; gap: .5rem; min-height: 2.5rem; margin: 0; padding: .3rem .75rem; border: 1px solid #dbe7fb; border-radius: .75rem; background: #f5f8ff; font-size: .86rem; cursor: pointer; }
    .cancel-reason input { position: absolute; opacity: 0; width: 1px; height: 1px; }
    .cancel-reason i { flex: 0 0 auto; color: #16a34a; visibility: hidden; }
    .cancel-reason:has(input:checked) { border-color: #16a34a; background: #f0fdf4; box-shadow: 0 0 0 1px #16a34a; }
    .cancel-reason:has(input:checked) i { visibility: visible; }
    .cancel-reason:has(input:focus-visible) { outline: 2px solid #2563eb; outline-offset: 2px; }
    #cancel_reason { border-radius: .75rem; font-size: .9rem; resize: vertical; }
    .cancel-confirm { margin-top: .6rem; font-size: .88rem; }
    #cancel-plan-modal .modal-footer { justify-content: center; gap: .75rem; border-top: 0; padding: .4rem 1.5rem 1.1rem; background: linear-gradient(180deg, #fff 0%, #e6efff 100%); }
    #cancel-plan-modal .modal-footer > * { margin: 0; }
    .cancel-submit { flex: 1 1 58%; padding: .7rem 1.25rem; border: 0; border-radius: 999px; font-size: 1.05rem; font-weight: 700; color: #fff; background: linear-gradient(180deg, #2f80ff 0%, #1565e0 100%); box-shadow: 0 6px 16px rgba(21, 101, 224, .35); }
    .cancel-submit:hover, .cancel-submit:focus { color: #fff; filter: brightness(1.05); }
    .cancel-keep { flex: 1 1 32%; padding: .7rem 1rem; border: 1px solid #e2e8f0; border-radius: 999px; background: #f8fafc; color: #1f2937; font-size: 1rem; }
    @media (max-width: 575.98px) {
        .cancel-reasons { grid-template-columns: minmax(0, 1fr); }
        #cancel-plan-modal .modal-footer { flex-direction: column-reverse; align-items: stretch; }
    }
</style>
@endpush

@section('content')
@php
    $stateColors = ['active' => 'success', 'expiring' => 'warning', 'expired' => 'danger'];
    $stateLabels = ['active' => 'فعال', 'expiring' => 'جلد ختم ہوگی', 'expired' => 'میعاد ختم'];
    $periodUnit = fn (int $days) => match ($days) { 30 => 'ماہ', 365 => 'سال', default => $days.' دن' };
    $currentPlanId = $currentSubscription && ! in_array($currentSubscription->state(), ['expired'], true) ? $currentSubscription->subscription_plan_id : null;
    $currentPlanPeriod = $currentPlanId ? $planGroups->search(fn ($group) => $group['plans']->contains('id', $currentPlanId)) : false;
    $defaultPeriod = $currentPlanPeriod !== false ? $currentPlanPeriod : ($planGroups->has(30) ? 30 : $planGroups->keys()->first());
    $pendingRequest = $business->pendingPlanRequest;
    $lastRequest = $business->latestPlanRequest;
    $requestedPlanId = $pendingRequest?->subscription_plan_id;
    $periodName = fn (int $days) => match ($days) { 30 => 'ماہانہ', 365 => 'سالانہ', default => $days.' دن' };
@endphp
<section class="main-content"><div class="container-fluid px-3 px-md-4 py-4" style="max-width:1200px">

    <div class="plans-hero mb-4">
        <i class="fas fa-shield-alt plans-hero-icon" aria-hidden="true"></i>
        <h1 class="h3 mb-2">سبسکرپشن پلان</h1>
        <p class="mb-0 text-muted">اپنی ضرورت کے مطابق بہترین پلان منتخب کریں۔</p>
    </div>

    @if(session('plan_requested'))
        <div class="alert alert-success" role="status">{{ session('plan_requested') }}</div>
    @endif

    @if($currentSubscription)
        @php($state = $currentSubscription->state())
        <div class="plans-panel mb-4 border-{{ $stateColors[$state] ?? 'secondary' }}">
            <div class="d-flex flex-wrap justify-content-between align-items-start" style="gap:1rem">
                <div>
                    <span class="badge badge-{{ $stateColors[$state] ?? 'secondary' }} mb-2">{{ $stateLabels[$state] ?? $state }}</span>
                    <h2 class="h5 mb-1">موجودہ پلان: {{ $currentSubscription->plan_name }}</h2>
                    <p class="mb-0 text-muted">{{ \App\Support\DisplayDate::long($currentSubscription->starts_on) }} تا {{ \App\Support\DisplayDate::long($currentSubscription->ends_on) }}</p>
                </div>
                <div><div class="small text-muted">بقایا رقم</div><div class="h4 mb-0">روپے {{ number_format($currentSubscription->balanceDue()) }}</div></div>
            </div>
            @if($currentSubscription->isEndingByShopRequest() && $state !== 'expired')
                {{-- The owner cancelled: the plan runs to its end date, then stops. --}}
                <div class="alert alert-warning mt-3 mb-0 d-flex flex-wrap justify-content-between align-items-center" style="gap:.75rem" role="status">
                    <div>
                        <strong><i class="fas fa-door-open" aria-hidden="true"></i> آپ نے سبسکرپشن منسوخ کر دی ہے۔</strong>
                        <div>آپ کا پلان {{ \App\Support\DisplayDate::long($currentSubscription->ends_on) }} تک ({{ $currentSubscription->daysRemaining() }} دن) چلتا رہے گا، اس کے بعد بند ہو جائے گا۔ آپ کا ڈیٹا محفوظ رہے گا۔</div>
                        @if($currentSubscription->cancel_request_reason)<div class="small text-muted mt-1">وجہ: {{ $currentSubscription->cancel_request_reason }}</div>@endif
                    </div>
                    <form method="POST" action="{{ route('admin.subscription.cancel.undo') }}">@csrf @method('DELETE')
                        <button class="btn btn-success btn-sm"><i class="fas fa-undo" aria-hidden="true"></i> منسوخی واپس لیں</button>
                    </form>
                </div>
            @else
                @if($state === 'expiring')<div class="alert alert-warning mt-3 mb-0">آپ کی سبسکرپشن {{ $currentSubscription->daysRemaining() }} دن میں ختم ہوگی۔ تجدید کے لیے نیچے سے پلان منتخب کریں۔</div>@endif
                @if(in_array($state, ['active', 'expiring'], true))
                    <div class="text-left mt-3">
                        <button type="button" class="btn btn-link btn-sm text-danger p-0" data-toggle="modal" data-target="#cancel-plan-modal"><i class="fas fa-times-circle" aria-hidden="true"></i> سبسکرپشن منسوخ کریں</button>
                    </div>
                @endif
            @endif
            @if($state === 'expired')<div class="alert alert-danger mt-3 mb-0">آپ کی سبسکرپشن کی میعاد ختم ہو چکی ہے۔ آپ کا کاروباری ڈیٹا محفوظ ہے؛ تجدید کے لیے نیچے سے پلان منتخب کریں۔</div>@endif
        </div>
    @endif

    @if($pendingRequest)
        <div class="alert alert-info d-flex flex-wrap align-items-center" style="gap:.5rem" role="status">
            <i class="fas fa-hourglass-half" aria-hidden="true"></i>
            @if($pendingRequest->payment_reference)
                <span>آپ کی ادائیگی زیر جائزہ ہے:</span> <strong>{{ $pendingRequest->plan?->name }}</strong>
                <span>روپے {{ number_format((float) $pendingRequest->amount) }}</span> · <span>TID</span> <code>{{ $pendingRequest->payment_reference }}</code>
                <span>تصدیق کے بعد آپ کا پلان فعال ہو جائے گا۔</span>
            @else
                <span>آپ نے یہ پلان مانگا ہے:</span> <strong>{{ $pendingRequest->plan?->name }}</strong>
                <span>سپر ایڈمن جلد اسے فعال کر دیں گے۔</span>
            @endif
        </div>
    @elseif($lastRequest && $lastRequest->status === \App\Models\SubscriptionPlanRequest::STATUS_REJECTED)
        <div class="alert alert-danger" role="alert">
            <strong>آپ کی ادائیگی کی تصدیق نہیں ہو سکی۔</strong>
            <span>{{ $lastRequest->plan?->name }}</span> · <span>TID</span> <code>{{ $lastRequest->payment_reference }}</code>
            <div class="mt-1"><span>وجہ:</span> {{ $lastRequest->review_note }}</div>
            <div class="small mt-1">درست تفصیل کے ساتھ دوبارہ پلان منتخب کریں۔</div>
        </div>
    @endif

    @if($planGroups->isNotEmpty())
        <div id="plans" class="plans-panel mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3" style="gap:.75rem">
                <h2 class="h5 mb-0"><i class="far fa-calendar-alt text-primary" aria-hidden="true"></i> بلنگ کا دورانیہ منتخب کریں</h2>
            </div>
            @if($planGroups->count() > 1)
                <div class="period-toggle mb-4" role="tablist" aria-label="Billing period">
                    @foreach($planGroups as $days => $group)
                        <button type="button" role="tab" id="period-tab-{{ $days }}" aria-controls="period-panel-{{ $days }}"
                            aria-selected="{{ $days == $defaultPeriod ? 'true' : 'false' }}"
                            class="{{ $days == $defaultPeriod ? 'is-active' : '' }}" data-period="{{ $days }}">
                            {{ $group['label'] }}
                            @if($days == 365 && $yearlySavings)<span class="save-chip">بچت</span>@endif
                        </button>
                    @endforeach
                </div>
            @endif

            @foreach($planGroups as $days => $group)
                <div class="row pt-2" id="period-panel-{{ $days }}" role="tabpanel" aria-labelledby="period-tab-{{ $days }}" data-period-panel="{{ $days }}" @if($days != $defaultPeriod) hidden @endif>
                    @foreach($group['plans'] as $plan)
                        @php($isCurrent = $currentPlanId === $plan->id)
                        @php($isRequested = $requestedPlanId === $plan->id)
                        @php($isPopular = in_array($plan->id, $popularPlanIds, true))
                        <div class="col-sm-6 col-xl-3 mb-4">
                            <div @class(['plan-card', 'is-popular' => $isPopular && ! $isCurrent, 'is-current' => $isCurrent])>
                                @if($isCurrent)<span class="plan-ribbon">موجودہ پلان</span>
                                @elseif($isPopular)<span class="plan-ribbon">مقبول</span>@endif
                                <h3>{{ preg_replace('/\s*\((Annual|Yearly|Monthly)\)$/i', '', $plan->name) }}</h3>
                                <div class="plan-price">روپے <strong>{{ number_format((float) $plan->price) }}</strong> <span class="text-muted">/ {{ $periodUnit((int) $plan->billing_period_days) }}</span></div>
                                <div class="plan-save">
                                    @if(isset($yearlySavings[$plan->id]))<span class="badge badge-success">روپے {{ number_format($yearlySavings[$plan->id]) }} کی بچت</span>@endif
                                </div>
                                <ul>
                                    <li><i class="fas fa-check-circle" aria-hidden="true"></i> {{ ['tailoring' => 'صرف ٹیلرنگ', 'clothing' => 'صرف کپڑا', 'both' => 'ٹیلرنگ اور کپڑا'][$plan->workspace()] ?? '—' }}</li>
                                    <li><i class="fas fa-check-circle" aria-hidden="true"></i> <span>زیادہ سے زیادہ گاہک</span> <strong>{{ $plan->max_customers === null ? 'لامحدود' : number_format($plan->max_customers) }}</strong></li>
                                    <li><i class="fas fa-check-circle" aria-hidden="true"></i> <span>زیادہ سے زیادہ ملازمین</span> <strong>{{ $plan->max_employees === null ? 'لامحدود' : number_format($plan->max_employees) }}</strong></li>
                                    @foreach(['allow_storefront' => 'آن لائن دکان', 'allow_financial_reports' => 'مالی رپورٹس', 'allow_activity_log' => 'ملازمین کی سرگرمی'] as $feature => $label)
                                        <li @class(['is-off' => ! $plan->{$feature}])><i class="fas {{ $plan->{$feature} ? 'fa-check-circle' : 'fa-times-circle' }}" aria-hidden="true"></i> {{ $label }}</li>
                                    @endforeach
                                    @if($plan->trial_days > 0 && ! $currentSubscription)<li class="text-success"><i class="fas fa-gift" aria-hidden="true"></i> {{ $plan->trial_days }} دن مفت آزمائش</li>@endif
                                </ul>
                                <div class="plan-action">
                                    @if($isCurrent)
                                        <button type="button" class="btn btn-success btn-block" disabled>موجودہ پلان</button>
                                    @elseif($isRequested)
                                        <button type="button" class="btn btn-outline-info btn-block" disabled><i class="fas fa-hourglass-half" aria-hidden="true"></i> {{ $pendingRequest->payment_reference ? 'ادائیگی زیر جائزہ' : 'درخواست بھیج دی گئی' }}</button>
                                    @else
                                        {{-- Always confirm in a popup first: payment details when Easypaisa is set up, otherwise a plain request. --}}
                                        @php($isUpgrade = $plan->isUpgradeFrom($currentPlan))
                                        <button type="button" class="btn btn-block {{ $isUpgrade ? 'btn-success' : ($isPopular ? 'btn-primary' : 'btn-outline-primary') }}"
                                            data-pay-upgrade="{{ $isUpgrade ? 1 : 0 }}"
                                            data-pay-plan="{{ $plan->id }}"
                                            data-pay-action="{{ route('admin.subscription.request', $plan) }}"
                                            data-pay-name="{{ preg_replace('/\s*\((Annual|Yearly|Monthly)\)$/i', '', $plan->name) }}"
                                            data-pay-period="{{ $periodName((int) $plan->billing_period_days) }}"
                                            data-pay-amount="{{ number_format((float) $plan->price) }}"
                                            data-pay-online="{{ $easypaisa && (float) $plan->price > 0 ? 1 : 0 }}">@if($isUpgrade)<i class="fas fa-arrow-up" aria-hidden="true"></i> اپ گریڈ کریں@elseمنتخب کریں@endif</button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
            @if($easypaisa)
                <p class="small text-muted mb-0 text-center"><i class="fas fa-mobile-alt text-success" aria-hidden="true"></i> ادائیگی ایزی پیسہ کے ذریعے کریں۔ تصدیق کے بعد پلان فعال کر دیا جائے گا۔</p>
            @else
                <p class="small text-muted mb-0 text-center">پلان منتخب کرنے پر آپ کی درخواست سپر ایڈمن کو بھیج دی جائے گی۔ ادائیگی کے بعد پلان فعال کر دیا جائے گا۔</p>
            @endif
        </div>
    @endif

    <div class="card"><div class="card-header bg-white"><h2 class="h5 mb-0">سبسکرپشن اور ادائیگی کی تاریخ</h2></div><div class="table-responsive"><table class="table mb-0">
        <thead><tr><th>مدت</th><th>فیس</th><th>وصول شدہ</th><th>بقایا</th><th>حالت</th></tr></thead>
        <tbody>@forelse($subscriptions as $subscription)<tr>
            <td>{{ $subscription->starts_on->format('d-m-Y') }} تا {{ $subscription->ends_on->format('d-m-Y') }}<br><small>{{ $subscription->plan_name }}</small></td>
            <td>روپے {{ number_format((float)$subscription->fee,2) }}</td><td>روپے {{ number_format($subscription->amountPaid(),2) }}</td><td>روپے {{ number_format($subscription->balanceDue(),2) }}</td>
            <td>@if($subscription->cancelled_at)<span class="badge badge-secondary">منسوخ</span>@else<span class="badge badge-{{ $stateColors[$subscription->state()] ?? 'secondary' }}">{{ $stateLabels[$subscription->state()] ?? $subscription->state() }}</span>@endif</td>
        </tr>
        @if($subscription->payments->isNotEmpty())<tr><td colspan="5" class="bg-light"><strong>ادائیگیاں:</strong> @foreach($subscription->payments as $payment)<span class="d-inline-block ml-3 {{ $payment->reversed_at?'text-muted':'' }}">روپے {{ number_format((float)$payment->amount,2) }} · {{ $payment->paid_on->format('d-m-Y') }} · {{ \App\Models\SubscriptionPayment::URDU_METHODS[$payment->payment_method] ?? $payment->payment_method }} @if($payment->reversed_at)(واپس/منسوخ)@endif</span>@endforeach</td></tr>@endif
        @empty<tr><td colspan="5" class="text-center text-muted py-5">ابھی کوئی سبسکرپشن ریکارڈ نہیں۔</td></tr>@endforelse</tbody>
    </table></div></div>
</div></section>

@if($currentSubscription && in_array($currentSubscription->state(), ['active', 'expiring'], true) && ! $currentSubscription->isEndingByShopRequest())
    @php($cancelDays = (int) ($currentSubscription->plan?->billing_period_days ?? ($currentSubscription->starts_on->diffInDays($currentSubscription->ends_on) + 1)))
    @php($cancelEnd = \App\Support\DisplayDate::long($currentSubscription->ends_on))
    <div class="modal fade" id="cancel-plan-modal" tabindex="-1" role="dialog" aria-labelledby="cancel-plan-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
            <form class="modal-content" method="POST" action="{{ route('admin.subscription.cancel') }}">@csrf
                <div class="pay-head">
                    <i class="fas fa-rocket pay-rocket" aria-hidden="true"></i>
                    <h5 id="cancel-plan-title">سبسکرپشن منسوخ کریں</h5>
                    <p class="cancel-subtitle">آپ کا پلان {{ $cancelEnd }} تک چلتا رہے گا</p>
                    <button type="button" class="pay-close" data-dismiss="modal" aria-label="بند کریں"><i class="fas fa-times" aria-hidden="true"></i></button>
                </div>
                <div class="modal-body">
                    <div class="cancel-plan-card">
                        <div class="cancel-plan-name"><i class="fas fa-crown" aria-hidden="true"></i> <span dir="ltr" translate="no">{{ $currentSubscription->plan_name }}</span></div>
                        <div class="pay-price">
                            <span>روپے</span>
                            <strong dir="ltr">{{ number_format((float) $currentSubscription->fee) }}</strong>
                            <span>/ {{ $periodName($cancelDays) }}</span>
                        </div>
                    </div>

                    <ul class="pay-features cancel-points">
                        <li><i class="fas fa-check-circle" aria-hidden="true"></i><span>{{ $cancelEnd }} کے بعد پلان کی تجدید نہیں ہوگی اور ایپ کا استعمال رک جائے گا</span></li>
                        <li><i class="fas fa-check-circle" aria-hidden="true"></i><span>آپ کا سارا ڈیٹا محفوظ رہے گا اور دوبارہ پلان لینے پر واپس مل جائے گا</span></li>
                        <li><i class="fas fa-check-circle" aria-hidden="true"></i><span>ادا شدہ رقم واپس نہیں ہوگی</span></li>
                        <li><i class="fas fa-check-circle" aria-hidden="true"></i><span>تاریخ سے پہلے آپ منسوخی واپس لے سکتے ہیں</span></li>
                    </ul>

                    <div class="cancel-label">منسوخی کی وجہ منتخب کریں</div>
                    <div class="cancel-reasons" role="group" aria-label="منسوخی کی وجہ">
                        @foreach(['قیمت زیادہ ہے', 'کاروبار بند کر رہے ہیں', 'ضرورت کی سہولت موجود نہیں', 'کوئی اور ایپ استعمال کریں گے', 'استعمال میں مشکل ہے', 'کوئی اور وجہ'] as $pick)
                            <label class="cancel-reason">
                                <input type="radio" name="cancel_reason_pick" value="{{ $loop->last ? '' : $pick }}">
                                <span>{{ $pick }}</span>
                                <i class="fas fa-check-circle" aria-hidden="true"></i>
                            </label>
                        @endforeach
                    </div>
                    <label for="cancel_reason" class="sr-only">منسوخی کی وجہ</label>
                    <textarea id="cancel_reason" name="cancel_reason" class="form-control @error('cancel_reason') is-invalid @enderror" rows="2" minlength="3" maxlength="1000" required placeholder="وجہ یہاں لکھیں…">{{ old('cancel_reason') }}</textarea>
                    @error('cancel_reason')<div class="pay-error" role="alert">{{ $message }}</div>@enderror

                    <div class="custom-control custom-checkbox cancel-confirm">
                        <input type="checkbox" class="custom-control-input" id="cancel_confirm" name="cancel_confirm" value="1" required>
                        <label class="custom-control-label" for="cancel_confirm">مجھے معلوم ہے کہ {{ $cancelEnd }} کے بعد پلان بند ہو جائے گا۔</label>
                    </div>
                    @error('cancel_confirm')<div class="pay-error text-center" role="alert">{{ $message }}</div>@enderror
                </div>
                <div class="modal-footer">
                    <button class="btn cancel-submit">منسوخی کی تصدیق کریں <i class="fas fa-arrow-left" aria-hidden="true"></i></button>
                    <button type="button" class="btn cancel-keep" data-dismiss="modal"><i class="fas fa-times" aria-hidden="true"></i> پلان جاری رکھیں</button>
                </div>
            </form>
        </div>
    </div>
    @push('scripts')
    <script>
        $(function () {
            $('[name=cancel_reason_pick]').on('change', function () { $('#cancel_reason').val(this.value); if (!this.value) { $('#cancel_reason').trigger('focus'); } });
            @if($errors->hasAny(['cancel_reason', 'cancel_confirm']))$('#cancel-plan-modal').modal('show');@endif
        });
    </script>
    @endpush
@endif
@if($planGroups->isNotEmpty())
    @php($oldPayPlan = old('plan_id') ? $planGroups->flatMap(fn ($group) => $group['plans'])->firstWhere('id', (int) old('plan_id')) : null)
    <div class="modal fade" id="pay-modal" tabindex="-1" role="dialog" aria-labelledby="pay-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
            <form class="modal-content{{ $easypaisa ? ' is-online' : '' }}" method="POST" enctype="multipart/form-data" action="{{ $oldPayPlan ? route('admin.subscription.request', $oldPayPlan) : '' }}" id="pay-form">
                @csrf
                <input type="hidden" name="plan_id" value="{{ $oldPayPlan?->id }}">
                <div class="pay-head">
                    <i class="fas fa-rocket pay-rocket" aria-hidden="true"></i>
                    <h5 id="pay-modal-title"><span class="js-online">تفصیلات ملاحظہ کریں</span><span class="js-offline">پلان کی درخواست بھیجیں</span></h5>
                    <button type="button" class="pay-close" data-dismiss="modal" aria-label="بند کریں"><i class="fas fa-times" aria-hidden="true"></i></button>
                </div>
                <div class="modal-body pay-grid">
                    <div class="pay-plan">
                    <div class="pay-chip"><i class="fas fa-crown" aria-hidden="true"></i><span dir="ltr" data-pay-field="name"></span></div>
                    <div class="pay-price">
                        <span>روپے</span>
                        <strong dir="ltr" data-pay-field="amount"></strong>
                        <span>/ <span data-pay-field="period"></span></span>
                    </div>
                    <ul class="pay-features" data-pay-field="features"></ul>

                    <div class="pay-upgrade js-upgrade">
                        <label for="upgrade_reason"><i class="fas fa-arrow-up" aria-hidden="true"></i> پلان اپ گریڈ کرنے کی وجہ</label>
                        <textarea id="upgrade_reason" name="upgrade_reason" class="form-control @error('upgrade_reason') is-invalid @enderror" rows="2" minlength="5" maxlength="1000" required placeholder="مثلاً مزید گاہک یا ملازمین چاہئیں، آن لائن دکان چاہیے…">{{ old('upgrade_reason') }}</textarea>
                        @error('upgrade_reason')<div class="pay-error" role="alert">{{ $message }}</div>@enderror
                    </div>

                    <p class="js-offline text-center mb-0">کیا آپ یہ پلان لینا چاہتے ہیں؟ درخواست بھیجنے کے بعد سپر ایڈمن ادائیگی اور پلان فعال کرنے کے لیے آپ سے رابطہ کریں گے۔</p>
                    </div>

                    @if($easypaisa)
                    <div class="js-online pay-pay">
                    <div class="pay-account">
                        @if($easypaisa['qr_path'])
                            <div class="pay-qr">
                                <img src="{{ route('platform.easypaisa-qr') }}" alt="ایزی پیسہ QR کوڈ">
                                <small dir="ltr">SCAN TO PAY</small>
                            </div>
                        @endif
                        <div class="pay-details">
                            <div class="small text-muted mb-1">ادائیگی کی تفصیلات (ایزی پیسہ):</div>
                            @if($easypaisa['account_name'])<div class="pay-name" dir="ltr" translate="no">{{ $easypaisa['account_name'] }}</div>@endif
                            <div class="d-flex flex-wrap align-items-center mt-1" style="gap:.6rem">
                                <span class="pay-number" dir="ltr" translate="no"><i class="fas fa-phone-alt" aria-hidden="true"></i><span id="easypaisa-number">{{ $easypaisa['number'] }}</span></span>
                                <button type="button" class="btn btn-sm btn-copy" data-copy="#easypaisa-number"><i class="far fa-copy" aria-hidden="true"></i> <span>کاپی کریں</span></button>
                            </div>
                        </div>
                    </div>

                    <label for="payment_reference" class="pay-hint d-block"><i class="fas fa-info-circle" aria-hidden="true"></i> رقم بھیجنے کے بعد ایپ یا ایس ایم ایس سے ٹرانزیکشن آئی ڈی (TID) درج کریں — ایزی پیسہ، جاز کیش، سادہ پے یا راست</label>
                    <div class="pay-tid">
                        <input id="payment_reference" name="payment_reference" class="form-control @error('payment_reference') is-invalid @enderror" dir="ltr" autocomplete="off" autocapitalize="characters" spellcheck="false" maxlength="40" required placeholder="e.g. 12345678901 / RAAST-45372891" value="{{ old('payment_reference') }}">
                        <i class="fas fa-mobile-alt" aria-hidden="true"></i>
                    </div>
                    @error('payment_reference')<div class="pay-error" role="alert">{{ $message }}</div>@enderror
                    <div class="pay-proof">
                        <input id="payment_proof" type="file" name="payment_proof" class="sr-only" accept="image/png,image/jpeg,image/webp,application/pdf">
                        <label for="payment_proof" class="pay-proof-btn mb-0"><i class="fas fa-paperclip" aria-hidden="true"></i> اسکرین شاٹ لگائیں</label>
                        <span class="pay-proof-name" data-proof-name>اختیاری — تصدیق جلدی ہو جاتی ہے</span>
                    </div>
                    @error('payment_proof')<div class="pay-error" role="alert">{{ $message }}</div>@enderror
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button class="btn btn-success pay-submit"><i class="fas fa-shield-alt" aria-hidden="true"></i> <span class="js-online">ادائیگی کی تصدیق کریں</span><span class="js-offline">درخواست بھیجیں</span></button>
                    <button type="button" class="btn pay-cancel" data-dismiss="modal"><i class="fas fa-times-circle" aria-hidden="true"></i> بند کریں</button>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
    $(function () {
        $(document).on('click', '[data-period]', function () {
            var period = String($(this).data('period'));
            $('[data-period]').each(function () {
                var active = String($(this).data('period')) === period;
                $(this).toggleClass('is-active', active).attr('aria-selected', active ? 'true' : 'false');
            });
            $('[data-period-panel]').each(function () {
                this.hidden = String($(this).data('period-panel')) !== period;
            });
        });

        // Select on a paid plan opens the Easypaisa payment popup for that plan.
        var openPay = function (button) {
            var form = $('#pay-form');
            form.attr('action', button.data('pay-action'));
            form.find('[name=plan_id]').val(button.data('pay-plan'));
            form.find('[data-pay-field=name]').text(button.data('pay-name'));
            form.find('[data-pay-field=period]').text(button.data('pay-period'));
            form.find('[data-pay-field=amount]').text(button.data('pay-amount'));
            // Show the plan's included features (the ticked lines on its card).
            var features = form.find('[data-pay-field=features]').empty();
            button.closest('.plan-card').find('ul li:not(.is-off)').each(function () {
                var text = $(this).text().replace(/\s+/g, ' ').trim();
                if (text) {
                    $('<li>').append('<i class="fas fa-check-circle" aria-hidden="true"></i>', $('<span>').text(text)).appendTo(features);
                }
            });
            // Free plans, or no Easypaisa account yet: just confirm the request, no payment fields.
            var online = String(button.data('pay-online')) === '1';
            form.toggleClass('is-online', online);
            form.find('.js-online').toggle(online);
            form.find('.js-offline').toggle(!online);
            // Upgrades to a pricier plan must say why.
            var upgrade = String(button.data('pay-upgrade')) === '1';
            form.find('.js-upgrade').toggle(upgrade).find(':input').prop('disabled', !upgrade);
            form.find('#pay-modal-title .js-online').text(upgrade ? 'پلان اپ گریڈ کریں' : 'تفصیلات ملاحظہ کریں');
            form.find('.js-online :input').prop('disabled', !online);
            $('#pay-modal').modal('show');
        };
        $(document).on('click', '[data-pay-plan]', function () {
            $('#payment_reference, #upgrade_reason').val('').removeClass('is-invalid');
            $('#payment_proof').val('').trigger('change');
            $('#pay-modal .pay-error').remove();
            $('#pay-modal .is-invalid').removeClass('is-invalid');
            openPay($(this));
        });
        @if($errors->hasAny(['payment_reference', 'payment_proof', 'upgrade_reason']) && old('plan_id'))
            openPay($('[data-pay-plan="{{ (int) old('plan_id') }}"]').first());
        @endif

        var proofHint = $('[data-proof-name]').text();
        $(document).on('change', '#payment_proof', function () {
            $('[data-proof-name]').text(this.files && this.files.length ? this.files[0].name : proofHint);
        });

        $(document).on('click', '[data-copy]', function () {
            var button = $(this);
            var text = $(button.data('copy')).text().trim();
            var done = function () {
                var label = button.find('span');
                var original = label.text();
                label.text('✓');
                setTimeout(function () { label.text(original); }, 1500);
            };
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(done);
            } else {
                var input = $('<input>').val(text).appendTo('body').select();
                document.execCommand('copy');
                input.remove();
                done();
            }
        });
    });
</script>
@endpush
