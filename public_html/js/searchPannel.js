import { loadMarkdown } from './markdownLoader.js';
import { escapeHtml } from './utils.js';

export const searchPanel = {
        root: null, input: null, results: null, openBtn: null, closeBtn: null,
        isOpen: false, activeIndex: -1, flatResults: [], index: [],
        open() { /* ...as in your code... */ },
        close() { /* ... */ },
        loadIndex(jsonArray) { /* ... */ },
        search(term) { /* ... */ },
        render(term) { /* ... */ },
        applyActive() { /* ... */ },
        move(delta) { /* ... */ },
        openActive() { /* ... */ }
};

// Most inner methods can be copy-pasted as-is from your code, with imports for loadMarkdown and escapeHtml.