<?php

namespace App\Filament\Admin\Resources\Invoices\Support;

use App\Enums\ServiceStatus;
use App\Models\Invoice;
use App\Models\Service;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\DB;

class InvoiceServiceSupport
{
    /**
     * @return array<int, TextInput>
     */
    public static function createServiceFormSchema(?callable $recordResolver = null, bool $useCurrentDateDefaults = false): array
    {
        return [
            TextInput::make('name')
                ->maxLength(255)
                ->default(function (?Invoice $record = null) use ($recordResolver): string {
                    $invoice = self::resolveInvoice($record, $recordResolver);

                    return (string) (data_get($invoice?->items, '0.title') ?: $invoice?->document_number ?: '');
                }),
            TextInput::make('domain')
                ->maxLength(255),
            TextInput::make('price')
                ->numeric()
                ->default(function (?Invoice $record = null) use ($recordResolver): float {
                    $invoice = self::resolveInvoice($record, $recordResolver);

                    return (float) (data_get($invoice?->items, '0.price') ?? 0);
                }),
            TextInput::make('currency')
                ->maxLength(10)
                ->default(function (?Invoice $record = null) use ($recordResolver): string {
                    $invoice = self::resolveInvoice($record, $recordResolver);

                    return (string) ($invoice?->currency ?: 'IDR');
                }),
            TextInput::make('start_date')
                ->maxLength(255)
                ->default(function (?Invoice $record = null) use ($recordResolver, $useCurrentDateDefaults): string {
                    $invoice = self::resolveInvoice($record, $recordResolver);

                    return $useCurrentDateDefaults
                        ? now()->toDateString()
                        : (string) ($invoice?->issue_date?->toDateString() ?: '');
                }),
            TextInput::make('renewal_date')
                ->maxLength(255)
                ->default(function (?Invoice $record = null) use ($recordResolver, $useCurrentDateDefaults): string {
                    $invoice = self::resolveInvoice($record, $recordResolver);

                    return $useCurrentDateDefaults
                        ? now()->toDateString()
                        : (string) ($invoice?->due_date?->toDateString() ?: '');
                }),
        ];
    }

    public static function createServiceFromInvoice(Invoice $invoice, array $data): Service
    {
        return DB::transaction(function () use ($invoice, $data): Service {
            $service = Service::create([
                'name' => (string) ($data['name'] ?? ''),
                'domain' => $data['domain'] ?: null,
                'price' => (float) ($data['price'] ?? data_get($invoice->items, '0.price', 0)),
                'currency' => (string) ($data['currency'] ?: $invoice->currency ?: 'IDR'),
                'start_date' => $data['start_date'] ?: null,
                'renewal_date' => $data['renewal_date'] ?: null,
                'client_id' => $invoice->client_id,
                'status' => ServiceStatus::ON_GOING,
                'notes' => is_array($invoice->notes) ? $invoice->notes : [],
            ]);

            // Keep proposal-service linkage intact for flow: proposal -> invoice -> service.
            if ($invoice->proposal_id) {
                $invoice->proposal()
                    ->whereNull('service_id')
                    ->update(['service_id' => $service->id]);
            }

            return $service;
        });
    }

    private static function resolveInvoice(?Invoice $record, ?callable $recordResolver): ?Invoice
    {
        if ($record instanceof Invoice) {
            return $record;
        }

        if ($recordResolver === null) {
            return null;
        }

        $resolved = $recordResolver();

        return $resolved instanceof Invoice ? $resolved : null;
    }
}
