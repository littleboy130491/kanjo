<?php

namespace App\Filament\Admin\Resources\Proposals\Pages;

use App\Filament\Admin\Resources\Proposals\ProposalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProposal extends CreateRecord
{
    protected static string $resource = ProposalResource::class;
}
