<?php

namespace App\Filament\Admin\Resources\ActivityLogs\Tables;

use App\Filament\Admin\Resources\ActivityLogs\Schemas\ActivityLogInfolist;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
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
                TextColumn::make('subject_type')
                    ->label('Model Associated')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? class_basename($state) : '-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subject_id')
                    ->label('Record Associated')
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
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
