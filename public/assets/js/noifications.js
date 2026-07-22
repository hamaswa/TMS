document.addEventListener("DOMContentLoaded", function () {
    // Determine the appropriate SSE URL based on user role
    let eventSourceUrl = null;

    @if(Auth::check() && Auth::user()->hasRole('shop_owner'))
        eventSourceUrl = "{{ route('admin.notifications-stream') }}";
    @endif

    if (eventSourceUrl && typeof(EventSource) !== "undefined") {
        const eventSource = new EventSource(eventSourceUrl);

        eventSource.onmessage = function(event) {
            const data = JSON.parse(event.data);
            displayNotification(data.message);
        };

        eventSource.onerror = function() {
            console.error("Error receiving notification stream.");
            eventSource.close();
        };
    } else if (!eventSourceUrl) {
        console.log("User not authenticated or does not have a valid role.");
    } else {
        console.log("Your browser does not support Server-Sent Events.");
    }

    function displayNotification(message) {
        // Display the notification (e.g., with a toast or modal)
        alert("New Notification: " + message);
    }
});
