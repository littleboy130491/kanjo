<?php

namespace App\Filament\Admin\Resources\Proposals\RelationManagers;

use App\Enums\DocumentStatus;
use App\Filament\Admin\Resources\Spks\SpkResource;
use App\Models\Spk;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SpksRelationManager extends RelationManager
{
    protected static string $relationship = 'spks';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document_number')
                    ->label('SPK Number')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Spk $record): string => SpkResource::getUrl('edit', ['record' => $record])),
                TextColumn::make('client_company')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client_pic_name')
                    ->label('Client PIC')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (DocumentStatus $state): string => $state->getLabel())
                    ->color(fn (DocumentStatus $state): string => $state->getColor())
                    ->sortable(),
                TextColumn::make('spk_date')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([])
            ->toolbarActions([]);
    }
}
