@extends('main')

@section('content')
    <div class="filter-container main-content px-3 px-md-4 py-4">
        <h3>اطلاعات منتخب کریں</h3>
        <label>
            <input type="radio" name="notificationType" value="admin" checked> کاروباری اطلاعات
        </label>
        <label>
            <input type="radio" name="notificationType" value="user"> گاہکوں کی اطلاعات
        </label>
    </div>
@endsection
