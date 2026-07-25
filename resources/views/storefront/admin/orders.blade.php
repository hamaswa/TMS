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
                    @if(Auth::user()->hasBusinessPermission('storefront.manage'))<a class="btn btn-outline-secondary" href="{{ route('admin.storefront.edit') }}">دکان کی ترتیب</a>@endif
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
                            @if(\App\Models\StorefrontOrder::requiresManualVerification($order->payment_method))
                                @if($order->payment_sender_phone) · <span dir="ltr">{{ $order->payment_sender_phone }}</span>@endif
                                · <code>{{ $order->payment_reference }}</code>
                                <span class="badge badge-{{ $order->payment_verification_status === \App\Models\StorefrontOrder::VERIFICATION_VERIFIED ? 'success' : ($order->payment_verification_status === \App\Models\StorefrontOrder::VERIFICATION_REJECTED ? 'danger' : 'info') }}">{{ \App\Models\StorefrontOrder::verificationStatuses()[$order->payment_verification_status] ?? 'دستی تصدیق درکار' }}</span>
                            @endif
                        </div>
                        @if(\App\Models\StorefrontOrder::requiresManualVerification($order->payment_method))
                            @php
                                $verificationLabels = \App\Models\StorefrontOrder::verificationStatuses();
                                $verificationClass = match($order->payment_verification_status) {
                                    \App\Models\StorefrontOrder::VERIFICATION_VERIFIED => 'success',
                                    \App\Models\StorefrontOrder::VERIFICATION_REJECTED => 'danger',
                                    default => 'warning',
                                };
                            @endphp
                            <div class="border rounded bg-light p-3 mt-3">
                                <div class="d-flex flex-wrap justify-content-between align-items-center">
                                    <strong>{{ \App\Models\StorefrontOrder::paymentMethods()[$order->payment_method] ?? $order->payment_method }} تصدیق</strong>
                                    <span class="badge badge-{{ $verificationClass }}">{{ $verificationLabels[$order->payment_verification_status] ?? $order->payment_verification_status }}</span>
                                </div>
                                @if($order->paymentVerifier)
                                    <small class="text-muted d-block mt-2">
                                        کارروائی: {{ $order->paymentVerifier->name ?: $order->paymentVerifier->username }}
                                        · {{ ($order->payment_verified_at ?: $order->payment_rejected_at)?->format('d-m-Y h:i A') }}
                                    </small>
                                @endif
                                @if($order->payment_verification_notes)
                                    <div class="small mt-2">{{ $order->payment_verification_notes }}</div>
                                @endif
                                @if($order->payment_evidence_path)
                                    <div class="mt-2">
                                        <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="{{ route('admin.storefront.orders.payment-evidence', $order) }}">
                                            <i class="fas fa-paperclip ml-1"></i> ادائیگی کا ثبوت دیکھیں
                                        </a>
                                        <small class="text-muted mr-2">{{ $order->payment_evidence_original_name }} · {{ number_format(($order->payment_evidence_size ?? 0) / 1024, 1) }} KB</small>
                                    </div>
                                @else
                                    <div class="small text-muted mt-2">گاہک نے ادائیگی کا ثبوت منسلک نہیں کیا۔</div>
                                @endif
                                @if($order->payment_verification_status !== \App\Models\StorefrontOrder::VERIFICATION_VERIFIED)
                                    <form method="POST" action="{{ route('admin.storefront.orders.payment-verification', $order) }}" class="mt-3">
                                        @csrf @method('PATCH')
                                        <label for="order_payment_notes_{{ $order->id }}">تصدیقی نوٹ <small class="text-muted">(مسترد کرنے پر ضروری)</small></label>
                                        <textarea id="order_payment_notes_{{ $order->id }}" name="payment_verification_notes" class="form-control mb-2" rows="2" maxlength="1000"></textarea>
                                        <button name="decision" value="verified" class="btn btn-success">ادائیگی تصدیق کریں</button>
                                        <button name="decision" value="rejected" class="btn btn-outline-danger">دعویٰ مسترد کریں</button>
                                    </form>
                                @endif
                            </div>
                        @endif
                        <ul class="mt-2 mb-3">@foreach($order->items as $item)<li>{{ $item->item_name }} — {{ $item->color }}، {{ number_format($item->quantity,2) }} میٹر</li>@endforeach</ul>
                        @if($order->returns->isNotEmpty())
                            <div class="border rounded bg-light p-3 mt-3">
                                <strong>جزوی واپسی اور تبدیلی کی تاریخ</strong>
                                @foreach($order->returns as $return)
                                    @php $returnItem = $return->items->first(); @endphp
                                    <div class="small border-top pt-2 mt-2">
                                        <span dir="ltr">{{ $return->reference }}</span>
                                        · {{ \App\Models\StorefrontOrderReturn::types()[$return->type] ?? $return->type }}
                                        · {{ $returnItem?->orderItem?->item_name }}
                                        · {{ number_format((float) $returnItem?->quantity, 2) }} میٹر
                                        @if((float) $return->refund_amount > 0) · {{ \App\Support\PakistanCurrency::format($return->refund_amount) }}@endif
                                        @if($returnItem?->replacementColor) · متبادل رنگ: {{ $returnItem->replacementColor->color }}@endif
                                        · {{ $returnItem?->restocked ? 'اسٹاک میں واپس' : 'خراب / اسٹاک میں واپس نہیں' }}
                                        <span class="d-block text-muted">{{ $return->processed_at->format('d-m-Y h:i A') }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @if($order->status !== \App\Models\StorefrontOrder::STATUS_CANCELLED && $order->refunds->isEmpty())
                            <div class="mt-3">
                                @foreach($order->items as $item)
                                    @php
                                        $processedQuantity = (float) $item->returnItems->sum('quantity');
                                        $returnableQuantity = max(0, (float) $item->quantity - $processedQuantity);
                                    @endphp
                                    @if($returnableQuantity > 0)
                                        <details class="border rounded p-2 mb-2">
                                            <summary class="font-weight-bold" style="cursor:pointer">{{ $item->item_name }} — جزوی واپسی یا رنگ تبدیل کریں <small class="text-muted">(دستیاب {{ number_format($returnableQuantity, 2) }} میٹر)</small></summary>
                                            <form method="POST" action="{{ route('admin.storefront.orders.returns.store', $order) }}" class="mt-3"
                                                data-confirm="مقدار، رقم اور اسٹاک کی تفصیل دوبارہ دیکھ لی ہے؟" data-confirm-variant="warning">
                                                @csrf
                                                <input type="hidden" name="order_item_id" value="{{ $item->id }}">
                                                <div class="form-row">
                                                    <div class="col-md-3 mb-2"><label for="return_type_{{ $item->id }}">کارروائی</label><select id="return_type_{{ $item->id }}" name="return_type" class="form-control" required>@foreach(\App\Models\StorefrontOrderReturn::types() as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
                                                    <div class="col-md-2 mb-2"><label for="return_quantity_{{ $item->id }}">مقدار (میٹر)</label><input id="return_quantity_{{ $item->id }}" name="quantity" type="number" class="form-control" min="0.01" max="{{ $returnableQuantity }}" step="0.01" required></div>
                                                    <div class="col-md-3 mb-2"><label for="replacement_color_{{ $item->id }}">متبادل رنگ <small class="text-muted">(صرف تبدیلی)</small></label><select id="replacement_color_{{ $item->id }}" name="replacement_cloth_color_id" class="form-control"><option value="">منتخب کریں</option>@foreach($item->cloth?->colors ?? [] as $color)@if($color->id !== $item->cloth_color_id)<option value="{{ $color->id }}">{{ $color->color }} — {{ number_format((float)$color->length,2) }} میٹر</option>@endif @endforeach</select></div>
                                                    @if((float) $order->paid_amount > 0)
                                                        <div class="col-md-2 mb-2"><label for="partial_refund_method_{{ $item->id }}">رقم واپسی <small class="text-muted">(صرف واپسی)</small></label><select id="partial_refund_method_{{ $item->id }}" name="refund_method" class="form-control"><option value="">منتخب کریں</option>@foreach(\App\Models\StorefrontOrderRefund::methods() as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
                                                        <div class="col-md-2 mb-2"><label for="partial_refund_reference_{{ $item->id }}">ادائیگی حوالہ</label><input id="partial_refund_reference_{{ $item->id }}" name="refund_reference" class="form-control" maxlength="100" dir="ltr"></div>
                                                    @endif
                                                </div>
                                                <div class="form-row align-items-end">
                                                    <div class="col-md-7 mb-2"><label for="return_notes_{{ $item->id }}">اندرونی نوٹ <small class="text-muted">(اختیاری)</small></label><input id="return_notes_{{ $item->id }}" name="return_notes" class="form-control" maxlength="1000"></div>
                                                    <div class="col-md-3 mb-2"><div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="restock_{{ $item->id }}" name="restock" value="1" checked><label class="custom-control-label" for="restock_{{ $item->id }}">کپڑا قابلِ فروخت ہے، اسٹاک میں واپس کریں</label></div></div>
                                                    <div class="col-md-2 mb-2"><button class="btn btn-warning btn-block">درج کریں</button></div>
                                                </div>
                                            </form>
                                        </details>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                        @if($order->refunds->isNotEmpty())
                            @foreach($order->refunds as $refund)
                                <div class="alert alert-info mt-3 mb-0">
                                    <strong>رقم واپس کی گئی:</strong> {{ \App\Support\PakistanCurrency::format($refund->amount) }}
                                    · {{ \App\Models\StorefrontOrderRefund::methods()[$refund->method] ?? $refund->method }}
                                    · <span dir="ltr">{{ $refund->reference }}</span>
                                    @if($refund->external_reference) · بیرونی حوالہ: <span dir="ltr">{{ $refund->external_reference }}</span>@endif
                                    <small class="d-block mt-1">{{ $refund->refunded_at->format('d-m-Y h:i A') }}</small>
                                </div>
                            @endforeach
                        @endif
                        @if($order->status==='pending')
                            <div class="mt-3">
                                <form method="POST" action="{{ route('admin.storefront.orders.update',$order) }}" class="d-inline-block ml-2">@csrf @method('PATCH')<input type="hidden" name="status" value="complete"><button class="btn btn-success" @disabled(\App\Models\StorefrontOrder::requiresManualVerification($order->payment_method) && $order->payment_verification_status !== \App\Models\StorefrontOrder::VERIFICATION_VERIFIED)>مکمل کریں</button></form>
                                @if($order->returns->isNotEmpty())
                                    <div class="alert alert-light mt-2 mb-0">اس آرڈر پر جزوی واپسی یا تبدیلی موجود ہے، اس لیے مکمل منسوخی دستیاب نہیں۔ باقی مقدار الگ واپسی یا تبدیلی سے درج کریں۔</div>
                                @elseif((float) $order->paid_amount <= 0)
                                    <form method="POST" action="{{ route('admin.storefront.orders.update',$order) }}" class="d-inline-block" data-confirm="آرڈر منسوخ کرنے سے اسٹاک اور گاہک کا بقایا واپس ہوگا۔ جاری رکھیں؟">@csrf @method('PATCH')<input type="hidden" name="status" value="cancelled"><button class="btn btn-outline-danger">منسوخ کریں</button></form>
                                @else
                                    <div class="border border-danger rounded p-3 mt-3">
                                        <h2 class="h5">مکمل رقم واپس کر کے آرڈر منسوخ کریں</h2>
                                        <p class="mb-3">واپس کی جانے والی رقم: <strong>{{ \App\Support\PakistanCurrency::format($order->paid_amount) }}</strong>۔ یہ کارروائی اسٹاک واپس کرے گی اور ایک قابلِ جانچ مالی اندراج بنائے گی۔</p>
                                        <form method="POST" action="{{ route('admin.storefront.orders.update',$order) }}" data-confirm="کیا رقم واقعی گاہک کو واپس کر دی گئی ہے؟ یہ کارروائی دوبارہ نہیں کی جا سکے گی۔">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="cancelled">
                                            <div class="form-row">
                                                <div class="col-md-4 mb-2"><label for="refund_method_{{ $order->id }}">رقم واپسی کا طریقہ</label><select id="refund_method_{{ $order->id }}" name="refund_method" class="form-control" required>@foreach(\App\Models\StorefrontOrderRefund::methods() as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
                                                <div class="col-md-4 mb-2"><label for="refund_reference_{{ $order->id }}">واپسی کا بیرونی حوالہ <small class="text-muted">(نقد کے علاوہ ضروری)</small></label><input id="refund_reference_{{ $order->id }}" name="refund_reference" class="form-control" maxlength="100" dir="ltr"></div>
                                                <div class="col-md-4 mb-2"><label for="refund_notes_{{ $order->id }}">اندرونی نوٹ <small class="text-muted">(اختیاری)</small></label><input id="refund_notes_{{ $order->id }}" name="refund_notes" class="form-control" maxlength="1000"></div>
                                            </div>
                                            <button class="btn btn-danger">رقم واپس اور آرڈر منسوخ کریں</button>
                                        </form>
                                    </div>
                                @endif
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
