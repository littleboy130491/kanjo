<?php

namespace App\Filament\Admin\Resources\Proposals\Tables;

use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\DocumentStatus;

class ProposalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document_number')
                    ->searchable()
                    ->sortable()
                    ->weight('font-bold'),
                TextColumn::make('client_company')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                TextColumn::make('client_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('offer_1_price')
                    ->money(fn($record) => $record->currency)
                    ->sortable()
                    ->alignment('right'),
                BadgeColumn::make('status')
                    ->formatStateUsing(fn(DocumentStatus $state): string => $state->getLabel())
                    ->color(fn(DocumentStatus $state): string => $state->getColor()),
                TextColumn::make('issue_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('invoices_count')
                    ->label('Invoices')
                    ->counts('invoices')
                    ->badge()
                    ->color('primary')
                    ->alignment('center'),
                TextColumn::make('company.brand_name')
                    ->label('Company')
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
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(DocumentStatus::class),
                SelectFilter::make('company_id')
                    ->label('Issuing Company')
                    ->relationship('company', 'brand_name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('has_invoice')
                    ->label('Has Invoice')
                    ->queries(
                        true: fn(Builder $query) => $query->whereHas('invoices'),
                        false: fn(Builder $query) => $query->whereDoesntHave('invoices'),
                    ),
                Filter::make('issue_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from'),
                        \Filament\Forms\Components\DatePicker::make('to'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn(Builder $query, $date): Builder => $query->whereDate('issue_date', '>=', $date),
                            )
                            ->when(
                                $data['to'] ?? null,
                                fn(Builder $query, $date): Builder => $query->whereDate('issue_date', '<=', $date),
                            );
                    }),
                Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from'),
                        \Filament\Forms\Components\DatePicker::make('to'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['to'] ?? null,
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                Filter::make('valid_until')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from'),
                        \Filament\Forms\Components\DatePicker::make('to'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn(Builder $query, $date): Builder => $query->whereDate('valid_until', '>=', $date),
                            )
                            ->when(
                                $data['to'] ?? null,
                                fn(Builder $query, $date): Builder => $query->whereDate('valid_until', '<=', $date),
                            );
                    }),
            ]);
    }
}
