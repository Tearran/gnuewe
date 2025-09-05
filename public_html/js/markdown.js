// Utility: load file as text (Markdown or HTML)
async function loadText(url) {
        const res = await fetch(url);
        if (!res.ok) throw new Error(`Failed to load: ${url}`);
        return await res.text();
}

// Process all cards
document.querySelectorAll('.card').forEach(async (card) => {
        try {
                if (card.dataset.markdown) {
                        const md = await loadText(card.dataset.markdown);
                        card.innerHTML = marked.parse(md);
                } else if (card.dataset.html) {
                        card.innerHTML = await loadText(card.dataset.html);
                }
        } catch (e) {
                card.innerHTML = `<pre style="color:red;">${e.message}</pre>`;
        }
});