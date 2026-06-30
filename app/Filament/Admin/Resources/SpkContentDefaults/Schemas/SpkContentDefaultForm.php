<?php

namespace App\Filament\Admin\Resources\SpkContentDefaults\Schemas;

use App\Models\SpkContentDefault;
use Awcodes\Curator\Components\Forms\RichEditor\AttachCuratorMediaPlugin;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use SolutionForest\FilamentTranslateField\Forms\Component\Translate;

class SpkContentDefaultForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('field_key')
                    ->default(SpkContentDefault::GLOBAL_FIELD_KEY)
                    ->dehydrated(),
                Section::make('SPK Default Content')
                    ->description('Placeholders like {{ client_company }} are resolved when an SPK is created.')
                    ->schema([
                        Translate::make()
                            ->exclude(
                                collect(config('translatable.locales'))
                                    ->flatMap(fn (string $locale): array => [
                                        "value.{$locale}.title",
                                        "value.{$locale}.subject",
                                        "value.{$locale}.content",
                                    ])
                                    ->all()
                            )
                            ->schema(fn (string $locale): array => [
                                RichEditor::make("value.{$locale}.title")
                                    ->label('Title')
                                    ->extraAttributes(['style' => 'min-height: 120px'])
                                    ->columnSpanFull(),
                                TextInput::make("value.{$locale}.subject")
                                    ->label('Subject')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                RichEditor::make("value.{$locale}.content")
                                    ->label('Content')
                                    ->enableToolbarButtons(['attachCuratorMedia'])
                                    ->plugins([AttachCuratorMediaPlugin::make()])
                                    ->extraAttributes(['style' => 'min-height: 520px'])
                                    ->columnSpanFull(),
                            ])
                            ->suffixLocaleLabel(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
