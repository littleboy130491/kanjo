<?php

namespace App\Filament\Admin\Resources\Clients\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Client Information')
                    ->schema([
                        TextInput::make('name')
                            ->label('Contact Person')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('company')
                            ->label('Company Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->tel()
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Notes')
                    ->schema([
                        Repeater::make('notes')
                            ->schema([
                                TextInput::make('note')
                                    ->label('Note')
                                    ->required()
                                    ->maxLength(500),
                            ])
                            ->columns(1)
                            ->addActionLabel('Add Note'),
                    ]),
            ]);
    }
}