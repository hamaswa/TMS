@extends('main')

@section('content')

<div class="container mt-4">
    <h2 class="text-center">{{ $user->name }}</h2>

    <form method="post" action="{{ route('admin.user.update', ['id' => $user->id]) }}">
        @csrf
        @method('PUT')

        <div class="row">

            <!-- Name -->
            <div class="col-md-6">
                <div class="form-group">
                    <label style="float:right;font-size:20px;">صارف کا نام:</label>
                    <input type="text" class="form-control" name="userName" value="{{ $user->name }}" required>
                </div>
            </div>

            <!-- Email -->
            <div class="col-md-6">
                <div class="form-group">
                    <label style="float:right;font-size:20px;">صارف کا ای میل:</label>
                    <input type="email" class="form-control" name="userEmail" value="{{ $user->email }}" required>
                </div>
            </div>

            <!-- Old Password -->
            <div class="col-md-6">
                <div class="form-group">
                    <label style="float:right;font-size:20px;">پرانا پاس ورڈ:</label>
                    <input type="password" class="form-control" name="oldPassword">
                </div>
            </div>

            <!-- New Password -->
            <div class="col-md-6">
                <div class="form-group">
                    <label style="float:right;font-size:20px;">نیا پاس ورڈ:</label>
                    <input type="password" class="form-control" name="newPassword">
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label style="float:right;font-size:20px;">نئے پاس ورڈ کی تصدیق:</label>
                    <input type="password" class="form-control" name="newPassword_confirmation">
                </div>
            </div>

        </div>

        <div class="text-right mt-3">
            <button type="submit" class="btn btn-primary">
                تبدیل کریں
            </button>
        </div>

    </form>
</div>

@endsection
