<?php

namespace App\Filament\Admin\Resources\Clients\RelationManagers;

use App\Enums\DocumentStatus;
use App\Filament\Admin\Resources\Proposals\ProposalResource;
use App\Models\Proposal;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProposalsRelationManager extends RelationManager
{
    protected static string $relationship = 'proposals';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document_number')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Proposal $record): string => ProposalResource::getUrl('edit', ['record' => $record])),
                TextColumn::make('offer_name_1')
                    ->label('Offer')
                    ->sortable(),
                TextColumn::make('offer_1_price')
                    ->money(fn (Proposal $record): string => $record->currency)
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (DocumentStatus $state): string => $state->getLabel())
                    ->color(fn (DocumentStatus $state): string => $state->getColor()),
                TextColumn::make('issue_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('valid_until')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([])
            ->toolbarActions([]);
    }
}
