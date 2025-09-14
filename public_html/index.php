<?php // ==== Helpers: File, Navigation, Outline, Front Matter ==== ?>
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

function pickFirst($arr) {
    foreach ($arr as $v) {
        if (is_array($v)) {
            $f = pickFirst($v); if ($f) return $f;
        } else return $v;
    }
    return null;
}

function extractFrontMatter(&$md) {
    if (preg_match('/^---\s*\n(.*?\n?)^---\s*$/ms', $md, $m)) {
        $frontMatter = trim($m[1]);
        $md = preg_replace('/^---\s*\n(.*?\n?)^---\s*$/ms', '', $md, 1);
        return $frontMatter;
    }
    return null;
}

function renderOutline($outline) {
    if (!$outline) return "";
    $out = "<b>Outline</b><ul class='outline-list'>";
    foreach ($outline as $item) {
        $out .= "<li style='margin-left:" . (16*($item['level']-1)) . "px'><a href='#{$item['id']}'>" .
                htmlspecialchars($item['text'], ENT_QUOTES, 'UTF-8') . "</a></li>";
    }
    $out .= "</ul>";
    return $out;
}
?>

<?php // ==== Markdown Parsing and Rendering ==== ?>
<?php
function safeMarkdown($md, &$outline = null) {
    $outlineItems = [];
    // 1) Fenced code blocks
    $md = preg_replace_callback('/^(```|~~~)[ \t]*([\w-]*)[^\n]*\n([\s\S]*?)^\1[ \t]*$/m', function($m) {
        $c = htmlspecialchars($m[3]);
        $lang = htmlspecialchars($m[2]);
        return "<pre><code class=\"language-$lang\">$c</code></pre>";
    }, $md);

    // 2) ATX headings (outline-safe)
    $md = preg_replace_callback('/^(#{1,6}) (.+)$/m', function($m) use (&$outlineItems) {
        $level = strlen($m[1]);
        $text = trim($m[2]);
        $id = strtolower(preg_replace('/[^a-z0-9]+/', '-', $text));
        $id = trim($id, '-');
        $outlineItems[] = ['level'=>$level,'text'=>$text,'id'=>$id];
        $safe = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        return "<h$level id=\"$id\">$safe</h$level>";
    }, $md);

    // Inline code
    $md = preg_replace_callback('/`([^`]+)`/', fn($m) => '<code>' . htmlspecialchars($m[1]) . '</code>', $md);

    // Blockquotes
    $md = preg_replace_callback('/(?:^> ?.*(?:\n|$))+?/m', function($m) {
        $inner = preg_replace('/^> ?/m', '', rtrim($m[0]));
        $escaped = implode("\n", array_map(
            fn($l) => htmlspecialchars($l, ENT_QUOTES, 'UTF-8'),
            explode("\n", $inner)
        ));
        return "<blockquote>$escaped</blockquote>";
    }, $md);

    // Lists
    $md = preg_replace_callback('/^\s*[-*+] (.*)$/m', fn($m) => '<li>' . htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8') . '</li>', $md);
    $md = preg_replace_callback('/^\s*\d+\. (.*)$/m', fn($m) => '<li>' . htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8') . '</li>', $md);

    // Unordered lists: group contiguous bullets
    $md = preg_replace_callback('/(?:^\s*[-*+]\s+.+\n?)+/m', function($m) {
        $items = preg_replace_callback(
            '/^\s*[-*+]\s+(.+)$/m',
            fn($i) => '<li>' . htmlspecialchars($i[1], ENT_QUOTES, 'UTF-8') . '</li>',
            $m[0]
        );
        return "<ul>\n$items\n</ul>";
    }, $md);

    // Ordered lists: group contiguous numbered items
    $md = preg_replace_callback('/(?:^\s*\d+\.\s+.+\n?)+/m', function($m) {
        $items = preg_replace_callback(
            '/^\s*\d+\.\s+(.+)$/m',
            fn($i) => '<li>' . htmlspecialchars($i[1], ENT_QUOTES, 'UTF-8') . '</li>',
            $m[0]
        );
        return "<ol>\n$items\n</ol>";
    }, $md);

    $md = preg_replace_callback('/(<li>.*<\/li>)+/s', function($m) {
        return (preg_match('/<li>\d/', $m[0]) ? "<ol>{$m[0]}</ol>" : "<ul>{$m[0]}</ul>");
    }, $md);

    // Emphasis and strong
    $md = preg_replace_callback('/\*\*(.*?)\*\*/s', fn($m) => '<strong>' . htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8') . '</strong>', $md);
    $md = preg_replace_callback('/\*(.*?)\*/s', fn($m) => '<em>' . htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8') . '</em>', $md);
    
    // Images
    $md = preg_replace_callback('/!\[([^\]]*)\]\(([^)]+)\)/', function($m) {
        $alt = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
        $src = trim($m[2]);
        if (!preg_match('#^(https?:)?//|^/|^\./|^\.\./|^data:image/#i', $src)) $src = '#';
    $md = preg_replace_callback('/\*\*(.*?)\*\*/s', fn($m) => '<strong>' . htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8') . '</strong>', $md);
    $md = preg_replace_callback('/\*(.*?)\*/s', fn($m) => '<em>' . htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8') . '</em>', $md);
    }, $md);
    // Horizontal rule: three or more -, *, or _ on a line by itself
$md = preg_replace('/^(?: {0,3})([-*_])(?: *\1){2,} *$/m', "<hr>", $md);
    // Links
    $md = preg_replace_callback('/\[([^\]]+)\]\(([^)]+)\)/', function($m) {
        $text = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
        $href = trim($m[2]);
        $is_external = preg_match('#^(https?:)?//#i', $href);
        if (!preg_match('#^(?:https?:)?//|^/|^\./|^\.\./|^mailto:#i', $href)) $href = '#';
        $href = htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
        $extra = $is_external ? ' target="_blank" rel="noopener"' : '';
        return "<a href=\"$href\"$extra>$text</a>";
    }, $md);

    // Paragraphs and safety
    $md = preg_replace('/\n{2,}/', "\n\n", $md);
    $md = preg_replace('/<(?!\/?(?:h[1-6]|p|ul|ol|li|blockquote|pre|code|img|a|strong|em|br|hr)(?:\s|>))/i', '&lt;', $md);
    $md = preg_replace('/(?:^|\n)([^\n<][^\n]*)\n/', "\n<p>$1</p>\n", $md);

    if (is_array($outline)) $outline = $outlineItems;
    return $md;
}
?>

<?php // ==== Navigation Rendering ==== ?>
<?php
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
?>

<?php // ==== State: File Selection, Content, Outline, Metadata ==== ?>
<?php
$files = listMdFiles($docsDir);
$currentFile = $_GET['file'] ?? pickFirst($files);
$fullPath = $currentFile ? realpath("$docsDir/$currentFile") : null;
$contentHtml = "<p>Select a page</p>";
$outlineArr = [];
$outlineHtml = "";
$metaHtml = "";

if ($fullPath && strpos($fullPath, realpath($docsDir)) === 0 && is_file($fullPath)) {
    $markdown = file_get_contents($fullPath);
    $frontMatter = extractFrontMatter($markdown);
    if ($frontMatter) {
        $metaHtml = '<div>
            <div onclick="this.nextElementSibling.hidden=!this.nextElementSibling.hidden" type="button">
                Show Metadata
            </div>
            <pre hidden style="border:1px solid padding:0.5em;">' .
            htmlspecialchars($frontMatter, ENT_QUOTES, 'UTF-8') .
            '</pre>
        </div>';
    }
    $contentHtml = $metaHtml . safeMarkdown($markdown, $outlineArr);
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
        --icon-color-base: var(--text-main);
        --icon-color-accent: var(--text-link);
        --icon-color-muted: var(--text-muted);
        --icon-color-danger: #ff4d4f;
        --icon-color-warn: #e0a210;
        --icon-color-success: #31c48d;
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
    --icon-stroke-width: 1.6;
    --icon-transition: 120ms;
    --icon-color-base: var(--color-text);
    --icon-color-accent: var(--color-link);
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
	body {
		margin: 0;
		font-family: sans-serif;
		background: var(--color-bg);
		color: var(--color-text);
	}
	a {
		color: var(--color-link);
		text-decoration: underline;
		transition: color 120ms;
	}
	a:hover, a:focus { color: var(--color-link-hover); }
	a:active { color: var(--color-link-active); }
	a:visited { color: var(--color-link-visited); }
	a:focus-visible { outline: 2px solid var(--color-link-focus); outline-offset: 2px; }
	.action-bar {
		background: var(--color-nav-bg);
		color: var(--color-text);
		padding: 0.5rem 1rem;
		display: flex;
		align-items: center;
		justify-content: space-between;
	}
	#brand { font-weight: bold; }
	#actions { display: flex; gap: 0.5rem; }
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
	#actions button:hover { background: var(--color-btn-hover); }
	#actions svg { width: 20px; height: 20px; }
	.icon {
		width: 1em; height: 1em; display: inline-block; vertical-align: middle; flex-shrink: 0;
		color: var(--icon-color-base); stroke: currentColor; stroke-width: var(--icon-stroke-width);
		stroke-linecap: round; stroke-linejoin: round; fill: none; vector-effect: non-scaling-stroke;
		transition: color var(--icon-transition), stroke var(--icon-transition),
			fill var(--icon-transition), transform var(--icon-transition), opacity var(--icon-transition);
	}
	.layout { display: flex; flex-direction: row; min-height: 100vh; }
	nav, main, aside { padding: 1rem; border: 1px solid var(--color-border); box-sizing: border-box; min-width: 0; min-height: 0; }
	nav { background: var(--color-nav-bg); flex: 0 0 200px; }
	main { background: var(--color-main-bg); flex: 1; }
	aside { background: var(--color-aside-bg); flex: 0 0 250px; }
	ul { margin: 0; padding-left: 1.2em; }
	.outline-list { list-style: none; padding-left: 0; }
	.outline-list li { margin: 0; }
	nav a.active { font-weight: bold; color: var(--color-btn-hover); }
	@media (max-width: 768px) {
		.layout { flex-direction: column; }
		nav { order: 0; }
		aside { order: 1; flex: 0 0 auto; }
		main { order: 2; flex: 1 1 auto; }
	}
	</style>
</head>
<body>
	<section class="action-bar" >
		<div id="brand">GNUEWE</div>
		<div id="actions">
			<button onclick="togglePanel('nav')" title="Toggle Navigation">
				<svg class="icon icon-md"><use href="images/icons.svg#i-list"></use></svg>
			</button>
			<button onclick="toggleDarkMode()" title="Toggle Dark Mode" aria-pressed="false">
				<svg id="darkIcon" class="icon icon-md"><use href="images/icons.svg#i-sun"></use></svg>
			</button>
			<button onclick="togglePanel('aside')" title="Toggle Outline">
				<svg class="icon icon-md"><use href="images/icons.svg#i-book"></use></svg>
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
	(function() {
		const darkPref = localStorage.getItem('dark-mode');
		if (darkPref === 'true') {
			document.body.classList.add('dark-mode');
			const useEl = document.querySelector('#darkIcon use');
			if (useEl) {
				useEl.setAttribute('href', 'images/icons.svg#i-moon');
				useEl.setAttributeNS('http://www.w3.org/1999/xlink','xlink:href', 'images/icons.svg#i-moon');
			}
			document.querySelector('button[title="Toggle Dark Mode"]').setAttribute('aria-pressed', "true");
		}
	})();
	function toggleDarkMode() {
		const isDark = document.body.classList.toggle('dark-mode');
		localStorage.setItem('dark-mode', isDark ? 'true' : 'false');
		const useEl = document.querySelector('#darkIcon use');
		const hrefVal = isDark ? 'images/icons.svg#i-moon' : 'images/icons.svg#i-sun';
		if (useEl) {
			useEl.setAttribute('href', hrefVal);
			useEl.setAttributeNS('http://www.w3.org/1999/xlink','xlink:href', hrefVal);
		}
		document.querySelector('button[title="Toggle Dark Mode"]').setAttribute('aria-pressed', String(isDark));
	}
</script>
</body>
</html>