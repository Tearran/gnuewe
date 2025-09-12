<?php
// ---- Config ----
$docsDir = __DIR__ . '/docs';

// ---- Front matter parser (simple) ----
function parseFrontMatter($markdown) {
    $meta = [];
    $content = $markdown;

    if (preg_match('/^---\s*\n(.*?)\n---\s*\n/s', $markdown, $m)) {
        $yaml = trim($m[1]);
        $content = substr($markdown, strlen($m[0]));

        foreach (preg_split("/\r\n|\n|\r/", $yaml) as $line) {
            if (preg_match('/^([A-Za-z0-9_-]+):\s*(.*)$/', $line, $kv)) {
                $key = $kv[1];
                $val = trim($kv[2], "\"' ");
                // inline array [a, b, c]
                if (preg_match('/^\[(.*)\]$/', $val, $arr)) {
                    $val = array_map('trim', explode(',', $arr[1]));
                }
                $meta[$key] = $val;
            }
        }
    }
    return [$meta, $content];
}

// ---- Markdown parser (lightweight GFM-ish) ----
function parseMarkdown($text) {
    // --- Step 1: Fenced code blocks ---
    $codeBlocks = [];
    $text = preg_replace_callback('/^(```|~~~)([a-zA-Z]*)\n([\s\S]*?)^\1\s*$/m', function($m) use (&$codeBlocks) {
        $lang = htmlspecialchars(trim($m[2]));
        $code = htmlspecialchars($m[3]);
        $placeholder = "%%CODE" . count($codeBlocks) . "%%";
        $codeBlocks[$placeholder] = "<pre><code class='language-$lang'>$code</code></pre>";
        return $placeholder;
    }, $text);

    // --- Step 2: Tables ---
    $tables = [];
    $text = preg_replace_callback('/(^\|.+\|\n\|[-:| ]+\|\n(?:\|.*\|\n?)*)/m', function($m) use (&$tables) {
        $lines = explode("\n", trim($m[1]));
        if (count($lines) < 2) return $m[0];
        $header = array_shift($lines);
        $divider = array_shift($lines);
        $headers = array_map('trim', explode('|', trim($header, '| ')));
        $thead = '<tr>' . implode('', array_map(fn($h) => '<th>'.htmlspecialchars($h).'</th>', $headers)) . '</tr>';
        $tbody = '';
        foreach ($lines as $row) {
            if (trim($row) === '') continue;
            $cells = array_map('trim', explode('|', trim($row, '| ')));
            $tbody .= '<tr>' . implode('', array_map(fn($c) => '<td>'.htmlspecialchars($c).'</td>', $cells)) . '</tr>';
        }
        $ph = "%%BLOCK" . md5($m[0]) . "%%";
        $tables[$ph] = "<table><thead>$thead</thead><tbody>$tbody</tbody></table>\n";
        return $ph;
    }, $text);

    // --- Step 3: Blockquotes ---
    $blockquotes = [];
    $text = preg_replace_callback('/(^>.*(\n>.*)*)/m', function($m) use (&$blockquotes) {
        $lines = explode("\n", $m[0]);
        $lines = array_map(function($l) { return preg_replace('/^>\s?/', '', $l); }, $lines);
        $ph = "%%BLOCK" . md5($m[0]) . "%%";
        $blockquotes[$ph] = '<blockquote>' . implode("\n", $lines) . '</blockquote>';
        return $ph;
    }, $text);

    // --- Step 4: Setext and ATX headings ---
    $text = preg_replace('/^(.+)\n=+\s*$/m', "%%BLOCKH1_$1%%", $text);
    $text = preg_replace('/^(.+)\n-+\s*$/m', "%%BLOCKH2_$1%%", $text);
    $text = preg_replace('/^###### (.*)$/m', "%%BLOCKH6_$1%%", $text);
    $text = preg_replace('/^##### (.*)$/m', "%%BLOCKH5_$1%%", $text);
    $text = preg_replace('/^#### (.*)$/m', "%%BLOCKH4_$1%%", $text);
    $text = preg_replace('/^### (.*)$/m', "%%BLOCKH3_$1%%", $text);
    $text = preg_replace('/^## (.*)$/m', "%%BLOCKH2_$1%%", $text);
    $text = preg_replace('/^# (.*)$/m', "%%BLOCKH1_$1%%", $text);

    // --- Step 5: Lists (ul/ol) ---
    $uls = [];
    $text = preg_replace_callback('/((?:^\s*[-+*]\s.*\n?)+)/m', function($m) use (&$uls) {
        $items = preg_replace('/^\s*[-+*]\s(.*)$/m', '<li>$1</li>', $m[1]);
        $ph = "%%BLOCK" . md5($m[0]) . "%%";
        $uls[$ph] = "<ul>$items</ul>";
        return $ph;
    }, $text);
    $ols = [];
    $text = preg_replace_callback('/((?:^\s*\d+\.\s.*\n?)+)/m', function($m) use (&$ols) {
        $items = preg_replace('/^\s*\d+\.\s(.*)$/m', '<li>$1</li>', $m[1]);
        $ph = "%%BLOCK" . md5($m[0]) . "%%";
        $ols[$ph] = "<ol>$items</ol>";
        return $ph;
    }, $text);

    // --- Step 6: Inline code (single/multiple backticks) ---
    $text = preg_replace_callback('/(?<!`)(`+)(?!`)(.+?)\1(?!`)/s', function($m) {
        $code = preg_replace('/^\s+|\s+$/', '', $m[2]);
        return '<code>' . htmlspecialchars($code) . '</code>';
    }, $text);

    // --- Step 7: Images/Links ---
    $text = preg_replace('/!\[([^\]]*)\]\(([^)]+)\)/', '<img alt="$1" src="$2">', $text);
    $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $text);

    // --- Step 8: Bold, italic, strikethrough ---
    $text = preg_replace('/\*\*\*(.*?)\*\*\*/s', '<strong><em>$1</em></strong>', $text);
    $text = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $text);
    $text = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $text);
    $text = preg_replace('/~~(.*?)~~/s', '<del>$1</del>', $text);

    // --- Step 9: Paragraphs (avoid block tokens) ---
    $text = preg_replace('/\r\n|\r/', "\n", $text);
    $parts = preg_split('/\n{2,}/', $text);
    foreach ($parts as &$p) {
        $p = trim($p);
        // Only wrap in <p> if not a block token or heading
        if (
            !preg_match('/^%%BLOCK/', $p) &&
            !preg_match('/^%%BLOCKH\d_/', $p) &&
            strpos($p, '<pre><code') === false
        ) {
            $p = "<p>$p</p>";
        }
    }
    $text = implode("\n", $parts);

    // --- Step 10: Restore headings ---
    foreach ([1,2,3,4,5,6] as $n) {
        $text = preg_replace('/%%BLOCKH' . $n . '_(.*?)%%/', '<h' . $n . '>$1</h' . $n . '>', $text);
    }

    // --- Step 11: Restore block elements ---
    $text = strtr($text, $tables);
    $text = strtr($text, $blockquotes);
    $text = strtr($text, $uls);
    $text = strtr($text, $ols);

    // --- Step 12: Restore code blocks ---
    $text = strtr($text, $codeBlocks);

    return $text;
}

// ---- Outline ----
function generateOutline($html) {
    preg_match_all('/<h([1-6])>(.*?)<\/h\1>/', $html, $matches, PREG_SET_ORDER);
    if (!$matches) return [$html, ''];
    $out = "<h3>Outline</h3><ul>";
    foreach ($matches as $m) {
        $id = strtolower(trim(preg_replace('/[^a-z0-9]+/', '-', $m[2]), '-'));
        $out .= "<li style='margin-left:" . (20 * ($m[1]-1)) . "px'><a href='#$id'>$m[2]</a></li>";
        $html = str_replace($m[0], "<h$m[1] id='$id'>$m[2]</h$m[1]>", $html);
    }
    $out .= "</ul>";
    return [$html, $out];
}

// ---- File navigation ----
function listMarkdownFiles($dir, $base = '') {
    $items = [];
    foreach (scandir($dir) as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = "$dir/$file";
        $rel = ltrim("$base/$file", '/');
        if (is_dir($path)) {
            $items[$file] = listMarkdownFiles($path, $rel);
        } elseif (pathinfo($file, PATHINFO_EXTENSION) === 'md') {
            $items[$file] = $rel;
        }
    }
    return $items;
}

// ---- Main ----
$files = listMarkdownFiles($docsDir);
function pickFirst($files) {
    foreach ($files as $val) {
        if (is_array($val)) {
            $nested = pickFirst($val);
            if ($nested) return $nested;
        } else return $val;
    }
    return null;
}
$currentFile = $_GET['file'] ?? pickFirst($files);
$fullPath = $currentFile ? realpath("$docsDir/$currentFile") : null;

$contentHtml = "<p>Select a page</p>";
$outlineHtml = "";

if ($fullPath && strpos($fullPath, realpath($docsDir)) === 0 && is_file($fullPath)) {
    $markdown = file_get_contents($fullPath);
    [$meta, $markdown] = parseFrontMatter($markdown);
    $contentHtml = parseMarkdown($markdown);
    [$contentHtml, $outlineHtml] = generateOutline($contentHtml);
}

function renderNav($files, $current) {
    echo "<ul>";
    foreach ($files as $name => $val) {
        if (is_array($val)) {
            echo "<li><details><summary>$name</summary>";
            renderNav($val, $current);
            echo "</details></li>";
        } else {
            $active = ($val === $current) ? 'class="active"' : '';
            echo "<li><a $active href='?file=" . urlencode($val) . "'>$name</a></li>";
        }
    }
    echo "</ul>";
}
?>


<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= htmlspecialchars($meta['title'] ?? "GNU EWE") ?></title>
	<meta name="description" content="A hobby FOSS SPA for bash, html, and a simple python3 web server">
	<meta name="author" content="Tearran (FOSS project)">
	<meta name="robots" content="index, follow">
	<link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#0066ff" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0af" media="(prefers-color-scheme: dark)">
    <style>
 :root {
        --bg-default: #fff;
        --bg-nav: #eee;
        --bg-header: #ddd;
        --bg-aside: #f5f5f5;

        --text-default: #111;
        --text-header: #000;
        --text-nav: #222;
        --text-link: #0077cc;
        --text-link-hover: #004499;

        --btn-bg: #ddd;
        --btn-hover: #bbb;
        --btn-text: #000;

        --accent: #0066ff;
}

html.light-mode {
        --bg-default: #fff;
        --bg-nav: #eee;
        --bg-header: #ddd;
        --bg-aside: #f5f5f5;

        --text-default: #111;
        --text-header: #000;
        --text-nav: #222;
        --text-link: #0077cc;
        --text-link-hover: #004499;

        --btn-bg: #ddd;
        --btn-hover: #bbb;
        --btn-text: #000;

        --accent: #0066ff;
}

html.dark-mode {
        --bg-default: #111;
        --bg-nav: #222;
        --bg-header: #000;
        --bg-aside: #1a1a1a;

        --text-default: #ddd;
        --text-header: #fff;
        --text-nav: #eee;
        --text-link: #ccc;
        --text-link-hover: #fff;

        --btn-bg: #333;
        --btn-hover: #555;
        --btn-text: #fff;

        --accent: #0af;
}

pre {
    background: var(--bg-aside);
    color: var(--text-default);
    padding: 10px;
    border-radius: 6px;
    overflow-x: auto;
    font-family: monospace;
    font-size: 0.9em;
}
pre code {
    background: none;
    color: inherit;
}


body {
        margin: 0;
        font-family: sans-serif;
        display: flex;
        flex-direction: column;
        height: 100vh;
        background: var(--bg-default);
        color: var(--text-default);
}

.header-tools {
        background: var(--bg-header);
        color: var(--text-header);
        padding: 10px 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
}

.layout {
        display: flex;
        flex: 1;
        overflow: hidden;
}

/* Columns */
nav {
        width: 250px;
        min-width: 0;
        /* allow flex to shrink */
        background: var(--bg-nav);
        color: var(--text-nav);
        padding: 10px;
        overflow-y: auto;
        transition: all 0.3s ease;
}

main {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
}

aside {
        width: 250px;
        min-width: 0;
        background: var(--bg-aside);
        color: var(--text-default);
        padding: 10px;
        overflow-y: auto;
        transition: all 0.3s ease;
}

/* Hidden state for toggling */
nav.hidden,
aside.hidden {
        width: 0;
        padding: 0;
        opacity: 0;
        overflow: hidden;
}


nav a {
        color: var(--text-link);
        text-decoration: none;
}

nav a:hover {
        color: var(--text-link-hover);
}

aside {
        width: 250px;
        background: var(--bg-aside);
        color: var(--text-default);
        padding: 10px;
        overflow-y: auto;
        transition: all 0.3s ease;
}

main {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
}

aside.hidden,
nav.hidden {
        display: none;
}

/* Icons */
.icon {
        width: 32px;
        height: 32px;
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

/* Unified style for all buttons and links acting as buttons */
.icon-btn, .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    background: var(--btn-bg);
    color: var(--btn-text);
    text-decoration: none;
    transition: background 0.2s, color 0.2s;
    border: none;
    font: inherit;
}

.icon-btn:hover, .btn:hover {
    background: var(--btn-hover);
    color: var(--btn-text);
}

.icon-btn svg.icon {
    width: 32px;
    height: 32px;
    pointer-events: none;
}

    </style>
</head>
<body>
 
<header>
    <div class="header-tools">

    <a href="#" class="icon-btn" onclick="toggleNav()">
    <svg class="icon icon-md">
        <use href="images/icons.svg#i-grid"></use>
    </svg>
</a>
<div>
                <a href="#" class="icon-btn" onclick="toggleMode()">
                    <svg class="icon icon-md">
                        <use href="images/icons.svg#i-color"></use>
                    </svg>
                </a>
                <a href="#" class="icon-btn" onclick="toggleOutline()">
                    <svg class="icon icon-md">
                            <use href="images/icons.svg#i-list"></use>
                    </svg>
                </a>

                </div>
        </div>
</header>

<div class="layout">     
    <nav id="nav" class="hidden">
        <h2>Docs</h2>
        <?php renderNav($files, $currentFile); ?>
    </nav>
    <main>
        <article><?= $contentHtml ?></article>
    </main>
    <aside id="outline" class="hidden">
        <?= $outlineHtml ?>
    </aside>
</div>


<script>
        function toggleOutline() {
                document.getElementById('outline').classList.toggle('hidden');
        }
        function toggleNav() {
                document.getElementById('nav').classList.toggle('hidden');
        }
        function toggleMode() {
            document.documentElement.classList.toggle('dark-mode');
            // Save mode correctly
            const mode = document.documentElement.classList.contains('dark-mode') ? 'dark' : 'light';
            localStorage.setItem('theme', mode);
        }

// Restore theme on load
window.addEventListener('DOMContentLoaded', () => {
    const saved = localStorage.getItem('theme');
    if (saved === 'dark') document.documentElement.classList.add('dark-mode');
});


</script>

</body>
</html>
