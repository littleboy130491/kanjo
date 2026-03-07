<?php

namespace App\Filament\Admin\Resources\Services\RelationManagers;

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\Invoices\InvoiceResource;
use App\Filament\Admin\Resources\Services\Actions\UnlinkInvoiceServiceAction;
use App\Filament\Admin\Resources\Services\Actions\UnlinkInvoiceServiceBulkAction;
use App\Models\Invoice;
use Filament\Actions\BulkActionGroup;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

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
                    ->url(fn(Invoice $record): string => InvoiceResource::getUrl('edit', ['record' => $record])),
                TextColumn::make('client_company')
                    ->label('Client Company')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total')
                    ->money(fn(Invoice $record): string => $record->currency)
                    ->sortable(),
                TextColumn::make('payment_status')
                    ->badge()
                    ->formatStateUsing(fn(PaymentStatus $state): string => $state->getLabel())
                    ->color(fn(PaymentStatus $state): string => $state->getColor())
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(DocumentStatus $state): string => $state->getLabel())
                    ->color(fn(DocumentStatus $state): string => $state->getColor())
                    ->sortable(),
                TextColumn::make('issue_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                UnlinkInvoiceServiceAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    UnlinkInvoiceServiceBulkAction::make(),
                ]),
            ]);
    }
}
