<?php

namespace App\Filament\Admin\Resources\Proposals\Pages;

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Enums\ServiceStatus;
use App\Filament\Admin\Resources\Invoices\InvoiceResource;
use App\Filament\Admin\Resources\Proposals\ProposalResource;
use App\Models\Invoice;
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
                ->action('save'),
            Action::make('convert_to_invoice')
                ->label('Convert to Invoice')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('primary')
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
            Action::make('create_service')
                ->label('Generate Service')
                ->icon('heroicon-o-wrench-screwdriver')
                ->color('success')
                ->visible(fn(): bool => filled($this->record->client_id) && blank($this->record->service_id))
                ->schema([
                    \Filament\Forms\Components\TextInput::make('name')
                        ->maxLength(255)
                        ->default(fn(): string => (string) ($this->record->offer_name_1 ?: $this->record->document_number)),
                    \Filament\Forms\Components\TextInput::make('domain')
                        ->maxLength(255),
                    \Filament\Forms\Components\TextInput::make('start_date')
                        ->maxLength(255)
                        ->default(fn(): ?string => $this->record->issue_date?->toDateString()),
                    \Filament\Forms\Components\TextInput::make('renewal_date')
                        ->maxLength(255)
                        ->default(fn(): ?string => $this->record->valid_until?->toDateString()),
                ])
                ->action(function (array $data) {
                    $service = Service::create([
                        'name' => (string) ($data['name'] ?? ''),
                        'domain' => $data['domain'] ?: null,
                        'start_date' => $data['start_date'] ?: null,
                        'renewal_date' => $data['renewal_date'] ?: null,
                        'client_id' => $this->record->client_id,
                        'status' => ServiceStatus::ON_GOING,
                        'notes' => is_array($this->record->notes) ? $this->record->notes : [],
                    ]);

                    $this->record->update(['service_id' => $service->id]);
                    $this->refreshFormData(['service_id']);
                }),
            Action::make('view_document')
                ->label('View Document')
                ->icon('heroicon-o-eye')
                ->url(fn(): string => route('proposal.show', [
                    'slug' => $this->record->slug ?: str_replace('/', '-', $this->record->document_number),
                ]))
                ->openUrlInNewTab(),
            Action::make('create_pdf')
                ->label('Create PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn(): string => route('pdf.proposal', [
                    'slug' => $this->record->slug ?: str_replace('/', '-', $this->record->document_number),
                ]))
                ->openUrlInNewTab(),
        ];
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
