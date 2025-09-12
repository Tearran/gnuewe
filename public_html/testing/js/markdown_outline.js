
/* Outline State */
const outlineState = {
        open: false,
        items: [],
        activeId: null,
        observer: null,
        headingSelector: 'h1, h2, h3, h4, h5, h6'
};

/* toggle Outline panel controls */
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

/* Outline panel controls */
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