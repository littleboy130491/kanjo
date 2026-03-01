<?php

namespace App\Filament\Admin\Concerns;

use App\Filament\Admin\Support\TranslatableRepeaterSync;

/**
 * Apply to Create pages that have translatable repeater fields.
 *
 * Provides mutateFormDataBeforeCreate() which fills any locale that still has
 * 0 rows from the default locale before the record is saved.
 *
 * Implementing classes must define getTranslatableRepeaterFieldKeys().
 */
trait HasTranslatableRepeaterSync
{
    /**
     * Before creating the record, fill any locale that still has 0 rows from the default locale.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return TranslatableRepeaterSync::fillMissingLocales(
            $data,
            $this->getTranslatableRepeaterFieldKeys(),
            config('translatable.locales')[0] ?? 'en',
            config('translatable.locales', ['en', 'id']),
        );
    }

    /**
     * Return the list of translatable repeater field keys that participate in locale sync.
     *
     * @return array<string>
     */
    abstract protected function getTranslatableRepeaterFieldKeys(): array;
}
