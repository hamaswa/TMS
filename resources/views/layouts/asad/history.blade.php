@include('layouts.asad.header')
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
    .inner-main{
        width: 80%;
        height: auto;
        margin-top: 10px;
        position: relative;
        left: 10%;
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

    .table th, .table td {
        padding: 15px;
        text-align: center;
        border: 1px solid #ddd;
    }
    .table td{
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
</style>

<div class="main">
    <h2 class="text-center mt-4">Your Order History</h2>
    @if($orders->isEmpty())
        <p class="text-center">You have no orders yet.</p>
    @else
    <h4 class="text-center mt-4">You Can Cancel Order within 2-3 days.</h4>
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
                        @foreach($orders as $order)
                        <tr>
                            <td>{{ $order->id }}</td>
                            <td>{{ $order->created_at->format('d/m/Y') }}</td>
                            <td>{{ $order->length }}</td>
                            <td>Rs{{ number_format($order->price * $order->length, 2) }}</td>
                            <td>{{ $order->status }}</td>
                            <td>
                                <a href="#" class="btn btn-danger">Cancel Order</a>
                            </td>
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
            "order": [[1, 'desc']]
        });
</script>


@include('layouts.asad.footer')
