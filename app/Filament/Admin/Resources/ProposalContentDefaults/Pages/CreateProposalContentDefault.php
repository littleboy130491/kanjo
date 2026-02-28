<?php

namespace App\Filament\Admin\Resources\ProposalContentDefaults\Pages;

use App\Filament\Admin\Resources\ProposalContentDefaults\ProposalContentDefaultResource;
use App\Models\ProposalContentDefault;
use Filament\Resources\Pages\CreateRecord;

class CreateProposalContentDefault extends CreateRecord
{
    protected static string $resource = ProposalContentDefaultResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['field_key'] = ProposalContentDefault::GLOBAL_FIELD_KEY;

        return $data;
    }
}
