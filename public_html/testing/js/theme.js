let themeManifest = [];
let activeThemeId = null;

function clearThemeClasses() {
        [...document.body.classList].forEach(c => {
                if (c.startsWith('theme-')) document.body.classList.remove(c);
        });
}
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
function markActiveCard() {
        document.querySelectorAll('.theme-item').forEach(div => {
                div.classList.toggle('active', div.dataset.themeId === (activeThemeId || ''));
        });
}
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