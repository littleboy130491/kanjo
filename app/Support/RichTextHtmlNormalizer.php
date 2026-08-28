<?php

namespace App\Support;

class RichTextHtmlNormalizer
{
    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public static function normalizeArray(array $values): array
    {
        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $values[$key] = self::normalizeArray($value);

                continue;
            }

            if (! is_string($value)) {
                continue;
            }

            $values[$key] = self::normalize($value);
        }

        return $values;
    }

    public static function normalize(string $html): string
    {
        $html = self::normalizeHeadingBreaks($html);

        return self::wrapListItemTextWithParagraphs($html);
    }

    public static function isBlankHtml(?string $html): bool
    {
        if (! is_string($html) || trim($html) === '') {
            return true;
        }

        if (preg_match('/<(img|table|ul|ol|li|h[1-6])\b/i', $html) === 1) {
            return false;
        }

        $text = trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $text = preg_replace('/\x{00A0}/u', ' ', $text) ?? $text;

        return trim($text) === '';
    }

    private static function normalizeHeadingBreaks(string $html): string
    {
        $updated = preg_replace(
            '/<h2>(.*?)<br\s*\/?>(.*?)<\/h2>/is',
            '<h3>$1 — $2</h3>',
            $html,
        );

        return is_string($updated) ? $updated : $html;
    }

    private static function wrapListItemTextWithParagraphs(string $html): string
    {
        if (
            ! str_contains($html, '<ol')
            && ! str_contains($html, '<ul')
            && ! str_contains($html, '<li')
        ) {
            return $html;
        }

        $internalErrors = libxml_use_internal_errors(true);

        $document = new \DOMDocument('1.0', 'UTF-8');
        $encodedHtml = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
        $document->loadHTML(
            "<!DOCTYPE html><html><body>{$encodedHtml}</body></html>",
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );

        $listItems = $document->getElementsByTagName('li');

        foreach ($listItems as $listItem) {
            $children = [];

            foreach ($listItem->childNodes as $childNode) {
                $children[] = $childNode;
            }

            $paragraph = null;

            foreach ($children as $childNode) {
                if (self::isBlockElement($childNode)) {
                    $paragraph = null;

                    continue;
                }

                $isLeadingWhitespaceText = $childNode->nodeType === XML_TEXT_NODE
                    && trim((string) $childNode->nodeValue) === ''
                    && $paragraph === null;

                if ($isLeadingWhitespaceText) {
                    continue;
                }

                if ($paragraph === null) {
                    $paragraph = $document->createElement('p');
                    $listItem->insertBefore($paragraph, $childNode);
                }

                $paragraph->appendChild($childNode);
            }
        }

        $body = $document->getElementsByTagName('body')->item(0);

        if (! $body) {
            libxml_clear_errors();
            libxml_use_internal_errors($internalErrors);

            return $html;
        }

        $normalized = '';

        foreach ($body->childNodes as $childNode) {
            $normalized .= $document->saveHTML($childNode);
        }

        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        return $normalized;
    }

    private static function isBlockElement(\DOMNode $node): bool
    {
        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return false;
        }

        return in_array(strtolower((string) $node->nodeName), [
            'p',
            'ol',
            'ul',
            'table',
            'thead',
            'tbody',
            'tr',
            'td',
            'th',
            'blockquote',
            'pre',
            'h1',
            'h2',
            'h3',
            'h4',
            'h5',
            'h6',
            'div',
        ], true);
    }
}
