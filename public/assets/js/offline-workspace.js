(function () {
    'use strict';

    const enabled = document.querySelector('meta[name="tms-offline-enabled"]');
    if (!enabled || !('indexedDB' in window)) {
        return;
    }

    const syncUrl = document.querySelector('meta[name="tms-offline-sync-url"]')?.content || null;
    const manifestUrl = document.querySelector('meta[name="tms-offline-manifest-url"]')?.content || null;
    const actorKey = document.querySelector('meta[name="tms-offline-actor"]')?.content || null;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const DB_NAME = 'tms-offline-workspace';
    const STORE_NAME = 'operations';
    const DB_VERSION = 1;
    let flushing = false;
    let panelOpen = false;
    let preparing = false;
    let preparationProgress = null;
    const ACTIVE_ACTOR_KEY = 'tms-offline-active-actor';
    const readinessKey = `tms-offline-readiness:${actorKey}`;
    const refreshKey = `tms-offline-refresh:${actorKey}`;

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
            <div class="tms-offline-readiness">
                <p class="tms-offline-readiness-text"></p>
                <div class="tms-offline-progress" hidden><span></span></div>
                <button type="button" class="tms-offline-prepare">آف لائن ورک اسپیس تیار کریں</button>
            </div>
            <div class="tms-offline-conflicts"></div>
            <button type="button" class="tms-offline-sync-now">ابھی سنک کریں</button>
        </section>`;
    document.body.appendChild(widget);

    const style = document.createElement('style');
    style.textContent = `
        #tms-offline-widget{position:fixed;left:16px;bottom:16px;z-index:1090;font-family:"Noto Nastaliq Urdu",Tahoma,Arial,sans-serif;text-align:right}.tms-offline-toggle{display:flex;align-items:center;gap:8px;min-height:42px;padding:8px 13px;border:1px solid #cbd9e7;border-radius:999px;background:#fff;color:#28445f;box-shadow:0 8px 25px rgba(15,42,67,.18);font-size:12px;font-weight:800}.tms-offline-dot{width:10px;height:10px;border-radius:50%;background:#e0a100}.tms-offline-toggle.is-online .tms-offline-dot{background:#15945c}.tms-offline-toggle.is-offline .tms-offline-dot{background:#c33c4d}.tms-offline-toggle.is-preparing .tms-offline-dot{background:#1769e0;animation:tmsOfflinePulse 1s infinite}.tms-offline-toggle.has-conflict .tms-offline-dot{background:#d97706}.tms-offline-count{min-width:20px;padding:1px 6px;border-radius:999px;background:#1769e0;color:#fff;text-align:center}.tms-offline-panel{position:absolute;left:0;bottom:52px;width:min(360px,calc(100vw - 32px));padding:15px;border:1px solid #d8e3ed;border-radius:14px;background:#fff;box-shadow:0 16px 42px rgba(15,42,67,.2);color:#334e68;font-size:12px;line-height:1.8}.tms-offline-panel p{margin:5px 0}.tms-offline-readiness{padding:10px;margin:9px 0;border:1px solid #d8e3ed;border-radius:10px;background:#f7fbff}.tms-offline-prepare,.tms-offline-sync-now,.tms-offline-discard{padding:7px 12px;border:0;border-radius:8px;background:#1769e0;color:#fff;font-weight:800}.tms-offline-prepare{width:100%}.tms-offline-prepare:disabled{cursor:wait;opacity:.7}.tms-offline-sync-now{background:#52677b}.tms-offline-progress{height:8px;margin:8px 0 10px;overflow:hidden;border-radius:99px;background:#dce7f2}.tms-offline-progress span{display:block;width:0;height:100%;background:#1769e0;transition:width .2s ease}.tms-offline-conflict{padding:9px;margin:8px 0;border:1px solid #f3c58c;border-radius:9px;background:#fff8eb}.tms-offline-conflict strong{display:block}.tms-offline-discard{margin-top:6px;background:#6b7c8f}.tms-offline-pending{outline:2px solid #e0a100;outline-offset:3px}.tms-offline-toast{position:fixed;right:16px;bottom:18px;z-index:1100;max-width:420px;padding:12px 16px;border-radius:10px;background:#173f5f;color:#fff;box-shadow:0 10px 30px rgba(0,0,0,.18);font-weight:700}@keyframes tmsOfflinePulse{50%{opacity:.35}}
    `;
    document.head.appendChild(style);

    const toggle = widget.querySelector('.tms-offline-toggle');
    const panel = widget.querySelector('.tms-offline-panel');
    const label = widget.querySelector('.tms-offline-label');
    const count = widget.querySelector('.tms-offline-count');
    const summary = widget.querySelector('.tms-offline-summary');
    const conflicts = widget.querySelector('.tms-offline-conflicts');
    const readinessText = widget.querySelector('.tms-offline-readiness-text');
    const progress = widget.querySelector('.tms-offline-progress');
    const progressBar = progress.querySelector('span');
    const prepareButton = widget.querySelector('.tms-offline-prepare');

    const readReadiness = function () {
        try { return JSON.parse(localStorage.getItem(readinessKey) || 'null'); } catch (error) { return null; }
    };

    const writeReadiness = function (value) {
        if (value) localStorage.setItem(readinessKey, JSON.stringify(value));
        else localStorage.removeItem(readinessKey);
    };

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
        const readiness = readReadiness();
        const ready = Boolean(readiness?.enabled && readiness?.status === 'ready');

        toggle.classList.toggle('is-online', navigator.onLine && !conflicted.length);
        toggle.classList.toggle('is-offline', !navigator.onLine);
        toggle.classList.toggle('is-preparing', preparing);
        toggle.classList.toggle('has-conflict', conflicted.length > 0);
        label.textContent = preparing
            ? `تیاری ${preparationProgress?.completed || 0}/${preparationProgress?.total || 0}`
            : (!navigator.onLine
            ? 'آف لائن'
            : (conflicted.length ? 'سنک تنازع' : (pending.length ? 'سنک باقی' : (ready ? 'آف لائن تیار' : 'آن لائن'))));
        count.hidden = total === 0;
        count.textContent = String(total);
        summary.textContent = !navigator.onLine
            ? (ready
                ? `${pending.length} تبدیلیاں انٹرنیٹ آنے پر سنک ہوں گی۔`
                : 'اس آلے پر مکمل آف لائن ورک اسپیس تیار نہیں کی گئی۔')
            : (total ? `${pending.length} زیرِ التوا، ${conflicted.length} تنازع۔` : 'تمام تبدیلیاں سرور کے ساتھ سنک ہیں۔');

        prepareButton.disabled = preparing || !navigator.onLine || !manifestUrl;
        prepareButton.textContent = preparing
            ? 'ورک اسپیس تیار ہو رہی ہے…'
            : (ready ? 'آف لائن مواد تازہ کریں' : 'آف لائن ورک اسپیس تیار کریں');
        progress.hidden = !preparing;
        if (preparing) {
            const completed = preparationProgress?.completed || 0;
            const progressTotal = preparationProgress?.total || 0;
            progressBar.style.width = `${progressTotal ? Math.round((completed / progressTotal) * 100) : 0}%`;
            readinessText.textContent = preparationProgress?.current
                ? `${preparationProgress.current} محفوظ ہو رہا ہے (${completed}/${progressTotal})`
                : `ضروری صفحات تیار کیے جا رہے ہیں (${completed}/${progressTotal})`;
        } else if (ready) {
            const preparedAt = readiness.prepared_at ? new Date(readiness.prepared_at).toLocaleString('ur-PK') : '';
            readinessText.textContent = `یہ ورک اسپیس آف لائن استعمال کے لیے تیار ہے۔${preparedAt ? ` آخری تیاری: ${preparedAt}` : ''}`;
        } else if (readiness?.status === 'partial') {
            readinessText.textContent = `${readiness.failed || 0} صفحات محفوظ نہیں ہو سکے۔ انٹرنیٹ چیک کر کے دوبارہ کوشش کریں۔`;
        } else {
            readinessText.textContent = 'پہلی بار آن لائن رہتے ہوئے ضروری صفحات آلے پر محفوظ کریں۔';
        }

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

    const prepareWorkspace = async function (automatic = false) {
        if (preparing || !navigator.onLine || !manifestUrl || !('serviceWorker' in navigator)) return;
        preparing = true;
        preparationProgress = { completed: 0, total: 0, current: '' };
        await renderStatus();

        try {
            const response = await fetch(manifestUrl, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                cache: 'no-store'
            });
            if (!response.ok) throw new Error(`manifest-${response.status}`);
            const manifest = await response.json();
            if (manifest.actor !== actorKey || !Array.isArray(manifest.pages) || !manifest.pages.length) {
                throw new Error('invalid-manifest');
            }

            preparationProgress.total = manifest.pages.length;
            await renderStatus();
            const registration = await navigator.serviceWorker.ready;
            const worker = navigator.serviceWorker.controller || registration.active;
            if (!worker) throw new Error('worker-unavailable');
            worker.postMessage({
                type: 'PREPARE_OFFLINE_WORKSPACE',
                pages: manifest.pages,
                version: manifest.version,
                actorKey: actorKey
            });
        } catch (error) {
            preparing = false;
            preparationProgress = null;
            if (!automatic) showToast('آف لائن ورک اسپیس تیار نہیں ہو سکی۔ انٹرنیٹ چیک کر کے دوبارہ کوشش کریں۔');
            await renderStatus();
        }
    };

    const handleWorkerMessage = async function (event) {
        const data = event.data || {};
        if (data.type === 'OFFLINE_PREPARE_PROGRESS') {
            preparationProgress = {
                completed: Number(data.completed || 0),
                total: Number(data.total || 0),
                current: data.current || ''
            };
            await renderStatus();
            return;
        }
        if (data.type !== 'OFFLINE_PREPARE_COMPLETE') return;

        preparing = false;
        preparationProgress = null;
        const failedCount = Array.isArray(data.failed) ? data.failed.length : 0;
        if (data.status === 'ready' && failedCount === 0) {
            writeReadiness({
                enabled: true,
                status: 'ready',
                version: data.version,
                pages: Number(data.total || 0),
                prepared_at: new Date().toISOString()
            });
            sessionStorage.setItem(refreshKey, '1');
            showToast(`${data.total} صفحات آف لائن استعمال کے لیے تیار ہیں۔`);
        } else {
            writeReadiness({
                enabled: false,
                status: 'partial',
                version: data.version,
                failed: failedCount,
                prepared_at: new Date().toISOString()
            });
            showToast(`${failedCount} صفحات محفوظ نہیں ہو سکے؛ دوبارہ کوشش کریں۔`);
        }
        await renderStatus();
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
    prepareButton.addEventListener('click', () => prepareWorkspace(false));

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
            localStorage.removeItem(readinessKey);
            localStorage.removeItem(ACTIVE_ACTOR_KEY);
        }
    });

    document.addEventListener('click', function (event) {
        const logout = event.target.closest('a[href*="logout"]');
        if (!logout) return;
        navigator.serviceWorker?.controller?.postMessage({ type: 'CLEAR_PRIVATE_DATA' });
        clearOperations();
        localStorage.removeItem(readinessKey);
        localStorage.removeItem(ACTIVE_ACTOR_KEY);
    });

    window.addEventListener('online', async function () {
        await renderStatus();
        await flush();
        if (readReadiness()?.enabled && !sessionStorage.getItem(refreshKey)) await prepareWorkspace(true);
    });
    window.addEventListener('offline', renderStatus);
    setInterval(flush, 30000);

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', handleWorkerMessage);
        navigator.serviceWorker.register('/service-worker.js?v=20260924f', { scope: '/', updateViaCache: 'none' })
            .then(() => navigator.serviceWorker.ready)
            .then((registration) => {
                const worker = navigator.serviceWorker.controller || registration.active;
                const activeActor = localStorage.getItem(ACTIVE_ACTOR_KEY);
                if (activeActor && activeActor !== actorKey) {
                    worker?.postMessage({ type: 'CLEAR_PRIVATE_DATA' });
                }
                localStorage.setItem(ACTIVE_ACTOR_KEY, actorKey);
                if (readReadiness()?.enabled) {
                    worker?.postMessage({ type: 'CACHE_CURRENT_PAGE', url: window.location.href });
                    if (navigator.onLine && !sessionStorage.getItem(refreshKey)) prepareWorkspace(true);
                }
            })
            .catch(function () { showToast('آف لائن سروس شروع نہیں ہو سکی۔'); });
    }

    renderStatus().then(flush);
})();
