<?php

namespace App\Filament\Admin\Resources\Clients\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
            ->recordActions([
                DeleteAction::make()
                    ->visible(fn (ListRecords $livewire): bool => $livewire->activeTab !== 'trash'),
                RestoreAction::make()
                    ->visible(fn (ListRecords $livewire): bool => $livewire->activeTab === 'trash'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (ListRecords $livewire): bool => $livewire->activeTab !== 'trash'),
                    RestoreBulkAction::make()
                        ->visible(fn (ListRecords $livewire): bool => $livewire->activeTab === 'trash'),
                    ForceDeleteBulkAction::make()
                        ->visible(fn (ListRecords $livewire): bool => $livewire->activeTab === 'trash'),
                ]),
            ]);
    }
}
