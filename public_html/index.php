<?php
$docsDir = __DIR__ . '/docs';

function listMdFiles($dir, $base = '') {
    $out = [];
    foreach (scandir($dir) as $f) {
        if ($f[0] === '.') continue;
        $p = "$dir/$f";
        $rel = ltrim("$base/$f", '/');
        if (is_dir($p)) $out[$f] = listMdFiles($p, $rel);
        elseif (preg_match('/\.md$/i', $f)) $out[$f] = $rel;
    }
    return $out;
}
function renderNav($files, $cur) {
    echo "<ul>";
    foreach ($files as $k => $v) {
        if (is_array($v)) {
            echo "<li><details><summary>$k</summary>";
            renderNav($v, $cur);
            echo "</details></li>";
        } else {
            $a = $v === $cur ? 'class="active"' : '';
            echo "<li><a $a href='?file=" . urlencode($v) . "'>$k</a></li>";
        }
    }
    echo "</ul>";
}
function safeMarkdown($md, &$outline = null) {
    $outlineItems = [];
    $md = preg_replace_callback('/^(#{1,6}) (.+)$/m', function($m) use (&$outlineItems) {
        $level = strlen($m[1]);
        $text = trim($m[2]);
        $id = strtolower(preg_replace('/[^a-z0-9]+/', '-', $text));
        $id = trim($id, '-');
        $outlineItems[] = ['level'=>$level,'text'=>$text,'id'=>$id];
        return "<h$level id=\"$id\">$text</h$level>";
    }, $md);
    $md = preg_replace_callback('/^(```|~~~)[ \t]*([\w-]*)[^\n]*\n([\s\S]*?)^\1[ \t]*$/m', function($m) {
        $c = htmlspecialchars($m[3]);
        $lang = htmlspecialchars($m[2]);
        return "<pre><code class=\"language-$lang\">$c</code></pre>";
    }, $md);
    $md = preg_replace_callback('/`([^`]+)`/', fn($m) => '<code>' . htmlspecialchars($m[1]) . '</code>', $md);
    $md = preg_replace('/^> ?(.*)$/m', '<blockquote>$1</blockquote>', $md);
    $md = preg_replace('/^\s*[-*+] (.*)$/m', '<li>$1</li>', $md);
    $md = preg_replace('/^\s*\d+\. (.*)$/m', '<li>$1</li>', $md);
    $md = preg_replace_callback('/(<li>.*<\/li>)+/s', function($m) {
        return (preg_match('/<li>\d/', $m[0]) ? "<ol>{$m[0]}</ol>" : "<ul>{$m[0]}</ul>");
    }, $md);
    $md = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $md);
    $md = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $md);
    $md = preg_replace('/!\[([^\]]*)\]\(([^)]+)\)/', '<img alt=\"$1\" src=\"$2\">', $md);
    $md = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href=\"$2\">$1</a>', $md);
    $md = preg_replace('/\n{2,}/', "\n\n", $md);
    $md = preg_replace('/(?:^|\n)([^\n<][^\n]*)\n/', "\n<p>$1</p>\n", $md);
    if (is_array($outline)) $outline = $outlineItems;
    return $md;
}
function renderOutline($outline) {
    if (!$outline) return "";
    $out = "<b>Outline</b><ul class='outline-list'>";
    foreach ($outline as $item) {
        $out .= "<li style='margin-left:" . (16*($item['level']-1)) . "px'><a href='#{$item['id']}'>{$item['text']}</a></li>";
    }
    $out .= "</ul>";
    return $out;
}
$files = listMdFiles($docsDir);
function pickFirst($arr) {
    foreach ($arr as $v) {
        if (is_array($v)) {
            $f = pickFirst($v); if ($f) return $f;
        } else return $v;
    }
    return null;
}
$currentFile = $_GET['file'] ?? pickFirst($files);
$fullPath = $currentFile ? realpath("$docsDir/$currentFile") : null;
$contentHtml = "<p>Select a page</p>";
$outlineArr = [];
$outlineHtml = "";
if ($fullPath && strpos($fullPath, realpath($docsDir)) === 0 && is_file($fullPath)) {
    $markdown = file_get_contents($fullPath);
    $contentHtml = safeMarkdown($markdown, $outlineArr);
    $outlineHtml = renderOutline($outlineArr);
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>GNU EWE</title>
	<style>
	/* =====================
       Theme Variables
       ===================== */
       :root {
        /* Main page background and text */
        --color-bg: #fff;
        --color-text: #000;
        /* Header colors */
        --color-header-bg: #222;
        --color-header-text: #fff;
        /* Section backgrounds */
        --color-nav-bg: #f3f3f3;
        --color-main-bg: #fff;
        --color-aside-bg: #fafafa;
        /* Borders */
        --color-border: #ccc;
        /* Buttons */
        --color-btn-bg: #fff;
        --color-btn-hover: #0077cc;
        --color-btn-text: #444;
        --icon-stroke-width: 1.6;
        --icon-transition: 120ms;
        --icon-color-base: var(--text-main);
        --icon-color-accent: var(--text-link);
        --icon-color-muted: var(--text-muted);
        --icon-color-danger: #ff4d4f;
        --icon-color-warn: #e0a210;
        --icon-color-success: #31c48d;
}

		/* =====================
       Dark Mode Overrides
       ===================== */
		body.dark-mode {
			--color-bg: #111;
			--color-text: #ddd;
			--color-header-bg: #000;
			--color-header-text: #fff;
			--color-nav-bg: #222;
			--color-main-bg: #111;
			--color-aside-bg: #1a1a1a;
			--color-border: #444;
			--color-btn-bg: #333;
			--color-btn-hover: #555;
			--color-btn-text: #fff;
		}

		/* =====================
       Base Body Styling
       ===================== */
		body {
			margin: 0;
			font-family: sans-serif;
			background: var(--color-bg);
			color: var(--color-text);
		}

		/* =====================
       Header / Top Bar
       ===================== */
		.action-bar {
			background: var(--color-nav-bg);
			color: var(--color-text);
			padding: 0.5rem 1rem;
			display: flex;
			align-items: center;
			justify-content: space-between;
		}

		#brand {
			font-weight: bold;
			/* Brand text */
		}

		/* =====================
       Action Buttons in Top Bar
       ===================== */
		#actions {
			display: flex;
			/* Always side by side */
			gap: 0.5rem;
			/* Spacing between buttons */
		}

		#actions button {
			background: var(--color-btn-bg);
			color: var(--color-btn-text);
			border: none;
			padding: 0.5rem;
			cursor: pointer;
			border-radius: 4px;
			display: flex;
			align-items: center;
			justify-content: center;
		}

		#actions button:hover {
			background: var(--color-btn-hover);
		}

		#actions svg {
			width: 20px;
			height: 20px;
		}

		/* Icons */
		.icon {
			width: 1em;
			height: 1em;
			display: inline-block;
			vertical-align: middle;
			flex-shrink: 0;
			color: var(--icon-color-base);
			stroke: currentColor;
			stroke-width: var(--icon-stroke-width);
			stroke-linecap: round;
			stroke-linejoin: round;
			fill: none;
			vector-effect: non-scaling-stroke;
			transition: color var(--icon-transition), stroke var(--icon-transition),
				fill var(--icon-transition), transform var(--icon-transition), opacity var(--icon-transition);
		}

		/* =====================
       Layout Containers
       ===================== */
		.layout {
			display: flex;
			/* Default: stacked for mobile */
			flex-direction: column;
		}

		nav,
		main,
		aside {
			padding: 1rem;
			border: 1px solid var(--color-border);
			box-sizing: border-box;
			min-width: 0;
			min-height: 0;
		}

		/* Section backgrounds */
		nav {
			background: var(--color-nav-bg);
		}

		main {
			background: var(--color-main-bg);
		}

		aside {
			background: var(--color-aside-bg);
		}

		/* =====================
       List Styling
       ===================== */
		ul {
			margin: 0;
			padding-left: 1.2em;
		}

		.outline-list {
			list-style: none;
			/* Remove bullets */
			padding-left: 0;
		}

		.outline-list li {
			margin: 0;
		}

		nav a.active {
			font-weight: bold;
			color: var(--color-btn-hover);
			/* Highlight current nav link */
		}

		.layout {
			display: flex;
			flex-direction: row;
			min-height: 100vh;
		}

		nav,
		main,
		aside {
			padding: 1rem;
		}

		nav {
			flex: 0 0 200px;
		}

		main {
			flex: 1;
		}

		aside {
			flex: 0 0 250px;
		}

		/* Mobile layout */
		@media (max-width: 768px) {
			.layout {
				flex-direction: column;
			}

			nav {
				order: 0;
				/* top */
			}

			aside {
				order: 1;
				/* second */
				flex: 0 0 auto;
				/* <-- important, let it size by content */
			}

			main {
				order: 2;
				/* last */
				flex: 1 1 auto;
				/* main takes remaining space */
			}
		}
	</style>
</head>

<body>
	<section class="action-bar" >
		<div id="brand">
			GNUEWE </div>
		<div id="actions">
			<!-- Nav toggle -->
			<button onclick="togglePanel('nav')" title="Toggle Navigation">
				<svg class="icon icon-md">
					<use href="/images/icons.svg#i-list"></use>
				</svg>
			</button>
			<!-- Dark mode toggle -->
			<button onclick="toggleDarkMode()" title="Toggle Dark Mode">
				<svg class="icon icon-md">
					<use href="images/icons.svg#i-sun"></use>
				</svg>
			</button>
			<!-- Outline toggle -->
			<button onclick="togglePanel('aside')" title="Toggle Outline">
				<svg class="icon icon-md">
					<use href="images/icons.svg#i-book"></use>
				</svg>
			</button>
		</div>
	</section>

	<div class="layout">
		<nav><?php renderNav($files, $currentFile); ?></nav>
		<main>
			<article><?= $contentHtml ?></article>
		</main>
		<aside> <?= $outlineHtml ?></aside>
	</div>

	<script>
		function togglePanel(panel) {
			const el = document.querySelector(panel);
			el.hidden = !el.hidden;
		}

		function toggleDarkMode() {
			document.body.classList.toggle('dark-mode');
			const icon = document.getElementById('darkIcon');
			if (document.body.classList.contains('dark-mode')) {
				// sun icon
				icon.innerHTML = '<circle cx="12" cy="12" r="5"/><path d="M12 1v2m0 18v2m11-11h-2M3 12H1m16.95-6.95-1.41 1.41M6.46 17.54l-1.41 1.41m0-13.9 1.41 1.41M17.54 17.54l1.41 1.41"/>';
			} else {
				// moon icon
				icon.innerHTML = '<path d="M12 3a9 9 0 0 0 0 18 9 9 0 0 1 0-18z"/>';
			}
		}
	</script>
</body>

</html>
