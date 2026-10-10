@extends('main')

@section('content')
    @php
        $employeeLimit = $business->subscriptionLimit('max_employees');
        $activeEmployeeCount = $business->members->where('employee_active', true)->count();
        $employeeLimitReached = $employeeLimit !== null && $activeEmployeeCount >= $employeeLimit;
        $tailorLimit = $business->subscriptionLimit('max_tailors');
        $tailorLimitReached = $tailorLimit !== null && $tailors->count() >= $tailorLimit;
        $canCreateStaff = $business->roles->isNotEmpty() || $business->clothing_enabled;
        $selectedTab = request('tab', 'all');
        $oldPersonType = old('people_type', request('type', 'staff'));
    @endphp
    <style>
        .people-page {
            --pp-blue: #1769e0;
            --pp-navy: #102a50;
            --pp-muted: #687b91;
            --pp-line: #dfe8f3;
            background: #f4f7fb;
            min-height: calc(100vh - 70px);
            padding: 24px 0 52px
        }

        .people-shell {
            width: min(100% - 32px, 1500px);
            margin-inline: auto
        }

        .people-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 22px 26px;
            border-radius: 20px;
            color: #fff;
            background: linear-gradient(135deg, #102a50, #1769e0);
            box-shadow: 0 16px 36px rgba(16, 42, 80, .17)
        }

        .people-hero h1 {
            margin: 0 0 6px;
            font-size: 1.7rem;
            font-weight: 800
        }

        .people-hero p {
            margin: 0;
            color: rgba(255, 255, 255, .82)
        }

        .people-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 9px
        }

        .people-actions .btn {
            border-radius: 10px;
            font-weight: 800;
            white-space: nowrap
        }

        .people-actions .btn-light {
            color: var(--pp-blue)
        }

        .people-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin: 16px 0
        }

        .people-stat {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 17px;
            border: 1px solid var(--pp-line);
            border-radius: 15px;
            background: #fff
        }

        .people-stat i {
            display: grid;
            place-items: center;
            width: 42px;
            height: 42px;
            border-radius: 12px;
            color: var(--pp-blue);
            background: #eaf3ff
        }

        .people-stat strong {
            display: block;
            color: var(--pp-navy);
            font-size: 1.2rem
        }

        .people-stat small {
            color: var(--pp-muted)
        }

        .people-panel {
            overflow: hidden;
            border: 1px solid var(--pp-line);
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 9px 28px rgba(25, 52, 84, .06)
        }

        .people-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 17px 19px;
            border-bottom: 1px solid var(--pp-line);
            background: #fbfdff
        }

        .people-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 7px
        }

        .people-tab {
            border: 1px solid #cddbeb;
            border-radius: 999px;
            padding: 7px 13px;
            color: #4e6680;
            background: #fff;
            font-size: .78rem;
            font-weight: 800
        }

        .people-tab.is-active {
            border-color: var(--pp-blue);
            color: #fff;
            background: var(--pp-blue)
        }

        .people-search {
            position: relative;
            flex: 0 1 320px
        }

        .people-search i {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: #8ca0b5
        }

        .people-search input {
            height: 42px;
            padding-right: 38px;
            border-color: #d8e3ef;
            border-radius: 11px
        }

        .people-table th {
            border-top: 0;
            color: #5d7188;
            background: #f6f9fc;
            font-size: .76rem
        }

        .people-table td {
            vertical-align: middle;
            border-color: #edf1f6
        }

        .person-main {
            display: flex;
            align-items: center;
            gap: 11px
        }

        .person-avatar {
            display: grid;
            place-items: center;
            flex: 0 0 42px;
            width: 42px;
            height: 42px;
            border-radius: 12px;
            color: #1769e0;
            background: #eaf3ff;
            font-weight: 900
        }

        .person-name {
            display: block;
            color: var(--pp-navy);
            font-weight: 800
        }

        .person-contact {
            display: block;
            color: #7a8b9d;
            font-size: .73rem;
            direction: ltr;
            text-align: right
        }

        .person-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 5px
        }

        .person-badge {
            padding: 4px 8px;
            border-radius: 999px;
            color: #31506e;
            background: #edf3f9;
            font-size: .69rem;
            font-weight: 800
        }

        .person-badge.is-sales {
            color: #077647;
            background: #e4f7ee
        }

        .person-badge.is-tailor {
            color: #7443b8;
            background: #f0e9ff
        }

        .person-badge.is-production {
            color: #9b5b08;
            background: #fff2dc
        }

        .person-state {
            font-size: .72rem;
            font-weight: 800
        }

        .person-state.is-active {
            color: #087d4b
        }

        .person-state.is-off {
            color: #b33a3a
        }

        .person-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #cbd9e9;
            border-radius: 9px;
            padding: 7px 10px;
            color: var(--pp-blue);
            font-size: .72rem;
            font-weight: 800;
            text-decoration: none !important
        }

        .people-empty {
            padding: 54px 20px;
            text-align: center;
            color: #8091a4
        }

        .people-empty i {
            display: block;
            margin-bottom: 10px;
            font-size: 2rem;
            color: #c2cfdd
        }

        .people-modal .modal-dialog {
            max-width: 900px
        }

        .people-modal .modal-content {
            max-height: calc(100vh - 30px);
            overflow: hidden;
            border: 0;
            border-radius: 18px
        }

        .people-modal .modal-header {
            flex: 0 0 auto;
            border-bottom: 1px solid var(--pp-line)
        }

        .people-kind-picker {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 9px;
            padding: 14px 18px;
            border-bottom: 1px solid var(--pp-line);
            background: #f7faff
        }

        .people-kind {
            display: flex;
            align-items: center;
            gap: 9px;
            margin: 0;
            padding: 11px;
            border: 1px solid #d5e1ee;
            border-radius: 11px;
            background: #fff;
            cursor: pointer
        }

        .people-kind input {
            position: absolute;
            opacity: 0
        }

        .people-kind i {
            display: grid;
            place-items: center;
            width: 35px;
            height: 35px;
            border-radius: 10px;
            color: var(--pp-blue);
            background: #eaf3ff
        }

        .people-kind strong {
            display: block;
            color: var(--pp-navy);
            font-size: .78rem
        }

        .people-kind small {
            display: block;
            color: var(--pp-muted);
            font-size: .65rem
        }

        .people-kind.is-selected {
            border-color: var(--pp-blue);
            box-shadow: 0 0 0 2px rgba(23, 105, 224, .11)
        }

        .people-form-pane {
            display: none;
            min-height: 0;
            flex: 1
        }

        .people-form-pane.is-active {
            display: flex;
            flex-direction: column
        }

        .people-form-body {
            overflow-y: auto;
            padding: 18px
        }

        .people-form-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex: 0 0 auto;
            padding: 13px 18px;
            border-top: 1px solid var(--pp-line);
            background: #fff;
            box-shadow: 0 -7px 16px rgba(30, 55, 85, .06)
        }

        .people-form-footer small {
            color: var(--pp-muted)
        }

        .people-form-footer .btn {
            min-width: 130px;
            font-weight: 800
        }

        .people-field-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px
        }

        .people-field-grid .is-wide {
            grid-column: 1/-1
        }

        .people-form-body label {
            color: #344d68;
            font-size: .76rem;
            font-weight: 800
        }

        .people-form-body .form-control {
            min-height: 45px;
            border-color: #d6e1ed;
            border-radius: 10px;
            padding-top: .42rem;
            padding-bottom: .42rem
        }

        .people-section {
            margin-top: 7px;
            padding: 14px;
            border: 1px solid #e0e8f2;
            border-radius: 12px;
            background: #fbfdff
        }

        .people-skills {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px
        }

        .people-skill {
            display: flex;
            align-items: center;
            gap: 7px;
            margin: 0;
            padding: 9px;
            border: 1px solid #dce5ef;
            border-radius: 9px;
            background: #fff;
            font-weight: 700 !important
        }

        @media(max-width:900px) {
            .people-stats {
                grid-template-columns: 1fr 1fr
            }

            .people-toolbar {
                align-items: stretch;
                flex-direction: column
            }

            .people-search {
                flex-basis: auto
            }

            .people-table thead {
                display: none
            }

            .people-table,
            .people-table tbody,
            .people-table tr,
            .people-table td {
                display: block;
                width: 100%
            }

            .people-table tr {
                padding: 12px;
                border-bottom: 1px solid var(--pp-line)
            }

            .people-table td {
                display: flex;
                justify-content: space-between;
                gap: 12px;
                padding: 7px;
                border: 0
            }

            .people-table td:before {
                content: attr(data-label);
                color: #6d8095;
                font-size: .72rem;
                font-weight: 800
            }

            .people-table td:first-child {
                display: block
            }

            .people-table td:first-child:before {
                display: none
            }
        }

        @media(max-width:650px) {
            .people-page {
                padding-top: 12px
            }

            .people-shell {
                width: min(100% - 18px, 1500px)
            }

            .people-hero {
                align-items: flex-start;
                flex-direction: column;
                padding: 18px
            }

            .people-stats {
                grid-template-columns: 1fr 1fr
            }

            .people-kind-picker,
            .people-field-grid,
            .people-skills {
                grid-template-columns: 1fr
            }

            .people-form-footer small {
                display: none
            }

            .people-form-footer .btn {
                flex: 1
            }

            .people-actions {
                width: 100%
            }

            .people-actions .btn {
                flex: 1
            }

            .people-stat {
                padding: 12px
            }

            .people-stat i {
                display: none
            }
        }
    </style>

    <section class="main-content people-page" dir="rtl">
        <div class="people-shell">
            <header class="people-hero">
                <div><span class="badge badge-light text-primary mb-2">افراد کا مرکزی انتظام</span>
                    <h1>ٹیم اور کاریگر</h1>
                    <p>{{ $business->clothing_enabled ? 'سیلز عملہ، درزی اور تمام پروڈکشن کاریگر ایک ہی فہرست میں۔' : 'دفتری / کارخانے کا عملہ، درزی اور تمام کاریگر ایک ہی فہرست میں۔' }}
                    </p>
                </div>
                <div class="people-actions">
                    @if ($business->tailoring_enabled)
                        <a class="btn btn-outline-light" href="{{ route('admin.order.total') }}"><i
                                class="fas fa-calendar-week ml-1"></i> ٹیلرنگ ورک فلو</a>
                    @endif
                    <a class="btn btn-outline-light" href="{{ route('admin.team.roles.index') }}">
                        <i class="fas fa-user-shield ml-1"></i> رولز اور اجازتیں</a><button class="btn btn-light"
                        type="button" data-toggle="modal" data-target="#addPersonModal"><i
                            class="fas fa-user-plus ml-1"></i> نیا فرد شامل کریں</button>
                </div>
            </header>
            @if (session('success') || session('insert'))
                <div class="alert alert-success mt-3">{{ session('success') ?: session('insert') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger mt-3"><strong>معلومات محفوظ نہیں ہو سکیں۔</strong>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="people-stats">
                <div class="people-stat"><i class="fas fa-users"></i>
                    <div>
                        <strong>{{ $business->members->count() + $tailors->count() + $productionWorkers->count() }}</strong><small>کل
                            افراد</small></div>
                </div>
                <div class="people-stat"><i class="fas fa-cash-register"></i>
                    <div>
                        <strong>{{ $business->clothing_enabled ? $business->members->filter(fn($member) => $member->businessRole?->hasPermission('clothing.sales'))->count() : $business->members->count() }}</strong><small>{{ $business->clothing_enabled ? 'سیلز عملہ' : 'دفتری / کارخانے کا عملہ' }}</small>
                    </div>
                </div>
                <div class="people-stat"><i class="fas fa-user-tie"></i>
                    <div><strong>{{ $tailors->count() }}</strong><small>درزی</small></div>
                </div>
                <div class="people-stat"><i class="fas fa-tools"></i>
                    <div><strong>{{ $productionWorkers->count() }}</strong><small>دیگر کاریگر</small></div>
                </div>
            </div>
            <section class="people-panel">
                <div class="people-toolbar">
                    <div class="people-tabs" role="tablist"><button class="people-tab" type="button" data-filter="all">تمام
                            افراد</button><button class="people-tab" type="button"
                            data-filter="staff">{{ $business->clothing_enabled ? 'سیلز اور دفتری عملہ' : 'دفتری / کارخانے کا عملہ' }}</button>
                        @if ($business->clothing_enabled)
                            <button class="people-tab" type="button" data-filter="sales">صرف سیلز</button>
                            @endif @if ($business->tailoring_enabled)
                                <button class="people-tab" type="button" data-filter="tailors">درزی</button><button
                                    class="people-tab" type="button" data-filter="production">دیگر کاریگر</button>
                            @endif
                    </div>
                    <div class="people-search"><i class="fas fa-search"></i><input id="peopleSearch" class="form-control"
                            type="search" placeholder="نام، فون، رول یا کام تلاش کریں"></div>
                </div>
                <div class="table-responsive">
                    <table class="table people-table mb-0">
                        <thead>
                            <tr>
                                <th>نام اور رابطہ</th>
                                <th>قسم / کام</th>
                                <th>اکاؤنٹ یا کام</th>
                                <th>حالت</th>
                                <th>عمل</th>
                            </tr>
                        </thead>
                        <tbody id="peopleRows">
                            @foreach ($business->members as $employee)
                                @php($isSales = $business->clothing_enabled && $employee->businessRole?->hasPermission('clothing.sales'))
                                <tr class="person-row" data-kinds="staff {{ $isSales ? 'sales' : '' }}"
                                    data-search="{{ mb_strtolower($employee->name . ' ' . $employee->phone . ' ' . $employee->email . ' ' . $employee->job_title . ' ' . ($employee->businessRole?->name ?? '')) }}">
                                    <td data-label="نام">
                                        <div class="person-main"><span
                                                class="person-avatar">{{ mb_substr($employee->name, 0, 1) }}</span>
                                            <div><span class="person-name">{{ $employee->name }}</span><span
                                                    class="person-contact">{{ $employee->phone ?: $employee->email }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="قسم">
                                        <div class="person-badges">
                                            @if ($isSales)
                                                <span class="person-badge is-sales">سیلز ایجنٹ</span>
                                            @endif
                                            <span class="person-badge">
                                                {{ $employee->job_title ?: 'دفتری ملازم' }}</span>
                                        </div>
                                    </td>
                                    <td data-label="رسائی">
                                        <strong>{{ $employee->businessRole?->name ?: 'رول مقرر نہیں' }}</strong><br><small
                                            class="text-muted" dir="ltr">{{ '@' . $employee->username }}</small></td>
                                    <td data-label="حالت"><span
                                            class="person-state {{ $employee->employee_active ? 'is-active' : 'is-off' }}">{{ $employee->employee_active ? 'لاگ اِن فعال' : 'اکاؤنٹ بند' }}</span>
                                    </td>
                                    <td data-label="عمل"><a class="person-action"
                                            href="{{ route('admin.team.employees.edit', $employee) }}"><i
                                                class="fas fa-user-shield"></i> معلومات اور رسائی</a></td>
                                </tr>
                            @endforeach
                            @foreach ($tailors as $tailor)
                                @php($skills = $tailor->productionWorker?->skills?->pluck('name')->all() ?? ['سلائی'])
                                <tr class="person-row" data-kinds="tailors"
                                    data-search="{{ mb_strtolower($tailor->name . ' ' . $tailor->phone_number1 . ' ' . implode(' ', $skills)) }}">
                                    <td data-label="نام">
                                        <div class="person-main"><span
                                                class="person-avatar">{{ mb_substr($tailor->name, 0, 1) }}</span>
                                            <div><span class="person-name">{{ $tailor->name }}</span><span
                                                    class="person-contact">{{ $tailor->phone_number1 }}</span></div>
                                        </div>
                                    </td>
                                    <td data-label="قسم">
                                        <div class="person-badges"><span class="person-badge is-tailor">درزی</span>
                                            @foreach ($skills as $skill)
                                                <span class="person-badge">{{ $skill }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td data-label="کام"><strong>{{ $tailor->orders_count }} آرڈرز</strong><br><small
                                            class="text-muted">{{ $tailor->tailorsalary->count() }} محفوظ اجرتیں</small>
                                    </td>
                                    <td data-label="حالت"><span class="person-state is-active">فعال</span></td>
                                    <td data-label="عمل"><a class="person-action"
                                            href="{{ route('admin.tailor-report', $tailor) }}"><i class="fas fa-wallet"></i>
                                            کھاتہ اور اجرت</a>
                                        <a class="person-action"
                                            href="{{ route('admin.tailor-rates', $tailor->id) }}"><i class="fas fa-wallet"></i>
                                            ریٹس دیکھیں</a>
                                    </td>
                                </tr>
                            @endforeach
                            @foreach ($productionWorkers as $worker)
                                <tr class="person-row" data-kinds="production"
                                    data-search="{{ mb_strtolower($worker->name . ' ' . $worker->phone . ' ' . $worker->email . ' ' . $worker->skills->pluck('name')->implode(' ')) }}">
                                    <td data-label="نام">
                                        <div class="person-main"><span
                                                class="person-avatar">{{ mb_substr($worker->name, 0, 1) }}</span>
                                            <div><span class="person-name">{{ $worker->name }}</span><span
                                                    class="person-contact">{{ $worker->phone ?: ($worker->email ?: 'رابطہ درج نہیں') }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="قسم">
                                        <div class="person-badges"><span class="person-badge is-production">پروڈکشن
                                                کاریگر</span>
                                            @foreach ($worker->skills as $skill)
                                                <span class="person-badge">{{ $skill->name }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td data-label="کام"><strong>{{ $worker->active_assignments_count }} جاری
                                            کام</strong><br><small class="text-muted">واجب الادا Rs.
                                            {{ number_format(max(0, (float) $worker->ledger_balance), 2) }}</small></td>
                                    <td data-label="حالت"><span
                                            class="person-state {{ $worker->active ? 'is-active' : 'is-off' }}">{{ $worker->active ? 'فعال' : 'غیر فعال' }}</span>
                                    </td>
                                    <td data-label="عمل"><a class="person-action"
                                            href="{{ route('admin.production-workers.show', $worker) }}"><i
                                                class="fas fa-wallet"></i> کھاتہ اور اجرت</a></td>
                                </tr>
                            @endforeach
                            <tr id="peopleEmpty" hidden>
                                <td colspan="5">
                                    <div class="people-empty"><i class="fas fa-search"></i>اس فلٹر میں کوئی فرد موجود
                                        نہیں۔</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </section>

    <div class="modal fade people-modal" id="addPersonModal" tabindex="-1" role="dialog"
        aria-labelledby="addPersonModalTitle" aria-hidden="true" dir="rtl">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header align-items-center">
                    <h5 class="modal-title font-weight-bold" id="addPersonModalTitle"><i
                            class="fas fa-user-plus text-primary ml-2"></i>نیا فرد شامل کریں</h5><button type="button"
                        class="close mr-auto ml-0" data-dismiss="modal" aria-label="بند کریں">&times;</button>
                </div>
                <div class="people-kind-picker"><label class="people-kind" data-kind="staff"><input type="radio"
                            name="person_kind" value="staff"><i
                            class="fas fa-cash-register"></i><span><strong>{{ $business->clothing_enabled ? 'سیلز / دفتری عملہ' : 'دفتری / کارخانے کا عملہ' }}</strong><small>لاگ
                                اِن اور اجازتوں کے ساتھ</small></span></label>
                    @if ($business->tailoring_enabled)
                        <label class="people-kind" data-kind="tailor"><input type="radio" name="person_kind"
                                value="tailor"><i class="fas fa-user-tie"></i><span><strong>درزی</strong><small>سلائی،
                                    اجرت اور پورٹل</small></span></label><label class="people-kind"
                            data-kind="production"><input type="radio" name="person_kind" value="production"><i
                                class="fas fa-tools"></i><span><strong>پروڈکشن کاریگر</strong><small>کٹائی، کڑھائی یا دوسرا
                                    کام</small></span></label>
                    @endif
                </div>
                <form class="people-form-pane" data-form-kind="staff" method="POST"
                    action="{{ route('admin.team.employees.store') }}">@csrf<input type="hidden" name="people_type"
                        value="staff">
                    <div class="people-form-body">
                        @unless ($canCreateStaff)
                            <div class="alert alert-warning">اس کاروبار کے لیے پہلے ایک دفتری رول بنائیں۔ <a
                                    href="{{ route('admin.team.roles.index') }}" class="font-weight-bold">رولز اور اجازتیں
                                    کھولیں</a></div>
                        @else
                            @if ($employeeLimitReached)
                                <div class="alert alert-warning">موجودہ پلان میں مزید فعال ملازم شامل نہیں کیا جا سکتا۔</div>
                            @endif
                            <div class="people-field-grid">
                                <div class="form-group"><label for="personEmployeeName">نام</label><input
                                        id="personEmployeeName" class="form-control" name="name"
                                        value="{{ $oldPersonType === 'staff' ? old('name') : '' }}" required></div>
                                <div class="form-group"><label for="personEmployeeRole">رول / اجازت</label><select
                                        id="personEmployeeRole" class="form-control" name="business_role_id" required>
                                        <option value="">رول منتخب کریں</option>
                                        @if ($business->clothing_enabled)
                                            <option value="preset:salesperson" @selected($oldPersonType === 'staff' && old('business_role_id') === 'preset:salesperson')>سیلز پرسن — تیار
                                                رول</option>
                                            @endif @foreach ($business->roles as $role)
                                                <option value="{{ $role->id }}" @selected($oldPersonType === 'staff' && (string) old('business_role_id') === (string) $role->id)>
                                                    {{ $role->name }}</option>
                                            @endforeach
                                    </select>
                                    <small
                                        class="form-text text-muted">{{ $business->clothing_enabled ? 'تیار سیلز رول منتخب کرنے پر ضروری اجازتیں خود بن جائیں گی۔' : 'عملے کے کام کے مطابق رول اور اجازتیں منتخب کریں۔' }}</small>
                                </div>
                                <div class="form-group"><label for="personEmployeeUsername">یوزر نیم</label><input
                                        id="personEmployeeUsername" class="form-control" name="username" dir="ltr"
                                        value="{{ $oldPersonType === 'staff' ? old('username') : '' }}"
                                        placeholder="staff.ahmad" required></div>
                                <div class="form-group"><label for="personEmployeeEmail">ای میل</label><input
                                        id="personEmployeeEmail" type="email" class="form-control" name="email"
                                        dir="ltr" value="{{ $oldPersonType === 'staff' ? old('email') : '' }}" required>
                                </div>
                                <div class="form-group"><label for="personEmployeePassword">عارضی پاس ورڈ</label><input
                                        id="personEmployeePassword" type="password" class="form-control" name="password"
                                        minlength="8" autocomplete="new-password" required></div>
                                <div class="form-group"><label for="personEmployeePhone">فون نمبر <span
                                            class="text-muted">(اختیاری)</span></label><input id="personEmployeePhone"
                                        class="form-control" name="phone" dir="ltr"
                                        value="{{ $oldPersonType === 'staff' ? old('phone') : '' }}"></div>
                                <div class="form-group is-wide"><label for="personEmployeeTitle">عہدہ <span
                                            class="text-muted">(اختیاری)</span></label><input id="personEmployeeTitle"
                                        class="form-control" name="job_title"
                                        value="{{ $oldPersonType === 'staff' ? old('job_title') : '' }}"
                                        placeholder="مثلاً دفتری ملازم"></div>
                            </div>
                        @endunless
                    </div>
                    <div class="people-form-footer"><small>یہ فرد سسٹم میں لاگ اِن کر سکے گا۔</small>
                        <div><button type="button" class="btn btn-light ml-2" data-dismiss="modal">منسوخ</button>
                            @if ($canCreateStaff && !$employeeLimitReached)
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save ml-1"></i> محفوظ
                                    کریں</button>
                            @endif
                        </div>
                    </div>
                </form>
                @if ($business->tailoring_enabled)
                    <form class="people-form-pane" data-form-kind="tailor" method="POST"
                        action="{{ route('admin.Tailor.store') }}">@csrf<input type="hidden" name="people_type"
                            value="tailor"><input type="hidden" name="return_to" value="people">
                        <div class="people-form-body">
                            @if ($tailorLimitReached)
                                <div class="alert alert-warning">موجودہ پلان میں مزید درزی شامل نہیں کیا جا سکتا۔</div>
                            @endif
                            <div class="people-field-grid">
                                <div class="form-group"><label for="personTailorName">درزی کا نام</label><input
                                        id="personTailorName" class="form-control" name="name"
                                        value="{{ $oldPersonType === 'tailor' ? old('name') : '' }}" required></div>
                                <div class="form-group"><label for="personTailorPhone">فون نمبر</label><input
                                        id="personTailorPhone" class="form-control" name="contact" dir="ltr"
                                        value="{{ $oldPersonType === 'tailor' ? old('contact') : '' }}" required></div>
                                <div class="form-group is-wide"><label for="personTailorPassword">درزی پورٹل پاس
                                        ورڈ</label><input id="personTailorPassword" type="password" class="form-control"
                                        name="password" minlength="6" autocomplete="new-password" required><small
                                        class="form-text text-muted">درزی اسی فون اور پاس ورڈ سے اپنے تفویض شدہ کام دیکھ
                                        سکے گا۔</small></div>
                                <div class="form-group"><label for="personTailorRateLabel">سلائی کی قسم <span
                                            class="text-muted">(اختیاری)</span></label><input id="personTailorRateLabel"
                                        class="form-control" name="initial_rate_label"
                                        value="{{ $oldPersonType === 'tailor' ? old('initial_rate_label') : '' }}"
                                        placeholder="معیاری سلائی"></div>
                                <div class="form-group"><label for="personTailorRate">فی سوٹ اجرت <span
                                            class="text-muted">(اختیاری)</span></label><input id="personTailorRate"
                                        type="number" min=".01" step=".01" class="form-control"
                                        name="initial_rate_price"
                                        value="{{ $oldPersonType === 'tailor' ? old('initial_rate_price') : '' }}"></div>
                                <div class="form-group"><label for="personTailorSecurity">سیکیورٹی ڈپازٹ <span
                                            class="text-muted">(اختیاری)</span></label><input id="personTailorSecurity"
                                        type="number" min="0" step=".01" class="form-control"
                                        name="security_deposit"
                                        value="{{ $oldPersonType === 'tailor' ? old('security_deposit') : '' }}"></div>
                                <div class="form-group"><label for="personTailorSecurityNote">سیکیورٹی نوٹ</label><input
                                        id="personTailorSecurityNote" class="form-control" name="security_deposit_note"
                                        value="{{ $oldPersonType === 'tailor' ? old('security_deposit_note') : '' }}"></div>
                            </div>
                        </div>
                        <div class="people-form-footer"><small>درزی خود بخود پروڈکشن فہرست سے بھی منسلک ہوگا۔</small>
                            <div><button type="button" class="btn btn-light ml-2" data-dismiss="modal">منسوخ</button>
                                @unless ($tailorLimitReached)
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save ml-1"></i> درزی
                                        محفوظ کریں</button>
                                @endunless
                            </div>
                        </div>
                    </form>
                    <form class="people-form-pane" data-form-kind="production" method="POST"
                        action="{{ route('admin.production-workers.store') }}">@csrf<input type="hidden"
                            name="people_type" value="production"><input type="hidden" name="return_to"
                            value="people">
                        <div class="people-form-body">
                            <div class="people-field-grid">
                                <div class="form-group"><label for="personWorkerName">نام</label><input
                                        id="personWorkerName" class="form-control" name="name"
                                        value="{{ $oldPersonType === 'production' ? old('name') : '' }}" required></div>
                                <div class="form-group"><label for="personWorkerRelationship">کاروبار سے
                                        تعلق</label><select id="personWorkerRelationship" class="form-control"
                                        name="relationship_type" required>
                                        <option value="contractor" @selected($oldPersonType === 'production' && old('relationship_type', 'contractor') === 'contractor')>آزاد کاریگر / ٹھیکیدار
                                        </option>
                                        <option value="employee" @selected($oldPersonType === 'production' && old('relationship_type') === 'employee')>تنخواہ دار</option>
                                    </select></div>
                                <div class="form-group"><label for="personWorkerPhone">فون نمبر <span
                                            class="text-muted">(اختیاری)</span></label><input id="personWorkerPhone"
                                        class="form-control" name="phone" dir="ltr"
                                        value="{{ $oldPersonType === 'production' ? old('phone') : '' }}"></div>
                                <div class="form-group"><label for="personWorkerEmail">ای میل <span
                                            class="text-muted">(اختیاری)</span></label><input id="personWorkerEmail"
                                        type="email" class="form-control" name="email" dir="ltr"
                                        value="{{ $oldPersonType === 'production' ? old('email') : '' }}"></div>
                                <div class="people-section is-wide"><label class="d-block mb-2">یہ شخص کون سے کام کر سکتا
                                        ہے؟</label>
                                    <div class="people-skills">
                                        @foreach ($workTypes as $type)
                                            <label class="people-skill"><input type="checkbox" name="work_type_ids[]"
                                                    value="{{ $type->id }}" @checked($oldPersonType === 'production' && in_array($type->id, old('work_type_ids', [])))>
                                                {{ $type->name }}</label>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="form-group is-wide"><label for="personWorkerNotes">نوٹ <span
                                            class="text-muted">(اختیاری)</span></label>
                                    <textarea id="personWorkerNotes" class="form-control" rows="2" name="notes">{{ $oldPersonType === 'production' ? old('notes') : '' }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="people-form-footer"><small>محفوظ کرنے کے بعد کھاتے میں اجرت مقرر کی جا سکتی ہے۔</small>
                            <div><button type="button" class="btn btn-light ml-2"
                                    data-dismiss="modal">منسوخ</button><button type="submit" class="btn btn-primary"><i
                                        class="fas fa-save ml-1"></i> کاریگر محفوظ کریں</button></div>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var selectedFilter = @json($selectedTab),
                search = document.getElementById('peopleSearch'),
                rows = Array.from(document.querySelectorAll('.person-row')),
                empty = document.getElementById('peopleEmpty'),
                tabs = Array.from(document.querySelectorAll('.people-tab'));

            function applyPeopleFilter(filter) {
                selectedFilter = filter || 'all';
                var term = (search.value || '').trim().toLocaleLowerCase(),
                    visible = 0;
                rows.forEach(function(row) {
                    var kinds = (row.dataset.kinds || '').split(/\s+/),
                        show = (selectedFilter === 'all' || kinds.indexOf(selectedFilter) !== -1) && (!
                            term || (row.dataset.search || '').indexOf(term) !== -1);
                    row.hidden = !show;
                    if (show) visible++;
                });
                tabs.forEach(function(tab) {
                    tab.classList.toggle('is-active', tab.dataset.filter === selectedFilter);
                });
                empty.hidden = visible !== 0;
            }
            tabs.forEach(function(tab) {
                tab.addEventListener('click', function() {
                    applyPeopleFilter(tab.dataset.filter);
                });
            });
            search.addEventListener('input', function() {
                applyPeopleFilter(selectedFilter);
            });
            if (!tabs.some(function(tab) {
                    return tab.dataset.filter === selectedFilter;
                })) selectedFilter = 'all';
            applyPeopleFilter(selectedFilter);
            var kindLabels = Array.from(document.querySelectorAll('.people-kind')),
                formPanes = Array.from(document.querySelectorAll('.people-form-pane'));

            function selectKind(kind) {
                if (!formPanes.some(function(form) {
                        return form.dataset.formKind === kind;
                    })) kind = 'staff';
                kindLabels.forEach(function(label) {
                    var active = label.dataset.kind === kind;
                    label.classList.toggle('is-selected', active);
                    var radio = label.querySelector('input');
                    if (radio) radio.checked = active;
                });
                formPanes.forEach(function(form) {
                    form.classList.toggle('is-active', form.dataset.formKind === kind);
                });
            }
            kindLabels.forEach(function(label) {
                label.addEventListener('click', function() {
                    selectKind(label.dataset.kind);
                });
            });
            selectKind(@json($oldPersonType));
            @if ($errors->any() || request('open') === 'create')
                $('#addPersonModal').modal('show');
            @endif
        });
    </script>
@endpush
