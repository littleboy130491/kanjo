<?php

namespace App\Filament\Admin\Resources\Services\Schemas;

use App\Enums\ServiceStatus;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Service Information')
                    ->schema([
                        TextInput::make('name')
                            ->label('Service Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('domain')
                            ->label('Domain/Website URL')
                            ->url()
                            ->nullable()
                            ->maxLength(255),
                        Select::make('client_id')
                            ->label('Client')
                            ->relationship('client', 'company')
                            ->nullable()
                            ->searchable()
                            ->preload(),
                        Select::make('status')
                            ->label('Status')
                            ->options(ServiceStatus::class)
                            ->default(ServiceStatus::ON_GOING->value)
                            ->required(),
                        TextInput::make('price')
                            ->label('Price')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        TextInput::make('currency')
                            ->label('Currency')
                            ->default('IDR')
                            ->required()
                            ->maxLength(10),
                    ])
                    ->columns(2),

                Section::make('Dates')
                    ->schema([
                        TextInput::make('start_date')
                            ->label('Start Date')
                            ->placeholder('e.g., 2024-01-15')
                            ->nullable()
                            ->maxLength(255),
                        TextInput::make('renewal_date')
                            ->label('Renewal Date')
                            ->placeholder('e.g., January 15')
                            ->nullable()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Notes')
                    ->schema([
                        Repeater::make('notes')
                            ->schema([
                                TextInput::make('note')
                                    ->label('Note')
                                    ->maxLength(500),
                            ])
                            ->columns(1)
                            ->addActionLabel('Add Note'),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
