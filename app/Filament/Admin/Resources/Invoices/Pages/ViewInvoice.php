<?php

namespace App\Filament\Admin\Resources\Invoices\Pages;

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\Invoices\InvoiceResource;
use App\Filament\Admin\Resources\Proposals\ProposalResource;
use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('generate_invoice_from_service')
                ->label('Generate Invoice from Service')
                ->icon('heroicon-o-document-plus')
                ->color('success')
                ->visible(fn (): bool => filled($this->record->service_id))
                ->action(function () {
                    $invoice = $this->createInvoiceFromService();

                    return redirect(InvoiceResource::getUrl('edit', ['record' => $invoice]));
                }),
            Action::make('view_document')
                ->label('View Document')
                ->icon('heroicon-o-eye')
                ->url(fn (): string => route('invoice.show', [
                    'slug' => $this->record->slug ?: str_replace('/', '-', $this->record->document_number),
                ]))
                ->openUrlInNewTab(),
            Action::make('create_pdf')
                ->label('Create PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn (): string => route('pdf.invoice', [
                    'slug' => $this->record->slug ?: str_replace('/', '-', $this->record->document_number),
                ]))
                ->openUrlInNewTab(),
            Action::make('view_proposal')
                ->label('View Proposal')
                ->icon('heroicon-o-eye')
                ->visible(fn (): bool => filled($this->record->proposal_id))
                ->url(fn (): string => ProposalResource::getUrl('edit', ['record' => $this->record->proposal_id])),
        ];
    }

    private function createInvoiceFromService(): Invoice
    {
        $service = $this->record->service;

        return Invoice::create([
            'client_company' => $this->record->client_company,
            'client_name' => $this->record->client_name,
            'client_email' => $this->record->client_email,
            'client_phone' => $this->record->client_phone,
            'client_id' => $this->record->client_id,
            'company_id' => $this->record->company_id,
            'user_id' => auth()->id() ?? $this->record->user_id,
            'service_id' => $this->record->service_id,
            'currency' => $this->record->currency,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'subtotal' => 0,
            'total' => 0,
            'access_username' => $this->record->access_username,
            'access_password' => $this->record->access_password,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'items' => [
                [
                    'title' => (string) ($service?->name ?? ''),
                    'price' => 0,
                    'description' => (string) ($service?->domain ?? ''),
                ],
            ],
            'status' => DocumentStatus::DRAFT,
            'payment_status' => PaymentStatus::UNPAID,
            'proposal_id' => null,
            'notes' => is_array($service?->notes) ? $service->notes : [],
        ]);
    }
}
