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
                @if($errors->any())
                <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif

                <div class="bg-white px-3 py-4">
                    <p class="text-right"><a href="{{url('admin/Tailor/create')}}" class="btn btn-primary">نیا درزی شامل کریں</a>
                    </p>
                    <div class="table-title  mb-4 mt-2">
                        <h1 class="h4 text-right">درزی ریکارڈ</h1>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table js-sortable-table cc-table-data-options-history"
                                    id="cc-table-data-options-history">
                                    <thead>
                                        <tr>
                                            <th scope="col" class="no-sort">نام</th>
                                            <th scope="col" class="no-sort">نمبر</th>
                                            <th scope="col" class="no-sort">ایڈوانس</th>
                                            <th scope="col" class="no-sort">حساب اور لین دین</th>
                                            <th scope="col" class="no-sort">نرخ</th>
                                            <th scope="col" class="no-sort">آرڈرز اور عمل</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($Tailors as $tailor)
                                        <tr>
                                            <td>{{$tailor->name}}</td>
                                            <td>{{$tailor->phone_number1}}</td>
                                            <td>روپے {{ number_format((float) ($tailor->advance ?? 0), 2) }}</td>
                                            <td>
                                                <a type="button" class="btn btn-success btn-sm mb-1" data-toggle="modal" data-target="#addRecordModal_{{$tailor->id}}">ایڈوانس دیں</a>
                                                <a class="btn btn-primary btn-sm mb-1" href="{{url('admin/tailor-report',$tailor->id)}}">حساب دیکھیں</a>
                                            </td>
                                            <td>
                                                <a class="btn btn-info btn-sm" href="{{url('admin/tailor-rates',$tailor->id)}}">نرخ دیکھیں</a>
                                            </td>
                                            <td>
                                                <a class="btn btn-secondary btn-sm mb-1" href="{{url('admin/tailor-orders',$tailor->id)}}">آرڈرز</a>
                                                <a class="btn btn-warning btn-sm mb-1 text-dark" href="{{ url('admin/Tailor/'.$tailor->id.'/edit')}}">ترمیم</a>
                                                <form action="{{ route('admin.Tailor.destroy', $tailor->id) }}" method="POST" class="d-inline" data-confirm="کیا آپ واقعی یہ درزی حذف کرنا چاہتے ہیں؟">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm mb-1" aria-label="درزی حذف کریں">حذف کریں</button>
                                                </form>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @foreach($Tailors as $tailor)
                                <div class="modal fade" id="addRecordModal_{{$tailor->id}}" tabindex="-1" role="dialog" aria-labelledby="addRecordModalLabel_{{$tailor->id}}" aria-hidden="true">
                                    <div class="modal-dialog" role="document"><div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="addRecordModalLabel_{{$tailor->id}}">{{ $tailor->name }} کو ایڈوانس دیں</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="بند کریں">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <form method="post" action="{{ route('admin.tailor.addAdvanceRecord', $tailor->id) }}">
                                                @csrf
                                                <div class="form-group">
                                                    <label for="advance_amount_{{$tailor->id}}">ایڈوانس رقم</label>
                                                    <input id="advance_amount_{{$tailor->id}}" type="number" min="0.01" step="0.01" name="amount" class="form-control" required>
                                                    <small class="form-text text-muted">یہ رقم موجودہ ایڈوانس میں جمع ہو جائے گی اور لین دین میں محفوظ ہوگی۔</small>
                                                </div>
                                                <button type="submit" class="btn btn-primary">ایڈوانس محفوظ کریں</button>
                                            </form>
                                        </div>
                                    </div></div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
</section>

@endsection
