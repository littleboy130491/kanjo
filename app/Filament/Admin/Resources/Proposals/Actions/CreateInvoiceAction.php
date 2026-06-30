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
            'client_address' => $proposal->client_address,
            'client_email' => $proposal->client_email,
            'client_phone' => $proposal->client_phone,
            'client_id' => $proposal->client_id,
            'company_id' => $proposal->company_id,
            'user_id' => auth()->id() ?? $proposal->user_id,
            'currency' => $proposal->currency,
            'activate_translation' => (bool) $proposal->activate_translation,
            'tax_rate' => $proposal->tax_rate,
            'tax_amount' => $taxAmount,
            'subtotal' => $subtotal,
            'total' => $subtotal + $taxAmount,
            'additional_info' => $proposal->getTranslations('additional_info'),
            'access_username' => $proposal->access_username,
            'access_password' => $proposal->access_password,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'items' => self::makeTranslatedItemsPayload(
                $title,
                $price,
                self::makeProposalItemDescription($proposal),
            ),
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

    private static function makeProposalItemDescription(Proposal $proposal): string
    {
        if (blank($proposal->slug)) {
            return '';
        }

        $label = filled($proposal->document_number)
            ? "View proposal {$proposal->document_number}"
            : 'View proposal';

        return sprintf(
            '<a href="%s">%s</a>',
            e(route('proposal.show', ['slug' => $proposal->slug])),
            e($label),
        );
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private static function makeTranslatedItemsPayload(string $title, float $price, string $description = ''): array
    {
        $item = [
            'title' => $title,
            'price' => $price,
            'description' => $description,
        ];

        $payload = [];

        foreach (config('translatable.locales', ['en', 'id']) as $locale) {
            $payload[$locale] = [$item];
        }

        return $payload;
    }
}
