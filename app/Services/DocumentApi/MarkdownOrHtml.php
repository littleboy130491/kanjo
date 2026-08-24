<?php

namespace App\Services\DocumentApi;

use App\Support\RichTextHtmlNormalizer;
use Illuminate\Support\Str;

class MarkdownOrHtml
{
    public static function toHtml(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        $html = self::looksLikeHtml($value)
            ? $value
            : Str::markdown($value);

        return RichTextHtmlNormalizer::normalize($html);
    }

    public static function looksLikeHtml(string $value): bool
    {
        return (bool) preg_match('/<\/?[a-zA-Z][a-zA-Z0-9]*(?:\s[^>]*)?>/', $value);
    }
}
