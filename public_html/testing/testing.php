<?php
// index.php: Single-page PHP Markdown documentation app

// ---------- CONFIG ----------
define('PAGES_DIR', __DIR__ . '/pages/');
define('THEMES_JSON', __DIR__ . '/json/themes.json');
define('CACHE_DIR', __DIR__ . '/cache/');
if (!file_exists(CACHE_DIR)) mkdir(CACHE_DIR);

// ---------- BASIC ROUTER FOR AJAX ----------
if (isset($_GET['api'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_GET['api'];
    if ($action === 'list') {
        // List Markdown files in pages/
        $files = glob(PAGES_DIR . '*.md');
        $pages = [];
        foreach ($files as $file) {
            $title = basename($file, '.md');
            $meta = get_page_metadata($file);
            $pages[] = [
                'id' => $title,
                'title' => $meta['title'],
                'tags' => $meta['tags'],
            ];
        }
        echo json_encode($pages);
        exit;
    } elseif ($action === 'page' && isset($_GET['id'])) {
        $id = preg_replace('/[^A-Za-z0-9_\-]/', '', $_GET['id']);
        $file = PAGES_DIR . $id . '.md';
        if (file_exists($file)) {
            list($html, $toc) = get_cached_markdown($file);
            echo json_encode([
                'html' => $html,
                'toc' => $toc,
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Page not found']);
        }
        exit;
    } elseif ($action === 'themes') {
        if (file_exists(THEMES_JSON)) {
            echo file_get_contents(THEMES_JSON);
        } else {
            echo json_encode([]);
        }
        exit;
    }
    http_response_code(400);
    echo json_encode(['error' => 'Invalid API']);
    exit;
}

// ---------- HELPER: GET PAGE METADATA ----------
function get_page_metadata($file) {
    $lines = file($file, FILE_IGNORE_NEW_LINES);
    $title = '';
    $tags = [];
    foreach ($lines as $line) {
        if (preg_match('/^# (.+)/', $line, $m)) $title = trim($m[1]);
        if (preg_match('/^tags:\s*(.+)/i', $line, $m)) $tags = preg_split('/\s*,\s*/', $m[1]);
        if ($title && $tags) break;
    }
    if (!$title) $title = basename($file, '.md');
    return ['title' => $title, 'tags' => $tags];
}

// ---------- HELPER: PARSE & CACHE MARKDOWN ----------
function get_cached_markdown($file) {
    $cachefile = CACHE_DIR . md5_file($file) . '.json';
    if (file_exists($cachefile)) {
        $data = json_decode(file_get_contents($cachefile), true);
        return [$data['html'], $data['toc']];
    }
    $md = file_get_contents($file);
    $parser = new MarkdownParser();
    $html = $parser->text($md);
    $toc = $parser->getToc();
    file_put_contents($cachefile, json_encode(['html' => $html, 'toc' => $toc]));
    return [$html, $toc];
}

// ---------- MARKDOWN PARSER (NO EXTERNAL LIBS) ----------
class MarkdownParser {
    private $toc = [];
    public function text($md) {
        $lines = preg_split('/\r?\n/', $md);
        $html = '';
        $in_list = false; $in_code = false; $in_ul = false; $in_ol = false; $in_blockquote = false;
        foreach ($lines as $line) {
            // Code block
            if (preg_match('/^```(\w*)/', $line, $m)) {
                if (!$in_code) { $in_code = true; $html .= "<pre><code" . ($m[1] ? " class=\"lang-{$m[1]}\"" : "") . ">"; continue; }
                else { $in_code = false; $html .= "</code></pre>"; continue; }
            }
            if ($in_code) { $html .= htmlspecialchars($line) . "\n"; continue; }
            // Headings
            if (preg_match('/^(#{1,6})\s*(.+)$/', $line, $m)) {
                $level = strlen($m[1]);
                $text = trim($m[2]);
                $id = strtolower(preg_replace('/[^a-z0-9]+/', '-', $text));
                $this->toc[] = ['level' => $level, 'text' => $text, 'id' => $id];
                $html .= "<h$level id=\"$id\">$text</h$level>\n";
                continue;
            }
            // Blockquote
            if (preg_match('/^>\s?(.*)/', $line, $m)) {
                if (!$in_blockquote) { $in_blockquote = true; $html .= "<blockquote>"; }
                $html .= $this->inline($m[1]);
                continue;
            } elseif ($in_blockquote) { $html .= "</blockquote>\n"; $in_blockquote = false; }
            // Unordered list
            if (preg_match('/^[-*]\s+(.+)/', $line, $m)) {
                if (!$in_ul) { $in_ul = true; $html .= "<ul>\n"; }
                $html .= "<li>" . $this->inline($m[1]) . "</li>\n";
                continue;
            } elseif ($in_ul && !trim($line)) { $html .= "</ul>\n"; $in_ul = false; }
            // Ordered list
            if (preg_match('/^\d+\.\s+(.+)/', $line, $m)) {
                if (!$in_ol) { $in_ol = true; $html .= "<ol>\n"; }
                $html .= "<li>" . $this->inline($m[1]) . "</li>\n";
                continue;
            } elseif ($in_ol && !trim($line)) { $html .= "</ol>\n"; $in_ol = false; }
            // Horizontal rule
            if (preg_match('/^---+$/', trim($line))) { $html .= "<hr>\n"; continue; }
            // Paragraph
            if (trim($line)) $html .= "<p>" . $this->inline($line) . "</p>\n";
        }
        if ($in_ul) $html .= "</ul>\n";
        if ($in_ol) $html .= "</ol>\n";
        if ($in_blockquote) $html .= "</blockquote>\n";
        return $html;
    }
    public function getToc() { return $this->toc; }
    private function inline($text) {
        // Inline code
        $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);
        // Bold, Italic
        $text = preg_replace('/\*\*\*([^\*]+)\*\*\*/', '<b><i>$1</i></b>', $text);
        $text = preg_replace('/\*\*([^\*]+)\*\*/', '<b>$1</b>', $text);
        $text = preg_replace('/\*([^\*]+)\*/', '<i>$1</i>', $text);
        // Strikethrough
        $text = preg_replace('/~~([^~]+)~~/', '<s>$1</s>', $text);
        // Links
        $text = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2" target="_blank" rel="noopener">$1</a>', $text);
        // Images
        $text = preg_replace('/!\[(.*?)\]\((.*?)\)/', '<img src="$2" alt="$1">', $text);
        return $text;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PHP Markdown Docs</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="app.css">
</head>
<style>
    :root {
    --bg: #18191c;
    --card: #23242a;
    --text: #e7eaf0;
    --accent: #7fb4ff;
    --border: #32343b;
    --navbar: #1a1b1e;
    --panel: #202127;
    --btn: #25262b;
    --btn-hover: #3658a6;
    --focus: #fff2;
    --shadow: 0 2px 8px #0005;
    --transition: 0.2s cubic-bezier(.4,0,.2,1);
}
html, body {
    margin: 0; padding: 0;
    background: var(--bg); color: var(--text);
    font-family: system-ui, sans-serif;
    min-height: 100vh;
}
.navbar {
    display: flex; gap: 1rem;
    background: var(--navbar);
    border-bottom: 1px solid var(--border);
    padding: 0.5rem 1rem;
    position: sticky; top: 0; z-index: 100;
}
.navbtn {
    background: var(--btn);
    color: var(--text);
    border: none;
    border-radius: 5px;
    padding: 0.5em 1em;
    margin-right: .5em;
    font-size: 1rem;
    cursor: pointer;
    display: flex; align-items: center; gap: .5em;
    transition: background var(--transition);
    box-shadow: none;
}
.navbtn:focus-visible, .navbtn[aria-expanded="true"] {
    outline: 2px solid var(--accent);
    background: var(--btn-hover);
}
.navbtn:hover { background: var(--btn-hover); }
.sidepanel {
    position: fixed;
    top: 3.2rem;
    left: 0;
    width: 320px;
    max-width: 90vw;
    height: calc(100vh - 3.2rem);
    background: var(--panel);
    color: var(--text);
    box-shadow: var(--shadow);
    border-right: 1px solid var(--border);
    transform: translateX(-105%);
    transition: transform var(--transition);
    z-index: 101;
    overflow-y: auto;
    will-change: transform;
    opacity: 0;
    pointer-events: none;
}
.sidepanel.open {
    transform: translateX(0);
    opacity: 1;
    pointer-events: auto;
}
.card.content {
    background: var(--card);
    margin: 2rem auto 2rem auto;
    max-width: 800px;
    box-shadow: var(--shadow);
    border-radius: 10px;
    padding: 2rem;
    min-height: 60vh;
    border: 1px solid var(--border);
    transition: background var(--transition), color var(--transition);
}
footer {
    margin-top: 2rem;
    text-align: center;
    color: #aaa;
    font-size: 0.95em;
    padding: 1.5rem 0;
}
a { color: var(--accent); text-decoration: underline; }
a:focus-visible { outline: 2px solid var(--accent); }
pre, code {
    font-family: 'Fira Mono', 'Consolas', monospace;
    background: #20232a;
    color: #b5e853;
    border-radius: 4px;
    padding: 2px 6px;
}
pre { padding: 1em; margin: 1em 0; overflow: auto; }
h1, h2, h3, h4, h5, h6 { color: var(--accent); margin-top: 1.5em; }
ul, ol { margin: 1em 0 1em 2em; }
hr { border: none; border-top: 1px solid var(--border); margin: 2em 0; }
blockquote {
    border-left: 4px solid var(--accent);
    padding-left: 1em;
    color: #b0bad4;
    margin: 1em 0;
    background: #232a33;
}
img { max-width: 100%; border-radius: 6px; }
#panel-outline ul {
    list-style: none;
    padding-left: 0;
}
#panel-outline li {
    margin-left: calc(var(--depth, 1) * 1em);
}
#panel-outline a {
    color: inherit;
    text-decoration: none;
    display: block;
    padding: .3em .5em;
    border-radius: 5px;
    transition: background var(--transition);
}
#panel-outline a.active, #panel-outline a:focus-visible {
    background: var(--accent);
    color: #222;
}
#panel-theme {
    padding: 1em 1.5em;
}
.theme-list {
    display: flex; flex-wrap: wrap; gap: .5em;
}
.theme-btn {
    background: var(--btn);
    border: 1px solid var(--border);
    color: var(--text);
    padding: .5em 1em;
    border-radius: 4px;
    cursor: pointer;
    transition: background var(--transition), color var(--transition);
}
.theme-btn.active, .theme-btn:focus-visible {
    background: var(--accent);
    color: #222;
}
@media (max-width: 600px) {
    .card.content { padding: 1rem; }
    .sidepanel { width: 97vw; }
}
::-webkit-scrollbar { width: 8px; background: #18191c; }
::-webkit-scrollbar-thumb { background: #373a40; border-radius: 8px; }

[aria-hidden="true"] { display: none !important; }
</style>

<body>
    <nav class="navbar" role="navigation" aria-label="Main">
        <button class="navbtn" id="btn-home" aria-label="Home" tabindex="0"><span>🏠</span> Home</button>
        <button class="navbtn" id="btn-search" aria-controls="panel-search" aria-expanded="false" tabindex="0"><span>🔍</span> Search</button>
        <button class="navbtn" id="btn-outline" aria-controls="panel-outline" aria-expanded="false" tabindex="0"><span>🗂️</span> Outline</button>
        <button class="navbtn" id="btn-theme" aria-controls="panel-theme" aria-expanded="false" tabindex="0"><span>🎨</span> Theme</button>
    </nav>
    <div id="panel-search" class="sidepanel" role="region" aria-label="Search panel" aria-hidden="true"></div>
    <div id="panel-outline" class="sidepanel" role="region" aria-label="Document outline" aria-hidden="true"></div>
    <div id="panel-theme" class="sidepanel" role="region" aria-label="Theme dashboard" aria-hidden="true"></div>
    <main id="main" tabindex="0" aria-live="polite">
        <div id="content-card" class="card content" role="main" aria-label="Documentation content"></div>
    </main>
    <footer>
        <div>PHP Markdown Docs • Powered by <a href="https://github.com/Tearran" target="_blank">Tearran</a></div>
    </footer>
    <script src="app.js"></script>
</body>
<script>
    // app.js: SPA app logic for PHP Markdown Docs

const panels = {
    search: document.getElementById('panel-search'),
    outline: document.getElementById('panel-outline'),
    theme: document.getElementById('panel-theme')
};

const navBtns = {
    home: document.getElementById('btn-home'),
    search: document.getElementById('btn-search'),
    outline: document.getElementById('btn-outline'),
    theme: document.getElementById('btn-theme')
};

const contentCard = document.getElementById('content-card');
const main = document.getElementById('main');

let pageList = [];
let currentPage = '';
let currentToc = [];
let activeTheme = 'default';

function fetchPages() {
    return fetch('?api=list').then(r => r.json()).then(pages => {
        pageList = pages;
        return pages;
    });
}

function fetchPage(id) {
    return fetch(`?api=page&id=${encodeURIComponent(id)}`).then(r => r.json());
}

function fetchThemes() {
    return fetch('?api=themes').then(r => r.json());
}

function renderPage(id, pushState = true) {
    fetchPage(id).then(data => {
        contentCard.innerHTML = data.html;
        currentToc = data.toc;
        renderOutline();
        if (pushState) updateHash({page: id});
        setTimeout(() => {
            focusFirstHeading();
            highlightActiveOutline();
        }, 30);
    });
    currentPage = id;
}

function renderOutline() {
    const toc = currentToc;
    if (!toc || !toc.length) {
        panels.outline.innerHTML = "<p>No outline available</p>";
        return;
    }
    let html = '<ul>';
    toc.forEach(h => {
        html += `<li style="--depth:${h.level-1}"><a href="#${h.id}" data-id="${h.id}" tabindex="0">${h.text}</a></li>`;
    });
    html += '</ul>';
    panels.outline.innerHTML = html;
    panels.outline.querySelectorAll('a').forEach(a => {
        a.addEventListener('click', e => {
            e.preventDefault();
            document.getElementById(a.dataset.id)?.scrollIntoView({behavior: "smooth"});
            highlightActiveOutline(a.dataset.id);
        });
        a.addEventListener('keydown', e => {
            if (e.key === 'Enter' || e.key === ' ') a.click();
        });
    });
}

function highlightActiveOutline(id) {
    const links = panels.outline.querySelectorAll('a');
    let activeId = id;
    if (!activeId) {
        let headings = Array.from(contentCard.querySelectorAll('h1,h2,h3,h4,h5,h6'));
        let y = window.scrollY || document.documentElement.scrollTop || document.body.scrollTop;
        let best = null, bestTop = -1;
        for (let h of headings) {
            let rect = h.getBoundingClientRect();
            if (rect.top >= 0 && rect.top < 200) {
                best = h; bestTop = rect.top;
            }
        }
        activeId = best?.id;
    }
    links.forEach(a => a.classList.toggle('active', a.dataset.id === activeId));
}

function renderSearch() {
    let html = `
        <label for="search-input" style="display:block;font-size:1.1em;margin-bottom:.5em;">Search Pages</label>
        <input id="search-input" type="text" aria-label="Search by title or tag" placeholder="Type to search..." autocomplete="off" style="width:100%;padding:.5em;font-size:1em;">
        <ul id="search-results" tabindex="0" role="listbox" style="margin-top:1em;"></ul>
    `;
    panels.search.innerHTML = html;
    const input = panels.search.querySelector('#search-input');
    const results = panels.search.querySelector('#search-results');
    let filtered = pageList.slice();
    function drawResults() {
        results.innerHTML = filtered.length ?
            filtered.map((p, i) =>
                `<li><a href="#" data-id="${p.id}" tabindex="-1">${p.title} ${p.tags.length ? `<span style="color:#7fb4ff;font-size:.9em;">[${p.tags.join(', ')}]</span>` : ''}</a></li>`).join('')
            : `<li>No results</li>`;
    }
    drawResults();
    input.focus();
    input.addEventListener('input', () => {
        const q = input.value.trim().toLowerCase();
        filtered = pageList.filter(p =>
            p.title.toLowerCase().includes(q) ||
            (p.tags && p.tags.some(tag => tag.toLowerCase().includes(q)))
        );
        drawResults();
    });
    results.addEventListener('click', e => {
        if (e.target.matches('a[data-id]')) {
            renderPage(e.target.dataset.id);
            closePanels();
        }
    });
    // Keyboard navigation (ArrowDown/Up, Enter)
    input.addEventListener('keydown', e => {
        let links = results.querySelectorAll('a[data-id]');
        let idx = Array.from(links).findIndex(a => a === document.activeElement);
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (links.length) links[(idx+1)%links.length].focus();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (links.length) links[(idx-1+links.length)%links.length].focus();
        } else if (e.key === 'Enter' && idx >= 0) {
            links[idx].click();
        }
    });
}

function renderThemes() {
    fetchThemes().then(themes => {
        let html = '<h3>Themes</h3><div class="theme-list">';
        Object.entries(themes).forEach(([name, obj]) => {
            html += `<button class="theme-btn${activeTheme===name?' active':''}" data-theme="${name}" style="background:${obj.bg};color:${obj.text};">${obj.label||name}</button>`;
        });
        html += '</div>';
        panels.theme.innerHTML = html;
        panels.theme.querySelectorAll('.theme-btn').forEach(btn => {
            btn.onclick = () => setTheme(btn.dataset.theme, themes[btn.dataset.theme]);
        });
    });
}

function setTheme(name, obj) {
    activeTheme = name;
    if (obj) {
        for (let k of Object.keys(obj)) {
            document.documentElement.style.setProperty(`--${k}`, obj[k]);
        }
    }
    document.querySelectorAll('.theme-btn').forEach(b => b.classList.toggle('active', b.dataset.theme===name));
    localStorage.setItem('theme', name);
}

function openPanel(panel) {
    closePanels();
    if (panel && panels[panel]) {
        panels[panel].classList.add('open');
        panels[panel].setAttribute('aria-hidden', 'false');
        navBtns[panel].setAttribute('aria-expanded', 'true');
        if (panel === 'search') renderSearch();
        if (panel === 'outline') renderOutline();
        if (panel === 'theme') renderThemes();
        panels[panel].focus();
    }
}

function closePanels() {
    Object.keys(panels).forEach(k => {
        panels[k].classList.remove('open');
        panels[k].setAttribute('aria-hidden', 'true');
        navBtns[k].setAttribute('aria-expanded', 'false');
    });
}

function updateHash(params) {
    let hash = Object.assign(getHashParams(), params);
    let s = Object.entries(hash).map(([k,v])=>`${k}=${encodeURIComponent(v)}`).join('&');
    window.location.hash = s ? '#' + s : '';
}

function getHashParams() {
    let h = window.location.hash.replace(/^#/, '');
    let o = {};
    h.split('&').forEach(pair=>{
        let [k,v]=pair.split('=');
        if(k) o[k]=decodeURIComponent(v||'');
    });
    return o;
}

function loadFromHash() {
    let params = getHashParams();
    if (params.page) {
        renderPage(params.page, false);
    } else {
        renderHome();
    }
}

function renderHome() {
    let html = `<h1>Welcome</h1>
        <p>This is a PHP Markdown documentation app. Use the <button class="navbtn" style="display:inline" aria-label="Search">Search</button> or <button class="navbtn" style="display:inline" aria-label="Outline">Outline</button> to navigate pages.</p>
        <ul>${pageList.map(p=>`<li><a href="#" data-id="${p.id}">${p.title}</a> ${p.tags.length?`<span style="color:#7fb4ff;font-size:.9em;">[${p.tags.join(', ')}]</span>`:''}</li>`).join('')}</ul>
    `;
    contentCard.innerHTML = html;
    contentCard.querySelectorAll('a[data-id]').forEach(a=>{
        a.onclick = e => { e.preventDefault(); renderPage(a.dataset.id); };
    });
    updateHash({});
}

function focusFirstHeading() {
    let h = contentCard.querySelector('h1,h2,h3,h4');
    if (h) h.setAttribute('tabindex', '0'), h.focus();
}

function setupNav() {
    navBtns.home.onclick = () => { renderHome(); closePanels(); navBtns.home.focus(); };
    navBtns.search.onclick = () => openPanel('search');
    navBtns.outline.onclick = () => openPanel('outline');
    navBtns.theme.onclick = () => openPanel('theme');
    Object.values(navBtns).forEach(btn => {
        btn.addEventListener('keydown', e => {
            if (e.key === 'ArrowRight') {
                let keys = Object.keys(navBtns);
                let idx = keys.findIndex(k=>navBtns[k]===btn);
                navBtns[keys[(idx+1)%keys.length]].focus();
            }
            if (e.key === 'ArrowLeft') {
                let keys = Object.keys(navBtns);
                let idx = keys.findIndex(k=>navBtns[k]===btn);
                navBtns[keys[(idx-1+keys.length)%keys.length]].focus();
            }
        });
    });
}

main.addEventListener('keydown', e => {
    if (e.key === 'Tab' && e.shiftKey && document.activeElement === main) {
        // Loop focus to navbar
        navBtns.home.focus(); e.preventDefault();
    }
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closePanels();
    // Open panels with shortcuts
    if (e.altKey && !e.shiftKey) {
        if (e.key === '1') { navBtns.home.click(); }
        if (e.key === '2') { navBtns.search.click(); }
        if (e.key === '3') { navBtns.outline.click(); }
        if (e.key === '4') { navBtns.theme.click(); }
    }
});
window.addEventListener('scroll', () => highlightActiveOutline());

window.addEventListener('hashchange', loadFromHash);

window.addEventListener('DOMContentLoaded', () => {
    fetchPages().then(() => {
        setupNav();
        // Apply saved theme
        fetchThemes().then(themes => {
            let t = localStorage.getItem('theme');
            if (t && themes[t]) setTheme(t, themes[t]);
        });
        loadFromHash();
    });
});
</script>
</html>