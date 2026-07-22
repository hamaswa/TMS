@include('layouts.waqar.header')
<!-- DataTables CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
<!-- DataTables JS -->
<script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
<style>
    .main {
        width: 100%;
        min-height: 100vh;
        padding: 20px;
        background-color: #f9f9f9;
    }

    .inner-main {
        width: 100%;
        height: auto;
        margin-top: 10px;
        position: relative;
        background: rgba(0, 0, 0, 0.16);
        padding: 10px 15px;
    }

    .table {
        width: 100%;
        margin-top: 20px;
        background-color: #fff;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        border-collapse: collapse;
        padding: 20px;
    }

    .table th,
    .table td {
        padding: 15px;
        text-align: center;
        border: 1px solid #ddd;
    }

    .table td {
        font-size: 20px;
    }

    .table th {
        background-color: #6c757d;
        color: #fff;
        font-weight: bold;
    }

    .table tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    .text-center {
        text-align: center;
    }

    .mt-4 {
        margin-top: 1.5rem;
    }

    .table .highlight {
        background-color: #ffeb3b;
    }

    .btn-disabled {
        pointer-events: none;
        opacity: 0.6;
        cursor: not-allowed;
    }

    /* responsive adjustments */
    @media(max-width: 576px) {
        .inner-main {
            width: 100%;
            overflow: hidden;
        }

        .table th,
        .table td {
            padding: 10px;
            text-align: center;
            border: 1px solid #ddd;
        }

        .table td,
        th {
            font-size: 14px;
        }

        .table .btn {
            font-size: 14px;
        }

        .main h2 {
            font-size: 2rem;
        }

        .main h4 {
            font-size: 0.875rem;
        }
    }
    @media(min-width: 768px) {
        .inner-main {
            width: 100%;
            overflow: hidden;
        }

        .table th,
        .table td {
            padding: 10px;
            text-align: center;
            border: 1px solid #ddd;
        }

        .table td,
        th {
            font-size: 14px;
        }

        .table .btn {
            font-size: 14px;
        }

        .main h2 {
            font-size: 2.2rem;
        }

        .main h4 {
            font-size: 1rem;
            margin-top: 10px;
        }
    }
    @media(min-width: 992px) {
        .inner-main {
            width: 100%;
            overflow: hidden;
        }

        .table th,
        .table td {
            padding: 10px;
            text-align: center;
            border: 1px solid #ddd;
        }

        .table td,
        th {
            font-size: 18px;
        }

        .table .btn {
            font-size: 18px;
        }

        .main h2 {
            font-size: 3rem;
        }

        .main h4 {
            font-size: 1.3rem;
            margin-top: 15px;
        }
    }
</style>

<div class="main">
    <h2 class="text-center mt-4">Your Order History</h2>
    @if ($orders->isEmpty())
        <p class="text-center">You have no orders yet.</p>
    @else
        <h4 class="text-center">You Can Cancel Order within 2-3 days.</h4>

        @if (session('success'))
            <div class="alert alert-success" id="success-message">
                {{ session('success') }}
            </div>
        @endif
        <div class="inner-main">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Length</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            @php
                                $orderDate = \Carbon\Carbon::parse($order->created_at);
                                $currentDate = \Carbon\Carbon::now();
                                $daysDifference = $currentDate->diffInDays($orderDate);
                                // dd($daysDifference);
                            @endphp

                            <tr>
                                <td>{{ $order->id }}</td>
                                <td>{{ $order->created_at->format('d/m/Y') }}</td>
                                <td>{{ $order->length }}</td>
                                <td>Rs{{ number_format($order->price * $order->length, 2) }}</td>
                                <td>{{ $order->status }}</td>
                                @if ($order->status == 'pending')
                                    <td>
                                        <form action="{{ route('user.order.cancel', ['slug' => $slug, 'id' => $order->id]) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-danger @if ($daysDifference > 3) btn-disabled @endif" @disabled($daysDifference > 3)>Cancel Order</button>
                                        </form>
                                    </td>
                                @else
                                    <td>
                                        <form action="{{ route('user.order.again', ['slug' => $slug, 'id' => $order->id]) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-primary">Again Order</button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
<script>
    $('.table').DataTable({
        "paging": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "order": [
            [1, 'desc']
        ]
    });
    // Wait for the document to be fully loaded
    $(document).ready(function() {
        // Delay execution by 3 seconds
        setTimeout(function() {
            $('#success-message').fadeOut('slow'); // Hide the message with fade out effect
        }, 3000); // 3000 milliseconds = 3 seconds
    });
</script>


@include('layouts.waqar.footer')
