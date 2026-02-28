<?php

namespace App\Filament\Admin\Resources\ProposalContentDefaults\Pages;

use App\Filament\Admin\Resources\ProposalContentDefaults\ProposalContentDefaultResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProposalContentDefault extends EditRecord
{
    protected static string $resource = ProposalContentDefaultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
