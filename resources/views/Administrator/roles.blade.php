@extends('main')
@section('content')

<div class="container mt-4">
    <h1 style="text-align: center;font-size:24px;"> صارفین رول اور اجازت کے ساتھ</h1>

    <table class="table" style="margin-top: 30px;">
        <thead>
            <tr style="text-align: center;font-size:20px;">
                <th>صارف کا نام</th>
                <th>رول</th>
                <th>اجازتیں</th>
                <th colspan="2">عمل</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
                <tr style="text-align: center;">
                    <td>{{ $user->name }}</td>
                    <td>
                        @foreach($user->roles as $role)
                            {{ $role->name }}
                            @if(!$loop->last)
                                <br> <!-- Add a line break if it's not the last role -->
                            @endif
                        @endforeach
                    </td>
                    <td>
                        @foreach($user->roles as $role)
                            @php
                                $permissions = $role->permissions->pluck('name')->implode(', ');
                            @endphp
                            {{ $permissions }}
                            <br> <!-- Always add a line break to separate permissions -->
                        @endforeach
                    </td>
                    <td>
                        <a href="{{ route('administrator.editUserRoles', ['id' => $user->id]) }}" class="btn btn-primary">رول میں ترمیم کریں۔</a>
                    </td>
                </tr>
            @endforeach
        </tbody>

    </table>
</div>

@endsection
