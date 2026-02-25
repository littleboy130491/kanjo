<?php

namespace App\Filament\Admin\Resources\Portfolios\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

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
                TextColumn::make('image_url')
                    ->label('Image')
                    ->url(fn ($record) => $record->image_url)
                    ->openUrlInNewTab()
                    ->limit(50),
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