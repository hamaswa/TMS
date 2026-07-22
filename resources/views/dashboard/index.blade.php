@include('inc/header')
<div class="container mt-5">
    <!-- Notification Button -->
    <div class="row mb-3">
        <div class="col-md-12">
            <button id="enableNotification" onclick="askPermission()" class="btn btn-primary">اطلاعات حاصل کریں۔</button>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-dark">
                    <h5 class="text-white">موجودہ ہفتہ</h5>
                </div>
                <div class="card-body">
                    موجودہ ہفتہ سوٹ<h2 class="d-inline"><span
                            class="badge badge-pill badge-success">{{ $current_week }}</span></h2>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-dark">
                    <h5 class="text-white">پچھلہ ہفتہ </h5>
                </div>
                <div class="card-body">
                    پچھلہ ہفتہ سوٹ <h2 class="d-inline"><span
                            class="badge badge-pill badge-success">{{ $pre_week }}</span></h2>
                </div>
            </div>
        </div>
    </div>
    <br>
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="text-white">موجودہ مہینہ</h5>
                </div>
                <div class="card-body">
                    موجودہ مہینہ سوٹ <h2 class="d-inline"><span
                            class="badge badge-pill badge-success">{{ $current_month }}</span></h2>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="text-white">پچھلہ مہینہ</h5>
                </div>
                <div class="card-body">
                    پچھلہ مہینہ سوٹ <h2 class="d-inline"><span
                            class="badge badge-pill badge-success">{{ $pre_month }}</span></h2>
                </div>
            </div>
        </div>
    </div>
</div>
@include('inc/footer')
<script>
    navigator.serviceWorker.register("{{ URL::asset('public/service-woker.js') }}");

    function askPermission() {
        Notification.requestPermission().then((permission) => {
            if (permission === 'granted') {
                navigator.serviceWorker.ready.then((sw) => {
                    sw.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: "BDqkizQ-A5R8gHmQ9DlCmECneXJkXrwDRsb91AiwA9OVX8oXHAcVxxiBbL7eMlwHzxgmswN6AeuKleP3hP5zleA",
                    }).then((subscription) => {
                        console.log(subscription);
                        saveSub(JSON.stringify(subscription));
                    })
                })
            } else if (permission === 'denied') {
                alert('Notifications are blocked. Please enable them in your browser settings.');
            } else {
                console.log('Notifications permission was dismissed.');
            }
        })
    }

    function saveSub(sub){
        $.ajax({
            type: "post",
            url: "{{route('admin.save-push')}}",
            data: {
                '_token' : "{{ csrf_token() }}",
                'sub' : sub,
            },
            success: function(data){
                console.log(data);
            }
        })
    }
</script>

{{-- <script>
    // Define the eventSourceUrl dynamically based on the user role
    @if (Auth::check() && Auth::user()->hasRole('shop_owner'))
        const eventSourceUrl = "{{ route('admin.notifications-stream') }}";
    @endif

    let eventSource;

    // Ensure EventSource is supported in the browser
    if (eventSourceUrl && typeof(EventSource) !== "undefined") {
        eventSource = new EventSource(eventSourceUrl);

        // Handler when a message is received from the SSE stream
        eventSource.onmessage = function(event) {
            const data = JSON.parse(event.data);
            if (data.message === 'Waiting for new notifications...') {
                console.log(data.message);  // Log to console when waiting for new notifications
            } else {
                displayNotification(data.message);  // Display new notifications
            }
        };

        // Error handling and reconnection
        eventSource.onerror = function(event) {
            console.error("SSE error:", event);
            eventSource.close();  // Close the current connection

            // Attempt to reconnect after 3 seconds
            setTimeout(() => {
                console.log("Reconnecting to SSE...");
                eventSource = new EventSource(eventSourceUrl);
            }, 3000);
        };
    } else {
        console.log("Your browser does not support Server-Sent Events.");
    }

    // Function to display notifications (customize as needed)
    function displayNotification(message) {
        // Show a toast notification, modal, or alert
        alert("New Notification: " + message);
    }
</script> --}}
