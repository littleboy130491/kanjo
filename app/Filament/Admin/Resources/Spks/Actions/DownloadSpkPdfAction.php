<?php

namespace App\Filament\Admin\Resources\Spks\Actions;

use App\Models\Spk;
use Filament\Actions\Action;

class DownloadSpkPdfAction
{
    public static function make(
        string $name = 'download_pdf',
        string $label = 'Download PDF',
        bool $asLink = false,
    ): Action {
        $action = Action::make($name)
            ->label($label)
            ->icon('heroicon-o-arrow-down-tray')
            ->url(fn (Spk $record): string => route('pdf.spk', [
                'slug' => $record->slug ?: str_replace('/', '-', $record->document_number),
            ]))
            ->openUrlInNewTab();

        if ($asLink) {
            $action->link();
        }

        return $action;
    }
}
