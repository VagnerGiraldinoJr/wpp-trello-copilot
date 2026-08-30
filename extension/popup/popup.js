const DEFAULT_API_BASE_URL = 'http://localhost:8000';
const REQUEST_TIMEOUT_MS = 4000;

const dotEl = document.getElementById('wtc-status-dot');
const textEl = document.getElementById('wtc-status-text');
const urlEl = document.getElementById('wtc-api-url');
const testButton = document.getElementById('wtc-test-button');

async function getApiBaseUrl() {
    const stored = await chrome.storage.local.get('apiBaseUrl');
    return stored.apiBaseUrl || DEFAULT_API_BASE_URL;
}

async function fetchWithTimeout(resource, options = {}, timeoutMs = REQUEST_TIMEOUT_MS) {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), timeoutMs);

    try {
        return await fetch(resource, { ...options, signal: controller.signal });
    } finally {
        clearTimeout(timer);
    }
}

function setStatus(status, message) {
    dotEl.className = `wtc-dot wtc-dot-${status}`;
    textEl.textContent = message;
}

async function checkConnection() {
    const apiBaseUrl = await getApiBaseUrl();
    urlEl.textContent = apiBaseUrl;

    setStatus('unknown', 'Verificando conexão...');

    try {
        const response = await fetchWithTimeout(`${apiBaseUrl}/up`);

        if (response.ok) {
            setStatus('online', 'Conectado ao Laravel');
        } else {
            setStatus('offline', `Servidor respondeu com erro (${response.status})`);
        }
    } catch (error) {
        setStatus('offline', 'Não foi possível conectar ao servidor local');
    }
}

testButton.addEventListener('click', checkConnection);

checkConnection();
