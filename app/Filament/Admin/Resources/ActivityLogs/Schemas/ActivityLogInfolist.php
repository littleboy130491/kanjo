<?php

namespace App\Filament\Admin\Resources\ActivityLogs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\Activitylog\Models\Activity;

class ActivityLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Activity Details')
                    ->schema([
                        TextEntry::make('causer_id')
                            ->label('User')
                            ->formatStateUsing(fn (mixed $state, Activity $record): string => $record->causer?->name ?? '-'),
                        TextEntry::make('event')
                            ->label('Activity')
                            ->badge()
                            ->default('-'),
                        TextEntry::make('subject_type')
                            ->label('Model Associated')
                            ->formatStateUsing(fn (?string $state): string => filled($state) ? class_basename($state) : '-'),
                        TextEntry::make('subject_id')
                            ->label('Record Associated')
                            ->default('-'),
                        TextEntry::make('created_at')
                            ->label('Date')
                            ->dateTime('d M Y H:i:s'),
                        TextEntry::make('ip_address')
                            ->label('IP')
                            ->default('-')
                            ->copyable(),
                        TextEntry::make('device')
                            ->label('Device')
                            ->default('-')
                            ->columnSpanFull()
                            ->copyable(),
                    ])
                    ->columns(2),
                Section::make('Change Payload')
                    ->schema([
                        TextEntry::make('properties')
                            ->hiddenLabel()
                            ->state(function (Activity $record): string {
                                $properties = $record->properties?->toArray() ?? [];

                                return json_encode(
                                    $properties,
                                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
                                ) ?: '{}';
                            })
                            ->copyable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
