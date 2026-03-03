<?php

namespace App\Filament\Admin\Resources\Services\Support;

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\Invoices\InvoiceResource;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Service;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Throwable;

class ServiceInvoiceSupport
{
    public static function makeCreateRenewalInvoiceAction(): Action
    {
        return Action::make('create_renewal_invoice')
            ->label('Create Renewal Invoice')
            ->icon('heroicon-o-arrow-path')
            ->color('primary')
            ->requiresConfirmation();
    }

    public static function executeCreateRenewalInvoiceAction(Service $service)
    {
        try {
            $invoice = self::createRenewalInvoice($service);

            Notification::make()
                ->title('Renewal invoice created.')
                ->success()
                ->send();

            return redirect(InvoiceResource::getUrl('edit', ['record' => $invoice]));
        } catch (Throwable $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function createRenewalInvoice(Service $service): Invoice
    {
        $service->loadMissing('client');

        $latestInvoice = $service->invoices()->latest('id')->first();
        $latestProposal = $service->proposals()->latest('id')->first();
        $client = $service->client;

        $companyId = $latestInvoice?->company_id
            ?? $latestProposal?->company_id
            ?? Company::query()->value('id');

        if (! $companyId) {
            throw new \RuntimeException('Cannot create invoice: no company is available.');
        }

        $invoice = new Invoice([
            'client_company' => (string) ($client?->company
                ?? $latestInvoice?->client_company
                ?? $latestProposal?->client_company
                ?? '-'),
            'client_name' => (string) ($client?->name
                ?? $latestInvoice?->client_name
                ?? $latestProposal?->client_name
                ?? '-'),
            'client_email' => (string) ($client?->email
                ?? $latestInvoice?->client_email
                ?? $latestProposal?->client_email
                ?? ''),
            'client_phone' => (string) ($client?->phone
                ?? $latestInvoice?->client_phone
                ?? $latestProposal?->client_phone
                ?? ''),
            'client_id' => $service->client_id,
            'company_id' => $companyId,
            'user_id' => auth()->id() ?? $latestInvoice?->user_id ?? $latestProposal?->user_id,
            'service_id' => $service->id,
            'proposal_id' => $latestInvoice?->proposal_id ?? $latestProposal?->id,
            'currency' => (string) ($service->currency ?: $latestInvoice?->currency ?: $latestProposal?->currency ?: 'IDR'),
            'tax_rate' => (float) ($latestInvoice?->tax_rate ?? $latestProposal?->tax_rate ?? 0),
            'issue_date' => now()->toDateString(),
            'due_date' => self::resolveDueDate($service->renewal_date),
            'items' => [
                [
                    'title' => 'Renewal: ' . (string) ($service->name ?: 'Service'),
                    'price' => (float) ($service->price ?? 0),
                    'description' => (string) ($service->domain ?? ''),
                ],
            ],
            'status' => DocumentStatus::DRAFT,
            'payment_status' => PaymentStatus::UNPAID,
            'access_username' => $latestInvoice?->access_username ?? $latestProposal?->access_username,
            'access_password' => $latestInvoice?->access_password ?? $latestProposal?->access_password,
            'notes' => is_array($service->notes) ? $service->notes : [],
        ]);

        $invoice->documentNumberSuffix = 'REN';
        $invoice->save();

        return $invoice;
    }

    private static function resolveDueDate(?string $renewalDate): string
    {
        if (blank($renewalDate)) {
            return now()->addDays(30)->toDateString();
        }

        try {
            return Carbon::parse($renewalDate)->toDateString();
        } catch (Throwable) {
            return now()->addDays(30)->toDateString();
        }
    }
}
