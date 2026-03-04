<?php

namespace App\Filament\Admin\Resources\Proposals\Actions;

use App\Models\Proposal;
use Filament\Actions\Action;

class DownloadProposalPdfAction
{
    public static function make(
        string $name = 'download_pdf',
        string $label = 'Download PDF',
        bool $asLink = false,
    ): Action {
        $action = Action::make($name)
            ->label($label)
            ->icon('heroicon-o-arrow-down-tray')
            ->url(fn (Proposal $record): string => route('pdf.proposal', [
                'slug' => $record->slug ?: str_replace('/', '-', $record->document_number),
            ]))
            ->openUrlInNewTab();

        if ($asLink) {
            $action->link();
        }

        return $action;
    }
}
