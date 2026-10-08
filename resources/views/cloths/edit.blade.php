@extends('main')

@section('content')
    @php
        $cloth = $data['cloth']->loadMissing(['colors', 'images']);
    @endphp
    @include('cloths.partials.inventory-form', ['isEdit' => true, 'cloth' => $cloth])
@endsection
