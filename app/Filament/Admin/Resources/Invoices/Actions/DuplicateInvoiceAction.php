<?php

namespace App\Filament\Admin\Resources\Invoices\Actions;

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use Filament\Actions\Action;

class DuplicateInvoiceAction
{
    public static function make(
        string $name = 'duplicate_invoice',
        string $label = 'Duplicate',
        bool $asLink = false,
    ): Action
    {
        $action = Action::make($name)
            ->label($label)
            ->icon('heroicon-o-document-duplicate')
            ->action(function (Invoice $record) {
                $duplicate = $record->replicate([
                    'document_number',
                    'slug',
                    'document_number_raw',
                    'issue_month',
                    'issue_year',
                    'payment_status',
                    'paid_at',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                ]);

                $duplicate->status = DocumentStatus::DRAFT;
                $duplicate->payment_status = PaymentStatus::UNPAID;
                $duplicate->paid_at = null;
                $duplicate->document_number_override = false;
                $duplicate->save();

                return redirect(InvoiceResource::getUrl('edit', ['record' => $duplicate]));
            });

        if ($asLink) {
            $action->link();
        }

        return $action;
    }
}
