{{--
    Item-box screen (Settings › Order screen › آئٹم باکس): every set or item in stock is a card.
    Tapping one puts it in the "Your order" panel (item-cart.blade.php) with its count, price and total;
    "آرڈر میں شامل کریں" then adds the whole cart to the order as draft items.
--}}
@php
    $tabIcon = function ($category) {
        $name = mb_strtolower(($category?->name ?? '').' '.($category?->name_ur ?? ''));
        return match (true) {
            $name === ' ' => 'fa-tag',
            (bool) preg_match('/women|ladies|girl|زنانہ|خواتین/u', $name) => 'fa-female',
            (bool) preg_match('/ready|stitched|abaya|ریڈی|عبایا/u', $name) => 'fa-user-tie',
            (bool) preg_match('/access|perfume|cap|متفرق|پرفیوم|ٹوپی/u', $name) => 'fa-shopping-bag',
            (bool) preg_match('/men|gent|مردانہ/u', $name) => 'fa-tshirt',
            default => 'fa-tag',
        };
    };
    $boxItems = $cloths->map(function ($cloth) use ($tabIcon) {
        $colorStock = $cloth->tracksColors()
            ? $cloth->colors->mapWithKeys(fn ($c) => [$c->color => round((float) $c->length, 2)])
            : collect();
        $stock = $cloth->tracksColors()
            ? (float) $colorStock->sum()
            : round((float) ($cloth->colors->sortBy('id')->first()?->length ?? 0), 2);

        return [
            'id' => $cloth->id,
            'name' => $cloth->name ?: 'سیٹ '.$cloth->id,
            'brand' => $cloth->brand?->name ?? '',
            'meta' => collect([$cloth->brand?->name, $cloth->countsPieces() ? null : $cloth->type?->name])->filter()->implode(' · '),
            'main' => $cloth->category?->parent?->localizedName() ?? $cloth->category?->localizedName() ?? 'دیگر',
            // Tab icon picked here from the category's English and Urdu names (kept out of the page script,
            // which English mode translates).
            'mainIcon' => $tabIcon($cloth->category?->parent ?? $cloth->category),
            'sub' => $cloth->category?->parent ? $cloth->category->localizedName() : '',
            'unit' => $cloth->stock_unit ?: 'meter',
            'unitLabel' => $cloth->stockUnitLabel(),
            'whole' => $cloth->countsWholeItems(),
            'basis' => $cloth->sale_price_basis,
            'price' => (float) ($cloth->sellsPerSuit() ? $cloth->suit_sale_price : $cloth->sale_price),
            // Cloth is sold by the metre at this rate; a pick starts at the stored suit length (e.g. 4 m).
            'meterPrice' => (float) $cloth->sale_price,
            'length' => (float) ($cloth->default_sale_length ?: 4.5),
            'stock' => $stock,
            'colors' => $cloth->selectableColorNames(),
            'colorStock' => $colorStock,
            'image' => $cloth->images->first()?->image_url,
        ];
    })->filter(fn ($item) => $item['stock'] > 0)->values();
@endphp

<style>
    .co-main.has-item-boxes{grid-template-columns:minmax(0,1fr) 390px}
    @media(max-width:1000px){.co-main.has-item-boxes{grid-template-columns:1fr}}
    .co-boxes{overflow:visible;border:0;background:transparent;box-shadow:none}
    .co-boxes-hero{position:relative;display:flex;align-items:center;gap:18px;min-height:104px;padding:18px 22px;overflow:hidden;border-radius:18px;background:linear-gradient(100deg,#eef4ff 0%,#e3edff 45%,#c9dcff 100%)}
    .co-boxes-hero:before,.co-boxes-hero:after{content:"";position:absolute;inset-inline-end:-60px;width:420px;height:180px;border-radius:50%;background:radial-gradient(ellipse at center,rgba(255,255,255,.75),rgba(255,255,255,0) 70%);transform:rotate(-14deg);pointer-events:none}
    .co-boxes-hero:before{top:-70px}.co-boxes-hero:after{bottom:-90px;inset-inline-end:80px;opacity:.7;background:radial-gradient(ellipse at center,rgba(147,183,255,.55),rgba(147,183,255,0) 70%)}
    .co-boxes-hero-icon{position:relative;z-index:1;display:grid;place-items:center;flex:0 0 66px;height:66px;border-radius:16px;background:linear-gradient(160deg,#3b82f6,#1d4ed8);color:#fff;font-size:28px;box-shadow:0 10px 22px rgba(29,78,216,.28)}
    .co-boxes-hero-text{position:relative;z-index:1;flex:1;min-width:0}.co-boxes-hero-text h2{margin:0;color:#0f2547;font-size:1.45rem;font-weight:900}.co-boxes-hero-text p{margin:4px 0 0;color:#4b5f7c}
    .co-boxes-search{position:relative;z-index:1;width:min(100%,300px)}.co-boxes-search i{position:absolute;inset-inline-start:14px;top:50%;transform:translateY(-50%);color:#94a3b8}
    .co-boxes-search input{width:100%;min-height:46px;padding:10px 40px;border:1px solid #d8e3f3;border-radius:999px;background:rgba(255,255,255,.92);font-family:inherit;box-shadow:0 4px 14px rgba(30,64,175,.06)}
    .co-boxes-tabs{display:flex;gap:10px;margin:18px 0 16px;overflow-x:auto;scrollbar-width:none}.co-boxes-tabs::-webkit-scrollbar{display:none}
    .co-boxes-tab{display:inline-flex;flex:0 0 auto;align-items:center;gap:10px;min-height:48px;padding:0 20px;border:1px solid #e3eaf4;border-radius:14px;background:#f1f5fb;color:#1e293b;font-weight:800}
    .co-boxes-tab i{font-size:1.05rem;color:#1e293b}.co-boxes-tab.is-active{border-color:#1769e0;background:#1769e0;color:#fff;box-shadow:0 8px 18px rgba(23,105,224,.25)}.co-boxes-tab.is-active i{color:#fff}
    .co-boxes-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(168px,1fr));gap:14px;max-height:calc(100vh - 230px);min-height:260px;padding:2px 2px 6px;overflow-y:auto}
    .co-box{position:relative;border:1px solid #e3eaf4;border-radius:16px;background:#fff;box-shadow:0 4px 14px rgba(15,37,71,.05);transition:.15s}.co-box:hover{transform:translateY(-2px);box-shadow:0 10px 22px rgba(15,37,71,.09)}
    .co-box.is-picked{border-color:#1769e0;box-shadow:0 0 0 3px rgba(23,105,224,.14)}.co-box.is-open{grid-column:span 2}
    .co-box-main{display:flex;flex-direction:column;align-items:stretch;gap:4px;width:100%;padding:9px 9px 12px;border:0;background:transparent;text-align:start;font-family:inherit}
    .co-box-media{position:relative;height:112px;margin-bottom:8px;overflow:hidden;border-radius:12px;background:#e8f0fd}
    .co-box-media img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}.co-box-initial{display:grid;place-items:center;height:100%;color:#1769e0;font-size:2.4rem;font-weight:900}
    .co-box-plus{position:absolute;top:8px;inset-inline-end:8px;display:grid;place-items:center;width:30px;height:30px;border-radius:50%;background:#fff;color:#1769e0;font-size:.8rem;box-shadow:0 3px 10px rgba(15,37,71,.15)}
    .co-box-count{position:absolute;top:8px;inset-inline-start:8px;display:grid;place-items:center;min-width:26px;height:26px;padding:0 7px;border-radius:999px;background:#1769e0;color:#fff;font-size:.78rem;font-weight:900}
    .co-box-main strong{padding:0 3px;overflow:hidden;color:#0f172a;font-size:.95rem;text-overflow:ellipsis;white-space:nowrap}
    .co-box-main small{padding:0 3px;overflow:hidden;color:#64748b;font-size:.78rem;text-overflow:ellipsis;white-space:nowrap}
    .co-box-price{padding:0 3px;margin-top:4px;color:#1769e0;font-size:.92rem;font-weight:900}
    .co-box-suit{padding:0 3px;color:#475569;font-size:.74rem;font-weight:700}
    .co-box-stock{align-self:flex-start;margin:4px 3px 0;padding:3px 10px;border-radius:999px;background:#e7f8ee;color:#15803d;font-size:.72rem;font-weight:800}
    .co-box-colors{display:flex;flex-wrap:wrap;gap:6px;padding:0 10px 12px}.co-box-color{padding:6px 11px;border:1px solid #bcd2f7;border-radius:9px;background:#f5f9ff;color:#1e3a5f;font-size:.8rem;font-weight:800}.co-box-color small{color:#64748b}.co-box-color:disabled{opacity:.45}
    .co-boxes-empty{padding:34px;color:#64748b;text-align:center}.co-boxes-empty i{display:block;margin-bottom:8px;font-size:30px;color:#c4d0de}
    .co-boxes-tailor{position:relative;z-index:1;flex:0 0 auto;min-height:46px;padding:0 18px;border-radius:999px;font-weight:800}.co-boxes-tailor[hidden]{display:none}
    @media(max-width:700px){.co-boxes-hero{flex-wrap:wrap}.co-boxes-search{width:100%}.co-boxes-tailor{width:100%}.co-boxes-grid{grid-template-columns:repeat(2,minmax(0,1fr));max-height:none}}
</style>

<section class="co-panel co-boxes" id="co-boxes" data-items='@json($boxItems)'>
    <div class="co-boxes-hero">
        <span class="co-boxes-hero-icon"><i class="fas fa-shopping-bag"></i></span>
        <div class="co-boxes-hero-text">
            <h2>آئٹم منتخب کریں</h2>
            <p>جو آئٹم آرڈر میں چاہیے اسے دبائیں، وہ ساتھ والے آرڈر میں آ جائے گا۔</p>
        </div>
        <div class="co-boxes-search"><i class="fas fa-search"></i><input type="search" id="co-boxes-search"
                placeholder="نام، برانڈ یا قسم سے تلاش کریں" aria-label="آئٹم تلاش کریں" autocomplete="off"></div>
        @if ($canAddTailoring)
            {{-- Tailoring sits up here with the item picker, so the whole order starts from one place.
                 Hidden for walk-in orders until a customer is chosen (edit.blade.php toggles it). --}}
            <button type="button" class="co-btn is-tailor co-boxes-tailor" id="co-add-tailoring-button" data-toggle="modal"
                data-target="#tailoringItemModal" {{ $canOfferTailoring ? '' : 'hidden' }}><i class="fas fa-plus"></i> سلائی شامل کریں</button>
        @endif
    </div>
    <div class="co-boxes-tabs" id="co-boxes-tabs" role="tablist" aria-label="کیٹیگری"></div>
    <div class="co-boxes-grid" id="co-boxes-grid"></div>
    <div class="co-boxes-empty" id="co-boxes-empty" hidden><i class="fas fa-box-open"></i>کوئی دستیاب آئٹم نہیں ملا</div>
</section>

@push('scripts')
    <script>
        (function() {
            var panel = document.getElementById('co-boxes'),
                cartPanel = document.getElementById('co-cart');
            if (!panel || !cartPanel) return;

            var items = JSON.parse(panel.dataset.items || '[]'),
                byId = {},
                grid = document.getElementById('co-boxes-grid'),
                tabs = document.getElementById('co-boxes-tabs'),
                search = document.getElementById('co-boxes-search'),
                empty = document.getElementById('co-boxes-empty'),
                linesBox = document.getElementById('co-cart-lines'),
                totalBox = document.getElementById('co-cart-total'),
                countBox = document.getElementById('co-cart-count'),
                submit = document.getElementById('co-cart-submit'),
                clear = document.getElementById('co-cart-clear'),
                errors = document.getElementById('co-cart-errors'),
                // v2: cloth lines hold metres and a per-metre price (v1 held a suit count and suit price).
                storageKey = 'co-cart-v2-' + cartPanel.dataset.order,
                activeMain = '',
                openId = null,
                cart = [];
            items.forEach(function(item) { byId[item.id] = item; });

            var esc = function(value) {
                return String(value == null ? '' : value).replace(/[&<>'"]/g, function(c) {
                    return {'&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'}[c];
                });
            };
            var rs = function(value) {
                return 'Rs. ' + Number(value || 0).toLocaleString('en-PK', {minimumFractionDigits: 0, maximumFractionDigits: 2});
            };
            var qtyText = function(item, value) {
                return item.whole ? Number(value).toFixed(0) + ' ' + item.unitLabel : Number(value).toFixed(2) + ' میٹر';
            };
            var priceUnit = function(item) {
                return item.whole ? item.unitLabel : 'میٹر';
            };
            var metres = function(value) {
                return Number(value).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' م';
            };
            // Stock left for one item (and colour), so the cart never asks for more than the shop has.
            var stockFor = function(item, color) {
                return item.colors.length && Object.keys(item.colorStock).length ? Number(item.colorStock[color] || 0) : Number(item.stock);
            };
            // Suits and pieces count whole items; cloth is metres × price per metre.
            var usage = function(line) {
                return byId[line.id].whole ? line.qty : line.length;
            };
            var lineTotal = function(line) {
                return byId[line.id].whole ? line.qty * line.price : line.length * line.price;
            };

            // The cart survives a reload of this order's page, in this browser only.
            try { cart = JSON.parse(localStorage.getItem(storageKey) || '[]').filter(function(line) { return byId[line.id]; }); } catch (e) { cart = []; }
            var save = function() { try { localStorage.setItem(storageKey, JSON.stringify(cart)); } catch (e) {} };

            function renderTabs() {
                var mains = [];
                items.forEach(function(item) {
                    if (!mains.some(function(entry) { return entry.main === item.main; })) mains.push({main: item.main, icon: item.mainIcon});
                });
                tabs.innerHTML = [{main: '', icon: 'fa-th-large'}].concat(mains).map(function(entry) {
                    var active = entry.main === activeMain;
                    return '<button type="button" role="tab" class="co-boxes-tab' + (active ? ' is-active' : '') +
                        '" data-main="' + esc(entry.main) + '" aria-selected="' + active + '"><i class="fas ' + esc(entry.icon) +
                        '"></i>' + esc(entry.main || 'سب') + '</button>';
                }).join('');
                tabs.hidden = mains.length < 2;
            }

            function renderGrid() {
                var query = search.value.trim().toLocaleLowerCase(),
                    shown = items.filter(function(item) {
                        return (!activeMain || item.main === activeMain) &&
                            (!query || [item.name, item.meta, item.main, item.sub].join(' ').toLocaleLowerCase().indexOf(query) !== -1);
                    });
                grid.innerHTML = shown.map(function(item) {
                    var inCart = cart.filter(function(line) { return line.id === item.id; }).reduce(function(sum, line) { return sum + line.qty; }, 0),
                        colors = item.id === openId && item.colors.length > 1
                            ? '<div class="co-box-colors">' + item.colors.map(function(color) {
                                var left = stockFor(item, color);
                                return '<button type="button" class="co-box-color" data-id="' + item.id + '" data-color="' + esc(color) + '"' +
                                    (left <= 0 ? ' disabled' : '') + '>' + esc(color) + (Object.keys(item.colorStock).length ? ' <small>' + esc(qtyText(item, left)) + '</small>' : '') + '</button>';
                            }).join('') + '</div>'
                            : '';
                    return '<div class="co-box' + (inCart ? ' is-picked' : '') + (item.id === openId ? ' is-open' : '') + '">' +
                        '<button type="button" class="co-box-main" data-id="' + item.id + '" aria-label="' + esc(item.name) + ' شامل کریں">' +
                        '<span class="co-box-media">' +
                        // The letter tile sits under the photo, so a missing photo falls back to it.
                        '<span class="co-box-initial">' + esc(item.name.charAt(0)) + '</span>' +
                        (item.image ? '<img src="' + esc(item.image) + '" alt="" loading="lazy" onerror="this.remove()">' : '') +
                        '<span class="co-box-plus"><i class="fas fa-plus"></i></span>' +
                        (inCart ? '<span class="co-box-count">' + esc(Number(inCart).toFixed(0)) + '</span>' : '') +
                        '</span>' +
                        '<strong>' + esc(item.name) + '</strong>' +
                        '<small>' + esc(item.meta || item.sub || item.main) + '</small>' +
                        '<span class="co-box-price">' + rs(item.whole ? item.price : item.meterPrice) + ' / ' + esc(priceUnit(item)) + '</span>' +
                        (item.whole ? '' : '<span class="co-box-suit">1 سوٹ ' + esc(metres(item.length)) + ' = ' + rs(item.meterPrice * item.length) + '</span>') +
                        '<span class="co-box-stock">' + esc(qtyText(item, item.stock)) + ' دستیاب</span>' +
                        '</button>' + colors + '</div>';
                }).join('');
                empty.hidden = shown.length > 0;
            }

            function renderCart() {
                errors.hidden = true;
                linesBox.innerHTML = cart.length ? cart.map(function(line, index) {
                    var item = byId[line.id],
                        meters = !item.whole,
                        suits = meters && item.length ? line.length / item.length : 0;
                    return '<div class="co-cart-line" data-index="' + index + '">' +
                        '<span class="co-cart-thumb">' + esc(item.name.charAt(0)) + (item.image ? '<img src="' + esc(item.image) + '" alt="" onerror="this.remove()">' : '') + '</span>' +
                        '<div class="co-cart-line-main">' +
                        '<div class="co-cart-line-head"><strong>' + esc(item.name) + '</strong>' +
                        '<button type="button" class="co-cart-remove" data-remove="' + index + '" aria-label="ہٹائیں"><i class="fas fa-times"></i></button></div>' +
                        '<small>' + esc([line.color, item.brand].filter(Boolean).join(' - ') || item.main) + '</small>' +
                        '<div class="co-cart-line-body">' +
                        // Cloth: − / + adds or removes one suit's metres; the metres can also be typed.
                        '<div class="co-cart-stepper' + (meters ? ' is-metres' : '') + '"><button type="button" data-step="-1" aria-label="کم کریں">−</button>' +
                        (meters
                            ? '<input type="number" min="0.01" step="0.01" data-field="length" value="' + line.length + '" aria-label="میٹر">'
                            : '<input type="number" min="1" step="1" data-field="qty" value="' + line.qty + '" aria-label="تعداد">') +
                        '<button type="button" data-step="1" aria-label="زیادہ کریں">+</button></div>' +
                        '<label class="co-cart-field" title="فی ' + esc(priceUnit(item)) + ' قیمت">' +
                        '<input type="number" min="0" step="0.01" class="form-control" data-field="price" value="' + line.price + '" aria-label="فی ' + esc(priceUnit(item)) + ' قیمت"></label>' +
                        '</div>' +
                        (meters
                            ? '<div class="co-cart-hint">' + esc(metres(line.length)) + ' × ' + rs(line.price) + ' / میٹر' +
                              (suits >= 1 && Math.abs(suits - Math.round(suits)) < 0.001 ? ' · ' + Math.round(suits) + ' سوٹ' : '') + '</div>'
                            : '<div class="co-cart-hint">' + rs(line.price) + ' / ' + esc(item.unitLabel) + '</div>') +
                        '<div class="co-cart-line-total">' + rs(lineTotal(line)) + '</div></div></div>';
                }).join('') : '<div class="co-cart-empty"><i class="fas fa-shopping-cart"></i>ابھی کوئی آئٹم منتخب نہیں — کسی باکس کو دبائیں</div>';
                var total = cart.reduce(function(sum, line) { return sum + lineTotal(line); }, 0);
                if (totalBox) totalBox.textContent = rs(total);
                countBox.textContent = cart.length;
                countBox.hidden = cart.length === 0;
                submit.disabled = cart.length === 0;
                clear.hidden = cart.length === 0;
                save();
                publish(total);
            }

            // The order page shows the picked lines in its summary and items list as soon as they change,
            // and saves them itself when the order is confirmed (see edit.blade.php).
            var api = window.coCart = {
                count: 0,
                total: 0,
                lines: [],
                remove: function(index) {
                    cart.splice(index, 1);
                    renderCart();
                    renderGrid();
                },
                flush: function() { return send(); }
            };
            function publish(total) {
                api.count = cart.length;
                api.total = Math.round(total * 100) / 100;
                api.lines = cart.map(function(line) {
                    var item = byId[line.id],
                        suits = !item.whole && item.length ? line.length / item.length : 0,
                        wholeSuits = item.basis === 'per_suit' && suits >= 1 && Math.abs(suits - Math.round(suits)) < 0.001;
                    return {
                        name: [item.name, item.meta].filter(Boolean).join(' · '),
                        color: line.color || '',
                        qty: item.whole
                            ? Number(line.qty).toFixed(0) + ' ' + item.unitLabel
                            : (wholeSuits ? Math.round(suits) + ' سوٹ × ' : '') + Number(wholeSuits ? item.length : line.length).toFixed(2) + ' میٹر',
                        total: lineTotal(line)
                    };
                });
                document.dispatchEvent(new CustomEvent('co-cart-change'));
            }

            function add(item, color) {
                var line = cart.find(function(entry) { return entry.id === item.id && entry.color === color; }),
                    meters = !item.whole,
                    used = cart.filter(function(entry) { return entry.id === item.id && entry.color === color; }).reduce(function(sum, entry) { return sum + usage(entry); }, 0),
                    step = item.whole ? 1 : item.length;
                if (used + step > stockFor(item, color) + 0.0001) {
                    showErrors([item.name + ': اسٹاک میں اتنا دستیاب نہیں۔']);
                    return;
                }
                if (line) {
                    if (meters) line.length = Math.round((line.length + item.length) * 100) / 100;
                    else line.qty += 1;
                } else {
                    cart.push({id: item.id, color: color, qty: 1, length: item.whole ? 1 : item.length, price: item.whole ? item.price : item.meterPrice});
                }
                openId = null;
                renderCart();
                renderGrid();
            }

            function showErrors(list) {
                errors.innerHTML = list.map(esc).join('<br>');
                errors.hidden = false;
            }

            tabs.addEventListener('click', function(event) {
                var tab = event.target.closest('[data-main]');
                if (!tab) return;
                activeMain = tab.dataset.main;
                renderTabs();
                renderGrid();
            });
            search.addEventListener('input', renderGrid);
            grid.addEventListener('click', function(event) {
                var chip = event.target.closest('.co-box-color'),
                    box = event.target.closest('.co-box-main');
                if (chip) return add(byId[chip.dataset.id], chip.dataset.color);
                if (!box) return;
                var item = byId[box.dataset.id];
                if (item.colors.length > 1) {
                    // Several colours: show them on the card and let the next tap pick one.
                    openId = openId === item.id ? null : item.id;
                    return renderGrid();
                }
                add(item, item.colors[0] || '');
            });
            linesBox.addEventListener('click', function(event) {
                var remove = event.target.closest('[data-remove]'),
                    step = event.target.closest('[data-step]');
                if (remove) {
                    cart.splice(Number(remove.dataset.remove), 1);
                } else if (step) {
                    var line = cart[Number(step.closest('.co-cart-line').dataset.index)],
                        item = byId[line.id],
                        field = item.whole ? 'qty' : 'length',
                        next = item.whole
                            ? line.qty + Number(step.dataset.step)
                            : Math.round((line.length + Number(step.dataset.step) * item.length) * 100) / 100,
                        others = cart.filter(function(entry) { return entry !== line && entry.id === line.id && entry.color === line.color; })
                            .reduce(function(sum, entry) { return sum + usage(entry); }, 0),
                        changed = Object.assign({}, line);
                    changed[field] = next;
                    if (next < (item.whole ? 1 : 0.01)) return;
                    if (others + usage(changed) > stockFor(item, line.color) + 0.0001) {
                        return showErrors([item.name + ': اسٹاک میں اتنا دستیاب نہیں۔']);
                    }
                    line[field] = next;
                } else {
                    return;
                }
                renderCart();
                renderGrid();
            });
            linesBox.addEventListener('change', function(event) {
                var input = event.target.closest('[data-field]');
                if (!input) return;
                var line = cart[Number(input.closest('.co-cart-line').dataset.index)],
                    value = Number(input.value);
                if (input.dataset.field === 'qty') line.qty = Math.max(1, Math.round(value) || 1);
                if (input.dataset.field === 'length') line.length = value > 0 ? Math.round(value * 100) / 100 : line.length;
                if (input.dataset.field === 'price') line.price = value >= 0 ? value : line.price;
                renderCart();
                renderGrid();
            });
            clear.addEventListener('click', function() {
                cart = [];
                renderCart();
                renderGrid();
            });
            // Saves the picked lines as new order items. Resolves with the server's reply; on failure shows
            // the messages in the cart and rejects with them.
            function send() {
                if (!cart.length) return Promise.resolve(null);
                submit.disabled = true;
                return fetch(cartPanel.dataset.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': cartPanel.dataset.token,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        items: cart.map(function(line) {
                            var item = byId[line.id],
                                suits = !item.whole && item.basis === 'per_suit' && item.length ? line.length / item.length : 0,
                                wholeSuits = suits >= 1 && Math.abs(suits - Math.round(suits)) < 0.001;
                            return {
                                cloth_id: line.id,
                                color: line.color || null,
                                // Cloth: 8 m of a 4 m suit goes as 2 suits × 4 m; any other length as one line of its metres.
                                // Either way the total is metres × price per metre.
                                quantity: item.whole ? line.qty : (wholeSuits ? Math.round(suits) : 1),
                                length: item.whole ? 1 : (wholeSuits ? item.length : line.length),
                                total_price: Math.round(lineTotal(line) * 100) / 100
                            };
                        })
                    })
                }).catch(function() {
                    throw ['کنکشن کا مسئلہ، دوبارہ کوشش کریں۔'];
                }).then(function(response) {
                    return response.json().then(function(body) { return {ok: response.ok, body: body}; });
                }).then(function(result) {
                    if (!result.ok) {
                        throw result.body.errors ? Object.values(result.body.errors).flat() : [result.body.message || 'آئٹم شامل نہیں ہو سکے۔'];
                    }
                    cart = [];
                    save();
                    return result.body;
                }).catch(function(messages) {
                    messages = Array.isArray(messages) ? messages : ['آئٹم شامل نہیں ہو سکے۔'];
                    showErrors(messages);
                    submit.disabled = false;
                    throw messages;
                });
            }
            submit.addEventListener('click', function() {
                send().then(function(body) {
                    if (body) window.location.href = body.redirect;
                }, function() {});
            });
            // Where this page can confirm the order, its confirm button saves the cart too, so no separate step.
            if (document.getElementById('co-confirm-form')) document.getElementById('co-cart-foot').hidden = true;

            renderTabs();
            renderGrid();
            renderCart();
        })();
    </script>
@endpush
