<?php

namespace App\Filament\Admin\Resources\ProposalContentDefaults\Pages;

use App\Filament\Admin\Resources\ProposalContentDefaults\ProposalContentDefaultResource;
use App\Models\ProposalContentDefault;
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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var ProposalContentDefault $record */
        $record = $this->getRecord();

        $data['value'] = $record->getTranslations('value');

        return $data;
    }
}
