@if($errors->any())<div class="alert alert-danger"><strong>معلومات محفوظ نہیں ہو سکیں۔</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="form-row">
    <div class="form-group col-md-6"><label for="name">نام</label><input id="name" name="name" class="form-control" maxlength="150" value="{{ old('name', $worker->name ?? '') }}" required></div>
    <div class="form-group col-md-6"><label for="relationship_type">کاروبار سے تعلق</label><select id="relationship_type" name="relationship_type" class="form-control" required><option value="contractor" @selected(old('relationship_type', $worker->relationship_type ?? 'contractor')==='contractor')>آزاد کاریگر / ٹھیکیدار</option><option value="employee" @selected(old('relationship_type', $worker->relationship_type ?? '')==='employee')>تنخواہ دار ملازم</option></select><small class="form-text text-muted">اس انتخاب سے سسٹم لاگ اِن یا اجازتیں نہیں ملتیں۔</small></div>
</div>
<div class="form-row">
    <div class="form-group col-md-6"><label for="phone">فون نمبر</label><input id="phone" name="phone" class="form-control" maxlength="50" value="{{ old('phone', $worker->phone ?? '') }}"></div>
    <div class="form-group col-md-6"><label for="email">ای میل</label><input id="email" type="email" name="email" class="form-control" value="{{ old('email', $worker->email ?? '') }}"></div>
</div>
<fieldset class="border rounded p-3 mb-3"><legend class="w-auto px-2 h6">یہ شخص کون سے کام کر سکتا ہے؟</legend><div class="row">@foreach($workTypes as $type)<div class="col-md-4 col-6 mb-2"><label class="d-flex align-items-center"><input type="checkbox" name="work_type_ids[]" value="{{ $type->id }}" class="ml-2" @checked(in_array($type->id, old('work_type_ids', isset($worker) ? $worker->skills->pluck('id')->all() : [])))>{{ $type->name }}</label></div>@endforeach</div></fieldset>
<div class="form-group"><label for="notes">نوٹ</label><textarea id="notes" name="notes" class="form-control" rows="3" maxlength="1000">{{ old('notes', $worker->notes ?? '') }}</textarea></div>
@isset($worker)<div class="form-group form-check"><input type="hidden" name="active" value="0"><input id="active" type="checkbox" name="active" value="1" class="form-check-input" @checked(old('active', $worker->active))><label for="active" class="form-check-label">ورکر فعال ہے</label></div>@endisset
<button class="btn btn-primary" type="submit">معلومات محفوظ کریں</button>
