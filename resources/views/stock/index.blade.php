@extends('main')
@section('content')
    <style>
        .dull-row {
            background-color: #555;
            /* Change the background color as desired */
            color: #ccc;
        }

        .stock-page-container {
            width: 100%;
            max-width: 1600px;
            padding-right: 24px;
            padding-left: 24px;
        }

        .stock-page-card {
            width: 100%;
        }

        .stock-toolbar {
            direction: rtl;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px 20px;
            margin-bottom: 20px;
        }

        .stock-toolbar-actions,
        .stock-date-filter {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin: 0;
        }

        .stock-toolbar .btn,
        .stock-toolbar .form-control,
        .stock-toolbar .input-group-text {
            min-height: 43px;
        }

        .stock-toolbar .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
        }

        .stock-list-table {
            min-width: 1050px;
        }

        .stock-list-table th,
        .stock-list-table td {
            vertical-align: middle;
        }

        .stock-list-table th,
        .stock-list-table td:nth-child(5),
        .stock-list-table td:nth-child(6),
        .stock-list-table td:nth-child(7),
        .stock-list-table td:nth-child(8) {
            white-space: nowrap;
        }

        @media (max-width: 767.98px) {
            .stock-page-container {
                padding-right: 10px;
                padding-left: 10px;
            }

            .stock-toolbar,
            .stock-toolbar-actions,
            .stock-date-filter {
                width: 100%;
            }

            .stock-toolbar-actions .btn,
            .stock-date-filter .form-group,
            .stock-date-filter .input-group,
            .stock-date-filter .btn {
                width: 100%;
            }
        }
    </style>
    <section class="main-content">
        <div class="container-fluid stock-page-container">
            <div class="card stock-page-card mx-auto">
                <div class="row">
                    <div class="col-md-12">

                        @include('inc.message')

                        <div class="bg-white px-3 py-4">
                            <div class="stock-toolbar">
                                <div class="stock-toolbar-actions">
                                    <a href="{{ route('admin.sellCloth') }}" class="btn btn-primary">فروخت +</a>
                                    <a href="{{ route('admin.customers.sale') }}" class="btn btn-primary">نیا گاہک شامل کریں +</a>
                                    <a href="{{ route('admin.record') }}" class="btn btn-primary">گاہک کی فہرست +</a>
                                </div>

                                <form class="stock-date-filter" method="POST"
                                    action="{{ route('admin.sales.specific') }}" id="date">
                                    @csrf
                                    <div class="form-group mb-0">
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="date_range" id="date_range" required
                                                placeholder="تاریخ کی حد منتخب کریں۔" autocomplete="off">
                                            <div class="input-group-append">
                                                <span class="input-group-text">
                                                    <i class="fa fa-calendar"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group mb-0">
                                        <button type="submit" class="btn btn-primary">فروخت چیک کریں</button>
                                    </div>
                                </form>
                            </div>


                            <div class="table-title  mb-4 mt-2">
                                <h5 class="text-right"> اسٹاک کی فہرست</h5>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="table-responsive">
                                        <table class="table js-sortable-table stock-list-table"
                                            id="cc-table-data-customer-list">
                                            <thead>
                                                <tr>
                                                    {{-- <th scope="col" class="no-sort"></th> --}}
                                                    <th scope="col" class="no-sort">#</th>
                                                    <th scope="col" class="no-sort">کپڑے کی قسم</th>
                                                    <th scope="col" class="no-sort">کپڑے کی کمپنی</th>
                                                    <th scope="col" class="no-sort">کپڑے کا رنگ</th>
                                                    <th scope="col" class="no-sort">کپڑے کی لمبائی</th>
                                                    <th scope="col" class="no-sort">نیا ریٹ</th>
                                                    <th scope="col" class="no-sort">فروختی ریٹ</th>
                                                    <th scope="col" class="no-sort">آخری اسٹاک اضافہ</th>
                                                    <th scope="col" class="no-sort">کپڑے کی تصویر</th>
                                                    {{-- <th scope="col" class="no-sort">عمل</th> --}}
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $counter = 1; // Initialize the counter outside the loops
                                                @endphp

                                                @foreach ($cloths as $cloth)
                                                    @php
                                                        $clothRowCount = $cloth->colors->count(); // Count the number of colors for the current cloth
                                                    @endphp
                                                    @foreach ($cloth->colors as $color)
                                                        @php
                                                            $latestStockAddition = $color->latestCostedStockAddition;
                                                            $latestCost = $latestStockAddition
                                                                ? (float) $latestStockAddition->unit_cost
                                                                : (float) $cloth->price;
                                                            $latestStockAdditionDate = $latestStockAddition?->occurred_at
                                                                ?? $color->created_at;
                                                        @endphp
                                                        <tr>
                                                            <td style="font-size: 18px;font-weight:600;">
                                                                {{ $counter++ }}
                                                            </td>

                                                            <td style="font-size: 18px;font-weight:600;">
                                                                {{ $cloth->type->name }}</td>

                                                            <td style="font-size: 18px;font-weight:600;">
                                                                {{ $cloth->brand->name }}</td>

                                                            <td style="font-size: 18px; font-weight: 600;">
                                                                {{ $color->color }}
                                                            </td>



                                                            <td style="font-size: 18px;font-weight:600;">
                                                                {{ $color->length }}
                                                                میٹر</td>

                                                            <td style="font-size: 18px;font-weight:600;">
                                                                Rs:{{ number_format($latestCost, 2) }}
                                                            </td>

                                                            <td style="font-size: 18px;font-weight:600;">
                                                                Rs:{{ number_format((float) ($cloth->sale_price ?: $cloth->price), 2) }}
                                                            </td>

                                                            <td style="font-size: 18px;font-weight:600;">
                                                                {{ $latestStockAdditionDate?->format('d-m-Y') ?: '—' }}
                                                            </td>

                                                            <td>
                                                                @php
                                                                    $image = $cloth->images->firstWhere(
                                                                        'image_color',
                                                                        $color->color,
                                                                    );
                                                                @endphp
                                                                @if ($image)
                                                                    <img src="{{ asset('/' . $image->images) }}"
                                                                        alt="{{ $color->color }}"
                                                                        style="width: 70px; height: 70px;">
                                                                @endif
                                                            </td>

                                                            {{-- <td>
                                                                <div class="video-container">
                                                                    @php
                                                                        $video = $cloth->videos->firstWhere(
                                                                            'video_color',
                                                                            $color->color,
                                                                        );
                                                                    @endphp
                                                                    @if ($video)
                                                                        <video width="70" height="70" controls>
                                                                            <source
                                                                                src="{{ asset('storage/' . $video->video) }}">
                                                                        </video>
                                                                    @endif

                                                                    <div class="overlay">
                                                                        <i class="fa fa-play-circle play-icon"></i>
                                                                    </div>
                                                                    <div class="overlay-content">
                                                                        <i class="fa fa-times close-icon"></i>
                                                                        <div class="video-div">
                                                                            @php
                                                                                $video = $cloth->videos->firstWhere(
                                                                                    'video_color',
                                                                                    $color->color,
                                                                                );
                                                                            @endphp
                                                                            @if ($video)
                                                                                <video width="70" height="70"
                                                                                    controls>
                                                                                    <source
                                                                                        src="{{ asset('storage/' . $video->video) }}">
                                                                                </video>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </td> --}}


                                                            {{-- <td class="d-flex justify-content-center align-items-center"
                                                                style="height: 60px;">
                                                                <a href="{{ route('admin.cloth.edit', $cloth->id) }}"
                                                                    class=""><i class="fa fa-edit"
                                                                        aria-hidden="true"></i></a>
                                                                <div>
                                                                    <button class="delete-selected btn btn-sm pb-0"
                                                                        type="button" data-id="{{ $cloth->id }}"
                                                                        data-color="{{ $color->color }}"><i
                                                                            class="fa fa-trash-alt"
                                                                            aria-hidden="true"></i></button>
                                                                </div>
                                                            </td> --}}
                                                        </tr>
                                                    @endforeach
                                                @endforeach
                                            </tbody>
                                            {{-- <tfoot class="bg-dark text-white">
                                                <tr>
                                                    <td colspan="7" class="text-right">ٹوٹل:</td>
                                                    <td>{{ $totalProfit }}</td>
                                                    <td>{{ $totalLoss }}</td>
                                                    <td></td>
                                                </tr>
                                            </tfoot> --}}
                                        </table>

                                    </div>
                                </div>


                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('stock.modal')
    </section>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.css" />
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>

    <script>
        $(document).ready(function($) {
            $('input[name="date_range"]').daterangepicker({
                opens: 'left',
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear',
                },
                ranges: {
                    'پچھلا ہفتہ': [moment().subtract(7, 'days').startOf('day'), moment().subtract(1, 'days')
                        .endOf('day')
                    ],
                    'آج': [moment(), moment()],
                    'کل': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'اس مہینے': [moment().startOf('month'), moment().endOf('month')],
                    'پچھلے مہینے': [
                        moment().subtract(1, 'month').startOf('month'),
                        moment().subtract(1, 'month').endOf('month')
                    ],
                    'پورے سال': [moment().startOf('year'), moment().endOf('year')]
                }
            });

            $('input[name="date_range"]').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + " to " + picker.endDate.format(
                    'YYYY-MM-DD'));
            });

            $('input[name="date_range"]').on('cancel.daterangepicker', function() {
                $(this).val('');
            });


            //for date range
            $("#date").submit(function(e) {
                e.preventDefault();
                var formData = $(this).serialize();
                // console.log(formData);
                submitform(formData);
            });

            function submitform(formData) {
                $.ajax({
                    type: 'POST',
                    url: $("#date").attr('action'),
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        console.log(response);
                        var stocks = response;

                        var modalContent = buildModalContent(stocks);

                        $('#modalContent').html(modalContent);

                        // Show modal
                        $('#myModal').modal('show');
                    },
                    error: function(error) {
                        console.error('Error:', error);
                    }
                });
            }


            function buildModalContent(stocks) {
                var modalContent =
                    '<div class="table-responsive p-4"><div class="d-flex"><table class="table table-bordered table-hover mr-4" id="mytable" style="max-height: 70vh; overflow-y: auto;">';
                modalContent += '<thead class="thead-dark"><tr>';
                modalContent += '<th scope="col">تاریخ</th>';
                modalContent += '<th scope="col">گاہک کا نام</th>';
                modalContent += '<th scope="col">برانڈ</th>';
                modalContent += '<th scope="col">کپڑے کی قسم</th>';
                modalContent += '<th scope="col">میٹر / گزانہ</th>';
                modalContent += '<th scope="col">ریٹ فی میٹر</th>';
                modalContent += '<th scope="col">کپڑے کی کل قیمت</th>';
                modalContent += '<th scope="col">منا فع</th>';
                modalContent += '<th scope="col">نقصان</th>';
                modalContent += '</tr></thead>';
                modalContent += '<tbody>';

                // Initialize total variables
                var totalPrice = 0,
                    totalProfit = 0,
                    totalLoss = 0;

                if (stocks.length > 0) {
                    stocks.forEach(function(stock) {
                        modalContent += '<tr>';
                        modalContent += '<td>' + stock.sellDate + '</td>';
                        modalContent += '<td>' + stock.c_name + '</td>';
                        modalContent += '<td>' + stock.brand.name + '</td>';
                        modalContent += '<td>' + stock.type.name + '</td>';
                        modalContent += '<td>' + stock.total_length + '</td>';
                        modalContent += '<td>' + stock.selling_price + '</td>';
                        modalContent += '<td>' + stock.total_length * stock.selling_price + '</td>';
                        modalContent += '<td>' + stock.total_profit + '</td>';
                        modalContent += '<td>' + stock.total_loss + '</td>';
                        modalContent += '</tr>';

                        //caculate sum
                        totalPrice += parseFloat(stock.total_length * stock.selling_price);
                        totalProfit += parseFloat(stock.total_profit);
                        totalLoss += parseFloat(stock.total_loss);
                    });

                    // Add a row for totals
                    modalContent += '<tr class="table-info">';
                    modalContent += '<td colspan="6" class="text-center"><strong>ٹوٹل:</strong></td>';
                    modalContent += '<td>' + Math.max(0,totalPrice.toFixed(2)) + '</td>';
                    modalContent += '<td>' + Math.max(0,totalProfit.toFixed(2)) + '</td>';
                    modalContent += '<td>' + Math.max(0,totalLoss.toFixed(2)) + '</td>';
                    modalContent += '</tr>';

                    modalContent += '</tbody></table></div></div>';
                } else {
                    modalContent += '<tr>';
                    modalContent += '<td colspan="9" class="text-center">کوئی ریکارڈ دستیاب نہیں۔</td>';
                    modalContent += '</tr></tbody></table></div></div>';
                }
                return modalContent;
            }


            // Check if DataTables has already been initialized before initializing it
            function initDataTable() {
                if (!$.fn.DataTable.isDataTable('#mytable')) {
                    $('#mytable').DataTable({
                        "autoWidth": true,
                        "paging": true,
                        "searching": true,
                        "pageLength": 10,
                        "width": "100%",
                        "rowCallback": function(row, data, index) {
                            $('td', row).css('padding', '8px 35px');
                        }
                    });
                }
            }

            // Initialize DataTables when the modal is shown
            $('#myModal').on('shown.bs.modal', function() {
                initDataTable();
            });
        });
    </script>
@endsection
