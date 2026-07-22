@extends('main')
@section('content')
<section class="main-content">
    <div class="container">
        <div class="card">
        <div class="row">
            <div class="col-md-12">

                @include('inc.message')

                <div class="bg-white px-3 py-4">
                    <p class="text-right"><a href="{{url('admin/sale/create')}}" class="btn btn-primary">فروخت +</a>
                    </p>
                    <div class="table-title  mb-4 mt-2">
                        <h5 class="text-right">فروخت ریکارڈ</h5>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table js-sortable-table cc-table-data-options-history"
                                    id="cc-table-data-options-history">
                                    <thead>
                                        <tr>
                                            <th scope="col" class="no-sort"></th>
                                            <th scope="col" class="no-sort">گاہک کا نام</th>
                                            <th scope="col" class="no-sort">فروخت کی رقم</th>
                                            <th scope="col" class="no-sort">مزید تفصیلات </th>
                                            <th scope="col" class="no-sort">عمل</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($sales as $sale)
                                        <tr>
                                            <td></td>
                                            <td>{{$sale->customer_name}}</td>
                                            <td>{{$sale->detail->sum('price')}}</td>
                                            <td><a href="{{ url('admin/sale/'.$sale->id) }}" class=""><i class="fa fa-eye" aria-hidden="true"></i></a></td>
                                            <td>
                                                <a href="{{ url('admin/sale/'.$sale->id.'/edit')}}"
                                                    class="btn btn-primary" style="font-size: 12px;">فروخت+</a>

                                                <form action="{{ route('admin.sale.destroy', $sale->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this sale?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-link p-0 delete-tr" aria-label="Delete sale"><i class="fa fa-trash-alt" aria-hidden="true"></i></button>
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
