{{-- Urdu keyboard panel (Urdu mode only). The toggle button lives in the top bar. --}}
<div id="tmsUrduKeyboard" class="tms-ukb" role="group" aria-label="اردو کی بورڈ" hidden>
    <div class="tms-ukb-head">
        <span><i class="fas fa-keyboard ml-1"></i> اردو کی بورڈ
            <small>عام کی بورڈ سے بھی لکھیں: a=ا، b=ب، k=ک، Shift+t=ٹ</small></span>
        <button type="button" class="tms-ukb-icon" data-action="minimize" aria-label="کی بورڈ چھپائیں">
            <i class="fas fa-chevron-down"></i>
        </button>
    </div>
    <div class="tms-ukb-keys"></div>
    <div class="tms-ukb-row tms-ukb-actions">
        <button type="button" class="tms-ukb-key is-wide" data-action="backspace" aria-label="مٹائیں"><i class="fas fa-backspace"></i></button>
        <button type="button" class="tms-ukb-key is-space" data-action="space">خالی جگہ</button>
    </div>
</div>
<button type="button" id="tmsUrduKeyboardMini" class="tms-ukb-mini" hidden>
    <i class="fas fa-keyboard ml-1"></i> کی بورڈ دکھائیں
</button>
<style>
    .tms-ukb-toggle { display: inline-flex; align-items: center; gap: 6px; margin: .45rem .35rem; padding: 4px 12px; border: 1px solid rgba(255, 255, 255, .28); border-radius: 999px; background: rgba(255, 255, 255, .08); color: rgba(255, 255, 255, .85); font-size: .82rem; font-weight: 700; line-height: 1.6; white-space: nowrap; }
    .tms-ukb-toggle:hover, .tms-ukb-toggle:focus { color: #fff; outline: none; box-shadow: 0 0 0 3px rgba(255, 255, 255, .15); }
    .tms-ukb-toggle.is-on { border-color: #4ade80; background: #16a34a; color: #fff; }
    .tms-ukb { position: fixed; z-index: 1090; right: 50%; bottom: 12px; width: min(640px, calc(100vw - 24px)); padding: 10px 10px 12px; border: 1px solid #d5dfeb; border-radius: 16px; background: #f8fbff; box-shadow: 0 18px 45px rgba(16, 42, 67, .25); transform: translateX(50%); direction: rtl; }
    .tms-ukb-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin: 0 4px 8px; color: #102a52; font-weight: 800; }
    .tms-ukb-head small { display: block; color: #6b7c91; font-weight: 600; }
    .tms-ukb-icon { width: 34px; height: 34px; border: 1px solid #d5dfeb; border-radius: 9px; background: #fff; color: #40566f; }
    .tms-ukb-row { display: flex; justify-content: center; gap: 5px; margin-top: 5px; }
    .tms-ukb-key { flex: 1 1 0; min-width: 0; height: 42px; padding: 0; border: 1px solid #d5dfeb; border-radius: 9px; background: #fff; color: #102a52; font-size: 1.2rem; font-weight: 700; line-height: 1; box-shadow: 0 2px 0 #d5dfeb; }
    .tms-ukb-key:hover { border-color: #1769df; color: #1769df; }
    .tms-ukb-key:active { transform: translateY(1px); box-shadow: none; }
    .tms-ukb-key.is-wide { flex: 0 0 90px; font-size: 1rem; }
    .tms-ukb-key.is-space { flex: 1 1 auto; font-size: .9rem; }
    .tms-ukb-mini { position: fixed; z-index: 1090; left: 16px; bottom: 16px; padding: 8px 14px; border: 0; border-radius: 999px; background: #174f78; color: #fff; font-weight: 700; box-shadow: 0 8px 20px rgba(16, 42, 67, .25); }
    body.tms-urdu-kb-open { padding-bottom: 260px; }
    @media (max-width: 575.98px) {
        .tms-ukb-key { height: 38px; font-size: 1rem; }
        .tms-ukb-head small { display: none; }
    }
</style>
<script src="{{ asset('assets/js/urdu-keyboard.js') }}?v=20261019b"></script>
