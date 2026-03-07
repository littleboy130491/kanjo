<?php

namespace App\Filament\Admin\Resources\Portfolios\Tables;

use App\Filament\Admin\Resources\Portfolios\PortfolioResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PortfoliosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Project Name')
                    ->searchable()
                    ->sortable(),
                ImageColumn::make('portfolio_image_url')
                    ->label('Image')
                    ->size(50)
                    ->url(fn ($record) => $record->portfolio_image_url)
                    ->openUrlInNewTab(),
                TextColumn::make('url_link')
                    ->label('Project Link')
                    ->url(fn ($record) => $record->url_link)
                    ->openUrlInNewTab()
                    ->limit(50),
                TextColumn::make('proposals_count')
                    ->label('Proposals')
                    ->counts('proposals'),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['image', 'proposals']))
            ->contentGrid(['md' => 2, 'lg' => 3])
            ->recordUrl(fn ($record): string => PortfolioResource::getUrl('edit', ['record' => $record]))
            ->filters([
                //
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (ListRecords $livewire): bool => $livewire->activeTab !== 'trash'),
                    ForceDeleteBulkAction::make()
                        ->visible(fn (ListRecords $livewire): bool => $livewire->activeTab === 'trash'),
                ]),
            ]);
    }
}
