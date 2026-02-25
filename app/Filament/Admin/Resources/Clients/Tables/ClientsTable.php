<?php

namespace App\Filament\Admin\Resources\Clients\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Columns\BadgeColumn;

class ClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Contact Person')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('company')
                    ->label('Company')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Phone'),
                TextColumn::make('proposals_count')
                    ->label('Proposals')
                    ->badge()
                    ->color('info')
                    ->counts('proposals'),
                TextColumn::make('invoices_count')
                    ->label('Invoices')
                    ->badge()
                    ->color('success')
                    ->counts('invoices'),
                TextColumn::make('services_count')
                    ->label('Services')
                    ->badge()
                    ->color('warning')
                    ->counts('services'),
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