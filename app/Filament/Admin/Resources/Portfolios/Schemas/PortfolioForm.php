<?php

namespace App\Filament\Admin\Resources\Portfolios\Schemas;

use Awcodes\Curator\Components\Forms\CuratorPicker;
use Awcodes\Curator\Enums\MimeType;
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
                CuratorPicker::make("image_media_id")
                    ->label("Portfolio Image")
                    ->acceptedFileTypes([
                        MimeType::ImageAvif->value,
                        MimeType::ImageBmp->value,
                        MimeType::ImageGif->value,
                        MimeType::ImageJpeg->value,
                        MimeType::ImagePng->value,
                        MimeType::ImageSvgXml->value,
                        MimeType::ImageTiff->value,
                        MimeType::ImageVndMicrosoftIcon->value,
                        MimeType::ImageWebp->value,
                    ]),
                TextInput::make("image_url_external")
                    ->label("Portfolio Image (from External URL)")
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
