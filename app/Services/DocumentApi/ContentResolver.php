<?php

namespace App\Services\DocumentApi;

use App\Models\Proposal;
use App\Models\ProposalContentDefault;
use App\Models\Spk;
use App\Services\SpkTemplateRenderer;

class ContentResolver
{
    /**
     * @param  array<string, mixed>  $content
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function resolveProposalContent(array $content, array $payload = []): array
    {
        $globalTimelineTemplate = $payload['timeline_template'] ?? null;
        $resolved = [];

        foreach (ProposalContentCatalog::fieldKeys() as $field) {
            $spec = $content[$field] ?? [];
            $mode = (string) ($spec['mode'] ?? '');
            $template = $spec['template'] ?? null;

            if (ProposalContentCatalog::isTimeline($field) && blank($template) && filled($globalTimelineTemplate)) {
                $template = $globalTimelineTemplate;
            }

            $resolved[$field] = match ($mode) {
                'empty' => $this->emptyProposalValue($field),
                'override' => $this->overrideProposalValue($field, $spec['value'] ?? null),
                default => $this->defaultProposalValue($field, is_string($template) ? $template : null),
            };
        }

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, array<string, string>>
     */
    public function resolveSpkContent(array $content, Spk $spk, ?Proposal $proposal = null, int $offerIndex = 1): array
    {
        $needsDefaults = collect($content)
            ->contains(fn (mixed $spec): bool => is_array($spec) && ($spec['mode'] ?? null) === 'default');

        if ($needsDefaults) {
            SpkTemplateRenderer::renderDefaultsForRecord($spk, $proposal, $offerIndex);
        }

        $resolved = [];

        foreach (SpkContentCatalog::FIELD_KEYS as $field) {
            $spec = $content[$field] ?? [];
            $mode = (string) ($spec['mode'] ?? 'empty');

            $resolved[$field] = match ($mode) {
                'empty' => $this->emptyTranslations(),
                'override' => $this->overrideRichText($spec['value'] ?? null),
                default => $field === 'title'
                    ? $this->emptyTranslations()
                    : ($spk->getTranslations($field) ?: $this->emptyTranslations()),
            };
        }

        return $resolved;
    }

    /**
     * @return array<string, string>|array<int, mixed>
     */
    public function emptyProposalValue(string $field): array
    {
        if (ProposalContentCatalog::isSharedRepeater($field)) {
            return [];
        }

        if (ProposalContentCatalog::isTranslatableRepeater($field)) {
            return $this->emptyLocaleLists();
        }

        return $this->emptyTranslations();
    }

    /**
     * @return array<string, string>
     */
    public function emptyTranslations(): array
    {
        $empty = [];

        foreach ($this->locales() as $locale) {
            $empty[$locale] = '';
        }

        return $empty;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function emptyLocaleLists(): array
    {
        $empty = [];

        foreach ($this->locales() as $locale) {
            $empty[$locale] = [];
        }

        return $empty;
    }

    /**
     * @return array<string, string>
     */
    public function overrideRichText(mixed $value): array
    {
        $translations = [];

        foreach ($this->locales() as $locale) {
            $translations[$locale] = MarkdownOrHtml::toHtml($this->localeString($value, $locale));
        }

        return $translations;
    }

    /**
     * @return array<int, string>
     */
    public function locales(): array
    {
        return config('translatable.locales', ['en', 'id']);
    }

    /**
     * @return array<string, mixed>
     */
    private function overrideProposalValue(string $field, mixed $value): array
    {
        if (ProposalContentCatalog::isSharedRepeater($field)) {
            return $this->normalizeSharedRepeater($value);
        }

        if (ProposalContentCatalog::isTranslatableRepeater($field)) {
            return $this->overrideRepeater($value);
        }

        return $this->overrideRichText($value);
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultProposalValue(string $field, ?string $template): array
    {
        $lookupKey = filled($template) ? $template : $field;

        if (ProposalContentCatalog::isSharedRepeater($field)) {
            $shared = $this->lookupDefault($lookupKey, $this->locales()[0] ?? 'en');

            return is_array($shared) ? $shared : [];
        }

        if (ProposalContentCatalog::isTranslatableRepeater($field) || ProposalContentCatalog::isTimeline($field)) {
            $rows = [];

            foreach ($this->locales() as $locale) {
                $value = $this->lookupDefault($lookupKey, $locale);
                $rows[$locale] = is_array($value) ? $value : [];
            }

            return $rows;
        }

        $translations = [];

        foreach ($this->locales() as $locale) {
            $value = $this->lookupDefault($lookupKey, $locale);
            $translations[$locale] = is_string($value) ? $value : '';
        }

        return $translations;
    }

    private function lookupDefault(string $fieldKey, string $locale): mixed
    {
        $globalDefault = ProposalContentDefault::query()
            ->where('field_key', ProposalContentDefault::GLOBAL_FIELD_KEY)
            ->first();

        if ($globalDefault instanceof ProposalContentDefault) {
            $translations = $globalDefault->getTranslations('value');

            if (in_array($fieldKey, ProposalContentDefault::SHARED_JSON_REPEATER_FIELDS, true)) {
                return ProposalContentDefault::resolveSharedJsonRepeaterValue($translations, $fieldKey);
            }

            $value = data_get($translations, "{$locale}.{$fieldKey}");

            if ($value !== null) {
                return $value;
            }

            $legacyValue = data_get($globalDefault->getAttribute("value_{$locale}"), $fieldKey);

            if ($legacyValue !== null) {
                return $legacyValue;
            }
        }

        $legacyDefault = ProposalContentDefault::query()
            ->where('field_key', $fieldKey)
            ->first();

        if ($legacyDefault instanceof ProposalContentDefault) {
            $translations = $legacyDefault->getTranslations('value');
            $value = data_get($translations, $locale);

            if ($value !== null) {
                return $value;
            }

            return $legacyDefault->getAttribute("value_{$locale}");
        }

        return null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function overrideRepeater(mixed $value): array
    {
        if (is_array($value) && ! $this->isLocaleMap($value)) {
            $copied = [];

            foreach ($this->locales() as $locale) {
                $copied[$locale] = array_values($value);
            }

            return $copied;
        }

        $rows = [];

        foreach ($this->locales() as $locale) {
            $localeValue = is_array($value) ? ($value[$locale] ?? []) : [];
            $rows[$locale] = is_array($localeValue) ? array_values($localeValue) : [];
        }

        return $rows;
    }

    /**
     * @return array<int, mixed>
     */
    private function normalizeSharedRepeater(mixed $value): array
    {
        if (is_array($value) && $this->isLocaleMap($value)) {
            foreach ($this->locales() as $locale) {
                if (is_array($value[$locale] ?? null)) {
                    return array_values($value[$locale]);
                }
            }
        }

        return is_array($value) ? array_values($value) : [];
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function isLocaleMap(array $value): bool
    {
        foreach ($this->locales() as $locale) {
            if (array_key_exists($locale, $value)) {
                return true;
            }
        }

        return false;
    }

    private function localeString(mixed $value, string $locale): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (! is_array($value)) {
            return '';
        }

        $localeValue = $value[$locale] ?? null;

        return is_string($localeValue) ? $localeValue : '';
    }
}
