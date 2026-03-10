<?php

namespace App\Filament\Admin\Pages;

use App\Settings\TrackingSettings;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ManageTrackingSettings extends SettingsPage
{
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-code-bracket-square';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Tracking Scripts';

    protected static ?string $title = 'Tracking Scripts';

    protected static ?string $slug = 'tracking-scripts';

    protected static string $settings = TrackingSettings::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Tracking Code')
                    ->description('Rendered only on published proposal and invoice document pages for guest visitors. Not rendered on the document access form or PDF output.')
                    ->schema([
                        CodeEditor::make('head_code')
                            ->label('Head Code')
                            ->language(Language::Html)
                            ->helperText('Injected before </head>. Use for scripts that need to initialize early.')
                            ->extraAttributes(['style' => 'min-height: 320px'])
                            ->columnSpanFull(),
                        CodeEditor::make('body_code')
                            ->label('Body Code')
                            ->language(Language::Html)
                            ->helperText('Injected immediately after <body>. Use for noscript tags or body-level tracking snippets.')
                            ->extraAttributes(['style' => 'min-height: 320px'])
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
