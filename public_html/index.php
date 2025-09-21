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
		#tag-links aside ul,
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

		.project-header,
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

		nav,
		aside {
			padding: 0;
			/* remove padding */
			margin: 0;
			/* remove margin */
			border: none;
			/* remove border if you want flush fit */
		}

		nav a.button,
		aside a.button {
			display: block;
			/* full width block */
			width: 100%;
			/* fill parent */
			margin: 0;
			/* kill margins */
			border-radius: 0;
			/* no rounded corners unless wanted */
			border-left: none;
			/* flush to edge */
			border-right: none;
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
			<a href="/" class="button" title="Home">
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
		<aside id="tag-links">
			<section class="md-wrap" aria-label="Project Navigation" data-base="./docs">
				<div data-role="tree"></div>

				<script>
					(function() {
						const root = document.currentScript.closest('.md-wrap');
						const treeContainer = root.querySelector('[data-role="tree"]');
						// Hardcoded fallback array (replace with PHP include or fetch if needed)
						const fallbackIndex = < ? php include './scan.php'; ? > ;
						async function loadDocs() {
							let docsIndex = fallbackIndex;
							// Group by project
							const grouped = docsIndex.reduce((acc, item) => {
								const key = item.project || 'unknown';
								if (!acc[key]) acc[key] = [];
								acc[key].push(item);
								return acc;
							}, {});
							// Build the HTML tree using +variable+ style
							for (const project in grouped) {
								const items = grouped[project];
								// Header div
								const headerHTML = "<div class='project-header button'>" +
									"<svg class='icon icon-md'><use href='#i-book'></use></svg>" +
									"<span>" + project + "</span>" +
									"</div>";
								// Child links container
								let linksHTML = "<div class='project-links' style='display:none; width:100%;'>";
								items.forEach(item => {
									linksHTML += "<a href='" + item.src + "' class='button' style='display:block; width:100%; margin:0;'>" +
										"<svg class='icon icon-md'><use href='#i-md'></use></svg>" +
										"<span>" + item.title + "</span>" +
										"</a>";
								});
								linksHTML += "</div>";
								// Combine header + links
								const wrapperHTML = "<div class='project-wrapper'>" + headerHTML + linksHTML + "</div>";
								// Append to tree container
								const wrapperEl = document.createElement('div');
								wrapperEl.innerHTML = wrapperHTML;
								// Attach toggle behavior to header
								const headerEl = wrapperEl.querySelector('.project-header');
								const linksEl = wrapperEl.querySelector('.project-links');
								headerEl.addEventListener('click', () => {
									linksEl.style.display = linksEl.style.display === 'none' ? 'block' : 'none';
								});
								treeContainer.appendChild(wrapperEl);
							}
						}
						loadDocs();
					})();
				</script>
			</section>
		</aside>

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