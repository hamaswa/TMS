@extends('main')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.css">
    <style>
        .stock-directory { padding: 28px 0 48px; }
        .stock-directory-card { overflow: visible; border: 1px solid #e3ebf5; border-radius: 22px; background: #fff; box-shadow: 0 18px 45px rgba(30, 64, 103, .08); }
        .stock-directory-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 24px; padding: 28px 30px 20px; }
        .stock-directory-title { margin: 0; color: #102a52; font-size: clamp(1.65rem, 3vw, 2.35rem); font-weight: 800; }
        .stock-directory-subtitle { margin: 4px 0 0; color: #7185a2; font-size: .94rem; }
        .stock-header-actions { display: flex; align-items: center; gap: 10px; }
        .stock-sale-button { display: inline-flex; align-items: center; justify-content: center; gap: 9px; min-height: 48px; padding: 9px 20px; border-radius: 12px; background: linear-gradient(135deg, #1670ef, #0d5bdb); box-shadow: 0 9px 22px rgba(22, 112, 239, .23); color: #fff; font-weight: 800; transition: transform .18s ease, box-shadow .18s ease; }
        .stock-sale-button:hover, .stock-sale-button:focus { transform: translateY(-1px); box-shadow: 0 12px 27px rgba(22, 112, 239, .3); color: #fff; text-decoration: none; }
        .stock-page-actions-toggle { display: inline-flex; align-items: center; justify-content: center; width: 48px; height: 48px; padding: 0; border: 1px solid #dce6f1; border-radius: 12px; background: #f6f9fd; color: #1d3c62; }
        .stock-page-actions-toggle:hover, .stock-page-actions-toggle:focus, .stock-page-actions.show .stock-page-actions-toggle { border-color: #bcd4f4; background: #eaf3ff; color: #1769df; }
        .stock-page-actions .dropdown-menu { min-width: 230px; padding: 7px; border: 1px solid #dce6f1; border-radius: 12px; box-shadow: 0 16px 36px rgba(30, 64, 103, .18); text-align: right; }
        .stock-page-actions .dropdown-item { display: flex; align-items: center; gap: 10px; padding: 9px 11px; border-radius: 8px; color: #38516f; font-size: .86rem; font-weight: 700; }
        .stock-page-actions .dropdown-item i { width: 19px; color: #1769df; text-align: center; }
        .stock-page-actions .dropdown-item:hover, .stock-page-actions .dropdown-item:focus { background: #eef5ff; color: #1769df; }
        .stock-summary { display: flex; flex-wrap: wrap; gap: 10px; padding: 0 30px 20px; }
        .stock-summary-pill { display: inline-flex; align-items: center; gap: 8px; padding: 8px 13px; border-radius: 999px; background: #edf5ff; color: #1769df; font-size: .82rem; font-weight: 800; }
        .stock-summary-pill.is-value { background: #ecfaf4; color: #13865d; }
        .stock-controls { display: flex; align-items: flex-end; justify-content: space-between; gap: 18px; padding: 20px 30px; border-top: 1px solid #edf2f7; border-bottom: 1px solid #edf2f7; background: #fbfdff; }
        .stock-search { position: relative; width: min(100%, 390px); }
        .stock-search i { position: absolute; top: 50%; left: 16px; z-index: 1; color: #8394ab; transform: translateY(-50%); pointer-events: none; }
        .stock-search .form-control { min-height: 48px; padding-right: 16px; padding-left: 46px; border: 1px solid #d9e4f1; border-radius: 12px; background: #fff; color: #213a5d; box-shadow: none; }
        .stock-search .form-control:focus { border-color: #4b94f3; box-shadow: 0 0 0 3px rgba(22, 112, 239, .1); }
        .stock-date-filter { display: flex; align-items: flex-end; gap: 10px; margin: 0; }
        .stock-date-field label { display: block; margin-bottom: 4px; color: #607590; font-size: .78rem; font-weight: 800; }
        .stock-date-filter .input-group { width: 300px; }
        .stock-date-filter .form-control, .stock-date-filter .input-group-text { min-height: 48px; border-color: #d9e4f1; background: #fff; }
        .stock-date-filter .form-control { border-radius: 0 11px 11px 0; }
        .stock-date-filter .input-group-text { border-radius: 11px 0 0 11px; color: #607590; }
        .stock-date-submit { min-height: 48px; padding-right: 18px; padding-left: 18px; border-radius: 11px; font-weight: 800; white-space: nowrap; }
        .stock-table-shell { margin: 24px 30px; border: 1px solid #e0e9f3; border-radius: 16px; background: #fff; }
        .stock-list-table { width: 100% !important; min-width: 1180px; margin: 0 !important; border-collapse: separate; border-spacing: 0; }
        .stock-list-table thead th { padding: 14px 15px; border: 0; border-bottom: 1px solid #dfe8f2; background: #f4f8fd; color: #4f6480; font-size: .78rem; font-weight: 800; white-space: nowrap; vertical-align: middle; }
        .stock-list-table tbody td { padding: 14px 15px; border: 0; border-bottom: 1px solid #e8eef5; color: #183354; font-size: .9rem; font-weight: 700; white-space: nowrap; vertical-align: middle; }
        .stock-list-table tbody tr:last-child td { border-bottom: 0; }
        .stock-list-table tbody tr { transition: background-color .18s ease; }
        .stock-list-table tbody tr:hover { background: #f9fbfe; }
        .stock-row-number { display: inline-flex; align-items: center; justify-content: center; min-width: 34px; height: 32px; padding: 0 8px; border-radius: 9px; background: #eef4fb; color: #29496d; font-family: Arial, sans-serif; font-weight: 800; }
        .stock-name { color: #102f55; font-weight: 800; }
        .stock-color { display: inline-flex; align-items: center; gap: 7px; }
        .stock-color::before { width: 8px; height: 8px; border-radius: 50%; background: #83a2c6; content: ''; }
        .stock-quantity { color: #1769df; }
        .stock-money { direction: ltr; display: inline-block; font-family: Arial, sans-serif; }
        .stock-money.is-value { color: #13865d; }
        .stock-image, .stock-image-placeholder { width: 52px; height: 52px; border: 1px solid #e2eaf3; border-radius: 12px; background: #f4f8fd; object-fit: cover; }
        .stock-image-placeholder { display: inline-flex; align-items: center; justify-content: center; color: #8ea1b8; }
        .stock-table-shell .dataTables_filter, .stock-table-shell .dataTables_length { display: none; }
        .stock-table-shell .dataTables_info { padding: 17px 18px !important; color: #7185a2; font-size: .83rem; }
        .stock-table-shell .dataTables_paginate { display: flex; align-items: center; gap: 5px; padding: 12px 16px 15px !important; }
        .stock-table-shell .dataTables_paginate .paginate_button { display: inline-flex !important; align-items: center; justify-content: center; min-width: 37px; height: 37px; margin: 0 !important; padding: 4px 10px !important; border: 1px solid #dbe5f0 !important; border-radius: 9px !important; background: #fff !important; color: #44607f !important; box-shadow: none !important; }
        .stock-table-shell .dataTables_paginate .paginate_button.current, .stock-table-shell .dataTables_paginate .paginate_button.current:hover { border-color: #1769df !important; background: #1769df !important; color: #fff !important; }
        .stock-table-shell .dataTables_paginate .paginate_button:hover { border-color: #bad2f3 !important; background: #edf5ff !important; color: #1769df !important; }

        @media (max-width: 991.98px) {
            .stock-controls { align-items: stretch; flex-direction: column; }
            .stock-search { width: 100%; }
            .stock-date-filter { width: 100%; }
            .stock-date-field, .stock-date-filter .input-group { flex: 1; width: 100%; }
        }

        @media (max-width: 767.98px) {
            .stock-directory { padding-top: 16px; }
            .stock-directory-card { border-radius: 16px; }
            .stock-directory-header { align-items: stretch; flex-direction: column; padding-right: 18px; padding-left: 18px; }
            .stock-header-actions, .stock-sale-button { width: 100%; }
            .stock-sale-button { flex: 1; }
            .stock-summary, .stock-controls { padding-right: 18px; padding-left: 18px; }
            .stock-date-filter { align-items: stretch; flex-direction: column; }
            .stock-date-submit { width: 100%; }
            .stock-table-shell { margin-right: 12px; margin-left: 12px; overflow-x: auto; }
        }
    </style>
@endpush

@section('content')
    @php
        $stockVariants = $cloths->sum(fn ($cloth) => $cloth->colors->count());
        $stockMeters = $cloths->sum(fn ($cloth) => $cloth->colors->sum('length'));
    @endphp

    <section class="main-content stock-directory">
        <div class="container-fluid px-3 px-lg-4">
            @include('inc.message')

            <div class="stock-directory-card">
                <div class="stock-directory-header">
                    <div>
                        <h1 class="stock-directory-title">اسٹاک کی فہرست</h1>
                        <p class="stock-directory-subtitle">دستیاب کپڑا، مقدار، لاگت، فروختی ریٹ اور تازہ اسٹاک کی معلومات دیکھیں۔</p>
                    </div>

                    <div class="stock-header-actions">
                        <a href="{{ route('admin.sellCloth') }}" class="stock-sale-button">
                            <i class="fas fa-cash-register" aria-hidden="true"></i>
                            <span>نئی فروخت</span>
                        </a>
                        <div class="dropdown stock-page-actions">
                            <button class="btn stock-page-actions-toggle" type="button" id="stockPageActions" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="مزید کارروائیاں">
                                <i class="fas fa-ellipsis-v" aria-hidden="true"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-left" aria-labelledby="stockPageActions" dir="rtl">
                                <a href="{{ route('admin.customers.sale') }}" class="dropdown-item">
                                    <i class="fas fa-user-plus" aria-hidden="true"></i>
                                    <span>نیا گاہک شامل کریں</span>
                                </a>
                                <a href="{{ route('admin.record') }}" class="dropdown-item">
                                    <i class="fas fa-users" aria-hidden="true"></i>
                                    <span>گاہک کی فہرست</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="stock-summary">
                    <span class="stock-summary-pill">
                        <i class="fas fa-layer-group" aria-hidden="true"></i>
                        {{ number_format($stockVariants) }} اسٹاک اقسام
                    </span>
                    <span class="stock-summary-pill is-value">
                        <i class="fas fa-ruler-horizontal" aria-hidden="true"></i>
                        {{ number_format((float) $stockMeters, 2) }} میٹر دستیاب
                    </span>
                </div>

                <div class="stock-controls">
                    <div class="stock-search">
                        <label for="stockDirectorySearch" class="sr-only">اسٹاک تلاش کریں</label>
                        <input type="search" id="stockDirectorySearch" class="form-control" placeholder="برانڈ، قسم یا رنگ تلاش کریں۔۔۔" autocomplete="off">
                        <i class="fas fa-search" aria-hidden="true"></i>
                    </div>

                    <form class="stock-date-filter" method="POST" action="{{ route('admin.sales.specific') }}" id="date">
                        @csrf
                        <div class="stock-date-field">
                            <label for="date_range">فروخت کی تاریخ</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="date_range" id="date_range" required placeholder="تاریخ کی حد منتخب کریں" autocomplete="off">
                                <div class="input-group-append">
                                    <span class="input-group-text"><i class="fas fa-calendar-alt" aria-hidden="true"></i></span>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-outline-primary stock-date-submit">
                            <i class="fas fa-chart-line ml-1" aria-hidden="true"></i>
                            فروخت چیک کریں
                        </button>
                    </form>
                </div>

                <div class="stock-table-shell">
                    <table class="table stock-list-table" id="stockDirectoryTable">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">کپڑے کی قسم</th>
                                <th scope="col">کپڑے کی کمپنی</th>
                                <th scope="col">رنگ</th>
                                <th scope="col">دستیاب مقدار</th>
                                <th scope="col">اوسط لاگت</th>
                                <th scope="col">اسٹاک مالیت</th>
                                <th scope="col">فروختی ریٹ</th>
                                <th scope="col">آخری اضافہ</th>
                                <th scope="col">تصویر</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $counter = 1;
                            @endphp
                            @foreach ($cloths as $cloth)
                                @foreach ($cloth->colors as $color)
                                    @php
                                        $latestStockAddition = $color->latestCostedStockAddition;
                                        $latestCost = (float) ($color->average_unit_cost ?: $latestStockAddition?->unit_cost ?: $cloth->price);
                                        $latestStockAdditionDate = $latestStockAddition?->occurred_at ?? $color->created_at;
                                        $image = $cloth->images->firstWhere('image_color', $color->color);
                                    @endphp
                                    <tr>
                                        <td><span class="stock-row-number">{{ $counter++ }}</span></td>
                                        <td><span class="stock-name">{{ $cloth->type->name }}</span></td>
                                        <td><span class="stock-name">{{ $cloth->brand->name }}</span></td>
                                        <td><span class="stock-color">{{ $color->color }}</span></td>
                                        <td><span class="stock-quantity">{{ number_format((float) $color->length, 2) }} میٹر</span></td>
                                        <td><span class="stock-money">Rs:{{ number_format($latestCost, 2) }}</span></td>
                                        <td><span class="stock-money is-value">Rs:{{ number_format((float) $color->length * $latestCost, 2) }}</span></td>
                                        <td><span class="stock-money">Rs:{{ number_format((float) ($cloth->sale_price ?: $cloth->price), 2) }}</span></td>
                                        <td>{{ $latestStockAdditionDate?->format('d-m-Y') ?: '—' }}</td>
                                        <td>
                                            @if ($image)
                                                <img class="stock-image" src="{{ asset('/'.$image->images) }}" alt="{{ $color->color }}">
                                            @else
                                                <span class="stock-image-placeholder" aria-label="تصویر موجود نہیں"><i class="far fa-image" aria-hidden="true"></i></span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @include('stock.modal')
    </section>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.min.js"></script>
    <script>
        jQuery(function ($) {
            var stockTableElement = $('#stockDirectoryTable');
            var stockTable = null;

            if (stockTableElement.length && $.fn.DataTable) {
                stockTable = stockTableElement.DataTable({
                    pageLength: 10,
                    lengthChange: false,
                    ordering: false,
                    autoWidth: false,
                    dom: 'rtip',
                    language: {
                        emptyTable: 'ابھی کوئی اسٹاک موجود نہیں۔',
                        zeroRecords: 'تلاش کے مطابق کوئی اسٹاک نہیں ملا۔',
                        info: 'کل _TOTAL_ ریکارڈز میں سے _START_ تا _END_ دکھائے جا رہے ہیں',
                        infoEmpty: 'کوئی اسٹاک ریکارڈ موجود نہیں۔',
                        paginate: {
                            next: '<i class="fas fa-chevron-left" aria-hidden="true"></i>',
                            previous: '<i class="fas fa-chevron-right" aria-hidden="true"></i>'
                        }
                    }
                });

                $('#stockDirectorySearch').on('input', function () {
                    stockTable.search(this.value).draw();
                });
            }

            if ($.fn.daterangepicker) {
                $('input[name="date_range"]').daterangepicker({
                    opens: 'left',
                    autoUpdateInput: false,
                    locale: { cancelLabel: 'صاف کریں' },
                    ranges: {
                        'پچھلا ہفتہ': [moment().subtract(7, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
                        'آج': [moment(), moment()],
                        'کل': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        'اس مہینے': [moment().startOf('month'), moment().endOf('month')],
                        'پچھلے مہینے': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                        'پورے سال': [moment().startOf('year'), moment().endOf('year')]
                    }
                });

                $('input[name="date_range"]').on('apply.daterangepicker', function (event, picker) {
                    $(this).val(picker.startDate.format('YYYY-MM-DD') + ' to ' + picker.endDate.format('YYYY-MM-DD'));
                }).on('cancel.daterangepicker', function () {
                    $(this).val('');
                });
            }

            $('#date').on('submit', function (event) {
                event.preventDefault();
                var form = $(this);

                $.ajax({
                    type: 'POST',
                    url: form.attr('action'),
                    data: form.serialize(),
                    dataType: 'json',
                    success: function (stocks) {
                        $('#modalContent').html(buildModalContent(stocks));
                        $('#myModal').modal('show');
                    },
                    error: function () {
                        window.alert('فروخت کا ریکارڈ حاصل نہیں ہو سکا۔ دوبارہ کوشش کریں۔');
                    }
                });
            });

            function buildModalContent(stocks) {
                var html = '<div class="table-responsive p-4"><table class="table table-bordered table-hover" id="stockSalesResultTable">';
                html += '<thead class="thead-dark"><tr><th>تاریخ</th><th>گاہک کا نام</th><th>برانڈ</th><th>کپڑے کی قسم</th><th>میٹر / گزانہ</th><th>ریٹ فی میٹر</th><th>کل قیمت</th><th>منافع</th><th>نقصان</th></tr></thead><tbody>';
                var totalPrice = 0;
                var totalProfit = 0;
                var totalLoss = 0;

                if (stocks.length) {
                    stocks.forEach(function (stock) {
                        var price = Number(stock.total_length) * Number(stock.selling_price);
                        totalPrice += price;
                        totalProfit += Number(stock.total_profit || 0);
                        totalLoss += Number(stock.total_loss || 0);
                        html += '<tr><td>' + stock.sellDate + '</td><td>' + stock.c_name + '</td><td>' + stock.brand.name + '</td><td>' + stock.type.name + '</td><td>' + stock.total_length + '</td><td>' + stock.selling_price + '</td><td>' + price.toFixed(2) + '</td><td>' + Number(stock.total_profit || 0).toFixed(2) + '</td><td>' + Number(stock.total_loss || 0).toFixed(2) + '</td></tr>';
                    });
                    html += '<tr class="table-info"><td colspan="6" class="text-center"><strong>ٹوٹل:</strong></td><td>' + Math.max(0, totalPrice).toFixed(2) + '</td><td>' + Math.max(0, totalProfit).toFixed(2) + '</td><td>' + Math.max(0, totalLoss).toFixed(2) + '</td></tr>';
                } else {
                    html += '<tr><td colspan="9" class="text-center">کوئی ریکارڈ دستیاب نہیں۔</td></tr>';
                }

                return html + '</tbody></table></div>';
            }

            $('#myModal').on('shown.bs.modal', function () {
                var resultsTable = $('#stockSalesResultTable');
                if (resultsTable.length && $.fn.DataTable && !$.fn.DataTable.isDataTable(resultsTable[0])) {
                    resultsTable.DataTable({ pageLength: 10, autoWidth: false });
                }
            });
        });
    </script>
@endpush
