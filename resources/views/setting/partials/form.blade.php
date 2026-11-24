{{--
    Shop details form shared by the add and edit pages.
    Expects: $action (form URL), $setting (null when adding), $submitLabel.
--}}
@php
    $setting = $setting ?? null;
    $logoUrl = $setting?->logo_url;
    $address = old('address', $setting?->address);
@endphp
@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
<form class="st-card" action="{{ $action }}" method="post" enctype="multipart/form-data" id="st-form">
    @csrf
    <h2 class="st-card-title"><i class="far fa-edit"></i> دکان کی معلومات</h2>
    <div class="st-fields">
        <div class="st-field">
            <label for="st-title">نام <span class="st-req">*</span></label>
            <div class="st-input"><i class="far fa-user"></i>
                <input id="st-title" type="text" name="title" value="{{ old('title', $setting?->name) }}" class="form-control"
                    maxlength="255" required placeholder="دکان کا نام"></div>
        </div>
        <div class="st-field">
            <label for="st-contact">نمبر</label>
            <div class="st-input"><i class="fas fa-phone-alt"></i>
                <input id="st-contact" type="tel" name="contact_no" value="{{ old('contact_no', $setting?->contact_no) }}"
                    class="form-control" maxlength="50" dir="ltr" placeholder="03XXXXXXXXX"></div>
        </div>
        <div class="st-field">
            <label for="st-logo">لوگو / تصویر</label>
            <div class="st-file">
                <span class="st-file-thumb" id="st-logo-thumb">
                    @if ($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $setting->name }} لوگو">@else<i class="far fa-image"></i>@endif
                </span>
                <label class="st-file-pick" for="st-logo">فائل منتخب کریں</label>
                <input id="st-logo" type="file" name="logo" accept="image/*">
                <span class="st-file-name" id="st-logo-name">{{ $logoUrl ? 'موجودہ لوگو' : 'کوئی فائل منتخب نہیں' }}</span>
                @if ($logoUrl)
                    <a class="st-file-view" id="st-logo-view" href="{{ $logoUrl }}" target="_blank" rel="noopener" title="لوگو دیکھیں"><i class="far fa-eye"></i></a>
                @endif
            </div>
            @if ($setting)<input type="hidden" name="oldlogo" value="{{ $setting->logo }}">@endif
        </div>
        <div class="st-field">
            <label for="st-note">نوٹ</label>
            <div class="st-input"><i class="far fa-file-alt"></i>
                <input id="st-note" type="text" name="note" value="{{ old('note', $setting?->note) }}" class="form-control"
                    maxlength="1000" placeholder="رسید پر لکھا جانے والا نوٹ"></div>
        </div>
        <div class="st-field is-wide">
            <label for="st-address">پتہ</label>
            <div class="st-input is-area"><i class="fas fa-map-marker-alt"></i>
                <textarea id="st-address" name="address" rows="3" class="form-control" maxlength="2000"
                    placeholder="دکان کا پتہ">{{ $address }}</textarea></div>
            <div class="st-count"><span id="st-address-count">{{ mb_strlen((string) $address) }}</span> / 2000</div>
        </div>
        <div class="st-field">
            <label for="st-paper">ڈیفالٹ پرنٹ سائز</label>
            <div class="st-input"><i class="fas fa-ruler"></i>
                <select id="st-paper" name="print_paper_size" class="form-control" required>
                    @foreach (\App\Models\Setting::printPaperSizes() as $paperValue => $paperLabel)
                        <option value="{{ $paperValue }}" @selected(old('print_paper_size', $setting?->print_paper_size ?: \App\Models\Setting::PRINT_PAPER_RECEIPT_80) === $paperValue)>{{ $paperLabel }}</option>
                    @endforeach
                </select></div>
        </div>
        <div class="st-field">
            <label class="d-none d-md-block">&nbsp;</label>
            <input type="hidden" name="print_show_qr" value="0">
            <label class="st-check" for="st-qr">
                <input id="st-qr" type="checkbox" name="print_show_qr" value="1" @checked(old('print_show_qr', $setting ? $setting->print_show_qr : true))>
                پرنٹ پر QR حوالہ دکھائیں
                <i class="fas fa-info-circle" title="رسید پر QR کوڈ چھپے گا جس سے گاہک آرڈر کی حالت دیکھ سکتا ہے"></i>
            </label>
        </div>
    </div>
    <div class="st-actions">
        <button type="submit" class="st-btn is-primary"><i class="far fa-save"></i> {{ $submitLabel }}</button>
        <button type="reset" class="st-btn" id="st-reset"><i class="fas fa-undo"></i> دوبارہ ترتیب دیں</button>
    </div>
</form>

@push('scripts')
    <script>
        (function() {
            var form = document.getElementById('st-form');
            if (!form) return;
            var file = document.getElementById('st-logo'),
                thumb = document.getElementById('st-logo-thumb'),
                name = document.getElementById('st-logo-name'),
                address = document.getElementById('st-address'),
                count = document.getElementById('st-address-count'),
                originalThumb = thumb.innerHTML,
                originalName = name.textContent;
            // Show the chosen picture before saving.
            file.addEventListener('change', function() {
                var picked = file.files && file.files[0];
                if (!picked) return;
                name.textContent = picked.name;
                var img = document.createElement('img');
                img.alt = '';
                img.src = URL.createObjectURL(picked);
                thumb.innerHTML = '';
                thumb.appendChild(img);
            });
            address.addEventListener('input', function() { count.textContent = address.value.length; });
            // Reset puts back the saved values, including the logo preview and the address count.
            form.addEventListener('reset', function() {
                window.setTimeout(function() {
                    thumb.innerHTML = originalThumb;
                    name.textContent = originalName;
                    count.textContent = address.value.length;
                });
            });
        })();
    </script>
@endpush
