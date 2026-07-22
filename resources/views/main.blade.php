@include('inc/header')
@if(session('error'))
    <div class="main-content px-3 px-md-4 pt-3" role="alert" aria-live="polite">
        <div class="alert alert-danger mb-0">{{ session('error') }}</div>
    </div>
@endif
@yield('content')
@include('inc/footer')
