@extends('main')
@section('content')
    <section class="main-content">
        <div class="container">
            <div class="card col-sm-10 mx-auto">
                <div class="row">
                    <div class="col-md-12">
                        @include('inc.message')

                        <div class="bg-white px-3 py-4">
                            <p class="text-right"><a href="{{ route('admin.expense.create') }}" class="btn btn-primary"> نئے
                                    اخراجات شامل کریں۔ +</a>
                            </p>
                            <p style="font-size: 24px;">اپنے اخراجات چیک کریں۔</p>

                            <form class="form-inline reversed-flex-direction" method="POST"
                                action="{{ route('admin.expense.specific') }}" id="date">
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

                            <div class="table-title  mb-4 mt-2">
                                <h5 class="text-right"> اخراجات کی فہرست</h5>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="table-responsive">
                                        <!-- First Table -->
                                        <table class="table js-sortable-table cc-table-data-options-history"
                                            id="cc-table-data-options-history1">
                                            <thead>
                                                <tr>
                                                    <th scope="col" class="no-sort">#</th>
                                                    <th scope="col" class="no-sort"> ماہانہ کرایہ </th>
                                                    <th scope="col" class="no-sort">ماہانہ بل</th>
                                                    <th scope="col" class="no-sort">عمل</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php $serialNumber=1; @endphp
                                                @foreach ($expenses as $expense)
                                                    <tr>
                                                        <td>{{ $serialNumber++ }}</td>
                                                        <td>{{ $expense->Monthly_Rent ?? 0 }}</td>
                                                        <td>{{ $expense->Monthly_Bill ?? 0 }}</td>
                                                        <td style="display: flex;">
                                                            <a href="{{route('admin.expense.edit',['id'=>$expense->id])}}">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <form action="{{ route('admin.expense.delete', ['id' => $expense->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this expense?')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-link p-0"><i class="fas fa-trash-alt" style="font-size: 16px;"></i></button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot class="bg-gray">
                                                <tr class="table-info">
                                                    <td colspan="2" class="text-right"><strong> کل اخراجات</strong></td>
                                                    <td>{{ $totalExpenses }}</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="table-responsive">
                                        <!-- Second Table -->
                                        <table class="table js-sortable-table cc-table-data-options-history"
                                            id="cc-table-data-options-history2">
                                            <thead>
                                                <tr>
                                                    <th scope="col" class="no-sort">#</th>
                                                    <th scope="col" class="no-sort"> ملازم کا نام </th>
                                                    <th scope="col" class="no-sort">ملازم تنخواہ</th>
                                                    <th scope="col" class="no-sort">عمل</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php $serialNumber=1; @endphp
                                                @foreach ($workers as $worker)
                                                    <tr>
                                                        <td>{{ $serialNumber++ }}</td>
                                                        <td>{{ $worker->Worker_Name ?? ''}}</td>
                                                        <td>{{ $worker->Worker_salary ?? 0}}  </td>
                                                        <td style="display: flex;">
                                                            <a href="{{route('admin.worker.edit',['id'=>$worker->id])}}">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <form action="{{ route('admin.worker.delete', ['id' => $worker->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this worker?')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-link p-0"><i class="fas fa-trash-alt" style="font-size: 16px;"></i></button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot class="bg-gray">
                                                <tr class="table-info">
                                                    <td colspan="1" class="text-right"><strong> کل تنخواہ</strong></td>
                                                    <td>{{ $totalSalaries }}</td>
                                                </tr>
                                            </tfoot>
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

    @include('Expenses.modal'){{-- to show date range modal box --}}

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
                'پچھلا ہفتہ': [moment().subtract(7, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
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
                    var expensesDetail = response.expenses_detail;
                    var totalExpenses = response.total_expenses;
                    var salariesDetail = response.salaries_detail;
                    var totalSalaries = response.salaries;
                    // console.log(salariesDetail);

                    // Build HTML for the modal content
                    var modalContent = buildModalContent(expensesDetail,salariesDetail,totalExpenses,totalSalaries);
                    // Display modal with the constructed content
                    $('#modalContent').html(modalContent);

                    $('#myModal').modal('show'); //modal box is in the Expenses folder
                },
                error: function(error) {
                    console.error('Error:', error);
                }
            });
        }

        function buildModalContent(expensesDetail,salariesDetail,totalSalaries,totalExpenses) {
            var total = parseFloat(totalSalaries) + parseFloat(totalExpenses);
            // console.log(salariesDetail);
            var modalContent =
                '<div class="table-responsive p-4"><div class="d-flex"><table class="table table-bordered mr-4" style="max-height: 70vh; overflow-y: auto;">';
            modalContent += '<thead class="thead-dark"><tr>';
            modalContent += '<th>تاریخ</th>';
            modalContent += '<th>ماہانہ کرایہ</th>';
            modalContent += '<th>ماہانہ بل</th>';
            modalContent += '</tr></thead>';
            modalContent += '<tbody>';


            // Check if there is data
            if (expensesDetail.length > 0) {
                // Populate data for the second table
                $.each(expensesDetail, function(index, expenses) {
                modalContent += '<tr>';
                modalContent += '<td>' + (expenses.expense_date !== undefined ? expenses.expense_date : 0) + '</td>';
                modalContent += '<td>' + (expenses.Monthly_Rent !== null ? expenses.Monthly_Rent : 0) + '</td>';
                modalContent += '<td>' + (expenses.Monthly_Bill !== null ? expenses.Monthly_Bill : 0) + '</td>';
                modalContent += '</tr>';
            });
            } else {
                // If no data, add a row with zeros or a message
                modalContent += '<tr>';
                modalContent += '<td>کوئی ریکارڈ دستیاب نہیں۔</td>';
                modalContent += '<td>0</td>';
                modalContent += '<td>0</td>';
                modalContent += '</tr>';
            }

            modalContent += '</tbody></table>';

            // Second Table
            modalContent += '<table class="table table-bordered" style="max-height: 70vh; overflow-y: auto;">';
            modalContent += '<thead class="thead-dark"><tr>';
            modalContent += '<th>ملازم کا نام</th>';
            modalContent += '<th>ملازم تنخواہ</th>';
            modalContent += '</tr></thead>';
            modalContent += '<tbody>';

            // Check if there is data
            if (salariesDetail.length > 0) {
                // Populate data for the second table
                $.each(salariesDetail, function(index, salary) {
                    modalContent += '<tr>';
                    modalContent += '<td>' + (salary.Worker_Name !== undefined ? salary.Worker_Name : '') + '</td>';
                    modalContent += '<td>' + (salary.Worker_salary !== null ? salary.Worker_salary : 0) + '</td>';
                    modalContent += '</tr>';
                });
            } else {
                // If no data, add a row with zeros or a message
                modalContent += '<tr>';
                modalContent += '<td>کوئی ریکارڈ دستیاب نہیں۔</td>';
                modalContent += '<td>0</td>';
                modalContent += '</tr>';
            }

            modalContent += '</tbody></table></div></div>';
            modalContent += '<div style="text-align: center;font-size:20px;"><span>  مکمل خرچہ : ' + total.toLocaleString('en-US') + '</span></div>';
            return modalContent;
        }


    });
</script>
@endsection

