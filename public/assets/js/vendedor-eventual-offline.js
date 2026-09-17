(function () {
    'use strict';

    const form = document.getElementById('opportunity-form');
    if (!form) {
        return;
    }

    const DB_NAME = 'spiv-vendedor-eventual';
    const STORE_NAME = 'pending-opportunities';
    const MAX_AGE_MS = 24 * 60 * 60 * 1000;
    const status = document.getElementById('offline-status');
    const submit = document.getElementById('opportunity-submit');
    const correlation = document.getElementById('correlation-id');
    const occurredAt = document.getElementById('occurred-at');
    const submissionMode = document.getElementById('submission-mode');
    const currentUserId = form.dataset.userId;

    function uuid() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID();
        }

        const bytes = new Uint8Array(16);
        window.crypto.getRandomValues(bytes);
        bytes[6] = (bytes[6] & 0x0f) | 0x40;
        bytes[8] = (bytes[8] & 0x3f) | 0x80;
        const hex = Array.from(bytes, (byte) => byte.toString(16).padStart(2, '0')).join('');
        return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
    }

    function prepareIdentifiers(includeOccurredAt) {
        if (!correlation.value) {
            correlation.value = uuid();
        }
        if (includeOccurredAt && !occurredAt.value) {
            occurredAt.value = new Date().toISOString();
        }
    }

    function showStatus(message, kind) {
        status.className = `alert alert-${kind || 'info'}`;
        status.textContent = message;
    }

    function openDatabase() {
        return new Promise((resolve, reject) => {
            if (!window.indexedDB) {
                reject(new Error('Armazenamento offline indisponível.'));
                return;
            }

            const request = window.indexedDB.open(DB_NAME, 1);
            request.onupgradeneeded = () => {
                const db = request.result;
                if (!db.objectStoreNames.contains(STORE_NAME)) {
                    db.createObjectStore(STORE_NAME, { keyPath: 'correlation_id' });
                }
            };
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async function withStore(mode, callback) {
        const db = await openDatabase();
        return new Promise((resolve, reject) => {
            const transaction = db.transaction(STORE_NAME, mode);
            const store = transaction.objectStore(STORE_NAME);
            callback(store);
            transaction.oncomplete = () => {
                db.close();
                resolve();
            };
            transaction.onerror = () => {
                db.close();
                reject(transaction.error);
            };
        });
    }

    async function savePending(record) {
        await withStore('readwrite', (store) => store.put(record));
    }

    async function deletePending(id) {
        await withStore('readwrite', (store) => store.delete(id));
    }

    async function pendingRecords() {
        const db = await openDatabase();
        return new Promise((resolve, reject) => {
            const transaction = db.transaction(STORE_NAME, 'readonly');
            const request = transaction.objectStore(STORE_NAME).getAll();
            request.onsuccess = () => resolve(request.result || []);
            request.onerror = () => reject(request.error);
            transaction.oncomplete = () => db.close();
        });
    }

    function payloadFromForm(mode) {
        prepareIdentifiers(true);
        const payload = Object.fromEntries(new FormData(form).entries());
        delete payload.csrf_test_name;
        payload.submission_mode = mode;

        return {
            correlation_id: payload.correlation_id,
            occurred_at: payload.occurred_at,
            submission_mode: payload.submission_mode,
            cnpj: payload.cnpj,
            channel: payload.channel,
            contact_context: payload.contact_context,
            cnpj_confirmed: payload.cnpj_confirmed,
            endpoint: form.action,
            owner_user_id: currentUserId,
            queued_at: new Date().toISOString(),
        };
    }

    async function send(record) {
        const body = new URLSearchParams();
        ['correlation_id', 'occurred_at', 'submission_mode', 'cnpj', 'channel', 'contact_context', 'cnpj_confirmed']
            .forEach((key) => body.set(key, record[key] || ''));

        const response = await window.fetch(record.endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: body.toString(),
        });

        const contentType = response.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
            throw new Error(response.redirected ? 'Sua sessão expirou. Entre novamente para sincronizar.' : 'Resposta inesperada do servidor.');
        }

        const result = await response.json();
        if (!response.ok || !result.ok) {
            const error = new Error(result.message || 'Não foi possível registrar a oportunidade.');
            error.validation = response.status >= 400 && response.status < 500;
            throw error;
        }

        return result;
    }

    async function queueCurrentForm() {
        const record = payloadFromForm('offline');
        await savePending(record);
        form.reset();
        correlation.value = '';
        occurredAt.value = '';
        submissionMode.value = 'online';
        prepareIdentifiers(false);
        showStatus('Registro salvo neste dispositivo. Ele será sincronizado automaticamente quando a conexão retornar.', 'warning');
    }

    async function syncPending() {
        if (!navigator.onLine) {
            return;
        }

        const records = await pendingRecords();
        if (records.length === 0) {
            return;
        }

        let synchronized = 0;
        let expired = 0;
        for (const record of records) {
            if (Date.now() - new Date(record.queued_at).getTime() > MAX_AGE_MS) {
                await deletePending(record.correlation_id);
                expired++;
                continue;
            }
            if (String(record.owner_user_id) !== currentUserId) {
                continue;
            }

            try {
                record.submission_mode = 'offline';
                await send(record);
                await deletePending(record.correlation_id);
                synchronized++;
            } catch (error) {
                if (error.validation) {
                    showStatus(`Registro pendente requer revisão: ${error.message}`, 'danger');
                }
                break;
            }
        }

        if (synchronized > 0) {
            showStatus(`${synchronized} registro(s) offline sincronizado(s) com sucesso.`, 'success');
        } else if (expired > 0) {
            showStatus(`${expired} registro(s) local(is) expirado(s) foi/foram removido(s) após 24 horas.`, 'secondary');
        }
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        prepareIdentifiers(true);
        submit.disabled = true;
        submissionMode.value = navigator.onLine ? 'online' : 'offline';

        try {
            if (!navigator.onLine) {
                await queueCurrentForm();
                return;
            }

            const result = await send(payloadFromForm('online'));
            window.location.assign(result.url);
        } catch (error) {
            if (error.validation) {
                showStatus(error.message, 'danger');
            } else {
                try {
                    await queueCurrentForm();
                    showStatus(`${error.message} O registro foi mantido neste dispositivo para nova tentativa.`, 'warning');
                } catch (storageError) {
                    showStatus('Sem conexão e sem armazenamento offline disponível. Os dados continuam no formulário; não feche esta página.', 'danger');
                }
            }
        } finally {
            submit.disabled = false;
        }
    });

    window.addEventListener('online', () => syncPending().catch(() => {}));
    prepareIdentifiers(false);
    syncPending().catch(() => {});
})();
