@extends('main')
@section('content')

<div class="container mt-4">
    <h2 style="text-align: center;"> {{ $user->name }} کے لیے رول میں ترمیم کریں۔ </h2>

    <form method="post" action="{{ route('administrator.updateUserRoles', ['id' => $user->id]) }}">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="userName" style="float: right;font-size:20px;">صارف کا نام:</label>
            <input type="text" class="form-control" id="userName" value="{{ $user->name }}" readonly>
        </div>

        <div class="form-group">
            <label for="userRole" style="float: right;font-size:20px;margin-right:30px;">رول:</label><br><br>
            <ul style="float: right;font-size:20px;margin-right:30px;">
                @foreach($allRoles as $role)
                    <li>
                        <input type="checkbox" name="userRoles[]" value="{{ $role->name }}" {{ in_array($role->name, $roles->toArray()) ? 'checked' : '' }}>
                        {{ $role->name }}
                    </li>
                @endforeach
            </ul>
        </div><br><br><br><br>

        <div class="form-group">
            <label for="userPermissions" style="float: right;font-size:20px;margin-right:30px;">اجازتیں:</label><br><br>
            <ul style="float: right;font-size:20px;margin-right:30px;">
                @foreach ($allPermissions as $permission)
                    <li>
                        <input type="checkbox" name="userPermissions[]" value="{{ $permission->name }}"
                            {{ in_array($permission->name, $permissions->pluck('name')->toArray()) ? 'checked' : '' }}>
                        {{ $permission->name }}
                    </li>
                @endforeach
            </ul>
        </div><br><br><br><br>

        <button type="submit" class="btn btn-primary" style="float: right;margin-right:60px;">تبدیل کریں۔</button>
    </form>
</div>
@endsection
