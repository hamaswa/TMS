@extends('main')

@section('content')
<style>
    .co-start{direction:rtl;padding:30px 16px 55px;color:#18324f}.co-start__shell{max-width:820px;margin:auto}.co-start__head{margin-bottom:20px;text-align:right}.co-start__head h1{margin:0 0 7px;font-size:1.8rem;font-weight:800}.co-start__head p{margin:0;color:#728197}.co-card{padding:26px;border:1px solid #e0e7f0;border-radius:17px;background:#fff;box-shadow:0 12px 35px rgba(20,50,85,.07)}.co-label{display:block;margin-bottom:8px;font-weight:800}.co-control{width:100%;min-height:48px;padding:10px 13px;border:1px solid #d4deeb;border-radius:10px;background:#fff}.co-actions{display:flex;justify-content:flex-start;gap:10px;margin-top:22px}.co-primary{min-height:45px;padding:9px 20px;border:0;border-radius:10px;color:#fff!important;background:#1769e0;font-weight:800}.co-secondary{min-height:45px;padding:9px 18px;border:1px solid #d4deeb;border-radius:10px;color:#42566f!important;background:#fff;font-weight:700}.co-note{margin-top:16px;padding:12px 14px;border-radius:10px;color:#45617e;background:#f4f8fc;font-size:.86rem}
    .co-open{margin-bottom:16px;padding:18px 20px;border:1px solid #cfe0f7;border-radius:14px;background:#f5f9ff}.co-open h2{margin:0 0 5px;font-size:1rem;font-weight:850}.co-open p{margin:0 0 12px;color:#657b94;font-size:.82rem}.co-open__row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 0;border-top:1px solid #dce8f6}.co-open__row:first-of-type{border-top:0}.co-open__row small{display:block;color:#718096}
    .co-customer-head{display:flex;align-items:center;justify-content:space-between;gap:12px}.co-quick{border:0;color:#1769e0;background:transparent;font-weight:800}.co-picker{position:relative}.co-picker__selected{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:8px;padding:10px 13px;border-radius:10px;color:#14532d;background:#ecfdf3}.co-picker__selected[hidden]{display:none}.co-picker__selected button{border:0;color:#64748b;background:transparent}.co-picker__results{position:absolute;z-index:30;top:100%;right:0;left:0;max-height:265px;margin-top:5px;overflow:auto;border:1px solid #d4deeb;border-radius:11px;background:#fff;box-shadow:0 15px 35px rgba(20,50,85,.14)}.co-picker__results[hidden]{display:none}.co-customer-result{display:flex;width:100%;align-items:center;justify-content:space-between;gap:14px;padding:11px 13px;border:0;border-bottom:1px solid #edf2f7;text-align:right;background:#fff}.co-customer-result:hover,.co-customer-result:focus{background:#f4f8ff}.co-customer-result small{direction:ltr;color:#64748b}.co-picker__empty{padding:14px;color:#718096;text-align:center}.co-modal-error{display:none;margin-bottom:12px}.co-modal-error.is-visible{display:block}
</style>
<section class="main-content co-start">
    <div class="co-start__shell">
        <header class="co-start__head"><h1>نیا آرڈر</h1><p>@if($canAddCloth && $canAddTailoring)ایک آرڈر شروع کریں، پھر اسی میں کپڑا اور سلائی ضرورت کے مطابق شامل کریں۔@elseif($canAddCloth)ایک آرڈر شروع کریں، پھر گاہک کے لیے فروخت ہونے والا کپڑا شامل کریں۔@else ایک آرڈر شروع کریں، پھر گاہک کا سلائی آرڈر شامل کریں۔@endif</p></header>
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        @if($openOrders->isNotEmpty())<section class="co-open"><h2>اس گاہک کے کھلے آرڈرز</h2><p>نیا آرڈر بنانے سے پہلے دیکھیں کہ موجودہ آرڈر میں ہی آئٹم شامل کرنا تو بہتر نہیں۔</p>@foreach($openOrders as $openOrder)<div class="co-open__row"><div><strong>{{ $openOrder->displayNumber() }}</strong><small>@if($openOrder->serial_number)حوالہ {{ $openOrder->reference }} · @endif{{ $openOrder->draft_items_count }} نئے · {{ $openOrder->confirmed_items_count }} تصدیق شدہ آئٹمز · Rs. {{ number_format((float)$openOrder->balance_amount,2) }} بقایا</small></div><a class="co-secondary" href="{{ route('admin.counter-orders.edit',['counterOrder'=>$openOrder,'profile'=>$selectedProfileId]) }}">آرڈر کھولیں</a></div>@endforeach</section>@endif
        <div class="co-card">
            <form method="POST" action="{{ route('admin.counter-orders.store') }}">@csrf
                <input type="hidden" name="profile_id" value="{{ old('profile_id',$selectedProfileId) }}">
                <div class="form-group co-picker" id="co-customer-picker">
                    <div class="co-customer-head"><label class="co-label mb-0" for="customer_search">گاہک</label><button class="co-quick" type="button" data-toggle="modal" data-target="#quickCustomerModal"><i class="fas fa-user-plus ml-1"></i> نیا / واک اِن گاہک</button></div>
                    <input type="hidden" id="customer_id" name="customer_id" value="{{ old('customer_id',$selectedCustomer?->id) }}" required>
                    <input class="co-control" id="customer_search" type="search" autocomplete="off" placeholder="نام، فون یا گاہک نمبر سے تلاش کریں" aria-controls="customer_search_results" aria-autocomplete="list">
                    <div id="customer_selected" class="co-picker__selected" @if(!$selectedCustomer) hidden @endif><strong id="customer_selected_text">@if($selectedCustomer){{ $selectedCustomer->name }} · {{ $selectedCustomer->phone_number1 }}@endif</strong><button type="button" id="customer_clear" aria-label="منتخب گاہک ہٹائیں"><i class="fas fa-times"></i></button></div>
                    <div id="customer_search_results" class="co-picker__results" role="listbox" hidden></div>
                </div>
                <div class="form-group mb-0"><label class="co-label" for="note">ابتدائی نوٹ <small class="text-muted">(اختیاری)</small></label><textarea class="co-control" id="note" name="note" rows="3" maxlength="2000" placeholder="مثلاً تقریب کی تاریخ یا خاص ہدایت">{{ old('note') }}</textarea></div>
                <div class="co-note"><i class="fas fa-info-circle ml-1"></i> ابھی کوئی رقم یا اسٹاک تبدیل نہیں ہوگا۔ آئٹمز شامل کرنے کے بعد ایڈمن ڈیسک سے تصدیق ہوگی۔</div>
                <div class="co-actions"><button class="co-primary" type="submit"><i class="fas fa-arrow-left ml-1"></i> آرڈر شروع کریں</button><a class="co-secondary" href="{{ route('admin.Customers.index') }}">منسوخ</a></div>
            </form>
        </div>
    </div>
</section>

<div class="modal fade" id="quickCustomerModal" tabindex="-1" role="dialog" aria-labelledby="quickCustomerTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content" dir="rtl">
        <div class="modal-header"><h2 class="modal-title h5 font-weight-bold" id="quickCustomerTitle">نیا گاہک شامل کریں</h2><button type="button" class="close ml-0" data-dismiss="modal" aria-label="بند کریں"><span aria-hidden="true">&times;</span></button></div>
        <form id="quick_customer_form"><div class="modal-body">
            <div id="quick_customer_error" class="alert alert-danger co-modal-error"></div>
            <div class="form-group"><label class="co-label" for="quick_customer_name">نام</label><input class="co-control" id="quick_customer_name" name="name" maxlength="255" required></div>
            <div class="form-group mb-0"><label class="co-label" for="quick_customer_phone">موبائل نمبر</label><input class="co-control" id="quick_customer_phone" name="phone" inputmode="tel" placeholder="03001234567" maxlength="50" required></div>
        </div><div class="modal-footer"><button class="co-secondary" type="button" data-dismiss="modal">منسوخ</button><button class="co-primary" type="submit">محفوظ اور منتخب کریں</button></div></form>
    </div></div>
</div>
@endsection

@php
    $customerPickerOptions = $customers->map(function ($customer) {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone_number1' => $customer->phone_number1,
            'serial_number' => $customer->serial_number,
        ];
    })->values();
@endphp
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('customer_search');
    const idInput = document.getElementById('customer_id');
    const results = document.getElementById('customer_search_results');
    const selected = document.getElementById('customer_selected');
    const selectedText = document.getElementById('customer_selected_text');
    const initialCustomers = @json($customerPickerOptions);
    let timer;

    function choose(customer) {
        idInput.value = customer.id;
        selectedText.textContent = customer.name + ' · ' + (customer.phone_number1 || 'فون موجود نہیں');
        selected.hidden = false;
        results.hidden = true;
        input.value = '';
    }
    function render(customers) {
        results.innerHTML = '';
        if (!customers.length) {
            results.innerHTML = '<div class="co-picker__empty">کوئی گاہک نہیں ملا۔ نیا گاہک شامل کریں۔</div>';
        } else {
            customers.forEach(function (customer) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'co-customer-result';
                button.setAttribute('role', 'option');
                const name = document.createElement('strong');
                name.textContent = customer.name;
                const meta = document.createElement('small');
                meta.textContent = (customer.phone_number1 || 'فون موجود نہیں') + (customer.serial_number ? ' · #' + customer.serial_number : '');
                button.append(name, meta);
                button.addEventListener('click', function () { choose(customer); });
                results.appendChild(button);
            });
        }
        results.hidden = false;
    }
    input.addEventListener('focus', function () { if (!input.value.trim()) render(initialCustomers); });
    input.addEventListener('input', function () {
        idInput.value = '';
        selected.hidden = true;
        clearTimeout(timer);
        timer = setTimeout(async function () {
            const response = await fetch(@json(route('admin.counter-orders.customers.search')) + '?q=' + encodeURIComponent(input.value.trim()), {headers:{'Accept':'application/json'}});
            if (response.ok) render((await response.json()).customers || []);
        }, 250);
    });
    document.getElementById('customer_clear').addEventListener('click', function () { idInput.value=''; selected.hidden=true; input.focus(); render(initialCustomers); });
    document.addEventListener('click', function (event) { if (!document.getElementById('co-customer-picker').contains(event.target)) results.hidden=true; });

    document.getElementById('quick_customer_form').addEventListener('submit', async function (event) {
        event.preventDefault();
        const form = event.currentTarget;
        const error = document.getElementById('quick_customer_error');
        error.classList.remove('is-visible');
        const response = await fetch(@json(route('admin.counter-orders.customers.store')), {
            method:'POST', headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':@json(csrf_token())},
            body:JSON.stringify({name:form.name.value,phone:form.phone.value})
        });
        const payload = await response.json();
        if (!response.ok) {
            error.textContent = Object.values(payload.errors || {}).flat()[0] || 'گاہک محفوظ نہیں ہو سکا۔';
            error.classList.add('is-visible');
            return;
        }
        choose(payload.customer);
        initialCustomers.unshift(payload.customer);
        form.reset();
        window.jQuery && window.jQuery('#quickCustomerModal').modal('hide');
    });
});
</script>
@endpush
