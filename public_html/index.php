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
            $a = $v === $cur ? 'class=\"active\"' : '';
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
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Docs Viewer</title>
<meta name="color-scheme" content="light dark">
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
body {
    margin: 0;
    font-family: sans-serif;
    height: 100vh;
    background: var(--bg-default);
    color: var(--text-default);
    display: flex; flex-direction: column;
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
    flex-direction: row;
}
nav, aside {
    width: 250px;
    min-width: 0;
    padding: 10px;
    overflow-y: auto;
    background: var(--bg-nav);
    color: var(--text-nav);
    transition: all 0.3s ease;
}
aside { background: var(--bg-aside); color: var(--text-default);}
main {
    flex: 1;
    min-width: 350px;
    max-width: 900px;
    padding: 20px;
    overflow-y: auto;
}
nav.hidden, aside.hidden { display:none !important; }
@media (max-width: 700px) {
    .layout {
        flex-direction: column;
    }
    nav, aside, main {
        width: 100vw;
        max-width: 100vw;
        min-width: 0;
        position: static;
        box-shadow: 0 2px 12px #0008;
        z-index: 10;
    }
    nav.hidden, aside.hidden { display:none !important; }
    .header-tools { position: sticky; top: 0; z-index: 100; }
}
nav a {
    color: var(--text-link);
    text-decoration: none;
}
nav a:hover {
    color: var(--text-link-hover);
}
.icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    background: var(--btn-bg);
    color: var(--btn-text);
    border: none;
    font: inherit;
    margin-right: 4px;
}
.icon-btn:hover { background: var(--btn-hover);}
.icon-btn svg { width: 32px; height: 32px; }
</style>
</head>
<body>
<header>
    <div class="header-tools">
        <button class="icon-btn" onclick="togglePanel('nav')" title="Nav">&#9776;</button>
        <div>
            <button class="icon-btn" onclick="toggleMode()" title="Theme">&#9788;</button>
            <button class="icon-btn" onclick="togglePanel('outline')" title="Outline">&#9776;</button>
        </div>
    </div>
</header>
<div class="layout">
    <nav id="nav">
        <h2>Docs</h2>
        <?php renderNav($files, $currentFile); ?>
    </nav>
    <main id="main">
        <article><?= $contentHtml ?></article>
    </main>
    <aside id="outline">
        <?= $outlineHtml ?>
    </aside>
</div>
<script>
function togglePanel(which) {
    var panel = document.getElementById(which);
    panel.classList.toggle('hidden');
}
function toggleMode() {
    document.documentElement.classList.toggle('dark-mode');
    localStorage.setItem('theme',
        document.documentElement.classList.contains('dark-mode') ? 'dark' : 'light');
}
window.addEventListener('DOMContentLoaded', () => {
    if (localStorage.getItem('theme') === 'dark')
        document.documentElement.classList.add('dark-mode');
    // On load, ensure panels are visible by default
    document.getElementById('nav').classList.remove('hidden');
    document.getElementById('outline').classList.remove('hidden');
    // Outline closes when a link is clicked
    document.getElementById('outline').addEventListener('click', function(e) {
        if (e.target.tagName === 'A') {
            document.getElementById('outline').classList.add('hidden');
        }
    });
});
window.addEventListener('resize', () => {
    // On resize, do nothing: user manages toggles on both desktop and mobile
});
</script>
</body>
</html>