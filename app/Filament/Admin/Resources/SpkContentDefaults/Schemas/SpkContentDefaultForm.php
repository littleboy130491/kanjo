<?php

namespace App\Filament\Admin\Resources\SpkContentDefaults\Schemas;

use App\Filament\Support\RichEditorHtml;
use App\Models\SpkContentDefault;
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
                    ->description('These defaults are copied onto each new SPK and can then be edited per document. Placeholders like {{ offer_name }}, {{ offer_price }}, and {{ offer_timeline }} use the selected offer when creating from a proposal (default Offer 1). Explicit Offer 1/2 placeholders: {{ offer_name_1 }}, {{ offer_price_1 }}, {{ offer_timeline_1 }}, {{ offer_name_2 }}, {{ offer_price_2 }}, {{ offer_timeline_2 }}. Cover placeholders: {{ client_company }}, {{ company_name }}, {{ subject }}, {{ spk_date }}, {{ client_pic_name }}, {{ client_pic_role }}, {{ client_address }}, {{ company_pic_name }}, {{ company_pic_role }}, {{ company_address }}.')
                    ->schema([
                        Translate::make()
                            ->exclude(
                                collect(config('translatable.locales'))
                                    ->flatMap(fn (string $locale): array => [
                                        "value.{$locale}.title",
                                        "value.{$locale}.party_identification",
                                        "value.{$locale}.subject",
                                        "value.{$locale}.content",
                                    ])
                                    ->all()
                            )
                            ->schema(fn (string $locale): array => [
                                RichEditorHtml::configure(
                                    RichEditor::make("value.{$locale}.title")
                                        ->label('Title')
                                        ->helperText('Cover heading copied onto new SPKs. Placeholders are resolved once on create.'),
                                )
                                    ->enableToolbarButtons(['table'])
                                    ->columnSpanFull(),
                                RichEditorHtml::configure(
                                    RichEditor::make("value.{$locale}.party_identification")
                                        ->label('Party Identification')
                                        ->helperText('Opening party block copied onto new SPKs. Placeholders are resolved once on create.'),
                                )
                                    ->enableToolbarButtons(['table'])
                                    ->extraAttributes(['style' => 'min-height: 360px'])
                                    ->columnSpanFull(),
                                TextInput::make("value.{$locale}.subject")
                                    ->label('Subject')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                RichEditorHtml::configure(
                                    RichEditor::make("value.{$locale}.content")
                                        ->label('Content'),
                                )
                                    ->extraAttributes(['style' => 'min-height: 520px'])
                                    ->columnSpanFull(),
                            ])
                            ->suffixLocaleLabel(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
