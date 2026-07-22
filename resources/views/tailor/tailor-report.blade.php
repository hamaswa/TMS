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
                        <h5 class="text-right">درزی کا نام: {{ $tailor->name }}</h5>
                        <h5 class="text-right">موجودہ ایڈوانس: روپے {{ number_format((float) $tailor->advance, 2) }}</h5>
                        <label for="filterType">مدت منتخب کریں:</label>
                        <select id="filterType" name="filterType">
                            <option value="weekly" {{ $filterType == 'weekly' ? 'selected' : '' }}>موجودہ ہفتہ</option>
                            <option value="monthly" {{ $filterType == 'monthly' ? 'selected' : '' }}>موجودہ مہینہ</option>
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
                                    class="btn btn-primary"><i class="fa fa-print" aria-hidden="true"></i> رپورٹ پرنٹ کریں</a>
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
                                                    <th scope="col" class="no-sort">سیریل نمبر</th>
                                                    <th scope="col" class="no-sort"> درزی کی رقم </th>
                                                    <th scope="col" class="no-sort">سوٹ کی مقدار</th>
                                                    <th scope="col" class="no-sort">سلائی</th>
                                                    <th scope="col" class="no-sort">کل سلائی کی قیمت</th>
                                                    {{-- <th scope="col" class="no-sort">رقم ادائیگی</th>
                                            <th scope="col" class="no-sort">تبصرہ</th> --}}

                                                </tr>
                                            </thead>
                                            <tbody>
    @php
        // Group the result by both date and design
        $groupedResult = $result
            ->groupBy(function ($item) {
                return date('Y-m-d', strtotime($item->created_at)) . '|' . $item->design; // Group by date and design
            })
            ->map(function ($group) {
                // Get unique suit numbers
                $suitNumbers = $group->pluck('suitNum')->unique()->toArray();

                return [
                    'suitNum' => implode(', ', $suitNumbers), // Join suit numbers with a comma if different
                    'totalPayment' => $group->first()->totalPayment,
                    'quantity' => $group->sum('quantity'),
                    'design' => $group->first()->design,
                    'created_at' => $group->first()->created_at,
                ];
            });
    @endphp

    @foreach ($groupedResult as $item)
        <tr class="f"
            data-month="{{ date('m', strtotime($item['created_at'])) }}"
            data-week="{{ Carbon\Carbon::parse($item['created_at'])->isoWeek }}">
            <td>{{ $loop->iteration }}</td>
            <td>{{ date('d-m-Y', strtotime($item['created_at'])) }}</td>
            <td>{{ Carbon\Carbon::parse($item['created_at'])->locale('ur')->translatedFormat('l') }}</td>
            {{-- Display all suit numbers if different, otherwise show only one --}}
            <td>{{ $item['suitNum'] }}</td>
            <td>روپے {{ number_format((float) $item['totalPayment'], 2) }}</td>
            <td>{{ $item['quantity'] }}</td>

            @if ($item['design'])
                <td>{{ $item['design'] }}</td>
            @else
                <td></td>
            @endif

            {{-- Calculate total amount based on totalPayment and quantity --}}
            <td>روپے {{ number_format((float) $item['totalPayment'] * (float) $item['quantity'], 2) }}</td>
        </tr>
    @endforeach
</tbody>


                                            <tfoot>
                                                <tr class="bg-own mt-2" id="totalAmount">
                                                    <td align="right" colspan="4"> موجودہ ہفتے کی کل آمدنی</td>
                                                    <td>
                                                        {{-- {{ $tailor_report->sum(function($order) {
                                                    return $order->suitQuantity;
                                                }) }} --}}
                                                    </td>
                                                    <td>
                                                        {{ $tailor_report->sum(function ($order) {
                                                            return $order->suitQuantity;
                                                        }) }}
                                                    </td>
                                                    <td colspan="2">
                                                        روپے {{ number_format((float) $tailor_report->sum(function ($order) {
                                                            return $order->tailor_price * $order->suitQuantity;
                                                        }), 2) }}
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
                                                                                        @php
    $totalAmount = 0; // Initialize total amount variable
    $showTotalRow = false; // Flag to check if a total row should be displayed

    // Group tailor records by date and comment, and sum the amounts
    $groupedRecords = $tailor_records
        ->groupBy(function ($item) {
            return $item->created_at->format('Y-m-d') . '_' . $item->comment; // Group by date and comment
        })
        ->map(function ($group) {
            return [
                'date' => $group->first()->created_at,
                'comment' => $group->first()->comment,
                'amount' => $group->sum('amount'), // Sum the amount for each group
            ];
        });
@endphp

<tbody>
    {{-- Show grouped records --}}
    @foreach ($groupedRecords as $item)
        {{-- Calculate amount based on comment --}}
        @php
            // Adjust totalAmount based on comment
            switch ($item['comment']) {
                case 'chai':
                case 'salary':
                    $totalAmount += $item['amount']; // Add chai and salary to total amount
                    $showTotalRow = true; // Set flag to true to indicate a total row should be displayed
                    break;
                case 'advance':
                    // Subtract advance only if there is no transaction available
                    if (!$transaction) {
                        $totalAmount -= $item['amount'];
                    }
                    break;
                default:
                    // Other comments, no change in total amount
                    break;
            }
        @endphp

        <tr class="f"
            data-month="{{ date('m', strtotime($item['date'])) }}"
            data-week="{{ Carbon\Carbon::parse($item['date'])->isoWeek }}">
            <td>{{ Carbon\Carbon::parse($item['date'])->locale('ur')->translatedFormat('l') }}</td>
            <td>{{ date('d-m-Y', strtotime($item['date'])) }}</td>
            <td>روپے {{ number_format((float) $item['amount'], 2) }}</td>
            <td>{{ ['advance' => 'ایڈوانس', 'salary' => 'اجرت کی ادائیگی', 'chai' => 'چائے کا خرچہ'][$item['comment']] ?? $item['comment'] }}</td>
        </tr>
    @endforeach

    {{-- Show total row if needed --}}
    @if ($showTotalRow)
        <tr>
            <td colspan="2">
                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#exampleModal">
                    ایڈوانس سے کاٹ دیں
                </button>
            </td>
            <td id="TotalAmount">{{ $totalAmount }}</td>
            <td>کل رقم</td>
        </tr>
    @endif

    {{-- Show transaction row if available --}}
    @if ($transaction)
        <tr>
            <td colspan="4">
                {{ $transaction->remainingBalance }}
                <span>ایڈوانس سے کاٹا گیا۔</span>
            </td>
        </tr>
    @endif
</tbody>


                                            <!-- Modal -->
                                            <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog"
                                                aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="exampleModalLabel">ایڈوانس منہا کریں
                                                            </h5>
                                                            <button type="button" class="close" data-dismiss="modal"
                                                                aria-label="بند کریں">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <form id="cutAdvanceForm" method="post"
                                                                action="{{ route('admin.tailor.cutAdvanceRecord', $tailor->id) }}">
                                                                @csrf
                                                                <input type="hidden" name="tailor_id"
                                                                    value="{{ $tailor->id }}">

                                                                <div class="form-group">
                                                                    <label for="total">کل رقم</label>
                                                                    <input type="text" name="total"
                                                                        class="form-control" required id="total">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="amount">رقم ایڈوانس</label>
                                                                    <input type="text" name="amount"
                                                                        class="form-control" required id="amountInput">
                                                                </div>
                                                                {{-- Add more fields as needed --}}
                                                                <button type="submit" class="btn btn-primary">ریکارڈ شامل
                                                                    کریں</button>
                                                            </form>

                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary"
                                                                data-dismiss="modal">بند کریں</button>
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
                    <h5 class="modal-title">درزی کا لین دین شامل کریں</h5>
                    <button type="button" class="btn-close" data-dismiss="modal" aria-label="بند کریں">&times;</button>
                </div>
                <div class="modal-body">
                    <form method="post" action="{{ route('admin.tailor.addRecord', $tailor->id) }}">
                        @csrf
                        <div class="form-group">
                            <label for="comment">تبصرہ</label>
                            <select name="comment" class="form-control" required>
                                <option value="advance">ایڈوانس</option>
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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var filter = document.getElementById('filterType');
            if (!filter) return;
            filter.addEventListener('change', function () {
                window.location.href = "{{ url('admin/tailor-report', $tailor->id) }}?filterType=" + encodeURIComponent(this.value);
            });
        });
    </script>
@endsection
