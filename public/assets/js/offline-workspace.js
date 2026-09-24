(function () {
    'use strict';

    const enabled = document.querySelector('meta[name="tms-offline-enabled"]');
    if (!enabled || !('indexedDB' in window)) {
        return;
    }

    const syncUrl = document.querySelector('meta[name="tms-offline-sync-url"]')?.content || null;
    const actorKey = document.querySelector('meta[name="tms-offline-actor"]')?.content || null;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const DB_NAME = 'tms-offline-workspace';
    const STORE_NAME = 'operations';
    const DB_VERSION = 1;
    let flushing = false;
    let panelOpen = false;

    const labels = {
        unassigned: 'درزی مقرر ہونا باقی',
        assigned: 'درزی مقرر',
        cutting: 'کٹائی',
        stitching: 'سلائی',
        trial: 'ٹرائل',
        ready: 'تیار',
        delivered: 'حوالہ شدہ'
    };

    const legacyStatuses = { start: 'cutting', complete: 'ready', deliver: 'delivered' };

    const uuid = function () {
        if (window.crypto?.randomUUID) return window.crypto.randomUUID();
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (character) {
            const random = Math.random() * 16 | 0;
            const value = character === 'x' ? random : (random & 0x3 | 0x8);
            return value.toString(16);
        });
    };

    const openDatabase = () => new Promise(function (resolve, reject) {
        const request = indexedDB.open(DB_NAME, DB_VERSION);
        request.onupgradeneeded = function () {
            const database = request.result;
            if (!database.objectStoreNames.contains(STORE_NAME)) {
                const store = database.createObjectStore(STORE_NAME, { keyPath: 'id' });
                store.createIndex('status', 'status', { unique: false });
                store.createIndex('created_at', 'created_at', { unique: false });
            }
        };
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });

    const transaction = async function (mode, callback) {
        const database = await openDatabase();
        return new Promise(function (resolve, reject) {
            const tx = database.transaction(STORE_NAME, mode);
            const store = tx.objectStore(STORE_NAME);
            const result = callback(store);
            tx.oncomplete = () => { database.close(); resolve(result); };
            tx.onerror = () => { database.close(); reject(tx.error); };
            tx.onabort = () => { database.close(); reject(tx.error); };
        });
    };

    const saveOperation = (operation) => transaction('readwrite', (store) => store.put(operation));
    const removeOperation = (id) => transaction('readwrite', (store) => store.delete(id));
    const allOperations = async () => {
        const database = await openDatabase();
        return new Promise(function (resolve, reject) {
            const tx = database.transaction(STORE_NAME, 'readonly');
            const request = tx.objectStore(STORE_NAME).getAll();
            request.onsuccess = () => resolve(request.result.sort((a, b) => a.created_at.localeCompare(b.created_at)));
            request.onerror = () => reject(request.error);
            tx.oncomplete = () => database.close();
        });
    };

    const clearOperations = async () => {
        try { await transaction('readwrite', (store) => store.clear()); } catch (error) { /* best effort */ }
    };

    const widget = document.createElement('aside');
    widget.id = 'tms-offline-widget';
    widget.setAttribute('dir', 'rtl');
    widget.innerHTML = `
        <button type="button" class="tms-offline-toggle" aria-expanded="false">
            <span class="tms-offline-dot"></span>
            <span class="tms-offline-label">سنک جانچ رہا ہے</span>
            <span class="tms-offline-count" hidden>0</span>
        </button>
        <section class="tms-offline-panel" hidden>
            <strong>آف لائن کام</strong>
            <p class="tms-offline-summary mb-2"></p>
            <div class="tms-offline-conflicts"></div>
            <button type="button" class="tms-offline-sync-now">ابھی سنک کریں</button>
        </section>`;
    document.body.appendChild(widget);

    const style = document.createElement('style');
    style.textContent = `
        #tms-offline-widget{position:fixed;left:16px;bottom:16px;z-index:1090;font-family:"Noto Nastaliq Urdu",Tahoma,Arial,sans-serif;text-align:right}.tms-offline-toggle{display:flex;align-items:center;gap:8px;min-height:42px;padding:8px 13px;border:1px solid #cbd9e7;border-radius:999px;background:#fff;color:#28445f;box-shadow:0 8px 25px rgba(15,42,67,.18);font-size:12px;font-weight:800}.tms-offline-dot{width:10px;height:10px;border-radius:50%;background:#e0a100}.tms-offline-toggle.is-online .tms-offline-dot{background:#15945c}.tms-offline-toggle.is-offline .tms-offline-dot{background:#c33c4d}.tms-offline-toggle.has-conflict .tms-offline-dot{background:#d97706}.tms-offline-count{min-width:20px;padding:1px 6px;border-radius:999px;background:#1769e0;color:#fff;text-align:center}.tms-offline-panel{position:absolute;left:0;bottom:52px;width:min(340px,calc(100vw - 32px));padding:15px;border:1px solid #d8e3ed;border-radius:14px;background:#fff;box-shadow:0 16px 42px rgba(15,42,67,.2);color:#334e68;font-size:12px;line-height:1.8}.tms-offline-panel p{margin:5px 0}.tms-offline-sync-now,.tms-offline-discard{padding:7px 12px;border:0;border-radius:8px;background:#1769e0;color:#fff;font-weight:800}.tms-offline-conflict{padding:9px;margin:8px 0;border:1px solid #f3c58c;border-radius:9px;background:#fff8eb}.tms-offline-conflict strong{display:block}.tms-offline-discard{margin-top:6px;background:#6b7c8f}.tms-offline-pending{outline:2px solid #e0a100;outline-offset:3px}.tms-offline-toast{position:fixed;right:16px;bottom:18px;z-index:1100;max-width:420px;padding:12px 16px;border-radius:10px;background:#173f5f;color:#fff;box-shadow:0 10px 30px rgba(0,0,0,.18);font-weight:700}
    `;
    document.head.appendChild(style);

    const toggle = widget.querySelector('.tms-offline-toggle');
    const panel = widget.querySelector('.tms-offline-panel');
    const label = widget.querySelector('.tms-offline-label');
    const count = widget.querySelector('.tms-offline-count');
    const summary = widget.querySelector('.tms-offline-summary');
    const conflicts = widget.querySelector('.tms-offline-conflicts');

    const showToast = function (message) {
        const toast = document.createElement('div');
        toast.className = 'tms-offline-toast';
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 4500);
    };

    const renderStatus = async function () {
        const operations = (await allOperations()).filter((operation) => operation.actor_key === actorKey);
        const pending = operations.filter((operation) => operation.status === 'pending');
        const conflicted = operations.filter((operation) => operation.status === 'conflict');
        const total = pending.length + conflicted.length;

        toggle.classList.toggle('is-online', navigator.onLine && !conflicted.length);
        toggle.classList.toggle('is-offline', !navigator.onLine);
        toggle.classList.toggle('has-conflict', conflicted.length > 0);
        label.textContent = !navigator.onLine
            ? 'آف لائن'
            : (conflicted.length ? 'سنک تنازع' : (pending.length ? 'سنک باقی' : 'آن لائن'));
        count.hidden = total === 0;
        count.textContent = String(total);
        summary.textContent = !navigator.onLine
            ? `${pending.length} تبدیلیاں انٹرنیٹ آنے پر سنک ہوں گی۔`
            : (total ? `${pending.length} زیرِ التوا، ${conflicted.length} تنازع۔` : 'تمام تبدیلیاں سرور کے ساتھ سنک ہیں۔');

        conflicts.replaceChildren();
        conflicted.forEach(function (operation) {
            const item = document.createElement('div');
            item.className = 'tms-offline-conflict';
            const title = document.createElement('strong');
            title.textContent = `آرڈر #${operation.aggregate_id}`;
            const message = document.createElement('span');
            message.textContent = operation.result?.message || 'تبدیلی دوسری تبدیلی سے متصادم ہے۔';
            const discard = document.createElement('button');
            discard.type = 'button';
            discard.className = 'tms-offline-discard';
            discard.textContent = 'سرور کی موجودہ حالت رکھیں';
            discard.addEventListener('click', async function () {
                await removeOperation(operation.id);
                await renderStatus();
                if (navigator.onLine) window.location.reload();
            });
            item.append(title, message, discard);
            conflicts.appendChild(item);
        });
    };

    const flush = async function () {
        if (flushing || !navigator.onLine || !syncUrl) return;
        const operations = (await allOperations()).filter((operation) => operation.actor_key === actorKey && operation.status === 'pending');
        if (!operations.length) { await renderStatus(); return; }

        flushing = true;
        try {
            const response = await fetch(syncUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ commands: operations.map(function (operation) {
                    return {
                        id: operation.id,
                        type: operation.type,
                        aggregate_id: operation.aggregate_id,
                        base_version: operation.base_version,
                        payload: operation.payload,
                        created_at: operation.created_at
                    };
                }) })
            });

            if (!response.ok) {
                if ([401, 403, 419].includes(response.status)) {
                    showToast('سنک کے لیے دوبارہ لاگ اِن کریں۔ آپ کی آف لائن تبدیلیاں محفوظ ہیں۔');
                    return;
                }
                throw new Error(`sync-failed-${response.status}`);
            }

            const data = await response.json();
            let applied = 0;
            for (const result of data.results || []) {
                if (['applied', 'superseded'].includes(result.status)) {
                    await removeOperation(result.id);
                    applied += 1;
                } else {
                    const operation = operations.find((candidate) => candidate.id === result.id);
                    if (operation) await saveOperation({ ...operation, status: 'conflict', result: result });
                }
            }
            await renderStatus();
            if (applied) showToast(`${applied} آف لائن تبدیلیاں کامیابی سے سنک ہو گئیں۔`);
        } catch (error) {
            if (navigator.onLine) showToast('سنک مکمل نہیں ہو سکا؛ دوبارہ کوشش خودکار طور پر ہوگی۔');
        } finally {
            flushing = false;
        }
    };

    toggle.addEventListener('click', function () {
        panelOpen = !panelOpen;
        panel.hidden = !panelOpen;
        toggle.setAttribute('aria-expanded', String(panelOpen));
    });
    widget.querySelector('.tms-offline-sync-now').addEventListener('click', flush);

    document.addEventListener('submit', async function (event) {
        const form = event.target.closest('form[data-offline-command="order.status.change"]');
        if (!form || !syncUrl) return;

        event.preventDefault();
        const data = new FormData(form);
        const submitter = event.submitter;
        const submittedButtonStatus = submitter
            && ['status', 'order_status'].includes(submitter.name)
            ? submitter.value
            : null;
        const rawStatus = data.get('status') || data.get('order_status') || submittedButtonStatus;
        if (!rawStatus) {
            showToast('تبدیلی محفوظ نہیں ہو سکی؛ صفحہ تازہ کر کے دوبارہ کوشش کریں۔');
            return;
        }
        const targetStatus = legacyStatuses[rawStatus] || rawStatus;
        const operation = {
            id: uuid(),
            type: 'order.status.change',
            aggregate_id: Number(form.dataset.orderId),
            base_version: form.dataset.baseStatus,
            payload: { status: targetStatus, note: data.get('note') || null },
            created_at: new Date().toISOString(),
            status: 'pending',
            actor_key: actorKey
        };

        await saveOperation(operation);
        form.classList.add('tms-offline-pending');
        form.querySelectorAll('button,select,input,textarea').forEach((element) => { element.disabled = true; });
        showToast(navigator.onLine ? 'تبدیلی سنک کی جا رہی ہے۔' : 'تبدیلی آلے پر محفوظ ہے اور بعد میں سنک ہوگی۔');
        await renderStatus();
        await flush();
    });

    document.addEventListener('submit', function (event) {
        if (event.target.matches('#logout-form') && navigator.serviceWorker?.controller) {
            navigator.serviceWorker.controller.postMessage({ type: 'CLEAR_PRIVATE_DATA' });
            clearOperations();
        }
    });

    document.addEventListener('click', function (event) {
        const logout = event.target.closest('a[href*="logout"]');
        if (!logout) return;
        navigator.serviceWorker?.controller?.postMessage({ type: 'CLEAR_PRIVATE_DATA' });
        clearOperations();
    });

    window.addEventListener('online', async function () { await renderStatus(); await flush(); });
    window.addEventListener('offline', renderStatus);
    setInterval(flush, 30000);

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/service-worker.js', { scope: '/' })
            .then(() => navigator.serviceWorker.ready)
            .then((registration) => {
                const worker = navigator.serviceWorker.controller || registration.active;
                worker?.postMessage({ type: 'CACHE_CURRENT_PAGE', url: window.location.href });
            })
            .catch(function () { showToast('آف لائن سروس شروع نہیں ہو سکی۔'); });
    }

    renderStatus().then(flush);
})();
