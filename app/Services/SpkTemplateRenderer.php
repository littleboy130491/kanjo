<?php

namespace App\Services;

use App\Models\Proposal;
use App\Models\Spk;
use App\Models\SpkContentDefault;
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
    public static function placeholderValues(Spk $spk, ?Proposal $proposal = null): array
    {
        $proposal ??= $spk->proposal;

        return [
            'spk_number' => (string) $spk->document_number,
            'spk_date' => (string) ($spk->spk_date?->translatedFormat('d F Y') ?? ''),
            'client_company' => (string) $spk->client_company,
            'client_pic_name' => (string) $spk->client_pic_name,
            'client_pic_role' => (string) $spk->client_pic_role,
            'client_address' => (string) $spk->client_address,
            'company_name' => (string) $spk->company_name,
            'company_pic_name' => (string) $spk->company_pic_name,
            'company_pic_role' => (string) $spk->company_pic_role,
            'company_address' => (string) $spk->company_address,
            'proposal_number' => (string) ($proposal?->document_number ?? ''),
            'proposal_date' => (string) ($proposal?->issue_date?->translatedFormat('d F Y') ?? ''),
            'offer_name' => (string) ($proposal?->offer_name_1 ?? ''),
            'offer_price' => $proposal ? self::formatMoney($proposal->offer_1_price, $proposal->currency) : '',
            'subject' => (string) ($spk->getTranslation('subject', app()->getLocale(), false) ?: $spk->subject ?: ''),
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

    public static function renderDefaultsForRecord(Spk $spk, ?Proposal $proposal = null): void
    {
        $values = self::placeholderValues($spk, $proposal);

        foreach (['title', 'subject', 'content'] as $field) {
            $translations = $spk->getTranslations($field) ?: self::defaultTranslations($field);

            $spk->setTranslations($field, self::replacePlaceholders($translations, $values));
        }
    }

    private static function formatMoney(mixed $value, ?string $currency): string
    {
        $currency = $currency ?: 'IDR';

        if ($currency === 'IDR') {
            return 'Rp. '.number_format((float) ($value ?? 0), 0, ',', '.');
        }

        return Number::currency((float) ($value ?? 0), $currency);
    }
}
