@extends('main')

@push('styles')
<style>
    .cloth-create-page{--cc-blue:#1769ef;--cc-ink:#14213d;--cc-muted:#6f7f94;--cc-line:#dde6f1;min-height:calc(100vh - 65px);padding:26px 0 54px;background:#f6f8fc;color:var(--cc-ink)}
    .cloth-create-shell{max-width:1120px;margin:auto;padding:0 22px}.cloth-create-breadcrumb{margin-bottom:12px;color:var(--cc-muted);font-size:.85rem}.cloth-create-breadcrumb a{color:inherit}.cloth-create-header{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:20px}.cloth-create-heading{display:flex;align-items:center;gap:14px}.cloth-create-heading-icon{display:grid;place-items:center;flex:0 0 54px;height:54px;border:1px solid var(--cc-line);border-radius:14px;background:#fff;color:var(--cc-blue);font-size:21px;box-shadow:0 6px 20px rgba(29,65,110,.06)}.cloth-create-heading h1{margin:0 0 4px;font-size:1.62rem;font-weight:800}.cloth-create-heading p{margin:0;color:var(--cc-muted)}.cloth-back-link{display:inline-flex;align-items:center;gap:8px;min-height:42px;padding:8px 14px;border:1px solid var(--cc-line);border-radius:9px;background:#fff;color:#52647d!important;font-weight:700}
    .cloth-setup-note{display:flex;align-items:flex-start;gap:13px;margin-bottom:18px;padding:15px 17px;border:1px solid #cfe1ff;border-radius:12px;background:#edf5ff;color:#244b7e}.cloth-setup-note>i{margin-top:5px;color:var(--cc-blue)}.cloth-setup-note strong{display:block;margin-bottom:3px}.cloth-setup-note p{margin:0;font-size:.9rem}.cloth-setup-note a{font-weight:800;text-decoration:underline}.cloth-alert-warning{border-color:#f4d18a;background:#fff8e9;color:#76520a}
    .cloth-form-card{overflow:hidden;border:1px solid var(--cc-line);border-radius:15px;background:#fff;box-shadow:0 8px 28px rgba(28,62,104,.06)}.cloth-form-section{padding:25px 28px;border-bottom:1px solid var(--cc-line)}.cloth-section-heading{display:flex;align-items:flex-start;gap:12px;margin-bottom:20px}.cloth-section-number{display:grid;place-items:center;flex:0 0 34px;height:34px;border-radius:10px;background:#eaf2ff;color:var(--cc-blue);font:800 .92rem Arial,sans-serif}.cloth-section-heading h2{margin:0 0 4px;font-size:1.08rem;font-weight:800}.cloth-section-heading p{margin:0;color:var(--cc-muted);font-size:.86rem}.cloth-field-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}.cloth-field{margin:0}.cloth-field.is-wide{grid-column:1/-1}.cloth-field label{display:flex;align-items:center;justify-content:space-between;margin-bottom:7px;color:#273b57;font-weight:800}.cloth-required{color:#dc3545}.cloth-field .form-control{min-height:47px;border:1px solid #d5dfeb;border-radius:9px;background:#fff;color:var(--cc-ink);box-shadow:none}.cloth-field .form-control:focus{border-color:#70a6ff;box-shadow:0 0 0 3px rgba(23,105,239,.1)}.cloth-field small{display:block;margin-top:6px;color:var(--cc-muted);line-height:1.8}.cloth-error{margin-top:6px;color:#c93643;font-size:.82rem;font-weight:700}.cloth-price-wrap{position:relative}.cloth-price-wrap .form-control{direction:ltr;padding-left:48px;text-align:left}.cloth-price-prefix{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#8795a8;font:700 .8rem Arial,sans-serif}
    .cloth-toolbar-row{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:2px 0 13px}.cloth-toolbar-row p{margin:0;color:var(--cc-muted);font-size:.84rem}.cloth-add-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:39px;padding:7px 13px;border:1px solid #bcd2f7;border-radius:8px;background:#f4f8ff;color:#1769ef;font-weight:800}.cloth-add-btn:hover{background:#eaf2ff}.cloth-entry-list{display:grid;gap:10px}.cloth-entry-empty{padding:22px;border:1px dashed #cbd7e7;border-radius:11px;background:#fafcff;color:#7d8ca0;text-align:center}.cloth-entry-empty i{display:block;margin-bottom:7px;color:#aab8ca;font-size:22px}.cloth-entry{display:grid;grid-template-columns:42px minmax(0,1fr) minmax(0,1fr) 38px;align-items:end;gap:12px;padding:14px;border:1px solid #e2e9f2;border-radius:11px;background:#fbfcfe}.cloth-entry-index{display:grid;place-items:center;width:32px;height:32px;margin-bottom:7px;border-radius:8px;background:#edf3fb;color:#526985;font:800 .82rem Arial,sans-serif}.cloth-entry-field label{display:block;margin-bottom:6px;color:#53647b;font-size:.78rem;font-weight:800}.cloth-entry-field .form-control{min-height:42px;border-color:#d8e1ec;border-radius:8px}.cloth-entry-remove{display:grid;place-items:center;width:38px;height:38px;margin-bottom:2px;border:1px solid #f0cbd0;border-radius:8px;background:#fff6f7;color:#d14350}.cloth-entry-remove:hover{background:#ffebed}.cloth-media-entry{grid-template-columns:42px minmax(0,1.35fr) minmax(190px,.65fr) 38px}.cloth-file-input{height:auto!important;padding:7px!important;direction:ltr;text-align:left;font-family:Arial,sans-serif;font-size:.82rem}
    .cloth-form-actions{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:19px 28px;background:#fbfcfe}.cloth-form-actions small{color:var(--cc-muted)}.cloth-action-buttons{display:flex;gap:10px}.cloth-save-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-width:150px;min-height:45px;border:0;border-radius:9px;background:linear-gradient(135deg,#1769ef,#287fff);color:#fff;font-weight:800;box-shadow:0 8px 20px rgba(23,105,239,.2)}.cloth-save-btn:hover{color:#fff;background:#135fdc}.cloth-cancel-btn{display:inline-flex;align-items:center;justify-content:center;min-height:45px;padding:9px 17px;border:1px solid var(--cc-line);border-radius:9px;background:#fff;color:#58697e!important;font-weight:700}
    @media(max-width:767.98px){.cloth-create-page{padding-top:18px}.cloth-create-shell{padding:0 12px}.cloth-create-header{align-items:stretch;flex-direction:column}.cloth-back-link{align-self:flex-start}.cloth-form-section{padding:20px 16px}.cloth-field-grid{grid-template-columns:1fr}.cloth-field.is-wide{grid-column:auto}.cloth-toolbar-row{align-items:stretch;flex-direction:column}.cloth-add-btn{width:100%}.cloth-entry,.cloth-media-entry{grid-template-columns:34px 1fr 38px}.cloth-entry-index{grid-column:1;grid-row:1}.cloth-entry-field{grid-column:1/-1}.cloth-entry-remove{grid-column:3;grid-row:1}.cloth-form-actions{align-items:stretch;flex-direction:column;padding:16px}.cloth-action-buttons{flex-direction:column}.cloth-save-btn,.cloth-cancel-btn{width:100%}}
</style>
@endpush

@section('content')
<section class="main-content cloth-create-page" dir="rtl">
<div class="cloth-create-shell">
    <div class="cloth-create-breadcrumb"><a href="{{ route('admin.home') }}">ڈیش بورڈ</a><span class="mx-2">‹</span><a href="{{ route('admin.cloth.index') }}">کپڑوں کی فہرست</a><span class="mx-2">‹</span>نیا کپڑا</div>
    <header class="cloth-create-header"><div class="cloth-create-heading"><span class="cloth-create-heading-icon"><i class="fas fa-layer-group"></i></span><div><h1>نیا کپڑا شامل کریں</h1><p>بنیادی معلومات اور ہر رنگ کا دستیاب اسٹاک درج کریں</p></div></div><a href="{{ route('admin.cloth.index') }}" class="cloth-back-link"><i class="fas fa-arrow-right"></i> فہرست پر واپس جائیں</a></header>

    <div class="cloth-setup-note {{ $cloth_types->isEmpty() || $cloth_brands->isEmpty() ? 'cloth-alert-warning' : '' }}"><i class="fas {{ $cloth_types->isEmpty() || $cloth_brands->isEmpty() ? 'fa-exclamation-triangle' : 'fa-info-circle' }}"></i><div><strong>قسم اور برانڈ پہلے سے موجود ہونا ضروری ہے</strong><p><a href="{{ route('admin.clothtype.index') }}">کپڑے کی قسم بنائیں</a> یا <a href="{{ route('admin.clothbrand.index') }}">برانڈ بنائیں</a>۔@if($cloth_types->isEmpty() || $cloth_brands->isEmpty()) مطلوبہ فہرست مکمل کرنے کے بعد یہاں واپس آئیں۔@endif</p></div></div>
    @include('inc.message')

    <form id="clothCreateForm" action="{{ route('admin.cloth.store') }}" method="post" enctype="multipart/form-data" class="cloth-form-card">
        @csrf
        <section class="cloth-form-section">
            <div class="cloth-section-heading"><span class="cloth-section-number">1</span><div><h2>کپڑے کی بنیادی معلومات</h2><p>قسم، برانڈ، رنگ اور فی میٹر قیمت درج کریں</p></div></div>
            <div class="cloth-field-grid">
                <div class="cloth-field"><label for="cloth_type_id">کپڑے کی قسم <span class="cloth-required">*</span></label><select id="cloth_type_id" name="cloth_type_id" class="form-control" required><option value="">قسم منتخب کریں</option>@foreach($cloth_types as $cloth_type)<option value="{{ $cloth_type->id }}" @selected(old('cloth_type_id') == $cloth_type->id)>{{ $cloth_type->name }}</option>@endforeach</select>@error('cloth_type_id')<div class="cloth-error">{{ $message }}</div>@enderror</div>
                <div class="cloth-field"><label for="cloth_brand_id">برانڈ / کمپنی <span class="cloth-required">*</span></label><select id="cloth_brand_id" name="cloth_brand_id" class="form-control" required><option value="">برانڈ منتخب کریں</option>@foreach($cloth_brands as $cloth_brand)<option value="{{ $cloth_brand->id }}" @selected(old('cloth_brand_id') == $cloth_brand->id)>{{ $cloth_brand->name }}</option>@endforeach</select>@error('cloth_brand_id')<div class="cloth-error">{{ $message }}</div>@enderror</div>
                <div class="cloth-field is-wide"><label for="colors">دستیاب رنگ <span class="cloth-required">*</span></label><input id="colors" type="text" name="colors" class="form-control" value="{{ old('colors') }}" placeholder="مثلاً: سفید، نیلا، کالا" required autocomplete="off"><small><i class="fas fa-info-circle ml-1"></i>ہر رنگ کو اردو یا انگریزی کوما سے الگ کریں۔ رنگ لکھنے کے بعد نیچے لمبائی کی قطاریں بنائیں۔</small>@error('colors')<div class="cloth-error">{{ $message }}</div>@enderror</div>
                <div class="cloth-field"><label for="price">فی میٹر قیمت خرید <span class="cloth-required">*</span></label><div class="cloth-price-wrap"><span class="cloth-price-prefix">Rs.</span><input id="price" type="number" name="price" class="form-control" value="{{ old('price') }}" min="0" step="0.01" placeholder="0.00" required inputmode="decimal"></div>@error('price')<div class="cloth-error">{{ $message }}</div>@enderror</div>
                <div class="cloth-field"><label for="sale_price">فی میٹر قیمت فروخت <span class="cloth-required">*</span></label><div class="cloth-price-wrap"><span class="cloth-price-prefix">Rs.</span><input id="sale_price" type="number" name="sale_price" class="form-control" value="{{ old('sale_price') }}" min="0" step="0.01" placeholder="0.00" required inputmode="decimal"></div>@error('sale_price')<div class="cloth-error">{{ $message }}</div>@enderror</div>
            </div>
        </section>

        <section class="cloth-form-section">
            <div class="cloth-section-heading"><span class="cloth-section-number">2</span><div><h2>ہر رنگ کا موجودہ اسٹاک</h2><p>اوپر لکھے گئے ہر رنگ کے لیے دستیاب لمبائی میٹر میں درج کریں</p></div></div>
            <div class="cloth-toolbar-row"><p id="lengthHelp">رنگ درج کریں، پھر قطاریں بنانے کے لیے بٹن دبائیں۔</p><button type="button" id="syncLengths" class="cloth-add-btn"><i class="fas fa-magic"></i> رنگوں کے مطابق لمبائی شامل کریں</button></div>
            @error('length')<div class="cloth-error mb-2">{{ $message }}</div>@enderror @error('length_colors')<div class="cloth-error mb-2">{{ $message }}</div>@enderror
            <div id="lengthUploads" class="cloth-entry-list" data-old-lengths='@json(old("length", []))' data-old-colors='@json(old("length_colors", []))'><div class="cloth-entry-empty"><i class="fas fa-ruler-combined"></i>ابھی کوئی رنگ شامل نہیں کیا گیا</div></div>
        </section>

        <section class="cloth-form-section">
            <div class="cloth-section-heading"><span class="cloth-section-number">3</span><div><h2>تصاویر اور ویڈیوز</h2><p>یہ حصہ اختیاری ہے—ہر فائل کو متعلقہ رنگ کے ساتھ جوڑ سکتے ہیں</p></div></div>
            <div class="cloth-toolbar-row"><p>واضح تصویر گاہک اور عملے کے لیے کپڑا پہچاننا آسان بناتی ہے۔</p><div><button type="button" id="addMoreImages" class="cloth-add-btn ml-2"><i class="fas fa-image"></i> تصویر شامل کریں</button><button type="button" id="addMoreVideos" class="cloth-add-btn"><i class="fas fa-video"></i> ویڈیو شامل کریں</button></div></div>
            @error('images')<div class="cloth-error mb-2">{{ $message }}</div>@enderror @error('videos')<div class="cloth-error mb-2">{{ $message }}</div>@enderror
            <div id="mediaUploads" class="cloth-entry-list"><div class="cloth-entry-empty"><i class="fas fa-photo-video"></i>تصویر یا ویڈیو شامل کرنا ضروری نہیں</div></div>
        </section>

        <footer class="cloth-form-actions"><small><span class="cloth-required">*</span> والی معلومات لازمی ہیں</small><div class="cloth-action-buttons"><a href="{{ route('admin.cloth.index') }}" class="cloth-cancel-btn">منسوخ کریں</a><button type="submit" class="cloth-save-btn"><i class="fas fa-check"></i> کپڑا محفوظ کریں</button></div></footer>
    </form>
</div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const colorsInput = document.getElementById('colors');
    const lengths = document.getElementById('lengthUploads');
    const media = document.getElementById('mediaUploads');
    const lengthHelp = document.getElementById('lengthHelp');
    const escapeHtml = value => String(value).replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[char]));
    const colors = () => [...new Set(colorsInput.value.split(/[,،]/).map(color => color.trim()).filter(Boolean))];
    const emptyState = (icon, text) => `<div class="cloth-entry-empty"><i class="fas ${icon}"></i>${text}</div>`;
    const optionList = () => colors().map(color => `<option value="${escapeHtml(color)}">${escapeHtml(color)}</option>`).join('');

    function syncLengthRows() {
        const current = {};
        lengths.querySelectorAll('.cloth-entry').forEach(row => { current[row.querySelector('[name="length_colors[]"]').value] = row.querySelector('[name="length[]"]').value; });
        const oldLengths = JSON.parse(lengths.dataset.oldLengths || '[]');
        const oldColors = JSON.parse(lengths.dataset.oldColors || '[]');
        oldColors.forEach((color, index) => { if (current[color] === undefined) current[color] = oldLengths[index] || ''; });
        const availableColors = colors();
        lengths.innerHTML = availableColors.length ? '' : emptyState('fa-ruler-combined', 'پہلے اوپر دستیاب رنگ درج کریں');
        availableColors.forEach((color, index) => {
            const row = document.createElement('div'); row.className = 'cloth-entry';
            row.innerHTML = `<span class="cloth-entry-index">${index + 1}</span><div class="cloth-entry-field"><label>رنگ</label><input type="text" class="form-control" value="${escapeHtml(color)}" readonly><input type="hidden" name="length_colors[]" value="${escapeHtml(color)}"></div><div class="cloth-entry-field"><label>دستیاب لمبائی (میٹر) <span class="cloth-required">*</span></label><input type="number" name="length[]" class="form-control" min="0" step="0.01" value="${escapeHtml(current[color] || '')}" placeholder="0.00" required inputmode="decimal"></div><span></span>`;
            lengths.appendChild(row);
        });
        lengthHelp.textContent = availableColors.length ? `${availableColors.length} رنگوں کے لیے لمبائی درج کریں۔` : 'رنگ درج کریں، پھر قطاریں بنانے کے لیے بٹن دبائیں۔';
    }

    function addMediaRow(type) {
        if (!colors().length) { colorsInput.focus(); lengthHelp.textContent = 'تصویر یا ویڈیو سے پہلے کم از کم ایک رنگ درج کریں۔'; return; }
        media.querySelector('.cloth-entry-empty')?.remove();
        const isImage = type === 'image';
        const row = document.createElement('div'); row.className = 'cloth-entry cloth-media-entry';
        row.innerHTML = `<span class="cloth-entry-index">${media.querySelectorAll('.cloth-entry').length + 1}</span><div class="cloth-entry-field"><label>${isImage ? 'تصویر (JPG, PNG)' : 'ویڈیو (MP4, MOV)'}</label><input type="file" class="form-control cloth-file-input" name="${isImage ? 'images[]' : 'videos[]'}" accept="${isImage ? 'image/png,image/jpeg,image/webp' : 'video/mp4,video/quicktime,video/ogg'}" required></div><div class="cloth-entry-field"><label>متعلقہ رنگ</label><select name="${isImage ? 'image_colors[]' : 'video_colors[]'}" class="form-control" required><option value="">رنگ منتخب کریں</option>${optionList()}</select></div><button type="button" class="cloth-entry-remove" title="ہٹائیں" aria-label="فائل ہٹائیں"><i class="fas fa-trash"></i></button>`;
        row.querySelector('.cloth-entry-remove').addEventListener('click', () => { row.remove(); renumberMedia(); });
        media.appendChild(row);
    }

    function renumberMedia() {
        media.querySelectorAll('.cloth-entry-index').forEach((item, index) => item.textContent = index + 1);
        if (!media.querySelector('.cloth-entry')) media.innerHTML = emptyState('fa-photo-video', 'تصویر یا ویڈیو شامل کرنا ضروری نہیں');
    }

    document.getElementById('syncLengths').addEventListener('click', syncLengthRows);
    colorsInput.addEventListener('blur', function () { if (colors().length) syncLengthRows(); });
    document.getElementById('addMoreImages').addEventListener('click', () => addMediaRow('image'));
    document.getElementById('addMoreVideos').addEventListener('click', () => addMediaRow('video'));
    document.getElementById('clothCreateForm').addEventListener('submit', function (event) { if (!lengths.querySelector('[name="length[]"]')) { event.preventDefault(); syncLengthRows(); lengths.scrollIntoView({behavior:'smooth', block:'center'}); } });
    if (colors().length) syncLengthRows();
});
</script>
@endpush
