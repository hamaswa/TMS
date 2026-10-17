@extends('main')

@push('styles')
    <style>
        .brand-directory { padding: 28px 0 48px; }
        .brand-directory-card { overflow: visible; border: 1px solid #e3ebf5; border-radius: 22px; background: #fff; box-shadow: 0 18px 45px rgba(30, 64, 103, .08); }
        .brand-directory-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 24px; padding: 28px 30px 22px; }
        .brand-directory-title { margin: 0; color: #102a52; font-size: clamp(1.65rem, 3vw, 2.35rem); font-weight: 800; }
        .brand-directory-subtitle { margin: 4px 0 0; color: #7185a2; font-size: .94rem; }
        .brand-add-button { display: inline-flex; align-items: center; justify-content: center; gap: 10px; min-height: 48px; padding: 9px 20px; border: 0; border-radius: 12px; background: linear-gradient(135deg, #1670ef, #0d5bdb); box-shadow: 0 9px 22px rgba(22, 112, 239, .23); color: #fff; font-weight: 800; transition: transform .18s ease, box-shadow .18s ease; }
        .brand-add-button:hover, .brand-add-button:focus { transform: translateY(-1px); box-shadow: 0 12px 27px rgba(22, 112, 239, .3); color: #fff; text-decoration: none; }
        .brand-directory-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 18px; padding: 0 30px 22px; }
        .brand-search { position: relative; width: min(100%, 390px); }
        .brand-search i { position: absolute; top: 50%; left: 16px; z-index: 1; color: #8394ab; transform: translateY(-50%); pointer-events: none; }
        .brand-search .form-control { min-height: 48px; padding-right: 16px; padding-left: 46px; border: 1px solid #d9e4f1; border-radius: 12px; background: #fbfdff; color: #213a5d; box-shadow: none; }
        .brand-search .form-control:focus { border-color: #4b94f3; background: #fff; box-shadow: 0 0 0 3px rgba(22, 112, 239, .1); }
        .brand-count { display: inline-flex; align-items: center; gap: 8px; padding: 8px 13px; border-radius: 999px; background: #edf5ff; color: #1769df; font-size: .82rem; font-weight: 800; white-space: nowrap; }
        .brand-table-shell { margin: 0 30px 24px; border: 1px solid #e0e9f3; border-radius: 16px; background: #fff; }
        .brand-table { width: 100% !important; margin: 0 !important; border-collapse: separate; border-spacing: 0; }
        .brand-table thead th { padding: 15px 20px; border: 0; border-bottom: 1px solid #dfe8f2; background: #f4f8fd; color: #4f6480; font-size: .85rem; font-weight: 800; vertical-align: middle; }
        .brand-table tbody td { padding: 15px 20px; border: 0; border-bottom: 1px solid #e8eef5; color: #183354; vertical-align: middle; }
        .brand-table tbody tr:last-child td { border-bottom: 0; }
        .brand-table tbody tr { transition: background-color .18s ease; }
        .brand-table tbody tr:hover { background: #f9fbfe; }
        .brand-number { display: inline-flex; align-items: center; justify-content: center; min-width: 36px; height: 34px; padding: 0 9px; border-radius: 9px; background: #eef4fb; color: #29496d; font-family: Arial, sans-serif; font-weight: 800; }
        .brand-name { font-size: 1rem; font-weight: 800; }
        .brand-logo { width: 56px; height: 56px; border: 1px solid #e2eaf3; border-radius: 13px; background: #f7faff; object-fit: cover; box-shadow: 0 4px 12px rgba(30, 64, 103, .08); }
        .brand-actions { position: relative; display: inline-block; }
        .brand-actions-toggle { display: inline-flex; align-items: center; justify-content: center; width: 43px; height: 43px; padding: 0; border: 1px solid #dce6f1; border-radius: 11px; background: #f6f9fd; color: #1d3c62; box-shadow: none; transition: background-color .18s ease, border-color .18s ease, color .18s ease; }
        .brand-actions-toggle:hover, .brand-actions-toggle:focus, .brand-actions.show .brand-actions-toggle { border-color: #bcd4f4; background: #eaf3ff; color: #1769df; }
        .brand-actions .dropdown-menu { min-width: 215px; padding: 7px; border: 1px solid #dce6f1; border-radius: 12px; box-shadow: 0 16px 36px rgba(30, 64, 103, .18); text-align: right; }
        .brand-actions .dropdown-item { display: flex; align-items: center; gap: 10px; width: 100%; padding: 9px 11px; border: 0; border-radius: 8px; background: transparent; color: #38516f; font-size: .86rem; font-weight: 700; text-align: right; }
        .brand-actions .dropdown-item i { width: 19px; color: #1769df; text-align: center; }
        .brand-actions .dropdown-item:hover, .brand-actions .dropdown-item:focus { background: #eef5ff; color: #1769df; }
        .brand-actions .brand-delete-action, .brand-actions .brand-delete-action i { color: #dc3545; }
        .brand-table-shell .dataTables_filter, .brand-table-shell .dataTables_length { display: none; }
        .brand-table-shell .dataTables_info { padding: 17px 18px !important; color: #7185a2; font-size: .83rem; }
        .brand-table-shell .dataTables_paginate { display: flex; align-items: center; gap: 5px; padding: 12px 16px 15px !important; }
        .brand-table-shell .dataTables_paginate .paginate_button { display: inline-flex !important; align-items: center; justify-content: center; min-width: 37px; height: 37px; margin: 0 !important; padding: 4px 10px !important; border: 1px solid #dbe5f0 !important; border-radius: 9px !important; background: #fff !important; color: #44607f !important; box-shadow: none !important; }
        .brand-table-shell .dataTables_paginate .paginate_button.current, .brand-table-shell .dataTables_paginate .paginate_button.current:hover { border-color: #1769df !important; background: #1769df !important; color: #fff !important; }
        .brand-table-shell .dataTables_paginate .paginate_button:hover { border-color: #bad2f3 !important; background: #edf5ff !important; color: #1769df !important; }

        .brand-form-modal .modal-content { border: 0; border-radius: 18px; text-align: right; }
        .brand-form-modal .modal-header { align-items: center; border-bottom-color: #e8eef5; }
        .brand-form-modal .modal-header .close { margin: -1rem auto -1rem -1rem; }
        .brand-form-modal .modal-title { color: #102a52; font-weight: 800; }
        .brand-form-modal label { color: #38516f; font-weight: 700; }
        .brand-form-modal .modal-footer { justify-content: flex-start; gap: 8px; border-top-color: #e8eef5; }
        .brand-form-preview { display: flex; align-items: center; gap: 14px; }
        .brand-form-preview img { flex: 0 0 64px; width: 64px; height: 64px; border: 1px solid #e2eaf3; border-radius: 13px; background: #f7faff; object-fit: cover; }

        @media (min-width: 768px) {
            .brand-table-shell, .brand-table-shell .dataTables_wrapper { overflow: visible; }
        }

        @media (max-width: 767.98px) {
            .brand-directory { padding-top: 16px; }
            .brand-directory-card { border-radius: 16px; }
            .brand-directory-header, .brand-directory-toolbar { align-items: stretch; flex-direction: column; padding-right: 18px; padding-left: 18px; }
            .brand-directory-toolbar { gap: 12px; }
            .brand-add-button, .brand-search { width: 100%; }
            .brand-count { align-self: flex-start; }
            .brand-table-shell { margin-right: 12px; margin-left: 12px; overflow-x: auto; }
            .brand-table { min-width: 670px; }
        }
    </style>
@endpush

@section('content')
    <section class="main-content brand-directory">
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

            <div class="brand-directory-card">
                <div class="brand-directory-header">
                    <div>
                        <h1 class="brand-directory-title">برانڈز کی فہرست</h1>
                        <p class="brand-directory-subtitle">اپنے کپڑے کے برانڈز اور تصاویر ایک جگہ منظم کریں۔</p>
                    </div>
                    <button type="button" class="brand-add-button" data-brand-create>
                        <i class="fas fa-plus" aria-hidden="true"></i>
                        <span>نیا برانڈ شامل کریں</span>
                    </button>
                </div>

                <div class="brand-directory-toolbar">
                    <div class="brand-search">
                        <label for="brandDirectorySearch" class="sr-only">برانڈ تلاش کریں</label>
                        <input type="search" id="brandDirectorySearch" class="form-control" placeholder="برانڈ کا نام تلاش کریں۔۔۔" autocomplete="off">
                        <i class="fas fa-search" aria-hidden="true"></i>
                    </div>
                    <span class="brand-count">
                        <i class="fas fa-layer-group" aria-hidden="true"></i>
                        کل {{ $cloth_brands->count() }} برانڈز
                    </span>
                </div>

                <div class="brand-table-shell">
                    <table class="table brand-table" id="brandDirectoryTable">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">نام</th>
                                <th scope="col">برانڈ کی تصویر</th>
                                <th scope="col">عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($cloth_brands as $cloth_brand)
                                <tr>
                                    <td><span class="brand-number">{{ $loop->iteration }}</span></td>
                                    <td><span class="brand-name">{{ $cloth_brand->name }}</span></td>
                                    <td>
                                        <img class="brand-logo" src="{{ $cloth_brand->brand_logo ? asset('storage/'.$cloth_brand->brand_logo) : asset('assets/images/logo.jpg') }}" alt="{{ $cloth_brand->name }}">
                                    </td>
                                    <td>
                                        <div class="dropdown brand-actions">
                                            <button class="btn brand-actions-toggle" type="button" id="brandActions{{ $cloth_brand->id }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="{{ $cloth_brand->name }} کی کارروائیاں">
                                                <i class="fas fa-ellipsis-v" aria-hidden="true"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-left" aria-labelledby="brandActions{{ $cloth_brand->id }}" dir="rtl">
                                                <button type="button" class="dropdown-item" data-brand-edit
                                                    data-id="{{ $cloth_brand->id }}"
                                                    data-name="{{ $cloth_brand->name }}"
                                                    data-action="{{ route('admin.clothbrand.update', $cloth_brand->id) }}"
                                                    data-logo="{{ $cloth_brand->brand_logo ? asset('storage/'.$cloth_brand->brand_logo) : '' }}">
                                                    <i class="fas fa-pen" aria-hidden="true"></i>
                                                    <span>برانڈ میں ترمیم کریں</span>
                                                </button>
                                                <div class="dropdown-divider"></div>
                                                <form action="{{ route('admin.clothbrand.destroy', $cloth_brand->id) }}" method="POST" data-confirm="کیا آپ واقعی یہ برانڈ حذف کرنا چاہتے ہیں؟">
                                                    @csrf
                                                    @method('delete')
                                                    <button class="dropdown-item brand-delete-action" type="submit">
                                                        <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                                        <span>برانڈ حذف کریں</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade brand-form-modal" id="brandFormModal" tabindex="-1" role="dialog" aria-labelledby="brandFormModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" dir="rtl">
                <form id="brandForm" action="{{ route('admin.clothbrand.store') }}" data-store-action="{{ route('admin.clothbrand.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="_method" value="PUT" id="brandFormMethod" disabled>
                    <input type="hidden" name="_brand_form" value="1">
                    <input type="hidden" name="_brand_id" id="brandFormId" value="{{ old('_brand_id') }}">

                    <div class="modal-header">
                        <h5 class="modal-title" id="brandFormModalTitle">نیا برانڈ شامل کریں</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="بند کریں">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        @if ($errors->any() && old('_brand_form'))
                            <div class="alert alert-danger">
                                @foreach ($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        <div class="form-group">
                            <label for="brandFormName">نام</label>
                            <input type="text" class="form-control" id="brandFormName" name="name" value="{{ old('name') }}" maxlength="255" required>
                        </div>

                        <div class="form-group mb-0">
                            <label for="brandFormFile">برانڈ کی تصویر</label>
                            <div class="brand-form-preview">
                                <img id="brandFormPreview" src="{{ asset('assets/images/logo.jpg') }}" data-placeholder="{{ asset('assets/images/logo.jpg') }}" alt="">
                                <div class="flex-grow-1">
                                    <input type="file" class="form-control" id="brandFormFile" name="file" accept="image/*">
                                    <small class="form-text text-muted" id="brandFormFileHelp">تصویر اختیاری ہے؛ بعد میں بھی شامل کی جا سکتی ہے۔</small>
                                </div>
                            </div>
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
            var tableElement = $('#brandDirectoryTable');
            var brandTable = null;
            var modal = $('#brandFormModal');
            var form = $('#brandForm');
            var preview = $('#brandFormPreview');
            var placeholderLogo = preview.data('placeholder');

            function openBrandModal(brand, keepInput) {
                var editing = !!brand;

                form.attr('action', editing ? brand.action : form.data('store-action'));
                $('#brandFormMethod').prop('disabled', !editing);
                $('#brandFormId').val(editing ? brand.id : '');
                $('#brandFormModalTitle').text(editing ? 'برانڈ میں ترمیم کریں' : 'نیا برانڈ شامل کریں');
                $('#brandFormFileHelp').text(editing
                    ? 'نئی تصویر صرف تبدیل کرنے کے لیے منتخب کریں۔'
                    : 'تصویر اختیاری ہے؛ بعد میں بھی شامل کی جا سکتی ہے۔');
                if (!keepInput) {
                    $('#brandFormName').val(editing ? brand.name : '');
                    form.find('.alert-danger').remove();
                }
                $('#brandFormFile').val('');
                preview.attr('src', (editing && brand.logo) ? brand.logo : placeholderLogo);
                modal.modal('show');
            }

            function brandFromButton(button) {
                button = $(button);
                return {
                    id: button.data('id'),
                    name: button.data('name'),
                    action: button.data('action'),
                    logo: button.data('logo')
                };
            }

            function findEditButton(id) {
                var rows = brandTable ? $(brandTable.rows().nodes()) : tableElement;
                return rows.find('[data-brand-edit][data-id="' + id + '"]').first();
            }

            $('[data-brand-create]').on('click', function () {
                openBrandModal(null, false);
            });

            $(document).on('click', '[data-brand-edit]', function () {
                openBrandModal(brandFromButton(this), false);
            });

            $('#brandFormFile').on('change', function () {
                var file = this.files && this.files[0];
                if (file) {
                    preview.attr('src', URL.createObjectURL(file));
                }
            });

            modal.on('shown.bs.modal', function () {
                $('#brandFormName').trigger('focus');
            });

            if (tableElement.length && $.fn.DataTable) {
                brandTable = tableElement.DataTable({
                    pageLength: 10,
                    lengthChange: false,
                    ordering: false,
                    autoWidth: false,
                    dom: 'rtip',
                    language: {
                        emptyTable: 'ابھی کوئی برانڈ شامل نہیں کیا گیا۔',
                        zeroRecords: 'اس نام کا کوئی برانڈ نہیں ملا۔',
                        info: 'کل _TOTAL_ برانڈز میں سے _START_ تا _END_ دکھائے جا رہے ہیں',
                        infoEmpty: 'کوئی برانڈ موجود نہیں۔',
                        paginate: {
                            next: '<i class="fas fa-chevron-left" aria-hidden="true"></i>',
                            previous: '<i class="fas fa-chevron-right" aria-hidden="true"></i>'
                        }
                    },
                    columnDefs: [
                        { targets: [0, 2, 3], searchable: false }
                    ]
                });

                $('#brandDirectorySearch').on('input', function () {
                    brandTable.search(this.value).draw();
                });
            }

            // Reopen after a failed submit, or when arriving from the legacy create/edit URLs.
            var failedId = @json(old('_brand_form') ? old('_brand_id') : null);
            var hasFailed = @json((bool) old('_brand_form'));
            var params = new URLSearchParams(window.location.search);
            var editId = hasFailed ? failedId : params.get('edit');

            if (editId) {
                var button = findEditButton(editId);
                if (button.length) {
                    openBrandModal(brandFromButton(button), hasFailed);
                }
            } else if (hasFailed || params.get('modal') === 'create') {
                openBrandModal(null, hasFailed);
            }
        });
    </script>
@endpush
