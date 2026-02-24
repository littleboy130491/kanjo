<?php

namespace App\Filament\Admin\Resources\Companies\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('brand_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('company_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email_1')
                    ->searchable(),
                TextColumn::make('phone_1')
                    ->searchable(),
                TextColumn::make('default_currency')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('default_currency')
                    ->options([
                        'IDR' => 'IDR',
                        'USD' => 'USD',
                        'EUR' => 'EUR',
                    ]),
            ]);
    }
}
