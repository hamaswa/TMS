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
                                   @php $button='';
                                    if($record->status =='new')
                                    {
                                        $button ='New';
                                        $btn = 'btn btn-primary btn-sm admin-order-status';
                                    }elseif ($record->status =='start') {
                                        $button ='Start';
                                        $btn = 'btn btn-warning btn-sm admin-order-status';
                                    }else{
                                        $button ='Complete';
                                        $btn = 'btn btn-success btn-sm admin-order-status';
                                    } @endphp
                                    <tr class="f">
                                        <td></td>
                                        <td>{{$record->customers->name}}</td>
                                        <td>{{$record->suitQuantity}}</td>
                                        <td>{{$record->tailor_price}}</td>
                                        <td><button type="button" class="<?php echo $btn ?>" data-toggle="modal" data-target="#myModal" data-orderid="<?php echo $record->id ?>" data-orderstatus="<?php echo $record->status ?>">{{$button}}</button></td>
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
<!-- The Modal -->
<div class="modal" id="myModal">
  <div class="modal-dialog">
    <div class="modal-content">
        <form action="{{url('tailor/order-status')}}" method="post">
            @csrf
            <input type="hidden" value="" id="order_id" name="order_id">
       
      <!-- Modal Header -->
      <div class="modal-header">
        <h4 class="modal-title">آرڈر کی درجہ منتخب کریں۔</h4>
      </div>

      <!-- Modal body -->
      <div class="modal-body">
       <p class="text-right"><label>درجہ منتخب کریں</label></p>
       <select class="form-control order-status" name="order_status">
           @foreach (\App\Models\Order::STATUSES as $status)
               <option value="{{ $status }}">{{ \App\Models\Order::STATUS_LABELS[$status] ?? ucfirst($status) }}</option>
           @endforeach
       </select>
      </div>

      <!-- Modal footer -->
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary  ml-2">محفوظ کریں</button>
        <button type="button" class="btn btn-danger" data-dismiss="modal">بند کریں</button>
        
      </div>
        </form>
    </div>
    </div>
  </div>
</div>
@endsection
