<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Keeps the simple formatting produced by the rich text editor and drops
 * everything else (scripts, attributes, embedded content).
 */
class HtmlSanitizer
{
    private const ALLOWED_TAGS = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'ul', 'ol', 'li', 'h2', 'h3', 'h4', 'blockquote', 'a'];

    private const DROPPED_WITH_CONTENT = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'svg', 'math', 'template', 'noscript'];

    public static function clean(?string $html): string
    {
        if (blank($html)) {
            return '';
        }

        $document = new DOMDocument;

        // The wrapper keeps several top-level nodes; the XML prolog forces UTF-8.
        @$document->loadHTML(
            '<?xml encoding="UTF-8"><div id="sanitizer-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET,
        );

        $root = $document->getElementById('sanitizer-root');

        if ($root === null) {
            return '';
        }

        self::cleanChildren($root);

        $clean = '';

        foreach ($root->childNodes as $child) {
            $clean .= $document->saveHTML($child);
        }

        return trim($clean);
    }

    private static function cleanChildren(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if (! $child instanceof DOMElement) {
                if ($child->nodeType !== XML_TEXT_NODE) {
                    $node->removeChild($child);
                }

                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROPPED_WITH_CONTENT, true)) {
                $node->removeChild($child);

                continue;
            }

            self::cleanChildren($child);

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                // Unknown tag: keep its (already cleaned) content only.
                while ($child->firstChild !== null) {
                    $node->insertBefore($child->firstChild, $child);
                }

                $node->removeChild($child);

                continue;
            }

            self::cleanAttributes($child, $tag);
        }
    }

    private static function cleanAttributes(DOMElement $element, string $tag): void
    {
        $href = $tag === 'a' ? trim($element->getAttribute('href')) : '';

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $element->removeAttribute($attribute->nodeName);
        }

        if ($tag !== 'a') {
            return;
        }

        if (preg_match('#^(https?://|mailto:)#i', $href)) {
            $element->setAttribute('href', $href);
            $element->setAttribute('target', '_blank');
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }
}
