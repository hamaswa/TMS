@extends('main')

@section('content')
    @php
        $orders = $Tailor_records->orders;
        $totalSuits = $orders->sum(fn ($order) => max(1, (int) $order->suitQuantity));
        $totalEarnings = $orders->sum(fn ($order) => (float) $order->tailor_price * max(1, (int) $order->suitQuantity));
        $urduDays = ['Sat' => 'ہفتہ', 'Sun' => 'اتوار', 'Mon' => 'پیر', 'Tue' => 'منگل', 'Wed' => 'بدھ', 'Thu' => 'جمعرات', 'Fri' => 'جمعہ'];
        $statusLabels = $detailedWorkflow ? \App\Models\Order::STATUS_LABELS : [
            'assigned' => 'کارخانے میں ہے',
            'cutting' => 'کارخانے میں ہے',
            'stitching' => 'کارخانے میں ہے',
            'trial' => 'کارخانے میں ہے',
            'ready' => 'تیار ہے',
            'delivered' => 'تیار ہے',
        ];
    @endphp

    <style>
        .tailor-orders-page {
            --to-blue: #1769e0;
            --to-navy: #102a50;
            --to-muted: #68778f;
            --to-line: #e1e9f3;
            direction: rtl;
            padding: 28px 0 52px;
        }
        .tailor-orders-page .to-shell { width: min(100% - 32px, 1720px); margin-inline: auto; }
        .to-page-head { display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-bottom: 20px; }
        .to-identity { display: flex; align-items: center; gap: 14px; text-align: right; }
        .to-avatar { display: grid; place-items: center; flex: 0 0 54px; width: 54px; height: 54px; color: var(--to-blue); background: #edf5ff; border: 1px solid #d8e8ff; border-radius: 16px; font-size: 1.2rem; font-weight: 800; }
        .to-identity h1 { margin: 0 0 5px; color: var(--to-navy); font-size: clamp(1.5rem, 2vw, 1.95rem); font-weight: 800; }
        .to-identity p { margin: 0; color: var(--to-muted); font-size: .92rem; }
        .to-actions { display: flex; flex-wrap: wrap; gap: 9px; }
        .to-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 42px; padding: 9px 15px; border: 1px solid #d7e1ed; border-radius: 10px; color: #344762; background: #fff; font-weight: 700; transition: .2s ease; }
        .to-btn:hover { color: var(--to-blue); border-color: #b7d2f8; text-decoration: none; transform: translateY(-1px); }
        .to-btn.is-primary { color: #fff; border-color: var(--to-blue); background: linear-gradient(135deg, #267bf1, #0d5bd2); box-shadow: 0 8px 18px rgba(23,105,224,.18); }
        .to-btn.is-primary:hover { color: #fff; }
        .to-filter { display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; margin-bottom: 18px; padding: 18px 20px; background: #fff; border: 1px solid var(--to-line); border-radius: 14px; box-shadow: 0 6px 22px rgba(21,47,81,.045); }
        .to-filter__intro { min-width: 210px; text-align: right; }
        .to-filter__intro strong { display: block; color: var(--to-navy); font-size: 1rem; }
        .to-filter__intro span { display: block; margin-top: 4px; color: var(--to-muted); font-size: .82rem; }
        .to-filter__fields { display: flex; align-items: flex-end; justify-content: flex-start; gap: 10px; flex: 1; }
        .to-field { width: min(100%, 230px); text-align: right; }
        .to-field label { display: block; margin-bottom: 6px; color: #425572; font-size: .8rem; font-weight: 750; }
        .to-field input { width: 100%; min-height: 42px; padding: 8px 12px; border: 1px solid #d5dfec; border-radius: 9px; color: var(--to-navy); background: #fbfdff; }
        .to-filter-error { display: none; margin-top: 10px; color: #c22c3a; font-size: .82rem; }
        .to-panel { overflow: hidden; background: #fff; border: 1px solid var(--to-line); border-radius: 16px; box-shadow: 0 8px 28px rgba(21,47,81,.06); }
        .to-panel__head { display: flex; align-items: center; justify-content: space-between; gap: 20px; padding: 20px 22px; border-bottom: 1px solid var(--to-line); }
        .to-panel__title h2 { margin: 0 0 5px; color: var(--to-navy); font-size: 1.22rem; font-weight: 800; }
        .to-panel__title p { margin: 0; color: var(--to-muted); font-size: .85rem; }
        .to-panel__controls { display: flex; align-items: center; gap: 9px; }
        .to-search { position: relative; width: min(38vw, 330px); }
        .to-search i { position: absolute; top: 50%; right: 14px; color: #8796aa; transform: translateY(-50%); }
        .to-search input { width: 100%; min-height: 42px; padding: 8px 40px 8px 12px; border: 1px solid #d5dfec; border-radius: 9px; color: var(--to-navy); background: #fbfdff; outline: 0; }
        .to-search input:focus, .to-field input:focus { border-color: #79abf4; box-shadow: 0 0 0 3px rgba(23,105,224,.1); }
        .to-page-size { min-height: 42px; padding: 7px 10px; border: 1px solid #d5dfec; border-radius: 9px; color: #435674; background: #fff; }
        .to-table-wrap { overflow-x: auto; }
        .to-table { width: 100% !important; min-width: 1120px; margin: 0 !important; border-collapse: collapse !important; table-layout: fixed; }
        .to-table thead th { padding: 14px 12px !important; color: #53647e !important; background: #f4f7fb !important; border: 0 !important; border-bottom: 1px solid var(--to-line) !important; font-size: .81rem !important; font-weight: 800 !important; text-align: center !important; white-space: nowrap; }
        .to-table tbody td { padding: 15px 12px !important; color: #253955; border-top: 1px solid #e7edf5 !important; font-size: .91rem; vertical-align: middle !important; text-align: center !important; }
        .to-table tbody tr:hover { background: #fbfdff; }
        .to-date, .to-customer { text-align: center; }
        .to-date strong, .to-customer strong { display: block; color: var(--to-navy); font-weight: 800; white-space: nowrap; }
        .to-date small, .to-customer small { display: block; margin-top: 3px; color: #8492a6; font-size: .75rem; }
        .to-serial { display: inline-block; max-width: 180px; color: #465a77; overflow-wrap: anywhere; }
        .to-quantity { display: inline-flex; align-items: center; justify-content: center; min-width: 34px; height: 30px; padding: 0 9px; color: #1769e0; background: #eaf3ff; border-radius: 8px; font-weight: 800; }
        .to-money { direction: ltr; unicode-bidi: isolate; color: var(--to-navy); font-weight: 800; white-space: nowrap; }
        .to-money.is-total { color: #078653; }
        .to-status-wrap { display: flex; align-items: center; justify-content: center; gap: 6px; flex-wrap: wrap; }
        .to-status { display: inline-flex; align-items: center; gap: 6px; padding: 5px 9px; color: #53647d; background: #f2f5f9; border-radius: 999px; font-size: .75rem; font-weight: 800; white-space: nowrap; }
        .to-status::before { content: ''; width: 6px; height: 6px; background: #7890ad; border-radius: 50%; }
        .to-status.is-workshop { color: #9b6200; background: #fff3cf; }
        .to-status.is-workshop::before { background: #d18a00; }
        .to-status.is-ready { color: #fff; background: linear-gradient(135deg, #2478ec, #1159bd); }
        .to-status.is-ready::before { background: #fff; }
        .to-status.is-delivered { color: #fff; background: linear-gradient(135deg, #1daa6a, #087747); box-shadow: 0 4px 11px rgba(8,119,71,.16); }
        .to-status.is-delivered::before { background: #fff; }
        .to-empty { padding: 55px 20px !important; text-align: center !important; }
        .to-empty i { display: block; margin-bottom: 12px; color: #b3c1d2; font-size: 2rem; }
        .to-empty strong { display: block; color: var(--to-navy); }
        .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_length { display: none; }
        .dataTables_wrapper .to-table-footer { display: flex; align-items: center; justify-content: space-between; gap: 15px; padding: 14px 20px; border-top: 1px solid var(--to-line); }
        .dataTables_wrapper .dataTables_info { float: none; padding: 0; color: #718096; font-size: .82rem; }
        .dataTables_wrapper .dataTables_paginate { float: none; padding: 0; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { min-width: 34px; margin: 0 2px; padding: 6px 10px !important; border: 1px solid #dce5f0 !important; border-radius: 7px; color: #50627d !important; background: #fff !important; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { color: #fff !important; border-color: var(--to-blue) !important; background: var(--to-blue) !important; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled { opacity: .45; }
        @media (max-width: 900px) {
            .to-page-head, .to-panel__head, .to-filter { align-items: stretch; flex-direction: column; }
            .to-actions, .to-filter__fields { width: 100%; }
            .to-filter__fields { flex-wrap: wrap; }
            .to-panel__controls, .to-search { width: 100%; }
        }
        @media (max-width: 600px) {
            .tailor-orders-page .to-shell { width: min(100% - 20px, 1720px); }
            .to-actions .to-btn, .to-filter__fields .to-field, .to-filter__fields .to-btn { width: 100%; max-width: none; }
            .to-panel__controls { flex-direction: column; align-items: stretch; }
            .to-page-size { width: 100%; }
            .dataTables_wrapper .to-table-footer { align-items: flex-start; flex-direction: column; }
        }
    </style>

    <section class="main-content tailor-orders-page">
        <div class="to-shell">
            <div class="to-page-head">
                <div class="to-identity">
                    <span class="to-avatar">{{ mb_substr(trim($data['tailor-name']), 0, 1) }}</span>
                    <div><h1>{{ $data['tailor-name'] }} کے آرڈرز</h1><p>سلائی، مقدار، اجرت اور حوالگی کی مکمل آرڈر ہسٹری۔</p></div>
                </div>
                <div class="to-actions">
                    <a class="to-btn" href="{{ route('admin.Tailor.index') }}"><i class="fas fa-arrow-right"></i> درزیوں کی فہرست</a>
                    <a class="to-btn" href="{{ route('admin.tailor-rates', $data['tailor-id']) }}"><i class="fas fa-tags"></i> سلائی نرخ</a>
                    <a class="to-btn is-primary" href="{{ route('admin.tailor-report', $data['tailor-id']) }}"><i class="fas fa-file-invoice-dollar"></i> حساب اور لین دین</a>
                </div>
            </div>

            <form class="to-filter" method="POST" action="{{ route('admin.record.specific', ['id' => $data['tailor-id']]) }}" id="tailorDateFilter">
                @csrf
                <input type="hidden" name="date_range" id="tailorDateRange">
                <div class="to-filter__intro"><strong><i class="fas fa-calendar-alt text-primary ml-1"></i> تاریخ کے مطابق دیکھیں</strong><span>مخصوص مدت کے آرڈرز الگ دیکھنے کے لیے دونوں تاریخیں منتخب کریں۔</span><div class="to-filter-error" id="tailorFilterError"></div></div>
                <div class="to-filter__fields">
                    <div class="to-field"><label for="tailorFromDate">ابتدائی تاریخ</label><input type="date" id="tailorFromDate" autocomplete="off"></div>
                    <div class="to-field"><label for="tailorToDate">آخری تاریخ</label><input type="date" id="tailorToDate" autocomplete="off"></div>
                    <button type="submit" class="to-btn is-primary"><i class="fas fa-filter"></i> ریکارڈ دکھائیں</button>
                </div>
            </form>

            <div class="to-panel">
                <div class="to-panel__head">
                    <div class="to-panel__title"><h2>آرڈر ریکارڈ</h2><p>{{ number_format($orders->count()) }} آرڈرز، {{ number_format($totalSuits) }} سوٹ — مجموعی اجرت Rs. {{ number_format($totalEarnings, 2) }}</p></div>
                    <div class="to-panel__controls">
                        <label class="to-search" for="tailorOrderSearch"><i class="fas fa-search"></i><input id="tailorOrderSearch" type="search" autocomplete="off" placeholder="گاہک، سیریل یا سلائی سے تلاش کریں"></label>
                        <select class="to-page-size" id="tailorOrderPageSize" aria-label="فی صفحہ ریکارڈ"><option value="10">10 ریکارڈ</option><option value="25">25 ریکارڈ</option><option value="50">50 ریکارڈ</option><option value="100">100 ریکارڈ</option></select>
                    </div>
                </div>
                <div class="to-table-wrap">
                    <table class="table to-table" id="cc-table-data-tailor-history">
                        <colgroup><col style="width:14%"><col style="width:20%"><col style="width:15%"><col style="width:7%"><col style="width:12%"><col style="width:12%"><col style="width:13%"><col style="width:10%"></colgroup>
                        <thead><tr><th>آرڈر کی تاریخ</th><th>گاہک / سیریل نمبر</th><th>سلائی کی قسم</th><th>سوٹ</th><th>فی سوٹ اجرت</th><th>کل اجرت</th><th>واپسی کی تاریخ</th><th>حالت</th></tr></thead>
                        <tbody>
                            @foreach ($orders as $record)
                                @php
                                    $serialNumbers = json_decode($record->suitNum, true);
                                    $serialDisplay = is_array($serialNumbers) ? implode('، ', $serialNumbers) : ($record->suitNum ?: '—');
                                    $quantity = max(1, (int) $record->suitQuantity);
                                    $orderDate = \Carbon\Carbon::parse($record->created_at);
                                    $returnDate = $record->returnDate ? \Carbon\Carbon::parse($record->returnDate) : null;
                                    $statusLabel = $statusLabels[$record->status] ?? ($record->status ?: 'درج شدہ');
                                    $statusClass = in_array($record->status, ['ready', 'delivered'], true) ? 'is-ready' : 'is-workshop';
                                    if ($detailedWorkflow) {
                                        $statusClass = $record->status === 'delivered' ? 'is-delivered' : ($record->status === 'ready' ? 'is-ready' : '');
                                    }
                                @endphp
                                <tr>
                                    <td data-order="{{ $orderDate->timestamp }}"><div class="to-date"><strong>{{ $orderDate->format('d-m-Y') }}</strong><small>{{ $urduDays[$orderDate->format('D')] ?? $orderDate->format('D') }}</small></div></td>
                                    <td><div class="to-customer"><strong>{{ $record->customers?->name ?? 'گاہک درج نہیں' }}</strong><small>سیریل: <span class="to-serial">{{ $serialDisplay }}</span></small></div></td>
                                    <td>{{ $record->rate?->options?->Name ?: $record->rate?->type ?: $record->design ?: '—' }}</td>
                                    <td><span class="to-quantity">{{ $quantity }}</span></td>
                                    <td><span class="to-money">Rs. {{ number_format((float) $record->tailor_price, 2) }}</span></td>
                                    <td><span class="to-money is-total">Rs. {{ number_format((float) $record->tailor_price * $quantity, 2) }}</span></td>
                                    <td><div class="to-date">@if($returnDate)<strong>{{ $returnDate->format('d-m-Y') }}</strong><small>{{ $urduDays[$returnDate->format('D')] ?? $returnDate->format('D') }}</small>@else<span>—</span>@endif</div></td>
                                    <td><div class="to-status-wrap">
                                        <span class="to-status {{ $statusClass }}">{{ $statusLabel }}</span>
                                        @if(! $detailedWorkflow && $record->status === 'delivered')
                                            <span class="to-status is-delivered">گاہک کے حوالے ہو گیا</span>
                                        @endif
                                    </div></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    @include('tailor.modal')
@endsection

@push('scripts')
    <script>
        $(function () {
            var orderTable = $('#cc-table-data-tailor-history').DataTable({
                autoWidth: false,
                paging: true,
                searching: true,
                ordering: true,
                pageLength: 10,
                order: [[0, 'desc']],
                dom: 'rt<"to-table-footer"ip>',
                language: {
                    emptyTable: 'کوئی ریکارڈ دستیاب نہیں۔',
                    info: '_TOTAL_ میں سے _START_ تا _END_ ریکارڈ',
                    infoEmpty: 'کوئی ریکارڈ دستیاب نہیں۔',
                    zeroRecords: 'کوئی مماثل ریکارڈ نہیں ملا۔',
                    paginate: { first: 'پہلا', last: 'آخری', next: 'اگلا', previous: 'پچھلا' }
                },
                columnDefs: [{ orderable: false, targets: [1, 2, 3, 4, 5, 6, 7] }]
            });

            $('#tailorOrderSearch').on('input', function () { orderTable.search(this.value).draw(); });
            $('#tailorOrderPageSize').on('change', function () { orderTable.page.len(Number(this.value)).draw(); });

            var form = $('#tailorDateFilter');
            var errorBox = $('#tailorFilterError');
            form.on('submit', function (event) {
                event.preventDefault();
                var from = $('#tailorFromDate').val();
                var to = $('#tailorToDate').val();
                errorBox.hide().text('');
                if (!from || !to) { errorBox.text('ابتدائی اور آخری دونوں تاریخیں منتخب کریں۔').show(); return; }
                if (from > to) { errorBox.text('ابتدائی تاریخ آخری تاریخ سے پہلے ہونی چاہیے۔').show(); return; }
                $('#tailorDateRange').val(from + ' to ' + to);

                var submitButton = form.find('button[type="submit"]');
                submitButton.prop('disabled', true);
                $.ajax({
                    type: 'POST', url: form.attr('action'), data: form.serialize(), dataType: 'json',
                    success: function (response) {
                        $('#tailorFilteredPeriod').text(from.split('-').reverse().join('-') + ' تا ' + to.split('-').reverse().join('-'));
                        $('#modalContent').html(buildFilteredOrders(response.tailors || []));
                        $('#tailorOrdersModal').modal('show');
                    },
                    error: function (xhr) { errorBox.text(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'ریکارڈ حاصل نہیں ہو سکا۔').show(); },
                    complete: function () { submitButton.prop('disabled', false); }
                });
            });

            function escapeHtml(value) { return $('<div>').text(value == null ? '' : String(value)).html(); }
            function formatDate(value) { if (!value) return '—'; var date = new Date(value); return isNaN(date.getTime()) ? '—' : date.toLocaleDateString('en-GB'); }
            function buildFilteredOrders(records) {
                var rows = '';
                var totalSuits = 0;
                var totalWages = 0;
                records.forEach(function (record) {
                    var quantity = Math.max(1, parseInt(record.suitQuantity || 1, 10));
                    var rate = parseFloat(record.tailor_price || 0);
                    var total = rate * quantity;
                    totalSuits += quantity;
                    totalWages += total;
                    var serial = record.suitNum || '—';
                    try { var decoded = JSON.parse(serial); if (Array.isArray(decoded)) serial = decoded.join('، '); } catch (e) {}
                    var customer = record.customers && record.customers.name ? record.customers.name : 'گاہک درج نہیں';
                    var sewingName = record.rate && record.rate.options && record.rate.options.Name
                        ? record.rate.options.Name
                        : (record.rate && record.rate.type ? record.rate.type : (record.design || '—'));
                    rows += '<tr><td>' + formatDate(record.created_at) + '</td><td><strong>' + escapeHtml(customer) + '</strong><small class="d-block text-muted">' + escapeHtml(serial) + '</small></td><td>' + escapeHtml(sewingName) + '</td><td>' + quantity + '</td><td>Rs. ' + rate.toFixed(2) + '</td><td class="text-success font-weight-bold">Rs. ' + total.toFixed(2) + '</td><td>' + formatDate(record.returnDate) + '</td></tr>';
                });
                if (!rows) rows = '<tr><td colspan="7" class="text-center py-5 text-muted">اس مدت میں کوئی آرڈر موجود نہیں۔</td></tr>';
                return '<div class="table-responsive"><table class="table to-filtered-table mb-0"><thead><tr><th>آرڈر تاریخ</th><th>گاہک / سیریل</th><th>سلائی</th><th>سوٹ</th><th>فی سوٹ</th><th>کل اجرت</th><th>واپسی</th></tr></thead><tbody>' + rows + '</tbody><tfoot><tr><th colspan="3">مجموعی</th><th>' + totalSuits + '</th><th></th><th>Rs. ' + totalWages.toFixed(2) + '</th><th></th></tr></tfoot></table></div>';
            }
        });
    </script>
@endpush
