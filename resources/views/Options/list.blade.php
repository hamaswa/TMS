@extends('main')
@section('content')
<section class="main-content">
    <div class="container">
        <div class="card">
            <h2 class="mb-4">آپشن</h2>

        <div class="row">
            @include('inc/OptionType')            
        </div>
        </div>
    </div>
</section>


@endsection