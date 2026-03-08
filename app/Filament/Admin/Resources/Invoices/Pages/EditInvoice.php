<?php

namespace App\Filament\Admin\Resources\Invoices\Pages;

use App\Filament\Admin\Resources\Concerns\UsesResourceLock;
use App\Filament\Admin\Resources\Invoices\Actions\CreateServiceAction;
use App\Filament\Admin\Resources\Invoices\Actions\DownloadInvoicePdfAction;
use App\Filament\Admin\Resources\Invoices\Actions\DuplicateInvoiceAction;
use App\Filament\Admin\Resources\Invoices\Actions\ViewInvoiceDocumentAction;
use App\Filament\Admin\Resources\Invoices\Actions\ViewProposalAction;
use App\Filament\Admin\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use App\Models\Service;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    use UsesResourceLock;

    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return $this->mergeLockActions([
            Action::make('save')
                ->label('Save')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->link()
                ->action('save'),
            DuplicateInvoiceAction::make(name: 'duplicate', asLink: true),
            CreateServiceAction::make(
                asLink: true,
                recordResolver: fn () => $this->record,
                useCurrentDateDefaults: true,
                afterLinked: fn (Invoice $record, Service $service) => $this->refreshFormData(['service_id']),
                notify: false,
            )->label('Generate Service'),
            ViewInvoiceDocumentAction::make(asLink: true),
            DownloadInvoicePdfAction::make(name: 'create_pdf', label: 'Create PDF', asLink: true)
                ->icon('heroicon-o-document-arrow-down'),
            ViewProposalAction::make(asLink: true),
        ]);
    }
}
