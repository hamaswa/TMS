@extends('main')
@section('content')
    <section class="main-content">
        <div class="container">
            <div class="card col-sm-10 mx-auto">
                <div class="row">
                    <div class="col-md-12">
                        @include('inc.message')

                        <div class="bg-white px-3 py-4">
                            <p class="text-right"><a href="{{ route('admin.dailyexpense.create') }}" class="btn btn-primary">
                                    نئے
                                    اخراجات شامل کریں۔ +</a>
                            </p>
                            <h1 class="h4 text-right">روزانہ اخراجات چیک کریں</h1>

                            <form class="form-inline reversed-flex-direction" method="POST"
                                action="{{ route('admin.dailyexpense.specific') }}" id="date">
                                @csrf
                                <!-- Date range picker input field -->
                                <div class="form-group mr-2">
                                    <label class="sr-only" for="daily_expense_date_range">تاریخ کی حد</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="date_range" id="daily_expense_date_range"
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

                            <div class="table-title  mb-4 mt-2">
                                <h2 class="h5 text-right">روزانہ اخراجات کی فہرست</h2>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="table-responsive">
                                        <!-- First Table -->
                                        <table class="table js-sortable-table cc-table-data-options-history"
                                            id="cc-table-data-options-history1">
                                            <thead>
                                                <tr>
                                                    <th scope="col" class="no-sort"> تاریخ </th>
                                                    <th scope="col" class="no-sort"> اخراجات کا عنوان </th>
                                                    <th scope="col" class="no-sort">خرچ کی رقم</th>
                                                    <th scope="col" class="no-sort" colspan="2"
                                                        style="text-align: center;">عمل</th>
                                                </tr>
                                            </thead>
                                            @php
                                                $totalamount = 0;
                                            @endphp
                                            <tbody>
                                                @foreach ($expense as $ex)
                                                    <tr>
                                                        <td>{{ $ex->created_at->format('Y-m-d') }}</td>
                                                        <td>{{ $ex->Expense_name }}</td>
                                                        <td>{{ $ex->Expense_payment }}</td>
                                                        <td><a href="{{ route('admin.dailyexpense.edit', ['id' => $ex->id]) }}"
                                                                class="btn btn-primary btn-sm">تبدیلی</a></td>
                                                        <td><form action="{{ route('admin.dailyexpense.delete', ['id' => $ex->id]) }}" method="POST" data-confirm="کیا آپ واقعی یہ روزانہ خرچ حذف کرنا چاہتے ہیں؟">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger btn-sm">حذف کریں</button>
                                                            </form></td>
                                                    </tr>
                                                    @php
                                                        $totalamount += $ex->Expense_payment;
                                                    @endphp
                                                @endforeach
                                                <tr class="table-info">
                                                    <td colspan="5" class="text-center"><strong> کل
                                                            اخراجات</strong>
                                                             &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>{{ $totalamount }}</b> </td>
                                                </tr>
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

    @include('DailyExpenses.modal'){{-- to show date range modal box --}}

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
                        var expenses = response.expense;
                        // console.log(expenses);

                        // Build HTML for the modal content
                        var modalContent = buildModalContent(expenses);
                        // Display modal with the constructed content
                        $('#modalContent').html(modalContent);

                        $('#myModal').modal('show'); //modal box is in the Expenses folder
                    },
                    error: function(error) {
                        console.error('Error:', error);
                    }
                });
            }

            function buildModalContent(expenses) {
                // console.log(salariesDetail);
                var total = 0;
                var modalContent =
                    '<div class="table-responsive p-4"><div class="d-flex"><table class="table table-bordered mr-4" style="max-height: 70vh; overflow-y: auto;">';
                modalContent += '<thead class="thead-dark"><tr>';
                modalContent += '<th>تاریخ</th>';
                modalContent += '<th>اخراجات کا عنوان</th>';
                modalContent += '<th>خرچ کی رقم</th>';
                modalContent += '</tr></thead>';
                modalContent += '<tbody>';


                // Check if there is data
                if (expenses.length > 0) {
                    $.each(expenses, function(index, expense) {
                        modalContent += '<tr>';
                        var createdAtDate = expense.created_at !== undefined ? new Date(expense
                            .created_at) : null;
                        var formattedCreatedAt = createdAtDate ? createdAtDate.toISOString().slice(0, 10) :
                            'N/A';
                        modalContent += '<td>' + formattedCreatedAt + '</td>';
                        modalContent += '<td>' + (expense.Expense_name !== undefined ? expense
                            .Expense_name : 0) + '</td>';
                        modalContent += '<td>' + (expense.Expense_payment !== undefined ? expense
                            .Expense_payment : 0) + '</td>';
                        total += expense.Expense_payment;
                    });
                } else {
                    modalContent += '<td colspan="2">' + ' کوئی ریکارڈ دستیاب نہیں۔' + '</td>';
                }

                modalContent += '</tbody></table></div></div>';
                modalContent += '<div style="text-align: center;font-size:20px;"><span>  مکمل خرچہ : '+total
                    .toLocaleString('en-US') + '</span></div>';
                return modalContent;
            }


        });
    </script>
@endsection
