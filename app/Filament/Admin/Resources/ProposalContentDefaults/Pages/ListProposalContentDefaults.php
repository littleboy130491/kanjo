<?php

namespace App\Filament\Admin\Resources\ProposalContentDefaults\Pages;

use App\Filament\Admin\Resources\ProposalContentDefaults\ProposalContentDefaultResource;
use App\Models\ProposalContentDefault;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProposalContentDefaults extends ListRecords
{
    protected static string $resource = ProposalContentDefaultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => ! ProposalContentDefault::query()
                    ->where('field_key', ProposalContentDefault::GLOBAL_FIELD_KEY)
                    ->exists()),
        ];
    }
}
