@extends('main')
@section('content')
    <section class="main-content">
        <div class="container">
            <div class="card col-sm-10 mx-auto">
                <div class="row">
                    <div class="col-md-12">
                        @if (Session::has('insert'))
                            <div class="alert alert-success">{{ Session::get('insert') }}</div>
                        @endif

                        @if (Session::has('update'))
                            <div class="alert alert-warning">{{ Session::get('update') }}</div>
                        @endif

                        @if (Session::has('delete'))
                            <div class="alert alert-danger">{{ Session::get('delete') }}</div>
                        @endif

                        <div class="bg-white px-3 py-4">
                            <p class="text-right"><a href="{{ route('admin.clothtype.create') }}"
                                    class="btn btn-primary">کپڑے کی قسم شامل کریں +</a>
                            </p>
                            <div class="table-title  mb-4 mt-2">
                                <h5 class="text-right">کپڑے کی قسم کی فہرست</h5>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="table-responsive">
                                        <table class="table js-sortable-table cc-table-data-options-history"
                                            id="cc-table-data-options-history">
                                            <thead>
                                                <tr>
                                                    <th scope="col" class="no-sort">#</th>
                                                    <th scope="col" class="no-sort">نام</th>
                                                    <th scope="col" class="no-sort">عمل</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($cloth_types as $cloth_type)
                                                    <tr>
                                                        <td style="font-size: 18px;font-weight:600;">{{ $loop->iteration }}</td>
                                                        <td style="font-size: 18px;font-weight:600;">{{ $cloth_type->name }}</td>
                                                        <td class="d-flex justify-content-end">
                                                            <a href="{{ route('admin.clothtype.edit', $cloth_type->id) }}"
                                                                class=""><i class="fa fa-edit"
                                                                    aria-hidden="true"></i></a>
                                                            <form
                                                                action="{{ route('admin.clothtype.destroy', $cloth_type->id) }}"
                                                                method="POST">
                                                                @csrf
                                                                @method('delete')
                                                                <button class="delete-tr btn btn-sm" type="submit"><i
                                                                        class="fa fa-trash-alt"
                                                                        aria-hidden="true"></i></button>
                                                            </form>
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
    </section>
@endsection
