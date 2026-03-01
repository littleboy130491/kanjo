<?php

namespace App\Filament\Admin\Resources\Clients\RelationManagers;

use App\Enums\ServiceStatus;
use App\Filament\Admin\Resources\Services\ServiceResource;
use App\Models\Service;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ServicesRelationManager extends RelationManager
{
    protected static string $relationship = 'services';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Service')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Service $record): string => ServiceResource::getUrl('edit', ['record' => $record])),
                TextColumn::make('domain')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (ServiceStatus $state): string => $state->getLabel())
                    ->color(fn (ServiceStatus $state): string => $state->getColor())
                    ->sortable(),
                TextColumn::make('start_date')
                    ->sortable(),
                TextColumn::make('renewal_date')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([])
            ->toolbarActions([]);
    }
}
