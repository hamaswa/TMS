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
        .onboarding-step{display:flex;align-items:flex-start;gap:.7rem;padding:.65rem 0;border-bottom:1px solid #edf1f3}
        .onboarding-step:last-child{border-bottom:0}.onboarding-step i{margin-top:.25rem}
        .branding-option{border:1px solid #dbe7e2;border-radius:12px;padding:.8rem;background:#fbfefd}.branding-option label{font-weight:700}
        .brand-color{height:44px;padding:.2rem;cursor:pointer}.advanced-branding{border:1px solid #dbe7e2;border-radius:14px;background:#f8fbfa;padding:1rem}
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
                            <input type="hidden" name="display_name" value="{{ old('display_name', $storefront->display_name) }}">
                            <input type="hidden" name="tagline" value="{{ old('tagline', $storefront->tagline) }}">
                            <input type="hidden" name="description" value="{{ old('description', $storefront->description) }}">
                            <div class="form-row">
                                <div class="form-group col-md-6"><label for="display_name_ur">دکان کا نام — اردو</label><input id="display_name_ur" name="display_name_ur" dir="rtl" class="form-control" maxlength="150" value="{{ old('display_name_ur',$storefront->display_name_ur ?: ($storefront->default_locale === 'ur' ? $storefront->display_name : '')) }}"></div>
                                <div class="form-group col-md-6"><label for="display_name_en">Shop name — English</label><input id="display_name_en" name="display_name_en" dir="ltr" class="form-control text-left" maxlength="150" value="{{ old('display_name_en',$storefront->display_name_en ?: ($storefront->default_locale === 'en' ? $storefront->display_name : '')) }}"></div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6"><label for="tagline_ur">مختصر تعارف — اردو</label><input id="tagline_ur" name="tagline_ur" dir="rtl" class="form-control" maxlength="180" value="{{ old('tagline_ur',$storefront->tagline_ur) }}"></div>
                                <div class="form-group col-md-6"><label for="tagline_en">Short tagline — English</label><input id="tagline_en" name="tagline_en" dir="ltr" class="form-control text-left" maxlength="180" value="{{ old('tagline_en',$storefront->tagline_en) }}"></div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6"><label for="description_ur">کاروبار کی تفصیل — اردو</label><textarea id="description_ur" name="description_ur" dir="rtl" class="form-control" rows="5" maxlength="3000">{{ old('description_ur',$storefront->description_ur) }}</textarea></div>
                                <div class="form-group col-md-6"><label for="description_en">Business description — English</label><textarea id="description_en" name="description_en" dir="ltr" class="form-control text-left" rows="5" maxlength="3000">{{ old('description_en',$storefront->description_en) }}</textarea></div>
                            </div>
                            <div class="form-group"><label for="slug">دکان کا مستقل لنک</label><div class="input-group" dir="ltr"><div class="input-group-prepend"><span class="input-group-text">/shops/</span></div><input id="slug" name="slug" class="form-control" maxlength="100" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" required value="{{ old('slug',$storefront->slug) }}"></div><small class="text-muted">صرف چھوٹے انگریزی حروف، اعداد اور ڈیش</small></div>
                            <div class="form-row">
                                <div class="form-group col-md-6"><label for="logo">لوگو <span class="text-muted">(اختیاری)</span></label><input id="logo" type="file" name="logo" class="form-control-file" accept="image/*">@if($storefront->logo_path)<label class="small mt-2"><input type="checkbox" name="remove_logo" value="1"> موجودہ لوگو ہٹائیں</label>@endif</div>
                                <div class="form-group col-md-6"><label for="cover">فل وڈتھ ہیرو / سرورق تصویر</label><input id="cover" type="file" name="cover" class="form-control-file" accept="image/*"><small class="text-muted d-block">بہتر نتیجہ: 1920×800 یا اس سے بڑی افقی تصویر، زیادہ سے زیادہ 4MB</small>@if($storefront->cover_path)<label class="small mt-2"><input type="checkbox" name="remove_cover" value="1"> موجودہ تصویر ہٹائیں</label>@endif</div>
                            </div>
                        </div>
                    </div>

                    @php
                        $savedNavigationLinks = old('navigation_links', $storefront->navigation_links ?: []);
                        $navigationRows = collect($savedNavigationLinks)->pad(6, [
                            'label_ur' => '', 'label_en' => '', 'url' => '', 'location' => 'both', 'new_tab' => false,
                        ])->take(6)->values();
                    @endphp
                    <div class="card storefront-card mb-4">
                        <div class="card-header"><h2 class="h5 mb-1">صفحۂ اول، برانڈنگ اور مینو</h2><p class="small text-muted mb-0">ہیرو بینر، رنگ، اعلان اور دکان کے مرکزی مینو کو خود ترتیب دیں۔</p></div>
                        <div class="card-body">
                            <input type="hidden" name="design_settings_present" value="1">
                            @if($storefront->logo_url || $storefront->cover_url)
                                <div class="form-row mb-3">
                                    @if($storefront->logo_url)<div class="col-md-3"><small class="text-muted d-block">موجودہ لوگو</small><img src="{{ $storefront->logo_url }}" alt="" style="width:72px;height:72px;object-fit:cover;border-radius:14px"></div>@endif
                                    @if($storefront->cover_url)<div class="col-md-9"><small class="text-muted d-block">موجودہ بینر</small><img src="{{ $storefront->cover_url }}" alt="" style="width:100%;height:110px;object-fit:cover;border-radius:14px"></div>@endif
                                </div>
                            @endif
                            <div class="form-row">
                                <div class="form-group col-md-6"><label for="hero_title_ur">ہیرو عنوان — اردو</label><input id="hero_title_ur" name="hero_title_ur" dir="rtl" class="form-control" maxlength="180" value="{{ old('hero_title_ur',$storefront->hero_title_ur) }}" placeholder="خریدیں، سلائی کرائیں، اعتماد کے ساتھ"></div>
                                <div class="form-group col-md-6"><label for="hero_title_en">Hero heading — English</label><input id="hero_title_en" name="hero_title_en" dir="ltr" class="form-control text-left" maxlength="180" value="{{ old('hero_title_en',$storefront->hero_title_en) }}" placeholder="Fabric and tailoring, made for you"></div>
                                <div class="form-group col-md-6"><label for="hero_text_ur">ہیرو متن — اردو</label><textarea id="hero_text_ur" name="hero_text_ur" dir="rtl" class="form-control" rows="3" maxlength="500">{{ old('hero_text_ur',$storefront->hero_text_ur) }}</textarea></div>
                                <div class="form-group col-md-6"><label for="hero_text_en">Hero text — English</label><textarea id="hero_text_en" name="hero_text_en" dir="ltr" class="form-control text-left" rows="3" maxlength="500">{{ old('hero_text_en',$storefront->hero_text_en) }}</textarea></div>
                                <div class="form-group col-md-6"><label for="announcement_ur">اعلانیہ پٹی — اردو</label><input id="announcement_ur" name="announcement_ur" dir="rtl" class="form-control" maxlength="180" value="{{ old('announcement_ur',$storefront->announcement_ur) }}" placeholder="مثلاً 5,000 روپے سے زائد آرڈر پر مفت ڈیلیوری"></div>
                                <div class="form-group col-md-6"><label for="announcement_en">Announcement bar — English</label><input id="announcement_en" name="announcement_en" dir="ltr" class="form-control text-left" maxlength="180" value="{{ old('announcement_en',$storefront->announcement_en) }}"></div>
                                <div class="form-group col-md-3"><label for="theme_primary_color">بنیادی رنگ</label><input id="theme_primary_color" name="theme_primary_color" type="color" class="form-control brand-color" value="{{ old('theme_primary_color',$storefront->theme_primary_color ?: '#126b4f') }}"></div>
                                <div class="form-group col-md-3"><label for="theme_accent_color">نمایاں رنگ</label><input id="theme_accent_color" name="theme_accent_color" type="color" class="form-control brand-color" value="{{ old('theme_accent_color',$storefront->theme_accent_color ?: '#d98a12') }}"></div>
                                <div class="form-group col-md-3"><label for="theme_background_color">صفحہ پس منظر</label><input id="theme_background_color" name="theme_background_color" type="color" class="form-control brand-color" value="{{ old('theme_background_color',$storefront->theme_background_color ?: '#f5f7f6') }}"></div>
                                <div class="form-group col-md-3"><label for="theme_surface_color">کارڈ / سطح</label><input id="theme_surface_color" name="theme_surface_color" type="color" class="form-control brand-color" value="{{ old('theme_surface_color',$storefront->theme_surface_color ?: '#ffffff') }}"></div>
                                <div class="form-group col-md-3"><label for="theme_text_color">متن کا رنگ</label><input id="theme_text_color" name="theme_text_color" type="color" class="form-control brand-color" value="{{ old('theme_text_color',$storefront->theme_text_color ?: '#17372e') }}"></div>
                            </div>
                            <div class="advanced-branding mb-3">
                                <strong class="d-block mb-1">اعلیٰ برانڈ لے آؤٹ</strong>
                                <p class="small text-muted">فونٹ، ہیرو کی ساخت، کارڈز اور مصنوعات کی کثافت اپنی برانڈ شناخت کے مطابق منتخب کریں۔</p>
                                <div class="form-row">
                                    <div class="form-group col-md-4"><label for="font_style">فونٹ انداز</label><select id="font_style" name="font_style" class="form-control"><option value="modern" @selected(old('font_style',$storefront->font_style ?: 'modern') === 'modern')>جدید</option><option value="classic" @selected(old('font_style',$storefront->font_style) === 'classic')>روایتی / اداریاتی</option><option value="minimal" @selected(old('font_style',$storefront->font_style) === 'minimal')>سادہ</option></select></div>
                                    <div class="form-group col-md-4"><label for="hero_layout">ہیرو لے آؤٹ</label><select id="hero_layout" name="hero_layout" class="form-control"><option value="overlay" @selected(old('hero_layout',$storefront->hero_layout ?: 'overlay') === 'overlay')>تصویر پر متن</option><option value="split" @selected(old('hero_layout',$storefront->hero_layout) === 'split')>متن اور تصویر الگ</option><option value="minimal" @selected(old('hero_layout',$storefront->hero_layout) === 'minimal')>سادہ رنگین ہیرو</option></select></div>
                                    <div class="form-group col-md-4"><label for="hero_alignment">ہیرو متن</label><select id="hero_alignment" name="hero_alignment" class="form-control"><option value="start" @selected(old('hero_alignment',$storefront->hero_alignment ?: 'start') === 'start')>زبان کے آغاز کی سمت</option><option value="center" @selected(old('hero_alignment',$storefront->hero_alignment) === 'center')>درمیان</option></select></div>
                                    <div class="form-group col-md-4"><label for="hero_overlay_strength">تصویر پر رنگ کی شدت</label><input id="hero_overlay_strength" name="hero_overlay_strength" type="range" min="20" max="90" step="5" class="custom-range" value="{{ old('hero_overlay_strength',$storefront->hero_overlay_strength ?? 70) }}"><small class="text-muted">کم: تصویر نمایاں، زیادہ: متن زیادہ واضح</small></div>
                                    <div class="form-group col-md-4"><label for="corner_style">کناروں کا انداز</label><select id="corner_style" name="corner_style" class="form-control"><option value="square" @selected(old('corner_style',$storefront->corner_style) === 'square')>سیدھے</option><option value="soft" @selected(old('corner_style',$storefront->corner_style ?: 'soft') === 'soft')>نرم</option><option value="rounded" @selected(old('corner_style',$storefront->corner_style) === 'rounded')>زیادہ گول</option></select></div>
                                    <div class="form-group col-md-4"><label for="product_columns">ڈیسک ٹاپ مصنوعات فی قطار</label><select id="product_columns" name="product_columns" class="form-control">@foreach([2,3,4] as $columns)<option value="{{ $columns }}" @selected((int)old('product_columns',$storefront->product_columns ?? 3) === $columns)>{{ $columns }}</option>@endforeach</select></div>
                                    <div class="form-group col-md-4"><label for="product_columns_tablet">ٹیبلٹ مصنوعات فی قطار</label><select id="product_columns_tablet" name="product_columns_tablet" class="form-control">@foreach([1,2,3] as $columns)<option value="{{ $columns }}" @selected((int)old('product_columns_tablet',$storefront->product_columns_tablet ?? 2) === $columns)>{{ $columns }}</option>@endforeach</select></div>
                                    <div class="form-group col-md-4"><label for="product_columns_mobile">موبائل مصنوعات فی قطار</label><select id="product_columns_mobile" name="product_columns_mobile" class="form-control">@foreach([1,2] as $columns)<option value="{{ $columns }}" @selected((int)old('product_columns_mobile',$storefront->product_columns_mobile ?? 1) === $columns)>{{ $columns }}</option>@endforeach</select></div>
                                    <div class="form-group col-md-4"><label for="product_image_ratio">پروڈکٹ تصویر کی شکل</label><select id="product_image_ratio" name="product_image_ratio" class="form-control"><option value="portrait" @selected(old('product_image_ratio',$storefront->product_image_ratio ?: 'portrait') === 'portrait')>عمودی</option><option value="square" @selected(old('product_image_ratio',$storefront->product_image_ratio) === 'square')>مربع</option><option value="landscape" @selected(old('product_image_ratio',$storefront->product_image_ratio) === 'landscape')>چوڑی</option><option value="natural" @selected(old('product_image_ratio',$storefront->product_image_ratio) === 'natural')>اصل تناسب</option></select></div>
                                    <div class="form-group col-md-4"><label for="header_layout">ہیڈر میں مینو / تلاش کی ترتیب</label><select id="header_layout" name="header_layout" class="form-control"><option value="menu_first" @selected(old('header_layout',$storefront->header_layout ?: 'menu_first') === 'menu_first')>پہلے مینو، پھر تلاش</option><option value="search_first" @selected(old('header_layout',$storefront->header_layout) === 'search_first')>پہلے تلاش، پھر مینو</option></select></div>
                                    <div class="form-group col-md-4"><label for="hero_height">ہیرو کی اونچائی</label><select id="hero_height" name="hero_height" class="form-control"><option value="compact" @selected(old('hero_height',$storefront->hero_height) === 'compact')>مختصر</option><option value="standard" @selected(old('hero_height',$storefront->hero_height ?: 'standard') === 'standard')>معیاری</option><option value="tall" @selected(old('hero_height',$storefront->hero_height) === 'tall')>بڑی / سنیما انداز</option></select></div>
                                    <div class="form-group col-md-4"><label for="hero_text_animation">متن کی حرکت</label><select id="hero_text_animation" name="hero_text_animation" class="form-control"><option value="none" @selected(old('hero_text_animation',$storefront->hero_text_animation) === 'none')>کوئی نہیں</option><option value="fade" @selected(old('hero_text_animation',$storefront->hero_text_animation) === 'fade')>آہستہ ظاہر</option><option value="fade_up" @selected(old('hero_text_animation',$storefront->hero_text_animation ?: 'fade_up') === 'fade_up')>نیچے سے ظاہر</option><option value="slide" @selected(old('hero_text_animation',$storefront->hero_text_animation) === 'slide')>سائیڈ سے ظاہر</option></select></div>
                                </div>
                                <div class="form-row border-top pt-3">
                                    <div class="form-group col-md-4"><label for="hero_media_type">ہیرو میڈیا</label><select id="hero_media_type" name="hero_media_type" class="form-control"><option value="image" @selected(old('hero_media_type',$storefront->hero_media_type ?: 'image') === 'image')>فل وڈتھ تصویر</option><option value="video" @selected(old('hero_media_type',$storefront->hero_media_type) === 'video')>پس منظر ویڈیو</option><option value="color" @selected(old('hero_media_type',$storefront->hero_media_type) === 'color')>صرف برانڈ رنگ</option></select></div>
                                    <div class="form-group col-md-4"><label for="hero_media_fit">میڈیا فٹنگ</label><select id="hero_media_fit" name="hero_media_fit" class="form-control"><option value="cover" @selected(old('hero_media_fit',$storefront->hero_media_fit ?: 'cover') === 'cover')>پوری جگہ بھریں</option><option value="contain" @selected(old('hero_media_fit',$storefront->hero_media_fit) === 'contain')>پوری تصویر دکھائیں</option></select></div>
                                    <div class="form-group col-md-4"><label for="hero_media_position">میڈیا فوکس</label><select id="hero_media_position" name="hero_media_position" class="form-control"><option value="center" @selected(old('hero_media_position',$storefront->hero_media_position ?: 'center') === 'center')>درمیان</option><option value="top" @selected(old('hero_media_position',$storefront->hero_media_position) === 'top')>اوپر</option><option value="bottom" @selected(old('hero_media_position',$storefront->hero_media_position) === 'bottom')>نیچے</option></select></div>
                                    <div class="form-group col-md-6"><label for="hero_video">ہیرو ویڈیو</label><input id="hero_video" name="hero_video" type="file" class="form-control-file" accept="video/mp4,video/webm,video/quicktime"><small class="text-muted d-block">MP4/WebM/MOV، زیادہ سے زیادہ 50MB۔ ویڈیو خاموش، خودکار اور مسلسل چلے گی۔</small>@if($storefront->hero_video_path)<label class="small mt-2"><input type="checkbox" name="remove_hero_video" value="1"> موجودہ ویڈیو ہٹائیں</label>@endif</div>
                                    <div class="form-group col-md-6"><label for="hero_video_poster">ویڈیو لوڈ ہونے تک تصویر</label><input id="hero_video_poster" name="hero_video_poster" type="file" class="form-control-file" accept="image/*"></div>
                                </div>
                            </div>
                            <div class="border rounded p-3 mb-3">
                                <strong class="d-block mb-2">مرکزی مینو اور صفحہ حصے</strong>
                                <div class="form-row">
                                    <div class="col-md-4 mb-2"><label><input type="checkbox" name="show_nav_categories" value="1" @checked(old('show_nav_categories',$storefront->show_nav_categories ?? true))> اقسام کا مینو</label></div>
                                    <div class="col-md-4 mb-2"><label><input type="checkbox" name="show_nav_brands" value="1" @checked(old('show_nav_brands',$storefront->show_nav_brands ?? true))> برانڈز کا مینو</label></div>
                                    <div class="col-md-4 mb-2"><label><input type="checkbox" name="show_featured_products" value="1" @checked(old('show_featured_products',$storefront->show_featured_products ?? true))> نمایاں مصنوعات</label></div>
                                    <div class="col-md-4 mb-2"><label><input type="checkbox" name="show_benefits" value="1" @checked(old('show_benefits',$storefront->show_benefits ?? true))> سہولتوں کی پٹی</label></div>
                                    <div class="col-md-4 mb-2"><label><input type="checkbox" name="show_services" value="1" @checked(old('show_services',$storefront->show_services ?? true))> خدمات کا حصہ</label></div>
                                    <div class="col-md-4 mb-2"><label><input type="checkbox" name="show_about" value="1" @checked(old('show_about',$storefront->show_about ?? true))> تعارف اور رابطہ</label></div>
                                    <div class="col-md-4 mb-2"><label><input type="checkbox" name="sticky_header" value="1" @checked(old('sticky_header',$storefront->sticky_header ?? true))> سکرول پر ہیڈر سامنے رہے</label></div>
                                    <div class="col-md-4 mb-2"><label><input type="checkbox" name="show_product_brand" value="1" @checked(old('show_product_brand',$storefront->show_product_brand ?? true))> کارڈ پر برانڈ</label></div>
                                    <div class="col-md-4 mb-2"><label><input type="checkbox" name="show_product_category" value="1" @checked(old('show_product_category',$storefront->show_product_category ?? true))> کارڈ پر قسم</label></div>
                                    <div class="col-md-4 mb-2"><label><input type="checkbox" name="show_product_stock" value="1" @checked(old('show_product_stock',$storefront->show_product_stock ?? true))> کارڈ پر دستیاب اسٹاک</label></div>
                                </div>
                                @if($storefront->exists)<a class="btn btn-outline-primary btn-sm mt-2" href="{{ route('admin.storefront.merchandising.index') }}">ہیرو سلائیڈز، کلیکشن اور کسٹم مینو ترتیب دیں</a>@endif
                            </div>
                            <div class="border rounded p-3">
                                <strong class="d-block mb-1">اپنا مینو اور فوٹر روابط <small class="text-muted">(زیادہ سے زیادہ 6)</small></strong>
                                <p class="small text-muted">سائز گائیڈ، ہماری کہانی یا واپسی کی پالیسی۔ محفوظ https:// لنک، /اندرونی-صفحہ یا #صفحہ-حصہ درج کریں۔</p>
                                @foreach($navigationRows as $index => $link)
                                    <div class="form-row border-top pt-2 mt-2">
                                        <div class="form-group col-md-2"><label>اردو نام</label><input name="navigation_links[{{ $index }}][label_ur]" class="form-control" maxlength="50" value="{{ $link['label_ur'] ?? '' }}"></div>
                                        <div class="form-group col-md-2"><label>English</label><input name="navigation_links[{{ $index }}][label_en]" dir="ltr" class="form-control text-left" maxlength="50" value="{{ $link['label_en'] ?? '' }}"></div>
                                        <div class="form-group col-md-4"><label>لنک</label><input name="navigation_links[{{ $index }}][url]" dir="ltr" class="form-control text-left" maxlength="500" value="{{ $link['url'] ?? '' }}"></div>
                                        <div class="form-group col-md-2"><label>کہاں دکھائیں</label><select name="navigation_links[{{ $index }}][location]" class="form-control"><option value="both" @selected(($link['location'] ?? 'both') === 'both')>دونوں</option><option value="header" @selected(($link['location'] ?? '') === 'header')>ہیڈر</option><option value="footer" @selected(($link['location'] ?? '') === 'footer')>فوٹر</option></select></div>
                                        <div class="form-group col-md-2 d-flex align-items-end"><label class="mb-2"><input type="checkbox" name="navigation_links[{{ $index }}][new_tab]" value="1" @checked($link['new_tab'] ?? false)> نئی ونڈو</label></div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="advanced-branding mt-3">
                                <strong class="d-block mb-2">فوٹر اور سوشل روابط</strong>
                                <div class="form-row">
                                    <div class="form-group col-md-4"><label for="footer_style">فوٹر انداز</label><select id="footer_style" name="footer_style" class="form-control"><option value="simple" @selected(old('footer_style',$storefront->footer_style) === 'simple')>سادہ</option><option value="columns" @selected(old('footer_style',$storefront->footer_style ?: 'columns') === 'columns')>کالمز</option><option value="brand" @selected(old('footer_style',$storefront->footer_style) === 'brand')>بڑا برانڈ فوٹر</option></select></div>
                                    <div class="form-group col-md-4"><label for="footer_text_ur">فوٹر متن — اردو</label><input id="footer_text_ur" name="footer_text_ur" class="form-control" maxlength="500" value="{{ old('footer_text_ur',$storefront->footer_text_ur) }}"></div>
                                    <div class="form-group col-md-4"><label for="footer_text_en">Footer text — English</label><input id="footer_text_en" name="footer_text_en" dir="ltr" class="form-control text-left" maxlength="500" value="{{ old('footer_text_en',$storefront->footer_text_en) }}"></div>
                                    @foreach(['facebook_url'=>'Facebook','instagram_url'=>'Instagram','tiktok_url'=>'TikTok','youtube_url'=>'YouTube'] as $field => $label)<div class="form-group col-md-6"><label for="{{ $field }}">{{ $label }}</label><input id="{{ $field }}" name="{{ $field }}" type="url" dir="ltr" class="form-control text-left" maxlength="500" value="{{ old($field,$storefront->{$field}) }}" placeholder="https://"></div>@endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($business->clothing_enabled || $business->tailoring_enabled)
                    <div class="card storefront-card mb-4">
                        <div class="card-header">
                            <h2 class="h5 mb-1">ہر شعبے کی الگ ترتیب</h2>
                            <p class="small text-muted mb-0">آرڈر، درخواست، ادائیگی اور فراہمی متعلقہ شعبے میں الگ کنٹرول کریں۔</p>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @if($business->tailoring_enabled)
                                <div class="col-md-6 mb-2">
                                    @if($storefront->exists)
                                    <a class="module-choice d-flex text-decoration-none h-100" href="{{ route('admin.storefront.module-settings.edit', 'tailoring') }}">
                                        <i class="fas fa-cut text-primary ml-3 mt-1"></i>
                                        <span><strong class="d-block text-dark">ٹیلرنگ کی ترتیب</strong><small class="text-muted">درخواستیں، ادائیگی، پیمائش/وصولی اور فراہمی</small></span>
                                    </a>
                                    @else
                                    <div class="module-choice d-flex h-100 text-muted" aria-disabled="true">
                                        <i class="fas fa-cut ml-3 mt-1"></i>
                                        <span><strong class="d-block">ٹیلرنگ کی ترتیب</strong><small>پہلے بنیادی معلومات محفوظ کریں، پھر ٹیلرنگ کی ترتیب دستیاب ہوگی۔</small></span>
                                    </div>
                                    @endif
                                </div>
                                @endif
                                @if($business->clothing_enabled)
                                <div class="col-md-6 mb-2">
                                    @if($storefront->exists)
                                    <a class="module-choice d-flex text-decoration-none h-100" href="{{ route('admin.storefront.module-settings.edit', 'clothing') }}">
                                        <i class="fas fa-shopping-bag text-success ml-3 mt-1"></i>
                                        <span><strong class="d-block text-dark">کپڑوں کی دکان کی ترتیب</strong><small class="text-muted">کیٹلاگ/آرڈر، ادائیگی، وصولی اور فراہمی</small></span>
                                    </a>
                                    @else
                                    <div class="module-choice d-flex h-100 text-muted" aria-disabled="true">
                                        <i class="fas fa-shopping-bag ml-3 mt-1"></i>
                                        <span><strong class="d-block">کپڑوں کی دکان کی ترتیب</strong><small>پہلے بنیادی معلومات محفوظ کریں، پھر کپڑوں کی دکان کی ترتیب دستیاب ہوگی۔</small></span>
                                    </div>
                                    @endif
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @php
                        $easypaisaSelected = (bool) ($errors->any() ? old('easypaisa_enabled') : old('easypaisa_enabled', $storefront->easypaisa_enabled));
                        $jazzcashSelected = (bool) ($errors->any() ? old('jazzcash_enabled') : old('jazzcash_enabled', $storefront->jazzcash_enabled));
                        $bankTransferSelected = (bool) ($errors->any() ? old('bank_transfer_enabled') : old('bank_transfer_enabled', $storefront->bank_transfer_enabled));
                        $raastSelected = (bool) ($errors->any() ? old('raast_enabled') : old('raast_enabled', $storefront->raast_enabled));
                        $savedPaymentMode = $storefront->unpaid_orders_enabled ? 'none' : 'methods';
                        $paymentCollectionMode = old('payment_collection_mode', $savedPaymentMode);
                        $showPaymentMethods = $paymentCollectionMode === 'methods';
                        $hasReceivingMethod = $easypaisaSelected || $jazzcashSelected || $bankTransferSelected || $raastSelected;
                    @endphp
                    <div class="card storefront-card mb-4 d-none" aria-hidden="true">
                        <div class="card-header">
                            <h2 class="h5 mb-1">آن لائن ادائیگی کے طریقے</h2>
                            <p class="small text-muted mb-0">کپڑوں کے آرڈر اور ٹیلرنگ درخواستوں کے لیے قبول شدہ طریقے الگ الگ کنٹرول کریں۔</p>
                        </div>
                        <div class="card-body">
                            <input type="hidden" name="commerce_settings_present" value="1">
                            @if($business->clothing_enabled)
                            <label class="module-choice d-block mb-3">
                                <div class="d-flex">
                                    <input type="checkbox" name="online_ordering_enabled" value="1" @checked(old('online_ordering_enabled',$storefront->online_ordering_enabled))>
                                    <div>
                                        <strong><i class="fas fa-shopping-cart text-success ml-1"></i> آن لائن آرڈر قبول کریں</strong>
                                        <div class="small text-muted mt-1">اسے بند رکھنے پر کپڑے، قیمت اور دستیابی نظر آئے گی مگر ٹوکری اور چیک آؤٹ نہیں ہوں گے۔</div>
                                    </div>
                                </div>
                            </label>
                            @endif
                            <h3 class="h6 mb-3">کیا آپ آرڈر یا درخواست کے ساتھ ادائیگی کا طریقہ دینا چاہتے ہیں؟</h3>
                            <div class="row d-none" aria-hidden="true">
                                <div class="col-md-6 mb-2">
                                    <label class="module-choice d-flex align-items-start">
                                        <input type="radio" name="payment_collection_mode" value="none" data-payment-mode @checked($paymentCollectionMode === 'none') required>
                                        <span><strong>نہیں — ابھی ادائیگی قبول نہیں کریں گے</strong><small class="d-block text-muted mt-1">گاہک آرڈر یا درخواست بغیر ادائیگی بھیج سکے گا۔</small></span>
                                    </label>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="module-choice d-flex align-items-start">
                                        <input type="radio" name="payment_collection_mode" value="methods" data-payment-mode @checked($paymentCollectionMode === 'methods') required>
                                        <span><strong>ہاں — ادائیگی کے طریقے منتخب کریں</strong><small class="d-block text-muted mt-1">اگلے مرحلے میں COD، والٹ، بینک یا راست منتخب کریں۔</small></span>
                                    </label>
                                </div>
                            </div>
                            <div id="no-payment-note" class="alert alert-light border mt-3 mb-0" {{ $showPaymentMethods ? 'hidden' : '' }}>
                                <i class="fas fa-check-circle text-success ml-1"></i> ادائیگی کے تمام طریقے چھپا دیے گئے ہیں۔ گاہک بغیر آن لائن ادائیگی درخواست یا آرڈر بھیج سکے گا۔
                            </div>
                            <div id="payment-method-options" class="mt-3" {{ ! $showPaymentMethods ? 'hidden' : '' }}>
                                <h3 class="h6">قبول شدہ طریقے منتخب کریں</h3>
                                <div class="row">
                                    <div class="col-md-4 mb-2"><label class="module-choice d-flex align-items-center"><input type="checkbox" name="cod_enabled" value="1" data-payment-option @checked(old('cod_enabled',$storefront->cod_enabled)) @disabled(! $showPaymentMethods)> کیش آن ڈیلیوری</label></div>
                                    <div class="col-md-4 mb-2"><label class="module-choice d-flex align-items-center"><input type="checkbox" name="easypaisa_enabled" value="1" data-payment-option data-payment-method="easypaisa" @checked($easypaisaSelected) @disabled(! $showPaymentMethods)> ایزی پیسہ — دستی تصدیق</label></div>
                                    <div class="col-md-4 mb-2"><label class="module-choice d-flex align-items-center"><input type="checkbox" name="jazzcash_enabled" value="1" data-payment-option data-payment-method="jazzcash" @checked($jazzcashSelected) @disabled(! $showPaymentMethods)> جاز کیش — دستی تصدیق</label></div>
                                    <div class="col-md-4 mb-2"><label class="module-choice d-flex align-items-center"><input type="checkbox" name="bank_transfer_enabled" value="1" data-payment-option data-payment-method="bank" @checked($bankTransferSelected) @disabled(! $showPaymentMethods)> بینک ٹرانسفر — دستی تصدیق</label></div>
                                    <div class="col-md-4 mb-2"><label class="module-choice d-flex align-items-center"><input type="checkbox" name="raast_enabled" value="1" data-payment-option data-payment-method="raast" @checked($raastSelected) @disabled(! $showPaymentMethods)> راست / Raast QR — دستی تصدیق</label></div>
                                </div>
                            </div>
                            <div id="payment-details-empty" class="alert alert-light border mt-3 mb-0" {{ ! $showPaymentMethods || $hasReceivingMethod ? 'hidden' : '' }}>
                                <i class="fas fa-info-circle ml-1"></i> کیش آن ڈیلیوری کے لیے اکاؤنٹ کی تفصیلات درکار نہیں۔ والٹ، بینک یا راست منتخب کرنے پر متعلقہ خانے ظاہر ہوں گے۔
                            </div>
                            <div id="payment-receiving-details" class="border rounded p-3 mt-3" {{ ! $showPaymentMethods || ! $hasReceivingMethod ? 'hidden' : '' }}>
                                <h3 class="h6">عوام کو دکھائی جانے والی وصولی کی معلومات</h3>
                                <p class="small text-muted">صرف فعال ادائیگی کے طریقے کی معلومات چیک آؤٹ پر دکھائی جائیں گی۔ خفیہ PIN، OTP یا پاس ورڈ کبھی درج نہ کریں۔</p>
                                <div class="form-row">
                                    <div class="col-12 payment-detail-panel" data-payment-details-for="easypaisa" {{ ! $easypaisaSelected ? 'hidden' : '' }}><div class="form-row">
                                        <div class="form-group col-md-6"><label for="easypaisa_account_title">ایزی پیسہ اکاؤنٹ عنوان</label><input id="easypaisa_account_title" name="easypaisa_account_title" class="form-control" maxlength="150" value="{{ old('easypaisa_account_title',$storefront->easypaisa_account_title) }}" @disabled(! $easypaisaSelected) required></div>
                                        <div class="form-group col-md-6"><label for="easypaisa_account_number">ایزی پیسہ نمبر</label><input id="easypaisa_account_number" name="easypaisa_account_number" dir="ltr" class="form-control text-left" maxlength="50" value="{{ old('easypaisa_account_number',$storefront->easypaisa_account_number) }}" @disabled(! $easypaisaSelected) required></div>
                                    </div></div>
                                    <div class="col-12 payment-detail-panel" data-payment-details-for="jazzcash" {{ ! $jazzcashSelected ? 'hidden' : '' }}><div class="form-row">
                                        <div class="form-group col-md-6"><label for="jazzcash_account_title">جاز کیش اکاؤنٹ عنوان</label><input id="jazzcash_account_title" name="jazzcash_account_title" class="form-control" maxlength="150" value="{{ old('jazzcash_account_title',$storefront->jazzcash_account_title) }}" @disabled(! $jazzcashSelected) required></div>
                                        <div class="form-group col-md-6"><label for="jazzcash_account_number">جاز کیش نمبر</label><input id="jazzcash_account_number" name="jazzcash_account_number" dir="ltr" class="form-control text-left" maxlength="50" value="{{ old('jazzcash_account_number',$storefront->jazzcash_account_number) }}" @disabled(! $jazzcashSelected) required></div>
                                    </div></div>
                                    <div class="col-12 payment-detail-panel" data-payment-details-for="bank" {{ ! $bankTransferSelected ? 'hidden' : '' }}><div class="form-row">
                                        <div class="form-group col-md-4"><label for="bank_name">بینک کا نام</label><input id="bank_name" name="bank_name" class="form-control" maxlength="150" value="{{ old('bank_name',$storefront->bank_name) }}" @disabled(! $bankTransferSelected) required></div>
                                        <div class="form-group col-md-4"><label for="bank_account_title">بینک اکاؤنٹ عنوان</label><input id="bank_account_title" name="bank_account_title" class="form-control" maxlength="150" value="{{ old('bank_account_title',$storefront->bank_account_title) }}" @disabled(! $bankTransferSelected) required></div>
                                        <div class="form-group col-md-4"><label for="bank_account_number">اکاؤنٹ نمبر</label><input id="bank_account_number" name="bank_account_number" dir="ltr" class="form-control text-left" maxlength="100" value="{{ old('bank_account_number',$storefront->bank_account_number) }}" @disabled(! $bankTransferSelected)></div>
                                        <div class="form-group col-md-12"><label for="bank_iban">IBAN <small class="text-muted">(PK سے شروع ہونے والے 24 حروف)</small></label><input id="bank_iban" name="bank_iban" dir="ltr" class="form-control text-left text-uppercase" maxlength="24" value="{{ old('bank_iban',$storefront->bank_iban) }}" @disabled(! $bankTransferSelected)></div>
                                    </div></div>
                                    <div class="col-12 payment-detail-panel" data-payment-details-for="raast" {{ ! $raastSelected ? 'hidden' : '' }}><div class="form-row">
                                        <div class="form-group col-md-6"><label for="raast_account_title">راست اکاؤنٹ عنوان</label><input id="raast_account_title" name="raast_account_title" class="form-control" maxlength="150" value="{{ old('raast_account_title',$storefront->raast_account_title) }}" @disabled(! $raastSelected)></div>
                                        <div class="form-group col-md-6"><label for="raast_id">راست ID / مرچنٹ Alias</label><input id="raast_id" name="raast_id" dir="ltr" class="form-control text-left" maxlength="100" value="{{ old('raast_id',$storefront->raast_id) }}" @disabled(! $raastSelected)></div>
                                        <div class="form-group col-md-12"><label for="raast_qr">بینک یا والٹ کا جاری کردہ Raast QR <small class="text-muted">(اختیاری، زیادہ سے زیادہ 2 MB)</small></label><input id="raast_qr" type="file" name="raast_qr" class="form-control-file" accept="image/*" @disabled(! $raastSelected)>@if($storefront->raast_qr_url)<img src="{{ $storefront->raast_qr_url }}" alt="موجودہ Raast QR" style="display:block;max-width:180px;max-height:180px;margin-top:10px">@endif</div>
                                    </div></div>
                                </div>
                            </div>
                            <div id="manual-payment-help" class="alert alert-info mb-0 mt-2" {{ ! $showPaymentMethods || ! $hasReceivingMethod ? 'hidden' : '' }}>
                                <strong>اہم:</strong> یہ لائیو گیٹ وے نہیں ہیں۔ گاہک ادائیگی کر کے حوالہ درج کرتا ہے، اور دکان رقم اپنے والٹ یا بینک میں دیکھ کر دستی طور پر تصدیق کرتی ہے۔ صرف اپنے مالی ادارے کا جاری کردہ Raast QR اپ لوڈ کریں۔
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
                                <div class="form-group col-md-6"><label for="whatsapp_number">واٹس ایپ نمبر</label><input id="whatsapp_number" name="whatsapp_number" dir="ltr" class="form-control text-left" maxlength="30" value="{{ old('whatsapp_number',$storefront->whatsapp_number) }}" placeholder="+923001234567"></div>
                            </div>
                            <input type="hidden" name="address" value="{{ old('address', $storefront->address) }}">
                            <input type="hidden" name="city" value="{{ old('city', $storefront->city) }}">
                            <div class="form-row">
                                <div class="form-group col-md-6"><label for="address_ur">پتہ — اردو</label><textarea id="address_ur" name="address_ur" dir="rtl" class="form-control" rows="3" maxlength="1000">{{ old('address_ur',$storefront->address_ur) }}</textarea></div>
                                <div class="form-group col-md-6"><label for="address_en">Address — English</label><textarea id="address_en" name="address_en" dir="ltr" class="form-control text-left" rows="3" maxlength="1000">{{ old('address_en',$storefront->address_en) }}</textarea></div>
                                <div class="form-group col-md-6"><label for="city_ur">شہر — اردو</label><input id="city_ur" name="city_ur" dir="rtl" class="form-control" maxlength="100" value="{{ old('city_ur',$storefront->city_ur) }}"></div>
                                <div class="form-group col-md-6"><label for="city_en">City — English</label><input id="city_en" name="city_en" dir="ltr" class="form-control text-left" maxlength="100" value="{{ old('city_en',$storefront->city_en) }}"></div>
                            </div>
                            <div class="form-group">
                                <label for="default_locale">عوامی دکان کی بنیادی زبان</label>
                                <select id="default_locale" name="default_locale" class="form-control">
                                    <option value="ur" @selected(old('default_locale', $storefront->default_locale ?: 'ur') === 'ur')>اردو — دائیں سے بائیں</option>
                                    <option value="en" @selected(old('default_locale', $storefront->default_locale) === 'en')>English — left to right</option>
                                </select>
                                <small class="text-muted">گاہک زبان تبدیل کر سکتے ہیں؛ ان کا انتخاب اگلی بار بھی محفوظ رہے گا۔</small>
                            </div>
                            <div class="row d-none" aria-hidden="true">
                                <div class="col-md-4 mb-2"><label><input type="checkbox" name="inquiries_enabled" value="1" @checked(old('inquiries_enabled',$storefront->inquiries_enabled))> گاہک کے سوالات قبول کریں</label></div>
                                <div class="col-md-4 mb-2"><label><input type="checkbox" name="pickup_enabled" value="1" @checked(old('pickup_enabled',$storefront->pickup_enabled))> دکان سے وصولی</label></div>
                                <div class="col-md-4 mb-2"><label><input type="checkbox" name="delivery_enabled" value="1" @checked(old('delivery_enabled',$storefront->delivery_enabled))> گھر تک فراہمی</label></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    @php
                        $hasPublicContact = filled($storefront->public_phone) || filled($storefront->public_email) || filled($storefront->address);
                        $hasPublishedContent = ($storefront->published_clothing_listings_count ?? 0) > 0
                            || ($storefront->published_tailoring_services_count ?? 0) > 0;
                        $onboardingSteps = [
                            ['done' => $storefront->exists, 'label' => 'بنیادی معلومات محفوظ کریں'],
                            ['done' => $hasPublicContact, 'label' => 'رابطہ یا پتہ درج کریں'],
                            ['done' => $hasPublishedContent, 'label' => 'کم از کم ایک پروڈکٹ یا خدمت شائع کریں'],
                            ['done' => (bool) $storefront->is_published, 'label' => 'عوامی دکان شائع کریں'],
                        ];
                        $completedOnboardingSteps = collect($onboardingSteps)->where('done', true)->count();
                    @endphp
                    <div class="card storefront-card mb-4">
                        <div class="card-header">
                            <h2 class="h5 mb-1">دکان مکمل کرنے کے مراحل</h2>
                            <p class="small text-muted mb-0">{{ $completedOnboardingSteps }} از {{ count($onboardingSteps) }} مراحل مکمل</p>
                        </div>
                        <div class="card-body py-2">
                            @foreach($onboardingSteps as $step)
                                <div class="onboarding-step">
                                    <i class="fas {{ $step['done'] ? 'fa-check-circle text-success' : 'fa-circle text-muted' }}" aria-hidden="true"></i>
                                    <span class="{{ $step['done'] ? 'text-dark' : 'text-muted' }}">{{ $step['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="card storefront-card mb-4">
                        <div class="card-header"><h2 class="h5 mb-0">عوامی لنک</h2></div>
                        <div class="card-body">
                            <div class="preview-box mb-3" dir="ltr">{{ url('/shops/'.$storefront->slug) }}</div>
                            @if($storefront->exists)<a class="btn btn-outline-primary btn-block mb-2" target="_blank" rel="noopener" href="{{ route('admin.storefront.preview') }}"><i class="fas fa-eye ml-1"></i> پیش منظر دیکھیں</a>@endif
                            @if($storefront->exists && $business->clothing_enabled)<a class="btn btn-outline-dark btn-block mb-2" href="{{ route('admin.storefront.clothing.index') }}"><i class="fas fa-tshirt ml-1"></i> کپڑوں کی عوامی فہرست</a><a class="btn btn-outline-warning btn-block mb-2" href="{{ route('admin.storefront.merchandising.index') }}"><i class="fas fa-layer-group ml-1"></i> کلیکشن اور مینو</a>@endif
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modeInputs = Array.from(document.querySelectorAll('[data-payment-mode]'));
    var optionInputs = Array.from(document.querySelectorAll('[data-payment-option]'));
    var methodInputs = Array.from(document.querySelectorAll('[data-payment-method]'));
    var methodOptions = document.getElementById('payment-method-options');
    var noPaymentNote = document.getElementById('no-payment-note');
    var detailsContainer = document.getElementById('payment-receiving-details');
    var emptyState = document.getElementById('payment-details-empty');
    var manualHelp = document.getElementById('manual-payment-help');

    function refreshPaymentDetails() {
        var methodsEnabled = modeInputs.some(function (input) {
            return input.checked && input.value === 'methods';
        });
        optionInputs.forEach(function (input) { input.disabled = !methodsEnabled; });
        if (methodOptions) methodOptions.hidden = !methodsEnabled;
        if (noPaymentNote) noPaymentNote.hidden = methodsEnabled;

        var anySelected = methodsEnabled && methodInputs.some(function (input) { return input.checked; });
        if (detailsContainer) detailsContainer.hidden = !anySelected;
        if (emptyState) emptyState.hidden = !methodsEnabled || anySelected;
        if (manualHelp) manualHelp.hidden = !anySelected;

        document.querySelectorAll('[data-payment-details-for]').forEach(function (panel) {
            var input = methodInputs.find(function (method) {
                return method.dataset.paymentMethod === panel.dataset.paymentDetailsFor;
            });
            var selected = Boolean(methodsEnabled && input && input.checked);
            panel.hidden = !selected;
            panel.querySelectorAll('input, select, textarea').forEach(function (field) {
                field.disabled = !selected;
            });
        });
    }

    methodInputs.forEach(function (input) {
        input.addEventListener('change', refreshPaymentDetails);
    });
    modeInputs.forEach(function (input) {
        input.addEventListener('change', refreshPaymentDetails);
    });
    refreshPaymentDetails();
});
</script>
@endsection
