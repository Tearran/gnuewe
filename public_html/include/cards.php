<?php
/**
 * Simple Markdown to HTML converter in PHP (no external libraries).
 * Escapes HTML first for security, then applies Markdown conversions.
 * Covers: headings, emphasis, links, lists, images, code, blockquotes, hr, tables, strikethrough.
 */

function simpleMarkdownToHtml($md) {
    // Escape HTML for safety
    $md = htmlspecialchars($md, ENT_QUOTES, 'UTF-8');

    // Code blocks ```lang ... ```
    $md = preg_replace('/```[a-zA-Z0-9]*\n([\s\S]*?)```/', '<pre><code>$1</code></pre>', $md);

    // Inline code `...`
    $md = preg_replace('/`([^`]+)`/', '<code>$1</code>', $md);

    // Headings ######
    for ($i = 6; $i >= 1; $i--) {
        $md = preg_replace('/^' . str_repeat('#', $i) . ' (.*?)$/m', "<h$i>$1</h$i>", $md);
    }

    // Horizontal rule ---
    $md = preg_replace('/^\s*---+\s*$/m', '<hr>', $md);

    // Bold & Italic
    $md = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $md);
    $md = preg_replace('/__(.*?)__/', '<strong>$1</strong>', $md);
    $md = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $md);
    $md = preg_replace('/_(.*?)_/', '<em>$1</em>', $md);
    $md = preg_replace('/~~(.*?)~~/', '<del>$1</del>', $md); // strikethrough

    // Links and images
    $md = preg_replace('/!\[(.*?)\]\((.*?)\)/', '<img src="$2" alt="$1">', $md);
    $md = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2" target="_blank" rel="noopener">$1</a>', $md);

    // Blockquotes
    $md = preg_replace('/^>\s?(.*)$/m', '<blockquote>$1</blockquote>', $md);

    // Unordered lists
    $md = preg_replace_callback('/(?:^[-*] .+(?:\n[-*] .+)*)/m', function ($matches) {
        $items = preg_replace('/^[-*] (.+)$/m', '<li>$1</li>', $matches[0]);
        return "<ul>$items</ul>";
    }, $md);

    // Ordered lists
    $md = preg_replace_callback('/(?:^\d+\. .+(?:\n\d+\. .+)*)/m', function ($matches) {
        $items = preg_replace('/^\d+\. (.+)$/m', '<li>$1</li>', $matches[0]);
        return "<ol>$items</ol>";
    }, $md);

    // Tables (simple pipe tables)
    $md = preg_replace_callback('/\|(.+\|)+\n\|[-| ]+\|\n((\|.*\|)+\n)+/m', function ($matches) {
        $lines = explode("\n", trim($matches[0]));
        $header = array_shift($lines);
        $divider = array_shift($lines);

        $headCells = array_map('trim', explode('|', trim($header, '| ')));
        $thead = '<tr>' . implode('', array_map(fn($c)=>"<th>$c</th>", $headCells)) . '</tr>';

        $rows = '';
        foreach ($lines as $line) {
            if (trim($line) === '') continue;
            $cells = array_map('trim', explode('|', trim($line, '| ')));
            $rows .= '<tr>' . implode('', array_map(fn($c)=>"<td>$c</td>", $cells)) . '</tr>';
        }

        return "<table><thead>$thead</thead><tbody>$rows</tbody></table>";
    }, $md);

    // Paragraphs (only plain text lines, not inside HTML tags)
    $md = preg_replace('/^(?!<)(.+)$/m', '<p>$1</p>', $md);

    return '<div class="markdown">'.$md.'</div>';
}
?>
