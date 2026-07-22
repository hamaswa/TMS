@extends('main')
@section('content')
<section class="main-content"><div class="container py-4" style="max-width:900px">
    <div class="mb-4"><h2 class="mb-1">Create client account</h2><p class="text-muted mb-0">Choose the business modules. The new account will remain pending until you approve it.</p></div>
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('administrator.insert') }}" class="card card-body p-4">@csrf
        @include('Administrator.partials.account-form', ['user' => null])
        <div class="d-flex justify-content-end mt-3"><a href="{{ route('administrator.index') }}" class="btn btn-light mr-2">Cancel</a><button class="btn btn-primary">Create client</button></div>
    </form>
</div></section>
@endsection
