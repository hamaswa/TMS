@forelse ($customers as $customer)
    @php
        $isFamilyProfile = $customer->parent_id !== null;
        $accountCustomer = $customer->primaryCustomer ?? $customer;
        $currentBalance = (float) ($accountCustomer->current_balance ?? 0);
        $initial = function_exists('mb_substr') ? mb_substr(trim($customer->name), 0, 1) : substr(trim($customer->name), 0, 1);
        $statementUrl = $isFamilyProfile
            ? route('admin.customers.statement', ['id' => $accountCustomer->id, 'tab' => 'measurements', 'profile' => $customer->id])
            : route('admin.customers.statement', $accountCustomer);
        $orderUrl = $isFamilyProfile
            ? route('admin.order.create', ['id' => $accountCustomer->id, 'profile' => $customer->id])
            : route('admin.order.create', $accountCustomer);
    @endphp
    <tr data-customer-row="{{ $customer->id }}" @class(['family-profile-row' => $isFamilyProfile])>
        <td class="customer_serial customer-serial-cell" data-label="نمبر">{{ $customer->id }}</td>
        <td class="customer-name-cell" data-label="گاہک">
            <div class="customer-identity">
                <span class="customer-avatar">{{ $initial ?: 'گ' }}</span>
                <a href="{{ $statementUrl }}"
                    class="customer-link"
                    aria-label="{{ $customer->name }} کا پروفائل اور کھاتہ کھولیں">
                    {{ $customer->name }}
                    <small>{{ $isFamilyProfile ? $accountCustomer->name.' کا خاندانی ناپ' : 'پروفائل اور کھاتہ دیکھیں' }}</small>
                </a>
            </div>
        </td>
        <td data-label="فون نمبر"><span class="customer-phone">{{ $accountCustomer->phone_number1 ?: '—' }}</span></td>
        <td data-label="موجودہ بقایا">
            @if ($canViewBalances)
                <span class="customer-balance {{ $currentBalance > 0 ? 'is-due' : 'is-clear' }}" data-customer-balance="{{ $accountCustomer->id }}">
                    Rs. {{ number_format($currentBalance, 2) }}
                </span>
            @else
                <span class="text-muted">اجازت درکار ہے</span>
            @endif
        </td>
        <td class="customer-actions-cell" data-label="فوری کارروائیاں">
            <div class="customer-row-actions">
                @if ($canCreateTailoringOrder)
                    <a href="{{ $orderUrl }}" class="customer-row-action is-blue customer-primary-action">
                        <i class="fas fa-cut"></i> نیا آرڈر
                    </a>
                @endif
                <div class="dropdown customer-more-actions">
                    <button type="button" class="customer-row-action customer-overflow-button"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                        data-boundary="viewport"
                        aria-label="{{ $customer->name }} کی مزید کارروائیاں">
                        <i class="fas fa-ellipsis-v" aria-hidden="true"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-left text-right" dir="rtl">
                        <a href="{{ $statementUrl }}" class="dropdown-item">
                            <i class="fas fa-id-card"></i><span>پروفائل / کھاتہ کھولیں</span>
                        </a>
                        @if ($canCreateTailoringOrder)
                            <button type="button" class="dropdown-item getCustomer"
                                data-url="{{ route('admin.getCustomer') }}"
                                data-id="{{ $accountCustomer->id }}"
                                data-name="{{ $customer->name }}">
                                <i class="fas fa-history"></i><span>حالیہ آرڈر دیکھیں</span>
                            </button>
                        @endif
                        @if ($canManageMeasurements)
                            <a href="{{ url('admin/Customers/' . $customer->id . '/edit') . '?' . http_build_query(['return_customer' => $customer->id, 'return_search' => request('search', '')]) }}" class="dropdown-item">
                                <i class="fas fa-ruler-combined"></i><span>معلومات اور پیمائش تبدیل کریں</span>
                            </a>
                            <a href="{{ route('admin.Customers.create', ['parent' => $accountCustomer->id]) }}" class="dropdown-item">
                                <i class="fas fa-user-plus"></i><span>خاندان کا نیا ناپ شامل کریں</span>
                            </a>
                        @endif
                        @if ($canViewBalances)
                            <div class="dropdown-divider"></div>
                            <button type="button" class="dropdown-item customer_payment_paid"
                                aria-label="{{ $customer->name }} کی ادائیگی درج کریں"
                                data-customerid="{{ $accountCustomer->id }}"
                                data-toggle="modal" data-target="#myModalpayment">
                                <i class="fas fa-wallet"></i><span>ادائیگی درج کریں</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </td>
    </tr>
@empty
    <tr class="customer-directory-empty">
        <td colspan="5" class="text-center text-muted py-5">کوئی گاہک نہیں ملا۔ نام، فون نمبر یا گاہک نمبر سے دوبارہ تلاش کریں۔</td>
    </tr>
@endforelse
