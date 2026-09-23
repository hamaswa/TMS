@extends('main')

@section('content')
<style>
    .customer-orders-page{min-height:calc(100vh - 70px);padding:28px 0;background:#f3f6fa;direction:rtl}
    .customer-orders-shell{width:min(100% - 32px,1720px);margin:0 auto}
    .customer-orders-hero{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:24px 28px;margin-bottom:20px;color:#fff;background:linear-gradient(135deg,#102a43,#1769aa);border-radius:18px;box-shadow:0 14px 32px rgba(16,42,67,.16)}
    .customer-orders-hero h1{margin:0 0 7px;color:#fff;font-size:1.65rem;font-weight:900}.customer-orders-hero p{margin:0;color:rgba(255,255,255,.78)}
    .customer-orders-hero__meta{display:flex;align-items:center;gap:9px;flex-wrap:wrap;margin-top:12px}.customer-orders-hero__meta span{padding:6px 10px;background:rgba(255,255,255,.12);border-radius:8px;font-size:.85rem;font-weight:700}
    .customer-orders-back{display:inline-flex;align-items:center;gap:7px;padding:10px 14px;color:#163a5f;background:#fff;border-radius:10px;font-weight:800;white-space:nowrap}.customer-orders-back:hover{color:#1769aa;text-decoration:none}
    .customer-orders-alert{display:flex;align-items:center;gap:9px;padding:13px 16px;margin-bottom:16px;color:#146c43;background:#eaf8f1;border:1px solid #ccebdc;border-radius:12px;font-weight:700}
    .customer-orders-panel{overflow:hidden;background:#fff;border:1px solid #dde5ef;border-radius:17px;box-shadow:0 8px 28px rgba(21,47,81,.06)}
    .customer-orders-panel__head{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:19px 22px;border-bottom:1px solid #e3eaf2;background:linear-gradient(135deg,#f7faff,#fff)}
    .customer-orders-panel__head h2{margin:0 0 4px;color:#102a43;font-size:1.22rem;font-weight:900}.customer-orders-panel__head p{margin:0;color:#718096;font-size:.88rem}
    .customer-orders-panel__icon{display:grid;place-items:center;width:46px;height:46px;color:#1769aa;background:#eaf3ff;border-radius:13px;font-size:1.1rem}
    .customer-orders-table-wrap{width:100%;overflow-x:auto}.customer-order-table{width:100%!important;min-width:0;margin:0!important;table-layout:fixed}
    .customer-order-table thead th{padding:14px 8px!important;color:#53647b!important;background:#f5f8fc!important;border:0!important;border-bottom:1px solid #dde5ef!important;text-align:right;white-space:normal;font-size:.84rem;font-weight:800;line-height:1.6}
    .customer-order-table tbody td{padding:14px 8px!important;border-top:0!important;border-bottom:1px solid #edf1f6!important;text-align:right;vertical-align:middle!important;white-space:normal;font-size:.9rem;overflow-wrap:anywhere}
    .customer-order-table th:nth-child(1){width:4%}.customer-order-table th:nth-child(2),.customer-order-table th:nth-child(3),.customer-order-table th:nth-child(4){width:7%}.customer-order-table th:nth-child(5){width:11%}.customer-order-table th:nth-child(6),.customer-order-table th:nth-child(7){width:8%}.customer-order-table th:nth-child(8){width:7%}.customer-order-table th:nth-child(9){width:8%}.customer-order-table th:nth-child(10){width:13%}.customer-order-table th:nth-child(11){width:10%}.customer-order-table th:nth-child(12){width:6%}.customer-order-table th:nth-child(13){width:4%}
    .order-money{direction:ltr;display:inline-block;font-weight:800}.order-money.is-paid{color:#138455}.order-money.is-due{color:#cf3f4d}
    .order-payment-cell,.order-status-cell{display:flex;align-items:center;justify-content:center;gap:7px;flex-wrap:wrap}.order-payment-status{display:inline-flex;align-items:center;justify-content:center;min-height:30px;padding:5px 9px;border-radius:999px;font-size:.76rem;font-weight:800}.order-payment-status.is-paid{color:#087747;background:#e6f7ef}.order-payment-status.is-partial{color:#9a6500;background:#fff3d6}.order-payment-status.is-unpaid{color:#b12f3c;background:#ffecef}
    .order-payment-button{min-height:32px;padding:5px 9px;border:1px solid #bce5d1;border-radius:8px;color:#087747;background:#effaf4;font-size:.76rem;font-weight:800}.order-payment-button:hover{border-color:#83cfaa;background:#ddf5e9}
    .customer-order-status{min-width:112px;border:1px solid transparent;border-radius:9px;font-weight:900;box-shadow:0 5px 14px rgba(25,45,75,.08)}.customer-order-status.order-stage-unassigned{color:#6b4d08;border-color:#ead08c;background:#fff8df}.customer-order-status.order-stage-workshop{color:#9b6200;border-color:#f2cf82;background:#fff3cf}.customer-order-status.order-stage-ready,.customer-order-status.order-stage-delivered{color:#fff;border-color:#1769e0;background:linear-gradient(135deg,#2478ec,#1159bd)}.customer-order-status.disabled{cursor:default;opacity:1}
    .order-delivery-form{margin:0}.order-delivery-action,.order-delivered-badge{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-height:34px;padding:6px 10px;border-radius:9px;font-size:.74rem;font-weight:900}.order-delivery-action{color:#087747;border:1px solid #8bd0ad;background:#e9f8f0}.order-delivery-action:hover{color:#fff;border-color:#15945c;background:#15945c}.order-delivered-badge{color:#fff;border:1px solid #15945c;background:linear-gradient(135deg,#1daa6a,#087747)}
    .customer-orders-page .dataTables_wrapper{padding:14px}.customer-orders-page .dataTables_filter{float:left;text-align:left}.customer-orders-page .dataTables_length{float:right}.customer-orders-page .modal-content{overflow:hidden;border:0;border-radius:15px;box-shadow:0 20px 60px rgba(12,35,68,.22)}
    @media(max-width:1300px){.customer-order-table{min-width:1320px;table-layout:auto}.customer-order-table thead th{white-space:nowrap}}
    @media(max-width:767px){.customer-orders-page{padding-top:18px}.customer-orders-shell{width:min(100% - 20px,1720px)}.customer-orders-hero{align-items:stretch;flex-direction:column;padding:20px}.customer-orders-back{justify-content:center}.customer-orders-panel__head{padding:16px}}
</style>

<section class="main-content customer-orders-page">
    <div class="customer-orders-shell">
        <header class="customer-orders-hero">
            <div>
                <h1>{{ $selectedCustomer->name }} کے حالیہ آرڈرز</h1>
                <p>آرڈر، ادائیگی، بقایا، مرحلہ، درزی اور ریک نمبر ایک الگ صفحے پر دیکھیں اور تبدیل کریں۔</p>
                <div class="customer-orders-hero__meta">
                    <span><i class="fas fa-phone ml-1"></i><bdi>{{ $customer->phone_number1 }}</bdi></span>
                    @if($selectedCustomer->id !== $customer->id)<span><i class="fas fa-users ml-1"></i>{{ $customer->name }} کا خاندانی ناپ</span>@endif
                </div>
            </div>
            <a href="{{ route('admin.Customers.index') }}" class="customer-orders-back"><i class="fas fa-arrow-right"></i> گاہکوں کی فہرست</a>
        </header>

        <div id="customerOrdersAjaxAlert" class="customer-orders-alert" style="display:none"><i class="fas fa-check-circle"></i><span></span></div>
        @if(session('insert'))<div class="customer-orders-alert"><i class="fas fa-check-circle"></i><span>{{ session('insert') }}</span></div>@endif
        @if(session('success'))<div class="customer-orders-alert"><i class="fas fa-check-circle"></i><span>{{ session('success') }}</span></div>@endif
        @if($errors->any())<div class="alert alert-danger text-right"><strong>تبدیلی محفوظ نہیں ہو سکی۔</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <section id="orderDetail" class="customer-orders-panel" aria-live="polite">
            <div class="customer-orders-panel__head">
                <div><h2>آرڈر ریکارڈ</h2><p>{{ $customer->name }} کے تمام ٹیلرنگ آرڈرز تازہ ترین ترتیب میں۔</p></div>
                <span class="customer-orders-panel__icon"><i class="fas fa-receipt"></i></span>
            </div>
            <div class="customer-orders-table-wrap">
                <table class="table js-sortable-table customer-order-table" id="cc-table-data-order-history" data-return-orders="{{ $selectedCustomer->id }}">
                    <thead><tr>
                        <th>نمبر</th><th>کل رقم</th><th>ادا شدہ</th><th>بقایا</th><th>ادائیگی</th>
                        <th>آرڈر کی تاریخ</th><th>واپسی کی تاریخ</th><th>کپڑوں کی تعداد</th><th>درزی</th>
                        <th>مرحلہ</th><th>ریک نمبر</th><th>تبدیلی</th><th>پرنٹ</th>
                    </tr></thead>
                    <tbody class="tbody"></tbody>
                </table>
            </div>
        </section>

        <button type="button" id="loadCustomerOrders" class="getCustomer d-none" data-id="{{ $customer->id }}" data-name="{{ $selectedCustomer->name }}" aria-hidden="true"></button>
    </div>

    <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content">
            <form id="orderStatusForm" action="{{ $detailedWorkflow ? '' : url('admin/order-status') }}" method="post" @if($detailedWorkflow) data-action-base="{{ url('admin/tailor-jobs') }}" @endif>
                @csrf @if($detailedWorkflow) @method('PATCH') @endif
                <input type="hidden" id="order_id" name="order_id">
                <div class="modal-header"><h4 class="modal-title">آرڈر کا اگلا مرحلہ</h4><button type="button" class="close mr-auto ml-0" data-dismiss="modal"><span>&times;</span></button></div>
                <div class="modal-body text-right"><label for="orderStatusSelect" class="font-weight-bold">نیا مرحلہ منتخب کریں</label><select id="orderStatusSelect" class="form-control order-status" name="{{ $detailedWorkflow ? 'status' : 'order_status' }}" required></select></div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary" id="submit-button">محفوظ کریں</button><button type="button" class="btn btn-light" data-dismiss="modal">منسوخ کریں</button></div>
            </form>
        </div></div>
    </div>

    @if($canViewBalances)
    <div class="modal fade" id="myModalpayment" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content">
            <form action="{{ route('admin.DirectPayment') }}" method="post">@csrf
                <input type="hidden" id="customer_id" name="customer_id"><input type="hidden" id="payment_order_id" name="order_id">
                <div class="modal-header"><h4 class="modal-title" id="paymentModalTitle">آرڈر کی ادائیگی</h4><button type="button" class="close mr-auto ml-0" data-dismiss="modal"><span>&times;</span></button></div>
                <div class="modal-body text-right">
                    <div id="paymentAjaxFeedback" class="alert alert-danger" style="display:none"></div><div id="orderPaymentContext" class="alert alert-info" style="display:none"></div>
                    <div class="form-group"><label for="directPaymentAmount" class="font-weight-bold">وصول شدہ رقم</label><div class="input-group" dir="ltr"><div class="input-group-prepend"><span class="input-group-text">Rs.</span></div><input id="directPaymentAmount" type="number" min="0" step="0.01" name="DirectPayment" class="form-control" required></div></div>
                    <div class="form-group mb-0"><label for="directPaymentComment" class="font-weight-bold">نوٹ / حوالہ</label><textarea id="directPaymentComment" class="form-control" rows="3" name="comment"></textarea></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-success">ادائیگی محفوظ کریں</button><button type="button" class="btn btn-light" data-dismiss="modal">منسوخ کریں</button></div>
            </form>
        </div></div>
    </div>
    @endif
</section>

<script>
    $(document).ready(function () {
        setTimeout(function () { $('#loadCustomerOrders').trigger('click'); }, 100);

        $('#myModalpayment form').on('submit', function (event) {
            event.preventDefault();
            var form = $(this), button = form.find('button[type="submit"]'), feedback = $('#paymentAjaxFeedback');
            feedback.hide().text(''); button.prop('disabled', true);
            $.ajax({
                url: form.attr('action'), type: 'POST', data: form.serialize(), dataType: 'json', headers: { Accept: 'application/json' },
                success: function (response) {
                    $('#myModalpayment').modal('hide');
                    $('#customerOrdersAjaxAlert').show().find('span').text(response.message);
                    form[0].reset();
                    $('#loadCustomerOrders').trigger('click');
                },
                error: function (xhr) {
                    var response = xhr.responseJSON || {};
                    feedback.text(response.message || 'ادائیگی محفوظ نہیں ہو سکی۔ معلومات چیک کرکے دوبارہ کوشش کریں۔').show();
                },
                complete: function () { button.prop('disabled', false); }
            });
        });
    });
</script>
@endsection
