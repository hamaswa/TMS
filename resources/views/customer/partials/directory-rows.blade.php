@forelse ($customers as $customer)
    @php
        $currentBalance = (float) ($customer->current_balance ?? 0);
        $initial = function_exists('mb_substr') ? mb_substr(trim($customer->name), 0, 1) : substr(trim($customer->name), 0, 1);
        $familyProfiles = $customer->familyMeasurementProfiles;
        $familySearchText = $familyProfiles->pluck('name')->implode(' ');
    @endphp
    <tr data-customer-row="{{ $customer->id }}" @class(['customer-account-row', 'has-family' => $familyProfiles->isNotEmpty()])>
        <td class="customer_serial customer-serial-cell" data-label="نمبر" data-order="{{ $customer->id }}">{{ $customer->serial_number ?? $customer->id }}</td>
        <td class="customer-name-cell" data-label="گاہک">
            <div class="customer-identity">
                @if($familyProfiles->isNotEmpty())
                    <button type="button" class="customer-family-toggle" aria-expanded="false" aria-label="{{ $customer->name }} کے خاندانی افراد دکھائیں">
                        <i class="fas fa-chevron-left" aria-hidden="true"></i>
                    </button>
                @endif
                <span class="customer-avatar">{{ $initial ?: 'گ' }}</span>
                <a href="{{ route('admin.customers.statement', $customer) }}"
                    class="customer-link"
                    aria-label="{{ $customer->name }} کا پروفائل اور کھاتہ کھولیں">
                    {{ $customer->name }}
                    <small>پروفائل اور کھاتہ دیکھیں @if($familyProfiles->isNotEmpty()) · {{ $familyProfiles->count() }} خاندانی فرد @endif</small>
                </a>
            </div>
            @if($familyProfiles->isNotEmpty())
                <span class="sr-only customer-family-search-text">{{ $familySearchText }}</span>
                <template class="customer-family-template">
                    <div class="customer-family-rows" dir="rtl">
                        @foreach($familyProfiles as $profile)
                            <div class="customer-family-row" data-family-profile="{{ $profile->id }}">
                                <div class="customer-family-cell customer-family-sequence"><span><i class="fas fa-level-up-alt fa-rotate-90"></i></span></div>
                                <div class="customer-family-cell customer-family-identity">
                                    <span class="customer-family-avatar">{{ function_exists('mb_substr') ? mb_substr(trim($profile->name), 0, 1) : substr(trim($profile->name), 0, 1) }}</span>
                                    <div>
                                        <strong>{{ $profile->name }}</strong>
                                        <small>{{ $profile->measurementTemplate?->name ?: 'پیمائش پروفائل' }}</small>
                                    </div>
                                </div>
                                <div class="customer-family-cell customer-family-relation"><span>خاندانی فرد</span><small>مشترکہ فون نمبر</small></div>
                                <div class="customer-family-cell customer-family-orders">
                                    <strong>Rs. {{ number_format((float) ($profile->orders_total ?? 0), 2) }}</strong>
                                    <small>{{ number_format((int) ($profile->profile_orders_count ?? 0)) }} آرڈر کی کل رقم · بقایا نہیں</small>
                                </div>
                                <div class="customer-family-cell customer-family-actions">
                                    @if($canViewTailoringOrders)
                                        <a class="customer-row-action" href="{{ route('admin.customer.orders', $profile) }}" aria-label="{{ $profile->name }} کے حالیہ آرڈر دیکھیں">
                                            <i class="fas fa-history"></i> حالیہ آرڈرز
                                        </a>
                                    @endif
                                    @if($canCreateTailoringOrder)
                                        <form method="POST" action="{{ route('admin.counter-orders.store') }}" class="m-0">
                                            @csrf
                                            <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                                            <input type="hidden" name="profile_id" value="{{ $profile->id }}">
                                            <button type="submit" class="customer-row-action is-blue"><i class="fas fa-plus"></i> نیا آرڈر</button>
                                        </form>
                                    @endif
                                    <a class="customer-row-action" href="{{ route('admin.customers.statement', ['id' => $customer->id, 'tab' => 'measurements', 'profile' => $profile->id]) }}" aria-label="{{ $profile->name }} کا ناپ دیکھیں">
                                        <i class="fas fa-ruler-combined"></i> ناپ
                                    </a>
                                    @if($canManageMeasurements)
                                        <a class="customer-row-action" href="{{ url('admin/Customers/' . $profile->id . '/edit') . '?' . http_build_query(['return_customer' => $profile->id, 'return_search' => request('search', '')]) }}" aria-label="{{ $profile->name }} کی پیمائش تبدیل کریں">
                                            <i class="fas fa-edit"></i> تبدیل
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </template>
            @endif
        </td>
        <td data-label="فون نمبر"><span class="customer-phone">{{ $customer->phone_number1 ?: '—' }}</span></td>
        <td data-label="بقایا">
            @if ($canViewBalances)
                <span class="customer-balance {{ $currentBalance > 0 ? 'is-due' : 'is-clear' }}" data-customer-balance="{{ $customer->id }}">
                    Rs. {{ number_format($currentBalance, 2) }}
                </span>
                @if($familyProfiles->isNotEmpty())<small class="customer-shared-balance-label">تمام خاندانی آرڈرز سمیت</small>@endif
            @else
                <span class="text-muted">اجازت درکار ہے</span>
            @endif
        </td>
        <td class="customer-actions-cell" data-label="فوری کارروائیاں">
            <div class="customer-row-actions">
                @if ($canCreateTailoringOrder)
                    <form method="POST" action="{{ route('admin.counter-orders.store') }}" class="m-0">
                        @csrf
                        <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                        <input type="hidden" name="profile_id" value="{{ $customer->id }}">
                        <button type="submit" class="customer-row-action is-blue customer-primary-action">
                            <i class="fas fa-plus"></i> نیا آرڈر
                        </button>
                    </form>
                @endif
                <div class="dropdown customer-more-actions">
                    <button type="button" class="customer-row-action customer-overflow-button"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                        data-boundary="viewport" aria-label="{{ $customer->name }} کی مزید کارروائیاں">
                        <i class="fas fa-ellipsis-v" aria-hidden="true"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-left text-right" dir="rtl">
                        <a href="{{ route('admin.customers.statement', $customer) }}" class="dropdown-item">
                            <i class="fas fa-id-card"></i><span>پروفائل / کھاتہ کھولیں</span>
                        </a>
                        @if($canViewTailoringOrders)
                            <a href="{{ route('admin.customer.orders', $customer) }}" class="dropdown-item">
                                <i class="fas fa-history"></i><span>حالیہ آرڈر دیکھیں</span>
                            </a>
                        @endif
                        @if($canManageMeasurements)
                            <a href="{{ url('admin/Customers/' . $customer->id . '/edit') . '?' . http_build_query(['return_customer' => $customer->id, 'return_search' => request('search', '')]) }}" class="dropdown-item">
                                <i class="fas fa-ruler-combined"></i><span>معلومات اور پیمائش تبدیل کریں</span>
                            </a>
                            <a href="{{ route('admin.Customers.create', ['parent' => $customer->id]) }}" class="dropdown-item">
                                <i class="fas fa-user-plus"></i><span>خاندان کا نیا ناپ شامل کریں</span>
                            </a>
                        @endif
                        @if($canViewBalances)
                            <div class="dropdown-divider"></div>
                            <button type="button" class="dropdown-item customer_payment_paid"
                                aria-label="{{ $customer->name }} کی ادائیگی درج کریں"
                                data-customerid="{{ $customer->id }}" data-toggle="modal" data-target="#myModalpayment">
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
