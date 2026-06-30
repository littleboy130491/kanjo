<?php

namespace App\Filament\Admin\Resources\Spks\Actions;

use App\Filament\Admin\Resources\Proposals\ProposalResource;
use App\Models\Spk;
use Filament\Actions\Action;

class ViewProposalAction
{
    public static function make(bool $asLink = false): Action
    {
        $action = Action::make('view_proposal')
            ->label('View Proposal')
            ->icon('heroicon-o-arrow-top-right-on-square')
            ->visible(fn (Spk $record): bool => filled($record->proposal_id))
            ->url(fn (Spk $record): string => ProposalResource::getUrl('edit', ['record' => $record->proposal_id]));

        if ($asLink) {
            $action->link();
        }

        return $action;
    }
}
