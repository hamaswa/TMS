@extends('main')

@push('styles')
    <style>
        .cloth-type-directory { padding: 28px 0 48px; }
        .cloth-type-card { overflow: visible; border: 1px solid #e3ebf5; border-radius: 22px; background: #fff; box-shadow: 0 18px 45px rgba(30, 64, 103, .08); }
        .cloth-type-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 24px; padding: 28px 30px 22px; }
        .cloth-type-title { margin: 0; color: #102a52; font-size: clamp(1.65rem, 3vw, 2.35rem); font-weight: 800; }
        .cloth-type-subtitle { margin: 4px 0 0; color: #7185a2; font-size: .94rem; }
        .cloth-type-add { display: inline-flex; align-items: center; justify-content: center; gap: 10px; min-height: 48px; padding: 9px 20px; border: 0; border-radius: 12px; background: linear-gradient(135deg, #1670ef, #0d5bdb); box-shadow: 0 9px 22px rgba(22, 112, 239, .23); color: #fff; font-weight: 800; transition: transform .18s ease, box-shadow .18s ease; }
        .cloth-type-add:hover, .cloth-type-add:focus { transform: translateY(-1px); box-shadow: 0 12px 27px rgba(22, 112, 239, .3); color: #fff; text-decoration: none; }
        .cloth-type-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 18px; padding: 0 30px 22px; }
        .cloth-type-search { position: relative; width: min(100%, 390px); }
        .cloth-type-search i { position: absolute; top: 50%; left: 16px; z-index: 1; color: #8394ab; transform: translateY(-50%); pointer-events: none; }
        .cloth-type-search .form-control { min-height: 48px; padding-right: 16px; padding-left: 46px; border: 1px solid #d9e4f1; border-radius: 12px; background: #fbfdff; color: #213a5d; box-shadow: none; }
        .cloth-type-search .form-control:focus { border-color: #4b94f3; background: #fff; box-shadow: 0 0 0 3px rgba(22, 112, 239, .1); }
        .cloth-type-count { display: inline-flex; align-items: center; gap: 8px; padding: 8px 13px; border-radius: 999px; background: #edf5ff; color: #1769df; font-size: .82rem; font-weight: 800; white-space: nowrap; }
        .cloth-type-table-shell { margin: 0 30px 24px; border: 1px solid #e0e9f3; border-radius: 16px; background: #fff; }
        .cloth-type-table { width: 100% !important; margin: 0 !important; border-collapse: separate; border-spacing: 0; }
        .cloth-type-table thead th { padding: 15px 20px; border: 0; border-bottom: 1px solid #dfe8f2; background: #f4f8fd; color: #4f6480; font-size: .85rem; font-weight: 800; vertical-align: middle; }
        .cloth-type-table tbody td { padding: 17px 20px; border: 0; border-bottom: 1px solid #e8eef5; color: #183354; vertical-align: middle; }
        .cloth-type-table tbody tr:last-child td { border-bottom: 0; }
        .cloth-type-table tbody tr { transition: background-color .18s ease; }
        .cloth-type-table tbody tr:hover { background: #f9fbfe; }
        .cloth-type-number { display: inline-flex; align-items: center; justify-content: center; min-width: 36px; height: 34px; padding: 0 9px; border-radius: 9px; background: #eef4fb; color: #29496d; font-family: Arial, sans-serif; font-weight: 800; }
        .cloth-type-name-cell { display: flex; align-items: center; gap: 12px; }
        .cloth-type-icon { display: inline-flex; align-items: center; justify-content: center; width: 44px; height: 44px; border-radius: 12px; background: #eef5ff; color: #1769df; }
        .cloth-type-name { font-size: 1rem; font-weight: 800; }
        .cloth-type-actions { position: relative; display: inline-block; }
        .cloth-type-actions-toggle { display: inline-flex; align-items: center; justify-content: center; width: 43px; height: 43px; padding: 0; border: 1px solid #dce6f1; border-radius: 11px; background: #f6f9fd; color: #1d3c62; box-shadow: none; transition: background-color .18s ease, border-color .18s ease, color .18s ease; }
        .cloth-type-actions-toggle:hover, .cloth-type-actions-toggle:focus, .cloth-type-actions.show .cloth-type-actions-toggle { border-color: #bcd4f4; background: #eaf3ff; color: #1769df; }
        .cloth-type-actions .dropdown-menu { min-width: 215px; padding: 7px; border: 1px solid #dce6f1; border-radius: 12px; box-shadow: 0 16px 36px rgba(30, 64, 103, .18); text-align: right; }
        .cloth-type-actions .dropdown-item { display: flex; align-items: center; gap: 10px; width: 100%; padding: 9px 11px; border: 0; border-radius: 8px; background: transparent; color: #38516f; font-size: .86rem; font-weight: 700; text-align: right; }
        .cloth-type-actions .dropdown-item i { width: 19px; color: #1769df; text-align: center; }
        .cloth-type-actions .dropdown-item:hover, .cloth-type-actions .dropdown-item:focus { background: #eef5ff; color: #1769df; }
        .cloth-type-actions .cloth-type-delete, .cloth-type-actions .cloth-type-delete i { color: #dc3545; }
        .cloth-type-table-shell .dataTables_filter, .cloth-type-table-shell .dataTables_length { display: none; }
        .cloth-type-table-shell .dataTables_info { padding: 17px 18px !important; color: #7185a2; font-size: .83rem; }
        .cloth-type-table-shell .dataTables_paginate { display: flex; align-items: center; gap: 5px; padding: 12px 16px 15px !important; }
        .cloth-type-table-shell .dataTables_paginate .paginate_button { display: inline-flex !important; align-items: center; justify-content: center; min-width: 37px; height: 37px; margin: 0 !important; padding: 4px 10px !important; border: 1px solid #dbe5f0 !important; border-radius: 9px !important; background: #fff !important; color: #44607f !important; box-shadow: none !important; }
        .cloth-type-table-shell .dataTables_paginate .paginate_button.current, .cloth-type-table-shell .dataTables_paginate .paginate_button.current:hover { border-color: #1769df !important; background: #1769df !important; color: #fff !important; }
        .cloth-type-table-shell .dataTables_paginate .paginate_button:hover { border-color: #bad2f3 !important; background: #edf5ff !important; color: #1769df !important; }

        @media (min-width: 768px) {
            .cloth-type-table-shell, .cloth-type-table-shell .dataTables_wrapper { overflow: visible; }
        }

        @media (max-width: 767.98px) {
            .cloth-type-directory { padding-top: 16px; }
            .cloth-type-card { border-radius: 16px; }
            .cloth-type-header, .cloth-type-toolbar { align-items: stretch; flex-direction: column; padding-right: 18px; padding-left: 18px; }
            .cloth-type-toolbar { gap: 12px; }
            .cloth-type-add, .cloth-type-search { width: 100%; }
            .cloth-type-count { align-self: flex-start; }
            .cloth-type-table-shell { margin-right: 12px; margin-left: 12px; overflow-x: auto; }
            .cloth-type-table { min-width: 560px; }
        }
    </style>
@endpush

@section('content')
    <section class="main-content cloth-type-directory">
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

            <div class="cloth-type-card">
                <div class="cloth-type-header">
                    <div>
                        <h1 class="cloth-type-title">کپڑے کی اقسام</h1>
                        <p class="cloth-type-subtitle">اپنی دکان میں دستیاب کپڑے کی تمام اقسام ایک جگہ منظم کریں۔</p>
                    </div>
                    <a href="{{ route('admin.clothtype.create') }}" class="cloth-type-add">
                        <i class="fas fa-plus" aria-hidden="true"></i>
                        <span>نئی قسم شامل کریں</span>
                    </a>
                </div>

                <div class="cloth-type-toolbar">
                    <div class="cloth-type-search">
                        <label for="clothTypeDirectorySearch" class="sr-only">کپڑے کی قسم تلاش کریں</label>
                        <input type="search" id="clothTypeDirectorySearch" class="form-control" placeholder="کپڑے کی قسم تلاش کریں۔۔۔" autocomplete="off">
                        <i class="fas fa-search" aria-hidden="true"></i>
                    </div>
                    <span class="cloth-type-count">
                        <i class="fas fa-tags" aria-hidden="true"></i>
                        کل {{ $cloth_types->count() }} اقسام
                    </span>
                </div>

                <div class="cloth-type-table-shell">
                    <table class="table cloth-type-table" id="clothTypeDirectoryTable">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">نام</th>
                                <th scope="col">عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($cloth_types as $cloth_type)
                                <tr>
                                    <td><span class="cloth-type-number">{{ $loop->iteration }}</span></td>
                                    <td>
                                        <div class="cloth-type-name-cell">
                                            <span class="cloth-type-icon"><i class="fas fa-tag" aria-hidden="true"></i></span>
                                            <span class="cloth-type-name">{{ $cloth_type->name }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="dropdown cloth-type-actions">
                                            <button class="btn cloth-type-actions-toggle" type="button" id="clothTypeActions{{ $cloth_type->id }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="{{ $cloth_type->name }} کی کارروائیاں">
                                                <i class="fas fa-ellipsis-v" aria-hidden="true"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-left" aria-labelledby="clothTypeActions{{ $cloth_type->id }}" dir="rtl">
                                                <a href="{{ route('admin.clothtype.edit', $cloth_type->id) }}" class="dropdown-item">
                                                    <i class="fas fa-pen" aria-hidden="true"></i>
                                                    <span>قسم میں ترمیم کریں</span>
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                <form action="{{ route('admin.clothtype.destroy', $cloth_type->id) }}" method="POST" data-confirm="کیا آپ واقعی یہ کپڑے کی قسم حذف کرنا چاہتے ہیں؟">
                                                    @csrf
                                                    @method('delete')
                                                    <button class="dropdown-item cloth-type-delete" type="submit">
                                                        <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                                        <span>قسم حذف کریں</span>
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
@endsection

@push('scripts')
    <script>
        jQuery(function ($) {
            var tableElement = $('#clothTypeDirectoryTable');

            if (!tableElement.length || !$.fn.DataTable) {
                return;
            }

            var clothTypeTable = tableElement.DataTable({
                pageLength: 10,
                lengthChange: false,
                ordering: false,
                autoWidth: false,
                dom: 'rtip',
                language: {
                    emptyTable: 'ابھی کپڑے کی کوئی قسم شامل نہیں کی گئی۔',
                    zeroRecords: 'اس نام کی کوئی قسم نہیں ملی۔',
                    info: 'کل _TOTAL_ اقسام میں سے _START_ تا _END_ دکھائی جا رہی ہیں',
                    infoEmpty: 'کوئی قسم موجود نہیں۔',
                    paginate: {
                        next: '<i class="fas fa-chevron-left" aria-hidden="true"></i>',
                        previous: '<i class="fas fa-chevron-right" aria-hidden="true"></i>'
                    }
                },
                columnDefs: [
                    { targets: [0, 2], searchable: false }
                ]
            });

            $('#clothTypeDirectorySearch').on('input', function () {
                clothTypeTable.search(this.value).draw();
            });
        });
    </script>
@endpush
