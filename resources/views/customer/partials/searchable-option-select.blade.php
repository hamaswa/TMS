<style>
    .ss-wrap { position: relative; }
    .ss-panel { display: none; position: fixed; z-index: 1060; background: #fff; border: 1px solid #cfd8e3; border-radius: .5rem; box-shadow: 0 8px 24px rgba(15, 35, 60, .15); }
    .ss-panel.open { display: block; }
    .ss-search { border: 0; border-bottom: 1px solid #e3e9f0; border-radius: .5rem .5rem 0 0; }
    .ss-search:focus { box-shadow: none; }
    .ss-list { max-height: 260px; overflow-y: auto; margin: 0; padding: .25rem 0; list-style: none; text-align: right; }
    .ss-item { padding: .45rem .9rem; cursor: pointer; }
    .ss-item.active, .ss-item:hover { background: #eef4ff; }
    .ss-item.selected { font-weight: 700; }
    .ss-item.ss-placeholder { color: #6c757d; }
    .ss-item.ss-add { color: #0d6efd; border-top: 1px solid #e3e9f0; }
    .ss-empty { padding: .6rem .9rem; color: #6c757d; text-align: right; }
</style>
<script>
// Stitching preference lists are long; open them as a searchable list instead of the native
// dropdown. The native <select> stays the source of truth (form value, template filtering,
// quick-add), and the search box is a normal text input so the Urdu keyboard types into it.
document.addEventListener('DOMContentLoaded', function () {
    function normalize(text) {
        return (text || '').toLowerCase()
            .replace(/[ً-ٰٟ]/g, '')
            .replace(/[يى]/g, 'ی').replace(/ك/g, 'ک').replace(/[هۀ]/g, 'ہ')
            .replace(/\s+/g, ' ').trim();
    }

    // With the Urdu keyboard off, "bin" should still find "بین": also try the query
    // through the same phonetic map the Urdu keyboard types with.
    function queries(text) {
        var list = [normalize(text)], map = window.TMS_URDU_PHONETIC;
        if (map && /[A-Za-z]/.test(text)) {
            list.push(normalize(text.replace(/[A-Za-z,.?;]/g, function (ch) { return map[ch] || ch; })));
        }
        return list.filter(Boolean);
    }

    document.querySelectorAll('select[data-template-system-field]').forEach(function (select) {
        var wrap = document.createElement('div');
        wrap.className = 'ss-wrap';
        select.parentNode.insertBefore(wrap, select);
        wrap.appendChild(select);

        var panel = document.createElement('div');
        panel.className = 'ss-panel';
        panel.innerHTML = '<input type="text" class="form-control ss-search" placeholder="تلاش کریں…" autocomplete="off">'
            + '<ul class="ss-list" role="listbox"></ul>';
        wrap.appendChild(panel);
        var search = panel.querySelector('.ss-search'), list = panel.querySelector('.ss-list'), active = -1;

        function items() { return list.querySelectorAll('.ss-item'); }

        function setActive(index) {
            var all = items();
            if (!all.length) { active = -1; return; }
            active = (index + all.length) % all.length;
            all.forEach(function (item, n) { item.classList.toggle('active', n === active); });
            all[active].scrollIntoView({ block: 'nearest' });
        }

        function addItem(option, className) {
            var item = document.createElement('li');
            item.className = 'ss-item' + (className ? ' ' + className : '') + (option.value === select.value && option.value ? ' selected' : '');
            item.setAttribute('role', 'option');
            item.dataset.value = option.value;
            item.textContent = option.text.trim();
            list.appendChild(item);
        }

        function build() {
            var terms = queries(search.value), query = terms.length > 0, matches = 0, addNew = null;
            list.innerHTML = '';
            Array.prototype.forEach.call(select.options, function (option) {
                if (option.hidden || option.disabled) return;
                if (option.value === '__add_new__') { addNew = option; return; }
                if (!option.value) { if (!query) addItem(option, 'ss-placeholder'); return; }
                var name = normalize(option.text);
                if (query && !terms.some(function (term) { return name.indexOf(term) !== -1; })) return;
                addItem(option);
                matches++;
            });
            if (query && !matches) {
                var empty = document.createElement('li');
                empty.className = 'ss-empty';
                empty.textContent = 'کوئی انتخاب نہیں ملا';
                list.appendChild(empty);
            }
            if (addNew) addItem(addNew, 'ss-add');
            var selected = list.querySelector('.ss-item.selected');
            setActive(query ? 0 : Array.prototype.indexOf.call(items(), selected));
        }

        // Fixed to the viewport so the card's overflow:hidden can't clip it; flips above when
        // there's no room below.
        function place() {
            var rect = select.getBoundingClientRect(), height = 320;
            var below = window.innerHeight - rect.bottom >= height || rect.top < height;
            panel.style.left = rect.left + 'px';
            panel.style.width = rect.width + 'px';
            panel.style.top = below ? (rect.bottom + 2) + 'px' : '';
            panel.style.bottom = below ? '' : (window.innerHeight - rect.top + 2) + 'px';
        }

        function open() {
            if (panel.classList.contains('open')) return;
            place();
            panel.classList.add('open');
            search.value = '';
            build();
            search.focus();
        }

        function close(refocus) {
            panel.classList.remove('open');
            if (refocus) select.focus();
        }

        function choose(value) {
            close(true);
            if (select.value === value) return;
            select.value = value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }

        select.addEventListener('mousedown', function (event) {
            event.preventDefault();
            panel.classList.contains('open') ? close(true) : open();
        });
        select.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ' || event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                open();
            }
        });
        search.addEventListener('input', build);
        search.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                setActive(active + (event.key === 'ArrowDown' ? 1 : -1));
            } else if (event.key === 'Enter') {
                event.preventDefault();
                var item = items()[active];
                if (item) choose(item.dataset.value);
            } else if (event.key === 'Escape') {
                event.preventDefault();
                close(true);
            } else if (event.key === 'Tab') {
                close(false);
            }
        });
        list.addEventListener('mousedown', function (event) { event.preventDefault(); });
        list.addEventListener('click', function (event) {
            var item = event.target.closest('.ss-item');
            if (item) choose(item.dataset.value);
        });
        document.addEventListener('mousedown', function (event) {
            if (!panel.classList.contains('open') || wrap.contains(event.target)) return;
            // Clicking the on-screen Urdu keyboard must not close the list.
            if (event.target.closest('#tmsUrduKeyboard, #tmsUrduKeyboardMini, #tmsUrduKeyboardToggle')) return;
            close(false);
        });
        window.addEventListener('scroll', function (event) {
            if (panel.classList.contains('open') && !panel.contains(event.target)) place();
        }, true);
        window.addEventListener('resize', function () { if (panel.classList.contains('open')) place(); });
    });
});
</script>
