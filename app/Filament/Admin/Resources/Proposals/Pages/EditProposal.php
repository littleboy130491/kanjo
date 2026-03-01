<?php

namespace App\Filament\Admin\Resources\Proposals\Pages;

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Enums\ServiceStatus;
use App\Filament\Admin\Resources\Invoices\InvoiceResource;
use App\Filament\Admin\Resources\Proposals\ProposalResource;
use App\Models\Invoice;
use App\Models\Proposal;
use App\Models\Service;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditProposal extends EditRecord
{
    protected static string $resource = ProposalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->link()
                ->action('save'),
            Action::make('duplicate')
                ->label('Duplicate')
                ->icon('heroicon-o-document-duplicate')
                ->link()
                ->action(function () {
                    $duplicate = $this->duplicateProposal();

                    return redirect(ProposalResource::getUrl('edit', ['record' => $duplicate]));
                }),
            Action::make('create_invoice')
                ->label('Create Invoice')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('primary')
                ->link()
                ->action(function () {
                    $invoice = $this->createInvoiceFromProposal(
                        (float) $this->record->offer_1_price,
                        (string) $this->record->offer_name_1,
                        'DP',
                    );

                    return redirect(InvoiceResource::getUrl('edit', ['record' => $invoice]));
                }),
            Action::make('create_renewal_invoice')
                ->label('Create Renewal Invoice')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->link()
                ->visible(fn(): bool => filled($this->record->offer_1_renewal_price))
                ->action(function () {
                    $title = trim(($this->record->offer_name_1 ?: 'Service') . ' — Renewal');
                    $invoice = $this->createInvoiceFromProposal(
                        (float) $this->record->offer_1_renewal_price,
                        $title,
                        'REN',
                    );

                    return redirect(InvoiceResource::getUrl('edit', ['record' => $invoice]));
                }),
            Action::make('view_document')
                ->label('View Document')
                ->icon('heroicon-o-eye')
                ->link()
                ->url(fn(): string => route('proposal.show', [
                    'slug' => $this->record->slug ?: str_replace('/', '-', $this->record->document_number),
                ]))
                ->openUrlInNewTab(),
            Action::make('create_pdf')
                ->label('Create PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->link()
                ->url(fn(): string => route('pdf.proposal', [
                    'slug' => $this->record->slug ?: str_replace('/', '-', $this->record->document_number),
                ]))
                ->openUrlInNewTab(),
        ];
    }

    private function duplicateProposal(): Proposal
    {
        $this->record->loadMissing('portfolios');

        $duplicate = $this->record->replicate([
            'document_number',
            'slug',
            'document_number_raw',
            'issue_month',
            'issue_year',
            'invoices_count',
            'created_at',
            'updated_at',
            'deleted_at',
        ]);

        $duplicate->status = DocumentStatus::DRAFT;
        $duplicate->document_number_override = false;
        $duplicate->save();
        $duplicate->portfolios()->sync($this->record->portfolios->modelKeys());

        return $duplicate;
    }

    private function createInvoiceFromProposal(float $price, string $title, string $suffix = 'NEW'): Invoice
    {
        $subtotal = $price;
        $taxAmount = $subtotal * (((float) $this->record->tax_rate) / 100);

        $invoice = new Invoice([
            'client_company' => $this->record->client_company,
            'client_name' => $this->record->client_name,
            'client_email' => $this->record->client_email,
            'client_phone' => $this->record->client_phone,
            'client_id' => $this->record->client_id,
            'company_id' => $this->record->company_id,
            'user_id' => auth()->id() ?? $this->record->user_id,
            'service_id' => $this->record->service_id,
            'currency' => $this->record->currency,
            'tax_rate' => $this->record->tax_rate,
            'tax_amount' => $taxAmount,
            'subtotal' => $subtotal,
            'total' => $subtotal + $taxAmount,
            'access_username' => $this->record->access_username,
            'access_password' => $this->record->access_password,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'items' => [
                [
                    'title' => $title,
                    'price' => $price,
                    'description' => '',
                ],
            ],
            'status' => DocumentStatus::PUBLISHED,
            'payment_status' => PaymentStatus::UNPAID,
            'proposal_id' => $this->record->id,
            'notes' => [],
        ]);

        $invoice->documentNumberSuffix = $suffix;
        $invoice->save();

        return $invoice;
    }
}
