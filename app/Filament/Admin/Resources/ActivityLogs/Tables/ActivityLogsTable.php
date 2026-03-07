<?php

namespace App\Filament\Admin\Resources\ActivityLogs\Tables;

use App\Filament\Admin\Resources\ActivityLogs\Schemas\ActivityLogInfolist;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('causer_id')
                    ->label('User')
                    ->formatStateUsing(fn (mixed $state, Activity $record): string => $record->causer?->name ?? '-')
                    ->sortable(),
                TextColumn::make('event')
                    ->label('Activity')
                    ->badge()
                    ->sortable(),
                TextColumn::make('associated_model')
                    ->label('Model Associated')
                    ->state(fn (Activity $record): string => ActivityLogInfolist::resolveAssociatedModel($record))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('associated_record')
                    ->label('Record Associated')
                    ->state(fn (Activity $record): string => ActivityLogInfolist::resolveAssociatedRecord($record))
                    ->searchable()
                    ->sortable()
                    ->url(fn (Activity $record): ?string => ActivityLogInfolist::resolveSubjectEditUrl($record))
                    ->openUrlInNewTab(),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('device')
                    ->label('Device')
                    ->limit(40)
                    ->tooltip(fn (Activity $record): ?string => $record->device)
                    ->searchable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('event')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'restored' => 'Restored',
                        'rate_limited' => 'Rate Limited',
                    ]),
                Filter::make('created_at')
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
                                fn ($query, $date) => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn ($query, $date) => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
