<?php

namespace App\Filament\Admin\Resources\SpkContentDefaults\Pages;

use App\Filament\Admin\Resources\SpkContentDefaults\SpkContentDefaultResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSpkContentDefault extends EditRecord
{
    protected static string $resource = SpkContentDefaultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
