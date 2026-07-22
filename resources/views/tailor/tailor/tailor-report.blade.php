@extends('main')
@section('content')
    <style>
        .bg-own {
            background: #b9c2cc !important;
            color: black !important;
            font-weight: bold;
            font-size: small
        }
    </style>
    <section class="main-content">
        <div class="container">
            <div class="card">
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="text-right"> درزی کا نام :{{ $tailor->name }} </h5>
                        <h5 class="text-right"> درزی کا ایڈوانس :{{ $tailor->advance }} </h5>
                        <label for="filterType">Select Filter Type:</label>
                        <select id="filterType" name="filterType">
                            <option value="weekly" {{ $filterType == 'weekly' ? 'selected' : '' }}>Weekly</option>
                            <option value="monthly" {{ $filterType == 'monthly' ? 'selected' : '' }}>Monthly</option>
                        </select>


                        {{-- <form method="post" action="{{url('admin/tailor-weakly-print',$data['tailor-id'])}}"> --}}
                        {{-- @csrf
                    <input name="Date" placeholder="Date" id="myflatpickr" required>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-print"></i></button>
                </form> --}}
                        <div class="bg-white px-3 py-4">
                            <div class="table-title  mb-2">
                                <h5 class="text-right">درزی ریکارڈ</h5>
                                <a href="{{ url('admin/tailor-weekly-report-print/' . $tailor->id) }}"
                                    class="btn btn-primary"><i class="fa fa-print"></i></a>
                            </div>


                            <div class="row">
                                <div class="col-md-8">
                                    <button type="button" class="btn btn-success mb-2" style="margin-left: 87%;"
                                        data-toggle="modal" data-target="#addRecordModal">
                                        رقم ادائیگی
                                    </button>
                                    <div class="table-responsive">
                                        <table class="table js-sortable-table cc-table-data-options-history"
                                            id="cc-table-data-options-history">
                                            <thead>
                                                <tr>
                                                    <th scope="col" class="no-sort">#</th>
                                                    <th scope="col" class="no-sort">تاریخ</th>
                                                    <th scope="col" class="no-sort">دن</th>
                                                    <th scope="col" class="no-sort">سوٹ نمبر</th>
                                                    <th scope="col" class="no-sort"> درزی کی رقم </th>
                                                    <th scope="col" class="no-sort">سوٹ کی مقدار</th>
                                                    <th scope="col" class="no-sort">ڈیزائن کی قیمت</th>
                                                    <th scope="col" class="no-sort">کل سلائی کی قیمت</th>
                                                    {{-- <th scope="col" class="no-sort">رقم ادائیگی</th>
                                            <th scope="col" class="no-sort">تبصرہ</th> --}}

                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($result as $item)
                                                    <tr class="f"
                                                        data-month="{{ date('m', strtotime($item->created_at)) }}"
                                                        data-week="{{ Carbon\Carbon::parse($item->created_at)->isoWeek }}">
                                                        <td>{{ $loop->iteration }}</td>
                                                        <td>{{ date('d-m-Y', strtotime($item->created_at)) }}</td>
                                                        <td>{{ Carbon\Carbon::parse($item->created_at)->format('D') }}</td>
                                                        {{-- <td>{{ $item->suitNum[] }}</td> --}}

                                                        <td>{{ $item->suitNum }}</td>



                                                        <td>{{ $item->totalPayment }}</td>
                                                        {{-- @if (@isset($report->rate->options->Name))
                                            <td>{{$report->rate->options->Name}}</td>
                                            @endif --}}
                                                        <td>{{ $item->quantity }}</td>
                                                        @if ($item->designPrice)
                                                            <td>{{ $item->designPrice }}</td>
                                                        @else
                                                            <td></td>
                                                        @endif
                                                        <td>{{ $item->totalPayment * $item->quantity }}</td>

                                                        {{-- <td>{{ $item->total_received}}</td>
                                            <td>{{ $item->comments }}</td> --}}
                                                        {{-- <td>{{$report->tailor_price}}</td> --}}

                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr class="bg-own mt-2" id="totalAmount">
                                                    <td align="right" colspan="3">گزشتہ ہفتے کی کل آمدنی</td>
                                                    <td>
                                                        {{-- {{ $tailor_report->sum(function($order) {
                                                    return $order->suitQuantity;
                                                }) }} --}}
                                                    </td>
                                                    <td>
                                                        {{ $tailor_report->sum(function ($order) {
                                                            return $order->tailor_price;
                                                        }) }}
                                                    </td>
                                                    <td>
                                                        {{ $tailor_report->sum(function ($order) {
                                                            return $order->suitQuantity;
                                                        }) }}
                                                    </td>
                                                    <td>
                                                        {{ $tailor_report->sum(function ($order) {
                                                            return $order->designPrice;
                                                        }) }}
                                                    </td>
                                                    <td>
                                                        {{ $tailor_report->sum(function ($order) {
                                                            return $order->tailor_price * $order->suitQuantity;
                                                        }) }}
                                                    </td>

                                                </tr>
                                                {{-- <tr class="bg-dark text-white mt-2">
                                            <td align="right" colspan="3">کل رقم</td>
                                            <td>{{ $total_amount }}</td>
                                        </tr> --}}
                                            </tfoot>

                                        </table>
                                    </div>
                                </div>
                                <div class="col-md-4 mt-4" style="top:21px;">

                                    <div class="table-responsive">
                                        <table class="table js-sortable-table cc-table-data-options-history"
                                            id="cc-table-data-options-history">
                                            <thead>
                                                <tr>
                                                    {{-- <th scope="col" class="no-sort">#</th> --}}
                                                    {{-- <th scope="col" class="no-sort"> درزی کی رقم </th>
                                            <th scope="col" class="no-sort">کپڑے کی سلائی کی قسم</th> --}}
                                                    {{-- <th scope="col" class="no-sort">سوٹ کی مقدار</th> --}}
                                                    <th scope="col" class="no-sort">دن</th>
                                                    <th scope="col" class="no-sort">تاریخ</th>
                                                    <th scope="col" class="no-sort">رقم ادائیگی</th>
                                                    <th scope="col" class="no-sort">تبصرہ</th>

                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $totalAmount = 0; // Initialize total amount variable
                                                    $showTotalRow = false; // Flag to check if a total row should be displayed
                                                @endphp

                                                @foreach ($tailor_records as $item)
                                                    {{-- Calculate amount based on comment --}}
                                                    @php
                                                        // Adjust amount based on comment
                                                        switch ($item->comment) {
                                                            case 'chai':
                                                            case 'salary':
                                                                $totalAmount += $item->amount; // Add chai and salary to total amount
                                                                $showTotalRow = true; // Set flag to true to indicate a total row should be displayed
                                                                break;
                                                            case 'advance':
                                                                $totalAmount -= $item->amount; // Subtract advance from total amount
                                                                break;
                                                            default:
                                                                // Other comments, no change in total amount
                                                                break;
                                                        }
                                                    @endphp
                                                @endforeach

                                                {{-- Show individual records --}}
                                                @foreach ($tailor_records as $item)
                                                    <tr class="f"
                                                        data-month="{{ date('m', strtotime($item->created_at)) }}"
                                                        data-week="{{ Carbon\Carbon::parse($item->created_at)->isoWeek }}">
                                                        <td>{{ Carbon\Carbon::parse($item->created_at)->format('D') }}</td>
                                                        <td>{{ date('d-m-Y', strtotime($item->created_at)) }}</td>
                                                        <td>{{ $item->amount }}</td>
                                                        <td>{{ $item->comment }}</td>
                                                    </tr>
                                                @endforeach

                                                {{-- Show total row if needed --}}
                                                @if ($showTotalRow)
                                                    <tr>
                                                        <td colspan="2"></td>
                                                        {{-- <td colspan="2"><button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#exampleModal">
                                                            ایڈوانس سے کاٹ دیں
                                                        </button></td> --}}
                                                        <td id="TotalAmount">{{ $totalAmount }}</td>
                                                        <td>Total Amount</td>
                                                    </tr>
                                                @endif
                                            </tbody>

                                            <!-- Modal -->
                                            <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog"
                                                aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="exampleModalLabel">Tailor Record</h5>
                                                            <button type="button" class="close" data-dismiss="modal"
                                                                aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <form id="cutAdvanceForm" method="post" action="{{ route('admin.tailor.cutAdvanceRecord', $tailor->id) }}">
                                                                @csrf
                                                                <input type="hidden" name="tailor_id" value="{{$tailor->id}}">
                                                                <div class="form-group">
                                                                    <label for="amount">رقم ایڈوانس</label>
                                                                    <input type="text" name="amount" class="form-control" required id="amountInput">
                                                                </div>
                                                                {{-- Add more fields as needed --}}
                                                                <button type="submit" class="btn btn-primary">ریکارڈ شامل کریں</button>
                                                            </form>

                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary"
                                                                data-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

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
    <!-- Modal -->
    <div class="modal" tabindex="-1" id="addRecordModal" tabindex="-1" role="dialog"
        aria-labelledby="addRecordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tailor Record</h5>
                    <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body">
                    <form method="post" action="{{ route('admin.tailor.addRecord', $tailor->id) }}">
                        @csrf
                        <div class="form-group">
                            <label for="comment">تبصرہ</label>
                            <select name="comment" class="form-control" required>
                                <option value="advance">ایڈونس</option>
                                <option value="salary">ہفتہ وار تنخواہ</option>
                                <option value="chai">چائے کا خرچہ</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="amount">رقم</label>
                            <input type="text" name="amount" class="form-control" required>
                        </div>




                        <button type="submit" class="btn btn-primary">ریکارڈ شامل کریں</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#filterType').change(function() {
                var selectedFilter = $(this).val();
                var url = "{{ url('admin/tailor-report', $tailor->id) }}?filterType=" + selectedFilter;

                if (selectedFilter === 'weekly') {
                    var selectedWeek = $('#weekFilter').val();
                    url += "&weekFilter=" + selectedWeek;
                } else if (selectedFilter === 'monthly') {
                    var selectedMonth = $('#monthFilter').val();
                    url += "&monthFilter=" + selectedMonth;
                }


                if (window.location.href !== url) {
                    window.location.href = url;
                }
            });


            var defaultFilter = "{{ $filterType }}";
            $('#filterType').val(defaultFilter).change();
        });
    </script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Listen for form submission
        document.getElementById("cutAdvanceForm").addEventListener("submit", function(event) {
            // Get the entered amount
            var enteredAmount = parseFloat(document.getElementById("amountInput").value);

            // Get the current total amount
            var currentTotalAmount = parseFloat(document.getElementById("TotalAmount").textContent);

            // Subtract the entered amount from the total amount
            var newTotalAmount = currentTotalAmount - enteredAmount;

            // Update the total amount displayed in the td
            document.getElementById("TotalAmount").textContent = newTotalAmount.toFixed(2); // Assuming two decimal places

            // Optionally, you can close the modal after form submission
            // $('#exampleModal').modal('hide');

            // Submit the form
            this.submit();
        });
    });
</script>

@endsection
