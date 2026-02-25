<?php

namespace App\Filament\Admin\Resources\Services\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

class ServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Service Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('domain')
                    ->label('Domain')
                    ->searchable()
                    ->url(fn ($record) => $record->domain),
                TextColumn::make('client.company')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'on-going' => 'success',
                        'suspended' => 'warning',
                        'terminated' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('renewal_date')
                    ->label('Renewal Date'),
                TextColumn::make('start_date')
                    ->label('Start Date'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'on-going' => 'On Going',
                        'suspended' => 'Suspended',
                        'terminated' => 'Terminated',
                    ]),
                SelectFilter::make('client_id')
                    ->label('Client')
                    ->relationship('client', 'company'),
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ]);
    }
}