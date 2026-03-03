<?php

namespace App\Filament\Admin\Resources\Portfolios\Schemas;

use Awcodes\Curator\Components\Forms\CuratorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PortfolioForm
{
    public static function getPortfolioInformationSection(): Section
    {
        return Section::make("Portfolio Information")
            ->schema([
                TextInput::make("name")
                    ->label("Project Name")
                    ->required()
                    ->maxLength(255),
                CuratorPicker::make("image_url")
                    ->label("Portfolio Image (Curator)")
                    ->imageResizeMode("cover")
                    ->imageCropAspectRatio("16:9")
                    ->imageResizeTargetWidth("1200")
                    ->imageResizeTargetHeight("675")
                    ->visible(fn($get) => !$get('use_external_image')),
                TextInput::make("image_url_external")
                    ->label("Portfolio Image (External URL)")
                    ->url()
                    ->nullable()
                    ->maxLength(2048)
                    ->placeholder("https://example.com/image.jpg")
                    ->helperText("Paste a direct image URL from a CDN or external source"),
                TextInput::make("url_link")
                    ->label("Project Link")
                    ->url()
                    ->nullable()
                    ->maxLength(255)
                    ->helperText("Link to the live project or case study"),
            ])
            ->columnSpanFull();
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([self::getPortfolioInformationSection()]);
    }
}
