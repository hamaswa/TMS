@extends('main')
@section('content')
    <section class="main-content">
        <div class="container">
            <div class="card">
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="text-right">{{ $data['tailor-name'] }} : درزی کا نام </h5>

                        <form class="form-inline reversed-flex-direction" method="POST"
                            action="{{ route('admin.record.specific', ['id' => $data['tailor-id']]) }}" id="date">
                            @csrf
                            <!-- Date range picker input field -->
                            <div class="form-group mr-2">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="date_range" id="date_range"
                                        placeholder="تاریخ کی حد منتخب کریں۔" autocomplete="off">
                                    <div class="input-group-append">
                                        <span class="input-group-text">
                                            <i class="fa fa-calendar"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group mr-2">
                                <button type="submit" class="btn btn-primary"> چیک کریں۔</button>
                            </div>
                        </form>
                        <div class="bg-white px-3 py-4">
                            <div class="table-title  mb-4">
                                <h5 class="text-right">درزی ریکارڈ</h5>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="table-responsive">
                                        <table class="table js-sortable-table" id="cc-table-data-tailor-history"
                                            style="font-size:24px;">
                                            <thead>
                                                <tr>
                                                    <th scope="col" class="no-sort">درزی کا نام </th>
                                                    <th scope="col" class="no-sort">تاریخ</th>
                                                    <th scope="col" class="no-sort">دن</th>
                                                    <th scope="col" class="no-sort">سیریل نمبر</th>
                                                    <th scope="col" class="no-sort">کپڑوں کی تعداد </th>
                                                    <th scope="col" class="no-sort">سلائی</th>
                                                    <th scope="col" class="no-sort"> درزی کی رقم </th>
                                                    <th scope="col" class="no-sort">آرڈر کی تاریخ</th>
                                                    <th scope="col" class="no-sort">واپسی تاریخ</th>
                                                    <!-- <th scope="col" class="no-sort">تبدیل</th> -->
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($Tailor_records->orders as $record)
                                                    <tr class="f">
                                                        <td>{{ $data['tailor-name'] }}</td>
                                                        <td>{{ date('d-m-Y', strtotime($record->created_at)) }}</td>
                                                        <!-- Date only -->
                                                        <td>{{ date('D', strtotime($record->created_at)) }}</td>
                                                        <!-- Day only -->
                                                        <td>{{ $record->suitNum }}</td>
                                                        <td>{{ $record->suitQuantity }}</td>
                                                        <td>{{ $record->design }}</td>
                                                        <td>{{ $record->tailor_price }}</td>
                                                        <td>{{ date('M d, Y', strtotime($record->created_at)) }}</td>
                                                        <!-- Date (Month day, Year) -->
                                                        <td>{{ date('M d, Y', strtotime($record->returnDate)) }}</td>
                                                        <!-- Return Date -->
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @include('tailor.modal')
    <!-- Include jQuery (required) and daterangepicker JS files -->
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.css" />
    <script>
        $(document).ready(function($) {
            $('input[name="date_range"]').daterangepicker({
                opens: 'left',
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear',
                },
                // ranges: {
                //     'پچھلا ہفتہ': [moment().subtract(7, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
                //     'آج': [moment(), moment()],
                //     'کل': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                //     'اس مہینے': [moment().startOf('month'), moment().endOf('month')],
                //     'پچھلے مہینے': [
                //         moment().subtract(1, 'month').startOf('month'),
                //         moment().subtract(1, 'month').endOf('month')
                //     ],
                //     'پورے سال': [moment().startOf('year'), moment().endOf('year')]
                // }
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
                        var Tailors = response.tailors;
                        // console.log(Tailors);

                        // Build HTML for the modal content
                        var modalContent = buildModalContent(Tailors);
                        // Display modal with the constructed content
                        $('#modalContent').html(modalContent);

                        $('#myModal').modal('show'); //modal box is in the tailor folder
                    },
                    error: function(error) {
                        console.error('Error:', error);
                    }
                });
            }

            function buildModalContent(Tailors) {
                // console.log(Tailors);
                var modalContent =
                    '<div class="table-responsive p-4"><table class="table table-bordered" style="max-height: 70vh; overflow-y: auto;">';
                modalContent += '<thead class="thead-dark"><tr>';
                modalContent += '<th>دن</th>';
                modalContent += '<th>کپڑوں کی تعداد</th>';
                modalContent += '<th>سلائی</th>';
                modalContent += '<th>درزی کی رقم</th>';
                modalContent += '<th>تاریخ</th>';
                modalContent += '<th>واپسی کی تاریخ</th>';
                modalContent += '</tr></thead>';
                modalContent += '<tbody>';

                // Initialize totals
                let totalSuitQuantity = 0;
                let totalTailorPrice = 0;

                if (Tailors.length > 0) {
                    $.each(Tailors, function(index, tailor) {
                        // Update totals
                        totalSuitQuantity += parseFloat(tailor.suitQuantity || 0);
                        totalTailorPrice += parseFloat(tailor.tailor_price || 0);

                        modalContent += '<tr>';
                        modalContent += '<td>' + (tailor.created_at ? new Date(tailor.created_at)
                            .toLocaleDateString('en-GB', {
                                weekday: 'short'
                            }) : '') + '</td>';
                        modalContent += '<td>' + (tailor.suitQuantity || 0) + '</td>';
                        modalContent += '<td>' + (tailor.design || 0) + '</td>';
                        modalContent += '<td>Rs: ' + (tailor.tailor_price || 0) + '</td>';
                        modalContent += '<td>' + (tailor.created_at ? new Date(tailor.created_at)
                            .toLocaleDateString('en-GB') : '') + '</td>';
                        modalContent += '<td>' + (tailor.returnDate ? new Date(tailor.returnDate)
                            .toLocaleDateString('en-GB') : '') + '</td>';
                        modalContent += '</tr>';
                    });
                } else {
                    modalContent += '<tr><td colspan="4">کوئی ریکارڈ دستیاب نہیں۔</td></tr>';
                }

                modalContent += '</tbody>';

                // Add totals in tfoot
                modalContent += '<tfoot style="background-color:#45aaf2;color:#fff;">';
                modalContent += '<tr>';
                modalContent += '<td>مجموعی</td>'; // Total suit quantity
                modalContent += '<td>' + totalSuitQuantity + '</td>'; // Merge cells
                modalContent += '<td></td>'; // Total tailor price
                modalContent += '<td>Rs:' + totalTailorPrice.toFixed(2) + '</td>'; // Total tailor price
                modalContent += '<td colspan="3"></td>'; // Empty cells
                modalContent += '</tr>';
                modalContent += '</tfoot>';

                modalContent += '</table></div>';
                return modalContent;
            }

            $('#cc-table-data-tailor-history').DataTable({
                "autoWidth": false,
                "paging": true, // Enables pagination
                "searching": true, // Enables search box
                "pageLength": 10, // Set default page length (number of records per page)
                "lengthMenu": [5, 10, 25, 50, 100],
                "columnDefs": [{
                        "orderable": false,
                        "targets": "no-sort"
                    } // Disable ordering for specified columns
                ]
            });
        });
    </script>
@endsection
