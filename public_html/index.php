<!-- Helpers: File, Navigation, Outline, Front Matter -->
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

<!-- Markdown Parsing and Rendering -->
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
        $src = htmlspecialchars($src, ENT_QUOTES, 'UTF-8');
        return "<img src=\"$src\" alt=\"$alt\">";
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

<!-- Navigation Rendering -->
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

<!-- State: File Selection, Content, Outline, Metadata-->
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
            <button onclick="this.nextElementSibling.hidden=!this.nextElementSibling.hidden" type="button" aria-expanded="false" aria-controls="metadata-content">
        	<svg class="icon icon-md"><use href="#i-list"></use></svg>
		<span class="brand"> Metadata </span>
            </button>
            <pre id="metadata-content" hidden style="border:1px solid; padding:0.5em;">' .
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

		/* links */
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
		}

		a:visited {
			color: var(--color-link-visited);
		}

		a:focus-visible {
			outline: 2px solid var(--color-link-focus);
			outline-offset: 2px;
		}

		/* action bar */
		.action-bar {
			display: flex;
			justify-content: space-between;
			/* left & right aligned bars */
			padding: 0.5rem 1rem;
			color: var(--color-text);
		}

		.actions {
			display: flex;
			gap: 0.5rem;
			/* spacing between buttons */
		}

		button {
			display: flex;
			align-items: center;
			/* icon and text vertically centered */
			gap: 0.5rem;
			/* spacing between icon and text */
			padding: 0.4rem 0.8rem;
			background: var(--color-btn-bg, #fff);
			border: 1px solid var(--color-border, #ccc);
			/* muted border from variable */
			border-radius: 0.4rem;
			color: var(--color-btn-text, #444);
			cursor: pointer;
			font-size: 0.9rem;
			transition: background 0.2s, border-color 0.2s, color 0.2s;
		}

		button:hover {
			background: var(--color-btn-hover, #0077cc);
			border-color: var(--color-btn-hover, #0077cc);
			color: #fff;
			/* ensure text is readable on hover */
		}

		svg.icon {
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
				fill var(--icon-transition), transform var(--icon-transition),
				opacity var(--icon-transition);
		}

		.layout {
			display: flex;
			flex-direction: row;
			min-height: 100vh;
		}

		main {
			flex: 1;
		}

		nav {
			flex: 0 0 200px;
		}

		aside {
			flex: 0 0 250px;
		}

		nav,
		aside,
		section {
			padding: 1rem;
			border: 1px solid var(--color-border, #ccc);
			min-width: 0;
			min-height: 0;
		}

		main {
			padding: 1rem;
			border: 1px solid var(--color-border);
			box-sizing:
				border-box;
			min-width: 0;
			min-height: 0;
		}

		ul {
			margin: 0;
			padding-left: 1.2em;
		}

		nav li,
		.outline-list {
			list-style: none;
			padding-left: 0;
		}

		.outline-list li {
			margin: 0;
		}

		nav a.active {
			font-weight: bold;
			color: var(--color-btn-hover);
		}

		@media (max-width: 768px) {
			.layout {
				flex-direction: column;
			}

			nav {
				order: 0;
			}

			aside {
				order: 1;
				flex: 0 0 auto;
			}

			main {
				order: 2;
				flex: 1 1 auto;
			}
		}
	</style>
</head>

<body>

	<svg id="svg90" display="none" aria-hidden="true" version="1.1" xmlns="http://www.w3.org/2000/svg">
		<symbol id="i-home" viewBox="0 0 24 24">
			<path id="path5" d="M3 11.2 12 4l9 7.2v7.8A1 1 0 0 1 20 20H4a1 1 0 0 1-1-1v-7.8Z" />
			<path id="path6" d="m9 20v-6a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v6" />
		</symbol>
		<symbol id="i-book" viewBox="0 0 24 24">
			<path id="path1" d="M4 5a2 2 0 0 1 2-2h11a3 3 0 0 1 3 3v13.5a.5.5 0 0 1-.79.407L17 18l-2.21 1.907a.5.5 0 0 1-.79-.407V6a1 1 0 0 0-1-1H6a2 2 0 0 1-2-2Z" />
			<path id="path2" d="m4 5v13a3 3 0 0 0 3 3h12" />
		</symbol>
		<symbol id="i-search" viewBox="0 0 24 24">
			<circle id="circle9" cx="11" cy="11" r="6" />
			<path id="path9" d="m16.2 16.2 3.3 3.3" />
		</symbol>
		<symbol id="i-edit" viewBox="0 0 24 24">
			<path id="path18" d="m4 17.5 1.5 2.5 3.7-0.7 9.8-9.8-3.5-3.5-11.5 11.5z" />
			<path id="path19" d="m15.5 6 1.9-1.9a1.4 1.4 0 0 1 2 2L19 7.5" />
		</symbol>
		<symbol id="i-filter" viewBox="0 0 24 24">
			<path id="path10" d="M4 5h16L14 13v4.8a1 1 0 0 1-1.5.9l-3-1.8a1 1 0 0 1-.5-.86V13L4 5Z" />
		</symbol>
		<symbol id="i-sort-v" viewBox="0 0 24 24">
			<path id="path11" d="m8 4v16l-3-3m3 3 3-3" />
			<path id="path12" d="m16 20v-16l3 3m-3-3-3 3" />
		</symbol>
		<symbol id="i-refresh" viewBox="0 0 24 24">
			<path id="path13" d="m20 5v5h-5" />
			<path id="path14" d="m4 19v-5h5" />
			<path id="path15" d="M6.3 9A7 7 0 0 1 18 10.2M17.7 15A7 7 0 0 1 6 13.8" />
		</symbol>
		<symbol id="i-plus" viewBox="0 0 24 24">
			<path id="path16" d="m12 4v16m-8-8h16" />
		</symbol>
		<symbol id="i-minus" viewBox="0 0 24 24">
			<path id="path17" d="m4 12h16" />
		</symbol>

		<symbol id="i-download" viewBox="0 0 24 24">
			<path id="path24" d="m5 20h14" />
			<path id="path25" d="m12 4v12" />
			<path id="path26" d="m7.5 12.5 4.5 4.5 4.5-4.5" />
		</symbol>
		<symbol id="i-download-box" viewBox="0 0 24 24">
			<path id="path27" d="m4 13v6a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-6" />
			<path id="path28" d="m12 4v10" />
			<path id="path29" d="m7.5 10.5 4.5 4.5 4.5-4.5" />
		</symbol>
		<symbol id="i-upload" viewBox="0 0 24 24">
			<path id="path37" d="m5 20h14" />
			<path id="path38" d="M12 20V8" />
			<path id="path39" d="m16.5 11.5-4.5-4.5-4.5 4.5" />
		</symbol>
		<symbol id="i-clipboard" viewBox="0 0 24 24">
			<path id="path40" d="m9 5h6" />
			<path id="path41" d="M8 3h8a1 1 0 0 1 1 1v2H7V4a1 1 0 0 1 1-1Z" />
			<path id="path42" d="M7 7h10v13a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1V7Z" />
		</symbol>
		<symbol id="i-external" viewBox="0 0 24 24">
			<path id="path43" d="m14 5h5v5" />
			<path id="path44" d="m20 4-7.5 7.5" />
			<path id="path45" d="M10 5H7a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10c1.1 0 2-.9 2-2v-3" />
		</symbol>
		<symbol id="i-info" viewBox="0 0 24 24">
			<circle id="circle45" cx="12" cy="12" r="9" />
			<path id="path46" d="M12 10v7M12 7h.01" />
		</symbol>
		<symbol id="i-warning" viewBox="0 0 24 24">
			<path id="path47" d="M12.9 4.5 21 19a1 1 0 0 1-.9 1.5H3.9A1 1 0 0 1 3 19l8.1-14.5a1 1 0 0 1 1.8 0Z" />
			<path id="path48" d="m12 9v5" />
			<path id="path49" d="M12 16h.01" />
		</symbol>
		<symbol id="i-error" viewBox="0 0 24 24">
			<path id="path50" d="M8 3h8l5 5v8l-5 5H8l-5-5V8l5-5Z" />
			<path id="path51" d="m9.5 9.5 5 5m0-5-5 5" />
		</symbol>
		<symbol id="i-check" viewBox="0 0 24 24">
			<path id="path52" d="m4 13 5.5 5.5 10.5-12" />
		</symbol>
		<symbol id="i-close" viewBox="0 0 24 24">
			<path id="path53" d="M5 5 19 19M19 5 5 19" />
		</symbol>
		<symbol id="i-user" viewBox="0 0 24 24">
			<circle id="circle53" cx="12" cy="9" r="4" />
			<path id="path54" d="m5 20c1.5-3.3 4.5-5 7-5s5.5 1.7 7 5" />
		</symbol>
		<symbol id="i-github" viewBox="0 0 24 24">

			<path d="M12 0.296C5.372 0.296 0 5.668 0 12.296c0 5.289 3.438 9.773 8.207 11.367.6.112.793-.261.793-.577 0-.285-.011-1.234-.017-2.238-3.338.726-4.042-1.61-4.042-1.61-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.73.083-.73 1.205.085 1.84 1.236 1.84 1.236 1.07 1.834 2.807 1.304 3.492.997.108-.775.419-1.305.762-1.606-2.665-.304-5.467-1.334-5.467-5.933 0-1.31.469-2.382 1.236-3.222-.124-.304-.535-1.527.117-3.182 0 0 1.008-.322 3.3 1.23a11.489 11.489 0 0 1 3.003-.404c1.019.004 2.045.138 3.003.404 2.291-1.552 3.297-1.23 3.297-1.23.654 1.655.243 2.878.12 3.182.77.84 1.235 1.912 1.235 3.222 0 4.61-2.807 5.625-5.479 5.922.43.37.823 1.101.823 2.22 0 1.605-.014 2.899-.014 3.293 0 .319.19.694.8.576C20.565 22.065 24 17.582 24 12.296 24 5.668 18.627 0.296 12 0.296z" />

		</symbol>
		<symbol id="i-sun" viewBox="0 0 24 24">
			<circle cx="12" cy="12" r="5" />
			<path d="M12 1v2m0 18v2m11-11h-2M3 12H1m16.95-6.95-1.41 1.41M6.46 17.54l-1.41 1.41m0-13.9 1.41 1.41M17.54 17.54l1.41 1.41" />
		</symbol>

		<symbol id="i-moon" viewBox="0 0 24 24">
			<path d="M12 3a9 9 0 0 0 0 18 9 9 0 0 1 0-18z" />
		</symbol>
		<symbol id="i-tag" viewBox="0 0 24 24">
			<path id="path59" d="M4 11.5V4h7.5L21 13.5l-7.5 7.5L4 11.5Z" />
			<circle id="circle59" cx="9" cy="9" r="1.4" />
		</symbol>
		<symbol id="i-star" viewBox="0 0 24 24">
			<path id="path60" d="m12 4 2.2 4.5 5 .7-3.6 3.6.9 5.1-4.5-2.4-4.5 2.4.9-5.1L4.8 9.2l5-.7L12 4Z" />
		</symbol>
		<symbol id="i-bell" viewBox="0 0 24 24">
			<path id="path65" d="M18 14V11a6 6 0 1 0-12 0v3l-1.5 2.3a.6.6 0 0 0 .5.9h15a.6.6 0 0 0 .5-.9L18 14Z" />
			<path id="path66" d="m10 19a2 2 0 0 0 4 0" />
		</symbol>
		<symbol id="i-terminal" viewBox="0 0 24 24">
			<rect id="rect66" x="3" y="5" width="18" height="14" rx="1.6" />
			<path id="path67" d="m7 10 3 2-3 2m4.5 0h5.5" />
		</symbol>
		<symbol id="i-list" viewBox="0 0 24 24">
			<path id="path75" d="M9 6h11M9 12h11M9 18h11" />
			<path id="path76" d="M4 6h.01M4 12h.01M4 18h.01" />
		</symbol>

		<symbol id="i-grid" viewBox="0 0 24 24">
			<rect id="rect76" x="4" y="4" width="6" height="6" rx="1" />
			<rect id="rect77" x="14" y="4" width="6" height="6" rx="1" />
			<rect id="rect78" x="4" y="14" width="6" height="6" rx="1" />
			<rect id="rect79" x="14" y="14" width="6" height="6" rx="1" />
		</symbol>
		<symbol id="i-arrow-right" viewBox="0 0 24 24">
			<path id="path79" d="m5 12h14" />
			<path id="path80" d="m13 6 6 6-6 6" />
		</symbol>
		<symbol id="i-chevron-down" viewBox="0 0 24 24">
			<path id="path81" d="m6 9.5 6 5.5 6-5.5" />
		</symbol>
		<symbol id="i-folder" viewBox="0 0 24 24">
			<path id="path82" d="M3 6a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v1H3V6Z" />
			<path id="path83" d="M3 10h18v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6Z" />
		</symbol>
		<symbol id="i-file" viewBox="0 0 24 24">
			<path id="path84" d="M7 3h6l5 5v11a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" />
			<path id="path85" d="m13 3v5h5" />
		</symbol>

		<symbol id="i-color" viewBox="0 0 24 24">
			<path id="path88" d="M12 3a9 9 0 0 0-9 9 9 9 0 0 0 9 9h.5a2.5 2.5 0 0 0 2.45-2.02c.07-.34.37-.63.72-.71.7-.15 1.43-.24 2.2-.24 1.78 0 3.13-1.57 2.96-3.33C20.97 8.59 16.95 3 12 3Z" />
			<circle id="circle88" cx="8.5" cy="10.5" r="1" />
			<circle id="circle89" cx="12" cy="8" r="1" />
			<circle id="circle90" cx="15.5" cy="10.5" r="1" />
		</symbol>

		<symbol id="i-check-circle" viewBox="0 0 24 24">

			<path id="path86" d="m12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z" />
			<path id="path87" d="m9 12 2 2 4-4" />
		</symbol>

	</svg>

	<section class="action-bar">
		<div id="left-bar" class="actions">
			<button onclick="togglePanel('nav')" title="Toggle Navigation">
				<svg class="icon">
					<use href="#i-book"></use>
				</svg>
				<span class="brand"> Pages </span>
			</button>

		</div>
		<div id="right-bar" class="actions">
			<button onclick="toggleDarkMode()" title="Toggle Dark Mode" aria-pressed="false">
				<svg id="darkIcon" class="icon">
					<use href="#i-sun"></use>
				</svg>
			</button>
			<button onclick="togglePanel('aside')" title="Toggle Outline">
				<svg class="icon">
					<use href="#i-list"></use>
				</svg>
				<span class="brand"> Outline </span>
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
					useEl.setAttribute('href', '#i-moon');
					useEl.setAttributeNS('http://www.w3.org/1999/xlink', 'xlink:href', '#i-moon');
				}
				document.querySelector('button[title="Toggle Dark Mode"]').setAttribute('aria-pressed', "true");
			}
		})();

		function toggleDarkMode() {
			const isDark = document.body.classList.toggle('dark-mode');
			localStorage.setItem('dark-mode', isDark ? 'true' : 'false');
			const useEl = document.querySelector('#darkIcon use');
			const hrefVal = isDark ? '#i-moon' : '#i-sun';
			if (useEl) {
				useEl.setAttribute('href', hrefVal);
				useEl.setAttributeNS('http://www.w3.org/1999/xlink', 'xlink:href', hrefVal);
			}
			document.querySelector('button[title="Toggle Dark Mode"]').setAttribute('aria-pressed', String(isDark));
		}
	</script>
</body>

</html>