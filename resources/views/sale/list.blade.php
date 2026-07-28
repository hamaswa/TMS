@extends('main')
@push('styles')
<style>
@media (max-width: 767.98px) {
    .sale-record-table, .sale-record-table tbody, .sale-record-table tr, .sale-record-table td { display:block; width:100%; }
    .sale-record-table thead { display:none; }
    .sale-record-table tr { border:1px solid #e3e8ee; border-radius:.65rem; margin-bottom:1rem; padding:.4rem .75rem; }
    .sale-record-table td { display:flex; justify-content:space-between; align-items:center; gap:1rem; border-top:1px solid #eef1f4; padding:.7rem 0; }
    .sale-record-table td:first-child { border-top:0; }
    .sale-record-table td::before { content:attr(data-label); flex:0 0 38%; color:#6c757d; font-weight:700; }
    .sale-record-table .sale-record-actions { display:grid; grid-template-columns:1fr; gap:.5rem; }
    .sale-record-table .sale-record-actions::before { display:none; }
    .sale-record-table .sale-record-actions .btn, .sale-record-table .sale-record-actions form { width:100%; }
}
</style>
@endpush
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
                        <h1 class="h4 text-right">فروخت ریکارڈ</h1>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table js-sortable-table sale-record-table"
                                    id="cc-table-data-options-history">
                                    <thead>
                                        <tr>
                                            <th scope="col" class="no-sort">گاہک کا نام</th>
                                            <th scope="col" class="no-sort">فروخت کی رقم</th>
                                            <th scope="col" class="no-sort">مزید تفصیلات </th>
                                            <th scope="col" class="no-sort">عمل</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($sales as $sale)
                                        <tr>
                                            <td data-label="گاہک کا نام">{{ $sale->customer?->name ?? $sale->customer_name }}</td>
                                            <td data-label="فروخت کی رقم">Rs {{ number_format($sale->detail->sum(fn ($detail) => (float) $detail->price * (int) $detail->quantity), 2) }}</td>
                                            <td data-label="مزید تفصیلات"><a href="{{ route('admin.sale.show', $sale) }}" class="btn btn-sm btn-outline-primary">دیکھیں</a></td>
                                            <td class="sale-record-actions" data-label="عمل">
                                                <a href="{{ url('admin/sale/'.$sale->id.'/edit')}}"
                                                    class="btn btn-sm btn-primary">تبدیل کریں</a>

                                                <form action="{{ route('admin.sale.destroy', $sale->id) }}" method="POST" class="d-inline" data-confirm="کیا آپ واقعی یہ فروخت حذف کرنا چاہتے ہیں؟">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="{{ $sale->customer?->name ?? $sale->customer_name }} کی فروخت حذف کریں">حذف کریں</button>
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
