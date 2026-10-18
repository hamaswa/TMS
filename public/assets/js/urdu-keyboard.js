/*
 * Urdu keyboard for the Urdu interface.
 * - Phonetic typing: with the toggle on, Latin keys type Urdu letters in text fields.
 * - On-screen keyboard: clickable Urdu keys for mouse and touch users.
 * Number, phone, email, password and date fields, and fields marked dir="ltr"
 * or data-no-urdu-keyboard, are never changed.
 */
(function () {
    var toggle = document.getElementById('tmsUrduKeyboardToggle');
    var panel = document.getElementById('tmsUrduKeyboard');
    var mini = document.getElementById('tmsUrduKeyboardMini');
    if (!toggle || !panel) return;

    var STORAGE_KEY = 'tms-urdu-keyboard';
    var PANEL_KEY = 'tms-urdu-keyboard-panel';

    // Standard Urdu phonetic layout.
    var PHONETIC = {
        a: 'ا', b: 'ب', c: 'چ', d: 'د', e: 'ع', f: 'ف', g: 'گ', h: 'ھ', i: 'ی', j: 'ج', k: 'ک', l: 'ل', m: 'م',
        n: 'ن', o: 'ہ', p: 'پ', q: 'ق', r: 'ر', s: 'س', t: 'ت', u: 'ء', v: 'ط', w: 'و', x: 'ش', y: 'ے', z: 'ز',
        A: 'آ', C: 'ث', D: 'ڈ', E: 'ٰ', G: 'غ', H: 'ح', I: 'ِ', J: 'ض', K: 'خ', L: 'ل', M: 'ْ', N: 'ں', O: 'ۃ',
        P: 'ُ', Q: 'ْ', R: 'ڑ', S: 'ص', T: 'ٹ', U: 'ئ', V: 'ظ', W: 'ؤ', X: 'ژ', Y: 'ۓ', Z: 'ذ',
        ',': '،', '.': '۔', '?': '؟', ';': '؛'
    };

    var ROWS = [
        ['ا', 'آ', 'ب', 'پ', 'ت', 'ٹ', 'ث', 'ج', 'چ', 'ح', 'خ'],
        ['د', 'ڈ', 'ذ', 'ر', 'ڑ', 'ز', 'ژ', 'س', 'ش', 'ص', 'ض'],
        ['ط', 'ظ', 'ع', 'غ', 'ف', 'ق', 'ک', 'گ', 'ل', 'م', 'ن'],
        ['ں', 'و', 'ہ', 'ھ', 'ء', 'ی', 'ے', 'ئ', 'ۃ', '۔', '،']
    ];

    var SKIP_TYPES = /^(number|tel|email|password|date|datetime-local|time|month|week|url|color|range|file|hidden|checkbox|radio|submit|button)$/i;

    function storage(key, value) {
        try {
            if (value === undefined) return window.localStorage.getItem(key);
            window.localStorage.setItem(key, value);
        } catch (e) { return null; }
    }

    var enabled = storage(STORAGE_KEY) === 'on';
    var panelOpen = storage(PANEL_KEY) !== 'closed';
    var target = null;

    function eligible(el) {
        if (!el || el.readOnly || el.disabled) return false;
        var tag = el.tagName;
        if (tag === 'TEXTAREA') return !el.closest('[dir="ltr"],[data-no-urdu-keyboard]');
        if (tag !== 'INPUT' || SKIP_TYPES.test(el.type || 'text')) return false;
        if (/numeric|decimal|tel|email/i.test(el.inputMode || '')) return false;
        return !el.closest('[dir="ltr"],[data-no-urdu-keyboard]');
    }

    function insert(el, text) {
        var start = el.selectionStart, end = el.selectionEnd;
        if (typeof start !== 'number') { el.value += text; }
        else { el.setRangeText(text, start, end, 'end'); }
        el.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function backspace(el) {
        var start = el.selectionStart, end = el.selectionEnd;
        if (typeof start !== 'number') { el.value = el.value.slice(0, -1); }
        else if (start !== end) { el.setRangeText('', start, end, 'end'); }
        else if (start > 0) { el.setRangeText('', start - 1, start, 'end'); }
        el.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function render() {
        toggle.classList.toggle('is-on', enabled);
        toggle.setAttribute('aria-pressed', enabled ? 'true' : 'false');
        toggle.querySelector('[data-state]').textContent = enabled ? 'آن' : 'بند';
        var active = enabled && target && eligible(target);
        var show = active && panelOpen;
        panel.hidden = !show;
        if (mini) mini.hidden = !(active && !panelOpen);
        document.body.classList.toggle('tms-urdu-kb-open', !!show);
    }

    // Build the on-screen keys.
    var keys = panel.querySelector('.tms-ukb-keys');
    ROWS.forEach(function (row) {
        var line = document.createElement('div');
        line.className = 'tms-ukb-row';
        row.forEach(function (letter) {
            var key = document.createElement('button');
            key.type = 'button';
            key.className = 'tms-ukb-key';
            key.textContent = letter;
            key.dataset.letter = letter;
            line.appendChild(key);
        });
        keys.appendChild(line);
    });

    toggle.addEventListener('click', function () {
        enabled = !enabled;
        storage(STORAGE_KEY, enabled ? 'on' : 'off');
        if (enabled) { panelOpen = true; storage(PANEL_KEY, 'open'); }
        render();
        if (target) target.focus();
    });

    document.addEventListener('focusin', function (event) {
        if (eligible(event.target)) {
            target = event.target;
            render();
        }
    });

    document.addEventListener('focusout', function () {
        // Wait so a click on the panel keeps the field as the target.
        setTimeout(function () {
            var active = document.activeElement;
            if (!panel.contains(active) && !(mini && mini.contains(active)) && !eligible(active)) {
                target = null;
                render();
            }
        }, 150);
    });

    // Phonetic typing on the physical keyboard.
    document.addEventListener('keydown', function (event) {
        if (!enabled || event.ctrlKey || event.metaKey || event.altKey || event.isComposing) return;
        if (!eligible(event.target) || !Object.prototype.hasOwnProperty.call(PHONETIC, event.key)) return;
        event.preventDefault();
        insert(event.target, PHONETIC[event.key]);
    });

    // Clicks on the panel must not take focus away from the field.
    panel.addEventListener('mousedown', function (event) { event.preventDefault(); });
    panel.addEventListener('click', function (event) {
        var button = event.target.closest('button');
        if (!button || !target) return;
        if (button.dataset.letter) insert(target, button.dataset.letter);
        else if (button.dataset.action === 'space') insert(target, ' ');
        else if (button.dataset.action === 'backspace') backspace(target);
        else if (button.dataset.action === 'minimize') {
            panelOpen = false;
            storage(PANEL_KEY, 'closed');
            render();
            return;
        }
        target.focus();
    });

    // Reopen the minimised on-screen keys.
    if (mini) {
        mini.addEventListener('mousedown', function (event) { event.preventDefault(); });
        mini.addEventListener('click', function () {
            panelOpen = true;
            storage(PANEL_KEY, 'open');
            render();
            if (target) target.focus();
        });
    }

    render();
})();
