@extends('main')

@section('content')
    <div class="filter-container">
        <h3>Filter Notifications</h3>
        <label>
            <input type="radio" name="notificationType" value="admin" checked> Admin Notifications
        </label>
        <label>
            <input type="radio" name="notificationType" value="user"> User Notifications
        </label>
    </div>
@endsection
