<?php

namespace App\Filament\Admin\Resources\Proposals\Actions;

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use App\Models\Proposal;
use Filament\Actions\Action;

class CreateInvoiceAction
{
    public static function make(bool $asLink = false): Action
    {
        $action = Action::make('create_invoice')
            ->label('Create Invoice')
            ->icon('heroicon-o-arrow-right-circle')
            ->color('primary')
            ->action(function (Proposal $record) {
                $invoice = self::createInvoiceFromProposal(
                    $record,
                    (float) $record->offer_1_price,
                    self::formatInvoiceItemTitle($record, (string) $record->offer_name_1),
                    'DP',
                );

                return redirect(InvoiceResource::getUrl('edit', ['record' => $invoice]));
            });

        if ($asLink) {
            $action->link();
        }

        return $action;
    }

    private static function createInvoiceFromProposal(
        Proposal $proposal,
        float $price,
        string $title,
        string $suffix = 'NEW',
    ): Invoice {
        $subtotal = $price;
        $taxAmount = $subtotal * (((float) $proposal->tax_rate) / 100);

        $invoice = new Invoice([
            'client_company' => $proposal->client_company,
            'client_name' => $proposal->client_name,
            'client_email' => $proposal->client_email,
            'client_phone' => $proposal->client_phone,
            'client_id' => $proposal->client_id,
            'company_id' => $proposal->company_id,
            'user_id' => auth()->id() ?? $proposal->user_id,
            'currency' => $proposal->currency,
            'tax_rate' => $proposal->tax_rate,
            'tax_amount' => $taxAmount,
            'subtotal' => $subtotal,
            'total' => $subtotal + $taxAmount,
            'access_username' => $proposal->access_username,
            'access_password' => $proposal->access_password,
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
            'proposal_id' => $proposal->id,
            'notes' => [],
        ]);

        $invoice->documentNumberSuffix = $suffix;
        $invoice->save();

        return $invoice;
    }

    private static function formatInvoiceItemTitle(Proposal $proposal, string $title): string
    {
        $baseTitle = filled($title) ? $title : 'Quotation';

        if (blank($proposal->document_number)) {
            return $baseTitle;
        }

        return sprintf('%s (%s)', $baseTitle, $proposal->document_number);
    }
}
