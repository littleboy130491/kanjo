<?php

namespace App\Filament\Admin\Resources\Services\Tables;

use App\Enums\ServiceStatus;
use App\Filament\Admin\Resources\Services\Support\ServiceInvoiceSupport;
use App\Models\Service;
use App\Support\ServiceDate;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

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
                TextColumn::make('price')
                    ->label('Price')
                    ->money(fn ($record): string => (string) ($record->currency ?: 'IDR'))
                    ->sortable(),
                TextColumn::make('currency')
                    ->label('Currency')
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state): string => $state instanceof ServiceStatus ? $state->getColor() : 'gray'),
                TextColumn::make('renewal_date')
                    ->label('Renewal Date')
                    ->formatStateUsing(fn (?string $state): ?string => ServiceDate::format($state, includeYear: false)),
                TextColumn::make('start_date')
                    ->label('Start Date')
                    ->formatStateUsing(fn (?string $state): ?string => ServiceDate::format($state)),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ServiceStatus::class),
                SelectFilter::make('client_id')
                    ->label('Client')
                    ->relationship('client', 'company'),
                Filter::make('renewal_month')
                    ->label('Renewal Month')
                    ->form([
                        \Filament\Forms\Components\Select::make('month')
                            ->label('Month')
                            ->options([
                                1 => 'January',
                                2 => 'February',
                                3 => 'March',
                                4 => 'April',
                                5 => 'May',
                                6 => 'June',
                                7 => 'July',
                                8 => 'August',
                                9 => 'September',
                                10 => 'October',
                                11 => 'November',
                                12 => 'December',
                            ]),
                    ])
                    ->query(function ($query, array $data) {
                        if (! $data['month']) {
                            return $query;
                        }

                        return $query->whereRaw(
                            "MONTH(STR_TO_DATE(renewal_date, '%Y-%m-%d')) = ?",
                            [$data['month']],
                        );
                    }),
                Filter::make('start_date')
                    ->label('Start Date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('From'),
                        \Filament\Forms\Components\DatePicker::make('to')
                            ->label('To'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn($query) => $query->whereDate('start_date', '>=', $data['from']),
                            )
                            ->when(
                                $data['to'] ?? null,
                                fn($query) => $query->whereDate('start_date', '<=', $data['to']),
                            );
                    }),
            ])
            ->recordActions([
                ServiceInvoiceSupport::makeCreateRenewalInvoiceAction()
                    ->visible(fn (Service $record): bool => filled($record->client_id))
                    ->action(fn (Service $record) => ServiceInvoiceSupport::executeCreateRenewalInvoiceAction($record)),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (ListRecords $livewire): bool => $livewire->activeTab !== 'trash'),
                    ForceDeleteBulkAction::make()
                        ->visible(fn (ListRecords $livewire): bool => $livewire->activeTab === 'trash'),
                ]),
            ]);
    }
}
