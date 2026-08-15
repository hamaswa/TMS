@extends('main')

@section('content')
    @php
        $employeeLimit = $business->subscriptionLimit('max_employees');
        $activeEmployeeCount = $business->members->where('employee_active', true)->count();
        $employeeLimitReached = $employeeLimit !== null && $activeEmployeeCount >= $employeeLimit;
    @endphp
    <style>
        .employees-page {
            --ep-blue: #1769e0;
            --ep-navy: #102a50;
            --ep-muted: #6d7f94;
            --ep-line: #e0e8f2;
            background: #f5f7fa;
            padding-bottom: 50px
        }

        .employees-page>.ep-shell {
            width: min(100% - 32px, 1500px);
            margin-inline: auto;
            padding-top: 24px
        }

        .employees-page .team-hero {
            margin-bottom: 16px !important;
            padding: 20px 24px;
            border-radius: 17px
        }

        .employees-page .team-hero h1 {
            font-size: 1.55rem
        }

        .employees-page .team-tabs {
            margin-bottom: 18px !important
        }

        .employees-page .team-tabs .btn {
            padding: .5rem .9rem;
            font-size: .82rem
        }

        .ep-overview {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 15px 18px;
            margin-bottom: 16px;
            border: 1px solid #d9e7f7;
            border-radius: 14px;
            background: #f3f8ff
        }

        .ep-overview-copy {
            display: flex;
            align-items: center;
            gap: 11px
        }

        .ep-overview-icon {
            display: grid;
            place-items: center;
            width: 42px;
            height: 42px;
            border-radius: 12px;
            color: var(--ep-blue);
            background: #fff;
            font-size: 17px
        }

        .ep-overview strong {
            display: block;
            color: var(--ep-navy);
            font-size: .86rem
        }

        .ep-overview small {
            display: block;
            margin-top: 2px;
            color: var(--ep-muted);
            font-size: .72rem
        }

        .ep-plan {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 6px 10px;
            border-radius: 999px;
            color: #526a86;
            background: #fff;
            font-size: .72rem;
            font-weight: 800;
            white-space: nowrap
        }

        .ep-panel {
            overflow: hidden;
            margin-bottom: 18px;
            border: 1px solid var(--ep-line);
            border-radius: 17px;
            background: #fff;
            box-shadow: 0 8px 28px rgba(21, 47, 81, .055)
        }

        .ep-panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 20px;
            border-bottom: 1px solid var(--ep-line);
            background: #fbfdff
        }

        .ep-panel-title {
            display: flex;
            align-items: center;
            gap: 11px
        }

        .ep-panel-icon {
            display: grid;
            place-items: center;
            width: 42px;
            height: 42px;
            border-radius: 12px;
            color: var(--ep-blue);
            background: #eaf3ff;
            font-size: 17px
        }

        .ep-panel-head h2 {
            margin: 0 0 3px;
            color: var(--ep-navy);
            font-size: 1.12rem;
            font-weight: 800
        }

        .ep-panel-head p {
            margin: 0;
            color: var(--ep-muted);
            font-size: .74rem
        }

        .ep-role-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 11px;
            border: 1px solid #cbdcf2;
            border-radius: 9px;
            color: var(--ep-blue);
            background: #fff;
            font-size: .73rem;
            font-weight: 800;
            text-decoration: none !important
        }

        .ep-steps {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            padding: 16px 20px 0
        }

        .ep-step {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 10px 12px;
            border-radius: 10px;
            color: #53667e;
            background: #f5f8fc;
            font-size: .72rem
        }

        .ep-step-number {
            display: grid;
            place-items: center;
            flex: 0 0 28px;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            color: #fff;
            background: var(--ep-blue);
            font-weight: 800
        }

        .ep-step:nth-child(2) .ep-step-number {
            background: #8654d5
        }

        .ep-step:nth-child(3) .ep-step-number {
            background: #15915a
        }

        .ep-step strong {
            display: block;
            color: var(--ep-navy);
            font-size: .76rem
        }

        .employee-form {
            padding: 18px 20px
        }

        .ep-form-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px
        }

        .ep-form-section {
            padding: 16px;
            border: 1px solid #e1e9f3;
            border-radius: 13px;
            background: #fff
        }

        .ep-form-section.is-access {
            grid-column: 1 / -1;
            background: #fbfdff
        }

        .ep-form-section-head {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 14px
        }

        .ep-form-section-head i {
            display: grid;
            place-items: center;
            width: 35px;
            height: 35px;
            border-radius: 10px;
            color: var(--ep-blue);
            background: #eaf3ff
        }

        .ep-form-section.is-login .ep-form-section-head i {
            color: #8654d5;
            background: #f1ebff
        }

        .ep-form-section.is-access .ep-form-section-head i {
            color: #15915a;
            background: #e7f7ef
        }

        .ep-form-section-head strong {
            display: block;
            color: var(--ep-navy);
            font-size: .85rem
        }

        .ep-form-section-head small {
            display: block;
            color: var(--ep-muted);
            font-size: .68rem
        }

        .ep-fields {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 13px
        }

        .ep-field.is-wide {
            grid-column: 1 / -1
        }

        .ep-field label {
            display: block;
            margin-bottom: 6px;
            color: #344a67;
            font-size: .78rem;
            font-weight: 800
        }

        .ep-field-wrap {
            position: relative
        }

        .ep-field-wrap>i {
            position: absolute;
            z-index: 2;
            top: 50%;
            right: 13px;
            color: #8493a6;
            transform: translateY(-50%)
        }

        .ep-field .form-control {
            min-height: 46px;
            padding: 8px 39px 8px 12px;
            border-color: #d3deeb;
            border-radius: 9px;
            color: var(--ep-navy);
            background: #fbfdff
        }

        .ep-field textarea.form-control {
            min-height: 84px;
            padding-top: 12px
        }

        .ep-field .form-control:focus {
            border-color: #75a8ef;
            box-shadow: 0 0 0 3px rgba(23, 105, 224, .1);
            background: #fff
        }

        .ep-field small {
            display: block;
            margin-top: 5px;
            color: var(--ep-muted);
            font-size: .68rem;
            line-height: 1.7
        }

        .ep-access-row {
            display: grid;
            grid-template-columns: 1fr 1.4fr;
            gap: 14px;
            align-items: center
        }

        .ep-role-help {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            padding: 11px 12px;
            border-radius: 10px;
            color: #53667e;
            background: #eef8f3;
            font-size: .71rem;
            line-height: 1.8
        }

        .ep-role-help i {
            margin-top: 3px;
            color: #15915a
        }

        .employee-form .password-strength {
            padding: .55rem;
            margin-top: .45rem
        }

        .employee-form .password-strength-rules {
            font-size: .68rem
        }

        .employee-form .password-strength-label {
            font-size: .7rem
        }

        .ep-form-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding-top: 16px
        }

        .ep-form-footer p {
            margin: 0;
            color: var(--ep-muted);
            font-size: .7rem
        }

        .ep-save {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 44px;
            padding: 8px 18px;
            border: 0;
            border-radius: 10px;
            color: #fff;
            background: #15915a;
            font-weight: 800;
            box-shadow: 0 7px 16px rgba(21, 145, 90, .16)
        }

        .ep-alert {
            padding: 14px 17px;
            margin: 16px 20px;
            border-radius: 11px
        }

        .ep-alert.is-info {
            border: 1px solid #cfe2f8;
            color: #315f91;
            background: #f1f7ff
        }

        .ep-alert.is-warning {
            border: 1px solid #f2deb0;
            color: #8a620c;
            background: #fff8e7
        }

        .ep-list-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 18px 20px;
            border-bottom: 1px solid var(--ep-line)
        }

        .ep-count {
            padding: 6px 11px;
            border-radius: 999px;
            color: var(--ep-blue);
            background: #eaf3ff;
            font-weight: 800
        }

        .ep-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 13px;
            padding: 16px
        }

        .employee-card {
            overflow: hidden;
            border: 1px solid var(--ep-line);
            border-radius: 14px;
            background: #fff
        }

        .employee-card-main {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px
        }

        .employee-avatar {
            display: grid;
            place-items: center;
            flex: 0 0 46px;
            width: 46px;
            height: 46px;
            border-radius: 13px;
            color: #fff;
            background: linear-gradient(135deg, #2479ee, #0c5bd1);
            font-weight: 800
        }

        .employee-identity {
            min-width: 0;
            flex: 1
        }

        .employee-identity strong {
            display: block;
            overflow: hidden;
            color: var(--ep-navy);
            text-overflow: ellipsis;
            white-space: nowrap
        }

        .employee-identity small {
            display: block;
            margin-top: 3px;
            color: var(--ep-muted);
            font-size: .7rem
        }

        .employee-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: .66rem;
            font-weight: 800;
            white-space: nowrap
        }

        .employee-status.is-active {
            color: #14764b;
            background: #e8f7ef
        }

        .employee-status.is-waiting {
            color: #93640c;
            background: #fff3db
        }

        .employee-status.is-off {
            color: #a33b47;
            background: #fff0f2
        }

        .employee-status i {
            font-size: .45rem
        }

        .employee-card-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            padding: 12px 15px;
            border-top: 1px solid #edf1f6;
            background: #fbfdff
        }

        .employee-detail small {
            display: block;
            color: #8493a6;
            font-size: .65rem
        }

        .employee-detail strong {
            display: block;
            overflow: hidden;
            margin-top: 2px;
            color: #40566f;
            font-size: .75rem;
            text-overflow: ellipsis;
            white-space: nowrap
        }

        .employee-card-contact {
            padding: 11px 15px;
            border-top: 1px solid #edf1f6;
            color: #75859a;
            font-size: .68rem
        }

        .employee-card-contact span {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap
        }

        .employee-card-action {
            display: flex;
            padding: 11px 15px;
            border-top: 1px solid #edf1f6
        }

        .employee-edit-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 100%;
            min-height: 36px;
            border: 1px solid #cbdcf2;
            border-radius: 8px;
            color: var(--ep-blue);
            background: #fff;
            font-size: .72rem;
            font-weight: 800;
            text-decoration: none !important
        }

        .ep-empty {
            grid-column: 1 / -1;
            padding: 42px 20px;
            color: var(--ep-muted);
            text-align: center
        }

        .ep-empty i {
            display: grid;
            place-items: center;
            width: 54px;
            height: 54px;
            margin: 0 auto 10px;
            border-radius: 50%;
            color: var(--ep-blue);
            background: #eaf3ff;
            font-size: 20px
        }

        .ep-empty strong {
            display: block;
            color: var(--ep-navy)
        }

        .ep-simple-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px
        }

        .ep-optional-details {
            grid-column: 1 / -1;
            overflow: hidden;
            border: 1px solid #dce6f1;
            border-radius: 11px;
            background: #fbfdff
        }

        .ep-optional-details summary {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 13px 15px;
            color: #40566f;
            font-size: .76rem;
            font-weight: 800;
            cursor: pointer;
            list-style: none
        }

        .ep-optional-details summary::-webkit-details-marker {
            display: none
        }

        .ep-optional-details summary i {
            color: var(--ep-blue)
        }

        .ep-optional-details summary span {
            color: var(--ep-muted);
            font-size: .67rem;
            font-weight: 600
        }

        .ep-optional-fields {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 13px;
            padding: 15px;
            border-top: 1px solid #e6edf5;
            background: #fff
        }

        @media(max-width:1100px) {
            .ep-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr))
            }
        }

        @media(max-width:850px) {

            .ep-steps,
            .ep-form-sections {
                grid-template-columns: 1fr
            }

            .ep-form-section.is-access {
                grid-column: auto
            }

            .ep-access-row {
                grid-template-columns: 1fr
            }

            .ep-optional-fields {
                grid-template-columns: 1fr
            }
        }

        @media(max-width:767px) {
            .employees-page>.ep-shell {
                width: min(100% - 20px, 1500px);
                padding-top: 16px
            }

            .employees-page .team-hero {
                padding: 18px
            }

            .employees-page .team-tabs {
                overflow-x: auto;
                flex-wrap: nowrap;
                padding-bottom: 4px
            }

            .employees-page .team-tabs .btn {
                white-space: nowrap
            }

            .ep-overview,
            .ep-panel-head,
            .ep-list-head {
                align-items: flex-start;
                flex-direction: column
            }

            .ep-plan,
            .ep-role-link {
                align-self: stretch;
                justify-content: center
            }

            .ep-fields,
            .ep-simple-grid,
            .ep-grid {
                grid-template-columns: 1fr
            }

            .ep-field.is-wide {
                grid-column: auto
            }

            .ep-form-footer {
                align-items: stretch;
                flex-direction: column
            }

            .ep-save {
                width: 100%
            }

            .employee-form {
                padding: 16px
            }

            .ep-steps {
                padding: 14px 16px 0
            }
        }
    </style>

    <section class="main-content team-page employees-page" dir="rtl">
        <div class="ep-shell">
            @include('team.partials.workspace')

            <section class="ep-panel">
                <div class="ep-panel-head">
                    <div class="ep-panel-title"><span class="ep-panel-icon"><i class="fas fa-user-plus"></i></span>
                        <div>
                            <h2>نیا ملازم شامل کریں</h2>
                            <p>صرف ضروری معلومات درج کریں۔ باقی معلومات اختیاری ہیں۔</p>
                        </div>
                    </div><a class="ep-role-link" href="{{ route('admin.team.roles.index') }}"><i
                            class="fas fa-user-shield"></i> رولز دیکھیں</a>
                </div>

                @if ($business->roles->isEmpty())
                    <div class="ep-alert is-info"><i class="fas fa-info-circle ml-1"></i> پہلے ملازم کے کام کا رول بنائیں،
                        پھر یہاں اس کا اکاؤنٹ شامل کریں۔ <a class="font-weight-bold"
                            href="{{ route('admin.team.roles.index') }}">نیا رول بنائیں</a></div>
                @elseif($employeeLimitReached)
                    <div class="ep-alert is-warning"><i class="fas fa-exclamation-circle ml-1"></i> موجودہ پلان میں مزید
                        فعال ملازم شامل نہیں کیا جا سکتا۔ پلان اپ گریڈ کے لیے سپر ایڈمن سے رابطہ کریں۔</div>
                @else
                    <form method="POST" action="{{ route('admin.team.employees.store') }}" class="employee-form">@csrf
                        <div class="ep-simple-grid">
                            <div class="ep-field"><label for="employeeName">نام</label>
                                <div class="ep-field-wrap"><i class="fas fa-user"></i><input id="employeeName"
                                        class="form-control" name="name" value="{{ old('name') }}" required
                                        placeholder="مثلاً احمد علی"></div>
                            </div>
                            <div class="ep-field"><label for="employeeRole">کام کا رول</label>
                                <div class="ep-field-wrap"><i class="fas fa-user-tag"></i><select id="employeeRole"
                                        class="form-control" name="business_role_id" required>
                                        <option value="">رول منتخب کریں</option>
                                        @foreach ($business->roles as $role)
                                            <option value="{{ $role->id }}" @selected((string) old('business_role_id') === (string) $role->id)>
                                                {{ $role->name }}</option>
                                        @endforeach
                                    </select></div><small>رول سے ملازم کے کام کی اجازت طے ہوگی۔</small>
                            </div>
                            <div class="ep-field"><label for="employeeUsername">یوزر نیم</label>
                                <div class="ep-field-wrap"><i class="fas fa-at"></i><input id="employeeUsername"
                                        class="form-control" name="username" value="{{ old('username') }}" dir="ltr"
                                        placeholder="sale.ahmad" required></div><small>یہ نام ملازم لاگ اِن کرتے وقت لکھے گا۔</small>
                            </div>
                            <div class="ep-field"><label for="employeeEmail">ای میل</label>
                                <div class="ep-field-wrap"><i class="fas fa-envelope"></i><input id="employeeEmail"
                                        type="email" class="form-control" dir="ltr" name="email"
                                        value="{{ old('email') }}" placeholder="ahmad@example.com" required></div>
                            </div>
                            <div class="ep-field is-wide"><label for="employee-temporary-password">پہلا پاس ورڈ</label>
                                <div class="ep-field-wrap"><i class="fas fa-lock"></i><input
                                        id="employee-temporary-password" type="password" class="form-control"
                                        name="password" minlength="8" required autocomplete="new-password"></div>
                                <small>یہ پاس ورڈ ملازم کو دیں۔ وہ پہلے لاگ اِن پر اسے بدل لے گا۔</small>
                                @include('team.partials.password-strength', [
                                    'inputId' => 'employee-temporary-password',
                                ])
                            </div>
                            <details class="ep-optional-details" @if (old('job_title') || old('phone') || old('address')) open @endif>
                                <summary><i class="fas fa-plus-circle"></i> مزید معلومات <span>(اختیاری)</span></summary>
                                <div class="ep-optional-fields">
                                    <div class="ep-field"><label for="employeeJobTitle">عہدہ</label>
                                        <div class="ep-field-wrap"><i class="fas fa-briefcase"></i><input
                                                id="employeeJobTitle" class="form-control" name="job_title"
                                                value="{{ old('job_title') }}" placeholder="مثلاً کاؤنٹر سیلز"></div>
                                    </div>
                                    <div class="ep-field"><label for="employeePhone">فون نمبر</label>
                                        <div class="ep-field-wrap"><i class="fas fa-phone-alt"></i><input
                                                id="employeePhone" class="form-control" name="phone"
                                                value="{{ old('phone') }}" dir="ltr" inputmode="tel"
                                                placeholder="03001234567"></div>
                                    </div>
                                    <div class="ep-field"><label for="employeeAddress">پتہ</label>
                                        <div class="ep-field-wrap"><i class="fas fa-map-marker-alt"></i><input
                                                id="employeeAddress" class="form-control" name="address"
                                                value="{{ old('address') }}" placeholder="مختصر پتہ"></div>
                                    </div>
                                </div>
                            </details>
                        </div>
                        <div class="ep-form-footer">
                            <p><i class="fas fa-check-circle text-success ml-1"></i> تمام ضروری خانے پُر کریں۔</p>
                            <button class="ep-save" type="submit"><i class="fas fa-user-plus"></i> ملازم شامل کریں</button>
                        </div>
                    </form>
                @endif
            </section>

            <section class="ep-panel">
                <div class="ep-list-head">
                    <div class="ep-panel-title"><span class="ep-panel-icon"><i class="fas fa-users"></i></span>
                        <div>
                            <h2>موجودہ ملازمین</h2>
                            <p>ہر ملازم کا کام، رول اور اکاؤنٹ کی حالت۔</p>
                        </div>
                    </div><span class="ep-count">{{ $business->members->count() }} ملازمین</span>
                </div>
                <div class="ep-grid">
                    @forelse($business->members as $employee)
                        @php
                            $isWaiting =
                                $employee->employee_active &&
                                ($employee->must_change_password || $employee->employeePasswordExpired());
                            $statusLabel = !$employee->employee_active
                                ? 'اکاؤنٹ بند'
                                : ($employee->must_change_password
                                    ? 'نیا پاس ورڈ باقی'
                                    : ($employee->employeePasswordExpired()
                                        ? 'پاس ورڈ تبدیل کریں'
                                        : 'فعال'));
                            $statusClass = !$employee->employee_active
                                ? 'is-off'
                                : ($isWaiting
                                    ? 'is-waiting'
                                    : 'is-active');
                        @endphp
                        <article class="employee-card">
                            <div class="employee-card-main"><span
                                    class="employee-avatar">{{ mb_substr($employee->name, 0, 1) }}</span>
                                <div class="employee-identity"><strong>{{ $employee->name }}</strong><small
                                        dir="ltr">{{ '@' . $employee->username }}</small></div><span
                                    class="employee-status {{ $statusClass }}"><i
                                        class="fas fa-circle"></i>{{ $statusLabel }}</span>
                            </div>
                            <div class="employee-card-details">
                                <div class="employee-detail"><small>کام /
                                        عہدہ</small><strong>{{ $employee->job_title ?: 'درج نہیں' }}</strong></div>
                                <div class="employee-detail"><small>کام کا
                                        رول</small><strong>{{ $employee->businessRole?->name ?: 'رول مقرر نہیں' }}</strong>
                                </div>
                            </div>
                            <div class="employee-card-contact"><span dir="ltr"><i
                                        class="fas fa-envelope ml-1"></i>{{ $employee->email }}</span>
                                @if ($employee->phone)
                                    <span dir="ltr"><i class="fas fa-phone ml-1"></i>{{ $employee->phone }}</span>
                                @endif
                            </div>
                            <div class="employee-card-action"><a class="employee-edit-btn"
                                    href="{{ route('admin.team.employees.edit', $employee) }}"><i class="fas fa-edit"></i>
                                    معلومات اور رسائی تبدیل کریں</a></div>
                        </article>
                    @empty
                        <div class="ep-empty"><i class="fas fa-user-plus"></i><strong>ابھی کوئی ملازم شامل
                                نہیں</strong><span>اوپر دی گئی تین آسان مراحل سے پہلا ملازم شامل کریں۔</span></div>
                    @endforelse
                </div>
            </section>
        </div>
    </section>
@endsection
