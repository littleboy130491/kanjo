<?php

namespace App\Filament\Admin\Resources\Proposals\Actions;

use App\Models\Proposal;
use Filament\Actions\Action;

class ViewProposalDocumentAction
{
    public static function make(bool $asLink = false): Action
    {
        $action = Action::make('view_document')
            ->label('View Document')
            ->icon('heroicon-o-eye')
            ->url(fn (Proposal $record): string => route('proposal.show', [
                'slug' => $record->slug ?: str_replace('/', '-', $record->document_number),
            ]))
            ->openUrlInNewTab();

        if ($asLink) {
            $action->link();
        }

        return $action;
    }
}
