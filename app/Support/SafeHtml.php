<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Allow-list cleaner for HTML the SEO agency writes into page bodies and
 * intros. Public pages render it with v-html, so anything that can run
 * script is removed: event attributes, style, javascript: and data: URLs,
 * and script/style/iframe/form tags with their contents. What the article
 * stylesheet supports is kept: headings, paragraphs, lists, links,
 * tables, quotes and images.
 *
 * Also: an h1 becomes an h2 (the page has its own H1), a table outside an
 * .edu-table-wrap div gets one so wide tables scroll on phones, and links
 * to Peptidemap pages lose target=_blank (the visual editor adds it to
 * every link).
 */
class SafeHtml
{
    /** Tags kept, and the attributes each may carry. */
    private const TAGS = [
        'p' => [], 'br' => [], 'hr' => [],
        'h2' => ['id'], 'h3' => ['id'], 'h4' => ['id'], 'h5' => ['id'], 'h6' => ['id'],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [],
        'sub' => [], 'sup' => [], 'mark' => [], 'small' => [], 'cite' => [],
        'abbr' => ['title'],
        'a' => ['href', 'title', 'target', 'rel'],
        'ul' => [], 'ol' => ['start'], 'li' => [],
        'dl' => [], 'dt' => [], 'dd' => [],
        'blockquote' => [], 'code' => [], 'pre' => [],
        'table' => [], 'caption' => [], 'thead' => [], 'tbody' => [], 'tfoot' => [], 'tr' => [],
        'th' => ['colspan', 'rowspan', 'scope'], 'td' => ['colspan', 'rowspan'],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
        'figure' => [], 'figcaption' => [],
        'div' => ['class'], 'span' => [],
    ];

    /** Removed together with everything inside them. */
    private const DROP = [
        'script', 'style', 'iframe', 'frame', 'frameset', 'object', 'embed', 'applet',
        'form', 'input', 'button', 'select', 'option', 'textarea', 'label',
        'noscript', 'template', 'svg', 'math', 'canvas', 'audio', 'video', 'source',
        'link', 'meta', 'base', 'head', 'title',
    ];

    /** Classes the article stylesheet defines. */
    private const CLASSES = ['edu-table-wrap'];

    private const REL_TOKENS = ['nofollow', 'noopener', 'noreferrer', 'sponsored', 'ugc'];

    public static function clean(?string $html): ?string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return null;
        }

        // Non-ASCII goes in as numeric entities so no parser default can
        // misread UTF-8 ("—" as "â€”"); the meta tag makes the output
        // UTF-8 again.
        $ascii = mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, 0x1FFFFF], 'UTF-8');

        $doc = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML(
            '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>'
            .$ascii
            .'</body></html>',
            LIBXML_NONET
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $doc->getElementsByTagName('body')->item(0);
        if (! $body) {
            return null;
        }

        self::cleanChildren($body);
        self::wrapTables($doc, $body);

        $out = '';
        foreach (iterator_to_array($body->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }
        $out = trim($out);

        // An emptied visual editor leaves "<p><br></p>".
        if (trim(strip_tags($out, '<img><table><hr>')) === '') {
            return null;
        }

        return $out;
    }

    private static function cleanChildren(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                continue;
            }
            if (! $child instanceof DOMElement) {
                // Comments, processing instructions, CDATA.
                $node->removeChild($child);

                continue;
            }

            $tag = strtolower($child->tagName);
            if (in_array($tag, self::DROP, true)) {
                $node->removeChild($child);

                continue;
            }
            if ($tag === 'h1') {
                $child = self::rename($child, 'h2');
                $tag = 'h2';
            }
            if (! array_key_exists($tag, self::TAGS)) {
                // Unknown wrapper (font, center, section...): keep its content.
                self::cleanChildren($child);
                self::unwrap($child);

                continue;
            }

            self::cleanAttributes($child, self::TAGS[$tag]);
            if ($tag === 'img' && ! $child->hasAttribute('src')) {
                $node->removeChild($child);

                continue;
            }
            if ($tag === 'a') {
                self::fixLinkTarget($child);
            }
            self::cleanChildren($child);
        }
    }

    /** @param list<string> $allowed */
    private static function cleanAttributes(DOMElement $el, array $allowed): void
    {
        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->name);
            $value = trim($attr->value);

            if (! in_array($name, $allowed, true)) {
                $el->removeAttribute($attr->name);

                continue;
            }

            $keep = match ($name) {
                'href' => self::safeUrl($value, true),
                'src' => self::safeUrl($value, false),
                'target' => $value === '_blank',
                'colspan', 'rowspan', 'start', 'width', 'height' => ctype_digit($value) && strlen($value) <= 4,
                'id' => (bool) preg_match('/^[A-Za-z][\w-]{0,63}$/', $value),
                'scope' => in_array($value, ['row', 'col', 'rowgroup', 'colgroup'], true),
                default => true,
            };

            if ($keep && $name === 'class') {
                $classes = array_values(array_intersect(preg_split('/\s+/', $value) ?: [], self::CLASSES));
                if ($classes === []) {
                    $keep = false;
                } else {
                    $el->setAttribute('class', implode(' ', $classes));

                    continue;
                }
            }
            if ($keep && $name === 'rel') {
                $tokens = array_values(array_intersect(preg_split('/\s+/', strtolower($value)) ?: [], self::REL_TOKENS));
                if ($tokens === []) {
                    $keep = false;
                } else {
                    $el->setAttribute('rel', implode(' ', $tokens));

                    continue;
                }
            }

            if (! $keep) {
                $el->removeAttribute($attr->name);
            }
        }
    }

    /**
     * http(s) everywhere; links may also be mailto:, tel:, site-relative
     * or #anchors. Whitespace and control characters are removed before
     * the scheme check, since browsers ignore them inside "javascript:".
     */
    private static function safeUrl(string $url, bool $isLink): bool
    {
        $compact = preg_replace('/[\x00-\x20\x7F]+/', '', $url) ?? '';
        if ($compact === '') {
            return false;
        }
        if (str_starts_with($compact, '#')) {
            return $isLink;
        }
        if (str_starts_with($compact, '/')) {
            return true;
        }
        if (preg_match('/^([a-z][a-z0-9+.\-]*):/i', $compact, $m)) {
            $scheme = strtolower($m[1]);

            return in_array($scheme, $isLink ? ['http', 'https', 'mailto', 'tel'] : ['http', 'https'], true);
        }

        // Relative path with no scheme, e.g. "compare/bpc-157".
        return true;
    }

    /** Internal links open in the same tab; external ones get noopener. */
    private static function fixLinkTarget(DOMElement $a): void
    {
        $href = $a->getAttribute('href');
        $internal = $href === ''
            || (str_starts_with($href, '/') && ! str_starts_with($href, '//'))
            || str_starts_with($href, '#')
            || (bool) preg_match('#^https?://(www\.)?peptidemap\.com(/|$)#i', $href);

        if ($internal) {
            $a->removeAttribute('target');
            $rel = array_values(array_diff(preg_split('/\s+/', $a->getAttribute('rel')) ?: [], ['noopener', 'noreferrer', '']));
            if ($rel === []) {
                $a->removeAttribute('rel');
            } else {
                $a->setAttribute('rel', implode(' ', $rel));
            }

            return;
        }
        if ($a->getAttribute('target') === '_blank') {
            $rel = preg_split('/\s+/', $a->getAttribute('rel')) ?: [];
            $rel[] = 'noopener';
            $a->setAttribute('rel', implode(' ', array_values(array_unique(array_filter($rel)))));
        }
    }

    private static function wrapTables(DOMDocument $doc, DOMElement $root): void
    {
        foreach (iterator_to_array($root->getElementsByTagName('table')) as $table) {
            $parent = $table->parentNode;
            if ($parent instanceof DOMElement
                && in_array('edu-table-wrap', preg_split('/\s+/', $parent->getAttribute('class')) ?: [], true)) {
                continue;
            }
            $wrap = $doc->createElement('div');
            $wrap->setAttribute('class', 'edu-table-wrap');
            $parent->replaceChild($wrap, $table);
            $wrap->appendChild($table);
        }
    }

    private static function rename(DOMElement $el, string $tag): DOMElement
    {
        $new = $el->ownerDocument->createElement($tag);
        foreach (iterator_to_array($el->attributes) as $attr) {
            $new->setAttribute($attr->name, $attr->value);
        }
        while ($el->firstChild) {
            $new->appendChild($el->firstChild);
        }
        $el->parentNode->replaceChild($new, $el);

        return $new;
    }

    private static function unwrap(DOMElement $el): void
    {
        $parent = $el->parentNode;
        while ($el->firstChild) {
            $parent->insertBefore($el->firstChild, $el);
        }
        $parent->removeChild($el);
    }
}
