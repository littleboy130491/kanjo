<?php

namespace App\Filament\Admin\Resources\DocumentViews\Tables;

use App\Filament\Admin\Resources\DocumentViews\DocumentViewResource;
use App\Filament\Admin\Resources\Invoices\InvoiceResource;
use App\Filament\Admin\Resources\Proposals\ProposalResource;
use App\Filament\Admin\Resources\Spks\SpkResource;
use App\Models\DocumentView;
use App\Models\Invoice;
use App\Models\Proposal;
use App\Models\Spk;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DocumentViewsTable
{
    public static function configure(Table $table, bool $withDocument = true): Table
    {
        $columns = [];

        if ($withDocument) {
            $columns[] = TextColumn::make('viewable_type')
                ->label('Document')
                ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-')
                ->sortable();
            $columns[] = TextColumn::make('document_number')
                ->label('Number')
                ->state(fn (DocumentView $record): string => $record->viewable?->document_number ?? class_basename($record->viewable_type ?? '').' #'.$record->viewable_id)
                ->url(fn (DocumentView $record): ?string => DocumentViewResource::resolveDocumentEditUrl($record))
                ->openUrlInNewTab()
                ->copyable();
        }

        $columns[] = TextColumn::make('viewed_at')
            ->label('Date')
            ->dateTime('d M Y H:i:s')
            ->sortable();
        $columns[] = TextColumn::make('page_type')
            ->badge()
            ->formatStateUsing(fn (?string $state): string => $state === 'pdf' ? 'PDF' : 'HTML')
            ->color(fn (?string $state): string => $state === 'pdf' ? 'success' : 'info')
            ->sortable();
        $columns[] = TextColumn::make('ip_address')
            ->label('IP')
            ->searchable()
            ->copyable()
            ->default('-');
        $columns[] = TextColumn::make('browser')
            ->searchable()
            ->default('-')
            ->toggleable();
        $columns[] = TextColumn::make('platform')
            ->default('-')
            ->toggleable();
        $columns[] = TextColumn::make('device')
            ->default('-')
            ->toggleable();
        $columns[] = TextColumn::make('user_agent')
            ->label('User Agent')
            ->limit(40)
            ->tooltip(fn (DocumentView $record): ?string => $record->user_agent)
            ->copyable()
            ->toggleable(isToggledHiddenByDefault: true);
        $columns[] = TextColumn::make('referer')
            ->limit(40)
            ->tooltip(fn (DocumentView $record): ?string => $record->referer)
            ->copyable()
            ->toggleable(isToggledHiddenByDefault: true);

        $filters = [];

        if ($withDocument) {
            $filters[] = SelectFilter::make('viewable_type')
                ->label('Document Type')
                ->options([
                    Proposal::class => 'Proposal',
                    Invoice::class => 'Invoice',
                    Spk::class => 'SPK',
                ]);
        }

        $filters[] = SelectFilter::make('page_type')
            ->label('Page Type')
            ->options([
                'html' => 'HTML',
                'pdf' => 'PDF',
            ]);
        $filters[] = Filter::make('viewed_at')
            ->label('Date')
            ->form([
                DatePicker::make('from')
                    ->label('From'),
                DatePicker::make('until')
                    ->label('Until'),
            ])
            ->query(function ($query, array $data) {
                return $query
                    ->when(
                        $data['from'] ?? null,
                        fn ($query, $date) => $query->whereDate('viewed_at', '>=', $date),
                    )
                    ->when(
                        $data['until'] ?? null,
                        fn ($query, $date) => $query->whereDate('viewed_at', '<=', $date),
                    );
            });

        $configured = $table
            ->columns($columns)
            ->defaultSort('viewed_at', 'desc')
            ->filters($filters);

        if ($withDocument) {
            return $configured->recordActions([
                ViewAction::make(),
            ]);
        }

        return $configured
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function resolveDocumentResourceClass(?string $viewableType): ?string
    {
        return match ($viewableType) {
            Proposal::class => ProposalResource::class,
            Invoice::class => InvoiceResource::class,
            Spk::class => SpkResource::class,
            default => null,
        };
    }
}
