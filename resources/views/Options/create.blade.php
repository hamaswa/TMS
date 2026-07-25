@extends('main')
@section('content')
<section class="main-content">
    <div class="container">
        <div class="card">
            <div class="row">

            @include('inc/OptionType')
            <div class="col-md-9">
                @if(Session::has('update'))
                <div class="alert alert-warning">{{Session::get('update')}}</div>
                @endif

                @if(Session::has('del'))
                <div class="alert alert-danger">{{Session::get('del')}}</div>
                @endif
                <div class="bg-white px-3 py-4">
                    <div class="tab-content" id="v-pills-tabContent">
                        <div class="tab-pane fade show active" id="v-pills-sewing" role="tabpanel"
                            aria-labelledby="v-pills-sewing-tab">
                            <form id="cc-form__optionsForm" action="{{ URL('admin/Options')}}" method="POST"
                                class="cc-form__box">
                                <input type="hidden" name="OptionTypeId" value="{{$optionType->id}}">
                                @csrf
                                <div class="row">
                                    <div class="col-md-2">
                                        <button class="btn btn-blue mt-md-0 mt-3">محفوظ کریں</button>
                                    </div>
                                    <div class="col-md-10">
                                        <div class="form-row m-0">
                                            <div class="col-sm-8 ">
                                                <input type="text" class="form-control" name="Name">
                                            </div>
                                            <label class="col-sm-4 col-form-label f"><span
                                                    class="english"></span>{{$optionType->Name}}</label>
                                        </div>
                                    </div>

                                </div>
                            </form>
                        </div>

                    </div>
                    <div class="table-title  mb-4 mt-5">
                        <h5 class="text-right">تمام آپشن کی تاریخ</h5>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table js-sortable-table cc-table-data-options-history"
                                id="cc-table-data-options-history">
                                <thead>
                                    <tr>
                                        <th scope="col" class="no-sort text-left">عمل</th>
                                        <th scope="col" class="no-sort">آپشن کا نام</th>
                                        <th scope="col" class="no-sort"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($options as $option)
                                    <tr>
                                        <form action="{{ url('admin/Options',$option->id)}}" method="post"
                                            data-confirm="کیا آپ واقعی یہ آپشن حذف کرنا چاہتے ہیں؟">
                                            <td>{{$option->Name}}</td>
                                            <td class="text-left">
                                                <a href="{{ url('admin/Options/'.$option->id.'/edit')}}"
                                                    class="delete-tr"><i class="fa fa-edit" aria-hidden="true"></i></a>

                                                <!-- form for delete record -->
                                                @csrf
                                                {{ method_field('DELETE')}}
                                                <button type="submit" class="btn btn-default delete-tr"><i
                                                        class="fa fa-trash-alt" aria-hidden="true"></i></button>
                                            </td>
                                            <td></td>
                                        </form>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td>Data Not Exist</td>
                                    </tr>
                                    @endforelse
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
