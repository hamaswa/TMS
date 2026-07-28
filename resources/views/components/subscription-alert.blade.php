@auth
    @if(Auth::user()->isBusinessOwner())
        @php($subscription = Auth::user()->business?->latestSubscription()->first())
        @if($subscription && in_array($subscription->state(), ['expiring','expired'], true))
            <div class="container-fluid px-3 px-md-4 pt-3">
                <div class="alert alert-{{ $subscription->state()==='expired'?'danger':'warning' }} mb-0 d-flex flex-wrap justify-content-between align-items-center" role="alert">
                    <div><strong>{{ $subscription->state()==='expired'?'آپ کی سبسکرپشن کی میعاد ختم ہو چکی ہے۔':'آپ کی سبسکرپشن جلد ختم ہونے والی ہے۔' }}</strong>
                    <span>{{ $subscription->ends_on->format('d-m-Y') }} @if($subscription->state()==='expiring')· {{ $subscription->daysRemaining() }} دن باقی@endif</span></div>
                    <a class="btn btn-sm btn-outline-dark mt-2 mt-md-0" href="{{ route('admin.subscription.index') }}">تفصیل دیکھیں</a>
                </div>
            </div>
        @endif
    @endif
@endauth
