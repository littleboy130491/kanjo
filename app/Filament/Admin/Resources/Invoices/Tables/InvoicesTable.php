<?php

namespace App\Filament\Admin\Resources\Invoices\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client_company')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total')
                    ->money(fn($record) => $record->currency)
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
                TextColumn::make('proposal.document_number')
                    ->label('Proposal')
                    ->url(
                        fn($record) => $record->proposal
                        ? route('filament.admin.resources.proposals.edit', $record->proposal)
                        : null
                    )
                    ->openUrlInNewTab()
                    ->placeholder('No proposal')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(DocumentStatus::class),
                SelectFilter::make('payment_status')
                    ->options(PaymentStatus::class),
                SelectFilter::make('company_id')
                    ->label('Company')
                    ->relationship('company', 'brand_name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('has_proposal')
                    ->label('Has Proposal')
                    ->placeholder('All invoices')
                    ->trueLabel('With proposal')
                    ->falseLabel('Without proposal')
                    ->queries(
                        true: fn($query) => $query->whereNotNull('proposal_id'),
                        false: fn($query) => $query->whereNull('proposal_id'),
                    ),
                Filter::make('issue_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from'),
                        \Filament\Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['from'],
                                fn($query, $date) => $query->whereDate('issue_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn($query, $date) => $query->whereDate('issue_date', '<=', $date),
                            );
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
