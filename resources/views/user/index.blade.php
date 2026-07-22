@extends('main')
@section('content')
<section class="main-content">
    <div class="container">
        <h1 style="text-align: center;font-size:40px;margin-bottom:40px;">خوش آمدید {{$user->name}}</h1>
        <div class="card col-sm-10 mx-auto">
            <div class="row">
                <div class="col-md-12">
                    @include('inc.message')

                    <div class="bg-white px-3 py-4">

                        <div class="table-title  mb-4 mt-2">
                            <h5 class="text-right">تفصیلات</h5>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="table-responsive">
                                    <!-- First Table -->
                                    <table class="table js-sortable-table cc-table-data-options-history"
                                        id="cc-table-data-options-history1">
                                        <thead>
                                            <tr>
                                                <th scope="col" class="no-sort"> صارف کا نام </th>
                                                <th scope="col" class="no-sort">صارف کا ای میل</th>
                                                <th scope="col" class="no-sort" colspan="2">عمل</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>{{$user->name}}</td>
                                                <td>{{$user->email}}</td>
                                                <td>
                                                    <a href="{{ route('admin.user.edit', ['id' => $user->id]) }}">
                                                        <button class="btn btn-primary">تبدیل کریں</button>
                                                    </a>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
