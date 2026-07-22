@extends('main')
@section('content')
<section class="main-content"><div class="container py-4"><div class="mb-3"><a href="{{ route('admin.production-workers.index') }}">← پروڈکشن ورکرز</a><h3 class="mt-2">نیا پروڈکشن ورکر</h3><p class="text-muted">یہ ریکارڈ ملازم اکاؤنٹ اور اجازتوں سے مکمل طور پر الگ رہے گا۔</p></div>
<div class="card card-body mb-4"><form method="POST" action="{{ route('admin.production-workers.store') }}">@csrf @include('production-workers._form')</form></div>
<div class="card"><div class="card-header"><strong>مطلوبہ کام فہرست میں نہیں؟</strong></div><div class="card-body"><form method="POST" action="{{ route('admin.production-work-types.store') }}" class="form-inline">@csrf<label for="work_name" class="ml-2">نیا کام</label><input id="work_name" name="name" class="form-control ml-2" maxlength="100" placeholder="مثلاً ہاتھ کی کڑھائی" required><button class="btn btn-outline-primary" type="submit">کام شامل کریں</button></form></div></div>
</div></section>
@endsection
