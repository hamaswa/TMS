@extends('main')
@section('content')
    <style>
        .bg-own {
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
                        @include('inc.message')
                        <h5 class="text-right">درزی کا نام: {{ $tailor->name }}</h5>

                        {{-- <form method="post" action="{{url('admin/tailor-weakly-print',$data['tailor-id'])}}"> --}}
                        {{-- @csrf
                    <input name="Date" placeholder="Date" id="myflatpickr" required>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-print"></i></button>
                </form> --}}
                        <div class="bg-white px-3 py-4">
                            <p class="text-right"><a href="{{ url('admin/tailors-rates/create/'.$tailor->id) }}"
                                    class="btn btn-primary">نئی اجرت مقرر کریں</a>
                            </p>
                            <div class="table-title  mb-4">
                                <h5 class="text-right">درزی کی اجرت کے نرخ</h5>
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
                                                    <th scope="col" class="no-sort">سلائی کی قسم</th>
                                                    <th scope="col" class="no-sort">تاریخ</th>
                                                    <th scope="col" class="no-sort">عمل</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($tailor_rates as $rate)
                                                    <tr class="f">
                                                        <td>{{ $loop->iteration }}</td>
                                                        <td>روپے {{ number_format((float) $rate->price, 2) }}</td>
                                                        <td>{{ $rate->options?->Name ?: ($rate->type ?: 'سلائی') }}</td>
                                                        <td>{{ date('d-m-Y', strtotime($rate->created_at)) }}</td>
                                                        <td>
                                                            <form action="{{ route('admin.tailor-rates.delete', $rate->id) }}" method="POST" class="d-inline" data-confirm="کیا آپ واقعی یہ اجرت حذف کرنا چاہتے ہیں؟">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-outline-danger btn-sm" aria-label="اجرت حذف کریں">حذف کریں</button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                @if($tailor_rates->isEmpty())
                                                    <tr><td colspan="5" class="text-center text-muted">ابھی کوئی اجرت مقرر نہیں کی گئی۔</td></tr>
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
        </div>
    </section>
@endsection
