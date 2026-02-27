<?php

namespace App\Filament\Admin\Resources\Portfolios\Tables;

use Awcodes\Curator\Components\Tables\CuratorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PortfoliosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Project Name')
                    ->searchable()
                    ->sortable(),
                CuratorColumn::make('image_url')
                    ->label('Image')
                    ->size(50)
                    ->url(fn ($record) => $record->image?->url)
                    ->openUrlInNewTab(),
                TextColumn::make('url_link')
                    ->label('Project Link')
                    ->url(fn ($record) => $record->url_link)
                    ->openUrlInNewTab()
                    ->limit(50),
                TextColumn::make('proposals_count')
                    ->label('Proposals')
                    ->counts('proposals'),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['image', 'proposals']))
            ->contentGrid(['md' => 2, 'lg' => 3])
            ->recordUrl(null)
            ->filters([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ]);
    }
}
