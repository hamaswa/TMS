@extends('main')
@section('content')
<section class="main-content">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                @if(Session::has('delete'))
                <div class="alert alert-danger">{{Session::get('delete')}}</div>
                @endif
                @if(Session::has('success'))
                <div class="alert alert-success">{{Session::get('success')}}</div>
                @endif

                <div class="bg-white px-3 py-4">
                    <div class="tab-content" id="v-pills-tabContent">
                        <div class="tab-pane fade show active" id="v-pills-sewing" role="tabpanel"
                            aria-labelledby="v-pills-sewing-tab">
                            <form id="cc-form__optionsForm" action="{{ url('admin/OptionType')}}" method="post"
                                class="cc-form__box">
                                @csrf

                                <div class="row text-right">
                                    <div class=" col-md-12">
                                        <a href="{{url('admin/setting/add')}}" class="btn btn-blue mt-md-0 mt-3">ترتیب +
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="table-title  mb-4 mt-4">
                            <h5 class="text-right">تمام ترتیب کی تاریخ</h5>
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
                                                <th scope="col" class="no-sort">تصویر</th>
                                                <th scope="col" class="no-sort">نمبر</th>
                                                <th scope="col" class="no-sort">نوٹ</th>
                                                <th scope="col" class="no-sort">پتہ</th>
                                                <th scope="col" class="no-sort">نوٹ</th>
                                                <th scope="col" class="no-sort">تبدیل</th>
                                                <th scope="col" class="no-sort"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($settings as $setting)
                                            <tr>
                                                <td></td>
                                                <td>{{$setting->name}}</td>
                                                <td><img src="{{asset('public/images/setting/'.$setting->logo)}}" alt=""
                                                        style="width:150px; hight:100px">
                                                </td>
                                                <td>{{$setting->contact_no}}</td>
                                                <td>{{$setting->note}}</td>
                                                <td>{{ $setting->address }}</td>

                                                <td class="text-right">
                                                    @if($setting->status ==0)
                                                    <form action="{{ route('admin.active-setting', $setting->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn-link p-0 delete-tr">Active</button>
                                                    </form>
                                                    @else
                                                    <form action="{{ route('admin.deactive-setting', $setting->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn-link p-0 delete-tr">Deactive</button>
                                                    </form>
                                                    @endif
                                                </td>
                                                <td class="text-right">
                                                    <a href="{{ url('admin/setting/edit',$setting->id)}}">
                                                        <i class="fa fa-edit" aria-hidden="true"></i>
                                                        </a>
                                                    <form action="{{ route('admin.delete-setting', $setting->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this setting?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-link p-0 delete-tr" aria-label="Delete setting"><i class="fa fa-trash" aria-hidden="true"></i></button>
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
    </div>
</section>


@endsection
