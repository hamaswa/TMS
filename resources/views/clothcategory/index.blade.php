@extends('main')

@push('styles')
    <style>
        .cloth-category-directory { padding: 28px 0 48px; }
        .cloth-category-card { overflow: visible; border: 1px solid #e3ebf5; border-radius: 22px; background: #fff; box-shadow: 0 18px 45px rgba(30, 64, 103, .08); }
        .cloth-category-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 24px; padding: 28px 30px 22px; }
        .cloth-category-title { margin: 0; color: #102a52; font-size: clamp(1.65rem, 3vw, 2.35rem); font-weight: 800; }
        .cloth-category-subtitle { margin: 4px 0 0; color: #7185a2; font-size: .94rem; }
        .cloth-category-add { display: inline-flex; align-items: center; justify-content: center; gap: 10px; min-height: 48px; padding: 9px 20px; border: 0; border-radius: 12px; background: linear-gradient(135deg, #1670ef, #0d5bdb); box-shadow: 0 9px 22px rgba(22, 112, 239, .23); color: #fff; font-weight: 800; transition: transform .18s ease, box-shadow .18s ease; }
        .cloth-category-add:hover, .cloth-category-add:focus { transform: translateY(-1px); box-shadow: 0 12px 27px rgba(22, 112, 239, .3); color: #fff; text-decoration: none; }
        .cloth-category-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 18px; padding: 0 30px 22px; }
        .cloth-category-search { position: relative; width: min(100%, 390px); }
        .cloth-category-search i { position: absolute; top: 50%; left: 16px; z-index: 1; color: #8394ab; transform: translateY(-50%); pointer-events: none; }
        .cloth-category-search .form-control { min-height: 48px; padding-right: 16px; padding-left: 46px; border: 1px solid #d9e4f1; border-radius: 12px; background: #fbfdff; color: #213a5d; box-shadow: none; }
        .cloth-category-search .form-control:focus { border-color: #4b94f3; background: #fff; box-shadow: 0 0 0 3px rgba(22, 112, 239, .1); }
        .cloth-category-count { display: inline-flex; align-items: center; gap: 8px; padding: 8px 13px; border-radius: 999px; background: #edf5ff; color: #1769df; font-size: .82rem; font-weight: 800; white-space: nowrap; }
        .cloth-category-hint { margin: 0 30px 18px; padding: 12px 16px; border-radius: 12px; background: #f6f9fd; color: #4f6480; font-size: .88rem; }
        .cloth-category-table-shell { margin: 0 30px 24px; border: 1px solid #e0e9f3; border-radius: 16px; background: #fff; }
        .cloth-category-table { width: 100%; margin: 0; border-collapse: separate; border-spacing: 0; }
        .cloth-category-table thead th { padding: 15px 20px; border: 0; border-bottom: 1px solid #dfe8f2; background: #f4f8fd; color: #4f6480; font-size: .85rem; font-weight: 800; vertical-align: middle; }
        .cloth-category-table tbody td { padding: 15px 20px; border: 0; border-bottom: 1px solid #e8eef5; color: #183354; vertical-align: middle; }
        .cloth-category-table tbody tr:last-child td { border-bottom: 0; }
        .cloth-category-table tbody tr:hover { background: #f9fbfe; }
        .cloth-category-table tr.is-child td { background: #fcfdff; }
        .cloth-category-table tr.is-inactive .cloth-category-name-cell { opacity: .55; }
        .cloth-category-name-cell { display: flex; align-items: center; gap: 12px; }
        .is-child .cloth-category-name-cell { padding-right: 34px; }
        .cloth-category-branch { color: #a5b5c9; }
        .cloth-category-icon { display: inline-flex; align-items: center; justify-content: center; width: 44px; height: 44px; border-radius: 12px; background: #eef5ff; color: #1769df; }
        .is-child .cloth-category-icon { width: 36px; height: 36px; border-radius: 10px; background: #f2f6fb; color: #5a7699; }
        .cloth-category-name { font-size: 1rem; font-weight: 800; }
        .cloth-category-name-en { display: block; color: #7185a2; font-size: .82rem; font-weight: 600; }
        .cloth-category-pill { display: inline-flex; align-items: center; gap: 6px; padding: 5px 11px; border-radius: 999px; font-size: .8rem; font-weight: 800; white-space: nowrap; }
        .cloth-category-pill.is-count { background: #eef4fb; color: #29496d; }
        .cloth-category-pill.is-unit { background: #f3effd; color: #5b3fb5; }
        .cloth-category-pill.is-active { background: #e8f8ef; color: #198754; }
        .cloth-category-pill.is-off { background: #fdf0f0; color: #b02a37; }
        .cloth-category-actions { position: relative; display: inline-block; }
        .cloth-category-actions-toggle { display: inline-flex; align-items: center; justify-content: center; width: 43px; height: 43px; padding: 0; border: 1px solid #dce6f1; border-radius: 11px; background: #f6f9fd; color: #1d3c62; box-shadow: none; }
        .cloth-category-actions-toggle:hover, .cloth-category-actions-toggle:focus, .cloth-category-actions.show .cloth-category-actions-toggle { border-color: #bcd4f4; background: #eaf3ff; color: #1769df; }
        .cloth-category-actions .dropdown-menu { min-width: 230px; padding: 7px; border: 1px solid #dce6f1; border-radius: 12px; box-shadow: 0 16px 36px rgba(30, 64, 103, .18); text-align: right; }
        .cloth-category-actions .dropdown-item { display: flex; align-items: center; gap: 10px; width: 100%; padding: 9px 11px; border: 0; border-radius: 8px; background: transparent; color: #38516f; font-size: .86rem; font-weight: 700; text-align: right; }
        .cloth-category-actions .dropdown-item i { width: 19px; color: #1769df; text-align: center; }
        .cloth-category-actions .dropdown-item:hover, .cloth-category-actions .dropdown-item:focus { background: #eef5ff; color: #1769df; }
        .cloth-category-actions .cloth-category-delete, .cloth-category-actions .cloth-category-delete i { color: #dc3545; }
        .cloth-category-empty { padding: 28px 20px; color: #7185a2; text-align: center; }

        .cloth-category-form-modal .modal-content { border: 0; border-radius: 18px; text-align: right; }
        .cloth-category-form-modal .modal-header { align-items: center; border-bottom-color: #e8eef5; }
        .cloth-category-form-modal .modal-header .close { margin: -1rem auto -1rem -1rem; }
        .cloth-category-form-modal .modal-title { color: #102a52; font-weight: 800; }
        .cloth-category-form-modal label { color: #38516f; font-weight: 700; }
        .cloth-category-form-modal .modal-footer { justify-content: flex-start; gap: 8px; border-top-color: #e8eef5; }

        @media (max-width: 767.98px) {
            .cloth-category-directory { padding-top: 16px; }
            .cloth-category-card { border-radius: 16px; }
            .cloth-category-header, .cloth-category-toolbar { align-items: stretch; flex-direction: column; padding-right: 18px; padding-left: 18px; }
            .cloth-category-toolbar { gap: 12px; }
            .cloth-category-add, .cloth-category-search { width: 100%; }
            .cloth-category-count { align-self: flex-start; }
            .cloth-category-hint { margin-right: 12px; margin-left: 12px; }
            .cloth-category-table-shell { margin-right: 12px; margin-left: 12px; overflow-x: auto; }
            .cloth-category-table { min-width: 620px; }
        }
    </style>
@endpush

@section('content')
    @php
        $categoryCount = $categories->count() + $categories->sum(fn ($category) => $category->children->count());
    @endphp
    <section class="main-content cloth-category-directory">
        <div class="container-fluid px-3 px-lg-4">
            @if (Session::has('insert'))
                <div class="alert alert-success" role="alert">{{ Session::get('insert') }}</div>
            @endif
            @if (Session::has('update'))
                <div class="alert alert-warning" role="alert">{{ Session::get('update') }}</div>
            @endif
            @if (Session::has('delete'))
                <div class="alert alert-danger" role="alert">{{ Session::get('delete') }}</div>
            @endif

            <div class="cloth-category-card">
                <div class="cloth-category-header">
                    <div>
                        <h1 class="cloth-category-title">کپڑے کی کیٹیگریز</h1>
                        <p class="cloth-category-subtitle">مردانہ، زنانہ، عبایا یا کوئی بھی گروپ بنائیں اور اس کے اندر سب کیٹیگریز رکھیں۔</p>
                    </div>
                    <button type="button" class="cloth-category-add" data-cloth-category-create>
                        <i class="fas fa-plus" aria-hidden="true"></i>
                        <span>نئی کیٹیگری شامل کریں</span>
                    </button>
                </div>

                <div class="cloth-category-toolbar">
                    <div class="cloth-category-search">
                        <label for="clothCategorySearch" class="sr-only">کیٹیگری تلاش کریں</label>
                        <input type="search" id="clothCategorySearch" class="form-control" placeholder="کیٹیگری تلاش کریں۔۔۔" autocomplete="off">
                        <i class="fas fa-search" aria-hidden="true"></i>
                    </div>
                    <span class="cloth-category-count">
                        <i class="fas fa-sitemap" aria-hidden="true"></i>
                        کل {{ $categoryCount }} کیٹیگریز
                    </span>
                </div>

                <p class="cloth-category-hint">
                    <i class="fas fa-info-circle ml-1" aria-hidden="true"></i>
                    کیٹیگری بتاتی ہے کہ کپڑا کس کے لیے ہے (مثلاً زنانہ › عبایا)۔ کپڑے کا نام جیسے واش اینڈ ویئر یا لان "قسم" میں رکھیں، کیٹیگری میں نہیں۔
                </p>

                <div class="cloth-category-table-shell">
                    <table class="table cloth-category-table" id="clothCategoryTable">
                        <thead>
                            <tr>
                                <th scope="col">کیٹیگری</th>
                                <th scope="col">گنتی</th>
                                <th scope="col">کپڑے</th>
                                <th scope="col">حالت</th>
                                <th scope="col">ترتیب</th>
                                <th scope="col">عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($categories as $category)
                                @include('clothcategory.partials.row', ['category' => $category, 'parent' => null])
                                @foreach ($category->children as $child)
                                    @include('clothcategory.partials.row', ['category' => $child, 'parent' => $category])
                                @endforeach
                            @empty
                                <tr><td colspan="6" class="cloth-category-empty">ابھی کوئی کیٹیگری شامل نہیں کی گئی۔</td></tr>
                            @endforelse
                            <tr class="d-none" data-cloth-category-no-match><td colspan="6" class="cloth-category-empty">اس نام کی کوئی کیٹیگری نہیں ملی۔</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade cloth-category-form-modal" id="clothCategoryFormModal" tabindex="-1" role="dialog" aria-labelledby="clothCategoryFormModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" dir="rtl">
                <form id="clothCategoryForm" action="{{ route('admin.clothcategory.store') }}" data-store-action="{{ route('admin.clothcategory.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="_method" value="PUT" id="clothCategoryFormMethod" disabled>
                    <input type="hidden" name="_cloth_category_form" value="1">
                    <input type="hidden" name="_cloth_category_id" id="clothCategoryFormId" value="{{ old('_cloth_category_id') }}">

                    <div class="modal-header">
                        <h5 class="modal-title" id="clothCategoryFormModalTitle">نئی کیٹیگری شامل کریں</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="بند کریں">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        @if ($errors->any() && old('_cloth_category_form'))
                            <div class="alert alert-danger">
                                @foreach ($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        <div class="form-group">
                            <label for="clothCategoryFormNameUr">نام (اردو)</label>
                            <input type="text" class="form-control" id="clothCategoryFormNameUr" name="name_ur" value="{{ old('name_ur') }}" maxlength="100" placeholder="مثلاً زنانہ یا عبایا" required>
                        </div>

                        <div class="form-group">
                            <label for="clothCategoryFormName">Name (English)</label>
                            <input type="text" class="form-control" id="clothCategoryFormName" name="name" value="{{ old('name') }}" maxlength="100" dir="ltr" placeholder="e.g. Women or Abayas" required>
                        </div>

                        <div class="form-group">
                            <label for="clothCategoryFormParent">مین کیٹیگری</label>
                            <select class="form-control" id="clothCategoryFormParent" name="parent_id">
                                <option value="">کوئی نہیں (یہ خود مین کیٹیگری ہے)</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" data-unit="{{ $category->stock_unit }}" @selected((string) old('parent_id') === (string) $category->id)>{{ $category->localizedName() }}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted" id="clothCategoryFormParentHint">مثلاً "عبایا" کے لیے مین کیٹیگری "زنانہ" چنیں۔</small>
                        </div>

                        <div class="form-group">
                            <label for="clothCategoryFormUnit">اسٹاک کس میں گنا جائے؟</label>
                            <select class="form-control" id="clothCategoryFormUnit" name="stock_unit">
                                @foreach (\App\Models\ClothCategory::UNIT_LABELS as $unit => $label)
                                    <option value="{{ $unit }}" @selected(old('stock_unit', \App\Models\ClothCategory::UNIT_METER) === $unit)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted" id="clothCategoryFormUnitHint">مردانہ کپڑا میٹر میں، زنانہ ٹو پیس / تھری پیس سوٹ میں۔</small>
                        </div>

                        <div class="form-group mb-0">
                            <label for="clothCategoryFormSort">ترتیب</label>
                            <input type="number" class="form-control" id="clothCategoryFormSort" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" max="9999" dir="ltr">
                            <small class="form-text text-muted">کم نمبر والی کیٹیگری پہلے دکھائی جائے گی۔</small>
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
    <script>
        jQuery(function ($) {
            var modal = $('#clothCategoryFormModal');
            var form = $('#clothCategoryForm');
            var parentSelect = $('#clothCategoryFormParent');
            var defaultParentHint = $('#clothCategoryFormParentHint').text();
            var unitSelect = $('#clothCategoryFormUnit');
            var defaultUnitHint = $('#clothCategoryFormUnitHint').text();

            // A new sub category starts with its main category's unit.
            parentSelect.on('change', function () {
                var parentUnit = parentSelect.find('option:selected').data('unit');
                if (parentUnit && !unitSelect.prop('disabled') && !$('#clothCategoryFormId').val()) {
                    unitSelect.val(parentUnit);
                }
            });

            function openCategoryModal(category, keepInput) {
                var editing = !!category;

                form.attr('action', editing ? category.action : form.data('store-action'));
                $('#clothCategoryFormMethod').prop('disabled', !editing);
                $('#clothCategoryFormId').val(editing ? category.id : '');
                $('#clothCategoryFormModalTitle').text(editing ? 'کیٹیگری میں ترمیم کریں' : 'نئی کیٹیگری شامل کریں');

                // A category can't sit under itself, and one with children has to stay top-level.
                parentSelect.find('option').prop('disabled', false);
                if (editing) {
                    parentSelect.find('option[value="' + category.id + '"]').prop('disabled', true);
                }
                var locked = editing && category.hasChildren;
                parentSelect.prop('disabled', locked);
                $('#clothCategoryFormParentHint').text(locked ? 'اس کیٹیگری میں سب کیٹیگریز ہیں، اس لیے یہ مین کیٹیگری ہی رہے گی۔' : defaultParentHint);

                // Stock already counted in this unit can't be reinterpreted.
                var unitLocked = editing && category.hasCloths;
                unitSelect.prop('disabled', unitLocked);
                $('#clothCategoryFormUnitHint').text(unitLocked ? 'اس کیٹیگری میں کپڑے موجود ہیں، اس لیے گنتی کا طریقہ نہیں بدلا جا سکتا۔' : defaultUnitHint);

                if (!keepInput) {
                    unitSelect.val(editing ? category.unit : 'meter');
                    $('#clothCategoryFormNameUr').val(editing ? category.nameUr : '');
                    $('#clothCategoryFormName').val(editing ? category.name : '');
                    parentSelect.val(editing ? (category.parentId || '') : '');
                    $('#clothCategoryFormSort').val(editing ? category.sortOrder : 0);
                    form.find('.alert-danger').remove();
                }
                modal.modal('show');
            }

            function categoryFromButton(button) {
                button = $(button);
                return {
                    id: button.data('id'),
                    nameUr: button.data('name-ur'),
                    name: button.data('name'),
                    parentId: button.data('parent-id'),
                    sortOrder: button.data('sort-order'),
                    hasChildren: button.data('has-children') === 1,
                    hasCloths: button.data('has-cloths') === 1,
                    unit: button.data('unit'),
                    action: button.data('action')
                };
            }

            $('[data-cloth-category-create]').on('click', function () {
                openCategoryModal(null, false);
            });

            $(document).on('click', '[data-cloth-category-edit]', function () {
                openCategoryModal(categoryFromButton(this), false);
            });

            modal.on('shown.bs.modal', function () {
                $('#clothCategoryFormNameUr').trigger('focus');
            });

            // Search keeps the tree readable: a matching child also shows its parent row.
            $('#clothCategorySearch').on('input', function () {
                var term = this.value.trim().toLowerCase();
                var rows = $('#clothCategoryTable tbody tr[data-category-row]');
                var visible = 0;

                rows.each(function () {
                    var row = $(this);
                    var match = !term || String(row.data('search')).toLowerCase().indexOf(term) !== -1;
                    row.toggleClass('d-none', !match);
                    if (match) {
                        visible++;
                        if (row.data('parent-row')) {
                            rows.filter('[data-category-id="' + row.data('parent-row') + '"]').removeClass('d-none');
                        }
                    }
                });
                $('[data-cloth-category-no-match]').toggleClass('d-none', visible > 0 || rows.length === 0);
            });

            // Reopen after a failed submit.
            var failedId = @json(old('_cloth_category_form') ? old('_cloth_category_id') : null);
            var hasFailed = @json((bool) old('_cloth_category_form'));

            if (failedId) {
                var button = $('[data-cloth-category-edit][data-id="' + failedId + '"]').first();
                if (button.length) {
                    openCategoryModal(categoryFromButton(button), true);
                }
            } else if (hasFailed) {
                openCategoryModal(null, true);
            }
        });
    </script>
@endpush
