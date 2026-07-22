<?php

namespace App\Filament\Admin\Resources\Invoices\Actions;

use App\Filament\Admin\Resources\Proposals\ProposalResource;
use App\Models\Invoice;
use Filament\Actions\Action;

class ViewProposalAction
{
    public static function make(bool $asLink = false): Action
    {
        $action = Action::make('view_proposal')
            ->label('View Proposal')
            ->icon('heroicon-o-eye')
            ->visible(fn (Invoice $record): bool => filled($record->proposal_id))
            ->url(fn (Invoice $record): string => ProposalResource::getUrl('edit', ['record' => $record->proposal_id]));

        if ($asLink) {
            $action->link();
        }

        return $action;
    }
}
