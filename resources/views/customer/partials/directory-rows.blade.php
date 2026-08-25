@forelse ($customers as $customer)
    @php
        $currentBalance = (float) ($customer->current_balance ?? 0);
        $initial = function_exists('mb_substr') ? mb_substr(trim($customer->name), 0, 1) : substr(trim($customer->name), 0, 1);
    @endphp
    <tr data-customer-row="{{ $customer->id }}">
        <td class="customer_serial customer-serial-cell" data-label="نمبر">{{ $customer->id }}</td>
        <td class="customer-name-cell" data-label="گاہک">
            <div class="customer-identity">
                <span class="customer-avatar">{{ $initial ?: 'گ' }}</span>
                <button type="button"
                    class="getCustomer customer-link"
                    data-url="{{ url('admin/getCustomer') }}"
                    data-id="{{ $customer->id }}"
                    data-name="{{ $customer->name }}"
                    aria-label="{{ $customer->name }} کے آرڈر دیکھیں">
                    {{ $customer->name }}
                    <small>آرڈر کی تفصیل دیکھیں</small>
                </button>
            </div>
        </td>
        <td data-label="فون نمبر"><span class="customer-phone">{{ $customer->phone_number1 ?: '—' }}</span></td>
        <td data-label="موجودہ بقایا">
            @if ($canViewBalances)
                <span class="customer-balance {{ $currentBalance > 0 ? 'is-due' : 'is-clear' }}" data-customer-balance="{{ $customer->id }}">
                    Rs. {{ number_format($currentBalance, 2) }}
                </span>
            @else
                <span class="text-muted">اجازت درکار ہے</span>
            @endif
        </td>
        <td class="customer-actions-cell" data-label="فوری کارروائیاں">
            <div class="customer-row-actions">
                <a href="{{ route('admin.customers.statement', $customer) }}" class="customer-row-action is-blue">
                    <i class="fas fa-id-card"></i> پروفائل / کھاتہ
                </a>
                <a href="{{ url('admin/order', ['id' => $customer->id]) }}" class="customer-row-action is-green">
                    <i class="fas fa-cut"></i> نیا آرڈر
                </a>
                <a href="{{ url('admin/Customers/' . $customer->id . '/edit') . '?' . http_build_query(['return_customer' => $customer->id, 'return_search' => request('search', '')]) }}" class="customer-row-action">
                    <i class="fas fa-ruler-combined"></i> معلومات / پیمائش
                </a>
                @if ($canViewBalances)
                    <button type="button"
                        class="customer-row-action customer_payment_paid"
                        aria-label="{{ $customer->name }} کی ادائیگی درج کریں"
                        data-customerid="{{ $customer->id }}"
                        data-toggle="modal"
                        data-target="#myModalpayment">
                        <i class="fas fa-wallet"></i> ادائیگی
                    </button>
                @endif
            </div>
        </td>
    </tr>
@empty
    <tr class="customer-directory-empty">
        <td colspan="5" class="text-center text-muted py-5">کوئی گاہک نہیں ملا۔ نام، فون نمبر یا گاہک نمبر سے دوبارہ تلاش کریں۔</td>
    </tr>
@endforelse
