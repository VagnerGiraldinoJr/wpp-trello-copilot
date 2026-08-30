(() => {
    'use strict';

    const DEFAULT_API_BASE_URL = 'http://localhost:8000';
    const SYNC_ENDPOINT = '/api/whatsapp/sync';
    const PENDING_ENDPOINT = '/api/whatsapp/pending-messages';
    const REQUEST_TIMEOUT_MS = 8000;
    const PENDING_POLL_INTERVAL_MS = 10000;
    const CONVERSATION_OPEN_DELAY_MS = 1200;
    const BETWEEN_CONVERSATIONS_DELAY_MS = 600;

    const state = {
        syncing: false,
        pendingPollTimer: null,
    };

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

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

    // ---------------------------------------------------------------------
    // WhatsApp Web DOM helpers
    // (selectors follow WhatsApp Web's current markup and may need updates
    // if WhatsApp changes its front-end)
    // ---------------------------------------------------------------------

    // WhatsApp Web has changed the chat list markup a few times over the
    // years (role="listitem" -> data-testid="cell-frame-container" -> ...).
    // Try known selectors in order and use the first one that matches.
    const CONVERSATION_ITEM_SELECTORS = [
        '[data-testid="cell-frame-container"]',
        '#pane-side [role="row"]',
        'div[role="listitem"]',
    ];

    const IGNORED_CONVERSATION_NAMES = new Set(['Archived', 'Arquivadas', 'Arquivadas ']);

    function getConversationItems() {
        for (const selector of CONVERSATION_ITEM_SELECTORS) {
            const items = document.querySelectorAll(selector);

            if (items.length > 0) {
                return Array.from(items).filter((item) => !IGNORED_CONVERSATION_NAMES.has(extractContactNameFromItem(item)));
            }
        }

        return [];
    }

    function getActiveChatHeaderName() {
        const header = document.querySelector('#main header') || document.querySelector('header');

        if (!header) {
            return null;
        }

        const titledSpan = header.querySelector('span[title]');

        return titledSpan ? titledSpan.getAttribute('title').trim() : null;
    }

    async function waitForActiveChatToChange(previousHeaderName, timeoutMs) {
        const start = Date.now();

        while (Date.now() - start < timeoutMs) {
            const current = getActiveChatHeaderName();

            if (current && current !== previousHeaderName) {
                return current;
            }

            await sleep(150);
        }

        return null;
    }

    function extractContactNameFromItem(item) {
        const titledSpan = item.querySelector('span[title]');
        if (titledSpan) {
            return titledSpan.getAttribute('title').trim();
        }

        return item.innerText.split('\n')[0]?.trim() || 'Desconhecido';
    }

    function parseWhatsAppTimestamp(rawMeta) {
        // rawMeta looks like: "[10:32, 29/08/2026] John Doe: "
        const match = rawMeta && rawMeta.match(/\[(\d{1,2}):(\d{2}),\s*(\d{1,2})\/(\d{1,2})\/(\d{4})\]/);

        if (!match) {
            return null;
        }

        const [, hour, minute, day, month, year] = match;
        const iso = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}T${hour.padStart(2, '0')}:${minute}:00`;

        return iso;
    }

    function extractMessagesFromActiveChat() {
        const messageEls = document.querySelectorAll('div[data-pre-plain-text]');

        return Array.from(messageEls)
            .map((el) => {
                const meta = el.getAttribute('data-pre-plain-text') || '';
                const textEl = el.querySelector('span.selectable-text') || el;
                const body = textEl.innerText.trim();

                return {
                    body,
                    sent_at: parseWhatsAppTimestamp(meta),
                };
            })
            .filter((message) => message.body.length > 0);
    }

    function findComposerBox() {
        return document.querySelector('footer div[contenteditable="true"]');
    }

    function findSendButton() {
        return document.querySelector('footer button[aria-label="Enviar"], footer span[data-icon="send"]');
    }

    async function typeAndSendMessage(text) {
        const composer = findComposerBox();

        if (!composer) {
            console.warn('[wpp-trello-copilot] Campo de mensagem não encontrado.');
            return false;
        }

        composer.focus();
        document.execCommand('insertText', false, text);
        composer.dispatchEvent(new InputEvent('input', { bubbles: true }));

        await sleep(200);

        const sendButton = findSendButton();

        if (sendButton) {
            sendButton.click();
            return true;
        }

        composer.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', code: 'Enter', bubbles: true }));

        return true;
    }

    function findConversationItemByContactName(name) {
        return getConversationItems().find((item) => extractContactNameFromItem(item) === name);
    }

    // ---------------------------------------------------------------------
    // Sync: WhatsApp -> Laravel
    // ---------------------------------------------------------------------

    async function syncConversation(item, previousHeaderName) {
        const contactName = extractContactNameFromItem(item);

        item.click();

        // WhatsApp Web's chat list is virtualized: the element we clicked may
        // already be detached from the DOM (e.g. the list re-sorted itself
        // after marking a chat as read), in which case .click() silently does
        // nothing and the previously open chat stays open. Confirm the header
        // actually changed before trusting the extracted messages - otherwise
        // we'd re-read the previous conversation and attribute it to the
        // wrong contact.
        const openedHeaderName = await waitForActiveChatToChange(previousHeaderName, CONVERSATION_OPEN_DELAY_MS);

        if (!openedHeaderName) {
            console.warn(`[wpp-trello-copilot] Não foi possível confirmar a abertura de "${contactName}"; pulando.`);
            return previousHeaderName;
        }

        const messages = extractMessagesFromActiveChat();

        if (messages.length === 0) {
            return openedHeaderName;
        }

        const apiBaseUrl = await getApiBaseUrl();

        try {
            await fetchWithTimeout(`${apiBaseUrl}${SYNC_ENDPOINT}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    contact: contactName,
                    messages,
                    synced_at: Date.now(),
                }),
            });
        } catch (error) {
            console.warn(`[wpp-trello-copilot] Falha ao sincronizar "${contactName}":`, error);
        }

        return openedHeaderName;
    }

    async function runSyncLoop() {
        // Snapshot the contact names up front, then re-query the live DOM on
        // every iteration instead of reusing element references, since the
        // virtualized list can detach/replace nodes as chats are opened.
        const targetNames = getConversationItems().map(extractContactNameFromItem);
        let lastHeaderName = getActiveChatHeaderName();

        for (const targetName of targetNames) {
            if (!state.syncing) {
                return;
            }

            const freshItem = getConversationItems().find((el) => extractContactNameFromItem(el) === targetName);

            if (!freshItem) {
                continue;
            }

            lastHeaderName = await syncConversation(freshItem, lastHeaderName);
            await sleep(BETWEEN_CONVERSATIONS_DELAY_MS);
        }
    }

    // ---------------------------------------------------------------------
    // Pending messages: Laravel -> WhatsApp
    // ---------------------------------------------------------------------

    async function pollPendingMessages() {
        if (!state.syncing) {
            return;
        }

        const apiBaseUrl = await getApiBaseUrl();

        let pending = [];

        try {
            const response = await fetchWithTimeout(`${apiBaseUrl}${PENDING_ENDPOINT}`);

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            pending = data.pending || [];
        } catch (error) {
            console.warn('[wpp-trello-copilot] Falha ao buscar mensagens pendentes:', error);
            return;
        }

        for (const item of pending) {
            const conversationItem = findConversationItemByContactName(item.contact);

            if (!conversationItem) {
                console.warn(`[wpp-trello-copilot] Contato "${item.contact}" não encontrado na lista de conversas.`);
                continue;
            }

            conversationItem.click();
            await sleep(CONVERSATION_OPEN_DELAY_MS);
            await typeAndSendMessage(item.message);
        }
    }

    function startPendingMessagesPolling() {
        stopPendingMessagesPolling();
        state.pendingPollTimer = setInterval(pollPendingMessages, PENDING_POLL_INTERVAL_MS);
    }

    function stopPendingMessagesPolling() {
        if (state.pendingPollTimer) {
            clearInterval(state.pendingPollTimer);
            state.pendingPollTimer = null;
        }
    }

    // ---------------------------------------------------------------------
    // Floating button UI
    // ---------------------------------------------------------------------

    function createFloatingButton() {
        const button = document.createElement('button');
        button.id = 'wtc-floating-button';
        button.innerHTML = '<span class="wtc-dot"></span><span class="wtc-label">▶ Iniciar Sincronização</span>';

        button.addEventListener('click', () => toggleSync(button));

        document.body.appendChild(button);

        return button;
    }

    function updateButtonState(button) {
        const label = button.querySelector('.wtc-label');

        if (state.syncing) {
            button.classList.add('wtc-active');
            label.textContent = '⏹ Parar';
        } else {
            button.classList.remove('wtc-active');
            label.textContent = '▶ Iniciar Sincronização';
        }
    }

    async function toggleSync(button) {
        state.syncing = !state.syncing;
        updateButtonState(button);

        await chrome.storage.local.set({ isSyncing: state.syncing });

        if (state.syncing) {
            startPendingMessagesPolling();
            runSyncLoop();
        } else {
            stopPendingMessagesPolling();
        }
    }

    // ---------------------------------------------------------------------
    // Init
    // ---------------------------------------------------------------------

    async function init() {
        const button = createFloatingButton();

        const stored = await chrome.storage.local.get('isSyncing');

        if (stored.isSyncing) {
            state.syncing = true;
            updateButtonState(button);
            startPendingMessagesPolling();
            runSyncLoop();
        }
    }

    init();
})();
