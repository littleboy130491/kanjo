<?php

namespace App\Services;

use App\Models\Proposal;
use App\Models\Spk;
use App\Models\SpkContentDefault;
use App\Services\DocumentApi\SpkContentCatalog;
use App\Support\RichTextHtmlNormalizer;
use Illuminate\Support\Number;

class SpkTemplateRenderer
{
    /**
     * @return array<string, string>
     */
    public static function defaultTranslations(string $field): array
    {
        $record = SpkContentDefault::query()
            ->where('field_key', SpkContentDefault::GLOBAL_FIELD_KEY)
            ->first();

        if (! $record) {
            return [];
        }

        $translations = $record->getTranslations('value');
        $payload = [];

        foreach (config('translatable.locales', ['en', 'id']) as $locale) {
            $payload[$locale] = (string) data_get($translations, "{$locale}.{$field}", '');
        }

        return $payload;
    }

    public static function defaultForLocale(string $field, string $locale): string
    {
        return self::defaultTranslations($field)[$locale] ?? '';
    }

    /**
     * @return array<string, string>
     */
    public static function placeholderValues(
        Spk $spk,
        ?Proposal $proposal = null,
        int $primaryOfferIndex = 1,
        string $locale = 'id',
    ): array {
        $proposal ??= $spk->proposal;
        $primaryOfferIndex = self::normalizeOfferIndex($primaryOfferIndex);

        return [
            'spk_number' => (string) $spk->document_number,
            'spk_date' => self::formatDocumentDate($spk->spk_date, $locale),
            'client_company' => (string) $spk->client_company,
            'client_pic_name' => (string) $spk->client_pic_name,
            'client_pic_role' => (string) $spk->client_pic_role,
            'client_address' => self::multilineForHtml((string) $spk->client_address),
            'company_name' => (string) $spk->company_name,
            'company_pic_name' => (string) $spk->company_pic_name,
            'company_pic_role' => (string) $spk->company_pic_role,
            'company_address' => self::multilineForHtml((string) $spk->company_address),
            'proposal_number' => (string) ($proposal?->document_number ?? ''),
            'proposal_date' => self::formatDocumentDate($proposal?->issue_date, $locale),
            'offer_name' => self::offerName($proposal, $primaryOfferIndex),
            'offer_price' => self::offerPrice($proposal, $primaryOfferIndex),
            'offer_name_1' => self::offerName($proposal, 1),
            'offer_price_1' => self::offerPrice($proposal, 1),
            'offer_name_2' => self::offerName($proposal, 2),
            'offer_price_2' => self::offerPrice($proposal, 2),
            'subject' => trim((string) (
                $spk->getTranslation('subject', $locale, false)
                ?: self::defaultForLocale('subject', $locale)
                ?: ''
            )),
        ];
    }

    /**
     * @param  array<string, string>  $translations
     * @param  array<string, string>  $values
     * @return array<string, string>
     */
    public static function replacePlaceholders(array $translations, array $values): array
    {
        foreach ($translations as $locale => $content) {
            foreach ($values as $key => $value) {
                $content = str_replace('{{ '.$key.' }}', $value, (string) $content);
                $content = str_replace('{{'.$key.'}}', $value, (string) $content);
            }

            $translations[$locale] = $content;
        }

        return $translations;
    }

    private static function replaceBlockPlaceholder(string $content, string $key, string $replacement): string
    {
        $quotedKey = preg_quote($key, '/');
        $wrappedPattern = '/<p>\s*\{\{\s*'.$quotedKey.'\s*\}\}\s*<\/p>/';
        $updated = preg_replace($wrappedPattern, $replacement, $content);

        if (is_string($updated) && $updated !== $content) {
            return $updated;
        }

        $content = str_replace('{{ '.$key.' }}', $replacement, $content);

        return str_replace('{{'.$key.'}}', $replacement, $content);
    }

    public static function renderDefaultsForRecord(Spk $spk, ?Proposal $proposal = null, int $primaryOfferIndex = 1): void
    {
        $proposal ??= $spk->relationLoaded('proposal') ? $spk->proposal : $spk->proposal()->first();
        $primaryOfferIndex = self::normalizeOfferIndex($primaryOfferIndex);

        foreach (SpkContentCatalog::FIELD_KEYS as $field) {
            $translations = $spk->getTranslations($field) ?: self::defaultTranslations($field);
            $resolved = [];

            foreach (config('translatable.locales', ['id', 'en']) as $locale) {
                $content = (string) ($translations[$locale] ?? self::defaultForLocale($field, $locale));
                $localeValues = array_merge(
                    self::placeholderValues($spk, $proposal, $primaryOfferIndex, $locale),
                    [
                        'offer_timeline' => self::formatTimelineTable($proposal, $locale, $primaryOfferIndex),
                        'offer_timeline_1' => self::formatTimelineTable($proposal, $locale, 1),
                        'offer_timeline_2' => self::formatTimelineTable($proposal, $locale, 2),
                    ],
                );
                $timelineKeys = ['offer_timeline', 'offer_timeline_1', 'offer_timeline_2'];
                $scalarValues = array_diff_key($localeValues, array_flip($timelineKeys));

                $content = self::replacePlaceholders([$locale => $content], $scalarValues)[$locale];

                if ($field === 'content') {
                    foreach ($timelineKeys as $timelineKey) {
                        $content = self::replaceBlockPlaceholder(
                            $content,
                            $timelineKey,
                            (string) ($localeValues[$timelineKey] ?? ''),
                        );
                    }
                }

                if ($field !== 'subject') {
                    $content = RichTextHtmlNormalizer::normalize($content);
                }

                $content = self::withEditableTables($field, $content);

                $resolved[$locale] = $content;
            }

            $spk->setTranslations($field, $resolved);
        }
    }

    public static function displayHtml(string $field, Spk $spk, string $locale): string
    {
        $stored = (string) $spk->getTranslation($field, $locale, false);

        if (! RichTextHtmlNormalizer::isBlankHtml($stored)) {
            return self::withDisplayTableClass($field, $stored);
        }

        return self::withDisplayTableClass($field, self::generatedHtml($field, $spk, $locale));
    }

    public static function generatedHtml(string $field, Spk $spk, string $locale): string
    {
        $template = self::defaultForLocale($field, $locale);

        if (! RichTextHtmlNormalizer::isBlankHtml($template)) {
            $html = self::replacePlaceholders(
                [$locale => $template],
                self::placeholderValues($spk, $spk->proposal, 1, $locale),
            )[$locale];

            $html = $field === 'subject' ? $html : RichTextHtmlNormalizer::normalize($html);

            return self::withEditableTables($field, $html);
        }

        return self::withEditableTables($field, self::fallbackGeneratedHtml($field, $spk, $locale));
    }

    public static function tipTapSignatureHtml(
        string $approvalLabel,
        string $firstPartyLabel,
        string $firstName,
        string $firstCompany,
        string $secondPartyLabel,
        string $secondName,
        string $secondCompany,
    ): string {
        $spacer = '<p class="spk-signature-space"><br></p>';
        $approvalLabel = e($approvalLabel);
        $firstPartyLabel = e($firstPartyLabel);
        $firstName = e($firstName);
        $firstCompany = e($firstCompany);
        $secondPartyLabel = e($secondPartyLabel);
        $secondName = e($secondName);
        $secondCompany = e($secondCompany);

        return '<p>'.$approvalLabel.'</p>'
            .'<div class="tableWrapper"><table class="spk-signature-table" style="min-width: 312px;"><colgroup><col style="min-width: 25px;"><col style="min-width: 25px;"></colgroup><tbody><tr>'
            .'<td colspan="1" rowspan="1"><p><strong>'.$firstPartyLabel.'</strong></p>'.$spacer.'<p><strong>'.$firstName.'</strong></p><p>'.$firstCompany.'</p></td>'
            .'<td colspan="1" rowspan="1"><p><strong>'.$secondPartyLabel.'</strong></p>'.$spacer.'<p><strong>'.$secondName.'</strong></p><p>'.$secondCompany.'</p></td>'
            .'</tr></tbody></table></div>';
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     */
    public static function tipTapPartyTable(array $rows): string
    {
        $bodyRows = '';

        foreach ($rows as $cells) {
            $tds = '';

            foreach (array_values($cells) as $index => $html) {
                $colwidth = match ($index) {
                    0 => ' colwidth="144"',
                    1 => ' colwidth="24"',
                    default => '',
                };
                $tds .= "<td colspan=\"1\" rowspan=\"1\"{$colwidth}><p>{$html}</p></td>";
            }

            $bodyRows .= "<tr>{$tds}</tr>";
        }

        return '<div class="tableWrapper"><table class="spk-party-table" style="min-width: 312px;"><colgroup><col style="min-width: 25px; width: 144px;"><col style="min-width: 25px; width: 24px;"><col style="min-width: 25px;"></colgroup><tbody>'.$bodyRows.'</tbody></table></div>';
    }

    public static function toEditableTables(string $html, string $tableClass = 'spk-party-table'): string
    {
        if (! str_contains(strtolower($html), '<table')) {
            return $html;
        }

        $internalErrors = libxml_use_internal_errors(true);
        $document = new \DOMDocument('1.0', 'UTF-8');
        $document->loadHTML(
            '<?xml encoding="utf-8"?><!DOCTYPE html><html><body>'.$html.'</body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );

        $tables = [];

        foreach ($document->getElementsByTagName('table') as $table) {
            if ($table instanceof \DOMElement) {
                $tables[] = $table;
            }
        }

        foreach ($tables as $table) {
            self::prepareEditableTable($document, $table, $tableClass);
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

    public static function ensurePartyTableClass(string $html): string
    {
        return self::ensureTableClass($html, 'spk-party-table');
    }

    public static function ensureSignatureTableClass(string $html): string
    {
        return self::ensureTableClass($html, 'spk-signature-table');
    }

    public static function ensureTableClass(string $html, string $class): string
    {
        $updated = preg_replace_callback(
            '/<table\b([^>]*)>/i',
            function (array $matches) use ($class): string {
                $attributes = $matches[1];

                if (preg_match('/\bclass\s*=\s*(["\'])(.*?)\1/i', $attributes, $classMatch) === 1) {
                    if (str_contains($classMatch[2], $class)) {
                        return $matches[0];
                    }

                    $attributes = preg_replace(
                        '/\bclass\s*=\s*(["\'])(.*?)\1/i',
                        'class=$1'.$class.' $2$1',
                        $attributes,
                        1,
                    );

                    return '<table'.$attributes.'>';
                }

                return '<table class="'.$class.'"'.$attributes.'>';
            },
            $html,
        );

        return is_string($updated) ? $updated : $html;
    }

    public static function formatTimelineTable(?Proposal $proposal, string $locale, int $offerIndex = 1): string
    {
        $rows = self::timelineRowsForLocale($proposal, $locale, $offerIndex);

        if ($rows === []) {
            return '';
        }

        $labels = self::timelineLabels($locale);
        $totalDays = self::timelineTotalDays($rows);
        $bodyRows = collect($rows)
            ->map(fn (array $row): string => sprintf(
                '<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
                e((string) ($row['activity_name'] ?? '-')),
                e((string) ($row['activity_pic'] ?? '-')),
                e(self::formatTimelineDays($row['activity_days'] ?? null, $locale)),
            ))
            ->implode('');

        return sprintf(
            '<table class="spk-timeline-table"><colgroup><col style="width:65%%"><col style="width:20%%"><col style="width:15%%"></colgroup><thead><tr><th>%s</th><th>%s</th><th>%s</th></tr></thead><tbody>%s<tr><td><strong>%s</strong></td><td></td><td><strong>%s</strong></td></tr></tbody></table>',
            e($labels['activity']),
            e($labels['pic']),
            e($labels['days']),
            $bodyRows,
            e($labels['total']),
            e(self::formatTimelineDays($totalDays, $locale)),
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function timelineRowsForLocale(?Proposal $proposal, string $locale, int $offerIndex = 1): array
    {
        if (! $proposal instanceof Proposal) {
            return [];
        }

        $timelineField = self::timelineFieldForOffer($offerIndex);
        $timeline = $proposal->getTranslation($timelineField, $locale, false);

        if (! is_array($timeline)) {
            return [];
        }

        return collect($timeline)
            ->filter(function (mixed $row): bool {
                if (! is_array($row)) {
                    return filled($row);
                }

                foreach ($row as $item) {
                    if (filled(is_string($item) ? trim($item) : $item)) {
                        return true;
                    }
                }

                return false;
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public static function timelineTotalDays(array $rows): int
    {
        return (int) collect($rows)->sum(function (array $row): int {
            $days = $row['activity_days'] ?? 0;

            return is_numeric($days) ? (int) $days : 0;
        });
    }

    public static function formatDocumentDate(mixed $date, string $locale): string
    {
        if (! $date instanceof \Carbon\CarbonInterface) {
            return '';
        }

        $carbonLocale = $locale === 'id' ? 'id' : 'en';

        return (string) $date->copy()->locale($carbonLocale)->translatedFormat('d F Y');
    }

    private static function withEditableTables(string $field, string $content): string
    {
        $tableClass = self::tableClassForField($field);

        return $tableClass === null ? $content : self::toEditableTables($content, $tableClass);
    }

    private static function withDisplayTableClass(string $field, string $content): string
    {
        $tableClass = self::tableClassForField($field);

        return $tableClass === null ? $content : self::ensureTableClass($content, $tableClass);
    }

    private static function tableClassForField(string $field): ?string
    {
        return match ($field) {
            'party_identification' => 'spk-party-table',
            'signature' => 'spk-signature-table',
            default => null,
        };
    }

    private static function prepareEditableTable(
        \DOMDocument $document,
        \DOMElement $table,
        string $tableClass = 'spk-party-table',
    ): void
    {
        $class = $table->getAttribute('class');

        if (! str_contains($class, $tableClass)) {
            $table->setAttribute('class', trim($class.' '.$tableClass));
        }

        if (! $table->hasAttribute('style')) {
            $table->setAttribute('style', 'min-width: 312px;');
        }

        $parent = $table->parentNode;

        if (
            ! ($parent instanceof \DOMElement)
            || strtolower($parent->nodeName) !== 'div'
            || ! str_contains($parent->getAttribute('class'), 'tableWrapper')
        ) {
            $wrapper = $document->createElement('div');
            $wrapper->setAttribute('class', 'tableWrapper');
            $parent?->insertBefore($wrapper, $table);
            $wrapper->appendChild($table);
        }

        $hasColgroup = false;

        foreach ($table->childNodes as $child) {
            if ($child instanceof \DOMElement && strtolower($child->nodeName) === 'colgroup') {
                $hasColgroup = true;
                break;
            }
        }

        if (! $hasColgroup) {
            $firstRow = $table->getElementsByTagName('tr')->item(0);
            $columnCount = 0;

            if ($firstRow instanceof \DOMElement) {
                foreach ($firstRow->childNodes as $cell) {
                    if ($cell instanceof \DOMElement && in_array(strtolower($cell->nodeName), ['td', 'th'], true)) {
                        $columnCount++;
                    }
                }
            }

            $columnCount = max($columnCount, 1);
            $colgroup = $document->createElement('colgroup');

            for ($index = 0; $index < $columnCount; $index++) {
                $col = $document->createElement('col');
                $style = $tableClass === 'spk-party-table'
                    ? match ($index) {
                        0 => 'min-width: 25px; width: 144px;',
                        1 => 'min-width: 25px; width: 24px;',
                        default => 'min-width: 25px;',
                    }
                    : 'min-width: 25px; width: 50%;';
                $col->setAttribute('style', $style);
                $colgroup->appendChild($col);
            }

            $table->insertBefore($colgroup, $table->firstChild);
        }

        $cells = [];

        foreach (['td', 'th'] as $tag) {
            foreach ($table->getElementsByTagName($tag) as $cell) {
                if ($cell instanceof \DOMElement) {
                    $cells[] = $cell;
                }
            }
        }

        foreach ($cells as $cell) {
            if (! $cell->hasAttribute('colspan')) {
                $cell->setAttribute('colspan', '1');
            }

            if (! $cell->hasAttribute('rowspan')) {
                $cell->setAttribute('rowspan', '1');
            }

            $hasBlockChild = false;

            foreach ($cell->childNodes as $child) {
                if (
                    $child instanceof \DOMElement
                    && in_array(strtolower($child->nodeName), ['p', 'ul', 'ol', 'div', 'table', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'pre'], true)
                ) {
                    $hasBlockChild = true;
                    break;
                }
            }

            if ($hasBlockChild) {
                continue;
            }

            $paragraph = $document->createElement('p');

            while ($cell->firstChild) {
                $paragraph->appendChild($cell->firstChild);
            }

            $cell->appendChild($paragraph);
        }
    }

    private static function fallbackGeneratedHtml(string $field, Spk $spk, string $locale): string
    {
        $subjectText = trim((string) (
            $spk->getTranslation('subject', $locale, false)
            ?: self::defaultForLocale('subject', $locale)
        ));
        $spkDateText = self::formatDocumentDate($spk->spk_date, $locale);

        $previousLocale = app()->getLocale();
        app()->setLocale($locale);

        try {
            return match ($field) {
                'title' => view('spks.partials.document-title', [
                    'spk' => $spk,
                    'subjectText' => $subjectText,
                ])->render(),
                'party_identification' => view('spks.partials.party-identification', [
                    'spk' => $spk,
                    'subjectText' => $subjectText,
                    'spkDateText' => $spkDateText,
                ])->render(),
                'signature' => self::tipTapSignatureHtml(
                    approvalLabel: $locale === 'id' ? 'Menyetujui,' : 'Approved by,',
                    firstPartyLabel: $locale === 'id' ? 'PIHAK PERTAMA' : 'FIRST PARTY',
                    firstName: (string) $spk->client_pic_name,
                    firstCompany: (string) $spk->client_company,
                    secondPartyLabel: $locale === 'id' ? 'PIHAK KEDUA' : 'SECOND PARTY',
                    secondName: (string) $spk->company_pic_name,
                    secondCompany: (string) $spk->company_name,
                ),
                default => '',
            };
        } finally {
            app()->setLocale($previousLocale);
        }
    }

    private static function multilineForHtml(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (str_contains(strtolower($value), '<br')) {
            return $value;
        }

        return nl2br($value, false);
    }

    private static function formatMoney(mixed $value, ?string $currency): string
    {
        $currency = $currency ?: 'IDR';

        if ($currency === 'IDR') {
            return 'Rp. '.number_format((float) ($value ?? 0), 0, ',', '.');
        }

        return Number::currency((float) ($value ?? 0), $currency);
    }

    private static function normalizeOfferIndex(int $offerIndex): int
    {
        return $offerIndex === 2 ? 2 : 1;
    }

    private static function offerName(?Proposal $proposal, int $offerIndex): string
    {
        if (! $proposal instanceof Proposal) {
            return '';
        }

        return (string) ($offerIndex === 2 ? $proposal->offer_name_2 : $proposal->offer_name_1);
    }

    private static function offerPrice(?Proposal $proposal, int $offerIndex): string
    {
        if (! $proposal instanceof Proposal) {
            return '';
        }

        $attribute = $offerIndex === 2 ? 'offer_2_price' : 'offer_1_price';
        $price = $proposal->getAttributes()[$attribute] ?? null;

        return self::formatMoney($price, $proposal->currency);
    }

    private static function timelineFieldForOffer(int $offerIndex): string
    {
        return $offerIndex === 2 ? 'offer_2_project_timeline' : 'offer_1_project_timeline';
    }

    /**
     * @return array{activity: string, pic: string, days: string, total: string}
     */
    private static function timelineLabels(string $locale): array
    {
        return match ($locale) {
            'id' => [
                'activity' => 'Kegiatan',
                'pic' => 'PIC',
                'days' => 'Jumlah Hari',
                'total' => 'Total Hari Kerja',
            ],
            default => [
                'activity' => 'Activity',
                'pic' => 'PIC',
                'days' => 'Day(s)',
                'total' => 'Total Days',
            ],
        };
    }

    private static function formatTimelineDays(mixed $days, string $locale): string
    {
        if (! filled($days) && $days !== 0 && $days !== '0') {
            return '-';
        }

        $days = is_numeric($days) ? (string) (int) $days : trim((string) $days);

        return match ($locale) {
            'id' => "{$days} hari",
            default => "{$days} day(s)",
        };
    }
}
