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
                    <!-- <div class="tab-content" id="v-pills-tabContent">
                        <div class="tab-pane fade show active" id="v-pills-sewing" role="tabpanel"
                            aria-labelledby="v-pills-sewing-tab">
                            <form id="cc-form__optionsForm" action="{{ url('admin/OptionType')}}" method="post"
                                class="cc-form__box">
                                @csrf
                                <input type="hidden" name="OptionTypeId" id="OptionType">
                                <div class="row">
                                    <div class="col-md-10">
                                        <div class="form-row m-0">
                                            <label class="col-sm-4 col-form-label"><span class="english">Add Sewing
                                                    Types</span> <span class="urdu"></span></label>
                                            <div class="col-sm-8">
                                                <input type="text" class="form-control" name="Name">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <button class="btn btn-blue mt-md-0 mt-3">add</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div> -->
                    <div class="table-title  mb-4 mt-1">
                        <h5 class="text-right">آپشن کا ریکارڈ</h5>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table js-sortable-table cc-table-data-options-history"
                                id="cc-table-data-options-history">
                                <thead>
                                    <tr>
                                        <th scope="col" class="no-sort text-left">عمل</th>
                                        <th scope="col" class="no-sort">آپشن کا نام</th>

                                    </tr>
                                </thead>
                                <tbody>
                                    @if($options)
                                    <tr>
                                        <form action="{{ url('admin/Options',$options->id)}}" method="post"
                                            data-confirm="کیا آپ واقعی یہ آپشن حذف کرنا چاہتے ہیں؟">
                                            <td>{{$options->Name}}</td>
                                            <td class="text-left">
                                                <a href="{{ url('admin/Options/'.$options->id.'/edit')}}">
                                                    <i class="fa fa-edit" aria-hidden="true"></i>
                                                    </a>

                                                <!-- form for delete record -->
                                                @csrf
                                                {{ method_field('DELETE')}}
                                                <button type="submit" class="btn btn-default delete-tr"><i
                                                        class="fa fa-trash-alt" aria-hidden="true"></i></button>
                                            </td>

                                        </form>
                                    </tr>
                                    @else
                                    <tr>
                                        <td>Data Not Exist</td>
                                    </tr>
                                    @endif

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
