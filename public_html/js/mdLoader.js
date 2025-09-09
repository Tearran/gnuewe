import { slugify } from './utils.js';
import { buildOutline, clearOutline, assignHeadingIds, setupHeadingObserver } from './outline.js';

const markdownCache = new Map();

export function loadMarkdown(filename) {
        const contentEl = document.getElementById('content');
        if (!filename) return;
        if (markdownCache.has(filename)) {
                contentEl.innerHTML = markdownCache.get(filename);
                enhanceLoadedContent();
                return;
        }
        contentEl.innerHTML = '<div class="loading-spinner"><span class="dot"></span><span class="dot"></span><span class="dot"></span>Loading…</div>';
        fetch(filename)
                .then(r => {
                        if (!r.ok) throw new Error('Not found');
                        return r.text();
                })
                .then(text => {
                        const html = marked.parse(text);
                        markdownCache.set(filename, html);
                        contentEl.innerHTML = html;
                        enhanceLoadedContent();
                })
                .catch(() => {
                        contentEl.innerHTML = '<p>Could not load ' + filename + '.</p>';
                        clearOutline();
                });
}

export function enhanceLoadedContent() {
        assignHeadingIds();
        buildOutline();
        setupHeadingObserver();
}