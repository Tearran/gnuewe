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
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Responsive Layout</title>
  <style>
    body {
      margin: 0;
      font-family: sans-serif;
    }

    header {
      background: #222;
      color: #fff;
      padding: 0.5rem 1rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    #brand {
      font-weight: bold;
    }

    #actions button {
      margin-left: 0.5rem;
      background: #444;
      color: #fff;
      border: none;
      padding: 0.5rem 1rem;
      cursor: pointer;
      border-radius: 4px;
    }

    #actions button:hover {
      background: #0077cc;
    }

    .layout {
      display: flex;
      flex-direction: column;
    }

    nav, main, aside {
      padding: 1rem;
      border: 1px solid #ccc;
      box-sizing: border-box;
      min-width: 0;
      min-height: 0;
    }

    nav   { background: #f3f3f3; }
    main  { background: #fff; }
    aside { background: #fafafa; }

    @media (min-width: 768px) {
      .layout {
        display: grid;
        grid-template-columns: 200px 1fr 200px;
        grid-template-areas: "nav main outline";
        height: calc(100vh - 50px); /* subtract header */
      }

      nav   { grid-area: nav; overflow-y: auto; }
      main  { grid-area: main; overflow-y: auto; }
      aside { grid-area: outline; overflow-y: auto; }
    }

    ul { margin: 0; padding-left: 1.2em; }
    .outline-list { list-style: none; padding-left: 0; }
    .outline-list li { margin: 0; }
    nav a.active { font-weight: bold; color: #0077cc; }
  </style>
</head>
<body>
<header>
  <div id="brand">GNUEWE</div>
  <div id="actions">
    <button onclick="togglePanel('nav')">Toggle Nav</button>
    <button onclick="togglePanel('aside')">Toggle Outline</button>
  </div>
</header>

<div class="layout">
  <nav>
    <?php renderNav($files, $currentFile); ?>
  </nav>
  <aside>
    <?= $outlineHtml ?>
  </aside>
  <main>
    <article><?= $contentHtml ?></article>
  </main>
</div>

<script>
  function togglePanel(panel) {
    const el = document.querySelector(panel);
    el.hidden = !el.hidden;
  }
</script>
</body>
</html>
