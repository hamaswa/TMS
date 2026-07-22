@extends('main')
@section('content')
<section class="main-content">
    <div class="container">
        <div class="card">
        <div class="row">
            <div class="col-md-12">
                @if(Session::has('insert'))
                <div class="alert alert-success">{{Session::get('insert')}}</div>
                @endif

                @if(Session::has('update'))
                <div class="alert alert-warning">{{Session::get('update')}}</div>
                @endif

                @if(Session::has('delete'))
                <div class="alert alert-danger">{{Session::get('delete')}}</div>
                @endif

                <div class="bg-white px-3 py-4">
                    <p class="text-right"><a href="{{url('admin/Tailor/create')}}" class="btn btn-primary">درزی +</a>
                    </p>
                    <div class="table-title  mb-4 mt-2">
                        <h5 class="text-right">درزی ریکارڈ</h5>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table js-sortable-table cc-table-data-options-history"
                                    id="cc-table-data-options-history">
                                    <thead>
                                        <tr>
                                            <th scope="col" class="no-sort"></th>
                                            <th scope="col" class="no-sort">رقم ادائیگی</th>
                                            <th scope="col" class="no-sort">نام</th>
                                            <th scope="col" class="no-sort">نمبر</th>
                                            <th scope="col" class="no-sort">پاس ورڈ</th>
                                            <th scope="col" class="no-sort">ایڈوانس</th>
                                            {{-- <th scope="col" class="no-sort">موصول ہوئی رقم</th> --}}
                                            <th scope="col" class="no-sort"> موجودہ ہفتے کی آمدنی  </th>
                                            {{-- <th scope="col" class="no-sort"> آرڈر</th> --}}
                                            <th scope="col" class="no-sort">درزی کے نرخوں کی فہرست</th>
                                            <th scope="col" class="no-sort">عمل</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($Tailors as $tailor)
                                        <tr>
                                            <td></td>
                                            <td>
                                                <a type="button" class="btn btn-success mb-2" data-toggle="modal" data-target="#addRecordModal_{{$tailor->id}}">
                                                    رقم ادائیگی
                                                </a>
                                            </td>
                                            <td>{{$tailor->name}}</td>
                                            <td>{{$tailor->phone_number1}}</td>
                                            <td>{{$tailor->password}}</td>
                                            <td>{{$tailor->advance ?? 0}}</td>
                                            <td>
                                                <a href="{{url('admin/tailor-report',$tailor->id)}}"><i class="fa fa-eye" aria-hidden="true"></i></a>
                                            </td>
                                            <td>
                                                <a href="{{url('admin/tailor-rates',$tailor->id)}}"><i class="fa fa-eye" aria-hidden="true"></i></a>
                                            </td>
                                            <td>
                                                <a href="{{url('admin/tailor-orders',$tailor->id)}}"><i class="fa fa-eye" aria-hidden="true"></i></a>
                                                <a href="{{ url('admin/Tailor/'.$tailor->id.'/edit')}}"><i class="fa fa-edit" aria-hidden="true"></i></a>
                                                <a href="{{ url('admin/Tailor/delete',$tailor->id)}}" class="delete-tr"><i class="fa fa-trash-alt" aria-hidden="true"></i></a>
                                            </td>
                                        </tr>
                                        <div class="modal" tabindex="-1" id="addRecordModal_{{$tailor->id}}" tabindex="-1" role="dialog" aria-labelledby="addRecordModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Tailor Record</h5>
                                                        <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close">&times;</button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <form method="post" action="{{ route('admin.tailor.addAdvanceRecord', $tailor->id) }}">
                                                            @csrf
                                                            <input type="hidden" name="tailor_id" value="{{$tailor->id}}">
                                                            <div class="form-group">
                                                                <label for="amount">رقم ایڈوانس</label>
                                                                <input type="text" name="amount" class="form-control" required>
                                                            </div>
                                                            {{-- Add more fields as needed --}}
                                                            <button type="submit" class="btn btn-primary">ریکارڈ شامل کریں</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
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
