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

<header class="action-bar">
  <div class="actions">
    <button onclick="togglePanel('#doc-links')" title="Toggle Navigation">
      <svg class="icon"><use href="/images/icons.svg#i-book"></use></svg>
    </button>
  </div>

  <div class="actions">
    <button onclick="togglePanel('#doc-outline')" title="Toggle Outline">
      <svg class="icon"><use href="/images/icons.svg#i-list"></use></svg>
    </button>
  </div>
</header>

<div class="layout">
  <!-- Site navigation -->
  <nav id="doc-links" aria-label="Site Navigation" hidden>
    <?php renderNav($files, $currentFile); ?>
  </nav>

  <!-- Main content -->
  <main>
    <article>
      <?= $contentHtml ?>
    </article>
  </main>

  <!-- Page outline -->
  <aside id="doc-outline" aria-label="Page Outline" hidden>
    <?= $outlineHtml ?>
  </aside>
</div>

<footer>
  <!-- footer content or leave for parent include -->
</footer>


<script>
        function togglePanel(panel) {
                const el = document.querySelector(panel);
                el.hidden = !el.hidden;
        }
        (function() {
                const darkPref = localStorage.getItem("dark-mode");
                if (darkPref === "true") {
                        document.body.classList.add("dark-mode");
                        const useEl = document.querySelector("#darkIcon use");
                        if (useEl) {
                                useEl.setAttribute("href", "#i-moon");
                                useEl.setAttributeNS(
                                        "http://www.w3.org/1999/xlink",
                                        "xlink:href",
                                        "#i-moon"
                                );
                        }
                        document
                                .querySelector('button[title="Toggle Dark Mode"]')
                                .setAttribute("aria-pressed", "true");
                }
        })();

        function toggleDarkMode() {
                const isDark = document.body.classList.toggle("dark-mode");
                localStorage.setItem("dark-mode", isDark ? "true" : "false");
                const useEl = document.querySelector("#darkIcon use");
                const hrefVal = isDark ? "#i-moon" : "#i-sun";
                if (useEl) {
                        useEl.setAttribute("href", hrefVal);
                        useEl.setAttributeNS("http://www.w3.org/1999/xlink", "xlink:href", hrefVal);
                }
                document
                        .querySelector('button[title="Toggle Dark Mode"]')
                        .setAttribute("aria-pressed", String(isDark));
        }
</script>
