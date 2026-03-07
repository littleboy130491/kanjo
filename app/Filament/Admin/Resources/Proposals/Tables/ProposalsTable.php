<?php

namespace App\Filament\Admin\Resources\Proposals\Tables;

use App\Enums\DocumentStatus;
use App\Filament\Admin\Resources\Proposals\Actions\CreateProposalClientAction;
use App\Filament\Admin\Resources\Proposals\Actions\CreateInvoiceAction;
use App\Filament\Admin\Resources\Proposals\Actions\DownloadProposalPdfAction;
use App\Filament\Admin\Resources\Proposals\Actions\DuplicateProposalAction;
use App\Models\Proposal;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

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
                TextColumn::make('offer_name_1')
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
            ])
            ->recordActions([
                CreateInvoiceAction::make(),
                DuplicateProposalAction::make(),
                CreateProposalClientAction::make(),
                DownloadProposalPdfAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (ListRecords $livewire): bool => $livewire->activeTab !== 'trash'),
                RestoreAction::make()
                    ->visible(fn (ListRecords $livewire): bool => $livewire->activeTab === 'trash'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('change_status')
                        ->label('Change Status')
                        ->icon('heroicon-o-pencil-square')
                        ->form([
                            Select::make('status')
                                ->label('Status')
                                ->options(collect(DocumentStatus::cases())->mapWithKeys(
                                    fn(DocumentStatus $status): array => [$status->value => $status->getLabel()]
                                )->all())
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $status = DocumentStatus::from((string) $data['status']);

                            $records->each(fn(Proposal $record): bool => $record->update(['status' => $status]));
                        })
                        ->visible(fn (ListRecords $livewire): bool => $livewire->activeTab !== 'trash'),
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
