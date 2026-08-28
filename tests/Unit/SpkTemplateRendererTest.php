<?php

namespace Tests\Unit;

use App\Services\SpkTemplateRenderer;
use PHPUnit\Framework\TestCase;

class SpkTemplateRendererTest extends TestCase
{
    public function test_it_converts_plain_tables_to_tiptap_markup(): void
    {
        $html = '<p>Intro</p><table class="spk-party-table"><tbody><tr><td>Nama</td><td>:</td><td>Debby</td></tr></tbody></table>';

        $converted = SpkTemplateRenderer::toEditableTables($html);

        $this->assertStringContainsString('tableWrapper', $converted);
        $this->assertStringContainsString('<colgroup>', $converted);
        $this->assertStringContainsString('<p>Nama</p>', $converted);
        $this->assertStringContainsString('<p>Debby</p>', $converted);
        $this->assertStringContainsString('colspan="1"', $converted);
    }

    public function test_it_does_not_double_wrap_tiptap_tables(): void
    {
        $html = SpkTemplateRenderer::tipTapPartyTable([
            ['Nama', ':', 'Debby'],
        ]);

        $converted = SpkTemplateRenderer::toEditableTables($html);

        $this->assertSame(1, substr_count($converted, 'tableWrapper'));
        $this->assertStringContainsString('<p>Nama</p>', $converted);
        $this->assertStringContainsString('<p>Debby</p>', $converted);
    }

    public function test_it_adds_party_table_class_when_missing(): void
    {
        $html = '<table style="min-width: 312px;"><tbody><tr><td><p>Nama</p></td></tr></tbody></table>';

        $this->assertStringContainsString(
            'class="spk-party-table"',
            SpkTemplateRenderer::ensurePartyTableClass($html),
        );
    }
}
