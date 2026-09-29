<?php

namespace App\Support;

/**
 * Small Markdown subset for editorial pages: headings, paragraphs, lists,
 * tables, links, and emphasis. Input is escaped before tags are added.
 */
class SimpleMarkdown
{
    public static function toHtml(string $markdown): string
    {
        $markdown = str_replace(["\r\n", "\r"], "\n", trim($markdown));
        if ($markdown === '') {
            return '';
        }

        $blocks = preg_split("/\n{2,}/", $markdown) ?: [];
        $html = [];
        foreach ($blocks as $block) {
            $rendered = self::renderBlock(trim($block));
            if ($rendered !== '') {
                $html[] = $rendered;
            }
        }

        return implode("\n", $html);
    }

    private static function renderBlock(string $block): string
    {
        if ($block === '') {
            return '';
        }

        if (preg_match('/^(-{3,}|\*{3,})$/', $block)) {
            return '<hr>';
        }

        if (preg_match('/^(#{2,4})\s+(.+)$/', $block, $m) && ! str_contains($block, "\n")) {
            $level = strlen($m[1]);

            return '<h'.$level.'>'.self::inline($m[2]).'</h'.$level.'>';
        }

        $lines = preg_split("/\n/", $block) ?: [];
        if (self::isTable($lines)) {
            return self::renderTable($lines);
        }
        if (self::isList($lines, '/^[-*]\s+/')) {
            return self::renderList($lines, 'ul', '/^[-*]\s+/');
        }
        if (self::isList($lines, '/^\d+\.\s+/')) {
            return self::renderList($lines, 'ol', '/^\d+\.\s+/');
        }

        return '<p>'.self::inline(preg_replace("/\n+/", ' ', $block) ?? $block).'</p>';
    }

    private static function isTable(array $lines): bool
    {
        if (count($lines) < 2) {
            return false;
        }
        foreach ($lines as $line) {
            if (! str_contains($line, '|')) {
                return false;
            }
        }

        return (bool) preg_match('/^\s*\|?\s*:?-{3,}:?\s*(\|\s*:?-{3,}:?\s*)+\|?\s*$/', $lines[1]);
    }

    private static function renderTable(array $lines): string
    {
        $headers = self::tableCells($lines[0]);
        $body = array_slice($lines, 2);
        $html = '<div class="edu-table-wrap"><table><thead><tr>';
        foreach ($headers as $cell) {
            $html .= '<th>'.self::inline($cell).'</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($body as $line) {
            $html .= '<tr>';
            foreach (self::tableCells($line) as $cell) {
                $html .= '<td>'.self::inline($cell).'</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table></div>';

        return $html;
    }

    private static function tableCells(string $line): array
    {
        $line = trim($line);
        if (str_starts_with($line, '|')) {
            $line = substr($line, 1);
        }
        if (str_ends_with($line, '|')) {
            $line = substr($line, 0, -1);
        }

        return array_map('trim', explode('|', $line));
    }

    private static function isList(array $lines, string $pattern): bool
    {
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            if (! preg_match($pattern, $line)) {
                return false;
            }
        }

        return true;
    }

    private static function renderList(array $lines, string $tag, string $pattern): string
    {
        $html = '<'.$tag.'>';
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $item = preg_replace($pattern, '', $line, 1) ?? $line;
            $html .= '<li>'.self::inline($item).'</li>';
        }
        $html .= '</'.$tag.'>';

        return $html;
    }

    private static function inline(string $text): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace_callback(
            '/\[([^\]]+)\]\(([^)\s]+)\)/',
            function (array $m) {
                $url = html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if (! preg_match('#^(https://|http://|/)#', $url) || str_starts_with(strtolower($url), 'javascript:')) {
                    return $m[1];
                }
                $safe = htmlspecialchars($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');

                return '<a href="'.$safe.'">'.$m[1].'</a>';
            },
            $text
        ) ?? $text;
        $text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text) ?? $text;
        $text = preg_replace('/\*([^*\n]+)\*/', '<em>$1</em>', $text) ?? $text;

        return $text;
    }
}
