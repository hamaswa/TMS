@extends('main')
@section('content')
<style>
    .bg-own{
        background: #b9c2cc !important;
        color: black !important;
        font-weight: bold;
        font-size: small
    }
</style>
<section class="main-content">
    <div class="container">
         <div class="card">
        <div class="row">
            <div class="col-md-12">
                <h5 class="text-right"> درزی کا نام :{{$tailor->name}} </h5>

                {{-- <form method="post" action="{{url('admin/tailor-weakly-print',$data['tailor-id'])}}"> --}}
                    {{-- @csrf
                    <input name="Date" placeholder="Date" id="myflatpickr" required>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-print"></i></button>
                </form> --}}
                <div class="bg-white px-3 py-4">
                    <div class="table-title  mb-4">
                        <h5 class="text-right">درزی ریکارڈ</h5>
                        <a href="{{ url('admin/tailor/report-print/'.$tailor->id) }}" class="btn btn-primary">Print</a>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table js-sortable-table cc-table-data-options-history"
                                    id="cc-table-data-options-history">
                                    <thead>
                                        <tr>
                                            <th scope="col" class="no-sort">#</th>
                                            <th scope="col" class="no-sort"> درزی کی رقم </th>
                                            <th scope="col" class="no-sort">کپڑے کی سلائی کی قسم</th>
                                            <th scope="col" class="no-sort">تاریخ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($tailor_report as $report)
                                        <tr class="f">
                                            <td>{{$loop->iteration}}</td>
                                            <td>{{$report->tailor_price}}</td>
                                            <td>{{$report->rate->options->Name}}</td>
                                            <td>{{ date('d-m-Y', strtotime($report->created_at))}}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-own mt-2">
                                            <td align="right" colspan="3">گزشتہ ہفتے کی کل آمدنی</td>
                                            <td>{{ $tailor_report->sum('tailor_price') }}</td>
                                        </tr>
                                        <tr class="bg-dark text-white mt-2">
                                            <td align="right" colspan="3">کل رقم</td>
                                            <td>{{ $total_amount }}</td>
                                        </tr>
                                    </tfoot>
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