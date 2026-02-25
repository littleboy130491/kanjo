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
        return $schema
            ->components([
                Section::make('Company Information')
                    ->schema([
                        TextInput::make('company_name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('brand_name')
                            ->required()
                            ->maxLength(255),
                        CuratorPicker::make('logo')
                            ->label('Logo')
                            ->directory('company-logos')
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('300')
                            ->imageResizeTargetHeight('300'),
                    ])
                    ->columns(2),

                Section::make('Contact Information')
                    ->schema([
                        Textarea::make('address')
                            ->rows(4)
                            ->columnSpanFull(),
                        TextInput::make('email_1')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email_2')
                            ->email()
                            ->nullable()
                            ->maxLength(255),
                        TextInput::make('phone_1')
                            ->tel()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone_2')
                            ->tel()
                            ->nullable()
                            ->maxLength(255),
                        TextInput::make('tax_id')
                            ->label('Tax ID (NPWP)')
                            ->nullable()
                            ->maxLength(255),
                        TextInput::make('website')
                            ->url()
                            ->nullable()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Branding & Currency')
                    ->schema([
                        Select::make('default_currency')
                            ->options([
                                'IDR' => 'IDR - Indonesian Rupiah',
                                'USD' => 'USD - US Dollar',
                                'EUR' => 'EUR - Euro',
                            ])
                            ->default('IDR')
                            ->required(),
                        TextInput::make('color_primary')
                            ->label('Primary Color (Hex)')
                            ->placeholder('#000000')
                            ->maxLength(7),
                        TextInput::make('color_secondary')
                            ->label('Secondary Color (Hex)')
                            ->placeholder('#FFFFFF')
                            ->maxLength(7),
                    ])
                    ->columns(3),

                Section::make('Footer Text')
                    ->schema([
                        Translate::make()
                            ->schema([
                                Textarea::make('footer_text')
                                    ->required()
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ])
                            ->locales(['en', 'id'])
                            ->suffixLocaleLabel(),
                    ]),

                Section::make('Bank Accounts')
                    ->schema([
                        Repeater::make('bank')
                            ->schema([
                                TextInput::make('bank_name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('account_name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('account_number')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->addable()
                            ->reorderable()
                            ->deletable()
                            ->default([])
                            ->columns(3),
                    ]),

                Section::make('Person in Charge (PIC)')
                    ->schema([
                        Repeater::make('pic')
                            ->schema([
                                TextInput::make('pic_name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('pic_role')
                                    ->required()
                                    ->maxLength(255),
                                CuratorPicker::make('pic_sign')
                                    ->label('Signature Image')
                                    ->directory('pic-signatures')
                                    ->imageResizeMode('cover'),
                            ])
                            ->addable()
                            ->reorderable()
                            ->deletable()
                            ->default([])
                            ->columns(3),
                    ]),
            ]);
    }
}
