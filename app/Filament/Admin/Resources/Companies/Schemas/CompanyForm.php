<?php

namespace App\Filament\Admin\Resources\Companies\Schemas;

use Awcodes\Curator\Components\Forms\CuratorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use SolutionForest\FilamentTranslateField\Forms\Component\Translate;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make("Company Information")
                ->schema([
                    TextInput::make("company_name")->required()->maxLength(255),
                    TextInput::make("brand_name")->required()->maxLength(255),
                    CuratorPicker::make("logo")
                        ->label("Logo")
                        ->imageResizeMode("cover")
                        ->imageCropAspectRatio("1:1")
                        ->imageResizeTargetWidth("300")
                        ->imageResizeTargetHeight("300"),
                ])
                ->columnSpanFull(),

            Section::make("Contact Information")
                ->schema([
                    Textarea::make("address")->rows(4)->columnSpanFull(),
                    TextInput::make("email_1")
                        ->email()
                        ->required()
                        ->maxLength(255),
                    TextInput::make("email_2")
                        ->email()
                        ->nullable()
                        ->maxLength(255),
                    TextInput::make("phone_1")
                        ->tel()
                        ->required()
                        ->maxLength(255),
                    TextInput::make("phone_2")
                        ->tel()
                        ->nullable()
                        ->maxLength(255),
                    TextInput::make("tax_id")
                        ->label("Tax ID (NPWP)")
                        ->nullable()
                        ->maxLength(255),
                    TextInput::make("website")
                        ->url()
                        ->nullable()
                        ->maxLength(255),
                    TextInput::make("google_maps_embed_url")
                        ->label("Google Maps Link")
                        ->url()
                        ->nullable()
                        ->maxLength(2048)
                        ->helperText('Paste a maps.app.goo.gl short link, place URL, or the iframe embed code from Share > Embed a map. The embed code shows the business card with reviews.')
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),

            Section::make("Branding & Currency")
                ->schema([
                    Select::make("default_currency")
                        ->options([
                            "IDR" => "IDR - Indonesian Rupiah",
                            "USD" => "USD - US Dollar",
                            "EUR" => "EUR - Euro",
                        ])
                        ->default("IDR")
                        ->required(),
                    TextInput::make("color_primary")
                        ->label("Primary Color (Hex)")
                        ->placeholder("#000000")
                        ->maxLength(7),
                    TextInput::make("color_secondary")
                        ->label("Secondary Color (Hex)")
                        ->placeholder("#FFFFFF")
                        ->maxLength(7),
                ])
                ->columnSpanFull(),

            Section::make("Footer Text")
                ->schema([
                    Translate::make()
                        ->schema([
                            Textarea::make("footer_text")
                                ->rows(3)
                                ->columnSpanFull(),
                        ])
                        ->suffixLocaleLabel(),
                ])
                ->columnSpanFull(),

            Section::make("Bank Accounts")
                ->schema([
                    Repeater::make("bank")
                        ->schema([
                            TextInput::make("bank_name")
                                ->required()
                                ->maxLength(255),
                            TextInput::make("account_name")
                                ->required()
                                ->maxLength(255),
                            TextInput::make("account_number")
                                ->required()
                                ->maxLength(255),
                        ])
                        ->addable()
                        ->reorderable()
                        ->deletable()
                        ->default([])
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),

            Section::make("Person in Charge (PIC)")
                ->schema([
                    Repeater::make("pic")
                        ->schema([
                            TextInput::make("pic_name")
                                ->required()
                                ->maxLength(255),
                            TextInput::make("pic_role")
                                ->required()
                                ->maxLength(255),
                            CuratorPicker::make("pic_sign")
                                ->label("Signature Image")
                                ->imageResizeMode("cover"),
                        ])
                        ->addable()
                        ->reorderable()
                        ->deletable()
                        ->default([])
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
