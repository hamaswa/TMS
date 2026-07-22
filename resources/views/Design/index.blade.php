@extends('main')
@section('content')
    <section class="main-content">
        <div class="container">
            <div class="card col-sm-10 mx-auto">
                <div class="row">
                    <div class="col-md-12">
                        @include('inc.message')

                        <div class="bg-white px-3 py-4">
                            <p class="text-right"><a href="{{ route('admin.design.create') }}" class="btn btn-primary">
                                    نئے
                                    ڈیزائن شامل کریں۔ +</a>
                            </p>

                            <div class="table-title  mb-4 mt-2">
                                <h5 class="text-right"> ڈیزائن کی فہرست</h5>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="table-responsive">
                                        <!-- First Table -->
                                        <table class="table js-sortable-table cc-table-data-options-history"
                                            id="cc-table-data-options-history1">
                                            <thead>
                                                <tr>
                                                    <th scope="col" class="no-sort"> ڈیزائن کا عنوان </th>
                                                    <th scope="col" class="no-sort">ڈیزائن کی رقم</th>
                                                    <th scope="col" class="no-sort" colspan="2"
                                                        style="text-align: center;">عمل</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($designs as $design)
                                                <tr>
                                                    <td>{{$design->design_name}}</td>
                                                    <td>{{$design->design_price}}</td>
                                                    <td><a href="{{ route('admin.design.edit', ['id' => $design->id]) }}"
                                                        class="btn btn-primary btn-sm">تبدیلی</a></td>
                                                <td><a href="{{ route('admin.design.delete', ['id' => $design->id]) }}"
                                                        class="btn btn-danger btn-sm">حذف کریں</a></td>
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
