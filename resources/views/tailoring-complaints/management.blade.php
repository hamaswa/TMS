@php
    $caseLabels = ['pending'=>'زیر جائزہ','investigating'=>'جانچ جاری','approved'=>'منظور','rework'=>'دوبارہ کام','settled'=>'تصفیہ مکمل','closed'=>'بند','rejected'=>'مسترد','withdrawn'=>'واپس لی گئی'];
    $recoveryLabels = ['unpaid_earnings'=>'غیر ادا شدہ اجرت سے','future_pay'=>'آئندہ ادائیگی سے','security_deposit'=>'سیکیورٹی ڈپازٹ سے','cash_recovered'=>'نقد وصولی','shop_absorbed'=>'دکان نے نقصان برداشت کیا'];
@endphp
<div class="border rounded p-3 mt-3" data-complaint-case="{{ $claim->id }}">
    <div class="d-flex justify-content-between"><strong>کیس #{{ $claim->id }}</strong><span class="badge badge-info">{{ $caseLabels[$claim->status] ?? $claim->status }}</span></div>
    <p class="mb-2">{{ $claim->reason }}</p>
    @if(!$claim->closed_at)
        <form method="POST" enctype="multipart/form-data" action="{{ route('admin.tailoring-complaints.evidence', $claim) }}" class="mb-3">@csrf
            <label>تصویر یا PDF ثبوت</label><div class="input-group"><input type="file" name="evidence" class="form-control" accept="image/jpeg,image/png,image/webp,application/pdf" required><div class="input-group-append"><button class="btn btn-outline-secondary">ثبوت شامل کریں</button></div></div><small class="text-muted">زیادہ سے زیادہ 5MB؛ کیس بند ہونے کے بعد ثبوت تبدیل نہیں ہوگا۔</small>
        </form>
    @endif
    @if($claim->evidence?->isNotEmpty())<div class="mb-3"><strong>ثبوت:</strong> @foreach($claim->evidence as $file)<a class="btn btn-sm btn-link" href="{{ route('admin.tailoring-complaints.evidence.download', [$claim, $file]) }}">{{ $file->original_name }}</a>@endforeach</div>@endif
    @if(in_array($claim->status, \App\Models\CounterOrderComplaint::OPEN_STATUSES, true))
        <form method="POST" action="{{ route('admin.tailoring-complaints.decision', $claim) }}" data-confirm="جانچ اور فیصلہ محفوظ کریں؟">@csrf
            <div class="form-row">
                <div class="col-md-4 form-group"><label>خرابی کی قسم</label><select name="category" class="form-control" required><option value="stitching_defect">سلائی کی خرابی</option><option value="measurement_error">غلط پیمائش</option><option value="cloth_damage">کپڑے کا نقصان</option><option value="fabric_defect">کپڑے کا اپنا عیب</option><option value="shop_error">دکان کی غلطی</option><option value="customer_change">گاہک کی تبدیلی</option><option value="other">دیگر</option></select></div>
                <div class="col-md-4 form-group"><label>ذمہ دار فریق</label><select name="responsible_party" class="form-control" required><option value="tailor">درزی</option><option value="cutter">کٹر</option><option value="shop">دکان</option><option value="customer">گاہک</option><option value="fabric">کپڑا</option><option value="undetermined">تعین نہیں ہوا</option></select></div>
                <div class="col-md-4 form-group"><label>ذمہ دار درزی</label><select name="responsible_tailor_id" class="form-control"><option value="">منتخب نہیں</option>@foreach($tailors as $tailor)<option value="{{ $tailor->id }}" @selected($claim->responsible_tailor_id == $tailor->id)>{{ $tailor->name }}</option>@endforeach</select></div>
            </div>
            <div class="form-group"><label>جانچ کے نتائج</label><textarea name="inspection_notes" class="form-control" minlength="10" required>{{ $claim->inspection_notes }}</textarea></div>
            <div class="form-row"><div class="col-md-6 form-group"><label>فیصلہ</label><select name="resolution" class="form-control" required><option value="rework">دوبارہ سلائی / ترمیم</option><option value="replacement">متبادل لباس</option><option value="refund">رقم واپسی</option><option value="compensation">صرف ہرجانہ</option><option value="refund_and_compensation">واپسی اور ہرجانہ</option><option value="rejected">دعویٰ مسترد</option><option value="withdrawn">دعویٰ واپس</option></select></div><div class="col-md-6 form-group"><label>دوبارہ کام کی آخری تاریخ</label><input type="date" name="rework_due_on" class="form-control" min="{{ now()->toDateString() }}"></div></div>
            <div class="form-group"><label>فیصلے کی وجہ / ہدایات</label><textarea name="decision_notes" class="form-control" minlength="5" required>{{ $claim->decision_notes }}</textarea></div>
            <button class="btn btn-primary">جانچ اور فیصلہ محفوظ کریں</button>
        </form>
    @endif
    @if(in_array($claim->status, ['approved','settled','rework'], true))
        <hr><form method="POST" action="{{ route('admin.tailoring-complaints.recovery', $claim) }}">@csrf
            <strong>درزی / دکان سے نقصان کی ریکوری</strong><div class="form-row mt-2"><div class="col-md-4 form-group"><select name="method" class="form-control" required>@foreach($recoveryLabels as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div><div class="col-md-4 form-group"><select name="tailor_id" class="form-control"><option value="">درزی منتخب کریں</option>@foreach($tailors as $tailor)<option value="{{ $tailor->id }}" @selected($claim->responsible_tailor_id == $tailor->id)>{{ $tailor->name }}</option>@endforeach</select></div><div class="col-md-4 form-group"><input type="number" name="amount" min="0.01" step="0.01" class="form-control" placeholder="رقم" required></div></div><input name="note" class="form-control mb-2" placeholder="ریکوری نوٹ"><button class="btn btn-outline-danger">ریکوری محفوظ کریں</button>
        </form>
    @endif
    @if($claim->recoveries?->isNotEmpty())<div class="mt-2"><strong>ریکوری:</strong>@foreach($claim->recoveries as $recovery)<div>{{ $recoveryLabels[$recovery->method] ?? $recovery->method }} — Rs {{ number_format($recovery->amount,2) }} — {{ $recovery->status }}</div>@endforeach</div>@endif
    @if($claim->status === 'rework')<hr><form method="POST" action="{{ route('admin.tailoring-complaints.complete-rework', $claim) }}">@csrf<div class="form-group"><label>دوبارہ کام کی تکمیل</label><textarea name="completion_note" class="form-control" minlength="5" required></textarea></div><button class="btn btn-success">کام مکمل اور کیس بند کریں</button></form>@endif
</div>
