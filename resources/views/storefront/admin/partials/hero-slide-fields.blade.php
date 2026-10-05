@php($slide = $heroSlide ?? null)
<div class="form-row">
    <div class="form-group col-md-6"><label>عنوان — اردو</label><input name="title_ur" class="form-control" maxlength="180" value="{{ old('title_ur',$slide?->title_ur) }}" required-without="title_en"></div>
    <div class="form-group col-md-6"><label>Heading — English</label><input name="title_en" dir="ltr" class="form-control text-left" maxlength="180" value="{{ old('title_en',$slide?->title_en) }}"></div>
    <div class="form-group col-md-6"><label>متن — اردو</label><textarea name="text_ur" class="form-control" rows="2" maxlength="700">{{ old('text_ur',$slide?->text_ur) }}</textarea></div>
    <div class="form-group col-md-6"><label>Text — English</label><textarea name="text_en" dir="ltr" class="form-control text-left" rows="2" maxlength="700">{{ old('text_en',$slide?->text_en) }}</textarea></div>
</div>
<div class="form-row">
    <div class="form-group col-md-3"><label>میڈیا</label><select name="media_type" class="form-control"><option value="image" @selected(old('media_type',$slide?->media_type ?? 'image')==='image')>تصویر</option><option value="video" @selected(old('media_type',$slide?->media_type)==='video')>ویڈیو</option><option value="color" @selected(old('media_type',$slide?->media_type)==='color')>برانڈ رنگ</option></select></div>
    <div class="form-group col-md-3"><label>متن کی جگہ</label><select name="alignment" class="form-control"><option value="start" @selected(old('alignment',$slide?->alignment ?? 'start')==='start')>زبان کے آغاز کی سمت</option><option value="center" @selected(old('alignment',$slide?->alignment)==='center')>درمیان</option></select></div>
    <div class="form-group col-md-3"><label>اوورلے</label><input name="overlay_strength" type="range" min="20" max="90" step="5" class="custom-range" value="{{ old('overlay_strength',$slide?->overlay_strength ?? 65) }}"></div>
    <div class="form-group col-md-3"><label>ترتیب</label><input name="sort_order" type="number" min="0" max="9999" class="form-control" value="{{ old('sort_order',$slide?->sort_order ?? 0) }}"></div>
    <div class="form-group col-md-3"><label>ڈیسک ٹاپ تصویر</label><input name="image" type="file" class="form-control-file" accept="image/*">@if($slide?->image_url)<a class="small" target="_blank" href="{{ $slide->image_url }}">موجودہ تصویر</a> <label class="small"><input type="checkbox" name="remove_image" value="1"> ہٹائیں</label>@endif</div>
    <div class="form-group col-md-3"><label>موبائل تصویر</label><input name="mobile_image" type="file" class="form-control-file" accept="image/*">@if($slide?->mobile_image_url)<a class="small" target="_blank" href="{{ $slide->mobile_image_url }}">موجودہ تصویر</a> <label class="small"><input type="checkbox" name="remove_mobile_image" value="1"> ہٹائیں</label>@endif</div>
    <div class="form-group col-md-3"><label>ویڈیو</label><input name="video" type="file" class="form-control-file" accept="video/mp4,video/webm,video/quicktime">@if($slide?->video_url)<span class="small text-success">ویڈیو محفوظ ہے</span> <label class="small"><input type="checkbox" name="remove_video" value="1"> ہٹائیں</label>@endif</div>
    <div class="form-group col-md-3"><label>ویڈیو پوسٹر</label><input name="video_poster" type="file" class="form-control-file" accept="image/*">@if($slide?->video_poster_url)<a class="small" target="_blank" href="{{ $slide->video_poster_url }}">موجودہ پوسٹر</a> <label class="small"><input type="checkbox" name="remove_video_poster" value="1"> ہٹائیں</label>@endif</div>
</div>
@foreach(['primary'=>'بنیادی بٹن','secondary'=>'دوسرا بٹن'] as $button => $buttonLabel)
<div class="form-row border-top pt-2">
    <div class="form-group col-md-2"><label>{{ $buttonLabel }} — اردو</label><input name="{{ $button }}_label_ur" class="form-control" maxlength="80" value="{{ old($button.'_label_ur',$slide?->{$button.'_label_ur'}) }}"></div>
    <div class="form-group col-md-2"><label>English label</label><input name="{{ $button }}_label_en" dir="ltr" class="form-control text-left" maxlength="80" value="{{ old($button.'_label_en',$slide?->{$button.'_label_en'}) }}"></div>
    <div class="form-group col-md-2"><label>لنک کی قسم</label><select name="{{ $button }}_link_type" class="form-control">@foreach(['none'=>'بٹن نہیں','catalog'=>'تمام مصنوعات','tailoring'=>'ٹیلرنگ','collection'=>'کلیکشن','contact'=>'رابطہ','custom'=>'اپنا لنک'] as $value=>$label)<option value="{{ $value }}" @selected(old($button.'_link_type',$slide?->{$button.'_link_type'} ?? 'none')===$value)>{{ $label }}</option>@endforeach</select></div>
    <div class="form-group col-md-3"><label>کلیکشن</label><select name="{{ $button }}_collection_id" class="form-control"><option value="">—</option>@foreach($collections as $collection)<option value="{{ $collection->id }}" @selected((int)old($button.'_collection_id',$slide?->{$button.'_collection_id'})===$collection->id)>{{ $collection->name_ur ?: $collection->name_en }}</option>@endforeach</select></div>
    <div class="form-group col-md-3"><label>اپنا URL</label><input name="{{ $button }}_url" dir="ltr" class="form-control text-left" maxlength="1000" value="{{ old($button.'_url',$slide?->{$button.'_url'}) }}" placeholder="https:// یا /page یا #section"></div>
</div>
@endforeach
<div class="form-row align-items-end">
    <div class="form-group col-md-3"><label>شروع (اختیاری)</label><input name="starts_at" type="datetime-local" class="form-control" value="{{ old('starts_at',$slide?->starts_at?->format('Y-m-d\TH:i')) }}"></div>
    <div class="form-group col-md-3"><label>ختم (اختیاری)</label><input name="ends_at" type="datetime-local" class="form-control" value="{{ old('ends_at',$slide?->ends_at?->format('Y-m-d\TH:i')) }}"></div>
    <div class="form-group col-md-3"><label><input name="is_active" type="checkbox" value="1" @checked(old('is_active',$slide?->is_active ?? true))> فعال سلائیڈ</label></div>
    <div class="form-group col-md-3"><button class="btn btn-primary btn-block">محفوظ کریں</button></div>
</div>
