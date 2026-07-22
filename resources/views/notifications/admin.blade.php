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

        <h2>Admin Notifications</h2>
        <table class="notification-table" id="notifications-table">
            <thead>
                <tr align="center">
                    <th>Time</th>
                    <th>Subject</th>
                    <th>Message</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($notifications->where('data.type', 'admin') as $notification)
                    <tr class="text-center">
                        <td>{{ $notification->created_at->diffForHumans() }}</td>
                        <td>{{ $notification->data['subject'] }}</td>
                        <td>{{ $notification->data['about'] ?? '0' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <script>
        function markAsRead(notificationId) {
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
        $(document).ready(function() {
            $('#notifications-table').DataTable({
                "paging": true, // Enables pagination
                "searching": true, // Enables search box
                "pageLength": 10, // Set default page length (number of records per page)
                "lengthMenu": [5, 10, 25, 50, 100],
            });
        });
    </script>
@endsection
