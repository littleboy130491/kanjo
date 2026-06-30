<?php

namespace App\Filament\Admin\Resources\Spks\Tables;

use App\Enums\DocumentStatus;
use App\Filament\Admin\Resources\Spks\Actions\DuplicateSpkAction;
use App\Filament\Admin\Resources\Spks\Actions\ViewProposalAction;
use App\Models\Spk;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SpksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document_number')
                    ->label('SPK Number')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Spk $record): ?string => $record->resourceLock?->isActive()
                        ? (($record->resourceLock->user?->name ?? 'Someone').' is editing this record')
                        : null),
                TextColumn::make('client_company')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                TextColumn::make('client_pic_name')
                    ->label('Client PIC')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('company_name')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                TextColumn::make('company_pic_name')
                    ->label('Company PIC')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (DocumentStatus $state): string => $state->getLabel())
                    ->color(fn (DocumentStatus $state): string => $state->getColor())
                    ->sortable(),
                TextColumn::make('spk_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('proposal.document_number')
                    ->label('Proposal')
                    ->url(fn (Spk $record): ?string => $record->proposal_id
                        ? route('filament.admin.resources.proposals.edit', $record->proposal_id)
                        : null)
                    ->openUrlInNewTab()
                    ->placeholder('No proposal'),
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
                    ->label('Company')
                    ->relationship('company', 'brand_name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('has_proposal')
                    ->label('Has Proposal')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('proposal_id'),
                        false: fn (Builder $query) => $query->whereNull('proposal_id'),
                    ),
                Filter::make('spk_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from'),
                        \Filament\Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('spk_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('spk_date', '<=', $date))),
            ])
            ->recordActions([
                DuplicateSpkAction::make(),
                ViewProposalAction::make(),
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

                            $records->each(fn (Spk $record): bool => $record->update(['status' => $status]));
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
