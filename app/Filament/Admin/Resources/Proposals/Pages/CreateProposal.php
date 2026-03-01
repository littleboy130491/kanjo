<?php

namespace App\Filament\Admin\Resources\Proposals\Pages;

use App\Filament\Admin\Concerns\HasTranslatableRepeaterSync;
use App\Filament\Admin\Resources\Proposals\ProposalResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateProposal extends CreateRecord
{
    use HasTranslatableRepeaterSync;

    protected static string $resource = ProposalResource::class;

    protected function getTranslatableRepeaterFieldKeys(): array
    {
        return [
            'brief',
            'core_services',
            'features',
            'server',
            'assets',
            'security',
            'support',
            'additional_benefit',
            'add_on',
            'payment',
            'terms_condition',
            'offer_1_project_timeline',
            'offer_2_project_timeline',
        ];
    }

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
