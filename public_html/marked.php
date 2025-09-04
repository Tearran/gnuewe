<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Cards with Marked.js</title>
  <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
  <style>
    .card {
      border: 1px solid #bbb;
      border-radius: 8px;
      margin: 1em 0;
      padding: 1em;
      background: #fff;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
  </style>
</head>
<body>
  <section id="cards">

    <div class="card" data-markdown="cards/README.md"></div>
    <div class="card" data-html="include/contributors.html"></div>

    <div class="card" data-markdown="cards/definitions.md"></div>
    <div class="card" data-markdown="https://raw.githubusercontent.com/armbian/configng/refs/heads/main/DOCUMENTATION.md"></div>
  </section>
  
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
</body>
</html>