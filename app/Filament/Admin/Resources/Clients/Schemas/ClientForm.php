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
                self::getClientInformationSection(),
                self::getNotesSection(),
            ]);
    }

    public static function getClientInformationSection(): Section
    {
        return Section::make('Client Information')
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
                    ->maxLength(255),
                TextInput::make('phone')
                    ->tel()
                    ->maxLength(255),
            ])
            ->columns(2);
    }

    public static function getNotesSection(): Section
    {
        return Section::make('Notes')
            ->schema([
                Repeater::make('notes')
                    ->schema([
                        TextInput::make('note')
                            ->label('Note')
                            ->maxLength(500),
                    ])
                    ->columns(1)
                    ->addActionLabel('Add Note'),
            ]);
    }

}