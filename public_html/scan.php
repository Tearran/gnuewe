<?php
$baseDir = __DIR__ . '/docs';
$output  = [];

// --- Scan all .md files ---
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir));
foreach ($rii as $file) {
    if ($file->isDir()) continue;
    if (pathinfo($file->getFilename(), PATHINFO_EXTENSION) !== 'md') continue;

    $content = file_get_contents($file->getPathname());

    // --- Look for YAML block ---
    if (preg_match('/^---\s*(.*?)\s*---/s', $content, $matches)) {
        $yaml = trim($matches[1]);

        $meta = [];
        $lines = preg_split('/\r\n|\r|\n/', $yaml);
        $key = null;
        foreach ($lines as $line) {
            // key: value
            if (preg_match('/^(\w+):\s*(.*)$/', $line, $m)) {
                $key = $m[1];
                $val = trim($m[2]);
                if ($val === '') {
                    $meta[$key] = [];
                } else {
                    $meta[$key] = $val;
                }
            }
            // - item
            elseif (preg_match('/^\s*-\s*(.*)$/', $line, $m) && $key) {
                if (!is_array($meta[$key])) $meta[$key] = [];
                $meta[$key][] = trim($m[1]);
            }
        }

        // --- Relative URL inside docs ---
        $relPath = str_replace($baseDir, '', $file->getPathname());
        $relPath = str_replace('\\', '/', $relPath); // for Windows paths
        $relPath = '/' . ltrim($relPath, '/');       // URL-friendly

        // --- Generic output ---
        $entry = $meta;
        $entry['url'] = $relPath;  // use 'url' instead of 'file'
        $output[] = $entry;
    }
}

// --- JSON output ---
header('Content-Type: application/json');
echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
