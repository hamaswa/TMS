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
                                            <th scope="col" class="no-sort">نام</th>
                                            <th scope="col" class="no-sort">نمبر</th>
                                            <th scope="col" class="no-sort">پاس ورڈ</th>
                                            {{-- <th scope="col" class="no-sort">موصول ہوئی رقم</th>  --}}
                                            <th scope="col" class="no-sort"> گزشتہ ہفتے کی آمدنی  </th>
                                            <th scope="col" class="no-sort">درزی کے نرخوں کی فہرست</th>
                                            <th scope="col" class="no-sort">عمل</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($Tailors as $tailor)
                                        <tr>
                                            <td></td>
                                            <td>{{$tailor->name}}</td>
                                            <td>{{$tailor->phone_number1}}</td>
                                            <td>{{$tailor->password}}</td>
                                            {{-- <td> <a href="{{route('payment-received',$tailor->id)}}"><i class="fa fa-eye "
                                                aria-hidden="true"></i></a>
                                            </td> --}}
                                            <td> <a href="{{route('tailor-report',$tailor->id)}}"><i class="fa fa-eye "
                                                aria-hidden="true"></i></a>
                                            </td>
                                            <td> <a href="{{route('tailor-rates',$tailor->id)}}"><i class="fa fa-eye "
                                                aria-hidden="true"></i></a>
                                            </td>
                                            <td>

                                                <a href="{{route('tailor-orders',$tailor->id)}}"><i class="fa fa-eye "
                                                        aria-hidden="true"></i></a>
                                                <a href="{{ url('admin/Tailor/'.$tailor->id.'/edit')}}"
                                                    class="delete-tr"><i class="fa fa-edit" aria-hidden="true"></i></a>

                                                <a href="{{ url('admin/Tailor/delete',$tailor->id)}}"
                                                    class="delete-tr" data-confirm="کیا آپ واقعی یہ درزی حذف کرنا چاہتے ہیں؟"><i class="fa fa-trash-alt"
                                                        aria-hidden="true"></i></a>
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
