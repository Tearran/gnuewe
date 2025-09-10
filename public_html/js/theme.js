let themeManifest = [];
let activeThemeId = null;

/**
 * Remove all CSS classes on document.body that begin with "theme-".
 *
 * This mutates the document's body class list to clear any theme-related
 * classes (prefix "theme-"), restoring a non-themed state for class-based theming.
 */
function clearThemeClasses() {
        [...document.body.classList].forEach(c => {
                if (c.startsWith('theme-')) document.body.classList.remove(c);
        });
}
/**
 * Injects or clears runtime CSS custom properties into a dedicated <style id="dynamic-theme"> element.
 *
 * When called with a truthy object, each entry is written as a CSS custom property into a `:root` rule
 * (e.g. `{ '--bg': '#000' }` becomes `:root { --bg: #000; }`). If the style element does not exist it
 * will be created and appended to document.head. If `varsObj` is falsy, the style element's content is cleared.
 *
 * @param {Object<string, string>|null|undefined} varsObj - Mapping of CSS custom property names to values.
 */
function applyRuntimeVars(varsObj) {
        let styleEl = document.getElementById('dynamic-theme');
        if (!styleEl) {
                styleEl = document.createElement('style');
                styleEl.id = 'dynamic-theme';
                document.head.appendChild(styleEl);
        }
        if (!varsObj) {
                styleEl.textContent = '';
                return;
        }
        const lines = Object.entries(varsObj).map(([k, v]) => `${k}: ${v};`);
        styleEl.textContent = `:root { ${lines.join(' ')} }`;
}
/**
 * Apply a theme by id, or revert to the baseline when id is null or not found.
 *
 * Clears any existing theme-related body classes and runtime CSS variables, then:
 * - If a matching theme is found in `themeManifest`, adds its `class` (if present),
 *   applies its `vars` as runtime CSS variables (if present), and sets `activeThemeId`.
 * - If no match is found (including when `id` is `null`), clears the active theme and sets `activeThemeId` to `null`.
 *
 * Side effects:
 * - Modifies document.body.classList.
 * - Updates the dynamic style element that holds runtime CSS variables.
 * - Updates the global `activeThemeId` and refreshes the theme UI via `markActiveCard()`.
 *
 * @param {string|null} id - The theme identifier to apply, or `null` to revert to baseline.
 */
function applyTheme(id) {
        const item = themeManifest.find(t => t.id === id);
        applyRuntimeVars(null);
        clearThemeClasses();
        if (!item) {
                activeThemeId = null;
                markActiveCard();
                return;
        }
        if (item.class) document.body.classList.add(item.class);
        if (item.vars) applyRuntimeVars(item.vars);
        activeThemeId = id;
        markActiveCard();
}
/**
 * Update theme item elements to reflect which theme is currently active.
 *
 * Finds all elements with class "theme-item" and toggles their "active" class
 * based on whether the element's `data-theme-id` matches the global `activeThemeId`
 * (or the empty string when `activeThemeId` is null).
 */
function markActiveCard() {
        document.querySelectorAll('.theme-item').forEach(div => {
                div.classList.toggle('active', div.dataset.themeId === (activeThemeId || ''));
        });
}
/**
 * Build the theme selection UI inside the #themes-list container.
 *
 * Clears the container and creates a baseline card plus one card per entry in the global
 * `themeManifest`. Each card gets a data-theme-id, a "Use" button that calls `applyTheme(...)`,
 * and optional description/group metadata. After rendering, updates which card is visually
 * active by calling `markActiveCard()`.
 *
 * Side effects:
 * - Mutates the DOM under #themes-list.
 * - Attaches click handlers that invoke `applyTheme`.
 * - Reads the global `themeManifest`.
 */
function buildThemeCards() {
        const container = document.getElementById('themes-list');
        container.innerHTML = '';
        const baselineCard = document.createElement('div');
        baselineCard.className = 'theme-item';
        baselineCard.dataset.themeId = '';
        baselineCard.innerHTML = `
		<div class="theme-item-header">
		<strong>Baseline (Dark)</strong>
		<button class="apply-theme" type="button">Use</button>
		</div>
		<small>Default variables</small>`;
        baselineCard.querySelector('button.apply-theme').addEventListener('click', () => applyTheme(null));
        container.appendChild(baselineCard);
        themeManifest.forEach(t => {
                const div = document.createElement('div');
                div.className = 'theme-item';
                div.dataset.themeId = t.id;
                const desc = t.description ? `<small>${t.description}</small>` : '';
                const group = t.group ? `<small style="opacity:0.6;">Group: ${t.group}</small>` : '';
                div.innerHTML = `
            <div class="theme-item-header">
                <strong>${t.label || t.id}</strong>
                <button class="apply-theme" type="button">Use</button>
            </div>
            ${desc}
            ${group}`;
                div.querySelector('button.apply-theme').addEventListener('click', () => applyTheme(t.id));
                container.appendChild(div);
        });
        markActiveCard();
}
/**
 * Load the theme manifest from THEME_MANIFEST_URL, update global themeManifest, and rebuild the theme UI.
 *
 * Fetches the manifest URL and, if the response JSON is an array, assigns it to the global `themeManifest`.
 * In all cases (success or failure) the function calls `buildThemeCards()` to refresh the UI; on error it resets
 * `themeManifest` to an empty array before rebuilding.
 *
 * @return {Promise<void>} Resolves after the manifest is processed and `buildThemeCards()` has been called.
 */
function loadThemeManifest() {
        return fetch(THEME_MANIFEST_URL)
                .then(r => {
                        if (!r.ok) throw new Error('No theme manifest');
                        return r.json();
                })
                .then(json => {
                        themeManifest = Array.isArray(json) ? json : [];
                        buildThemeCards();
                })
                .catch(() => {
                        themeManifest = [];
                        buildThemeCards();
                });
}
/**
 * Initialize the theme dashboard UI: wire up open/close controls and keyboard handling.
 *
 * Attaches click handlers to the element with id "open-theme-dashboard" (toggles the dashboard)
 * and the element inside "#theme-dashboard" with class ".close-dashboard" (closes it). Also listens
 * for the Escape key to close the panel when open. The panel's visibility is driven by the
 * "open" CSS class and the "aria-hidden" attribute ("false" when open, "true" when closed).
 *
 * Requires:
 * - An element with id "open-theme-dashboard" (toggle button).
 * - An element with id "theme-dashboard" (panel).
 * - Inside the panel, an element with class "close-dashboard" (close button).
 *
 * Side effects:
 * - Adds/removes the "open" class on the panel.
 * - Sets the panel's "aria-hidden" attribute.
 * - Registers event listeners on the document and the specified controls.
 */
function setupThemeDashboard() {
        const openBtn = document.getElementById('open-theme-dashboard');
        const panel = document.getElementById('theme-dashboard');
        const closeBtn = panel.querySelector('.close-dashboard');
        function open() {
                panel.classList.add('open');
                panel.setAttribute('aria-hidden', 'false');
        }
        function close() {
                panel.classList.remove('open');
                panel.setAttribute('aria-hidden', 'true');
        }
        openBtn.addEventListener('click', () => {
                if (panel.classList.contains('open')) close(); else open();
        });
        closeBtn.addEventListener('click', close);
        document.addEventListener('keydown', e => {
                if (e.key === 'Escape' && panel.classList.contains('open')) close();
        });
}