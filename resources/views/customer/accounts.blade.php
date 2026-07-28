@extends('main')

@section('content')
<section class="main-content">
    <div class="container py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">گاہکوں کے مشترکہ کھاتے</h1>
                <p class="text-muted mb-0">ٹیلرنگ اور کپڑے کی دکان کا مجموعی بقایا اور ادائیگی ایک جگہ دیکھیں۔</p>
            </div>
            @if(Auth::user()->hasBusinessPermission('finance.view'))
                <a class="btn btn-outline-primary mt-2 mt-md-0" href="{{ route('admin.financial-reports.index') }}">
                    <i class="fas fa-chart-line ml-1" aria-hidden="true"></i> مالیاتی ڈیش بورڈ
                </a>
            @endif
        </div>

        @if(Session::has('insert'))
            <div class="alert alert-success" role="alert">{{ Session::get('insert') }}</div>
        @endif
        @if(Session::has('balanceError'))
            <div class="alert alert-danger" role="alert">{{ Session::get('balanceError') }}</div>
        @endif

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 js-sortable-table">
                        <thead>
                            <tr>
                                <th scope="col">گاہک</th>
                                <th scope="col">فون نمبر</th>
                                <th scope="col">مجموعی بقایا</th>
                                <th scope="col" class="no-sort">عمل</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customers as $customer)
                                <tr>
                                    <td class="font-weight-bold">{{ $customer->name }}</td>
                                    <td dir="ltr">{{ $customer->phone_number1 }}</td>
                                    <td>روپے {{ number_format((float) $customer->current_balance, 2) }}</td>
                                    <td>
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.customers.statement', $customer) }}">
                                            پروفائل / کھاتہ
                                        </a>
                                        @if((float) $customer->current_balance > 0)
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-primary"
                                                data-toggle="modal"
                                                data-target="#customerPaymentModal"
                                                data-customer-id="{{ $customer->id }}"
                                                data-customer-name="{{ $customer->name }}"
                                                data-customer-balance="{{ (float) $customer->current_balance }}"
                                            >
                                                ادائیگی درج کریں
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-5">ابھی کوئی گاہک موجود نہیں۔</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="customerPaymentModal" tabindex="-1" role="dialog" aria-labelledby="customerPaymentTitle" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.customer-payments.store') }}">
                @csrf
                <input type="hidden" name="customer_id" id="account_customer_id">
                <input type="hidden" name="return_to_accounts" value="1">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="customerPaymentTitle">گاہک کی ادائیگی درج کریں</h2>
                    <button type="button" class="close ml-0" data-dismiss="modal" aria-label="بند کریں"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">
                        <strong id="account_customer_name"></strong>
                        <span class="text-muted"> — موجودہ بقایا: روپے <span id="account_customer_balance"></span></span>
                    </p>
                    <div class="form-group">
                        <label for="account_payment_amount">وصول شدہ رقم</label>
                        <input id="account_payment_amount" type="number" name="DirectPayment" min="0.01" step="0.01" class="form-control" required>
                    </div>
                    @include('components.payment-method-fields', ['prefix' => 'account_payment'])
                    <div class="form-group">
                        <label for="account_payment_date">ادائیگی کی تاریخ</label>
                        <input id="account_payment_date" type="date" name="paid_on" value="{{ now()->toDateString() }}" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="account_payment_comment">نوٹ <small class="text-muted">(اختیاری)</small></label>
                        <textarea id="account_payment_comment" name="comment" rows="3" maxlength="1000" class="form-control" placeholder="مثلاً نقد ادائیگی"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">منسوخ کریں</button>
                    <button type="submit" class="btn btn-primary">ادائیگی محفوظ کریں</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    $('#customerPaymentModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var balance = Number(button.data('customer-balance') || 0);
        $('#account_customer_id').val(button.data('customer-id'));
        $('#account_customer_name').text(button.data('customer-name'));
        $('#account_customer_balance').text(balance.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        $('#account_payment_amount').attr('max', balance).val('');
        $('#account_payment_comment').val('');
    });
</script>
@endsection
