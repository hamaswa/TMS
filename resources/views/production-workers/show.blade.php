@extends('main')
@section('content')
@php($methodLabels=['fixed_salary'=>'ماہانہ تنخواہ','per_piece'=>'فی عدد','commission'=>'کمیشن','hybrid'=>'مشترکہ'])
@php($entryLabels=['earning'=>'کمائی','payment'=>'ادائیگی','salary'=>'تنخواہ','commission'=>'کمیشن','advance'=>'ایڈوانس','adjustment'=>'ایڈجسٹمنٹ'])
<section class="main-content"><div class="container-fluid px-3 px-md-4 py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3"><div><a href="{{ route('admin.production-workers.index') }}">← پروڈکشن ورکرز</a><h1 class="h3 mt-2 mb-1">{{ $worker->name }}</h1><div class="text-muted">{{ $worker->relationship_type === 'employee' ? 'تنخواہ دار ملازم' : 'آزاد کاریگر' }} · {{ $worker->phone ?: 'فون درج نہیں' }}</div></div><a class="btn btn-outline-primary" href="{{ route('admin.production-workers.edit', $worker) }}">معلومات تبدیل کریں</a></div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="row mb-3"><div class="col-md-4"><div class="card border-left-danger"><div class="card-body"><div class="text-muted">موجودہ واجب الادا</div><div class="h3 mb-0">روپے {{ number_format($balance,2) }}</div></div></div></div><div class="col-md-8"><div class="card"><div class="card-body"><div class="text-muted mb-2">کام کی مہارت</div>@foreach($worker->skills as $skill)<span class="badge badge-info ml-1 p-2">{{ $skill->name }}</span>@endforeach</div></div></div></div>
    <div class="row">
        <div class="col-lg-7 mb-4"><div class="card h-100"><div class="card-header"><strong>اجرت کے اصول</strong></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>کام</th><th>طریقہ</th><th>شرح</th><th>حالت</th></tr></thead><tbody>@forelse($worker->compensationPlans->sortByDesc('id') as $plan)<tr><td>{{ $plan->workType->name ?? 'تمام کام' }}</td><td>{{ $methodLabels[$plan->method] ?? $plan->method }}</td><td>@if((float)$plan->fixed_salary>0)روپے {{ number_format((float)$plan->fixed_salary,2) }} ماہانہ<br>@endif @if((float)$plan->rate>0)روپے {{ number_format((float)$plan->rate,2) }} فی عدد<br>@endif @if((float)$plan->commission_percent>0){{ number_format((float)$plan->commission_percent,2) }}٪@endif</td><td><span class="badge badge-{{ $plan->active?'success':'secondary' }}">{{ $plan->active?'فعال':'پرانا' }}</span></td></tr>@empty<tr><td colspan="4" class="text-center text-muted">ابھی اجرت مقرر نہیں۔</td></tr>@endforelse</tbody></table></div></div></div>
        <div class="col-lg-5 mb-4"><div class="card h-100"><div class="card-header"><strong>نیا اجرت اصول</strong></div><div class="card-body"><form method="POST" action="{{ route('admin.production-workers.compensation.store',$worker) }}">@csrf<div class="form-group"><label for="worker_work_type">کام</label><select id="worker_work_type" name="work_type_id" class="form-control" required>@foreach($workTypes as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach</select></div><div class="form-group"><label for="worker_compensation_method">طریقۂ اجرت</label><select id="worker_compensation_method" name="method" class="form-control" required><option value="per_piece">فی عدد</option><option value="fixed_salary">ماہانہ تنخواہ</option><option value="commission">فیصد کمیشن</option><option value="hybrid">مشترکہ</option></select><small id="worker_compensation_help" class="form-text text-muted">ہر تیار شدہ عدد کی اجرت درج کریں۔</small></div><div class="form-row"><div class="form-group col-md-4" data-compensation-field="rate"><label for="worker_piece_rate">فی عدد رقم</label><input id="worker_piece_rate" type="number" name="rate" min="0" step="0.01" class="form-control" value="0"></div><div class="form-group col-md-4" data-compensation-field="fixed_salary"><label for="worker_fixed_salary">ماہانہ تنخواہ</label><input id="worker_fixed_salary" type="number" name="fixed_salary" min="0" step="0.01" class="form-control" value="0"></div><div class="form-group col-md-4" data-compensation-field="commission"><label for="worker_commission">کمیشن ٪</label><input id="worker_commission" type="number" name="commission_percent" min="0" max="100" step="0.01" class="form-control" value="0"></div></div><div class="form-group"><label for="worker_effective_from">نافذ ہونے کی تاریخ</label><input id="worker_effective_from" type="date" name="effective_from" class="form-control" value="{{ now()->toDateString() }}"></div><button class="btn btn-primary" type="submit">اصول محفوظ کریں</button></form></div></div></div>
    </div>
    <div class="row"><div class="col-lg-8 mb-4"><div class="card"><div class="card-header"><strong>ورکر کھاتہ</strong></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>تاریخ</th><th>قسم</th><th>حوالہ</th><th>رقم</th></tr></thead><tbody>@forelse($entries as $entry)<tr><td>{{ $entry->entry_date->format('d-m-Y') }}</td><td>{{ $entryLabels[$entry->entry_type] ?? $entry->entry_type }}</td><td>@if($entry->assignment)<a href="{{ route('admin.order.edit',$entry->assignment->order_id) }}">آرڈر #{{ $entry->assignment->order_id }}</a>@else{{ $entry->notes ?: '—' }}@endif</td><td class="{{ (float)$entry->amount>=0?'text-danger':'text-success' }}">{{ (float)$entry->amount>=0?'+':'−' }} روپے {{ number_format(abs((float)$entry->amount),2) }}</td></tr>@empty<tr><td colspan="4" class="text-center text-muted py-4">ابھی کوئی کھاتہ اندراج نہیں۔</td></tr>@endforelse</tbody></table></div>@if($entries->hasPages())<div class="card-footer">{{ $entries->links() }}</div>@endif</div></div>
        <div class="col-lg-4 mb-4"><div class="card"><div class="card-header"><strong>ادائیگی درج کریں</strong></div><div class="card-body">@if($balance > 0)<form method="POST" action="{{ route('admin.production-workers.payments.store',$worker) }}">@csrf<div class="form-group"><label for="worker_payment_amount">رقم</label><input id="worker_payment_amount" type="number" name="amount" min="0.01" max="{{ max(0,$balance) }}" step="0.01" class="form-control" required></div><div class="form-group"><label for="worker_payment_date">تاریخ</label><input id="worker_payment_date" type="date" name="entry_date" class="form-control" value="{{ now()->toDateString() }}" required></div>@include('components.payment-method-fields', ['prefix' => 'worker_payment'])<div class="form-group"><label for="worker_payment_notes">نوٹ</label><textarea id="worker_payment_notes" name="notes" class="form-control" rows="2" maxlength="500"></textarea></div><button class="btn btn-success" type="submit">ادائیگی محفوظ کریں</button></form>@else<div class="alert alert-info mb-0" role="status">اس ورکر کی کوئی واجب الادا رقم نہیں۔ کام مکمل ہونے اور اجرت درج ہونے کے بعد ادائیگی یہاں دستیاب ہوگی۔</div>@endif</div></div></div></div>
</div></section>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const method = document.getElementById('worker_compensation_method');
    const help = document.getElementById('worker_compensation_help');
    const groups = document.querySelectorAll('[data-compensation-field]');

    if (!method || !help || !groups.length) {
        return;
    }

    const visibleFields = {
        per_piece: ['rate'],
        fixed_salary: ['fixed_salary'],
        commission: ['commission'],
        hybrid: ['rate', 'fixed_salary', 'commission']
    };
    const helpText = {
        per_piece: 'ہر تیار شدہ عدد کی اجرت درج کریں۔',
        fixed_salary: 'ورکر کی ماہانہ مقررہ تنخواہ درج کریں۔',
        commission: 'کام یا آمدن میں سے کمیشن فیصد درج کریں۔',
        hybrid: 'مشترکہ اصول میں صرف وہ رقم یا فیصد درج کریں جو اس ورکر پر لاگو ہو۔'
    };

    const syncCompensationFields = function () {
        const active = visibleFields[method.value] || [];
        groups.forEach(function (group) {
            const visible = active.includes(group.dataset.compensationField);
            group.hidden = !visible;
            const input = group.querySelector('input');
            if (input) {
                input.disabled = !visible;
            }
        });
        help.textContent = helpText[method.value] || '';
    };

    method.addEventListener('change', syncCompensationFields);
    syncCompensationFields();
});
</script>
@endpush
@endsection
