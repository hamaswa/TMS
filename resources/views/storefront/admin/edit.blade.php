@extends('main')

@section('content')
<section class="main-content storefront-admin">
    <style>
        .storefront-admin{background:#f3f7f8;min-height:calc(100vh - 70px)}
        .storefront-hero{background:linear-gradient(135deg,#0f5132,#14805e);border-radius:22px;color:#fff;padding:1.8rem 2rem;box-shadow:0 16px 36px rgba(15,81,50,.2)}
        .storefront-hero h1{color:#fff!important}.storefront-hero p{color:rgba(255,255,255,.78)}
        .storefront-card{border:0;border-radius:18px;box-shadow:0 10px 28px rgba(31,45,61,.08);overflow:hidden}
        .storefront-card .card-header{background:#fff;border-bottom:1px solid #e8eef2;padding:1rem 1.25rem}
        .storefront-card .card-body{padding:1.35rem}
        .module-choice{display:block;border:1px solid #dbe7e2;border-radius:16px;padding:1rem;height:100%;background:#fbfefd;cursor:pointer}
        .module-choice:hover{border-color:#14805e}.module-choice input{margin-left:.55rem}
        .publish-state{border-radius:999px;padding:.45rem .8rem;font-weight:700}
        .preview-box{background:#eef8f4;border:1px dashed #78b89e;border-radius:14px;padding:1rem;word-break:break-word}
        .sticky-actions{position:sticky;bottom:0;background:rgba(255,255,255,.96);border-top:1px solid #e1e9ed;padding:1rem;z-index:10}
        @media(max-width:767px){.storefront-hero{border-radius:15px;padding:1.25rem}.storefront-card .card-body{padding:1rem}.sticky-actions .btn{width:100%;margin:.25rem 0!important}}
    </style>
    <div class="container-fluid px-3 px-md-4 py-4">
        <div class="storefront-hero mb-4 d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <div class="small mb-2">عوامی تشہیر اور آن لائن کاروبار</div>
                <h1 class="h3 mb-2">آن لائن دکان</h1>
                <p class="mb-0">اپنی دکان کا تعارف، رابطہ معلومات اور عوام کو دکھائے جانے والے شعبے ترتیب دیں۔</p>
            </div>
            <span class="publish-state mt-3 mt-md-0 {{ $storefront->is_published ? 'bg-success' : 'bg-light text-dark' }}">
                {{ $storefront->is_published ? 'شائع شدہ' : 'مسودہ' }}
            </span>
        </div>

        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if($storefront->exists && $storefront->moderation_status === 'paused')
            <div class="alert alert-danger"><strong>عوامی دکان عارضی طور پر روکی گئی ہے۔</strong><br>{{ $storefront->moderation_reason }}<br><small>آپ کی دکان، آرڈرز، گاہک اور تمام ریکارڈ محفوظ ہیں۔ مسئلہ حل ہونے کے بعد سپر ایڈمن عوامی رسائی دوبارہ بحال کر سکتا ہے۔</small></div>
        @endif
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <form method="POST" action="{{ route('admin.storefront.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-lg-8">
                    <div class="card storefront-card mb-4">
                        <div class="card-header"><h2 class="h5 mb-1">دکان کا تعارف</h2><p class="small text-muted mb-0">یہ معلومات ہر آنے والے گاہک کو نظر آئیں گی۔</p></div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group col-md-7"><label for="display_name">عوامی دکان کا نام</label><input id="display_name" name="display_name" class="form-control" maxlength="150" required value="{{ old('display_name',$storefront->display_name) }}"></div>
                                <div class="form-group col-md-5"><label for="slug">دکان کا مستقل لنک</label><div class="input-group" dir="ltr"><div class="input-group-prepend"><span class="input-group-text">/shops/</span></div><input id="slug" name="slug" class="form-control" maxlength="100" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" required value="{{ old('slug',$storefront->slug) }}"></div><small class="text-muted">صرف چھوٹے انگریزی حروف، اعداد اور ڈیش</small></div>
                            </div>
                            <div class="form-group"><label for="tagline">مختصر تعارفی جملہ</label><input id="tagline" name="tagline" class="form-control" maxlength="180" value="{{ old('tagline',$storefront->tagline) }}" placeholder="مثلاً معیاری کپڑا، نفیس سلائی اور بروقت فراہمی"></div>
                            <div class="form-group"><label for="description">کاروبار کی تفصیل</label><textarea id="description" name="description" class="form-control" rows="5" maxlength="3000" placeholder="اپنی خصوصیات، تجربہ اور خدمات بیان کریں۔">{{ old('description',$storefront->description) }}</textarea></div>
                            <div class="form-row">
                                <div class="form-group col-md-6"><label for="logo">لوگو <span class="text-muted">(اختیاری)</span></label><input id="logo" type="file" name="logo" class="form-control-file" accept="image/*"></div>
                                <div class="form-group col-md-6"><label for="cover">سرورق تصویر <span class="text-muted">(اختیاری)</span></label><input id="cover" type="file" name="cover" class="form-control-file" accept="image/*"></div>
                            </div>
                        </div>
                    </div>

                    @if($business->clothing_enabled)
                    <div class="card storefront-card mb-4">
                        <div class="card-header">
                            <h2 class="h5 mb-1">آن لائن فروخت</h2>
                            <p class="small text-muted mb-0">عوامی فہرست، آن لائن آرڈر اور ادائیگی کے طریقے الگ الگ کنٹرول کریں۔</p>
                        </div>
                        <div class="card-body">
                            <input type="hidden" name="commerce_settings_present" value="1">
                            <label class="module-choice d-block mb-3">
                                <div class="d-flex">
                                    <input type="checkbox" name="online_ordering_enabled" value="1" @checked(old('online_ordering_enabled',$storefront->online_ordering_enabled))>
                                    <div>
                                        <strong><i class="fas fa-shopping-cart text-success ml-1"></i> آن لائن آرڈر قبول کریں</strong>
                                        <div class="small text-muted mt-1">اسے بند رکھنے پر کپڑے، قیمت اور دستیابی نظر آئے گی مگر ٹوکری اور چیک آؤٹ نہیں ہوں گے۔</div>
                                    </div>
                                </div>
                            </label>
                            <h3 class="h6">قبول شدہ طریقے</h3>
                            <div class="row">
                                <div class="col-md-4 mb-2"><label class="module-choice d-flex align-items-center"><input type="checkbox" name="unpaid_orders_enabled" value="1" @checked(old('unpaid_orders_enabled',$storefront->unpaid_orders_enabled))> ابھی ادائیگی نہیں</label></div>
                                <div class="col-md-4 mb-2"><label class="module-choice d-flex align-items-center"><input type="checkbox" name="cod_enabled" value="1" @checked(old('cod_enabled',$storefront->cod_enabled))> کیش آن ڈیلیوری</label></div>
                                <div class="col-md-4 mb-2"><label class="module-choice d-flex align-items-center"><input type="checkbox" name="easypaisa_enabled" value="1" @checked(old('easypaisa_enabled',$storefront->easypaisa_enabled))> ایزی پیسہ — دستی تصدیق</label></div>
                            </div>
                            <div class="alert alert-info mb-0 mt-2">
                                <strong>اہم:</strong> ایزی پیسہ ابھی لائیو گیٹ وے نہیں ہے۔ گاہک حوالہ درج کرتا ہے اور دکان اسے دستی طور پر تصدیق کرتی ہے۔
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="card storefront-card mb-4">
                        <div class="card-header"><h2 class="h5 mb-1">عوام کو دکھائے جانے والے شعبے</h2><p class="small text-muted mb-0">صرف کاروبار کے فعال شعبے منتخب کیے جا سکتے ہیں۔</p></div>
                        <div class="card-body"><div class="row">
                            @if($business->clothing_enabled)<div class="col-md-6 mb-3"><label class="module-choice"><div class="d-flex"><input type="checkbox" name="show_clothing" value="1" @checked(old('show_clothing',$storefront->show_clothing))><div><strong><i class="fas fa-store text-success ml-1"></i> کپڑے کی دکان</strong><div class="small text-muted mt-1">کپڑے، رنگ، قیمت اور دستیاب اسٹاک</div></div></div></label></div>@endif
                            @if($business->tailoring_enabled)<div class="col-md-6 mb-3"><label class="module-choice"><div class="d-flex"><input type="checkbox" name="show_tailoring" value="1" @checked(old('show_tailoring',$storefront->show_tailoring))><div><strong><i class="fas fa-cut text-primary ml-1"></i> ٹیلرنگ خدمات</strong><div class="small text-muted mt-1">سلائی، ڈیزائن، پیمائش اور بکنگ</div></div></div></label></div>@endif
                        </div></div>
                    </div>

                    <div class="card storefront-card mb-4">
                        <div class="card-header"><h2 class="h5 mb-1">رابطہ اور سہولیات</h2></div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group col-md-6"><label for="public_phone">عوامی فون نمبر</label><input id="public_phone" name="public_phone" dir="ltr" class="form-control text-left" maxlength="50" value="{{ old('public_phone',$storefront->public_phone) }}"></div>
                                <div class="form-group col-md-6"><label for="public_email">عوامی ای میل</label><input id="public_email" type="email" name="public_email" dir="ltr" class="form-control text-left" maxlength="150" value="{{ old('public_email',$storefront->public_email) }}"></div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-8"><label for="address">دکان کا پتہ</label><textarea id="address" name="address" class="form-control" rows="3" maxlength="1000">{{ old('address',$storefront->address) }}</textarea></div>
                                <div class="form-group col-md-4"><label for="city">شہر</label><input id="city" name="city" class="form-control" maxlength="100" value="{{ old('city',$storefront->city) }}"></div>
                            </div>
                            <div class="form-group">
                                <label for="default_locale">عوامی دکان کی بنیادی زبان</label>
                                <select id="default_locale" name="default_locale" class="form-control">
                                    <option value="ur" @selected(old('default_locale', $storefront->default_locale ?: 'ur') === 'ur')>اردو — دائیں سے بائیں</option>
                                    <option value="en" @selected(old('default_locale', $storefront->default_locale) === 'en')>English — left to right</option>
                                </select>
                                <small class="text-muted">گاہک زبان تبدیل کر سکتے ہیں؛ ان کا انتخاب اگلی بار بھی محفوظ رہے گا۔</small>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-2"><label><input type="checkbox" name="inquiries_enabled" value="1" @checked(old('inquiries_enabled',$storefront->inquiries_enabled))> گاہک کے سوالات قبول کریں</label></div>
                                <div class="col-md-4 mb-2"><label><input type="checkbox" name="pickup_enabled" value="1" @checked(old('pickup_enabled',$storefront->pickup_enabled))> دکان سے وصولی</label></div>
                                <div class="col-md-4 mb-2"><label><input type="checkbox" name="delivery_enabled" value="1" @checked(old('delivery_enabled',$storefront->delivery_enabled))> گھر تک فراہمی</label></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card storefront-card mb-4">
                        <div class="card-header"><h2 class="h5 mb-0">عوامی لنک</h2></div>
                        <div class="card-body">
                            <div class="preview-box mb-3" dir="ltr">{{ url('/shops/'.$storefront->slug) }}</div>
                            @if($storefront->exists)<a class="btn btn-outline-primary btn-block mb-2" target="_blank" rel="noopener" href="{{ route('admin.storefront.preview') }}"><i class="fas fa-eye ml-1"></i> پیش منظر دیکھیں</a>@endif
                            @if($storefront->exists && $business->clothing_enabled)<a class="btn btn-outline-dark btn-block mb-2" href="{{ route('admin.storefront.clothing.index') }}"><i class="fas fa-tshirt ml-1"></i> کپڑوں کی عوامی فہرست</a>@endif
                            @if($storefront->exists && $business->clothing_enabled && Auth::user()->hasBusinessPermission('clothing.sales'))<a class="btn btn-outline-success btn-block mb-2" href="{{ route('admin.storefront.orders.index') }}"><i class="fas fa-shopping-bag ml-1"></i> آن لائن آرڈرز</a>@endif
                            @if($storefront->exists && $business->tailoring_enabled)<a class="btn btn-outline-dark btn-block mb-2" href="{{ route('admin.storefront.tailoring.services') }}"><i class="fas fa-cut ml-1"></i> ٹیلرنگ خدمات</a>@endif
                            @if($storefront->exists)<a class="btn btn-outline-info btn-block mb-2" href="{{ route('admin.storefront.inquiries.index') }}"><i class="fas fa-comments ml-1"></i> گاہکوں کی درخواستیں</a>@endif
                            @if($storefront->is_published)<a class="btn btn-outline-success btn-block" target="_blank" rel="noopener" href="{{ route('storefront.show',$storefront) }}"><i class="fas fa-external-link-alt ml-1"></i> عوامی دکان کھولیں</a>@endif
                        </div>
                    </div>
                    <div class="card storefront-card mb-4"><div class="card-body"><h3 class="h6">محفوظ اشاعت</h3><p class="small text-muted mb-0">مسودہ محفوظ کرنے سے دکان فوراً عوام کو نظر نہیں آئے گی۔ معلومات مکمل ہونے کے بعد الگ سے شائع کریں۔</p></div></div>
                </div>
            </div>
            <div class="sticky-actions d-flex flex-wrap justify-content-between">
                <button class="btn btn-primary px-4" type="submit"><i class="fas fa-save ml-1"></i> معلومات محفوظ کریں</button>
            </div>
        </form>

        @if($storefront->exists)
        <form method="POST" action="{{ route('admin.storefront.publish') }}" class="mt-3">
            @csrf
            @method('PATCH')
            <input type="hidden" name="published" value="{{ $storefront->is_published ? 0 : 1 }}">
            <button class="btn {{ $storefront->is_published ? 'btn-outline-danger' : 'btn-success' }}" type="submit">
                <i class="fas {{ $storefront->is_published ? 'fa-eye-slash' : 'fa-globe-asia' }} ml-1"></i>
                {{ $storefront->is_published ? 'عوامی دکان چھپائیں' : 'عوامی دکان شائع کریں' }}
            </button>
        </form>
        @endif
    </div>
</section>
@endsection
