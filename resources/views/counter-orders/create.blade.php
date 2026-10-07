@extends('main')

@section('content')
<style>
    .co-start{direction:rtl;padding:30px 16px 55px;color:#18324f}.co-start__shell{max-width:820px;margin:auto}.co-start__head{margin-bottom:20px;text-align:right}.co-start__head h1{margin:0 0 7px;font-size:1.8rem;font-weight:800}.co-start__head p{margin:0;color:#728197}.co-card{padding:26px;border:1px solid #e0e7f0;border-radius:17px;background:#fff;box-shadow:0 12px 35px rgba(20,50,85,.07)}.co-label{display:block;margin-bottom:8px;font-weight:800}.co-control{width:100%;min-height:48px;padding:10px 13px;border:1px solid #d4deeb;border-radius:10px;background:#fff}.co-actions{display:flex;justify-content:flex-start;gap:10px;margin-top:22px}.co-primary{min-height:45px;padding:9px 20px;border:0;border-radius:10px;color:#fff!important;background:#1769e0;font-weight:800}.co-secondary{min-height:45px;padding:9px 18px;border:1px solid #d4deeb;border-radius:10px;color:#42566f!important;background:#fff;font-weight:700}.co-note{margin-top:16px;padding:12px 14px;border-radius:10px;color:#45617e;background:#f4f8fc;font-size:.86rem}
    .co-open{margin-bottom:16px;padding:18px 20px;border:1px solid #cfe0f7;border-radius:14px;background:#f5f9ff}.co-open h2{margin:0 0 5px;font-size:1rem;font-weight:850}.co-open p{margin:0 0 12px;color:#657b94;font-size:.82rem}.co-open__row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 0;border-top:1px solid #dce8f6}.co-open__row:first-of-type{border-top:0}.co-open__row small{display:block;color:#718096}
</style>
<section class="main-content co-start">
    <div class="co-start__shell">
        <header class="co-start__head"><h1>نیا آرڈر</h1><p>@if($canAddCloth && $canAddTailoring)ایک آرڈر شروع کریں، پھر اسی میں کپڑا اور سلائی ضرورت کے مطابق شامل کریں۔@elseif($canAddCloth)ایک آرڈر شروع کریں، پھر گاہک کے لیے فروخت ہونے والا کپڑا شامل کریں۔@else ایک آرڈر شروع کریں، پھر گاہک کا سلائی آرڈر شامل کریں۔@endif</p></header>
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        @if($openOrders->isNotEmpty())<section class="co-open"><h2>اس گاہک کے کھلے آرڈرز</h2><p>نیا آرڈر بنانے سے پہلے دیکھیں کہ موجودہ آرڈر میں ہی آئٹم شامل کرنا تو بہتر نہیں۔</p>@foreach($openOrders as $openOrder)<div class="co-open__row"><div><strong>{{ $openOrder->reference }}</strong><small>{{ $openOrder->draft_items_count }} نئے · {{ $openOrder->confirmed_items_count }} تصدیق شدہ آئٹمز · Rs. {{ number_format((float)$openOrder->balance_amount,2) }} بقایا</small></div><a class="co-secondary" href="{{ route('admin.counter-orders.edit',['counterOrder'=>$openOrder,'profile'=>$selectedProfileId]) }}">آرڈر کھولیں</a></div>@endforeach</section>@endif
        <div class="co-card">
            <form method="POST" action="{{ route('admin.counter-orders.store') }}">@csrf
                <input type="hidden" name="profile_id" value="{{ old('profile_id',$selectedProfileId) }}">
                <div class="form-group"><label class="co-label" for="customer_id">گاہک</label><select class="co-control" id="customer_id" name="customer_id" required><option value="">گاہک منتخب کریں</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" @selected((int)old('customer_id',$selectedCustomer?->id)===$customer->id)>{{ $customer->name }} · {{ $customer->phone_number1 ?: 'فون موجود نہیں' }}</option>@endforeach</select></div>
                <div class="form-group mb-0"><label class="co-label" for="note">ابتدائی نوٹ <small class="text-muted">(اختیاری)</small></label><textarea class="co-control" id="note" name="note" rows="3" maxlength="2000" placeholder="مثلاً تقریب کی تاریخ یا خاص ہدایت">{{ old('note') }}</textarea></div>
                <div class="co-note"><i class="fas fa-info-circle ml-1"></i> ابھی کوئی رقم یا اسٹاک تبدیل نہیں ہوگا۔ آئٹمز شامل کرنے کے بعد ایڈمن ڈیسک سے تصدیق ہوگی۔</div>
                <div class="co-actions"><button class="co-primary" type="submit"><i class="fas fa-arrow-left ml-1"></i> آرڈر شروع کریں</button><a class="co-secondary" href="{{ route('admin.Customers.index') }}">منسوخ</a></div>
            </form>
        </div>
    </div>
</section>
@endsection
