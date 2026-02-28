<?php

namespace App\Filament\Admin\Resources\ProposalContentDefaults\Schemas;

use App\Models\ProposalContentDefault;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProposalContentDefaultForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Content Field')
                    ->schema([
                        Select::make('field_key')
                            ->label('Content Field')
                            ->options(ProposalContentDefault::FIELD_OPTIONS)
                            ->searchable()
                            ->required()
                            ->unique(ignoreRecord: true),
                    ]),
                Section::make('Default Values')
                    ->description('Enter JSON arrays for each locale. Example: [{"feature_name":"...","feature_description":"..."}]')
                    ->schema([
                        Textarea::make('value_en')
                            ->label('Default Value (EN)')
                            ->rows(16)
                            ->required()
                            ->helperText('Must be a valid JSON array.')
                            ->formatStateUsing(fn ($state): string => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                            ->dehydrateStateUsing(fn (?string $state): array => json_decode($state ?: '[]', true) ?: [])
                            ->rule(function () {
                                return function (string $attribute, $value, \Closure $fail): void {
                                    $decoded = json_decode((string) $value, true);

                                    if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                                        $fail('The value must be a valid JSON array.');
                                    }
                                };
                            }),
                        Textarea::make('value_id')
                            ->label('Default Value (ID)')
                            ->rows(16)
                            ->required()
                            ->helperText('Must be a valid JSON array.')
                            ->formatStateUsing(fn ($state): string => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                            ->dehydrateStateUsing(fn (?string $state): array => json_decode($state ?: '[]', true) ?: [])
                            ->rule(function () {
                                return function (string $attribute, $value, \Closure $fail): void {
                                    $decoded = json_decode((string) $value, true);

                                    if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                                        $fail('The value must be a valid JSON array.');
                                    }
                                };
                            }),
                    ])
                    ->columns(2),
            ]);
    }
}
