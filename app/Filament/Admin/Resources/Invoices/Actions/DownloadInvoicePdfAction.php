<?php

namespace App\Filament\Admin\Resources\Invoices\Actions;

use App\Models\Invoice;
use Filament\Actions\Action;

class DownloadInvoicePdfAction
{
    public static function make(
        string $name = 'download_pdf',
        string $label = 'Download PDF',
        bool $asLink = false,
    ): Action {
        $action = Action::make($name)
            ->label($label)
            ->icon('heroicon-o-arrow-down-tray')
            ->url(fn (Invoice $record): string => route('pdf.invoice', [
                'slug' => $record->slug ?: str_replace('/', '-', $record->document_number),
            ]))
            ->openUrlInNewTab();

        if ($asLink) {
            $action->link();
        }

        return $action;
    }
}
