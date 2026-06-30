<?php

namespace App\Filament\Admin\Resources\Spks\Pages;

use App\Filament\Admin\Resources\Spks\SpkResource;
use App\Services\SpkTemplateRenderer;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateSpk extends CreateRecord
{
    protected static string $resource = SpkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Create')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->action('create'),
        ];
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        SpkTemplateRenderer::renderDefaultsForRecord($record);
        $record->saveQuietly();
    }
}
