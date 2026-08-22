@extends('main')

@section('content')
    @php
        $customerCount = $customers->count();
        $totalBalance = $canViewBalances ? (float) array_sum($customerTransactions) : 0;
        $customersWithBalance = $canViewBalances
            ? collect($customerTransactions)->filter(fn ($balance) => (float) $balance > 0)->count()
            : 0;
        $settledCustomers = $canViewBalances ? max(0, $customerCount - $customersWithBalance) : 0;
    @endphp

    <style>
        .sales-record-workspace {
            --sales-blue: #1769e0;
            --sales-navy: #102a50;
            --sales-muted: #68788f;
            --sales-line: #e1e9f3;
            --sales-surface: #fff;
            direction: rtl;
            padding: 28px 0 48px;
        }

        .sales-record-shell { width: min(100% - 32px, 1720px); margin-inline: auto; }
        .sales-record-head { display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-bottom: 22px; }
        .sales-record-title { display: flex; align-items: center; gap: 14px; }
        .sales-record-title__icon {
            display: grid;
            place-items: center;
            flex: 0 0 52px;
            width: 52px;
            height: 52px;
            color: var(--sales-blue);
            background: #edf5ff;
            border: 1px solid #d8e8ff;
            border-radius: 15px;
            font-size: 21px;
        }

        .sales-record-title h1 { margin: 0 0 5px; color: var(--sales-navy); font-size: clamp(1.55rem, 2vw, 2rem); font-weight: 800; }
        .sales-record-title p { margin: 0; color: var(--sales-muted); font-size: .94rem; }
        .sales-record-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 43px;
            padding: 9px 16px;
            color: #fff;
            background: linear-gradient(135deg, #2378ee, #0c5ad1);
            border: 1px solid var(--sales-blue);
            border-radius: 10px;
            box-shadow: 0 8px 18px rgba(23, 105, 224, .2);
            font-weight: 800;
        }

        .sales-record-primary:hover { color: #fff; text-decoration: none; transform: translateY(-1px); }
        .sales-record-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin-bottom: 22px; }
        .sales-record-stat {
            display: flex;
            align-items: center;
            gap: 14px;
            min-height: 108px;
            padding: 18px;
            background: var(--sales-surface);
            border: 1px solid var(--sales-line);
            border-radius: 15px;
            box-shadow: 0 6px 22px rgba(21, 47, 81, .055);
        }

        .sales-record-stat__icon { display: grid; place-items: center; flex: 0 0 50px; width: 50px; height: 50px; border-radius: 50%; font-size: 19px; }
        .sales-record-stat:nth-child(1) .sales-record-stat__icon { color: #1769e0; background: #eaf3ff; }
        .sales-record-stat:nth-child(2) .sales-record-stat__icon { color: #dc7d0e; background: #fff3dc; }
        .sales-record-stat:nth-child(3) .sales-record-stat__icon { color: #cf3f4d; background: #fff0f2; }
        .sales-record-stat:nth-child(4) .sales-record-stat__icon { color: #0c9560; background: #e8f8f0; }
        .sales-record-stat__label { color: var(--sales-muted); font-size: .86rem; }
        .sales-record-stat__value { margin-top: 5px; color: var(--sales-navy); direction: ltr; font-size: 1.3rem; font-weight: 800; text-align: right; }

        .sales-record-panel {
            overflow: hidden;
            background: var(--sales-surface);
            border: 1px solid var(--sales-line);
            border-radius: 16px;
            box-shadow: 0 8px 28px rgba(21, 47, 81, .06);
        }

        .sales-record-panel + .sales-record-panel { margin-top: 22px; }
        .sales-record-panel__head { display: flex; align-items: center; justify-content: space-between; gap: 20px; padding: 20px 22px; border-bottom: 1px solid var(--sales-line); }
        .sales-record-panel__title h2 { margin: 0 0 4px; color: var(--sales-navy); font-size: 1.25rem; font-weight: 800; }
        .sales-record-panel__title p { margin: 0; color: var(--sales-muted); font-size: .86rem; }
        .sales-record-search { position: relative; width: min(100%, 360px); margin: 0; }
        .sales-record-search i { position: absolute; top: 50%; right: 15px; color: #8796aa; transform: translateY(-50%); }
        .sales-record-search input {
            width: 100%;
            min-height: 43px;
            padding: 9px 42px 9px 14px;
            border: 1px solid #d5dfec;
            border-radius: 10px;
            outline: none;
            transition: .2s ease;
        }

        .sales-record-search input:focus { border-color: #82b4f8; box-shadow: 0 0 0 3px rgba(23, 105, 224, .1); }
        .sales-record-table-wrap { width: 100%; height: auto !important; max-height: none !important; overflow-x: auto; overflow-y: visible !important; }
        .sales-record-table { width: 100% !important; min-width: 980px; margin: 0 !important; border-collapse: separate; border-spacing: 0; table-layout: fixed; }
        .sales-customer-table th:nth-child(1), .sales-customer-table td:nth-child(1) { width: 7%; }
        .sales-customer-table th:nth-child(2), .sales-customer-table td:nth-child(2) { width: 27%; }
        .sales-customer-table th:nth-child(3), .sales-customer-table td:nth-child(3) { width: 18%; }
        .sales-customer-table th:nth-child(4), .sales-customer-table td:nth-child(4) { width: 18%; }
        .sales-customer-table th:nth-child(5), .sales-customer-table td:nth-child(5) { width: 30%; }
        .sales-record-table thead th {
            padding: 15px 18px !important;
            color: #53647b !important;
            background: #f5f8fc !important;
            border: 0 !important;
            border-bottom: 1px solid var(--sales-line) !important;
            font-size: .93rem;
            font-weight: 800;
            text-align: right;
            white-space: nowrap;
        }

        .sales-record-table tbody td {
            height: 78px;
            padding: 16px 18px !important;
            color: #273951;
            border-top: 0 !important;
            border-bottom: 1px solid #edf1f6 !important;
            text-align: right;
            vertical-align: middle !important;
            white-space: nowrap;
        }

        .sales-record-table tbody tr:last-child td { border-bottom: 0 !important; }
        .sales-record-table tbody tr:hover { background: #fbfdff; }
        .sales-customer-identity { display: flex; align-items: center; gap: 11px; min-width: 190px; }
        .sales-customer-avatar { display: grid; place-items: center; flex: 0 0 46px; width: 46px; height: 46px; color: var(--sales-blue); background: #edf5ff; border-radius: 12px; font-size: 1.08rem; font-weight: 800; text-transform: uppercase; }
        .sales-customer-link { padding: 0; border: 0; color: var(--sales-navy); background: transparent; font-size: 1.05rem; font-weight: 800; text-align: right; cursor: pointer; }
        .sales-customer-link:hover { color: var(--sales-blue); text-decoration: underline; }
        .sales-customer-link small { display: block; margin-top: 4px; color: #8794a7; font-size: .76rem; font-weight: 500; }
        .sales-customer-phone { display: inline-block; color: #334b69; direction: ltr; font-weight: 700; }
        .sales-balance { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; direction: ltr; border-radius: 999px; font-size: .95rem; font-weight: 800; }
        .sales-balance.is-due { color: #c63848; background: #fff0f2; }
        .sales-balance.is-clear { color: #118654; background: #eaf8f1; }
        .sales-row-actions { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; }
        .sales-row-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-height: 39px;
            padding: 8px 11px;
            color: #3d536e;
            background: #fff;
            border: 1px solid #d8e2ef;
            border-radius: 9px;
            font-size: .84rem;
            font-weight: 700;
            transition: .18s ease;
        }

        .sales-row-action:hover { color: var(--sales-blue); border-color: #aecaef; background: #f5f9ff; text-decoration: none; }
        .sales-row-action.is-blue { color: #fff; border-color: var(--sales-blue); background: var(--sales-blue); }
        .sales-row-action.is-blue:hover { color: #fff; background: #0d59c8; }
        .sales-row-action.is-green { color: #128153; border-color: #bce5d1; background: #effaf4; }
        .sales-row-action.is-danger { color: #c92f3f; border-color: #f0cbd0; background: #fff5f6; }
        .sales-record-workspace .dataTables_wrapper,
        .sales-record-workspace .dataTables_wrapper > .row,
        .sales-record-workspace .dataTables_wrapper > .row > [class*="col-"] { width: 100%; max-width: 100%; }
        .sales-record-workspace .dataTables_wrapper { padding-bottom: 14px; }
        .sales-record-workspace .dataTables_wrapper .dataTables_filter,
        .sales-record-workspace .dataTables_wrapper .dt-buttons { display: none; }
        .sales-record-workspace .dataTables_scrollBody { height: auto !important; max-height: none !important; overflow-y: visible !important; }
        .sales-record-workspace .dataTables_info { padding: 15px 20px 0 !important; color: #738198 !important; }
        .sales-record-workspace .dataTables_paginate { padding: 10px 18px 0 !important; }
        .sales-history-panel { scroll-margin-top: 90px; }
        .sales-history-panel .sales-record-panel__head { background: linear-gradient(135deg, #f7faff, #fff); }
        .sales-history-customer { color: var(--sales-blue); }
        .sales-history-table { min-width: 1180px; }
        .sales-record-workspace .modal-content { overflow: hidden; border: 0; border-radius: 15px; box-shadow: 0 20px 60px rgba(12, 35, 68, .22); }
        .sales-record-workspace .modal-header { align-items: center; border-bottom: 1px solid var(--sales-line); }
        .sales-record-workspace .modal-title { color: var(--sales-navy); font-size: 1.15rem; font-weight: 800; }
        .sales-record-workspace .modal-body { text-align: right; }
        .sales-record-workspace .modal-footer { justify-content: flex-start; border-top: 1px solid var(--sales-line); }
        .sales-record-workspace .form-control { min-height: 43px; border-color: #d7e0ec; border-radius: 9px; }

        @media (max-width: 1100px) {
            .sales-record-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 767px) {
            .sales-record-workspace { padding-top: 18px; }
            .sales-record-shell { width: min(100% - 20px, 1720px); }
            .sales-record-head, .sales-record-panel__head { align-items: stretch; flex-direction: column; }
            .sales-record-primary, .sales-record-search { width: 100%; }
            .sales-record-stats { grid-template-columns: 1fr 1fr; gap: 10px; }
            .sales-record-stat { min-height: 92px; padding: 13px; }
            .sales-record-stat__icon { flex-basis: 42px; width: 42px; height: 42px; }
            .sales-record-stat__value { font-size: 1.05rem; }
            .sales-record-panel__head { padding: 17px; }
            .sales-customer-table { min-width: 0; table-layout: auto; }
            .sales-customer-table thead { display: none; }
            .sales-customer-table, .sales-customer-table tbody, .sales-customer-table tr, .sales-customer-table td { display: block; width: 100% !important; }
            .sales-customer-table tbody tr { padding: 14px; border-bottom: 1px solid var(--sales-line); }
            .sales-customer-table tbody td { display: flex; align-items: center; justify-content: space-between; gap: 16px; height: auto; padding: 9px 0 !important; border: 0 !important; white-space: normal; }
            .sales-customer-table tbody td::before { content: attr(data-label); color: #7b899d; font-size: .78rem; font-weight: 700; }
            .sales-customer-table tbody td.sales-name-cell::before,
            .sales-customer-table tbody td.sales-actions-cell::before,
            .sales-customer-table tbody td.sales-serial-cell { display: none; }
            .sales-customer-table tbody td.sales-name-cell { padding-bottom: 13px !important; }
            .sales-customer-table tbody td.sales-actions-cell { padding-top: 12px !important; }
            .sales-row-actions { width: 100%; }
            .sales-row-action { flex: 1 1 auto; }
        }

        @media (max-width: 480px) {
            .sales-record-stats { grid-template-columns: 1fr; }
            .sales-record-title__icon { width: 46px; height: 46px; }
        }
    </style>

    <section class="main-content sales-record-workspace">
        <div class="sales-record-shell">
            <div class="sales-record-head">
                <div class="sales-record-title">
                    <span class="sales-record-title__icon"><i class="fas fa-cash-register"></i></span>
                    <div>
                        <h1>کپڑے کی فروخت کا ریکارڈ</h1>
                        <p>گاہک، بقایا، ادائیگی اور فروخت کی مکمل تفصیل ایک جگہ دیکھیں۔</p>
                    </div>
                </div>
                <a href="{{ url('admin/sellcloth') }}" class="sales-record-primary"><i class="fas fa-plus"></i> نئی فروخت درج کریں</a>
            </div>

            @include('inc.message')

            <div class="sales-record-stats">
                <article class="sales-record-stat"><span class="sales-record-stat__icon"><i class="fas fa-users"></i></span><div><div class="sales-record-stat__label">کل گاہک</div><div class="sales-record-stat__value">{{ number_format($customerCount) }}</div></div></article>
                <article class="sales-record-stat"><span class="sales-record-stat__icon"><i class="fas fa-wallet"></i></span><div><div class="sales-record-stat__label">کل بقایا</div><div class="sales-record-stat__value">{{ $canViewBalances ? 'Rs. '.number_format($totalBalance, 2) : 'اجازت درکار ہے' }}</div></div></article>
                <article class="sales-record-stat"><span class="sales-record-stat__icon"><i class="fas fa-exclamation-circle"></i></span><div><div class="sales-record-stat__label">بقایا والے گاہک</div><div class="sales-record-stat__value">{{ $canViewBalances ? number_format($customersWithBalance) : '—' }}</div></div></article>
                <article class="sales-record-stat"><span class="sales-record-stat__icon"><i class="fas fa-check-circle"></i></span><div><div class="sales-record-stat__label">حساب مکمل</div><div class="sales-record-stat__value">{{ $canViewBalances ? number_format($settledCustomers) : '—' }}</div></div></article>
            </div>

            <section class="sales-record-panel" aria-labelledby="sales-customer-title">
                <div class="sales-record-panel__head">
                    <div class="sales-record-panel__title">
                        <h2 id="sales-customer-title">فروخت والے گاہک</h2>
                        <p>گاہک کے نام پر کلک کرنے سے اس کی کپڑے کی فروخت نیچے دکھائی جائے گی۔</p>
                    </div>
                    <label class="sales-record-search" for="salesCustomerSearch">
                        <i class="fas fa-search"></i>
                        <input id="salesCustomerSearch" type="search" placeholder="نام، فون یا سیریل نمبر سے تلاش کریں" autocomplete="off">
                    </label>
                </div>

                <div class="sales-record-table-wrap">
                    <table class="table js-sortable-table sales-record-table sales-customer-table" id="cc-table-data-customer-list">
                        <thead><tr><th>#</th><th>گاہک</th><th class="no-sort">فون نمبر</th><th class="no-sort">موجودہ بقایا</th><th class="no-sort">فوری کارروائیاں</th></tr></thead>
                        <tbody>
                            @foreach ($customers as $customer)
                                @php
                                    $balance = (float) ($customerTransactions[$customer->id] ?? 0);
                                    $initial = function_exists('mb_substr') ? mb_substr(trim($customer->name), 0, 1) : substr(trim($customer->name), 0, 1);
                                @endphp
                                <tr>
                                    <td class="customer_id sales-serial-cell" data-label="سیریل نمبر">{{ $customer->id }}</td>
                                    <td class="sales-name-cell" data-label="گاہک">
                                        <div class="sales-customer-identity">
                                            <span class="sales-customer-avatar">{{ $initial ?: 'گ' }}</span>
                                            <button type="button" class="GetCustomer sale sales-customer-link" data-url="{{ url('admin/getSale') }}" data-id="{{ $customer->id }}" data-name="{{ $customer->name }}" aria-label="{{ $customer->name }} کی فروخت دیکھیں">
                                                {{ $customer->name }}<small>فروخت کی تفصیل دیکھیں</small>
                                            </button>
                                        </div>
                                    </td>
                                    <td data-label="فون نمبر"><span class="sales-customer-phone">{{ $customer->phone_number1 ?: '—' }}</span></td>
                                    <td data-label="موجودہ بقایا">
                                        @if ($canViewBalances)
                                            <span class="sales-balance {{ $balance > 0 ? 'is-due' : 'is-clear' }}"><i class="fas {{ $balance > 0 ? 'fa-exclamation-circle' : 'fa-check-circle' }}"></i> Rs. {{ number_format($balance, 2) }}</span>
                                        @else
                                            <span class="text-muted">اجازت درکار ہے</span>
                                        @endif
                                    </td>
                                    <td class="sales-actions-cell" data-label="فوری کارروائیاں">
                                        <div class="sales-row-actions">
                                            @if ($canViewBalances)
                                                <button type="button" class="sales-row-action is-green customer_payment" data-customerid="{{ $customer->id }}" data-toggle="modal" data-target="#myModalpayment"><i class="fas fa-wallet"></i> ادائیگی</button>
                                            @endif
                                            <a href="{{ route('admin.customers.statement', $customer) }}" class="sales-row-action is-blue"><i class="fas fa-file-invoice-dollar"></i> مشترکہ کھاتہ</a>
                                            <form action="{{ route('admin.dlt', $customer->id) }}" method="POST" class="d-inline" data-confirm="کیا آپ واقعی یہ ریکارڈ حذف کرنا چاہتے ہیں؟">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="sales-row-action is-danger"><i class="fas fa-trash-alt"></i> حذف</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="orderDetail" class="sales-record-panel sales-history-panel" style="display:none" aria-live="polite">
                <div class="sales-record-panel__head">
                    <div class="sales-record-panel__title"><h2><span id="cus_name" class="sales-history-customer"></span> کی فروخت</h2><p>تاریخ، کپڑے کی تفصیل، مقدار، ریٹ اور رسید یہاں دیکھیں۔</p></div>
                    <span class="sales-record-title__icon"><i class="fas fa-receipt"></i></span>
                </div>
                <div class="sales-record-table-wrap">
                    <table class="table js-sortable-table sales-record-table sales-history-table" id="cc-table-data-order-history">
                        <thead><tr><th></th><th>نمبر</th><th>رقم</th><th>فروخت کی تاریخ</th><th>برانڈ</th><th>کپڑے کی قسم</th><th>رنگ</th><th>میٹر / گز</th><th>ریٹ فی میٹر</th><th>رسید</th></tr></thead>
                        <tbody class="tbody"></tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="modal fade" id="myModalpayment" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <form action="{{ url('admin/SaleDirectPayment') }}" method="post">
                        @csrf
                        <input type="hidden" id="customer_id" name="customer_id">
                        <div class="modal-header">
                            <h4 class="modal-title"><i class="fas fa-wallet text-success ml-2"></i> فروخت کی ادائیگی درج کریں</h4>
                            <button type="button" class="close mr-auto ml-0" data-dismiss="modal" aria-label="بند کریں"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="saleDirectPayment" class="font-weight-bold">وصول شدہ رقم</label>
                                <div class="input-group" dir="ltr"><div class="input-group-prepend"><span class="input-group-text">Rs.</span></div><input id="saleDirectPayment" type="number" min="0" step="0.01" name="DirectPayment" class="form-control" required placeholder="0.00"></div>
                            </div>
                            <div class="form-group mb-0">
                                <label for="salePaymentDepartment" class="font-weight-bold">ادائیگی کا شعبہ</label>
                                <select id="salePaymentDepartment" name="comment" class="form-control" required><option value="Sale">کپڑے کی فروخت</option><option value="Tailor">ٹیلرنگ</option></select>
                            </div>
                        </div>
                        <div class="modal-footer"><button type="submit" class="btn btn-success"><i class="fas fa-check ml-1"></i> ادائیگی محفوظ کریں</button><button type="button" class="btn btn-light" data-dismiss="modal">منسوخ کریں</button></div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <script>
        $(document).ready(function () {
            $('#salesCustomerSearch').on('input', function () {
                if ($.fn.DataTable && $.fn.DataTable.isDataTable('#cc-table-data-customer-list')) {
                    $('#cc-table-data-customer-list').DataTable().search(this.value).draw();
                }
            });

            $(document).ajaxComplete(function (event, xhr, settings) {
                if (settings.url && settings.url.indexOf('/admin/getSale') !== -1) {
                    setTimeout(function () {
                        var historyPanel = document.getElementById('orderDetail');
                        if (historyPanel) historyPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }, 120);
                }
            });
        });
    </script>
@endsection
