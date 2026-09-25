<?php

namespace App\Filament\Admin\Resources\DocumentViews\Pages;

use App\Filament\Admin\Resources\DocumentViews\Actions\PruneDocumentViewsAction;
use App\Filament\Admin\Resources\DocumentViews\DocumentViewResource;
use Filament\Resources\Pages\ViewRecord;

class ViewDocumentView extends ViewRecord
{
    protected static string $resource = DocumentViewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PruneDocumentViewsAction::make(redirectToIndexOnSuccess: true),
        ];
    }
}
