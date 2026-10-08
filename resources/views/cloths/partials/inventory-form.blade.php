@php
    $isEdit = (bool) ($isEdit ?? false);
    $cloth = $cloth ?? null;
    $formAction = $isEdit ? route('admin.cloth.update', $cloth->id) : route('admin.cloth.store');
    $currentMode = old('stock_mode', $cloth?->tracksColors() ? 'per_color' : 'shared');
    $sharedLength = old('shared_length', $isEdit && ! $cloth->tracksColors() ? optional($cloth->colors->first())->length : 0);
    $initialRows = [];
    if (old('color_names') !== null) {
        foreach (old('color_names', []) as $index => $name) {
            $initialRows[] = [
                'id' => old("color_ids.$index"),
                'original' => old("original_color_names.$index", $name),
                'name' => $name,
                'hex' => old("color_hexes.$index", '#808080'),
                'length' => old("color_lengths.$index", 0),
                'image' => null,
            ];
        }
    } elseif ($isEdit && $cloth->tracksColors()) {
        foreach ($cloth->colors as $color) {
            $initialRows[] = [
                'id' => $color->id,
                'original' => $color->color,
                'name' => $color->color,
                'hex' => $color->color_hex ?: '#808080',
                'length' => $color->length,
                'image' => optional($cloth->images->firstWhere('image_color', $color->color))->images,
            ];
        }
    } elseif ($isEdit && $cloth->usesDisplayOnlyColors()) {
        foreach ($cloth->selectableColorNames() as $name) {
            $initialRows[] = [
                'id' => null,
                'original' => $name,
                'name' => $name,
                'hex' => $cloth->display_color_codes[$name] ?? '#808080',
                'length' => 0,
                'image' => optional($cloth->images->firstWhere('image_color', $name))->images,
            ];
        }
    }
@endphp

<style>
    .cloth-create-page{--cc-blue:#1769ef;--cc-ink:#14213d;--cc-muted:#6f7f94;--cc-line:#dde6f1;min-height:calc(100vh - 65px);padding:26px 0 54px;background:#f6f8fc;color:var(--cc-ink)}
    .cloth-create-shell{max-width:1120px;margin:auto;padding:0 22px}.cloth-create-breadcrumb{margin-bottom:12px;color:var(--cc-muted);font-size:.85rem}.cloth-create-breadcrumb a{color:inherit}.cloth-create-header{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:20px}.cloth-create-heading{display:flex;align-items:center;gap:14px}.cloth-create-heading-icon{display:grid;place-items:center;flex:0 0 54px;height:54px;border:1px solid var(--cc-line);border-radius:14px;background:#fff;color:var(--cc-blue);font-size:21px;box-shadow:0 6px 20px rgba(29,65,110,.06)}.cloth-create-heading h1{margin:0 0 4px;font-size:1.62rem;font-weight:800}.cloth-create-heading p{margin:0;color:var(--cc-muted)}.cloth-back-link{display:inline-flex;align-items:center;gap:8px;min-height:42px;padding:8px 14px;border:1px solid var(--cc-line);border-radius:9px;background:#fff;color:#52647d!important;font-weight:700}
    .cloth-setup-note{display:flex;align-items:flex-start;gap:13px;margin-bottom:18px;padding:15px 17px;border:1px solid #cfe1ff;border-radius:12px;background:#edf5ff;color:#244b7e}.cloth-setup-note>i{margin-top:5px;color:var(--cc-blue)}.cloth-setup-note strong{display:block;margin-bottom:3px}.cloth-setup-note p{margin:0;font-size:.9rem}.cloth-setup-note a{font-weight:800;text-decoration:underline}.cloth-alert-warning{border-color:#f4d18a;background:#fff8e9;color:#76520a}
    .cloth-form-card{overflow:hidden;border:1px solid var(--cc-line);border-radius:15px;background:#fff;box-shadow:0 8px 28px rgba(28,62,104,.06)}.cloth-form-section{padding:25px 28px;border-bottom:1px solid var(--cc-line)}.cloth-section-heading{display:flex;align-items:flex-start;gap:12px;margin-bottom:20px}.cloth-section-number{display:grid;place-items:center;flex:0 0 34px;height:34px;border-radius:10px;background:#eaf2ff;color:var(--cc-blue);font:800 .92rem Arial,sans-serif}.cloth-section-heading h2{margin:0 0 4px;font-size:1.08rem;font-weight:800}.cloth-section-heading p{margin:0;color:var(--cc-muted);font-size:.86rem}.cloth-field-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}.cloth-field{margin:0}.cloth-field.is-wide{grid-column:1/-1}.cloth-field label{display:flex;align-items:center;justify-content:space-between;margin-bottom:7px;color:#273b57;font-weight:800}.cloth-required{color:#dc3545}.cloth-field .form-control{min-height:47px;border:1px solid #d5dfeb;border-radius:9px;background:#fff;color:var(--cc-ink);box-shadow:none}.cloth-field .form-control:focus{border-color:#70a6ff;box-shadow:0 0 0 3px rgba(23,105,239,.1)}.cloth-field small{display:block;margin-top:6px;color:var(--cc-muted);line-height:1.8}.cloth-error{margin-top:6px;color:#c93643;font-size:.82rem;font-weight:700}.cloth-price-wrap{position:relative}.cloth-price-wrap .form-control{direction:ltr;padding-left:48px;text-align:left}.cloth-price-prefix{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#8795a8;font:700 .8rem Arial,sans-serif}
    .cloth-stock-options{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:18px}.cloth-stock-option{position:relative;display:block;margin:0;padding:15px 44px 15px 16px;border:2px solid var(--cc-line);border-radius:12px;background:#fff;cursor:pointer}.cloth-stock-option:has(input:checked){border-color:var(--cc-blue);background:#f3f7ff}.cloth-stock-option input{position:absolute;right:16px;top:20px}.cloth-stock-option strong,.cloth-stock-option span{display:block}.cloth-stock-option span{margin-top:3px;color:var(--cc-muted);font-size:.82rem}.cloth-color-toolbar{display:flex;align-items:end;justify-content:space-between;gap:18px;margin-bottom:12px}.cloth-shared-stock{width:min(320px,100%)}.cloth-add-color{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:42px;padding:8px 15px;border:1px solid #bcd2f7;border-radius:8px;background:#f4f8ff;color:#1769ef;font-weight:800}.cloth-color-table-wrap{overflow-x:auto;border:1px solid var(--cc-line);border-radius:12px}.cloth-color-table{width:100%;min-width:830px;border-collapse:collapse}.cloth-color-table th{padding:11px 9px;background:#f4f7fb;color:#52647d;font-size:.8rem;text-align:right;white-space:nowrap}.cloth-color-table td{padding:10px 8px;border-top:1px solid #e7edf4;vertical-align:middle}.cloth-color-table .form-control{min-height:42px;border-color:#d8e1ec;border-radius:8px}.cloth-color-number{width:34px;color:#718096;text-align:center;font:700 .85rem Arial}.cloth-color-swatch{display:flex;align-items:center;gap:7px;direction:ltr}.cloth-color-swatch input[type=color]{flex:0 0 42px;width:42px;height:42px;padding:3px;border:1px solid #d8e1ec;border-radius:8px;background:#fff}.cloth-color-swatch input[type=text]{width:92px;text-transform:uppercase}.cloth-color-image{display:flex;align-items:center;gap:8px;min-width:190px}.cloth-color-image input{max-width:155px;font-size:.76rem}.cloth-current-image{width:40px;height:40px;border:1px solid #dce4ee;border-radius:7px;object-fit:cover}.cloth-remove-color{display:grid;place-items:center;width:38px;height:38px;border:1px solid #f0cbd0;border-radius:8px;background:#fff6f7;color:#d14350}.cloth-color-empty{padding:26px!important;color:#7d8ca0;text-align:center}.cloth-grid-help{margin:10px 0 0;color:var(--cc-muted);font-size:.82rem}
    .cloth-availability-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.cloth-availability-option{position:relative;display:block;margin:0;padding:17px 18px 17px 50px;border:2px solid var(--cc-line);border-radius:13px;background:#fff;cursor:pointer}.cloth-availability-option:has(input:checked){border-color:var(--cc-blue);background:#f3f7ff}.cloth-availability-option input{position:absolute;left:18px;top:20px}.cloth-availability-option strong,.cloth-availability-option span{display:block}.cloth-availability-option span{margin-top:4px;color:var(--cc-muted);font-size:.84rem;line-height:1.7}.cloth-availability-option.is-disabled{cursor:not-allowed;opacity:.58}.cloth-form-actions{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:19px 28px;background:#fbfcfe}.cloth-form-actions small{color:var(--cc-muted)}.cloth-action-buttons{display:flex;gap:10px}.cloth-save-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-width:150px;min-height:45px;border:0;border-radius:9px;background:linear-gradient(135deg,#1769ef,#287fff);color:#fff;font-weight:800;box-shadow:0 8px 20px rgba(23,105,239,.2)}.cloth-cancel-btn{display:inline-flex;align-items:center;justify-content:center;min-height:45px;padding:9px 17px;border:1px solid var(--cc-line);border-radius:9px;background:#fff;color:#58697e!important;font-weight:700}
    @media(max-width:767.98px){.cloth-create-shell{padding:0 12px}.cloth-create-header{align-items:stretch;flex-direction:column}.cloth-back-link{align-self:flex-start}.cloth-form-section{padding:20px 16px}.cloth-field-grid,.cloth-stock-options,.cloth-availability-grid{grid-template-columns:1fr}.cloth-field.is-wide{grid-column:auto}.cloth-color-toolbar{align-items:stretch;flex-direction:column}.cloth-add-color{width:100%}.cloth-form-actions{align-items:stretch;flex-direction:column;padding:16px}.cloth-action-buttons{flex-direction:column}.cloth-save-btn,.cloth-cancel-btn{width:100%}}
</style>

<section class="main-content cloth-create-page" dir="rtl">
<div class="cloth-create-shell">
    <div class="cloth-create-breadcrumb"><a href="{{ route('admin.home') }}">ڈیش بورڈ</a><span class="mx-2">‹</span><a href="{{ route('admin.cloth.index') }}">کپڑوں کی فہرست</a><span class="mx-2">‹</span>{{ $isEdit ? 'کپڑے میں ترمیم' : 'نیا کپڑا' }}</div>
    <header class="cloth-create-header"><div class="cloth-create-heading"><span class="cloth-create-heading-icon"><i class="fas fa-layer-group"></i></span><div><h1>{{ $isEdit ? 'کپڑا اپ ڈیٹ کریں' : 'نیا کپڑا شامل کریں' }}</h1><p>پہلے سیٹ کی معلومات درج کریں، پھر ضرورت کے مطابق رنگوں کی قطاریں شامل کریں۔</p></div></div><a href="{{ route('admin.cloth.index') }}" class="cloth-back-link"><i class="fas fa-arrow-right"></i> فہرست پر واپس جائیں</a></header>
    @unless($isEdit)<div class="cloth-setup-note {{ $cloth_types->isEmpty() || $cloth_brands->isEmpty() ? 'cloth-alert-warning' : '' }}"><i class="fas fa-info-circle"></i><div><strong>قسم اور برانڈ پہلے سے موجود ہونا ضروری ہے</strong><p><a href="{{ route('admin.clothtype.index') }}">کپڑے کی قسم بنائیں</a> یا <a href="{{ route('admin.clothbrand.index') }}">برانڈ بنائیں</a>۔</p></div></div>@endunless
    @include('inc.message')
    <form id="clothInventoryForm" action="{{ $formAction }}" method="post" enctype="multipart/form-data" class="cloth-form-card">
        @csrf @if($isEdit) @method('put') @endif
        <input type="hidden" name="inventory_grid" value="1">
        <section class="cloth-form-section">
            <div class="cloth-section-heading"><span class="cloth-section-number">1</span><div><h2>سیٹ کی بنیادی معلومات</h2><p>نام، برانڈ، قسم اور قیمت ایک مرتبہ درج کریں</p></div></div>
            <div class="cloth-field-grid">
                <div class="cloth-field is-wide"><label for="name">سیٹ کا نام <span class="cloth-required">*</span></label><input id="name" type="text" name="name" class="form-control" value="{{ old('name', $cloth?->name) }}" maxlength="100" placeholder="مثلاً Dilbar، Waqar یا Premium Gold" required>@error('name')<div class="cloth-error">{{ $message }}</div>@enderror</div>
                <div class="cloth-field"><label for="cloth_type_id">کپڑے کی قسم <span class="cloth-required">*</span></label><select id="cloth_type_id" name="cloth_type_id" class="form-control" required><option value="">قسم منتخب کریں</option>@foreach($cloth_types as $type)<option value="{{ $type->id }}" @selected(old('cloth_type_id', $cloth?->cloth_type_id) == $type->id)>{{ $type->name }}</option>@endforeach</select></div>
                <div class="cloth-field"><label for="cloth_brand_id">برانڈ / کمپنی <span class="cloth-required">*</span></label><select id="cloth_brand_id" name="cloth_brand_id" class="form-control" required><option value="">برانڈ منتخب کریں</option>@foreach($cloth_brands as $brand)<option value="{{ $brand->id }}" @selected(old('cloth_brand_id', $cloth?->cloth_brand_id) == $brand->id)>{{ $brand->name }}</option>@endforeach</select></div>
                <div class="cloth-field"><label for="price">فی میٹر قیمت خرید <span class="cloth-required">*</span></label><div class="cloth-price-wrap"><span class="cloth-price-prefix">Rs.</span><input id="price" type="number" name="price" class="form-control" value="{{ old('price', $cloth?->price) }}" min="0" step="0.01" required></div></div>
                <div class="cloth-field"><label for="sale_price_basis">فروخت کے ریٹ کی بنیاد <span class="cloth-required">*</span></label><select id="sale_price_basis" name="sale_price_basis" class="form-control" required><option value="per_meter" @selected(old('sale_price_basis', $cloth?->sale_price_basis ?? 'per_meter') === 'per_meter')>فی میٹر</option><option value="per_suit" @selected(old('sale_price_basis', $cloth?->sale_price_basis) === 'per_suit')>فی سوٹ</option></select></div>
                <div class="cloth-field"><label for="default_sale_length">ڈیفالٹ سوٹ لمبائی (میٹر)</label><input id="default_sale_length" type="number" name="default_sale_length" class="form-control" value="{{ old('default_sale_length', $cloth?->default_sale_length) }}" min="0.01" step="0.01" placeholder="مثلاً 4 یا 4.5"><small>اسکین یا فروخت پر خود بھرے گی اور بعد میں تبدیل کی جا سکتی ہے۔</small></div>
                <div class="cloth-field"><label for="sale_price">فی میٹر قیمت فروخت <span class="cloth-required">*</span></label><div class="cloth-price-wrap"><span class="cloth-price-prefix">Rs.</span><input id="sale_price" type="number" name="sale_price" class="form-control" value="{{ old('sale_price', $cloth?->sale_price) }}" min="0" step="0.01" required></div></div>
                <div id="suitSalePriceField" class="cloth-field"><label for="suit_sale_price">فی سوٹ قیمت فروخت <span class="cloth-required">*</span></label><div class="cloth-price-wrap"><span class="cloth-price-prefix">Rs.</span><input id="suit_sale_price" type="number" name="suit_sale_price" class="form-control" value="{{ old('suit_sale_price', $cloth?->suit_sale_price) }}" min="0" step="0.01"></div></div>
            </div>
        </section>
        <section class="cloth-form-section">
            <div class="cloth-section-heading"><span class="cloth-section-number">2</span><div><h2>رنگ اور موجودہ اسٹاک</h2><p>رنگ نہ ہوں تو جدول خالی چھوڑ دیں؛ رنگ ہوں تو ہر رنگ ایک قطار میں شامل کریں</p></div></div>
            <div class="cloth-stock-options">
                <label class="cloth-stock-option"><input type="radio" name="stock_mode" value="shared" @checked($currentMode === 'shared')><strong>رنگوں کا مشترکہ اسٹاک</strong><span>تمام رنگ ایک ہی مجموعی میٹر مقدار استعمال کریں گے</span></label>
                <label class="cloth-stock-option"><input type="radio" name="stock_mode" value="per_color" @checked($currentMode === 'per_color')><strong>ہر رنگ کا الگ اسٹاک</strong><span>ہر رنگ کی اپنی دستیاب میٹر مقدار ہوگی</span></label>
            </div>
            @error('stock_mode')<div class="cloth-error mb-2">{{ $message }}</div>@enderror @error('color_names')<div class="cloth-error mb-2">{{ $message }}</div>@enderror @error('color_lengths')<div class="cloth-error mb-2">{{ $message }}</div>@enderror
            <div class="cloth-color-toolbar"><div id="sharedStockField" class="cloth-field cloth-shared-stock"><label for="shared_length">کل دستیاب لمبائی (میٹر)</label><input id="shared_length" type="number" name="shared_length" class="form-control" value="{{ $sharedLength }}" min="0" step="0.01"></div><button type="button" id="addColorRow" class="cloth-add-color"><i class="fas fa-plus"></i> رنگ شامل کریں</button></div>
            <div class="cloth-color-table-wrap"><table class="cloth-color-table"><thead><tr><th>#</th><th>رنگ کا نام</th><th>رنگ / کوڈ</th><th class="per-color-column">دستیاب میٹر</th><th>تصویر (اختیاری)</th><th>عمل</th></tr></thead><tbody id="colorRows"></tbody></table></div>
            <p class="cloth-grid-help"><i class="fas fa-info-circle ml-1"></i> تصویر، رنگ کوڈ اور پیلیٹ اختیاری ہیں۔ رنگ کا نام قطار میں ضروری ہے۔</p>
        </section>
        <section class="cloth-form-section">
            <div class="cloth-section-heading"><span class="cloth-section-number">3</span><div><h2>فروخت کی دستیابی</h2><p>صرف دکان یا دکان کے ساتھ آن لائن فروخت منتخب کریں</p></div></div>
            <div class="cloth-availability-grid">
                <label class="cloth-availability-option {{ $isEdit && !$canConfigureOnline ? 'is-disabled' : '' }}"><input type="radio" name="online_availability" value="pos_only" @checked(old('online_availability', $isEdit ? $onlineAvailability : 'pos_only') === 'pos_only') @disabled($isEdit && !$canConfigureOnline)><strong><i class="fas fa-cash-register ml-1"></i> صرف POS / دکان کی فروخت</strong><span>کاؤنٹر اور سیلز ایجنٹ ایپ میں دستیاب ہوگا۔</span></label>
                <label class="cloth-availability-option {{ !$canConfigureOnline ? 'is-disabled' : '' }}"><input type="radio" name="online_availability" value="online_order" @checked(old('online_availability', $isEdit ? $onlineAvailability : 'pos_only') === 'online_order') @disabled(!$canConfigureOnline)><strong><i class="fas fa-globe ml-1"></i> POS اور آن لائن آرڈر</strong><span>عوامی کیٹلاگ میں بھی فروخت کے لیے دستیاب ہوگا۔</span></label>
            </div>
            @if($isEdit && !$canConfigureOnline)<input type="hidden" name="online_availability" value="{{ $onlineAvailability }}">@endif
        </section>
        <footer class="cloth-form-actions"><small><span class="cloth-required">*</span> والی معلومات لازمی ہیں</small><div class="cloth-action-buttons"><a href="{{ route('admin.cloth.index') }}" class="cloth-cancel-btn">منسوخ کریں</a><button type="submit" class="cloth-save-btn"><i class="fas fa-check"></i> {{ $isEdit ? 'تبدیلیاں محفوظ کریں' : 'کپڑا محفوظ کریں' }}</button></div></footer>
    </form>
</div>
</section>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const initialRows = @json($initialRows);
    const rows = document.getElementById('colorRows');
    const sharedField = document.getElementById('sharedStockField');
    const basis = document.getElementById('sale_price_basis');
    const suitField = document.getElementById('suitSalePriceField');
    const suitPrice = document.getElementById('suit_sale_price');
    const defaultLength = document.getElementById('default_sale_length');
    const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[char]));
    const stockMode = () => document.querySelector('[name="stock_mode"]:checked').value;

    function emptyRow(){return '<tr class="cloth-empty-row"><td colspan="6" class="cloth-color-empty"><i class="fas fa-palette ml-1"></i> ابھی کوئی رنگ شامل نہیں — ضرورت ہو تو “رنگ شامل کریں” دبائیں</td></tr>'}
    function rowMarkup(item = {}) {
        const index = rows.querySelectorAll('tr[data-color-row]').length;
        const image = item.image ? `<img class="cloth-current-image" src="{{ asset('storage') }}/${escapeHtml(item.image)}" alt="">` : '';
        return `<tr data-color-row><td class="cloth-color-number">${index + 1}</td><td><input type="hidden" name="color_ids[]" value="${escapeHtml(item.id)}"><input type="hidden" name="original_color_names[]" value="${escapeHtml(item.original || item.name)}"><input type="text" name="color_names[]" class="form-control" value="${escapeHtml(item.name)}" placeholder="مثلاً گلابی" required></td><td><div class="cloth-color-swatch"><input type="color" class="color-picker" value="${escapeHtml(item.hex || '#808080')}" aria-label="پیلیٹ سے رنگ منتخب کریں"><input type="text" name="color_hexes[]" class="form-control color-hex" value="${escapeHtml(item.hex || '#808080')}" pattern="#[0-9A-Fa-f]{6}" maxlength="7" aria-label="رنگ کوڈ"></div></td><td class="per-color-column"><input type="number" name="color_lengths[]" class="form-control color-length" value="${escapeHtml(item.length || 0)}" min="0" step="0.01"></td><td><div class="cloth-color-image">${image}<input type="file" name="color_images[${index}]" accept="image/png,image/jpeg,image/webp"></div></td><td><button type="button" class="cloth-remove-color" title="رنگ ہٹائیں"><i class="fas fa-trash"></i></button></td></tr>`;
    }
    function bindRow(row){const picker=row.querySelector('.color-picker');const hex=row.querySelector('.color-hex');picker.addEventListener('input',()=>hex.value=picker.value.toUpperCase());hex.addEventListener('input',()=>{if(/^#[0-9A-Fa-f]{6}$/.test(hex.value))picker.value=hex.value});row.querySelector('.cloth-remove-color').addEventListener('click',()=>{row.remove();renumber();syncMode()})}
    function addRow(item = {}){rows.querySelector('.cloth-empty-row')?.remove();rows.insertAdjacentHTML('beforeend',rowMarkup(item));bindRow(rows.lastElementChild);syncMode()}
    function renumber(){rows.querySelectorAll('tr[data-color-row]').forEach((row,index)=>{row.querySelector('.cloth-color-number').textContent=index+1;row.querySelector('input[type=file]').name=`color_images[${index}]`});if(!rows.querySelector('tr[data-color-row]'))rows.innerHTML=emptyRow()}
    function syncMode(){const separate=stockMode()==='per_color';sharedField.hidden=separate;document.querySelectorAll('.per-color-column').forEach(el=>el.hidden=!separate);document.querySelectorAll('.color-length').forEach(el=>el.required=separate)}
    function syncPricing(){const perSuit=basis.value==='per_suit';suitField.hidden=!perSuit;suitPrice.required=perSuit;defaultLength.required=perSuit}
    initialRows.forEach(addRow);renumber();syncMode();syncPricing();
    document.getElementById('addColorRow').addEventListener('click',()=>addRow({hex:'#808080',length:0}));
    document.querySelectorAll('[name="stock_mode"]').forEach(input=>input.addEventListener('change',syncMode));
    basis.addEventListener('change',syncPricing);
});
</script>
@endpush
