<?php

namespace App\Filament\Admin\Resources\ProposalContentDefaults\Schemas;

use App\Models\ProposalContentDefault;
use Awcodes\Curator\Components\Forms\RichEditor\AttachCuratorMediaPlugin;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
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
            ->map(fn(string $label, string $fieldKey): Section =>
                Section::make($label)
                    ->schema([
                        Translate::make()
                            ->exclude(
                                collect(config('translatable.locales'))
                                    ->map(fn(string $locale): string => "value.{$locale}.{$fieldKey}")
                                    ->all()
                            )
                            ->schema(fn(string $locale): array => [
                                in_array($fieldKey, self::$jsonRepeaterFields)
                                ? self::makeJsonTextarea("value.{$locale}.{$fieldKey}", 'Default Value')
                                : self::makeRichEditor("value.{$locale}.{$fieldKey}", 'Default Value'),
                            ])
                            ->suffixLocaleLabel(),
                    ])
                    ->columnSpanFull())
            ->all();

        return $schema
            ->components([
                Hidden::make('field_key')
                    ->default(ProposalContentDefault::GLOBAL_FIELD_KEY)
                    ->dehydrated(),
                ...$fieldSections,
            ]);
    }

    protected static function makeRichEditor(string $statePath, string $label): RichEditor
    {
        return RichEditor::make($statePath)
            ->label($label)
            ->enableToolbarButtons(['attachCuratorMedia'])
            ->plugins([AttachCuratorMediaPlugin::make()])
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
            ->formatStateUsing(fn($state): string => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
            ->dehydrateStateUsing(fn(?string $state): array => json_decode($state ?: '[]', true) ?: [])
            ->rule(function () {
                return function (string $attribute, $value, \Closure $fail): void {
                    $decoded = json_decode((string) $value, true);

                    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                        $fail('The value must be a valid JSON array.');
                    }
                };
            });
    }
}
