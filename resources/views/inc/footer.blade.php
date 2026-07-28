<!-- <div class="language">    <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="navbarLanguage" role="button" data-toggle="dropdown"
                aria-haspopup="true" aria-expanded="false">
                Language
            </a>
            <div class="dropdown-menu" aria-labelledby="navbarLanguage">
                <a class="dropdown-item" href="{{url('admin/Lang-change?lang=eng')}}" id="english">English</a>
                <a class="dropdown-item" href="{{url('admin/Lang-change?lang=ur')}}" id="urdu">urdu</a>
            </div>
        </li>
    </ul>
</div> -->


<!-- JavaScript
================================================== -->
@include('components.confirmation-modal')
<script src="{{ asset('assets/js/popper.min.js')}}"></script>
<script src="{{ asset('assets/js/jquery.dataTables.min.js')}}"></script>
<script src="{{ asset('assets/owlcarousel/owl.carousel.min.js')}}"></script>
<script src="{{ asset('assets/js/main.js')}}"></script>
<script src="{{asset('assets/js/custom.js')}}"></script>
<script src="{{ asset('assets/js/form-accessibility.js') }}?v=20260728"></script>
<script src="{{asset('assets/js/confirm-modal.js')}}"></script>
@stack('scripts')
<!--<script src="{{ asset('assets/js/popper.min.js')}}"></script>-->
<!--<script src="{{ asset('assets/js/jquery.dataTables.min.js')}}"></script>-->
<!--<script src="{{ asset('assets/owlcarousel/owl.carousel.min.js')}}"></script>-->
<!--<script src="{{ asset('assets/js/main.js')}}"></script>-->
<!--<script src="{{asset('assets/js/custom.js')}}"></script>-->
<!--{{-- <script src="{{asset('public/assets/js/notifications.js')}}"></script> --}}-->
<!-- <script src="https://cdn.ckeditor.com/4.9.2/standard/ckeditor.js"></script> -->

</body>
<!--{{-- <script>-->
<!--    setTimeout(() => {-->
<!--        window.Echo.channel('NotificationChannel')-->
<!--        .listen('NotificationEvent', (e)=>{-->
<!--            console.log(e);-->
<!--        })-->
<!--    }, 200);-->
<!--    </script> --}}-->
<!--    {{-- <script>-->
        // Define the eventSourceUrl dynamically based on the user role
<!--        @if(Auth::check() && Auth::user()->hasRole('shop_owner'))-->
<!--            const eventSourceUrl = "{{ route('admin.notifications-stream') }}";-->
<!--        @endif-->

<!--        let eventSource;-->

        // Function to initialize the SSE connection
<!--        function initializeSSE() {-->
<!--            if (eventSourceUrl && typeof(EventSource) !== "undefined") {-->
<!--                eventSource = new EventSource(eventSourceUrl);-->

                // Handler when a message is received from the SSE stream
<!--                eventSource.onmessage = function(event) {-->
<!--                    const data = JSON.parse(event.data);-->
<!--                    if (data.message === 'Waiting for new notifications...') {-->
                        // console.log(data.message);  // Log to console when waiting for new notifications
<!--                    } else {-->
                        displayNotification(data.message);  // Display new notifications
<!--                    }-->
<!--                };-->

                // Error handling and reconnection
<!--                eventSource.onerror = function(event) {-->
<!--                    console.error("SSE error:", event);-->
                    eventSource.close();  // Close the current connection

                    // Attempt to reconnect after 3 seconds
<!--                    setTimeout(() => {-->
<!--                        console.log("Reconnecting to SSE...");-->
<!--                        initializeSSE();-->
<!--                    }, 3000);-->
<!--                };-->

<!--                console.log("SSE connection initialized.");-->
<!--            } else {-->
<!--                console.log("Your browser does not support Server-Sent Events.");-->
<!--            }-->
<!--        }-->

        // Function to display notifications (customize as needed)
<!--        function displayNotification(message) {-->
            // Show a toast notification, modal, or alert
<!--            alert("New Notification: " + message);-->
<!--        }-->

        // Delay SSE initialization by 5 seconds
<!--        setTimeout(() => {-->
<!--            console.log("Initializing SSE connection after 5 seconds...");-->
<!--            initializeSSE();-->
<!--        }, 5000);-->
<!--    </script> --}}-->

</html>
