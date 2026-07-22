@extends('main')
@section('content')
@php($statusLabels=['assigned'=>'تفویض شدہ','in_progress'=>'جاری','completed'=>'مکمل','cancelled'=>'منسوخ'])
<section class="main-content"><div class="container-fluid px-3 px-md-4 py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3"><div><a href="{{ route('admin.tailor-jobs.index') }}">← کام کی فہرست</a><h3 class="mt-2 mb-1">آرڈر #{{ $order->id }} — کاریگر اور کام</h3><div class="text-muted">گاہک: {{ $order->customers->name ?? 'نام موجود نہیں' }} · سوٹ: {{ $order->suitQuantity ?: 1 }}</div></div><a class="btn btn-outline-primary" href="{{ route('admin.production-workers.index') }}">پروڈکشن ورکرز</a></div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><strong>کام محفوظ نہیں ہو سکا۔</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="row"><div class="col-lg-8 mb-4"><div class="card"><div class="card-header"><strong>تفویض شدہ کام</strong></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>کام</th><th>ورکر</th><th>مقدار × شرح</th><th>کل اجرت</th><th>حالت</th><th>عمل</th></tr></thead><tbody>
        @forelse($order->workAssignments as $assignment)<tr>
            <td>{{ $assignment->workType->name ?? 'کام' }}@if($assignment->legacy_key)<br><span class="badge badge-info">موجودہ ٹیلرنگ آرڈر</span>@endif</td>
            <td><a href="{{ route('admin.production-workers.show',$assignment->worker) }}">{{ $assignment->worker->name ?? 'ورکر' }}</a></td>
            <td>{{ number_format((float)$assignment->quantity,2) }} × روپے {{ number_format((float)$assignment->rate,2) }}</td>
            <td>روپے {{ number_format((float)$assignment->amount,2) }}</td>
            <td><span class="badge badge-{{ $assignment->status==='completed'?'success':($assignment->status==='cancelled'?'secondary':'warning') }}">{{ $statusLabels[$assignment->status] ?? $assignment->status }}</span></td>
            <td>@if(!$assignment->legacy_key && in_array($assignment->status,['assigned','in_progress']))<form method="POST" action="{{ route('admin.orders.workforce.status',[$order,$assignment]) }}">@csrf @method('PATCH')<div class="input-group input-group-sm"><select name="status" class="form-control">@if($assignment->status==='assigned')<option value="in_progress">کام شروع</option>@endif<option value="completed">مکمل</option><option value="cancelled">منسوخ</option></select><div class="input-group-append"><button class="btn btn-outline-primary">محفوظ</button></div></div></form>@elseif($assignment->legacy_key)<small class="text-muted">مرکزی آرڈر مراحل سے منسلک</small>@else—@endif</td>
        </tr>@empty<tr><td colspan="6" class="text-center text-muted py-4">کوئی کام تفویض نہیں۔</td></tr>@endforelse
    </tbody></table></div></div></div>
    <div class="col-lg-4 mb-4"><div class="card"><div class="card-header"><strong>اضافی کام تفویض کریں</strong></div><div class="card-body">
        @if($order->status==='delivered')<div class="alert alert-info mb-0">یہ آرڈر حوالہ کیا جا چکا ہے، اس لیے نیا کام تفویض نہیں کیا جا سکتا۔</div>@elseif($workers->isEmpty())<div class="alert alert-warning">پہلے پروڈکشن ورکر اور اس کی اجرت بنائیں۔</div><a class="btn btn-primary" href="{{ route('admin.production-workers.create') }}">نیا ورکر</a>@else
        <form method="POST" action="{{ route('admin.orders.workforce.store',$order) }}">@csrf
            <div class="form-group"><label for="worker_id">ورکر</label><select id="worker_id" name="production_worker_id" class="form-control" required><option value="">منتخب کریں</option>@foreach($workers as $worker)<option value="{{ $worker->id }}">{{ $worker->name }}</option>@endforeach</select></div>
            <div class="form-group"><label for="work_type_id">کام اور فی عدد اجرت</label><select id="work_type_id" name="work_type_id" class="form-control" required disabled><option value="">پہلے ورکر منتخب کریں</option>@foreach($workers as $worker)@foreach($worker->skills as $skill)@php($plan=$worker->compensationPlans->firstWhere('work_type_id',$skill->id))@if($plan && in_array($plan->method,['per_piece','hybrid']) && (float)$plan->rate>0)<option value="{{ $skill->id }}" data-worker="{{ $worker->id }}" hidden>{{ $skill->name }} — روپے {{ number_format((float)$plan->rate,2) }}</option>@endif @endforeach @endforeach</select><small class="form-text text-muted">شرح اجرت کے اصول سے خود لی جائے گی اور بعد میں نہیں بدلے گی۔</small></div>
            <div class="form-group"><label for="quantity">مقدار</label><input id="quantity" type="number" name="quantity" min="0.001" step="0.001" class="form-control" value="{{ old('quantity',$order->suitQuantity ?: 1) }}" required></div>
            <div class="form-group"><label for="notes">کام کی ہدایت</label><textarea id="notes" name="notes" class="form-control" rows="3" maxlength="1000"></textarea></div>
            <button class="btn btn-primary" type="submit">کام تفویض کریں</button>
        </form>@endif
    </div></div></div></div>
</div></section>
<script>
$(function(){
    $('#worker_id').on('change',function(){
        var worker=$(this).val(), select=$('#work_type_id');
        select.prop('disabled',!worker).val('');
        select.find('option[data-worker]').prop('hidden',true).prop('disabled',true);
        select.find('option[data-worker="'+worker+'"]').prop('hidden',false).prop('disabled',false);
        select.find('option:first').text(worker?'کام منتخب کریں':'پہلے ورکر منتخب کریں');
    });
});
</script>
@endsection
