@extends('main')

@push('styles')
    @include('Expenses.partials.styles')
@endpush

@section('content')
    @php
        $filtered = ! empty($filters['from']) || ! empty($filters['to']);
        $oldNames = old('_form') === 'daily-create' ? array_values((array) old('name', [''])) : [''];
        $oldAmounts = old('_form') === 'daily-create' ? array_values((array) old('rupee', [''])) : [''];
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
                        <h1 class="xp-title">روزانہ اخراجات</h1>
                        <p class="xp-subtitle">چائے، کرایہ گاڑی، مرمت جیسے روزمرہ کے خرچ یہاں درج کریں۔</p>
                    </div>
                    <div class="xp-header-actions">
                        <button type="button" class="xp-add" data-toggle="modal" data-target="#dailyCreateModal">
                            <i class="fas fa-plus" aria-hidden="true"></i>
                            <span>نیا خرچ شامل کریں</span>
                        </button>
                    </div>
                </div>

                <div class="xp-stats">
                    <div class="xp-stat">
                        <span class="xp-stat-icon blue"><i class="fas fa-calendar-day"></i></span>
                        <div><small>آج کے اخراجات</small><strong dir="ltr">Rs. {{ number_format($todayTotal, 2) }}</strong></div>
                    </div>
                    <div class="xp-stat">
                        <span class="xp-stat-icon orange"><i class="fas fa-calendar-alt"></i></span>
                        <div><small>اس مہینے کے اخراجات</small><strong dir="ltr">Rs. {{ number_format($monthTotal, 2) }}</strong></div>
                    </div>
                    <div class="xp-stat">
                        <span class="xp-stat-icon green"><i class="fas fa-list-ol"></i></span>
                        <div>
                            <small>{{ $filtered ? 'منتخب مدت کا کل' : 'کل درج شدہ اخراجات' }}</small>
                            <strong dir="ltr">Rs. {{ number_format($listTotal, 2) }}</strong>
                        </div>
                    </div>
                </div>

                <div class="xp-toolbar">
                    <form method="GET" action="{{ route('admin.dailyexpense.index') }}" class="xp-filter">
                        <div>
                            <label for="dailyFrom">سے</label>
                            <input type="date" id="dailyFrom" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control">
                        </div>
                        <div>
                            <label for="dailyTo">تک</label>
                            <input type="date" id="dailyTo" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-filter ml-1"></i> فلٹر کریں</button>
                        @if ($filtered)
                            <a href="{{ route('admin.dailyexpense.index') }}" class="btn btn-light">صاف کریں</a>
                        @endif
                    </form>

                    <div class="xp-search">
                        <label for="dailySearch" class="sr-only">خرچ تلاش کریں</label>
                        <input type="search" id="dailySearch" class="form-control" placeholder="عنوان تلاش کریں۔۔۔" autocomplete="off" data-xp-search="#dailyTable">
                        <i class="fas fa-search" aria-hidden="true"></i>
                    </div>
                </div>

                <div class="xp-table-shell">
                    <table class="xp-table" id="dailyTable">
                        <thead>
                            <tr>
                                <th scope="col">تاریخ</th>
                                <th scope="col">اخراجات کا عنوان</th>
                                <th scope="col">رقم</th>
                                <th scope="col">عمل</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($expense as $ex)
                                <tr data-xp-row>
                                    <td class="xp-date">{{ $ex->created_at?->format('d-m-Y') }}</td>
                                    <td data-xp-text>{{ $ex->Expense_name }}</td>
                                    <td class="xp-amount" dir="ltr">Rs. {{ number_format((float) $ex->Expense_payment, 2) }}</td>
                                    <td>
                                        <div class="xp-actions">
                                            <button type="button" class="xp-icon-btn" data-daily-edit
                                                data-id="{{ $ex->id }}"
                                                data-name="{{ $ex->Expense_name }}"
                                                data-amount="{{ $ex->Expense_payment }}"
                                                data-action="{{ route('admin.dailyexpense.update', ['id' => $ex->id]) }}"
                                                aria-label="{{ $ex->Expense_name }} میں ترمیم کریں">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <form action="{{ route('admin.dailyexpense.delete', ['id' => $ex->id]) }}" method="POST" data-confirm="کیا آپ واقعی یہ روزانہ خرچ حذف کرنا چاہتے ہیں؟">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="xp-icon-btn is-danger" aria-label="{{ $ex->Expense_name }} حذف کریں">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="xp-empty">
                                        <i class="fas fa-receipt"></i>
                                        {{ $filtered ? 'اس مدت میں کوئی خرچ درج نہیں۔' : 'ابھی کوئی روزانہ خرچ درج نہیں کیا گیا۔' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($expense->isNotEmpty())
                            <tfoot>
                                <tr>
                                    <td colspan="2">کل اخراجات ({{ $expense->total() }} ریکارڈ)</td>
                                    <td class="xp-amount" dir="ltr" colspan="2">Rs. {{ number_format($listTotal, 2) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                @if ($expense->hasPages())
                    <div class="xp-pagination">
                        <small class="text-muted">
                            کل {{ $expense->total() }} میں سے {{ $expense->firstItem() }} تا {{ $expense->lastItem() }}
                        </small>
                        {{ $expense->onEachSide(1)->links() }}
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- Add one or more daily expenses --}}
    <div class="modal fade xp-modal" id="dailyCreateModal" tabindex="-1" role="dialog" aria-labelledby="dailyCreateTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" dir="rtl">
                <form method="POST" action="{{ route('admin.dailyexpense.insert') }}">
                    @csrf
                    <input type="hidden" name="_form" value="daily-create">
                    <div class="modal-header">
                        <h5 class="modal-title" id="dailyCreateTitle">روزانہ اخراجات شامل کریں</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="بند کریں"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        @if ($errors->any() && old('_form') === 'daily-create')
                            <div class="alert alert-danger">
                                @foreach ($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        <div class="xp-rows" id="dailyRows">
                            @foreach ($oldNames as $index => $oldName)
                                <div class="xp-row" data-xp-entry>
                                    <div class="form-group">
                                        <label>اخراجات کا عنوان</label>
                                        <input type="text" name="name[]" value="{{ $oldName }}" class="form-control" maxlength="255" required placeholder="مثلاً چائے">
                                    </div>
                                    <div class="form-group">
                                        <label>رقم</label>
                                        <input type="number" name="rupee[]" value="{{ $oldAmounts[$index] ?? '' }}" class="form-control" step="0.01" min="0" required dir="ltr" data-xp-amount>
                                    </div>
                                    <button type="button" class="btn btn-light xp-row-remove" data-xp-remove aria-label="قطار ہٹائیں"><i class="fas fa-times"></i></button>
                                </div>
                            @endforeach
                        </div>

                        <button type="button" class="btn btn-link px-0 xp-add-row" id="dailyAddRow">
                            <i class="fas fa-plus-circle ml-1"></i> مزید خرچ شامل کریں
                        </button>

                        <div class="xp-modal-total">
                            <span>کل</span>
                            <span dir="ltr" id="dailyCreateTotal">Rs. 0.00</span>
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

    {{-- Edit a single daily expense --}}
    <div class="modal fade xp-modal" id="dailyEditModal" tabindex="-1" role="dialog" aria-labelledby="dailyEditTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" dir="rtl">
                <form method="POST" id="dailyEditForm" action="">
                    @csrf
                    <input type="hidden" name="_form" value="daily-edit">
                    <input type="hidden" name="_edit_id" id="dailyEditId" value="{{ old('_edit_id') }}">
                    <div class="modal-header">
                        <h5 class="modal-title" id="dailyEditTitle">خرچ میں ترمیم کریں</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="بند کریں"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        @if ($errors->any() && old('_form') === 'daily-edit')
                            <div class="alert alert-danger">
                                @foreach ($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif
                        <div class="form-group">
                            <label for="dailyEditName">اخراجات کا عنوان</label>
                            <input type="text" id="dailyEditName" name="name" value="{{ old('_form') === 'daily-edit' ? old('name') : '' }}" class="form-control" maxlength="255" required>
                        </div>
                        <div class="form-group mb-0">
                            <label for="dailyEditAmount">رقم</label>
                            <input type="number" id="dailyEditAmount" name="rupee" value="{{ old('_form') === 'daily-edit' ? old('rupee') : '' }}" class="form-control" step="0.01" min="0" required dir="ltr">
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
            var rows = $('#dailyRows');

            function updateCreateTotal() {
                var total = 0;
                rows.find('[data-xp-amount]').each(function () { total += parseFloat(this.value) || 0; });
                $('#dailyCreateTotal').text(xpMoney(total));
                rows.find('[data-xp-remove]').prop('disabled', rows.children().length === 1);
            }

            $('#dailyAddRow').on('click', function () {
                var row = rows.children().first().clone();
                row.find('input').val('');
                rows.append(row);
                updateCreateTotal();
                row.find('input').first().trigger('focus');
            });

            rows.on('click', '[data-xp-remove]', function () {
                if (rows.children().length > 1) {
                    $(this).closest('[data-xp-entry]').remove();
                    updateCreateTotal();
                }
            });
            rows.on('input', '[data-xp-amount]', updateCreateTotal);
            updateCreateTotal();

            $('#dailyCreateModal').on('shown.bs.modal', function () {
                rows.find('input').first().trigger('focus');
            });

            function openEdit(record, keepInput) {
                $('#dailyEditForm').attr('action', record.action);
                $('#dailyEditId').val(record.id);
                if (!keepInput) {
                    $('#dailyEditName').val(record.name);
                    $('#dailyEditAmount').val(record.amount);
                    $('#dailyEditModal .alert-danger').remove();
                }
                $('#dailyEditModal').modal('show');
            }

            $(document).on('click', '[data-daily-edit]', function () {
                var button = $(this);
                openEdit({
                    id: button.data('id'),
                    name: button.data('name'),
                    amount: button.data('amount'),
                    action: button.data('action')
                }, false);
            });

            // Reopen after a failed submit, or when arriving from the old create/edit URLs.
            // The record comes from the server because it may be on another page.
            var failedForm = @json(old('_form'));
            var params = new URLSearchParams(window.location.search);
            var editTarget = @json($editTarget);

            if (editTarget) {
                openEdit(editTarget, failedForm === 'daily-edit');
            } else if (failedForm === 'daily-create' || params.get('modal') === 'create') {
                $('#dailyCreateModal').modal('show');
            }
        });
    </script>
@endpush
