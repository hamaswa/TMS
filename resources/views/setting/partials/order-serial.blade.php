{{-- "Order numbers" section of the settings page: ORD-000108 / ORD-000108-01, or plain 108 / 108-1.
     Uses the .screen-option look from order-screen.blade.php. Saved by OrderScreenSettingController. --}}
@php($serialStyle = old('order_serial_style', $business?->order_serial_style ?: \App\Models\Business::SERIAL_STYLE_STANDARD))
<section class="st-card is-full" id="order-serial">
    <div class="st-card-head">
        <div>
            <h2 class="st-card-title"><i class="fas fa-hashtag"></i> آرڈر نمبر</h2>
            <p>نئے آرڈر اور ان کے سوٹ کس طرح نمبر ہوں</p>
        </div>
    </div>
    <form method="POST" action="{{ route('admin.order-serial.update') }}">
        @csrf @method('PUT')
        <div class="screen-options">
            <label class="screen-option">
                <input type="radio" name="order_serial_style" value="standard" @checked($serialStyle === 'standard')>
                <span class="screen-title">معیاری <i class="fas fa-check screen-check"></i></span>
                <p>آرڈر نمبر کے ساتھ ORD لکھا ہو۔ یہ پہلے سے منتخب ہے۔</p>
                <span class="screen-preview" dir="ltr"><strong>ORD-000108</strong><small>ORD-000108-01 &nbsp;|&nbsp; ORD-000108-02</small></span>
            </label>
            <label class="screen-option">
                <input type="radio" name="order_serial_style" value="simple" @checked($serialStyle === 'simple')>
                <span class="screen-title">سادہ نمبر <i class="fas fa-check screen-check"></i></span>
                <p>صرف نمبر: آرڈر 1، 2، 3… اور اس کے سوٹ آرڈر نمبر کے ساتھ 1، 2، 3۔</p>
                <span class="screen-preview" dir="ltr"><strong>108</strong><small>108-1 &nbsp;|&nbsp; 108-2</small></span>
            </label>
        </div>
        @error('order_serial_style')<div class="text-danger mt-2">{{ $message }}</div>@enderror
        <div class="screen-note"><i class="fas fa-info-circle ml-1"></i>صرف نئے آرڈرز پر لاگو ہوگا اور گنتی وہیں سے آگے چلے گی۔ پرانے آرڈرز کے نمبر نہیں بدلیں گے۔</div>
        <button class="screen-save" type="submit"><i class="fas fa-save ml-1"></i>ترتیب محفوظ کریں</button>
    </form>
</section>
<style>
    #order-serial .screen-preview strong{font-size:1.15rem;color:#163b62}
    #order-serial .screen-preview small{color:#718198;font-weight:700}
</style>
