export function slugify(text, existing) {
        let s = text.toLowerCase()
                .replace(/[`~!@#$%^&*()+=<>?,./:;"'|\\[\]{}]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '')
                || 'section';
        let base = s;
        let i = 2;
        while (existing.has(s)) {
                s = base + '-' + i++;
        }
        return s;
}

export function escapeHtml(s) {
        return s.replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
}

export function parseHashParams() {
        const raw = location.hash.startsWith('#') ? decodeURIComponent(location.hash.slice(1)) : '';
        if (!raw) return null;
        const parts = raw.split('&').map(p => p.trim()).filter(Boolean);
        const obj = {};
        parts.forEach(p => {
                const i = p.indexOf('=');
                if (i === -1) return;
                const k = p.slice(0, i).toLowerCase();
                const v = p.slice(i + 1);
                obj[k] = v;
        });
        return Object.keys(obj).length ? obj : null;
}