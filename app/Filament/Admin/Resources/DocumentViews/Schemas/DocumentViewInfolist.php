<?php

namespace App\Filament\Admin\Resources\DocumentViews\Schemas;

use App\Filament\Admin\Resources\DocumentViews\DocumentViewResource;
use App\Models\DocumentView;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DocumentViewInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('View Details')
                    ->schema([
                        TextEntry::make('document_type')
                            ->label('Document Type')
                            ->state(fn (DocumentView $record): string => $record->viewable_type ? class_basename($record->viewable_type) : '-'),
                        TextEntry::make('document_number')
                            ->label('Document Number')
                            ->state(fn (DocumentView $record): string => $record->viewable?->document_number ?? '-')
                            ->url(fn (DocumentView $record): ?string => DocumentViewResource::resolveDocumentEditUrl($record))
                            ->openUrlInNewTab()
                            ->copyable(),
                        TextEntry::make('viewed_at')
                            ->label('Date')
                            ->dateTime('d M Y H:i:s'),
                        TextEntry::make('page_type')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => $state === 'pdf' ? 'PDF' : 'HTML')
                            ->color(fn (?string $state): string => $state === 'pdf' ? 'success' : 'info'),
                        TextEntry::make('ip_address')
                            ->label('IP')
                            ->default('-')
                            ->copyable(),
                        TextEntry::make('browser')
                            ->default('-'),
                        TextEntry::make('platform')
                            ->default('-'),
                        TextEntry::make('device')
                            ->default('-'),
                        TextEntry::make('user_agent')
                            ->label('User Agent')
                            ->default('-')
                            ->columnSpanFull()
                            ->copyable(),
                        TextEntry::make('referer')
                            ->default('-')
                            ->columnSpanFull()
                            ->copyable(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
