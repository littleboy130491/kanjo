<?php

namespace App\Services\DocumentApi;

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\Proposals\Actions\CreateInvoiceAction;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Proposal;
use App\Services\DocumentNumberGenerator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceCreator
{
    public function __construct(
        private readonly ClientSnapshotResolver $clients,
        private readonly ContentResolver $content,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function handleStandalone(array $payload): array
    {
        $dryRun = (bool) ($payload['dry_run'] ?? false);
        $author = DocumentApiAuthor::user();
        $company = $this->company($payload);
        $issueDate = $this->date($payload['issue_date'] ?? null) ?? now();
        $dueDate = $this->date($payload['due_date'] ?? null) ?? $issueDate->copy()->addDays(30);
        $items = $this->translatedItems($payload['items'] ?? []);
        $additionalInfo = $this->standaloneAdditionalInfo($payload);
        $taxRate = (float) ($payload['tax_rate'] ?? 0);

        if ($dryRun) {
            $snapshot = $this->clients->resolve($payload, persist: false);
            $preview = DocumentNumberGenerator::preview('INV', $issueDate);

            return [
                'dry_run' => true,
                'valid' => true,
                'would_create' => [
                    'type' => 'invoice',
                    'document_number' => $preview['document_number'],
                    'client' => [
                        'action' => $snapshot['action'],
                        'company' => $snapshot['client_company'],
                        'name' => $snapshot['client_name'],
                        'client_id' => $snapshot['client_id'],
                    ],
                ],
                'resolved_content_preview' => [
                    'items' => $items,
                    'additional_info' => $additionalInfo,
                ],
                'warnings' => [],
            ];
        }

        return DB::transaction(function () use (
            $payload,
            $author,
            $company,
            $issueDate,
            $dueDate,
            $items,
            $additionalInfo,
            $taxRate,
        ): array {
            $snapshot = $this->clients->resolve($payload, persist: true);
            $invoice = new Invoice([
                'client_company' => $snapshot['client_company'],
                'client_name' => $snapshot['client_name'],
                'client_address' => $snapshot['client_address'],
                'client_email' => $snapshot['client_email'],
                'client_phone' => $snapshot['client_phone'],
                'client_id' => $snapshot['client_id'],
                'company_id' => $company->id,
                'user_id' => $author->id,
                'updated_by' => $author->id,
                'issue_date' => $issueDate->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'currency' => (string) ($payload['currency'] ?? $company->default_currency ?: 'IDR'),
                'activate_translation' => (bool) ($payload['activate_translation'] ?? false),
                'tax_rate' => $taxRate,
                'service_id' => $payload['service_id'] ?? null,
                'proposal_id' => $payload['proposal_id'] ?? null,
                'status' => DocumentStatus::PUBLISHED,
                'payment_status' => PaymentStatus::UNPAID,
                'notes' => [],
            ]);
            $invoice->setTranslations('items', $items);
            $invoice->setTranslations('additional_info', $additionalInfo);
            $invoice->save();
            $invoice->refresh();

            return DocumentApiResponse::document($invoice);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function handleFromProposal(Proposal $proposal, array $payload): array
    {
        $dryRun = (bool) ($payload['dry_run'] ?? false);
        $author = DocumentApiAuthor::user();
        $offer = (int) ($payload['offer'] ?? 1);
        $renewal = (bool) ($payload['renewal'] ?? false);
        [$price, $title, $suffix] = $this->offerItem($proposal, $offer, $renewal);
        $items = isset($payload['items'])
            ? $this->translatedItems($payload['items'])
            : $this->translatedItems([[
                'title' => $title,
                'price' => $price,
                'description' => '',
            ]]);
        $additionalInfo = $this->fromProposalAdditionalInfo($proposal, $payload);
        $issueDate = now();

        if ($dryRun) {
            $preview = DocumentNumberGenerator::preview('INV', $issueDate, $suffix);

            return [
                'dry_run' => true,
                'valid' => true,
                'would_create' => [
                    'type' => 'invoice',
                    'document_number' => $preview['document_number'],
                    'client' => [
                        'action' => 'existing',
                        'company' => $proposal->client_company,
                        'name' => $proposal->client_name,
                        'client_id' => $proposal->client_id,
                    ],
                    'proposal_id' => $proposal->id,
                    'suffix' => $suffix,
                ],
                'resolved_content_preview' => [
                    'items' => $items,
                    'additional_info' => $additionalInfo,
                ],
                'warnings' => [],
            ];
        }

        if (isset($payload['items']) || $this->hasAdditionalInfoOverride($payload)) {
            return DB::transaction(function () use (
                $proposal,
                $author,
                $items,
                $additionalInfo,
                $suffix,
            ): array {
                $invoice = new Invoice([
                    'client_company' => $proposal->client_company,
                    'client_name' => $proposal->client_name,
                    'client_address' => $proposal->client_address,
                    'client_email' => $proposal->client_email,
                    'client_phone' => $proposal->client_phone,
                    'client_id' => $proposal->client_id,
                    'company_id' => $proposal->company_id,
                    'user_id' => $author->id,
                    'updated_by' => $author->id,
                    'currency' => $proposal->currency,
                    'activate_translation' => (bool) $proposal->activate_translation,
                    'tax_rate' => $proposal->getAttributes()['tax_rate'] ?? 0,
                    'issue_date' => now()->toDateString(),
                    'due_date' => now()->addDays(30)->toDateString(),
                    'status' => DocumentStatus::PUBLISHED,
                    'payment_status' => PaymentStatus::UNPAID,
                    'proposal_id' => $proposal->id,
                    'notes' => [],
                ]);
                $invoice->documentNumberSuffix = $suffix;
                $invoice->setTranslations('items', $items);
                $invoice->setTranslations('additional_info', $additionalInfo);
                $invoice->save();
                $invoice->refresh();

                return DocumentApiResponse::document($invoice);
            });
        }

        $invoice = CreateInvoiceAction::createInvoiceFromProposal(
            $proposal,
            $price,
            $title,
            $suffix,
            $author->id,
        );
        $invoice->refresh();

        return DocumentApiResponse::document($invoice);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, string>
     */
    private function standaloneAdditionalInfo(array $payload): array
    {
        $spec = data_get($payload, 'content.additional_info', ['mode' => 'empty']);
        $mode = (string) ($spec['mode'] ?? 'empty');

        if ($mode === 'override') {
            return $this->content->overrideRichText($spec['value'] ?? null);
        }

        return $this->content->emptyTranslations();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, string>
     */
    private function fromProposalAdditionalInfo(Proposal $proposal, array $payload): array
    {
        $spec = data_get($payload, 'content.additional_info');

        if (! is_array($spec)) {
            return $proposal->getTranslations('additional_info') ?: $this->content->emptyTranslations();
        }

        return match ((string) ($spec['mode'] ?? 'default')) {
            'empty' => $this->content->emptyTranslations(),
            'override' => $this->content->overrideRichText($spec['value'] ?? null),
            default => $proposal->getTranslations('additional_info') ?: $this->content->emptyTranslations(),
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function hasAdditionalInfoOverride(array $payload): bool
    {
        $mode = data_get($payload, 'content.additional_info.mode');

        return in_array($mode, ['empty', 'override'], true);
    }

    /**
     * @return array{0: float, 1: string, 2: string}
     */
    private function offerItem(Proposal $proposal, int $offer, bool $renewal): array
    {
        $offer = $offer === 2 ? 2 : 1;

        if ($renewal) {
            $name = (string) ($offer === 2 ? $proposal->offer_name_2 : $proposal->offer_name_1);
            $price = (float) ($proposal->getAttributes()[$offer === 2 ? 'offer_2_renewal_price' : 'offer_1_renewal_price'] ?? 0);
            $base = filled($name) ? $name : 'Quotation';

            return [$price, $base.' — Renewal', 'NEW'];
        }

        $name = (string) ($offer === 2 ? $proposal->offer_name_2 : $proposal->offer_name_1);
        $price = (float) ($proposal->getAttributes()[$offer === 2 ? 'offer_2_price' : 'offer_1_price'] ?? 0);
        $base = filled($name) ? $name : 'Quotation';
        $title = filled($proposal->document_number)
            ? sprintf('%s (%s)', $base, $proposal->document_number)
            : $base;

        return [$price, $title, 'DP'];
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function translatedItems(mixed $items): array
    {
        if (! is_array($items)) {
            $items = [];
        }

        if (isset($items['en']) || isset($items['id'])) {
            $payload = [];

            foreach ($this->content->locales() as $locale) {
                $payload[$locale] = $this->normalizeItemRows($items[$locale] ?? []);
            }

            return $payload;
        }

        $rows = $this->normalizeItemRows($items);
        $payload = [];

        foreach ($this->content->locales() as $locale) {
            $payload[$locale] = $rows;
        }

        return $payload;
    }

    /**
     * @return array<int, array{title: string, price: float, description: string}>
     */
    private function normalizeItemRows(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $normalized = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $normalized[] = [
                'title' => (string) ($row['title'] ?? ''),
                'price' => (float) ($row['price'] ?? 0),
                'description' => MarkdownOrHtml::toHtml((string) ($row['description'] ?? '')),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function company(array $payload): Company
    {
        $company = Company::query()->find($payload['company_id'] ?? null);

        if (! $company instanceof Company) {
            throw ValidationException::withMessages([
                'company_id' => 'The selected company id is invalid.',
            ]);
        }

        return $company;
    }

    private function date(mixed $value): ?Carbon
    {
        if (! filled($value)) {
            return null;
        }

        return Carbon::parse((string) $value);
    }
}
