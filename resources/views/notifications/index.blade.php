@extends('main')

@section('content')
<section class="main-content"><div class="container-fluid px-3 px-md-4 py-4" style="max-width:1000px">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div><h1 class="h3 mb-1">اطلاعات</h1><p class="text-muted mb-0">سبسکرپشن، آن لائن آرڈر اور کاروباری سرگرمی کی تازہ اطلاعات۔</p></div>
        @if(Auth::user()->isBusinessOwner())<a class="btn btn-outline-primary" href="{{ route('admin.subscription.index') }}">سبسکرپشن دیکھیں</a>@endif
    </div>
    <div class="card"><div class="list-group list-group-flush">
        @forelse($notifications as $notification)
            @php($data=$notification->data)
            <div class="list-group-item py-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div><span class="badge badge-{{ ($data['type'] ?? '')==='subscription'?'warning':'primary' }} ml-2">{{ ($data['type'] ?? '')==='subscription'?'سبسکرپشن':'کاروباری اطلاع' }}</span><strong>{{ $data['subject'] ?? $data['message'] ?? 'نئی اطلاع' }}</strong></div>
                    <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                </div>
                <p class="mb-1 mt-2">{{ $data['about'] ?? $data['message'] ?? '' }}</p>
                @if(!empty($data['action_url']))<a href="{{ $data['action_url'] }}" class="small">تفصیل دیکھیں</a>@endif
            </div>
        @empty<div class="text-center text-muted py-5">ابھی کوئی اطلاع موجود نہیں۔</div>@endforelse
    </div></div>
    <div class="mt-3">{{ $notifications->links() }}</div>
</div></section>
@endsection
