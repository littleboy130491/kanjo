<?php

namespace App\Services;

use App\Models\Proposal;
use App\Models\Spk;
use App\Models\SpkContentDefault;
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
            'client_address' => (string) $spk->client_address,
            'company_name' => (string) $spk->company_name,
            'company_pic_name' => (string) $spk->company_pic_name,
            'company_pic_role' => (string) $spk->company_pic_role,
            'company_address' => (string) $spk->company_address,
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

        foreach (['subject', 'content'] as $field) {
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

                    $content = RichTextHtmlNormalizer::normalize($content);
                }

                $resolved[$locale] = $content;
            }

            $spk->setTranslations($field, $resolved);
        }
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
