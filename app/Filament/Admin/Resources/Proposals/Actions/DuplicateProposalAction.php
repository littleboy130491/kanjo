<?php

namespace App\Filament\Admin\Resources\Proposals\Actions;

use App\Enums\DocumentStatus;
use App\Filament\Admin\Resources\Proposals\ProposalResource;
use App\Models\Proposal;
use Filament\Actions\Action;

class DuplicateProposalAction
{
    public static function make(
        string $name = 'duplicate_proposal',
        string $label = 'Duplicate',
        bool $asLink = false,
    ): Action {
        $action = Action::make($name)
            ->label($label)
            ->icon('heroicon-o-document-duplicate')
            ->action(function (Proposal $record) {
                $record->loadMissing('portfolios');

                $duplicate = $record->replicate([
                    'document_number',
                    'slug',
                    'document_number_raw',
                    'document_number_override',
                    'issue_month',
                    'issue_year',
                    'invoices_count',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                ]);

                $duplicate->status = DocumentStatus::DRAFT;
                $duplicate->document_number = null;
                $duplicate->document_number_raw = null;
                $duplicate->issue_month = null;
                $duplicate->issue_year = null;
                $duplicate->document_number_override = false;
                $duplicate->save();
                $duplicate->portfolios()->sync($record->portfolios->modelKeys());

                return redirect(ProposalResource::getUrl('edit', ['record' => $duplicate]));
            });

        if ($asLink) {
            $action->link();
        }

        return $action;
    }
}
