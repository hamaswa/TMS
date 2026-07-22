@extends('main')
@section('content')
    <style>
        span {
            font-size: 20px;
        }
    </style>
    <section class="main-content">
        <div class="container">

            <div class="card col-sm-10 mx-auto">
                @include('inc.message')
                <h2 class="mb-4 text-right">صارفین شامل کریں۔</h2>
                <form id="cc-form__addCustomerForm" class="add-customer-form" method="post"
                    action="{{ route('administrator.update', ['id' => $user->id]) }}">
                    @csrf
                    <div class="form-group row">
                        <div class="col-md-8">
                            <div class="form-row m-0">
                                <label class="col-sm-3 col-form-label f"><span class="english">صارف کا ریکارڈ تبدیل
                                        کریں۔</span> </label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="name" required
                                        value="{{ $user->name }}">
                                    @error('email')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-md-8">
                            <div class="form-row m-0">
                                <label class="col-sm-3 col-form-label f"><span class="english">صارف کا ای میل</span>
                                </label>
                                <div class="col-sm-9">
                                    <input type="email" class="form-control" name="email" required
                                        value="{{ $user->email }}">
                                    @error('email')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-md-8">
                            <div class="form-row m-0">
                                <label class="col-sm-3 col-form-label f"><span class="english">صارف کا پاس ورڈ</span>
                                </label>
                                <div class="col-sm-9">
                                    <input type="password" class="form-control" name="password"
                                        placeholder="Enter new password (leave blank to keep current password)">
                                    @error('password')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-md-8">
                            <div class="form-row m-0">
                                <label class="col-sm-3 col-form-label f"><span class="english"> صارف کے لیے رول</span>
                                </label>
                                <div class="col-sm-9">
                                    <select name="role" id="" class="form-control" required>
                                        @foreach ($allRoles as $role)
                                            <option value="{{ $role->name }}"
                                                {{ $user->hasRole($role->name) ? 'selected' : '' }}>
                                                {{ $role->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('role')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-md-8">
                            <div class="form-row m-0">
                                <label class="col-sm-3 col-form-label f"><span class="english">صارف کی اجازت</span></label>
                                <div class="col-sm-9">
                                    <select name="permission" id="" class="form-control" required>
                                        @foreach ($allPermissions as $permission)
                                            <option value="{{ $permission->name }}"
                                                {{ $user->hasPermissionTo($permission->name) ? 'selected' : '' }}>
                                                {{ $permission->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('permission')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="workers-container"></div>


                    <div class="form-group col-md-8 mx-auto row">
                        <div class="button-group">
                            <button type="submit" class="btn btn-blue mr-3">محفوظ کریں</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
