@extends('main')
@section('content')
<section class="main-content"><div class="container py-4"><a href="{{ route('admin.production-workers.show', $worker) }}">← {{ $worker->name }}</a><h3 class="my-3">ورکر کی معلومات تبدیل کریں</h3><div class="card card-body"><form method="POST" action="{{ route('admin.production-workers.update', $worker) }}">@csrf @method('PUT') @include('production-workers._form')</form></div></div></section>
@endsection
