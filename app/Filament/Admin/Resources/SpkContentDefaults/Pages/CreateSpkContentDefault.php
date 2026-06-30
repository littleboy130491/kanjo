<?php

namespace App\Filament\Admin\Resources\SpkContentDefaults\Pages;

use App\Filament\Admin\Resources\SpkContentDefaults\SpkContentDefaultResource;
use App\Models\SpkContentDefault;
use Filament\Resources\Pages\CreateRecord;

class CreateSpkContentDefault extends CreateRecord
{
    protected static string $resource = SpkContentDefaultResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['field_key'] = SpkContentDefault::GLOBAL_FIELD_KEY;

        return $data;
    }
}
