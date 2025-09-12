// 2. Modify the tagsMenu object to always display content
const tagsMenu = {
        container: null, titleEl: null, listEl: null, closeBtn: null, currentQuery: null,
        ensureDom() {
                if (this.container) return;
                this.container = document.getElementById('tags-container');
                this.titleEl = document.getElementById('tags-title');
                this.listEl = document.getElementById('tags-list');

        },
        show(query) {
                this.ensureDom();
                const term = (query || '').trim();
                this.listEl.innerHTML = '';
                if (!term) {
                        this.titleEl.textContent = 'All Documents';
                        // Still populate with all documents when no query
                        const allItems = searchPanel.index || [];
                        this.populateList(allItems);
                        return;
                }
                const matches = (searchPanel.index || []).filter(entry => tagsMenuFilterMatch(entry, term));
                this.populateList(matches);
                this.currentQuery = term;

                // Update URL with tag
                updateUrlParameters({ tags: term });
        },
        populateList(items) {
                this.listEl.innerHTML = '';
                if (items.length === 0) {
                        const li = document.createElement('li');
                        li.innerHTML = '<span style="opacity:.6;font-size:.75em;">No results</span>';
                        this.listEl.appendChild(li);
                } else {
                        items.forEach(item => {
                                const li = document.createElement('li');
                                const a = document.createElement('a');
                                a.textContent = item.title;

                                // Extract page name from file path
                                const pageName = item.file.split('/').pop().replace('.md', '');

                                // Create URL with parameters for bookmarking
                                const tagName = this.currentQuery ||
                                        (item.tags && item.tags.length > 0 ? item.tags[0] : '');

                                if (tagName) {
                                        a.href = `#tags=${tagName}&page=${pageName}`;
                                } else if (pageName) {
                                        a.href = `#page=${pageName}`;
                                } else {
                                        a.href = `#tags=home&page=home`;
                                }

                                a.addEventListener('click', e => {
                                        e.preventDefault();
                                        loadMarkdown(item.file);
                                });

                                li.appendChild(a);
                                this.listEl.appendChild(li);
                        });
                }
        },
        // Modify hide to not actually hide, just clear selection
        hide() {
                this.ensureDom();
                this.currentQuery = null;
                this.titleEl.textContent = 'All Documents';

                // Show all documents instead of hiding
                const allItems = searchPanel.index || [];
                this.populateList(allItems);
        }
};


function tagsMenuFilterMatch(entry, term) {
        const q = term.toLowerCase();
        return entry.title.toLowerCase().includes(q) ||
                entry.file.toLowerCase().includes(q) ||
                (entry.tags && entry.tags.some(t => t.toLowerCase().includes(q)));
}
