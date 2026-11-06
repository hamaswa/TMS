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
            @if($state === 'expiring')<div class="alert alert-warning mt-3 mb-0">آپ کی سبسکرپشن {{ $currentSubscription->daysRemaining() }} دن میں ختم ہوگی۔ تجدید کے لیے نیچے سے پلان منتخب کریں۔</div>@endif
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
                                        <button type="button" class="btn btn-block {{ $isPopular ? 'btn-primary' : 'btn-outline-primary' }}"
                                            data-pay-plan="{{ $plan->id }}"
                                            data-pay-action="{{ route('admin.subscription.request', $plan) }}"
                                            data-pay-name="{{ preg_replace('/\s*\((Annual|Yearly|Monthly)\)$/i', '', $plan->name) }}"
                                            data-pay-period="{{ $periodName((int) $plan->billing_period_days) }}"
                                            data-pay-amount="{{ number_format((float) $plan->price) }}"
                                            data-pay-online="{{ $easypaisa && (float) $plan->price > 0 ? 1 : 0 }}">منتخب کریں</button>
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

@if($planGroups->isNotEmpty())
    @php($payErrors = $errors->hasAny(['payment_reference', 'payment_proof']))
    @php($oldPayPlan = old('plan_id') ? $planGroups->flatMap(fn ($group) => $group['plans'])->firstWhere('id', (int) old('plan_id')) : null)
    <div class="modal fade" id="pay-modal" tabindex="-1" role="dialog" aria-labelledby="pay-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
            <form class="modal-content" method="POST" enctype="multipart/form-data" action="{{ $oldPayPlan ? route('admin.subscription.request', $oldPayPlan) : '' }}" id="pay-form">
                @csrf
                <input type="hidden" name="plan_id" value="{{ $oldPayPlan?->id }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="pay-modal-title"><span class="js-online">ایزی پیسہ سے ادائیگی کریں</span><span class="js-offline">پلان کی درخواست بھیجیں</span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="بند کریں"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="pay-summary text-center rounded p-3 mb-3" style="background:#f1f5fa">
                        <div class="small text-muted" data-pay-field="label"></div>
                        <div class="h3 mb-0">روپے <strong data-pay-field="amount"></strong></div>
                    </div>

                    @if($payErrors)
                        <div class="alert alert-danger"><ul class="mb-0 pl-3">@foreach(array_merge($errors->get('payment_reference'), $errors->get('payment_proof')) as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                    @endif

                    <p class="js-offline mb-0">کیا آپ یہ پلان لینا چاہتے ہیں؟ درخواست بھیجنے کے بعد سپر ایڈمن ادائیگی اور پلان فعال کرنے کے لیے آپ سے رابطہ کریں گے۔</p>

                    @if($easypaisa)
                    <div class="js-online">
                    <ol class="small pl-3 mb-3">
                        <li class="mb-1">نیچے دیے گئے ایزی پیسہ اکاؤنٹ میں اوپر لکھی رقم بھیجیں۔</li>
                        <li class="mb-1">ادائیگی کے بعد ایس ایم ایس یا ایپ سے ٹرانزیکشن آئی ڈی نوٹ کریں۔</li>
                        <li>ٹرانزیکشن آئی ڈی نیچے درج کریں اور بھیجیں۔ تصدیق کے بعد پلان فعال ہو جائے گا۔</li>
                    </ol>

                    <div class="d-flex flex-wrap align-items-center border rounded p-3 mb-3" style="gap:1rem">
                        @if($easypaisa['qr_path'])
                            <img src="{{ route('platform.easypaisa-qr') }}" alt="ایزی پیسہ QR کوڈ" class="border rounded" style="width:132px;height:132px;object-fit:contain">
                        @endif
                        <div class="flex-grow-1">
                            <div class="small text-muted">ایزی پیسہ اکاؤنٹ</div>
                            @if($easypaisa['account_name'])<div class="font-weight-bold" dir="ltr" translate="no">{{ $easypaisa['account_name'] }}</div>@endif
                            <div class="d-flex align-items-center mt-1" style="gap:.5rem">
                                <span class="h4 mb-0" dir="ltr" translate="no" id="easypaisa-number">{{ $easypaisa['number'] }}</span>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-copy="#easypaisa-number"><i class="far fa-copy" aria-hidden="true"></i> <span>کاپی کریں</span></button>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="payment_reference">ٹرانزیکشن آئی ڈی (TID)</label>
                        <input id="payment_reference" name="payment_reference" class="form-control @error('payment_reference') is-invalid @enderror" dir="ltr" inputmode="numeric" autocomplete="off" maxlength="30" required placeholder="e.g. 12345678901" value="{{ old('payment_reference') }}">
                    </div>
                    <div class="form-group mb-0">
                        <label for="payment_proof">ادائیگی کا اسکرین شاٹ <span class="text-muted">(اختیاری)</span></label>
                        <input id="payment_proof" type="file" name="payment_proof" class="form-control-file @error('payment_proof') is-invalid @enderror" accept="image/png,image/jpeg,image/webp,application/pdf">
                        <small class="form-text text-muted">اسکرین شاٹ سے تصدیق جلدی ہو جاتی ہے۔</small>
                    </div>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">بند کریں</button>
                    <button class="btn btn-success"><i class="fas fa-paper-plane" aria-hidden="true"></i> <span class="js-online">ادائیگی کی تفصیل بھیجیں</span><span class="js-offline">درخواست بھیجیں</span></button>
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
            form.find('[data-pay-field=label]').text(button.data('pay-name') + ' · ' + button.data('pay-period'));
            form.find('[data-pay-field=amount]').text(button.data('pay-amount'));
            // Free plans, or no Easypaisa account yet: just confirm the request, no payment fields.
            var online = String(button.data('pay-online')) === '1';
            form.find('.js-online').toggle(online);
            form.find('.js-offline').toggle(!online);
            form.find('.js-online :input').prop('disabled', !online);
            $('#pay-modal').modal('show');
        };
        $(document).on('click', '[data-pay-plan]', function () {
            $('#payment_reference').val('').removeClass('is-invalid');
            $('#pay-modal .alert-danger').remove();
            openPay($(this));
        });
        @if($easypaisa && $errors->hasAny(['payment_reference', 'payment_proof']) && old('plan_id'))
            openPay($('[data-pay-plan="{{ (int) old('plan_id') }}"]').first());
        @endif

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
