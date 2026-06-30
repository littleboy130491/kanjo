<?php

namespace App\Filament\Admin\Resources\ProposalContentDefaults\Schemas;

use App\Filament\Support\RichEditorHtml;
use App\Models\ProposalContentDefault;
use Awcodes\Curator\Components\Forms\RichEditor\AttachCuratorMediaPlugin;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use SolutionForest\FilamentTranslateField\Forms\Component\Translate;

class ProposalContentDefaultForm
{
    /**
     * Fields that use JSON array repeaters (not rich text).
     */
    protected static array $jsonRepeaterFields = [
        'add_on',
        'short_project_timeline',
        'business_project_timeline',
        'prime_project_timeline',
        'corporate_project_timeline',
        'custom_project_timeline',
    ];

    public static function configure(Schema $schema): Schema
    {
        $fieldSections = collect(ProposalContentDefault::FIELD_OPTIONS)
            ->map(fn (string $label, string $fieldKey): Section => self::makeFieldSection($label, $fieldKey))
            ->all();

        return $schema
            ->components([
                Hidden::make('field_key')
                    ->default(ProposalContentDefault::GLOBAL_FIELD_KEY)
                    ->dehydrated(),
                ...$fieldSections,
            ]);
    }

    protected static function makeFieldSection(string $label, string $fieldKey): Section
    {
        if (in_array($fieldKey, ProposalContentDefault::SHARED_JSON_REPEATER_FIELDS, true)) {
            return Section::make($label)
                ->schema([
                    self::makeSharedUrlRepeater($fieldKey),
                ])
                ->columnSpanFull();
        }

        return Section::make($label)
            ->schema([
                Translate::make()
                    ->exclude(
                        collect(config('translatable.locales'))
                            ->map(fn (string $locale): string => "value.{$locale}.{$fieldKey}")
                            ->all()
                    )
                    ->schema(fn (string $locale): array => [
                        in_array($fieldKey, self::$jsonRepeaterFields, true)
                            ? self::makeJsonTextarea("value.{$locale}.{$fieldKey}", 'Default Value')
                            : self::makeRichEditor("value.{$locale}.{$fieldKey}", ''),
                    ])
                    ->suffixLocaleLabel(),
            ])
            ->columnSpanFull();
    }

    protected static function makeSharedUrlRepeater(string $fieldKey): Repeater
    {
        $primaryLocale = config('app.locale', 'en');
        $urlLabel = match ($fieldKey) {
            'client_logos' => 'Logo Image URL',
            'video_testimonials' => 'Video URL',
            default => 'URL',
        };

        return Repeater::make("value.{$primaryLocale}.{$fieldKey}")
            ->schema([
                TextInput::make('url')
                    ->label($urlLabel)
                    ->url()
                    ->required()
                    ->maxLength(2048)
                    ->columnSpanFull(),
            ])
            ->addable()
            ->reorderable()
            ->deletable()
            ->default([])
            ->helperText('Shared across all languages.')
            ->afterStateUpdated(function (?array $state, Set $set) use ($fieldKey): void {
                $rows = is_array($state) ? $state : [];

                foreach (config('translatable.locales', ['en', 'id']) as $locale) {
                    $set("value.{$locale}.{$fieldKey}", $rows);
                }
            })
            ->columnSpanFull();
    }

    protected static function makeRichEditor(string $statePath, string $label): RichEditor
    {
        return RichEditor::make($statePath)
            ->label($label)
            ->enableToolbarButtons(['attachCuratorMedia'])
            ->plugins([AttachCuratorMediaPlugin::make()])
            ->afterStateHydrated(function (RichEditor $component): void {
                self::normalizeRichEditorRawState($component);
            })
            ->extraAttributes(['style' => 'min-height: 200px'])
            ->columnSpanFull();
    }

    protected static function makeJsonTextarea(string $statePath, string $label): Textarea
    {
        return Textarea::make($statePath)
            ->label($label)
            ->rows(12)
            ->required()
            ->helperText('Must be a valid JSON array.')
            ->formatStateUsing(fn ($state): string => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
            ->dehydrateStateUsing(fn (?string $state): array => json_decode($state ?: '[]', true) ?: [])
            ->rule(function () {
                return function (string $attribute, $value, \Closure $fail): void {
                    $decoded = json_decode((string) $value, true);

                    if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                        $fail('The value must be a valid JSON array.');
                    }
                };
            });
    }

    protected static function normalizeRichEditorRawState(RichEditor $component): void
    {
        RichEditorHtml::normalizeRawState($component);
    }
}
