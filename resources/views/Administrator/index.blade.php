@extends('main')
@section('content')
    <section class="main-content">
        <div class="container">
            <h1 style="text-align: center;font-size:40px;margin-bottom:60px;">خوش آمدید ایڈمنسٹریٹر</h1>
            <div class="card col-sm-10 mx-auto">
                <div class="row">
                    <div class="col-md-12">
                        @include('inc.message')

                        <div class="bg-white px-3 py-4">
                            <p class="text-right"><a href="{{ route('administrator.create') }}" class="btn btn-primary">
                                    نئے صارفین شامل کریں۔۔ +</a>
                            </p>

                            <div class="table-title  mb-4 mt-2">
                                <h5 class="text-right"> صارفین کی فہرست</h5>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="table-responsive">
                                        <!-- First Table -->
                                        <table class="table js-sortable-table cc-table-data-options-history"
                                            id="cc-table-data-options-history1">
                                            <thead>
                                                <tr>
                                                    <th scope="col" class="no-sort">#</th>
                                                    <th scope="col" class="no-sort"> صارف کا نام </th>
                                                    <th scope="col" class="no-sort">صارف کا ای میل</th>
                                                    <th scope="col" class="no-sort" colspan="3">عمل</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php $serialNumber=1; @endphp
                                                @foreach ($users as $user)
                                                    <tr>
                                                        <td>{{$serialNumber++}}</td>
                                                        <td>{{ $user->name }}</td>
                                                        <td>{{ $user->email }}</td>
                                                        <td>
                                                            <a href="{{ route('administrator.edit', ['id' => $user->id]) }}">
                                                                <button class="btn btn-primary">تبدیل کریں</button>
                                                            </a>
                                                        </td>
                                                        <td>
                                                            <a href="{{route('administrator.delete',['id'=>$user->id])}}">
                                                                <button class="btn btn-danger">حذف کریں</button>
                                                            </a>
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('administrator.noti',['id'=>$user->id]) }}">
                                                                <button class="btn btn-primary">نوٹس بھیجیں۔</button>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
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
