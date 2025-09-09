import { slugify, escapeHtml } from './utils.js';

export const outlineState = {
        open: false,
        items: [],
        activeId: null,
        observer: null,
        headingSelector: 'h1, h2, h3, h4, h5, h6'
};

export function assignHeadingIds() {
        const contentEl = document.getElementById('content');
        const headings = contentEl.querySelectorAll(outlineState.headingSelector);
        const used = new Set();
        headings.forEach(h => {
                if (!h.id || used.has(h.id)) {
                        const txt = h.textContent.trim();
                        h.id = slugify(txt, used);
                }
                used.add(h.id);
        });
}

export function clearOutline() {
        outlineState.items = [];
        const oc = document.getElementById('outline-content');
        if (oc) oc.innerHTML = '<div class="outline-empty">No headings</div>';
}

export function buildOutline() {
        const contentEl = document.getElementById('content');
        const oc = document.getElementById('outline-content');
        if (!oc) return;
        const headings = [...contentEl.querySelectorAll(outlineState.headingSelector)];
        if (headings.length === 0) {
                clearOutline();
                return;
        }
        const items = headings.map(h => ({
                level: parseInt(h.tagName.slice(1), 10),
                id: h.id,
                text: h.textContent.trim()
        }));
        outlineState.items = items;
        // Build nested tree
        const root = [];
        const stack = [];
        items.forEach(item => {
                const node = { ...item, children: [] };
                while (stack.length && stack[stack.length - 1].level >= node.level) stack.pop();
                if (stack.length === 0) {
                        root.push(node);
                } else {
                        stack[stack.length - 1].children.push(node);
                }
                stack.push(node);
        });
        oc.innerHTML = '';
        if (root.length === 0) {
                clearOutline();
                return;
        }
        const frag = document.createDocumentFragment();
        root.forEach(n => frag.appendChild(renderOutlineNode(n)));
        oc.appendChild(frag);
}

function renderOutlineNode(node) {
        const wrapper = document.createElement('div');
        wrapper.style.marginLeft = ((node.level - 1) * 4) + 'px';

        const link = document.createElement('a');
        link.href = '#' + node.id;
        link.className = 'outline-item';
        link.dataset.targetId = node.id;
        link.dataset.level = node.level;
        link.innerHTML = '<span class="lvl">H' + node.level + ':</span>' + escapeHtml(node.text);

        link.addEventListener('click', e => {
                e.preventDefault();
                scrollToHeading(node.id);
        });

        wrapper.appendChild(link);

        if (node.children && node.children.length) {
                link.classList.add('has-children');
                const childrenContainer = document.createElement('div');
                childrenContainer.className = 'outline-children';
                node.children.forEach(ch => {
                        childrenContainer.appendChild(renderOutlineNode(ch));
                });
                wrapper.appendChild(childrenContainer);
        }
        return wrapper;
}

export function scrollToHeading(id) {
        const h = document.getElementById(id);
        if (!h) return;
        h.classList.add('current-heading-flash');
        h.scrollIntoView({ behavior: 'smooth', block: 'start' });
        outlineState.activeId = id;
        updateActiveOutlineItem();
        setTimeout(() => h.classList.remove('current-heading-flash'), 900);
        h.focus?.();
}

function updateActiveOutlineItem() {
        const oc = document.getElementById('outline-content');
        if (!oc) return;
        oc.querySelectorAll('.outline-item').forEach(a => {
                a.classList.toggle('active', a.dataset.targetId === outlineState.activeId);
        });
}

export function setupHeadingObserver() {
        if (outlineState.observer) {
                outlineState.observer.disconnect();
                outlineState.observer = null;
        }
        const headings = document.querySelectorAll('#content ' + outlineState.headingSelector);
        if (!headings.length) return;
        const opts = {
                root: null,
                rootMargin: '0px 0px -70% 0px',
                threshold: 0
        };
        outlineState.observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                        if (entry.isIntersecting) {
                                outlineState.activeId = entry.target.id;
                                updateActiveOutlineItem();
                        }
                });
        }, opts);
        headings.forEach(h => outlineState.observer.observe(h));
}