<?php

namespace App\Filament\Admin\Resources\Invoices\Tables;

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\Invoices\Actions\CreateServiceAction;
use App\Filament\Admin\Resources\Invoices\Actions\CreateInvoiceClientAction;
use App\Filament\Admin\Resources\Invoices\Actions\DownloadInvoicePdfAction;
use App\Filament\Admin\Resources\Invoices\Actions\DuplicateInvoiceAction;
use App\Filament\Admin\Resources\Invoices\Actions\MarkAsPaidAction;
use App\Filament\Admin\Resources\Invoices\Actions\ViewProposalAction;
use App\Models\Invoice;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document_number')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Invoice $record): ?string => $record->resourceLock?->isActive()
                        ? (($record->resourceLock->user?->name ?? 'Someone') . ' is editing this record')
                        : null),
                TextColumn::make('client_company')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total')
                    ->money(fn ($record) => $record->currency)
                    ->sortable(),
                TextColumn::make('payment_status')
                    ->badge()
                    ->formatStateUsing(fn (PaymentStatus $state): string => $state->getLabel())
                    ->color(fn (PaymentStatus $state): string => $state->getColor())
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (DocumentStatus $state): string => $state->getLabel())
                    ->color(fn (DocumentStatus $state): string => $state->getColor())
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
                        fn ($record) => $record->proposal
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
                        true: fn ($query) => $query->whereNotNull('proposal_id'),
                        false: fn ($query) => $query->whereNull('proposal_id'),
                    ),
                Filter::make('issue_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from'),
                        \Filament\Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn ($query, $date) => $query->whereDate('issue_date', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn ($query, $date) => $query->whereDate('issue_date', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                DuplicateInvoiceAction::make(),
                MarkAsPaidAction::make(),
                ViewProposalAction::make(),
                CreateInvoiceClientAction::make(),
                CreateServiceAction::make(),
                DownloadInvoicePdfAction::make(),
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
                                    fn (DocumentStatus $status): array => [$status->value => $status->getLabel()]
                                )->all())
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $status = DocumentStatus::from((string) $data['status']);

                            $records->each(fn (Invoice $record): bool => $record->update(['status' => $status]));
                        })
                        ->visible(fn (ListRecords $livewire): bool => $livewire->activeTab !== 'trash'),
                    BulkAction::make('change_payment_status')
                        ->label('Change Payment Status')
                        ->icon('heroicon-o-banknotes')
                        ->form([
                            Select::make('payment_status')
                                ->label('Payment Status')
                                ->options(collect(PaymentStatus::cases())->mapWithKeys(
                                    fn (PaymentStatus $status): array => [$status->value => $status->getLabel()]
                                )->all())
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $paymentStatus = PaymentStatus::from((string) $data['payment_status']);
                            $paidAt = $paymentStatus === PaymentStatus::PAID ? now() : null;

                            $records->each(fn (Invoice $record): bool => $record->update([
                                'payment_status' => $paymentStatus,
                                'paid_at' => $paidAt,
                            ]));
                        })
                        ->visible(fn (ListRecords $livewire): bool => $livewire->activeTab !== 'trash'),
                    DeleteBulkAction::make()
                        ->visible(fn (ListRecords $livewire): bool => $livewire->activeTab !== 'trash'),
                    RestoreBulkAction::make()
                        ->visible(fn (ListRecords $livewire): bool => $livewire->activeTab === 'trash'),
                    ForceDeleteBulkAction::make()
                        ->visible(fn (ListRecords $livewire): bool => $livewire->activeTab === 'trash'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
