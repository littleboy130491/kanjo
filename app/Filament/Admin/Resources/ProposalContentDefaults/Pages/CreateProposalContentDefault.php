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
        $data['slug'] = $data['slug'] ?? \Illuminate\Support\Str::slug((string) ($data['name'] ?? '')) ?: 'pack';
        $data['field_key'] = $data['slug'] === 'default'
            ? ProposalContentDefault::GLOBAL_FIELD_KEY
            : $data['slug'];

        if (isset($data['value']) && is_array($data['value'])) {
            $data['value'] = ProposalContentDefault::syncSharedJsonRepeaterFields($data['value']);
        }

        return $data;
    }
}
