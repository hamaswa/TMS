@extends('main')
@section('content')
<section class="main-content">
    <div class="container">
        <div class="card">
        <div class="row">
            <div class="col-md-12">
                <h5 class="text-right">{{$data['tailor-name']}} : درزی کا نام </h5>
                @if(Session::has('insert'))
                <div class="alert alert-success">{{Session::get('insert')}}</div>
                @endif
                <div class="">
                    <form method="post" action="{{url('tailor/tailor-weakly-print',$data['tailor-id'])}}">
                        @csrf
                        <input name="Date" placeholder="Date" id="myflatpickr" required>
                        <button type="submit" class="btn btn-primary"><i class="fa fa-print"></i></button>
                    </form>

                </div>
                <div class="bg-white px-3 py-4">
                    <div class="table-title  mb-4">
                        <h5 class="text-right">درزی ریکارڈ</h5>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table js-sortable-table cc-table-data-options-history"
                                id="cc-table-data-options-history">
                                <thead>
                                    <tr>
                                        <th scope="col" class="no-sort"></th>
                                        <th scope="col" class="no-sort">نام</th>
                                        <th scope="col" class="no-sort">کپڑوں کی تعداد</th>
                                        <th scope="col" class="no-sort">درزی رقم</th>
                                        <th scope="col" class="no-sort">درج</th>
                                        <th scope="col" class="no-sort">تاریخ</th>
                                        <th scope="col" class="no-sort">واپسی تاریخ</th>
                                        <!-- <th scope="col" class="no-sort">منتخب کریں</th> -->

                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($Tailor_records->orders as $record)
                                   @php
                                       $nextStatusOptions = collect($record->nextStatusOptions())
                                           ->reject(fn ($option) => $option['value'] === 'delivered');
                                   @endphp
                                    <tr class="f">
                                        <td></td>
                                        <td>{{$record->customers->name}}</td>
                                        <td>{{$record->suitQuantity}}</td>
                                        <td>{{$record->tailor_price}}</td>
                                        <td>
                                            <span class="badge badge-info">{{ \App\Models\Order::STATUS_LABELS[$record->status] ?? $record->status }}</span>
                                            @if($nextStatusOptions->isNotEmpty())
                                                <form action="{{ route('tailor.jobs.status', $record) }}" method="post" class="mt-2">
                                                    @csrf @method('PATCH')
                                                    <div class="input-group input-group-sm">
                                                        <select name="status" class="form-control" required>
                                                            @foreach($nextStatusOptions as $option)
                                                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                                            @endforeach
                                                        </select>
                                                        <div class="input-group-append"><button class="btn btn-primary">اپ ڈیٹ کریں</button></div>
                                                    </div>
                                                </form>
                                            @endif
                                        </td>
                                        <td>{{ date('d-m-Y', strtotime($record->created_at))}}</td>
                                        <td>{{ date('d-m-Y', strtotime($record->returnDate))}}</td>

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
</section>
@endsection
