@extends('main')
@section('content')
<section class="main-content">
    <div class="container">
         <div class="card">
        <div class="row">
            <div class="col-md-12">
                <h5 class="text-right">{{$data['tailor-name']}} : درزی کا نام </h5>

                <form method="post" action="{{url('admin/tailor-weakly-print',$data['tailor-id'])}}">
                    @csrf
                    <input name="Date" placeholder="Date" id="myflatpickr" required>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-print"></i></button>
                </form>
                <div class="bg-white px-3 py-4">
                    <div class="table-title  mb-4">
                        <h5 class="text-right">درزی ریکارڈ</h5>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table js-sortable-table cc-table-data-options-history"
                                    id="cc-table-data-options-history">
                                    <thead>
                                        <tr>
                                            <th scope="col" class="no-sort">نام</th>
                                            <th scope="col" class="no-sort">کپڑوں کی تعداد</th>
                                            <th scope="col" class="no-sort">تاریخ</th>
                                            <th scope="col" class="no-sort">واپسی تاریخ</th>
                                            <!-- <th scope="col" class="no-sort">تبدیل</th> -->
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($Tailor_records->orders as $record)
                                        <tr class="f">
                                            <td>{{$data['tailor-name']}}</td>
                                            <td>{{$record->suitQuantity}}</td>
                                            <td>{{ date('d-m-Y', strtotime($record->created_at))}}</td>
                                            <td>{{ date('d-m-Y', strtotime($record->returnDate))}}</td>
                                            <!-- <td>
                                                <select class="form-control">
                                                    <option value="Pending">سلا ئی شروع ہے </option>
                                                    <option value="Complete">مکمل</option>
                                                    <option value="Delivered">بھیج د یا</option>
                                                </select>
                                            </td> -->
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