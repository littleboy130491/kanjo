<?php

namespace App\Filament\Admin\Resources\Invoices\Pages;

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Enums\ServiceStatus;
use App\Filament\Admin\Resources\Invoices\InvoiceResource;
use App\Filament\Admin\Resources\Proposals\ProposalResource;
use App\Filament\Admin\Resources\Services\ServiceResource;
use App\Models\Invoice;
use App\Models\Service;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->action('save'),
            Action::make('create_service')
                ->label('Generate Service')
                ->icon('heroicon-o-wrench-screwdriver')
                ->color('success')
                ->visible(fn (): bool => filled($this->record->client_id) && blank($this->record->service_id))
                ->schema([
                    \Filament\Forms\Components\TextInput::make('name')
                        ->maxLength(255)
                        ->default(fn (): string => (string) (data_get($this->record->items, '0.title') ?: $this->record->document_number)),
                    \Filament\Forms\Components\TextInput::make('domain')
                        ->maxLength(255),
                    \Filament\Forms\Components\TextInput::make('start_date')
                        ->maxLength(255)
                        ->default(fn (): ?string => $this->record->issue_date?->toDateString()),
                    \Filament\Forms\Components\TextInput::make('renewal_date')
                        ->maxLength(255)
                        ->default(fn (): ?string => $this->record->due_date?->toDateString()),
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

                    return redirect(ServiceResource::getUrl('edit', ['record' => $service]));
                }),
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
