<?php

namespace App\Filament\Admin\Resources\ResourceLocks\Tables;

use App\Models\ResourceLock;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ResourceLocksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Locked By')
                    ->searchable(),
                TextColumn::make('lockable_type')
                    ->label('Resource Type')
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->badge(),
                TextColumn::make('lockable_id')
                    ->label('Resource ID'),
                TextColumn::make('locked_at')
                    ->label('Locked At')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('Expires At')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (ResourceLock $record): string => $record->isActive() ? 'Active' : 'Expired')
                    ->color(fn (ResourceLock $record): string => $record->isActive() ? 'success' : 'danger'),
            ])
            ->defaultSort('locked_at', 'desc')
            ->recordActions([
                DeleteAction::make('unlock')
                    ->label('Unlock')
                    ->icon('heroicon-o-lock-open'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make('unlock_selected')
                        ->label('Unlock Selected'),
                ]),
            ]);
    }
}
