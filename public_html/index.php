<!doctype html>
<html lang="en">

<head>
<style>
.code-block-wrapper {
    position: relative;
    margin-bottom: 1.5em;
}
.copy-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    background: #467fcf;
    color: #fff;
    border: none;
    border-radius: 4px;
    padding: 0.3em 0.7em;
    font-size: 0.9em;
    cursor: pointer;
    z-index: 2;
    opacity: 0.8;
    transition: opacity 0.2s;
}
.copy-btn:hover {
    opacity: 1;
}
pre {
    margin: 0;
}
</style>

<!-- Place this script at the end of the body or after your code blocks -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('pre > code').forEach(function(code) {
        // Create wrapper
        const wrapper = document.createElement('div');
        wrapper.className = 'code-block-wrapper';

        // Insert wrapper before <pre>
        const pre = code.parentElement;
        pre.parentNode.insertBefore(wrapper, pre);

        // Move <pre> into wrapper
        wrapper.appendChild(pre);

        // Create button
        const btn = document.createElement('button');
        btn.className = 'copy-btn';
        btn.textContent = 'Copy';

        btn.onclick = function () {
            const codeText = code.innerText || code.textContent;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(codeText).then(function() {
                    btn.textContent = "Copied!";
                    setTimeout(() => btn.textContent = "Copy", 1200);
                });
            } else {
                // Fallback for older browsers
                const textarea = document.createElement("textarea");
                textarea.value = codeText;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand("copy");
                document.body.removeChild(textarea);
                btn.textContent = "Copied!";
                setTimeout(() => btn.textContent = "Copy", 1200);
            }
        };

        // Add button to wrapper
        wrapper.appendChild(btn);
    });
});
</script>


        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>GNU ewe</title>
        <meta name="description" content="A hobby FOSS SPA for bash, html, and a simple python3 web server">
        <meta name="author" content="Tearran (FOSS project)">
        <meta name="robots" content="index, follow">
        <link rel="icon" type="image/svg+xml" href="/favicon.ico">
        <meta name="theme-color" content="#0366d6">
        <link rel="stylesheet" href="/css/footer.css">
        <link rel="stylesheet" href="/css/palette.css">
        <link rel="stylesheet" href="/css/styles.css">
</head>

<body>
        <?php include 'include/header.html'; ?>

        <?php include 'include/nav.html'; ?>

       <main class="site-main">
                <?php include 'include/home.html'; ?>
                <?php include 'include/configng.html'; ?>
                <?php include 'include/downloads.html'; ?>
                <section id="about" class="page-section" aria-hidden="true" hidden>
                <section id="cards">

                        <div class="card" data-markdown="cards/README.md"></div>
                        <div class="card" data-html="include/contributors.html"></div>
                        <div class="card" data-markdown="cards/definitions.md"></div>
                        <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
                        <script>
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
                        </script>
                </section>
        </main>

        <?php include 'include/footer.html'; ?>

        <script src="/js/page.js"></script>
        <script src="/js/loader.js"></script>

</body>

</html>