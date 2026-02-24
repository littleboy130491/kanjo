<?php

namespace App\Filament\Admin\Resources\Proposals\Pages;

use App\Filament\Admin\Resources\Proposals\ProposalResource;
use Filament\Resources\Pages\EditRecord;

class EditProposal extends EditRecord
{
    protected static string $resource = ProposalResource::class;
}
