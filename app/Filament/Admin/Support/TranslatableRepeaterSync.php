<?php

namespace App\Filament\Admin\Support;

use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;

class TranslatableRepeaterSync
{
    /**
     * Configure a repeater inside a Translate component with delete sync.
     */
    public static function configure(Repeater $repeater, string $locale): Repeater
    {
        $deletedRowIndex = null;

        return $repeater->deleteAction(
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

    /**
     * Create a "Copy to all languages" action for use in Translate::make()->actions([...]).
     *
     * Visible only on the default locale tab. Shows a confirmation dialog before overwriting.
     */
    public static function makeCopyToAllLocalesAction(
        string $fieldKey,
        ?string $defaultLocale = null,
    ): Action {
        $defaultLocale ??= config('app.locale', 'en');
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
     * @return array<string, Repeater>
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
