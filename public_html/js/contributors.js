// Minimal static JSON renderer for index.html
// Loads exactly /json/contributors.json and renders into #contributors-container.
// No GitHub API fallback. Exposes window.renderContribsFromFile(path) for manual calls.

(function () {
        // Path to contributors JSON
        const DEFAULT_PATH = '/json/contributors.json';
        // ID of the container to render into
        const CONTAINER_ID = 'contributors-container';

        // Helper: Get element by ID
        function el(id) { return document.getElementById(id); }

        // Helper: Escape HTML for output safety
        function escapeHtml(s) {
                return String(s == null ? '' : s)
                        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        // Helper: Show a message in the container
        function showMessage(container, msg) {
                if (!container) return;
                container.innerHTML = `<p>${escapeHtml(msg)}</p>`;
        }

        // Build a contributor tile (<a class="contributor">...</a>) for a user object
        function buildTile(user) {
                const a = document.createElement('a');
                a.className = 'contributor';
                a.href = user.html_url || '#';
                a.target = '_blank';
                a.rel = 'noopener';
                a.setAttribute('aria-label', `View ${user.login}'s GitHub profile`);

                const img = document.createElement('img');
                img.src = user.avatar_url || '';
                img.alt = `Avatar of ${user.login || 'contributor'}`;
                img.loading = 'lazy';

                const name = document.createElement('p');
                name.className = 'name';
                name.textContent = user.login || '';

                const meta = document.createElement('p');
                meta.className = 'meta';
                meta.textContent = `${user.contributions || 0} commit${(user.contributions || 0) !== 1 ? 's' : ''}`;

                a.appendChild(img);
                a.appendChild(name);
                a.appendChild(meta);
                return a;
        }

        // Fetch the JSON and render contributor tiles (each wrapped in <div class="stack-item">)
        async function fetchAndRender(path) {
                const container = el(CONTAINER_ID);
                if (!container) {
                        // Container not found: warn, do nothing
                        console.warn('contribs: container not found; call window.renderContribsFromFile(path) after the DOM or include injection.');
                        return;
                }

                showMessage(container, 'Loading contributors…');

                try {
                        const res = await fetch(path, { cache: 'no-cache' });
                        if (!res.ok) {
                                showMessage(container, `Contributors JSON not found at ${path} (HTTP ${res.status})`);
                                return;
                        }
                        const data = await res.json();
                        if (!Array.isArray(data) || data.length === 0) {
                                showMessage(container, 'No contributors in JSON.');
                                return;
                        }

                        // Render grid: each user wrapped in <div class="stack-item">
                        container.innerHTML = '';
                        const grid = document.createElement('div');
                        grid.className = 'contributors-row';

                        data.forEach(u => {
                                // CREATE wrapper for each tile
                                const wrapper = document.createElement('div');
                                wrapper.className = 'icon';
                                // Put the tile inside the wrapper
                                wrapper.appendChild(buildTile(u));
                                // Add wrapper to grid
                                grid.appendChild(wrapper);
                        });

                        container.appendChild(grid);
                } catch (err) {
                        console.error('contribs fetch error', err);
                        showMessage(container, 'Error loading contributors JSON.');
                }
        }

        // Expose manual function: window.renderContribsFromFile(path)
        window.renderContribsFromFile = function (path) {
                const p = path || DEFAULT_PATH;
                return fetchAndRender(p);
        };

        // Auto-run on DOM ready if the container is present in the page
        if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => {
                        if (el(CONTAINER_ID)) window.renderContribsFromFile(DEFAULT_PATH);
                }, { once: true });
        } else {
                if (el(CONTAINER_ID)) window.renderContribsFromFile(DEFAULT_PATH);
        }
})();