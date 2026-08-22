@extends('main')

@section('content')
    @php
        $customerCount = $customers->count();
        $totalBalance = $canViewBalances ? (float) $customers->sum('current_balance') : 0;
        $customersWithBalance = $canViewBalances
            ? $customers->filter(fn ($customer) => (float) $customer->current_balance > 0)->count()
            : 0;
        $settledCustomers = $canViewBalances
            ? $customers->filter(fn ($customer) => (float) $customer->current_balance <= 0)->count()
            : 0;
    @endphp

    <style>
        .customer-workspace {
            --customer-blue: #1769e0;
            --customer-navy: #102a50;
            --customer-muted: #66758f;
            --customer-line: #e2e9f3;
            --customer-surface: #ffffff;
            direction: rtl;
            padding: 28px 0 48px;
        }

        .customer-workspace .customer-shell {
            width: min(100% - 32px, 1720px);
            margin-inline: auto;
        }

        .customer-page-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 22px;
        }

        .customer-page-title {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .customer-page-title__icon {
            display: grid;
            place-items: center;
            width: 52px;
            height: 52px;
            color: var(--customer-blue);
            background: #edf5ff;
            border: 1px solid #d8e8ff;
            border-radius: 15px;
            font-size: 21px;
        }

        .customer-page-title h1 {
            margin: 0 0 5px;
            color: var(--customer-navy);
            font-size: clamp(1.55rem, 2vw, 2rem);
            font-weight: 800;
        }

        .customer-page-title p {
            margin: 0;
            color: var(--customer-muted);
            font-size: .95rem;
        }

        .customer-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
        }

        .customer-action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 43px;
            padding: 9px 15px;
            border: 1px solid #d8e2ef;
            border-radius: 10px;
            color: #344762;
            background: #fff;
            font-weight: 700;
            box-shadow: 0 3px 10px rgba(24, 54, 93, .04);
            transition: .2s ease;
        }

        .customer-action-btn:hover {
            color: var(--customer-blue);
            border-color: #b8d2f8;
            transform: translateY(-1px);
            text-decoration: none;
        }

        .customer-action-btn.is-primary {
            color: #fff;
            border-color: var(--customer-blue);
            background: linear-gradient(135deg, #2378ee, #0c5ad1);
            box-shadow: 0 8px 18px rgba(23, 105, 224, .2);
        }

        .customer-action-btn.is-primary:hover {
            color: #fff;
        }

        .customer-alert {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 16px;
            margin-bottom: 14px;
            border: 1px solid transparent;
            border-radius: 12px;
            text-align: right;
        }

        .customer-alert i { margin-top: 4px; }
        .customer-alert.is-success { color: #146c43; background: #eaf8f1; border-color: #ccebdc; }
        .customer-alert.is-warning { color: #805800; background: #fff8e5; border-color: #f4dfaa; }
        .customer-alert.is-danger { color: #a32834; background: #fff0f1; border-color: #f2c9cd; }
        .customer-alert code { color: #d63355; font-size: 1.15rem; font-weight: 800; }

        .customer-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin: 18px 0 22px;
        }

        .customer-stat {
            display: flex;
            align-items: center;
            gap: 14px;
            min-height: 110px;
            padding: 18px;
            background: var(--customer-surface);
            border: 1px solid var(--customer-line);
            border-radius: 15px;
            box-shadow: 0 6px 22px rgba(21, 47, 81, .055);
        }

        .customer-stat__icon {
            display: grid;
            place-items: center;
            flex: 0 0 52px;
            width: 52px;
            height: 52px;
            border-radius: 50%;
            font-size: 20px;
        }

        .customer-stat:nth-child(1) .customer-stat__icon { color: #1769e0; background: #eaf3ff; }
        .customer-stat:nth-child(2) .customer-stat__icon { color: #e38a13; background: #fff4df; }
        .customer-stat:nth-child(3) .customer-stat__icon { color: #0c9b60; background: #e7f8ef; }
        .customer-stat:nth-child(4) .customer-stat__icon { color: #7b4ad9; background: #f1ebff; }

        .customer-stat__label { color: var(--customer-muted); font-size: .86rem; }
        .customer-stat__value { margin-top: 5px; color: var(--customer-navy); font-size: 1.35rem; font-weight: 800; direction: ltr; text-align: right; }

        .customer-panel {
            overflow: hidden;
            background: var(--customer-surface);
            border: 1px solid var(--customer-line);
            border-radius: 16px;
            box-shadow: 0 8px 28px rgba(21, 47, 81, .06);
        }

        .customer-panel + .customer-panel { margin-top: 22px; }

        .customer-panel__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 20px 22px;
            border-bottom: 1px solid var(--customer-line);
        }

        .customer-panel__title h2 {
            margin: 0 0 4px;
            color: var(--customer-navy);
            font-size: 1.25rem;
            font-weight: 800;
        }

        .customer-panel__title p { margin: 0; color: var(--customer-muted); font-size: .86rem; }

        .customer-search {
            position: relative;
            width: min(100%, 350px);
        }

        .customer-search i {
            position: absolute;
            top: 50%;
            right: 15px;
            color: #8796aa;
            transform: translateY(-50%);
        }

        .customer-search input {
            width: 100%;
            min-height: 43px;
            padding: 9px 42px 9px 14px;
            border: 1px solid #d5dfec;
            border-radius: 10px;
            outline: none;
            transition: .2s ease;
        }

        .customer-search input:focus { border-color: #82b4f8; box-shadow: 0 0 0 3px rgba(23, 105, 224, .1); }

        .customer-table-wrap { width: 100%; overflow-x: auto; }
        .customer-directory { width: 100% !important; min-width: 1080px; margin: 0 !important; border-collapse: separate; border-spacing: 0; table-layout: fixed; }
        .customer-list-table th:nth-child(1), .customer-list-table td:nth-child(1) { width: 5%; }
        .customer-list-table th:nth-child(2), .customer-list-table td:nth-child(2) { width: 25%; }
        .customer-list-table th:nth-child(3), .customer-list-table td:nth-child(3) { width: 17%; }
        .customer-list-table th:nth-child(4), .customer-list-table td:nth-child(4) { width: 15%; }
        .customer-list-table th:nth-child(5), .customer-list-table td:nth-child(5) { width: 38%; }
        .customer-directory thead th {
            padding: 15px 18px !important;
            color: #53647b !important;
            background: #f5f8fc !important;
            border: 0 !important;
            border-bottom: 1px solid var(--customer-line) !important;
            font-size: .94rem;
            font-weight: 800;
            text-align: right;
            white-space: nowrap;
        }

        .customer-directory tbody td {
            height: 82px;
            padding: 18px !important;
            color: #273951;
            border-top: 0 !important;
            border-bottom: 1px solid #edf1f6 !important;
            text-align: right;
            vertical-align: middle !important;
            white-space: nowrap;
            font-size: 1rem;
        }

        .customer-directory tbody tr:last-child td { border-bottom: 0 !important; }
        .customer-directory tbody tr:hover { background: #fbfdff; }

        .customer-identity {
            display: flex;
            align-items: center;
            gap: 11px;
            min-width: 190px;
        }

        .customer-avatar {
            display: grid;
            place-items: center;
            flex: 0 0 48px;
            width: 48px;
            height: 48px;
            color: #1769e0;
            background: #edf5ff;
            border-radius: 12px;
            font-size: 1.12rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .customer-link {
            padding: 0;
            border: 0;
            color: var(--customer-navy);
            background: transparent;
            font-size: 1.08rem;
            font-weight: 800;
            text-align: right;
            cursor: pointer;
        }

        .customer-link:hover { color: var(--customer-blue); text-decoration: underline; }
        .customer-link small { display: block; margin-top: 5px; color: #8794a7; font-size: .78rem; font-weight: 500; }

        .customer-phone { direction: ltr; display: inline-block; color: #334b69; font-size: 1.05rem; font-weight: 700; }
        .customer-balance { direction: ltr; display: inline-block; font-size: 1.05rem; font-weight: 800; }
        .customer-balance.is-due { color: #cf3f4d; }
        .customer-balance.is-clear { color: #11945b; }

        .customer-row-actions { display: flex; align-items: center; justify-content: flex-start; gap: 9px; width: 100%; }
        .customer-row-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-height: 40px;
            padding: 8px 12px;
            border: 1px solid #d8e2ef;
            border-radius: 9px;
            color: #3d536e;
            background: #fff;
            font-size: .86rem;
            font-weight: 700;
            transition: .18s ease;
        }

        .customer-row-action:hover { color: var(--customer-blue); border-color: #aecaef; background: #f5f9ff; text-decoration: none; }
        .customer-row-action.is-blue { color: #fff; border-color: var(--customer-blue); background: var(--customer-blue); }
        .customer-row-action.is-blue:hover { color: #fff; background: #0d59c8; }
        .customer-row-action.is-green { color: #138455; border-color: #bce5d1; background: #effaf4; }

        .customer-workspace .dataTables_wrapper,
        .customer-workspace .dataTables_wrapper > .row,
        .customer-workspace .dataTables_wrapper > .row > [class*="col-"] { width: 100%; max-width: 100%; }
        .customer-workspace .dataTables_wrapper { padding-bottom: 14px; }
        .customer-workspace .dataTables_wrapper .dataTables_filter,
        .customer-workspace .dataTables_wrapper .dt-buttons { display: none; }
        .customer-workspace .dataTables_info { padding: 15px 20px 0 !important; color: #738198 !important; }
        .customer-workspace .dataTables_paginate { padding: 10px 18px 0 !important; }

        .customer-empty {
            padding: 54px 20px !important;
            color: var(--customer-muted) !important;
            text-align: center !important;
        }

        .customer-empty i { display: block; margin-bottom: 12px; color: #b3c0d2; font-size: 2rem; }

        .customer-order-panel { scroll-margin-top: 90px; }
        .customer-order-panel .customer-panel__head { background: linear-gradient(135deg, #f7faff, #fff); }
        .customer-order-person { color: var(--customer-blue); }
        .customer-order-table { min-width: 1120px; }

        .customer-workspace .modal-content { overflow: hidden; border: 0; border-radius: 15px; box-shadow: 0 20px 60px rgba(12, 35, 68, .22); }
        .customer-workspace .modal-header { align-items: center; border-bottom: 1px solid var(--customer-line); }
        .customer-workspace .modal-title { color: var(--customer-navy); font-size: 1.15rem; font-weight: 800; }
        .customer-workspace .modal-body { text-align: right; }
        .customer-workspace .modal-footer { justify-content: flex-start; border-top: 1px solid var(--customer-line); }
        .customer-workspace .form-control { min-height: 43px; border-color: #d7e0ec; border-radius: 9px; }

        @media (max-width: 1100px) {
            .customer-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 767px) {
            .customer-workspace { padding-top: 18px; }
            .customer-workspace .customer-shell { width: min(100% - 20px, 1720px); }
            .customer-page-head, .customer-panel__head { align-items: stretch; flex-direction: column; }
            .customer-actions, .customer-search { width: 100%; }
            .customer-action-btn { flex: 1 1 calc(50% - 8px); }
            .customer-stats { grid-template-columns: 1fr 1fr; gap: 10px; }
            .customer-stat { min-height: 92px; padding: 13px; }
            .customer-stat__icon { flex-basis: 42px; width: 42px; height: 42px; }
            .customer-stat__value { font-size: 1.05rem; }
            .customer-panel__head { padding: 17px; }
            .customer-directory thead { display: none; }
            .customer-directory { min-width: 0; table-layout: auto; }
            .customer-directory, .customer-directory tbody, .customer-directory tr, .customer-directory td { display: block; width: 100% !important; }
            .customer-directory tbody tr { padding: 14px; border-bottom: 1px solid var(--customer-line); }
            .customer-directory tbody td { display: flex; align-items: center; justify-content: space-between; gap: 16px; height: auto; padding: 9px 0 !important; border: 0 !important; white-space: normal; }
            .customer-directory tbody td::before { content: attr(data-label); color: #7b899d; font-size: .78rem; font-weight: 700; }
            .customer-directory tbody td.customer-name-cell::before,
            .customer-directory tbody td.customer-actions-cell::before,
            .customer-directory tbody td.customer-serial-cell { display: none; }
            .customer-directory tbody td.customer-name-cell { padding-bottom: 13px !important; }
            .customer-directory tbody td.customer-actions-cell { padding-top: 12px !important; }
            .customer-row-actions { flex-wrap: wrap; width: 100%; }
            .customer-row-action { flex: 1 1 auto; }
        }

        @media (max-width: 480px) {
            .customer-stats { grid-template-columns: 1fr; }
            .customer-page-title__icon { width: 46px; height: 46px; }
            .customer-action-btn { flex-basis: 100%; }
        }
    </style>

    <section class="main-content customer-workspace">
        <div class="customer-shell">
            <div class="customer-page-head">
                <div class="customer-page-title">
                    <span class="customer-page-title__icon"><i class="fas fa-users"></i></span>
                    <div>
                        <h1>گاہک اور پیمائش</h1>
                        <p>گاہک کی معلومات، پیمائش، آرڈر اور بقایا ایک جگہ منظم کریں۔</p>
                    </div>
                </div>

                <div class="customer-actions">
                    <a href="{{ route('admin.Customers.create') }}" class="customer-action-btn is-primary">
                        <i class="fas fa-user-plus" aria-hidden="true"></i>
                        نیا گاہک شامل کریں
                    </a>
                    <button type="button" class="customer-action-btn" data-toggle="modal" data-target="#customersCsvModal">
                        <i class="fas fa-file-import"></i> ایکسل سے درآمد
                    </button>
                    <a href="{{ route('admin.customercsv') }}" class="customer-action-btn">
                        <i class="fas fa-file-export"></i> ایکسل میں برآمد
                    </a>
                    <button type="button" class="customer-action-btn" data-toggle="modal" data-target="#myRackModal">
                        <i class="fas fa-layer-group"></i> نیا ریک نمبر
                    </button>
                </div>
            </div>

            @if (Session::has('insert'))
                <div class="customer-alert is-success" role="alert">
                    <i class="fas fa-check-circle"></i>
                    <div>{{ Session::get('insert') }}</div>
                </div>
            @endif

            @if (Session::has('customer_pin'))
                <div class="customer-alert is-warning" role="alert">
                    <i class="fas fa-key"></i>
                    <div>
                        <strong>{{ Session::get('customer_pin_name') }} کا موبائل پن:</strong>
                        <code class="mx-2">{{ Session::get('customer_pin') }}</code>
                        <div class="small mt-1">یہ پن صرف ایک بار دکھایا جا رہا ہے۔ اسے محفوظ طریقے سے گاہک کو دیں۔</div>
                    </div>
                </div>
            @endif

            @if (Session::has('balanceError'))
                <div class="customer-alert is-danger" role="alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>{{ Session::get('balanceError') }}</div>
                </div>
            @endif

            <div class="customer-stats">
                <article class="customer-stat">
                    <span class="customer-stat__icon"><i class="fas fa-user-friends"></i></span>
                    <div>
                        <div class="customer-stat__label">کل گاہک</div>
                        <div class="customer-stat__value">{{ number_format($customerCount) }}</div>
                    </div>
                </article>

                <article class="customer-stat">
                    <span class="customer-stat__icon"><i class="fas fa-wallet"></i></span>
                    <div>
                        <div class="customer-stat__label">کل بقایا</div>
                        <div class="customer-stat__value">
                            @if ($canViewBalances)
                                Rs. {{ number_format($totalBalance, 2) }}
                            @else
                                <small>اجازت درکار ہے</small>
                            @endif
                        </div>
                    </div>
                </article>

                <article class="customer-stat">
                    <span class="customer-stat__icon"><i class="fas fa-exclamation-circle"></i></span>
                    <div>
                        <div class="customer-stat__label">بقایا والے گاہک</div>
                        <div class="customer-stat__value">{{ $canViewBalances ? number_format($customersWithBalance) : '—' }}</div>
                    </div>
                </article>

                <article class="customer-stat">
                    <span class="customer-stat__icon"><i class="fas fa-user-check"></i></span>
                    <div>
                        <div class="customer-stat__label">حساب مکمل</div>
                        <div class="customer-stat__value">{{ $canViewBalances ? number_format($settledCustomers) : '—' }}</div>
                    </div>
                </article>
            </div>

            <section class="customer-panel" aria-labelledby="customer-directory-title">
                <div class="customer-panel__head">
                    <div class="customer-panel__title">
                        <h2 id="customer-directory-title">گاہکوں کی فہرست</h2>
                        <p>نام پر کلک کرنے سے اسی گاہک کے سابقہ آرڈر نیچے دکھائی دیں گے۔</p>
                    </div>
                    <label class="customer-search" for="customerDirectorySearch">
                        <i class="fas fa-search"></i>
                        <input id="customerDirectorySearch" type="search" placeholder="نام یا فون نمبر سے تلاش کریں" autocomplete="off">
                    </label>
                </div>

                <div class="customer-table-wrap">
                    <table class="table js-sortable-table customer-directory customer-list-table" id="cc-table-data-customer-list">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">گاہک</th>
                                <th scope="col" class="no-sort">فون نمبر</th>
                                <th scope="col" class="no-sort">موجودہ بقایا</th>
                                <th scope="col" class="no-sort">فوری کارروائیاں</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($customers as $customer)
                                @php
                                    $currentBalance = (float) ($customer->current_balance ?? 0);
                                    $initial = function_exists('mb_substr') ? mb_substr(trim($customer->name), 0, 1) : substr(trim($customer->name), 0, 1);
                                @endphp
                                <tr>
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
                                            <span class="customer-balance {{ $currentBalance > 0 ? 'is-due' : 'is-clear' }}">
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
                                            <a href="{{ url('admin/Customers/' . $customer->id . '/edit') }}" class="customer-row-action">
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
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="orderDetail" class="customer-panel customer-order-panel" style="display:none" aria-live="polite">
                <div class="customer-panel__head">
                    <div class="customer-panel__title">
                        <h2><span id="cus_name" class="customer-order-person"></span> کے آرڈر</h2>
                        <p>آرڈر کی تاریخ، واپسی، درزی، موجودہ مرحلہ اور ریک نمبر یہاں دیکھیں۔</p>
                    </div>
                    <span class="customer-stat__icon"><i class="fas fa-receipt"></i></span>
                </div>
                <div class="customer-table-wrap">
                    <table class="table js-sortable-table customer-directory customer-order-table" id="cc-table-data-order-history">
                        <thead>
                            <tr>
                                <th></th>
                                <th>نمبر</th>
                                <th class="no-sort">رقم</th>
                                <th class="no-sort">آرڈر کی تاریخ</th>
                                <th class="no-sort">واپسی کی تاریخ</th>
                                <th class="no-sort">کپڑوں کی تعداد</th>
                                <th class="no-sort">درزی</th>
                                <th class="no-sort">مرحلہ</th>
                                <th class="no-sort">ریک نمبر</th>
                                <th class="no-sort">تبدیلی</th>
                                <th class="no-sort">پرنٹ</th>
                            </tr>
                        </thead>
                        <tbody class="tbody"></tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <form action="{{ url('admin/order-status') }}" method="post">
                        @csrf
                        <input type="hidden" id="order_id" name="order_id">
                        <div class="modal-header">
                            <h4 class="modal-title"><i class="fas fa-tasks text-primary ml-2"></i> آرڈر کا اگلا مرحلہ</h4>
                            <button type="button" class="close mr-auto ml-0" data-dismiss="modal" aria-label="بند کریں"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <label for="orderStatusSelect" class="font-weight-bold">نیا مرحلہ منتخب کریں</label>
                            <select id="orderStatusSelect" class="form-control order-status" name="order_status" required style="padding-top: 0px;"></select>
                            <small class="form-text text-muted mt-2">آرڈر کی موجودہ حالت کے لیے صرف کارخانے میں ہے یا تیار ہے منتخب کریں۔</small>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary" id="submit-button"><i class="fas fa-check ml-1"></i> محفوظ کریں</button>
                            <button type="button" class="btn btn-light" data-dismiss="modal">منسوخ کریں</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="myModalpayment" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <form action="{{ url('admin/DirectPayment') }}" method="post">
                        @csrf
                        <input type="hidden" id="customer_id" name="customer_id">
                        <div class="modal-header">
                            <h4 class="modal-title"><i class="fas fa-wallet text-success ml-2"></i> گاہک کی ادائیگی درج کریں</h4>
                            <button type="button" class="close mr-auto ml-0" data-dismiss="modal" aria-label="بند کریں"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="directPaymentAmount" class="font-weight-bold">وصول شدہ رقم</label>
                                <div class="input-group" dir="ltr">
                                    <div class="input-group-prepend"><span class="input-group-text">Rs.</span></div>
                                    <input id="directPaymentAmount" type="number" min="0" step="0.01" name="DirectPayment" class="form-control" required placeholder="0.00">
                                </div>
                            </div>
                            <div class="form-group mb-0">
                                <label for="directPaymentComment" class="font-weight-bold">نوٹ / حوالہ</label>
                                <textarea id="directPaymentComment" class="form-control" rows="3" name="comment" placeholder="ادائیگی کے بارے میں اختیاری نوٹ"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-success"><i class="fas fa-check ml-1"></i> ادائیگی محفوظ کریں</button>
                            <button type="button" class="btn btn-light" data-dismiss="modal">منسوخ کریں</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="myRackModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <form action="{{ url('admin/RackNo') }}" method="post">
                        @csrf
                        <div class="modal-header">
                            <h4 class="modal-title"><i class="fas fa-layer-group text-primary ml-2"></i> نیا ریک نمبر شامل کریں</h4>
                            <button type="button" class="close mr-auto ml-0" data-dismiss="modal" aria-label="بند کریں"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <label for="rackNumber" class="font-weight-bold">ریک نمبر / نام</label>
                            <input id="rackNumber" type="text" name="RackNo" class="form-control" required placeholder="مثلاً A-01">
                            <small class="form-text text-muted mt-2">یہ ریک نمبر آرڈر محفوظ کرنے اور تلاش کرنے میں مدد دے گا۔</small>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-plus ml-1"></i> ریک شامل کریں</button>
                            <button type="button" class="btn btn-light" data-dismiss="modal">منسوخ کریں</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="customersCsvModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <form action="{{ route('admin.customerscsv') }}" method="post" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header">
                            <h4 class="modal-title"><i class="fas fa-file-excel text-success ml-2"></i> گاہک ایکسل سے درآمد کریں</h4>
                            <button type="button" class="close mr-auto ml-0" data-dismiss="modal" aria-label="بند کریں"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <label for="customersCsvFile" class="font-weight-bold">ایکسل / CSV فائل منتخب کریں</label>
                            <input id="customersCsvFile" type="file" name="csvFile" class="form-control" accept=".csv,.xls,.xlsx" required>
                            <small class="form-text text-muted mt-2">درست کالم ترتیب والی فائل منتخب کریں تاکہ معلومات صحیح شامل ہوں۔</small>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-success"><i class="fas fa-upload ml-1"></i> فائل درآمد کریں</button>
                            <button type="button" class="btn btn-light" data-dismiss="modal">منسوخ کریں</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <script>
        $(document).ready(function () {
            $('#customerDirectorySearch').on('input', function () {
                if ($.fn.DataTable && $.fn.DataTable.isDataTable('#cc-table-data-customer-list')) {
                    $('#cc-table-data-customer-list').DataTable().search(this.value).draw();
                }
            });

            $(document).on('click', '.getCustomer', function () {
                setTimeout(function () {
                    var orderPanel = document.getElementById('orderDetail');
                    if (orderPanel) {
                        orderPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }, 350);
            });
        });
    </script>
@endsection
