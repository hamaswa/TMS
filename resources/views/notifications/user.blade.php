@extends('main')

@section('content')

<style>
    .notification-container {
        max-width: 1050px;
        margin: 0 auto;
        padding: 20px;
        background-color: #f8f9fa;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .notification-container h2 {
        text-align: center;
        color: #343a40;
        margin-bottom: 20px;
    }

    .notification-table {
        width: 100%;
        border-collapse: collapse;
    }

    .notification-table th,
    .notification-table td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    .notification-table th {
        background-color: #007bff;
        color: white;
    }

    .notification-table tr:hover {
        background-color: #f1f1f1;
    }

    .notification-table tr.unread {
        background-color: #e9ecef;
        font-weight: bold;
    }
</style>

<div class="notification-container">
    <h2>User Notifications for Online Orders</h2>
        <table class="notification-table">
            <thead>
                <tr align="center">
                    <th class="text-center">Actions</th>
                    <th>Order Status</th>
                    <th>Time</th>
                    <th>Price</th>
                    <th>Color</th>
                    <th>Length</th>
                    <th>Brand Name</th>
                    <th>Type</th>
                    <th>User Name</th>
                    <th>Order ID</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($notifications->where('data.type', 'user') as $notification)
                    @php
                        $userId = $notification->data['user_id'] ?? null;
                        $user = $userId ? App\Models\User::find($userId) : null;
                        $clothId = $notification->data['cloth_id'] ?? null;
                        $cloth = $clothId ? App\Models\Cloth::with(['brand', 'type'])->find($clothId) : null;
                        $brandName = $cloth ? $cloth->brand->name : 'No Brand';
                        $typeName = $cloth ? $cloth->type->name : 'No Type';
                    @endphp
                    <tr class="text-center">
                        <td>
                            <button class="btn btn-danger btn-action" type="button"
                                onclick="markAsRead('{{ $notification->id }}')"
                                @if ($notification->read_at) disabled @endif>
                                Mark as Read</button>
                        </td>
                        <td class="status-cell">{{ $notification->data['status'] ?? 'No status' }}</td>
                        <td>{{ $notification->created_at->diffForHumans() }}</td>
                        <td>{{ $notification->data['price'] * $notification->data['length'] ?? 'No Price' }}</td>
                        <td>{{ $notification->data['color'] ?? 'No Color' }}</td>
                        <td>{{ $notification->data['length'] ?? '0' }}</td>
                        <td>{{ $typeName }}</td>
                        <td>{{ $brandName }}</td>
                        <td>{{ $user ? $user->name : 'No User' }}</td>
                        <td>{{ $notification->data['order_id'] ?? 'No Order ID' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
</div>

<script>
    $(document).ready(function() {
            $('.notification-table').DataTable({
                "paging": true, // Enables pagination
                "searching": true, // Enables search box
                "pageLength": 10, // Set default page length (number of records per page)
                "lengthMenu": [5, 10, 25, 50, 100],
            });
        });
    function markAsRead(notificationId) {
        // console.log(notificationId);
        $.ajax({
            url: '/admin/mark-as-read/' + notificationId,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Failed to mark as read');
                }
            }
        });
    }

    function OrderComlete(OrderId) {
        // console.log('OrderComlete function called with OrderId:', OrderId);
        $.ajax({
            url: '/admin/order-complete/' + OrderId,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                // console.log('Server response:', response);
                if (response.success) {
                    // location.reload();
                    alert('Order Completed');
                } else {
                    alert(response.error || 'Unexpected error occurred');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                alert('AJAX error: ' + error);
            }
        });
    }


</script>
@endsection
