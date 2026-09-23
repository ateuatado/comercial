/**
 * Acrescenta o token CSRF do CodeIgniter às requisições AJAX mutáveis feitas
 * para a mesma origem. Formulários HTML continuam usando csrf_field().
 */
(function () {
    'use strict';

    const meta = document.getElementById('spiv-csrf');
    if (!meta) {
        return;
    }

    const headerName = meta.getAttribute('name');
    const token = meta.getAttribute('content');
    const safeMethods = new Set(['GET', 'HEAD', 'OPTIONS']);

    function isSameOrigin(url) {
        try {
            return new URL(url, window.location.href).origin === window.location.origin;
        } catch (error) {
            return false;
        }
    }

    if (window.fetch) {
        const originalFetch = window.fetch.bind(window);

        window.fetch = function (input, init) {
            const requestInit = Object.assign({}, init || {});
            const method = String(
                requestInit.method || (input instanceof Request ? input.method : 'GET')
            ).toUpperCase();
            const url = input instanceof Request ? input.url : String(input);

            if (!safeMethods.has(method) && isSameOrigin(url)) {
                const headers = new Headers(input instanceof Request ? input.headers : undefined);
                new Headers(requestInit.headers || {}).forEach((value, name) => headers.set(name, value));
                headers.set(headerName, token);
                requestInit.headers = headers;
            }

            return originalFetch(input, requestInit);
        };
    }

    if (window.XMLHttpRequest) {
        const originalOpen = XMLHttpRequest.prototype.open;
        const originalSend = XMLHttpRequest.prototype.send;

        XMLHttpRequest.prototype.open = function (method, url) {
            this.spivCsrfRequired = !safeMethods.has(String(method).toUpperCase()) && isSameOrigin(url);
            return originalOpen.apply(this, arguments);
        };

        XMLHttpRequest.prototype.send = function () {
            if (this.spivCsrfRequired) {
                this.setRequestHeader(headerName, token);
            }
            return originalSend.apply(this, arguments);
        };
    }
})();
