<?php

namespace Tests\Unit;

use App\Services\DocumentApi\MarkdownOrHtml;
use PHPUnit\Framework\TestCase;

class MarkdownOrHtmlTest extends TestCase
{
    public function test_it_detects_html_tags(): void
    {
        $this->assertTrue(MarkdownOrHtml::looksLikeHtml('<p>Hello</p>'));
        $this->assertFalse(MarkdownOrHtml::looksLikeHtml('## Hello'));
    }

    public function test_it_converts_markdown_and_keeps_html(): void
    {
        $markdown = MarkdownOrHtml::toHtml('## Hello');
        $html = MarkdownOrHtml::toHtml('<p>Hello</p>');

        $this->assertStringContainsString('<h2>Hello</h2>', $markdown);
        $this->assertStringContainsString('<p>Hello</p>', $html);
    }

    public function test_empty_string_stays_empty(): void
    {
        $this->assertSame('', MarkdownOrHtml::toHtml(''));
        $this->assertSame('', MarkdownOrHtml::toHtml(null));
    }
}
