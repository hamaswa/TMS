@php
    $selectedCustomer = $selectedCustomer ?? $customer;
    $returnSearch = request('search', '');
@endphp

<style>
    .customer-more-actions {
        position: relative;
        display: inline-block;
    }

    .customer-more-actions .customer-overflow-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        padding: 0;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #fff;
        color: #64748b;
        cursor: pointer;
        transition: all .2s ease;
    }

    .customer-more-actions .customer-overflow-button:hover,
    .customer-more-actions .customer-overflow-button:focus {
        background: #f1f5f9;
        border-color: #cbd5e1;
        color: #1e293b;
        outline: none;
    }

    .customer-more-actions .dropdown-menu {
        min-width: 235px;
        margin-top: 6px;
        padding: 6px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
        box-shadow: 0 10px 25px rgba(15, 23, 42, .12);
        z-index: 1050;
    }

    .customer-more-actions .dropdown-item,
    .customer-more-actions .customer-new-order-button {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        padding: 10px 12px;
        border: none;
        border-radius: 6px;
        background: transparent;
        color: #334155;
        font-size: 13px;
        line-height: 1.5;
        text-align: right;
        text-decoration: none;
        white-space: normal;
        cursor: pointer;
        transition: background .15s ease, color .15s ease;
    }

    .customer-more-actions .dropdown-item:hover,
    .customer-more-actions .dropdown-item:focus,
    .customer-more-actions .customer-new-order-button:hover,
    .customer-more-actions .customer-new-order-button:focus {
        background: #f1f5f9;
        color: #0f172a;
        text-decoration: none;
        outline: none;
    }

    .customer-more-actions .dropdown-item i,
    .customer-more-actions .customer-new-order-button i {
        width: 17px;
        flex-shrink: 0;
        color: #64748b;
        text-align: center;
    }

    .customer-more-actions .dropdown-item:hover i,
    .customer-more-actions .customer-new-order-button:hover i {
        color: #2563eb;
    }

    .customer-more-actions .dropdown-divider {
        margin: 5px 0;
        border-top: 1px solid #e2e8f0;
    }
</style>

<div class="dropdown customer-more-actions">
    <button type="button" class="customer-row-action customer-overflow-button" data-toggle="dropdown" aria-haspopup="true"
        aria-expanded="false" data-boundary="viewport" aria-label="{{ $selectedCustomer->name }} کی مزید کارروائیاں">
        <i class="fas fa-ellipsis-v" aria-hidden="true"></i>
    </button>

    <div class="dropdown-menu dropdown-menu-left text-right" dir="rtl">
        {{-- Create a new order --}}
        <form method="POST" action="{{ route('admin.counter-orders.store') }}" class="m-0">
            @csrf

            <input type="hidden" name="customer_id" value="{{ $customer->id }}">
            <input type="hidden" name="profile_id" value="{{ $selectedCustomer->id }}">

            <button type="submit" class="customer-new-order-button">
                <i class="fas fa-plus"></i>
                <span>نیا آرڈر</span>
            </button>
        </form>

        {{-- Customer profile and account --}}
        <a href="{{ route('admin.customers.statement', $customer) }}" class="dropdown-item">
            <i class="fas fa-id-card"></i>
            <span>پروفائل / کھاتہ کھولیں</span>
        </a>

        <a href="{{ route('admin.Customers.index') }}" class="dropdown-item">
            <i class="fas fa-arrow-right"></i>
            گاہکوں کی فہرست
        </a>

        {{-- Recent orders --}}
        @if ($canViewTailoringOrders)
            <a href="{{ route('admin.customer.orders', $customer) }}" class="dropdown-item">
                <i class="fas fa-history"></i>
                <span>حالیہ آرڈر دیکھیں</span>
            </a>
        @endif

        {{-- Edit customer or selected measurement profile --}}
        @if ($canManageMeasurements)
            <a href="{{ url('admin/Customers/' . $selectedCustomer->id . '/edit') .
                '?' .
                http_build_query([
                    'return_customer' => $selectedCustomer->id,
                    'return_search' => $returnSearch,
                ]) }}"
                class="dropdown-item">
                <i class="fas fa-ruler-combined"></i>
                <span>معلومات اور پیمائش تبدیل کریں</span>
            </a>

            {{-- Add a family measurement profile --}}
            <a href="{{ route('admin.Customers.create', ['parent' => $customer->id]) }}" class="dropdown-item">
                <i class="fas fa-user-plus"></i>
                <span>خاندان کا نیا ناپ شامل کریں</span>
            </a>
        @endif

        {{-- Record payment --}}
        @if ($canViewBalances)
            <div class="dropdown-divider"></div>

            <button type="button" class="dropdown-item customer_payment_paid"
                aria-label="{{ $selectedCustomer->name }} کی ادائیگی درج کریں" data-customerid="{{ $customer->id }}"
                data-toggle="modal" data-target="#myModalpayment">
                <i class="fas fa-wallet"></i>
                <span>ادائیگی درج کریں</span>
            </button>
        @endif
    </div>
</div>
