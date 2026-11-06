@extends('main')
@section('content')
<section class="main-content"><div class="container-fluid px-3 px-md-4 py-4" style="max-width:1100px">
    <div class="mb-4"><h1 class="h3 mb-1">سبسکرپشن اور ادائیگی</h1><p class="text-muted mb-0">اپنی موجودہ مدت، بقایا رقم اور سپر ایڈمن کے درج کردہ ادائیگی ریکارڈ دیکھیں۔</p></div>
    @if($currentSubscription)
        @php($state=$currentSubscription->state())
        <div class="card mb-4 border-{{ ['active'=>'success','expiring'=>'warning','expired'=>'danger'][$state] ?? 'secondary' }}"><div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start">
                <div><span class="badge badge-{{ ['active'=>'success','expiring'=>'warning','expired'=>'danger'][$state] ?? 'secondary' }} mb-2">{{ ['active'=>'فعال','expiring'=>'جلد ختم ہوگی','expired'=>'میعاد ختم'][$state] ?? $state }}</span><h2 class="h4">{{ $currentSubscription->plan_name }}</h2><p class="mb-0">{{ $currentSubscription->starts_on->format('d-m-Y') }} تا {{ $currentSubscription->ends_on->format('d-m-Y') }}</p></div>
                <div class="text-left mt-3 mt-md-0"><div class="small text-muted">بقایا رقم</div><div class="h3 mb-0">روپے {{ number_format($currentSubscription->balanceDue(),2) }}</div></div>
            </div>
            @if($state==='expiring')<div class="alert alert-warning mt-3 mb-0">آپ کی سبسکرپشن {{ $currentSubscription->daysRemaining() }} دن میں ختم ہوگی۔ تجدید کے لیے سپر ایڈمن سے رابطہ کریں۔</div>@endif
            @if($state==='expired')<div class="alert alert-danger mt-3 mb-0">آپ کی سبسکرپشن کی میعاد ختم ہو چکی ہے۔ آپ کا کاروباری ڈیٹا محفوظ ہے؛ تجدید کے لیے سپر ایڈمن سے رابطہ کریں۔</div>@endif
        </div></div>
    @else
        <div class="alert alert-info">ابھی آپ کے اکاؤنٹ کے لیے سبسکرپشن مدت مقرر نہیں کی گئی۔ اکاؤنٹ اور کاروباری ڈیٹا معمول کے مطابق محفوظ ہیں۔</div>
    @endif
    <div class="card"><div class="card-header bg-white"><h2 class="h5 mb-0">سبسکرپشن اور ادائیگی کی تاریخ</h2></div><div class="table-responsive"><table class="table mb-0">
        <thead><tr><th>مدت</th><th>فیس</th><th>وصول شدہ</th><th>بقایا</th><th>حالت</th></tr></thead>
        <tbody>@forelse($subscriptions as $subscription)<tr>
            <td>{{ $subscription->starts_on->format('d-m-Y') }} تا {{ $subscription->ends_on->format('d-m-Y') }}<br><small>{{ $subscription->plan_name }}</small></td>
            <td>روپے {{ number_format((float)$subscription->fee,2) }}</td><td>روپے {{ number_format($subscription->amountPaid(),2) }}</td><td>روپے {{ number_format($subscription->balanceDue(),2) }}</td>
            <td>@if($subscription->cancelled_at)<span class="badge badge-secondary">منسوخ</span>@else<span class="badge badge-{{ ['active'=>'success','expiring'=>'warning','expired'=>'danger'][$subscription->state()] ?? 'secondary' }}">{{ ['active'=>'فعال','expiring'=>'جلد ختم ہوگی','expired'=>'میعاد ختم'][$subscription->state()] ?? $subscription->state() }}</span>@endif</td>
        </tr>
        @if($subscription->payments->isNotEmpty())<tr><td colspan="5" class="bg-light"><strong>ادائیگیاں:</strong> @foreach($subscription->payments as $payment)<span class="d-inline-block ml-3 {{ $payment->reversed_at?'text-muted':'' }}">روپے {{ number_format((float)$payment->amount,2) }} · {{ $payment->paid_on->format('d-m-Y') }} · {{ \App\Models\SubscriptionPayment::URDU_METHODS[$payment->payment_method] ?? $payment->payment_method }} @if($payment->reversed_at)(واپس/منسوخ)@endif</span>@endforeach</td></tr>@endif
        @empty<tr><td colspan="5" class="text-center text-muted py-5">ابھی کوئی سبسکرپشن ریکارڈ نہیں۔</td></tr>@endforelse</tbody>
    </table></div></div>

    @if($plans->isNotEmpty())
        <div id="plans" class="pt-4">
            <h2 class="h4 mb-1">دستیاب پلان</h2>
            <p class="text-muted">پلان فعال کروانے یا تبدیل کرنے کے لیے سپر ایڈمن سے رابطہ کریں۔</p>
            <div class="row">
                @foreach($plans as $plan)
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="card h-100 {{ $plan->is_recommended ? 'border-primary shadow-sm' : '' }}">
                            <div class="card-body d-flex flex-column">
                                @if($plan->is_recommended)<span class="badge badge-primary align-self-start mb-2">تجویز کردہ</span>@endif
                                <h3 class="h5 mb-1">{{ $plan->name }}</h3>
                                <div class="h4 mb-3">روپے {{ number_format((float) $plan->price) }} <small class="text-muted">/ {{ $plan->billing_period_days == 365 ? 'سال' : ($plan->billing_period_days == 30 ? 'ماہ' : $plan->billing_period_days.' دن') }}</small></div>
                                <ul class="list-unstyled small mb-0">
                                    <li class="mb-1"><i class="fas fa-store fa-fw text-primary"></i> {{ ['tailoring' => 'صرف ٹیلرنگ', 'clothing' => 'صرف کپڑا', 'both' => 'ٹیلرنگ اور کپڑا'][$plan->workspace()] ?? '—' }}</li>
                                    <li class="mb-1"><i class="fas fa-users fa-fw text-primary"></i> زیادہ سے زیادہ گاہک: {{ $plan->max_customers === null ? 'لامحدود' : number_format($plan->max_customers) }}</li>
                                    <li class="mb-1"><i class="fas fa-user-tie fa-fw text-primary"></i> زیادہ سے زیادہ ملازمین: {{ $plan->max_employees === null ? 'لامحدود' : number_format($plan->max_employees) }}</li>
                                    @foreach(['allow_storefront' => 'آن لائن دکان', 'allow_financial_reports' => 'مالی رپورٹس', 'allow_activity_log' => 'ملازمین کی سرگرمی'] as $feature => $label)
                                        <li class="mb-1 {{ $plan->{$feature} ? '' : 'text-muted' }}"><i class="fas {{ $plan->{$feature} ? 'fa-check text-success' : 'fa-times' }} fa-fw"></i> {{ $label }}</li>
                                    @endforeach
                                </ul>
                                @if($plan->trial_days > 0 && ! $currentSubscription)<div class="small text-success mt-auto pt-3">{{ $plan->trial_days }} دن مفت آزمائش</div>@endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div></section>
@endsection
