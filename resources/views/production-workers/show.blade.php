@extends('main')

@push('styles')
    <style>
        .worker-page {
            --wp-blue: #1769e8;
            --wp-navy: #102a50;
            --wp-green: #12905b;
            --wp-red: #d84a56;
            --wp-muted: #6d7e93;
            --wp-line: #dee7f1;
            min-height: calc(100vh - 65px);
            padding: 26px 0 50px;
            background: #f5f8fc;
            color: var(--wp-navy)
        }

        .worker-shell {
            width: min(100% - 32px, 1500px);
            margin: auto
        }

        .worker-breadcrumb {
            margin-bottom: 12px;
            color: var(--wp-muted);
            font-size: .84rem
        }

        .worker-breadcrumb a {
            color: inherit
        }

        .worker-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 20px
        }

        .worker-identity {
            display: flex;
            align-items: center;
            gap: 14px
        }

        .worker-avatar {
            display: grid;
            place-items: center;
            flex: 0 0 58px;
            height: 58px;
            border-radius: 16px;
            background: linear-gradient(135deg, #2479ee, #0c5bd1);
            color: #fff;
            font: 800 1.3rem Arial, sans-serif;
            box-shadow: 0 9px 22px rgba(23, 105, 232, .2)
        }

        .worker-identity h1 {
            margin: 0 0 5px;
            font-size: 1.6rem;
            font-weight: 900
        }

        .worker-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--wp-muted);
            font-size: .88rem
        }

        .worker-dot {
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: #a7b4c4
        }

        .worker-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 9px;
            border-radius: 999px;
            background: #e8f8f0;
            color: #168653;
            font-size: .74rem;
            font-weight: 800
        }

        .worker-status.is-inactive {
            background: #edf0f4;
            color: #697789
        }

        .worker-header-actions {
            display: flex;
            gap: 9px
        }

        .worker-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 43px;
            padding: 8px 15px;
            border: 1px solid var(--wp-line);
            border-radius: 9px;
            background: #fff;
            color: #53677f !important;
            font-weight: 800
        }

        .worker-btn.is-primary {
            border-color: var(--wp-blue);
            background: var(--wp-blue);
            color: #fff !important;
            box-shadow: 0 7px 17px rgba(23, 105, 232, .18)
        }

        .worker-flash {
            display: flex;
            gap: 10px;
            margin-bottom: 16px;
            padding: 13px 16px;
            border: 1px solid #bce4cf;
            border-radius: 10px;
            background: #edfaf3;
            color: #147044
        }

        .worker-errors {
            margin-bottom: 16px;
            padding: 14px 18px;
            border: 1px solid #f1c6ca;
            border-radius: 10px;
            background: #fff3f4;
            color: #a92d38
        }

        .worker-errors ul {
            margin: 0;
            padding-right: 20px
        }

        .worker-stats {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 14px;
            margin-bottom: 18px
        }

        .worker-stat {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 112px;
            padding: 19px 20px;
            border: 1px solid var(--wp-line);
            border-radius: 13px;
            background: #fff;
            box-shadow: 0 5px 20px rgba(28, 63, 105, .045)
        }

        .worker-stat small {
            display: block;
            color: var(--wp-muted);
            font-weight: 700
        }

        .worker-stat strong {
            display: block;
            margin-top: 7px;
            font: 900 1.25rem/1.3 Arial, Tahoma, sans-serif;
            direction: ltr
        }

        .worker-stat-icon {
            display: grid;
            place-items: center;
            width: 52px;
            height: 52px;
            border-radius: 50%;
            font-size: 20px
        }

        .worker-stat.is-due strong {
            color: var(--wp-red)
        }

        .worker-stat.is-due .worker-stat-icon {
            background: #fff0f1;
            color: var(--wp-red)
        }

        .worker-stat.is-skill .worker-stat-icon {
            background: #eaf2ff;
            color: var(--wp-blue)
        }

        .worker-stat.is-rule .worker-stat-icon {
            background: #e9f9f1;
            color: var(--wp-green)
        }

        .worker-skills {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 7px
        }

        .worker-skill {
            padding: 4px 9px;
            border-radius: 999px;
            background: #edf4ff;
            color: #2465bb;
            font-size: .74rem;
            font-weight: 800
        }

        .worker-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(330px, .7fr);
            gap: 18px;
            margin-bottom: 18px
        }

        .worker-panel {
            overflow: hidden;
            border: 1px solid var(--wp-line);
            border-radius: 13px;
            background: #fff;
            box-shadow: 0 5px 20px rgba(28, 63, 105, .045)
        }

        .worker-panel-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 18px 20px;
            border-bottom: 1px solid var(--wp-line)
        }

        .worker-panel-head h2 {
            margin: 0 0 4px;
            font-size: 1.05rem;
            font-weight: 900
        }

        .worker-panel-head p {
            margin: 0;
            color: var(--wp-muted);
            font-size: .82rem
        }

        .worker-count {
            display: inline-flex;
            padding: 4px 9px;
            border-radius: 999px;
            background: #edf4ff;
            color: #1769e8;
            font: 800 .74rem Arial, Tahoma, sans-serif
        }

        .worker-table {
            margin: 0
        }

        .worker-table thead th {
            padding: 12px 15px;
            border: 0;
            border-bottom: 1px solid var(--wp-line);
            background: #f8fafd;
            color: #596b81;
            font-size: .82rem;
            font-weight: 900;
            white-space: nowrap
        }

        .worker-table td {
            padding: 14px 15px;
            vertical-align: middle;
            border-color: #edf1f6
        }

        .worker-table-primary {
            display: block;
            font-weight: 900
        }

        .worker-table-secondary {
            display: block;
            margin-top: 3px;
            color: var(--wp-muted);
            font-size: .77rem
        }

        .worker-rate-lines {
            display: grid;
            gap: 3px;
            font: 800 .83rem Arial, Tahoma, sans-serif;
            direction: ltr;
            text-align: right
        }

        .worker-plan-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 8px;
            border-radius: 999px;
            background: #e9f9f1;
            color: #168653;
            font-size: .72rem;
            font-weight: 900
        }

        .worker-plan-status.is-old {
            background: #edf0f4;
            color: #6e7b8b
        }

        .worker-empty {
            padding: 42px 18px !important;
            text-align: center;
            color: #8492a5
        }

        .worker-empty i {
            display: block;
            margin-bottom: 9px;
            color: #bdc8d6;
            font-size: 28px
        }

        .worker-form {
            padding: 19px 20px
        }

        .worker-form .form-group {
            margin-bottom: 15px
        }

        .worker-form label {
            margin-bottom: 6px;
            color: #344a67;
            font-size: .84rem;
            font-weight: 900
        }

        .worker-form .form-control {
            min-height: 44px;
            border-color: #d5dfeb;
            border-radius: 8px;
            box-shadow: none
        }

        .worker-form .form-control:focus {
            border-color: #70a6ff;
            box-shadow: 0 0 0 3px rgba(23, 105, 232, .1)
        }

        .worker-form small {
            line-height: 1.7
        }

        .worker-form-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px
        }

        .worker-form-fields .form-group {
            margin-bottom: 0
        }

        .worker-money {
            position: relative
        }

        .worker-money .form-control {
            direction: ltr;
            padding-left: 44px;
            text-align: left
        }

        .worker-money span {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #8a97a8;
            font: 700 .75rem Arial, sans-serif
        }

        .worker-submit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            width: 100%;
            min-height: 44px;
            border: 0;
            border-radius: 8px;
            background: var(--wp-blue);
            color: #fff;
            font-weight: 900
        }

        .worker-submit.is-success {
            background: var(--wp-green)
        }

        .worker-form-note {
            display: flex;
            gap: 8px;
            margin: 2px 0 16px;
            padding: 10px 12px;
            border-radius: 8px;
            background: #f2f7ff;
            color: #49627e;
            font-size: .78rem;
            line-height: 1.8
        }

        .worker-ledger-amount {
            font: 900 .85rem Arial, Tahoma, sans-serif;
            direction: ltr;
            white-space: nowrap
        }

        .worker-ledger-amount.is-due {
            color: var(--wp-red)
        }

        .worker-ledger-amount.is-paid {
            color: var(--wp-green)
        }

        .worker-entry-type {
            display: inline-flex;
            padding: 4px 8px;
            border-radius: 999px;
            background: #fff2e6;
            color: #b96516;
            font-size: .72rem;
            font-weight: 900
        }

        .worker-entry-type.is-payment {
            background: #e9f9f1;
            color: #168653
        }

        .worker-pagination {
            padding: 13px 18px;
            border-top: 1px solid var(--wp-line)
        }

        .worker-zero-balance {
            margin: 18px;
            padding: 24px 17px;
            border: 1px dashed #b9d9ca;
            border-radius: 10px;
            background: #f3fbf7;
            text-align: center;
            color: #347057
        }

        .worker-zero-balance i {
            display: block;
            margin-bottom: 8px;
            color: #2b9b67;
            font-size: 25px
        }

        .worker-zero-balance strong {
            display: block;
            margin-bottom: 4px
        }

        .worker-payment-help {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
            padding: 11px 12px;
            border-radius: 9px;
            background: #fff4f5;
            color: #a83843
        }

        .worker-payment-help strong {
            direction: ltr;
            font-family: Arial, Tahoma, sans-serif
        }

        @media(max-width:1050px) {
            .worker-grid {
                grid-template-columns: 1fr
            }

            .worker-stats {
                grid-template-columns: 1fr 1fr
            }

            .worker-stat:first-child {
                grid-column: 1/-1
            }
        }

        @media(max-width:767.98px) {
            .worker-page {
                padding-top: 17px
            }

            .worker-shell {
                width: min(100% - 22px, 1500px)
            }

            .worker-header {
                align-items: stretch;
                flex-direction: column
            }

            .worker-header-actions {
                flex-direction: column
            }

            .worker-btn {
                width: 100%
            }

            .worker-stats {
                grid-template-columns: 1fr
            }

            .worker-stat:first-child {
                grid-column: auto
            }

            .worker-form-fields {
                grid-template-columns: 1fr
            }

            .worker-table,
            .worker-table tbody,
            .worker-table tr,
            .worker-table td {
                display: block;
                width: 100%
            }

            .worker-table thead {
                display: none
            }

            .worker-table tr {
                margin: 10px;
                width: calc(100% - 20px);
                padding: 7px;
                border: 1px solid var(--wp-line);
                border-radius: 10px
            }

            .worker-table td {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                padding: 9px 8px;
                border-top: 1px solid #edf1f6
            }

            .worker-table td:first-child {
                border-top: 0
            }

            .worker-table td::before {
                content: attr(data-label);
                flex: 0 0 34%;
                color: var(--wp-muted);
                font-size: .77rem;
                font-weight: 900
            }

            .worker-empty {
                display: block !important
            }

            .worker-empty::before {
                display: none
            }
        }
    </style>
@endpush

@section('content')
    @php
        $methodLabels = [
            'fixed_salary' => 'ماہانہ تنخواہ',
            'per_piece' => 'فی عدد',
            'commission' => 'کمیشن',
            'hybrid' => 'مشترکہ',
        ];
        $entryLabels = [
            'earning' => 'کمائی',
            'payment' => 'ادائیگی',
            'salary' => 'تنخواہ',
            'commission' => 'کمیشن',
            'advance' => 'ایڈوانس',
            'adjustment' => 'ایڈجسٹمنٹ',
        ];
        $activePlans = $worker->compensationPlans->where('active', true);
    @endphp
    <section class="main-content worker-page" dir="rtl">
        <div class="worker-shell">
            <div class="worker-breadcrumb"><a href="{{ route('admin.home') }}">ڈیش بورڈ</a><span class="mx-2">‹</span><a
                    href="{{ route('admin.production-workers.index') }}">پروڈکشن ورکرز</a><span
                    class="mx-2">‹</span>{{ $worker->name }}</div>
            <header class="worker-header">
                <div class="worker-identity"><span class="worker-avatar">{{ mb_substr($worker->name, 0, 1) }}</span>
                    <div>
                        <h1>{{ $worker->name }}</h1>
                        <div class="worker-meta">
                            <span>{{ $worker->relationship_type === 'employee' ? 'تنخواہ دار ملازم' : 'آزاد کاریگر' }}</span><span
                                class="worker-dot"></span><span
                                dir="ltr">{{ $worker->phone ?: 'فون درج نہیں' }}</span><span
                                class="worker-status {{ $worker->active ? '' : 'is-inactive' }}"><i
                                    class="fas fa-circle"></i>{{ $worker->active ? 'فعال' : 'غیر فعال' }}</span></div>
                    </div>
                </div>
                <div class="worker-header-actions"><a class="worker-btn"
                        href="{{ route('admin.production-workers.index') }}"><i class="fas fa-arrow-right"></i> ورکرز کی
                        فہرست</a><a class="worker-btn is-primary"
                        href="{{ route('admin.production-workers.edit', $worker) }}"><i class="fas fa-user-edit"></i>
                        معلومات تبدیل کریں</a></div>
            </header>

            @if (session('success'))
                <div class="worker-flash"><i class="fas fa-check-circle"></i><span>{{ session('success') }}</span></div>
            @endif
            @if ($errors->any())
                <div class="worker-errors" role="alert"><strong>درج ذیل معلومات درست کریں:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="worker-stats">
                <article class="worker-stat is-due">
                    <div><small>موجودہ واجب الادا رقم</small><strong>Rs. {{ number_format($balance, 2) }}</strong></div>
                    <span class="worker-stat-icon"><i class="fas fa-wallet"></i></span>
                </article>
                <article class="worker-stat is-skill">
                    <div><small>کام کی مہارتیں</small>
                        <div class="worker-skills">
                            @forelse($worker->skills as $skill)
                            <span class="worker-skill">{{ $skill->name }}</span>@empty<span class="text-muted">کوئی
                                    مہارت درج نہیں</span>
                            @endforelse
                        </div>
                    </div><span class="worker-stat-icon"><i class="fas fa-tools"></i></span>
                </article>
                <article class="worker-stat is-rule">
                    <div><small>فعال اجرت کے اصول</small><strong>{{ $activePlans->count() }}</strong></div><span
                        class="worker-stat-icon"><i class="fas fa-coins"></i></span>
                </article>
            </div>

            <div class="worker-grid">
                <section class="worker-panel">
                    <div class="worker-panel-head">
                        <div>
                            <h2>موجودہ اجرت کے اصول</h2>
                            <p>ہر کام کے لیے ورکر کو ملنے والی موجودہ شرح</p>
                        </div><span class="worker-count">{{ $worker->compensationPlans->count() }} اصول</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table worker-table">
                            <thead>
                                <tr>
                                    <th>کام</th>
                                    <th>طریقۂ اجرت</th>
                                    <th>شرح</th>
                                    <th>حالت</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($worker->compensationPlans->sortByDesc('id') as $plan)
                                    <tr>
                                        <td data-label="کام"><span
                                                class="worker-table-primary">{{ $plan->workType->name ?? 'تمام کام' }}</span>
                                        </td>
                                        <td data-label="طریقۂ اجرت">{{ $methodLabels[$plan->method] ?? $plan->method }}
                                        </td>
                                        <td data-label="شرح">
                                            <div class="worker-rate-lines">
                                                @if ((float) $plan->fixed_salary > 0)
                                                    <span>Rs. {{ number_format((float) $plan->fixed_salary, 2) }} /
                                                        ماہ</span>
                                                    @endif @if ((float) $plan->rate > 0)
                                                        <span>Rs. {{ number_format((float) $plan->rate, 2) }} / عدد</span>
                                                        @endif @if ((float) $plan->commission_percent > 0)
                                                            <span>{{ number_format((float) $plan->commission_percent, 2) }}%</span>
                                                        @endif
                                            </div>
                                        </td>
                                        <td data-label="حالت"><span
                                                class="worker-plan-status {{ $plan->active ? '' : 'is-old' }}"><i
                                                    class="fas fa-circle"></i>{{ $plan->active ? 'فعال' : 'پرانا' }}</span>
                                        </td>
                                    </tr>
                                @empty<tr>
                                        <td colspan="4" class="worker-empty"><i class="fas fa-coins"></i>ابھی اجرت مقرر
                                            نہیں۔ دائیں طرف فارم سے پہلا اصول بنائیں۔</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <aside class="worker-panel">
                    <div class="worker-panel-head">
                        <div>
                            <h2>اجرت کا نیا اصول</h2>
                            <p>کام اور ادائیگی کا طریقہ منتخب کریں</p>
                        </div><span class="worker-count"><i class="fas fa-plus"></i></span>
                    </div>
                    <form class="worker-form" method="POST"
                        action="{{ route('admin.production-workers.compensation.store', $worker) }}">@csrf
                        <div class="form-group"><label for="worker_work_type">کس کام کی اجرت؟</label><select
                                id="worker_work_type" name="work_type_id" class="form-control" required style="padding-top: 0px;">
                                @foreach ($workTypes as $type)
                                    <option value="{{ $type->id }}" @selected(old('work_type_id') == $type->id)>{{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group"><label for="worker_compensation_method">ادائیگی کیسے ہوگی؟</label><select
                                id="worker_compensation_method" name="method" class="form-control" required style="padding-top: 0px;">
                                <option value="per_piece" @selected(old('method', 'per_piece') === 'per_piece')>ہر تیار عدد کے حساب سے</option>
                                <option value="fixed_salary" @selected(old('method') === 'fixed_salary')>ماہانہ مقررہ تنخواہ</option>
                                <option value="commission" @selected(old('method') === 'commission')>فیصد کمیشن</option>
                                <option value="hybrid" @selected(old('method') === 'hybrid')>مشترکہ طریقہ</option>
                            </select></div>
                        <div id="worker_compensation_help" class="worker-form-note"><i
                                class="fas fa-info-circle"></i><span>ہر تیار شدہ عدد کی اجرت درج کریں۔</span></div>
                        <div class="worker-form-fields">
                            <div class="form-group" data-compensation-field="rate"><label for="worker_piece_rate">فی عدد
                                    رقم</label>
                                <div class="worker-money"><span>Rs.</span><input id="worker_piece_rate" type="number"
                                        name="rate" min="0" step="0.01" class="form-control"
                                        value="{{ old('rate', 0) }}"></div>
                            </div>
                            <div class="form-group" data-compensation-field="fixed_salary"><label
                                    for="worker_fixed_salary">ماہانہ تنخواہ</label>
                                <div class="worker-money"><span>Rs.</span><input id="worker_fixed_salary" type="number"
                                        name="fixed_salary" min="0" step="0.01" class="form-control"
                                        value="{{ old('fixed_salary', 0) }}"></div>
                            </div>
                            <div class="form-group" data-compensation-field="commission"><label
                                    for="worker_commission">کمیشن فیصد</label><input id="worker_commission"
                                    type="number" name="commission_percent" min="0" max="100"
                                    step="0.01" class="form-control" value="{{ old('commission_percent', 0) }}"></div>
                            <div class="form-group"><label for="worker_effective_from">کب سے نافذ ہوگا؟</label><input
                                    id="worker_effective_from" type="date" name="effective_from" class="form-control"
                                    value="{{ old('effective_from', now()->toDateString()) }}"></div>
                        </div>
                        <button class="worker-submit mt-3" type="submit"><i class="fas fa-check"></i> اجرت کا اصول محفوظ
                            کریں</button>
                    </form>
                </aside>
            </div>

            <div class="worker-grid">
                <section class="worker-panel">
                    <div class="worker-panel-head">
                        <div>
                            <h2>ورکر کا کھاتہ</h2>
                            <p>کمائی، ادائیگی اور دوسری مالی سرگرمی</p>
                        </div><span class="worker-count">{{ $entries->total() }} اندراجات</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table worker-table">
                            <thead>
                                <tr>
                                    <th>تاریخ</th>
                                    <th>قسم</th>
                                    <th>حوالہ / تفصیل</th>
                                    <th>رقم</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($entries as $entry)
                                    <tr>
                                        <td data-label="تاریخ"><span class="worker-table-primary"
                                                dir="ltr">{{ $entry->entry_date->format('d-m-Y') }}</span></td>
                                        <td data-label="قسم"><span
                                                class="worker-entry-type {{ $entry->entry_type === 'payment' ? 'is-payment' : '' }}">{{ $entryLabels[$entry->entry_type] ?? $entry->entry_type }}</span>
                                        </td>
                                        <td data-label="حوالہ / تفصیل">
                                            @if ($entry->assignment)
                                                <a class="worker-table-primary"
                                                    href="{{ route('admin.order.edit', $entry->assignment->order_id) }}">آرڈر
                                                    #{{ $entry->assignment->order_id }}</a>@else{{ $entry->notes ?: '—' }}
                                            @endif
                                        </td>
                                        <td data-label="رقم"><span
                                                class="worker-ledger-amount {{ (float) $entry->amount >= 0 ? 'is-due' : 'is-paid' }}">{{ (float) $entry->amount >= 0 ? '+' : '−' }}
                                                Rs. {{ number_format(abs((float) $entry->amount), 2) }}</span></td>
                                    </tr>
                                @empty<tr>
                                        <td colspan="4" class="worker-empty"><i class="fas fa-receipt"></i>ابھی کوئی
                                            کھاتہ اندراج نہیں۔</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($entries->hasPages())
                        <div class="worker-pagination">{{ $entries->links() }}</div>
                    @endif
                </section>

                <aside class="worker-panel">
                    <div class="worker-panel-head">
                        <div>
                            <h2>ادائیگی درج کریں</h2>
                            <p>ورکر کو ادا کی گئی رقم کھاتے میں محفوظ کریں</p>
                        </div><span class="worker-count"><i class="fas fa-hand-holding-usd"></i></span>
                    </div>
                    @if ($balance > 0)
                        <form class="worker-form" method="POST"
                            action="{{ route('admin.production-workers.payments.store', $worker) }}">@csrf<div
                                class="worker-payment-help"><span>زیادہ سے زیادہ ادائیگی</span><strong>Rs.
                                    {{ number_format($balance, 2) }}</strong></div>
                            <div class="worker-form-fields">
                                <div class="form-group"><label for="worker_payment_amount">ادا کی گئی رقم</label>
                                    <div class="worker-money"><span>Rs.</span><input id="worker_payment_amount"
                                            type="number" name="amount" min="0.01" max="{{ max(0, $balance) }}"
                                            step="0.01" class="form-control" value="{{ old('amount') }}" required>
                                    </div>
                                </div>
                                <div class="form-group"><label for="worker_payment_date">ادائیگی کی تاریخ</label><input
                                        id="worker_payment_date" type="date" name="entry_date" class="form-control"
                                        value="{{ old('entry_date', now()->toDateString()) }}" required></div>
                            </div>@include('components.payment-method-fields', ['prefix' => 'worker_payment'])<div class="form-group"><label
                                    for="worker_payment_notes">نوٹ <span
                                        class="text-muted font-weight-normal">(اختیاری)</span></label>
                                <textarea id="worker_payment_notes" name="notes" class="form-control" rows="2" maxlength="500"
                                    placeholder="ادائیگی کی مختصر تفصیل">{{ old('notes') }}</textarea>
                            </div><button class="worker-submit is-success" type="submit"><i class="fas fa-check"></i>
                                ادائیگی محفوظ کریں</button>
                        </form>
                    @else<div class="worker-zero-balance" role="status"><i
                                class="fas fa-check-circle"></i><strong>کوئی رقم واجب الادا نہیں</strong><span>اس ورکر کی
                                کوئی واجب الادا رقم نہیں۔ کام مکمل ہونے اور اجرت درج ہونے کے بعد ادائیگی یہاں دستیاب
                                ہوگی۔</span></div>
                    @endif
                </aside>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const method = document.getElementById('worker_compensation_method');
            const help = document.getElementById('worker_compensation_help');
            const groups = document.querySelectorAll('[data-compensation-field]');
            if (!method || !help || !groups.length) return;
            const visibleFields = {
                per_piece: ['rate'],
                fixed_salary: ['fixed_salary'],
                commission: ['commission'],
                hybrid: ['rate', 'fixed_salary', 'commission']
            };
            const helpText = {
                per_piece: 'ہر تیار شدہ عدد کی اجرت درج کریں۔',
                fixed_salary: 'ورکر کی ماہانہ مقررہ تنخواہ درج کریں۔',
                commission: 'کام یا آمدن میں سے کمیشن فیصد درج کریں۔',
                hybrid: 'مشترکہ اصول میں لاگو ہونے والی رقم یا فیصد درج کریں۔'
            };

            function syncFields() {
                const active = visibleFields[method.value] || [];
                groups.forEach(group => {
                    const visible = active.includes(group.dataset.compensationField);
                    group.hidden = !visible;
                    const input = group.querySelector('input');
                    if (input) input.disabled = !visible;
                });
                help.querySelector('span').textContent = helpText[method.value] || '';
            }
            method.addEventListener('change', syncFields);
            syncFields();
        });
    </script>
@endpush
