<?php

namespace App\Filament\Admin\Resources\Proposals\Pages;

use App\Filament\Admin\Resources\Proposals\ProposalResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateProposal extends CreateRecord
{
    protected static string $resource = ProposalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Create')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->action('create'),
        ];
    }
}
