@extends('main')

@section('content')
<style>
    .storefront-orders-head{display:flex;justify-content:space-between;align-items:center;gap:18px}
    .storefront-orders-head h1{line-height:1.8}
    @media(max-width:575px){
        .storefront-orders-head{display:block}
        .storefront-orders-head .btn{display:inline-block;margin-top:10px}
    }
</style>
<section class="main-content" dir="rtl">
    <div class="container">
        <div class="card">
            <div class="card-body">
                <div class="storefront-orders-head mb-3">
                    <div><div class="text-muted small">آن لائن دکان</div><h1 class="h3 mb-0">کپڑے کے آن لائن آرڈرز</h1></div>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.storefront.edit') }}">دکان کی ترتیب</a>
                </div>
                @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
                @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
                <form method="GET" class="form-row mb-4">
                    <div class="col-md-6 mb-2"><label for="order_search">حوالہ، نام یا فون</label><input id="order_search" class="form-control" name="search" value="{{ request('search') }}"></div>
                    <div class="col-md-4 mb-2"><label for="order_status">حالت</label><select id="order_status" class="form-control" name="status"><option value="">تمام</option><option value="pending" @selected(request('status')==='pending')>زیرِ انتظار</option><option value="complete" @selected(request('status')==='complete')>مکمل</option><option value="cancelled" @selected(request('status')==='cancelled')>منسوخ</option></select></div>
                    <div class="col-md-2 mb-2 d-flex align-items-end"><button class="btn btn-primary btn-block">تلاش کریں</button></div>
                </form>
                @forelse($orders as $order)
                    @php $statusLabels=['pending'=>'زیرِ انتظار','complete'=>'مکمل','cancelled'=>'منسوخ']; @endphp
                    <article class="border rounded p-3 mb-3">
                        <div class="d-flex flex-wrap justify-content-between">
                            <div><strong dir="ltr">{{ $order->reference }}</strong><div>{{ $order->customer->name }} · <span dir="ltr">{{ $order->customer->phone_number1 }}</span></div><small class="text-muted">{{ $order->placed_at->format('d-m-Y h:i A') }}</small></div>
                            <div class="text-left"><span class="badge badge-{{ $order->status==='pending'?'warning':($order->status==='complete'?'success':'secondary') }}">{{ $statusLabels[$order->status] ?? $order->status }}</span><div class="h5 mt-2">Rs {{ number_format($order->subtotal,2) }}</div></div>
                        </div>
                        <div class="mt-2"><strong>ادائیگی:</strong> {{ \App\Models\StorefrontOrder::paymentMethods()[$order->payment_method] ?? $order->payment_method }}
                            @if($order->payment_method === \App\Models\StorefrontOrder::PAYMENT_EASYPAISA)
                                · <span dir="ltr">{{ $order->payment_sender_phone }}</span>
                                · <code>{{ $order->payment_reference }}</code>
                                <span class="badge badge-info">دستی تصدیق درکار</span>
                            @endif
                        </div>
                        <ul class="mt-2 mb-3">@foreach($order->items as $item)<li>{{ $item->item_name }} — {{ $item->color }}، {{ number_format($item->quantity,2) }} میٹر</li>@endforeach</ul>
                        @if($order->status==='pending')
                            <div class="d-flex flex-wrap">
                                <form method="POST" action="{{ route('admin.storefront.orders.update',$order) }}" class="ml-2">@csrf @method('PATCH')<input type="hidden" name="status" value="complete"><button class="btn btn-success">مکمل کریں</button></form>
                                <form method="POST" action="{{ route('admin.storefront.orders.update',$order) }}" onsubmit="return confirm('آرڈر منسوخ کرنے سے اسٹاک اور گاہک کا بقایا واپس ہوگا۔ جاری رکھیں؟')">@csrf @method('PATCH')<input type="hidden" name="status" value="cancelled"><button class="btn btn-outline-danger">منسوخ کریں</button></form>
                            </div>
                        @endif
                    </article>
                @empty
                    <div class="alert alert-light text-center">کوئی آن لائن آرڈر موجود نہیں۔</div>
                @endforelse
                {{ $orders->links() }}
            </div>
        </div>
    </div>
</section>
@endsection
