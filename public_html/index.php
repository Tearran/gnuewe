<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>GNU EWE</title>
	<style>
		:root {
			--color-bg: #fff;
			--color-text: #000;
			--color-header-bg: #222;
			--color-header-text: #fff;
			--color-nav-bg: #f3f3f3;
			--color-main-bg: #fff;
			--color-aside-bg: #fafafa;
			--color-border: #ccc;
			--color-btn-bg: #fff;
			--color-btn-hover: #0077cc;
			--color-btn-text: #444;
			--icon-stroke-width: 1.6;
			--icon-transition: 120ms;
		}

		body.dark-mode {
			--color-bg: #181a1b;
			--color-text: #ececec;
			--color-header-bg: #23272e;
			--color-header-text: #f6f6f6;
			--color-nav-bg: #23272e;
			--color-main-bg: #181a1b;
			--color-aside-bg: #1e2124;
			--color-border: #333a41;
			--color-btn-bg: #23272e;
			--color-btn-hover: #58aaff;
			--color-btn-text: #ececec;
			--icon-color-muted: #888b92;
			--icon-color-danger: #ff7675;
			--icon-color-warn: #ffe066;
			--icon-color-success: #2ecc71;
			--color-link: #41aaff;
			--color-link-hover: #82cfff;
			--color-link-active: #1a8cff;
			--color-link-visited: #cabfff;
			--color-link-focus: #80e1ff;
		}

		html,
		body {
			margin: 0;
		}

		body {
			font-family: sans-serif;
			background: var(--color-bg);
			color: var(--color-text);
		}

		a {
			color: var(--color-link);
			text-decoration: none;
			transition: color 120ms;
		}

		a:hover,
		a:focus {
			color: var(--color-link-hover);
		}

		a:active {
			color: var(--color-link-active);
			text-decoration: underline;
		}

		a:visited {
			color: var(--color-link-visited);
		}

		a:focus-visible {
			outline: 2px solid var(--color-link-focus);
			outline-offset: 2px;
		}

		header {
			display: flex;
			justify-content: space-between;
			padding: 0.5rem 1rem;
			background: var(--color-bg);
			color: var(--color-text);
		}

		.actions {
			display: flex;
			gap: 0.5rem;
		}

		a.button {
			display: flex;
			align-items: center;
			gap: 0.5rem;
			padding: 0.4rem 0.8rem;
			text-decoration: none;
			background: var(--color-btn-bg);
			border: 1px solid var(--color-border);
			border-radius: 0.4rem;
			color: var(--color-btn-text);
			cursor: pointer;
			font-size: 0.9rem;
			transition: background 0.2s, border-color 0.2s, color 0.2s;
		}

		a.button:hover {
			background: var(--color-btn-hover);
			border-color: var(--color-btn-hover);
			color: #fff;
		}

		.layout {
			display: flex;
			flex-direction: row;
			min-height: 100vh;
		}

		nav {
			flex: 0 0 250px;
			padding: 1rem;
			border: 1px solid var(--color-border);
			background: var(--color-bg);
		}
		
		#tool-links,
		#sources-links,
		aside ul,
		nav ul {
			list-style: none;
			padding: 0;
			margin: 0;
		}

		nav li {
			margin: 0.25rem 0;
		}

		main {
			flex: 1;
			border: 1px solid var(--color-border);
			background: var(--color-main-bg);
			padding: 0;
		}

		.container,
		.page,
		article {
			padding: 1rem;
		}

		aside {
			flex: 0 0 250px;
			padding: 1rem;
			border: 1px solid var(--color-border);
			background: var(--color-bg);
		}

		footer {
			padding: 1rem;
			border-top: 1px solid var(--color-border);
			text-align: center;
		}

		svg.icon {
			width: 1em;
			height: 1em;
			display: inline-block;
			vertical-align: middle;
			flex-shrink: 0;
			color: currentColor;
			stroke: currentColor;
			stroke-linecap: round;
			stroke-linejoin: round;
			fill: none;
			vector-effect: non-scaling-stroke;
			transition: color var(--icon-transition), stroke var(--icon-transition),
				fill var(--icon-transition), transform var(--icon-transition),
				opacity var(--icon-transition);
		}

		svg.icon-md {
			width: 1.5em;
			height: 1.5em;
		}

		svg.icon-lg {
			width: 2em;
			height: 2em;
		}

		/* Mobile layout */
		@media (max-width: 768px) {
			.layout {
				flex-direction: column;
			}

			nav,
			aside {
				flex: none;
				order: 0;
			}

			main {
				order: 2;
			}
		}
	</style>
</head>

<body>
<?php include "./images/icons.svg"; ?>
	<header>
		<div class="actions">
			<a href="javascript:void(0);" class="button" onclick="togglePanel('#tool-links')" title="Toggle Tools">
			<svg class="icon icon-lg">
					<use href="#i-grid"></use>
				</svg>
			</a>
			<a href="/" class="button"  title="Home">
				<img src="images/ewe_hat.svg" width="48" height="48" alt="GNU EWE logo" loading="lazy" decoding="async">
			</a>
		</div>

		<div class="actions">
			<a href="javascript:void(0);" class="button" onclick="toggleDarkMode()" title="Dark Mode">
				<svg class="icon  icon-lg">
					<use href="#i-light"></use>
				</svg>
			</a>

			<a href="javascript:void(0);" class="button" onclick="togglePanel('#tag-links')" title="Toggle Outline">
				<svg class="icon  icon-lg">
					<use href="#i-book"></use>
				</svg>
			</a>
			<a href="javascript:void(0);" class="button" onclick="togglePanel('#sources-links')" title="Toggle sources">
				<svg class="icon icon-lg">
					<use href="#i-download-box"></use>
				</svg>

			</a>
		</div>
	</header>

	<div class="layout">
		<nav id="tool-links" aria-label="Site Navigation" hidden>
			<a href="?app=PseudoShell" class="button">
				<svg class="icon icon-md">
					<use href="#i-terminal"></use>
				</svg>
				PseudoShell - Bash Sim</a>
			<a href="?app=MiniPen" class="button">
				<svg class="icon icon-md">
					<use href="#i-html5"></use>
				</svg>
				MiniPen - HTML Sadbox</a>
			<a href="?app=MiniMD" class="button">
				<svg class="icon icon-md">
					<use href="#i-md"></use>
				</svg>
				MiniMD - MarkDown Editor</a>
			<a href="?app=MiniSVG" class="button">
				<svg class="icon icon-md">
					<use href="#i-info"></use>
				</svg>
				MiniSVC - SVG Icon Paths</a>
		</nav>

		<main>
			<?php
		$page = $_GET['app'] ?? 'MiniMD';

		switch ($page) {
			case 'home':
				include "./MiniMD.html";
				break;
			case 'markdown':
				include "./markdown.php";
				break;
			case 'html':
				include "./playhtml.html";
				break;
			case 'scan':
				include "./scan.php";
				break;
			case 'PseudoShell':
				include "./PseudoShell.html";
				break;
			case 'MiniMD':
				include "./MiniMD.html";
				break;
			case 'MiniPen':
				include "./MiniPen.html";
				break;
			case 'MiniSVG':
				include "./MiniSVG.html";
				break;				
			case 'md':
				include "./playmd.html";
				break;
			default:
					echo "<h1>404<h1><p>Page not found.</p>";
		}
		?>
		</main>

		<aside id="sources-links" hidden>
			<a href="https://github.com/Tearran/gnuewe" class="button" target="_blank" rel="noopener">
				<svg class="icon icon-lg">
					<use href="#i-github"></use>
				</svg>
				<span>Github</span>
			</a>

			<a href="https://codepen.io/Tearran" class="button" target="_blank" rel="noopener">
				<svg class="icon icon-lg">
					<use href="#i-codepen"></use>
				</svg>
				<span>CodePen</span>
			</a>
		</aside>
		<aside id="tag-links">
			<section id="nav-tree"></section>
		</aside>

<style>
  .tree ul {
    list-style: none;
    padding-left: 1rem;
    margin: 0;
  }
  .tree li {
    margin: 0.2rem 0;
    cursor: pointer;
  }
  .toggle {
    font-weight: bold;
    margin-right: 0.5rem;
    cursor: pointer;
  }
  .hidden {
    display: none;
  }
</style>

<script>
(async function() {
  // Fallback data (hardcoded)
  const fallbackData = [
    { "title": "Reference", "tags": ["docs","reference"], "src": "?src=docs/reference.md" },
    { "title": "Home", "tags": ["home","docs"], "src": "?src=docs/README.md" },
    { "title": "Icons", "tags": ["docs","icons"], "src": "?src=docs/icons.md" },
    { "title": "Example", "tags": ["docs","example"], "src": "?src=docs/markdown/example.md" }
  ];

  // Load JSON from scan.php or fallback
  async function loadData() {
    try {
//      const response = await fetch("scan.php");
      const response = await fetch("");
      if (!response.ok) throw new Error("Network error");
      return await response.json();
    } catch (err) {
      console.warn("Using fallback data:", err.message);
      return fallbackData;
    }
  }

  const items = await loadData();

  // Build tag → items map
  const tagMap = {};
  for (const item of items) {
    for (const tag of item.tags || []) {
      if (!tagMap[tag]) tagMap[tag] = [];
      tagMap[tag].push(item);
    }
  }

  // Render tree using HTML-like template strings
  function renderTree(container, map) {
    container.classList.add("tree");

    const html = `
      <ul>
        ${Object.keys(map).sort().map(tag => {
          const children = map[tag].map(child => `<li><a href="${child.src}">${child.title}</a></li>`).join("");
          return `
            <li>
              <span class="toggle">+</span>
              <span class="label">${tag}</span>
              <ul class="hidden">${children}</ul>
            </li>
          `;
        }).join("")}
      </ul>
    `;

    container.innerHTML = html;

    // Add toggle behavior
    container.querySelectorAll(".toggle").forEach(toggle => {
      toggle.addEventListener("click", () => {
        const childUl = toggle.parentElement.querySelector("ul");
        const hidden = childUl.classList.toggle("hidden");
        toggle.textContent = hidden ? "+" : "-";
      });
    });
  }

  renderTree(document.getElementById("nav-tree"), tagMap);
})();
</script>


	</div>

	<footer id="site-footer" class="site-footer" role="contentinfo">
		<div id="footer-about" class="footer-loading">
			<p>Powered by boredom, caffeine, and questionable time management</p>
			<p>© 2025 Joey Turner</p>
		</div>
		<div id="footer-legal" class="footer-loading">
			All trademarks are the property of their respective owners. All rights reserved.
			Content is provided "as is" and is for informational purposes only. Use at your own risk.</div>
	</footer>


	<script>
	// Example: load a markdown doc on page load
		window.addEventListener("DOMContentLoaded", () => {
			if (typeof loadMarkdownFromURL === 'function') {
				loadMarkdownFromURL("docs/README.md");
			}
		});

	// Or bind links in your nav
	document.querySelectorAll("nav a[data-md]").forEach(a => {
			a.addEventListener("click", e => {
			e.preventDefault();
			loadMarkdownFromURL(a.getAttribute("href"));
		});
	});
	</script>

	<script>
/* -----------------------------
   PANEL TOGGLE
   ----------------------------- */
function togglePanel(selector) {
    const el = document.querySelector(selector);
    if (!el) return;
    el.hidden = !el.hidden;
}

/* -----------------------------
   DARK MODE TOGGLE
   ----------------------------- */
(function initDarkMode() {
    const darkPref = localStorage.getItem("dark-mode");
    if (darkPref === "true") {
        document.body.classList.add("dark-mode");
    }

    window.toggleDarkMode = function() {
        const isDark = document.body.classList.toggle("dark-mode");
        localStorage.setItem("dark-mode", isDark ? "true" : "false");
    };
})();
</script>


</body>

</html>