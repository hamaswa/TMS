@extends('main')

@section('content')
    @include('cloths.partials.inventory-form', ['isEdit' => false])
@endsection
