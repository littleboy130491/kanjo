<?php

namespace App\Filament\Admin\Resources\Portfolios\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PortfolioForm
{
    public static function getPortfolioInformationSection(): Section
    {
        return Section::make('Portfolio Information')
            ->schema([
                TextInput::make('name')
                    ->label('Project Name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('image_url')
                    ->label('Image URL')
                    ->url()
                    ->nullable()
                    ->maxLength(255)
                    ->helperText('URL to the portfolio image (compatible with Curator)'),
                TextInput::make('url_link')
                    ->label('Project Link')
                    ->url()
                    ->nullable()
                    ->maxLength(255)
                    ->helperText('Link to the live project or case study'),
            ])
            ->columns(2);
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                self::getPortfolioInformationSection(),
            ]);
    }
}
