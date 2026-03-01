<?php

namespace App\Filament\Admin\Support;

use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Utilities\Set;

class TranslatableRepeaterSync
{
    /**
     * Configure a repeater inside a Translate component with row sync behaviors.
     *
     * @param  array|callable  $emptyRowTemplate  Array or callable returning an array for empty rows mirrored to other locales.
     */
    public static function configure(
        Repeater $repeater,
        string $locale,
        bool $syncAdd = true,
        bool $syncDelete = true,
        array|callable $emptyRowTemplate = [],
    ): Repeater {
        if (! $syncAdd && ! $syncDelete) {
            return $repeater;
        }

        $deletedRowIndex = null;

        if ($syncAdd) {
            $repeater = $repeater->addAction(
                fn (Action $action): Action => $action->after(
                    function (Repeater $component) use ($locale, $emptyRowTemplate): void {
                        static::syncAddedRow($component, $locale, $emptyRowTemplate);
                    }
                )
            );
        }

        if ($syncDelete) {
            $repeater = $repeater->deleteAction(
                fn (Action $action): Action => $action
                    ->before(function (array $arguments, Repeater $component) use (&$deletedRowIndex): void {
                        $rawRows = $component->getRawState() ?? [];
                        $rawKeys = array_keys($rawRows);
                        $deletedKey = $arguments['item'] ?? null;
                        $index = array_search($deletedKey, $rawKeys, true);

                        $deletedRowIndex = $index === false ? null : (int) $index;
                    })
                    ->after(function (Repeater $component) use (&$deletedRowIndex, $locale): void {
                        static::syncDeletedRow($component, $locale, $deletedRowIndex);
                        $deletedRowIndex = null;
                    })
            );
        }

        return $repeater;
    }

    /**
     * Wrap a field to always sync its value across all locales in real-time (on blur).
     *
     * Safe to call on fields that already have afterStateUpdated callbacks — stacks on top.
     */
    public static function permanentlySynced(Field $field): Field
    {
        return $field
            ->live(onBlur: true)
            ->afterStateUpdated(function (Field $component, Set $set, mixed $state): void {
                static::syncFieldAcrossLocales($component, $set, $state);
            });
    }

    /**
     * Create a "Copy to all languages" action for use in Translate::make()->actions([...]).
     *
     * The action is visible only on the default locale tab (confirmed via $arguments['locale']).
     * It shows a confirmation dialog before overwriting other locales.
     *
     * @param  string  $defaultLocale  Locale whose data is copied to all others (e.g. 'en')
     */
    public static function makeCopyToAllLocalesAction(
        string $fieldKey,
        string $defaultLocale = 'en',
    ): Action {
        return Action::make('translatable_copy_to_all_' . $fieldKey)
            ->label('Copy to all languages')
            ->icon('heroicon-o-language')
            ->color('gray')
            ->visible(fn (array $arguments): bool => ($arguments['locale'] ?? null) === $defaultLocale)
            ->requiresConfirmation()
            ->modalHeading('Copy to all languages')
            ->modalDescription('This will overwrite all other language variants with the content from the current language. This cannot be undone.')
            ->modalSubmitActionLabel('Copy')
            ->action(function (array $arguments, mixed $livewire) use ($fieldKey, $defaultLocale): void {
                $locale = $arguments['locale'] ?? $defaultLocale;
                $allLocales = config('translatable.locales', ['en', 'id']);

                $data = $livewire->data;
                $sourceRows = data_get($data, "{$fieldKey}.{$locale}", []);

                foreach ($allLocales as $targetLocale) {
                    if ($targetLocale === $locale) {
                        continue;
                    }

                    data_set($data, "{$fieldKey}.{$targetLocale}", $sourceRows);
                }

                $livewire->data = $data;
            });
    }

    /**
     * Copy rows from the default locale to any other locale that has 0 rows.
     * Intended for use in mutateFormDataBeforeCreate().
     *
     * @param  array<string>  $fieldKeys
     * @param  array<string>  $allLocales
     */
    public static function fillMissingLocales(
        array $data,
        array $fieldKeys,
        string $defaultLocale,
        array $allLocales,
    ): array {
        foreach ($fieldKeys as $fieldKey) {
            $defaultRows = data_get($data, "{$fieldKey}.{$defaultLocale}", []);

            foreach ($allLocales as $locale) {
                if ($locale === $defaultLocale) {
                    continue;
                }

                $localeRows = data_get($data, "{$fieldKey}.{$locale}", []);

                if (empty($localeRows) && ! empty($defaultRows)) {
                    data_set($data, "{$fieldKey}.{$locale}", $defaultRows);
                }
            }
        }

        return $data;
    }

    /**
     * Sync a field value to the same field path in all other locales.
     * Can be called directly from within an existing afterStateUpdated callback.
     */
    public static function syncFieldAcrossLocales(mixed $component, Set $set, mixed $state): void
    {
        $currentPath = $component->getStatePath();
        $locales = config('translatable.locales', ['en', 'id']);

        foreach ($locales as $locale) {
            if (! str_contains($currentPath, ".{$locale}.")) {
                continue;
            }

            foreach ($locales as $targetLocale) {
                if ($targetLocale === $locale) {
                    continue;
                }

                $targetPath = str_replace(".{$locale}.", ".{$targetLocale}.", $currentPath);
                $set($targetPath, $state, isAbsolute: true);
            }

            break; // Only one locale will match in the path
        }
    }

    protected static function syncAddedRow(Repeater $component, string $locale, array|callable $emptyRowTemplate): void
    {
        $emptyRow = is_callable($emptyRowTemplate) ? $emptyRowTemplate() : $emptyRowTemplate;

        foreach (static::getMirroredLocaleRepeaters($component, $locale) as $targetRepeater) {
            $currentRows = is_array($component->getRawState()) ? $component->getRawState() : [];
            $targetRows = is_array($targetRepeater->getRawState()) ? $targetRepeater->getRawState() : [];

            if (count($targetRows) >= count($currentRows)) {
                continue;
            }

            $missingRows = count($currentRows) - count($targetRows);
            $newKeys = [];

            for ($i = 0; $i < $missingRows; $i++) {
                $newKey = $targetRepeater->generateUuid();

                if ($newKey) {
                    $targetRows[$newKey] = $emptyRow;
                    $newKeys[] = $newKey;
                } else {
                    $targetRows[] = $emptyRow;
                    $newKeys[] = array_key_last($targetRows);
                }
            }

            $targetRepeater->rawState($targetRows);

            foreach ($newKeys as $newKey) {
                if ($newKey !== null) {
                    $targetRepeater->getChildSchema($newKey)->fill();
                }
            }
        }
    }

    protected static function syncDeletedRow(Repeater $component, string $locale, ?int $deletedRowIndex): void
    {
        foreach (static::getMirroredLocaleRepeaters($component, $locale) as $targetRepeater) {
            $currentRows = is_array($component->getRawState()) ? $component->getRawState() : [];
            $targetRows = is_array($targetRepeater->getRawState()) ? $targetRepeater->getRawState() : [];

            if (count($targetRows) <= count($currentRows)) {
                continue;
            }

            $targetKeys = array_keys($targetRows);
            $index = $deletedRowIndex ?? count($currentRows);

            if (! isset($targetKeys[$index])) {
                $index = array_key_last($targetKeys);
            }

            if ($index === null || ! isset($targetKeys[$index])) {
                continue;
            }

            unset($targetRows[$targetKeys[$index]]);
            $targetRepeater->rawState($targetRows);
        }
    }

    /**
     * @return array<string, Repeater>  Keyed by locale
     */
    protected static function getMirroredLocaleRepeaters(Repeater $component, string $currentLocale): array
    {
        $locales = config('translatable.locales', ['en', 'id']);
        $result = [];

        foreach ($locales as $targetLocale) {
            if ($targetLocale === $currentLocale) {
                continue;
            }

            $targetPath = static::getMirroredLocaleStatePathForLocale($component, $currentLocale, $targetLocale);

            if (! $targetPath) {
                continue;
            }

            $targetComponent = $component
                ->getRootContainer()
                ->getComponentByStatePath($targetPath, withHidden: true, withAbsoluteStatePath: true);

            if ($targetComponent instanceof Repeater) {
                $result[$targetLocale] = $targetComponent;
            }
        }

        return $result;
    }

    protected static function getMirroredLocaleStatePathForLocale(Repeater $component, string $currentLocale, string $targetLocale): ?string
    {
        $currentPath = $component->getStatePath();
        $localeSuffix = ".{$currentLocale}";

        if (! str_ends_with($currentPath, $localeSuffix)) {
            return null;
        }

        return substr($currentPath, 0, -strlen($localeSuffix)) . ".{$targetLocale}";
    }
}
