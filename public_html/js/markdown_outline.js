/* Outline State */
const outlineState = {
        open: false,
        items: [],
        activeId: null,
        observer: null,
        headingSelector: 'h1, h2, h3, h4, h5, h6'
};

/**
 * Toggle the visibility of the outline panel.
 *
 * When opening, rebuilds the outline, updates the active item, and focuses the first `.outline-item`.
 * Updates `outlineState.open`, toggles the panel's `open` class, sets `aria-hidden` on the panel,
 * and toggles the `outline-open` class on document.body. If the panel element (`#outline-panel`) is
 * not present the function returns without side effects.
 *
 * @param {boolean} [open] - If provided, forces the panel to the given state; otherwise toggles it.
 */
function toggleOutline(open) {
        const panel = document.getElementById('outline-panel');
        if (!panel) return;
        const desired = (typeof open === 'boolean') ? open : !outlineState.open;
        outlineState.open = desired;
        panel.classList.toggle('open', desired);
        panel.setAttribute('aria-hidden', desired ? 'false' : 'true');
        document.body.classList.toggle('outline-open', desired);
        if (desired) {
                // Rebuild fresh in case content changed
                buildOutline();
                updateActiveOutlineItem();
                setTimeout(() => {
                        const first = panel.querySelector('.outline-item');
                        first?.focus();
                }, 50);
        }
}

/**
 * Wire up UI controls and keyboard shortcuts for the outline panel.
 *
 * Attaches click handlers to the "open-outline" button and the panel's
 * ".close-outline" control, and a global keydown handler that:
 * - closes the panel on Escape when open
 * - toggles the panel on Ctrl/Cmd + Shift + O (prevents the default)
 *
 * Assumes elements with IDs "open-outline" and "outline-panel" (and a
 * ".close-outline" child inside the panel) exist in the DOM.
 */
function setupOutlinePanel() {
        const openBtn = document.getElementById('open-outline');
        const panel = document.getElementById('outline-panel');
        const closeBtn = panel.querySelector('.close-outline');
        openBtn.addEventListener('click', () => toggleOutline());
        closeBtn.addEventListener('click', () => toggleOutline(false));
        document.addEventListener('keydown', e => {
                if (e.key === 'Escape' && outlineState.open) toggleOutline(false);
                // Keyboard shortcut: Ctrl+Shift+O
                if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key.toLowerCase() === 'o') {
                        e.preventDefault();
                        toggleOutline();
                }
        });
}