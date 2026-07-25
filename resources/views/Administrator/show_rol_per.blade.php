@extends('main')
@section('content')
    <section class="main-content">
        <div class="container">
            <h1 style="text-align: center;font-size:40px;">تمام رول اور اجازت</h1>

            <!-- Roles Table -->
            <div class="card col-sm-10 mx-auto">
                <h2>Roles</h2>
                <div class="table-responsive">
                    <table class="table js-sortable-table cc-table-data-options-history">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col"> رول کا نام </th>
                                <th scope="col" colspan="2" style="text-align: center;">عمل</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($roles as $role)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $role->name }}</td>
                                    <td>
                                        <!-- Edit button -->
                                        <a href="{{route('administrator.role.edit',['id'=>$role->id])}}" class="btn btn-primary">Edit</a>
                                    </td>
                                    <td>
                                        <!-- Edit button -->
                                        <form action="{{ route('administrator.role.delete', ['id' => $role->id]) }}" method="POST" data-confirm="Delete this role?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Permissions Table -->
            <div class="card col-sm-10 mx-auto">
                <h2>Permissions</h2>
                <div class="table-responsive">
                    <table class="table js-sortable-table cc-table-data-options-history">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">اجازت نام</th>
                                <th scope="col" colspan="2" style="text-align: center;">عمل</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($perm as $permission)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $permission->name }}</td>
                                    <td>
                                        <!-- Edit button -->
                                        <a href="{{route('administrator.perm.edit',['id'=>$permission->id])}}"
                                            class="btn btn-primary">Edit</a>
                                    </td>
                                    <td>
                                        <!-- Edit button -->
                                        <form action="{{ route('administrator.perm.delete', ['id' => $permission->id]) }}" method="POST" data-confirm="Delete this permission?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection
