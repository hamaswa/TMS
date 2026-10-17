@extends('main')

@push('styles')
    @include('Expenses.partials.styles')
@endpush

@section('content')
    @php
        $filtered = ! empty($filters['from']) || ! empty($filters['to']);
        $oldWorkers = old('_form') === 'staff-create' ? array_values((array) old('workers', [[]])) : [[]];
    @endphp

    <section class="main-content xp-page">
        <div class="container-fluid px-3 px-lg-4">
            @include('inc.message')

            @if ($errors->any() && ! old('_form'))
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <div class="xp-card">
                <div class="xp-header">
                    <div>
                        <h1 class="xp-title">ماہانہ اخراجات</h1>
                        <p class="xp-subtitle">دکان کا کرایہ، بل اور ملازمین کی تنخواہیں ایک جگہ منظم کریں۔</p>
                    </div>
                    <div class="xp-header-actions">
                        <button type="button" class="xp-add" data-xp-bill-create>
                            <i class="fas fa-file-invoice" aria-hidden="true"></i>
                            <span>کرایہ / بل شامل کریں</span>
                        </button>
                        <button type="button" class="xp-add is-secondary" data-toggle="modal" data-target="#staffCreateModal">
                            <i class="fas fa-user-plus" aria-hidden="true"></i>
                            <span>ملازم کی تنخواہ شامل کریں</span>
                        </button>
                    </div>
                </div>

                <div class="xp-stats">
                    <div class="xp-stat">
                        <span class="xp-stat-icon blue"><i class="fas fa-file-invoice-dollar"></i></span>
                        <div><small>کرایہ اور بل</small><strong dir="ltr">Rs. {{ number_format($totalExpenses, 2) }}</strong></div>
                    </div>
                    <div class="xp-stat">
                        <span class="xp-stat-icon orange"><i class="fas fa-users"></i></span>
                        <div><small>تنخواہیں</small><strong dir="ltr">Rs. {{ number_format($totalSalaries, 2) }}</strong></div>
                    </div>
                    <div class="xp-stat">
                        <span class="xp-stat-icon green"><i class="fas fa-wallet"></i></span>
                        <div>
                            <small>{{ $filtered ? 'منتخب مدت کا کل خرچ' : 'کل ماہانہ خرچ' }}</small>
                            <strong dir="ltr">Rs. {{ number_format($totalExpenses + $totalSalaries, 2) }}</strong>
                        </div>
                    </div>
                </div>

                <div class="xp-toolbar">
                    <form method="GET" action="{{ route('admin.expense.index') }}" class="xp-filter">
                        <div>
                            <label for="monthlyFrom">سے</label>
                            <input type="date" id="monthlyFrom" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control">
                        </div>
                        <div>
                            <label for="monthlyTo">تک</label>
                            <input type="date" id="monthlyTo" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-filter ml-1"></i> فلٹر کریں</button>
                        @if ($filtered)
                            <a href="{{ route('admin.expense.index') }}" class="btn btn-light">صاف کریں</a>
                        @endif
                    </form>
                </div>

                <div class="xp-section-title">
                    <h2><i class="fas fa-file-invoice text-primary ml-1"></i> کرایہ اور بل</h2>
                </div>
                <div class="xp-table-shell">
                    <table class="xp-table">
                        <thead>
                            <tr>
                                <th scope="col">تاریخ</th>
                                <th scope="col">ماہانہ کرایہ</th>
                                <th scope="col">ماہانہ بل</th>
                                <th scope="col">کل</th>
                                <th scope="col">عمل</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($expenses as $expense)
                                @php $expenseDate = $expense->expense_date ? \Carbon\Carbon::parse($expense->expense_date) : null; @endphp
                                <tr>
                                    <td class="xp-date">{{ $expenseDate?->format('d-m-Y') ?? '—' }}</td>
                                    <td class="xp-amount" dir="ltr">Rs. {{ number_format((float) $expense->Monthly_Rent, 2) }}</td>
                                    <td class="xp-amount" dir="ltr">Rs. {{ number_format((float) $expense->Monthly_Bill, 2) }}</td>
                                    <td class="xp-amount" dir="ltr">Rs. {{ number_format((float) $expense->Monthly_Rent + (float) $expense->Monthly_Bill, 2) }}</td>
                                    <td>
                                        <div class="xp-actions">
                                            <button type="button" class="xp-icon-btn" data-bill-edit
                                                data-id="{{ $expense->id }}"
                                                data-rent="{{ $expense->Monthly_Rent }}"
                                                data-bill="{{ $expense->Monthly_Bill }}"
                                                data-date="{{ $expenseDate?->toDateString() }}"
                                                data-action="{{ route('admin.expense.update', ['id' => $expense->id]) }}"
                                                aria-label="ریکارڈ میں ترمیم کریں">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <form action="{{ route('admin.expense.delete', ['id' => $expense->id]) }}" method="POST" data-confirm="کیا آپ واقعی یہ خرچ حذف کرنا چاہتے ہیں؟">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="xp-icon-btn is-danger" aria-label="ریکارڈ حذف کریں"><i class="fas fa-trash-alt"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="xp-empty">
                                        <i class="fas fa-file-invoice"></i>
                                        {{ $filtered ? 'اس مدت میں کوئی کرایہ یا بل درج نہیں۔' : 'ابھی کوئی کرایہ یا بل درج نہیں کیا گیا۔' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($expenses->isNotEmpty())
                            <tfoot>
                                <tr>
                                    <td colspan="3">کل کرایہ اور بل</td>
                                    <td class="xp-amount" dir="ltr" colspan="2">Rs. {{ number_format($totalExpenses, 2) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                <div class="xp-section-title">
                    <h2><i class="fas fa-users text-warning ml-1"></i> ملازمین کی تنخواہیں</h2>
                    <div class="xp-search">
                        <label for="staffSearch" class="sr-only">ملازم تلاش کریں</label>
                        <input type="search" id="staffSearch" class="form-control" placeholder="ملازم کا نام تلاش کریں۔۔۔" autocomplete="off" data-xp-search="#staffTable">
                        <i class="fas fa-search" aria-hidden="true"></i>
                    </div>
                </div>
                <div class="xp-table-shell">
                    <table class="xp-table" id="staffTable">
                        <thead>
                            <tr>
                                <th scope="col">تاریخ</th>
                                <th scope="col">ملازم کا نام</th>
                                <th scope="col">تنخواہ</th>
                                <th scope="col">عمل</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($workers as $worker)
                                <tr data-xp-row>
                                    <td class="xp-date">{{ $worker->dateentered ? \Carbon\Carbon::parse($worker->dateentered)->format('d-m-Y') : '—' }}</td>
                                    <td data-xp-text>{{ $worker->Worker_Name }}</td>
                                    <td class="xp-amount" dir="ltr">Rs. {{ number_format((float) $worker->Worker_salary, 2) }}</td>
                                    <td>
                                        <div class="xp-actions">
                                            <button type="button" class="xp-icon-btn" data-staff-edit
                                                data-id="{{ $worker->id }}"
                                                data-name="{{ $worker->Worker_Name }}"
                                                data-salary="{{ $worker->Worker_salary }}"
                                                data-action="{{ route('admin.worker.update', ['id' => $worker->id]) }}"
                                                aria-label="{{ $worker->Worker_Name }} میں ترمیم کریں">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <form action="{{ route('admin.worker.delete', ['id' => $worker->id]) }}" method="POST" data-confirm="کیا آپ واقعی یہ ملازم حذف کرنا چاہتے ہیں؟">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="xp-icon-btn is-danger" aria-label="{{ $worker->Worker_Name }} حذف کریں"><i class="fas fa-trash-alt"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="xp-empty">
                                        <i class="fas fa-users"></i>
                                        {{ $filtered ? 'اس مدت میں کوئی تنخواہ درج نہیں۔' : 'ابھی کوئی تنخواہ درج نہیں کی گئی۔' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($workers->isNotEmpty())
                            <tfoot>
                                <tr>
                                    <td colspan="2">کل تنخواہ</td>
                                    <td class="xp-amount" dir="ltr" colspan="2">Rs. {{ number_format($totalSalaries, 2) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </section>

    {{-- Add or edit rent / bill --}}
    <div class="modal fade xp-modal" id="billModal" tabindex="-1" role="dialog" aria-labelledby="billModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" dir="rtl">
                <form method="POST" id="billForm" action="{{ route('admin.expense.insert') }}" data-store-action="{{ route('admin.expense.insert') }}">
                    @csrf
                    <input type="hidden" name="_form" id="billFormMode" value="{{ old('_form') === 'bill-edit' ? 'bill-edit' : 'bill-create' }}">
                    <input type="hidden" name="_edit_id" id="billEditId" value="{{ old('_edit_id') }}">
                    <div class="modal-header">
                        <h5 class="modal-title" id="billModalTitle">کرایہ / بل شامل کریں</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="بند کریں"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        @if ($errors->any() && in_array(old('_form'), ['bill-create', 'bill-edit'], true))
                            <div class="alert alert-danger">
                                @foreach ($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif
                        <div class="form-group">
                            <label for="billDate">تاریخ</label>
                            <input type="date" id="billDate" name="expense_date" value="{{ old('expense_date', now()->toDateString()) }}" class="form-control" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-6">
                                <label for="billRent">ماہانہ کرایہ</label>
                                <input type="number" id="billRent" name="rent" value="{{ old('rent') }}" class="form-control" step="0.01" min="0" dir="ltr" placeholder="0.00" data-bill-amount>
                            </div>
                            <div class="form-group col-6">
                                <label for="billBill">ماہانہ بل</label>
                                <input type="number" id="billBill" name="bill" value="{{ old('bill') }}" class="form-control" step="0.01" min="0" dir="ltr" placeholder="0.00" data-bill-amount>
                            </div>
                        </div>
                        <div class="xp-modal-total mt-0">
                            <span>کل</span>
                            <span dir="ltr" id="billTotal">Rs. 0.00</span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">منسوخ کریں</button>
                        <button type="submit" class="btn btn-blue">محفوظ کریں</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Add one or more staff salaries --}}
    <div class="modal fade xp-modal" id="staffCreateModal" tabindex="-1" role="dialog" aria-labelledby="staffCreateTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" dir="rtl">
                <form method="POST" action="{{ route('admin.expense.insert') }}">
                    @csrf
                    <input type="hidden" name="_form" value="staff-create">
                    <div class="modal-header">
                        <h5 class="modal-title" id="staffCreateTitle">ملازمین کی تنخواہ شامل کریں</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="بند کریں"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        @if ($errors->any() && old('_form') === 'staff-create')
                            <div class="alert alert-danger">
                                @foreach ($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif
                        <div class="xp-rows" id="staffRows">
                            @foreach ($oldWorkers as $oldWorker)
                                <div class="xp-row" data-xp-entry>
                                    <div class="form-group">
                                        <label>ملازم کا نام</label>
                                        <input type="text" data-field="name" value="{{ $oldWorker['name'] ?? '' }}" class="form-control" maxlength="255" required>
                                    </div>
                                    <div class="form-group">
                                        <label>تنخواہ</label>
                                        <input type="number" data-field="salary" value="{{ $oldWorker['salary'] ?? '' }}" class="form-control" step="0.01" min="0" required dir="ltr" data-xp-amount>
                                    </div>
                                    <button type="button" class="btn btn-light xp-row-remove" data-xp-remove aria-label="قطار ہٹائیں"><i class="fas fa-times"></i></button>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-link px-0 xp-add-row" id="staffAddRow">
                            <i class="fas fa-plus-circle ml-1"></i> مزید ملازم شامل کریں
                        </button>
                        <div class="xp-modal-total">
                            <span>کل تنخواہ</span>
                            <span dir="ltr" id="staffCreateTotal">Rs. 0.00</span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">منسوخ کریں</button>
                        <button type="submit" class="btn btn-blue">محفوظ کریں</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit a single staff salary --}}
    <div class="modal fade xp-modal" id="staffEditModal" tabindex="-1" role="dialog" aria-labelledby="staffEditTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" dir="rtl">
                <form method="POST" id="staffEditForm" action="">
                    @csrf
                    <input type="hidden" name="_form" value="staff-edit">
                    <input type="hidden" name="_edit_id" id="staffEditId" value="{{ old('_edit_id') }}">
                    <div class="modal-header">
                        <h5 class="modal-title" id="staffEditTitle">ملازم میں ترمیم کریں</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="بند کریں"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        @if ($errors->any() && old('_form') === 'staff-edit')
                            <div class="alert alert-danger">
                                @foreach ($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif
                        <div class="form-group">
                            <label for="staffEditName">ملازم کا نام</label>
                            <input type="text" id="staffEditName" name="name" value="{{ old('_form') === 'staff-edit' ? old('name') : '' }}" class="form-control" maxlength="255" required>
                        </div>
                        <div class="form-group mb-0">
                            <label for="staffEditSalary">تنخواہ</label>
                            <input type="number" id="staffEditSalary" name="salary" value="{{ old('_form') === 'staff-edit' ? old('salary') : '' }}" class="form-control" step="0.01" min="0" required dir="ltr">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">منسوخ کریں</button>
                        <button type="submit" class="btn btn-blue">محفوظ کریں</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @include('Expenses.partials.scripts')
    <script>
        jQuery(function ($) {
            var failedForm = @json(old('_form'));
            var failedEditId = @json(old('_edit_id'));
            var params = new URLSearchParams(window.location.search);

            // Rent / bill modal (shared by add and edit).
            var billForm = $('#billForm');

            function updateBillTotal() {
                var total = 0;
                billForm.find('[data-bill-amount]').each(function () { total += parseFloat(this.value) || 0; });
                $('#billTotal').text(xpMoney(total));
            }

            function openBill(button, keepInput) {
                var editing = !!button;
                button = $(button);
                billForm.attr('action', editing ? button.data('action') : billForm.data('store-action'));
                $('#billFormMode').val(editing ? 'bill-edit' : 'bill-create');
                $('#billEditId').val(editing ? button.data('id') : '');
                $('#billModalTitle').text(editing ? 'کرایہ / بل میں ترمیم کریں' : 'کرایہ / بل شامل کریں');
                if (!keepInput) {
                    $('#billDate').val(editing ? (button.data('date') || '') : @json(now()->toDateString()));
                    $('#billRent').val(editing ? button.data('rent') : '');
                    $('#billBill').val(editing ? button.data('bill') : '');
                    billForm.find('.alert-danger').remove();
                }
                updateBillTotal();
                $('#billModal').modal('show');
            }

            $('[data-xp-bill-create]').on('click', function () { openBill(null, false); });
            $(document).on('click', '[data-bill-edit]', function () { openBill(this, false); });
            billForm.on('input', '[data-bill-amount]', updateBillTotal);

            // Staff create modal with repeatable rows; inputs are named on submit.
            var staffRows = $('#staffRows');

            function updateStaffTotal() {
                var total = 0;
                staffRows.find('[data-xp-amount]').each(function () { total += parseFloat(this.value) || 0; });
                $('#staffCreateTotal').text(xpMoney(total));
                staffRows.find('[data-xp-remove]').prop('disabled', staffRows.children().length === 1);
            }

            function nameStaffRows() {
                staffRows.children().each(function (index) {
                    $(this).find('[data-field]').each(function () {
                        this.name = 'workers[' + index + '][' + $(this).data('field') + ']';
                    });
                });
            }

            $('#staffAddRow').on('click', function () {
                var row = staffRows.children().first().clone();
                row.find('input').val('');
                staffRows.append(row);
                nameStaffRows();
                updateStaffTotal();
                row.find('input').first().trigger('focus');
            });

            staffRows.on('click', '[data-xp-remove]', function () {
                if (staffRows.children().length > 1) {
                    $(this).closest('[data-xp-entry]').remove();
                    nameStaffRows();
                    updateStaffTotal();
                }
            });
            staffRows.on('input', '[data-xp-amount]', updateStaffTotal);
            nameStaffRows();
            updateStaffTotal();

            $('#staffCreateModal').on('shown.bs.modal', function () {
                staffRows.find('input').first().trigger('focus');
            });

            // Staff edit modal.
            function openStaffEdit(button, keepInput) {
                button = $(button);
                $('#staffEditForm').attr('action', button.data('action'));
                $('#staffEditId').val(button.data('id'));
                if (!keepInput) {
                    $('#staffEditName').val(button.data('name'));
                    $('#staffEditSalary').val(button.data('salary'));
                    $('#staffEditModal .alert-danger').remove();
                }
                $('#staffEditModal').modal('show');
            }

            $(document).on('click', '[data-staff-edit]', function () { openStaffEdit(this, false); });

            // Reopen after a failed submit, or when arriving from the old create/edit URLs.
            var billEditId = failedForm === 'bill-edit' ? failedEditId : params.get('edit_bill');
            var staffEditId = failedForm === 'staff-edit' ? failedEditId : params.get('edit_staff');

            if (billEditId) {
                var billButton = $('[data-bill-edit][data-id="' + billEditId + '"]');
                if (billButton.length) {
                    openBill(billButton, failedForm === 'bill-edit');
                }
            } else if (staffEditId) {
                var staffButton = $('[data-staff-edit][data-id="' + staffEditId + '"]');
                if (staffButton.length) {
                    openStaffEdit(staffButton, failedForm === 'staff-edit');
                }
            } else if (failedForm === 'bill-create' || params.get('modal') === 'bill') {
                openBill(null, failedForm === 'bill-create');
            } else if (failedForm === 'staff-create') {
                $('#staffCreateModal').modal('show');
            }
        });
    </script>
@endpush
