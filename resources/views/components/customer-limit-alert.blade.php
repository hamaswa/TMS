{{-- Shown on pages that add customers once the shop's plan is full, so the block is never silent. --}}
@auth
    @php($customerLimit = app(\App\Services\SubscriptionEntitlementService::class)->customerLimitReached((int) Auth::user()->businessOwnerId()))
    @if($customerLimit)
        <div class="alert alert-danger d-flex flex-wrap justify-content-between align-items-center" role="alert" style="gap:.5rem">
            <span><i class="fas fa-users-slash" aria-hidden="true"></i> {{ \App\Services\SubscriptionEntitlementService::customerLimitMessage($customerLimit) }}</span>
            @if(Auth::user()->isBusinessOwner())
                <a class="btn btn-sm btn-danger" href="{{ route('admin.subscription.index') }}#plans">پلان اپ گریڈ کریں</a>
            @endif
        </div>
    @endif
@endauth
