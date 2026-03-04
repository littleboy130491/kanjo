<?php

namespace App\Filament\Admin\Resources\Invoices\Actions;

use App\Models\Invoice;
use Filament\Actions\Action;

class ViewInvoiceDocumentAction
{
    public static function make(bool $asLink = false): Action
    {
        $action = Action::make('view_document')
            ->label('View Document')
            ->icon('heroicon-o-eye')
            ->url(fn (Invoice $record): string => route('invoice.show', [
                'slug' => $record->slug ?: str_replace('/', '-', $record->document_number),
            ]))
            ->openUrlInNewTab();

        if ($asLink) {
            $action->link();
        }

        return $action;
    }
}
