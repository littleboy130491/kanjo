<?php

namespace App\Filament\Admin\Resources\SpkContentDefaults\Pages;

use App\Filament\Admin\Resources\SpkContentDefaults\SpkContentDefaultResource;
use App\Models\SpkContentDefault;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSpkContentDefaults extends ListRecords
{
    protected static string $resource = SpkContentDefaultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => ! SpkContentDefault::query()
                    ->where('field_key', SpkContentDefault::GLOBAL_FIELD_KEY)
                    ->exists()),
        ];
    }
}
