<?php

namespace App\Services\DocumentApi;

use App\Enums\DocumentStatus;
use App\Models\Company;
use App\Models\Proposal;
use App\Models\ProposalContentDefault;
use App\Services\DocumentNumberGenerator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProposalCreator
{
    public function __construct(
        private readonly ClientSnapshotResolver $clients,
        private readonly ContentResolver $content,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function handle(array $payload): array
    {
        $dryRun = (bool) ($payload['dry_run'] ?? false);
        $author = DocumentApiAuthor::user();
        $company = $this->company($payload);
        $issueDate = $this->date($payload['issue_date'] ?? null) ?? now();
        $validUntil = array_key_exists('valid_until', $payload) && $payload['valid_until'] === null
            ? null
            : ($this->date($payload['valid_until'] ?? null) ?? $issueDate->copy()->addDays(30));
        $taxRate = (float) ($payload['tax_rate'] ?? 11);
        $this->content->forPack(ProposalContentDefault::pack(
            isset($payload['content_default_id']) ? (int) $payload['content_default_id'] : null,
        ));
        $resolvedContent = $this->content->resolveProposalContent(
            is_array($payload['content'] ?? null) ? $payload['content'] : [],
            $payload,
        );

        if ($dryRun) {
            $snapshot = $this->clients->resolve($payload, persist: false);
            $preview = DocumentNumberGenerator::preview('QUO', $issueDate);

            return [
                'dry_run' => true,
                'valid' => true,
                'would_create' => [
                    'type' => 'proposal',
                    'document_number' => $preview['document_number'],
                    'client' => [
                        'action' => $snapshot['action'],
                        'company' => $snapshot['client_company'],
                        'name' => $snapshot['client_name'],
                        'client_id' => $snapshot['client_id'],
                    ],
                ],
                'resolved_content_preview' => $resolvedContent,
                'warnings' => $this->warnings($payload),
            ];
        }

        return DB::transaction(function () use (
            $payload,
            $author,
            $company,
            $issueDate,
            $validUntil,
            $taxRate,
            $resolvedContent,
        ): array {
            $snapshot = $this->clients->resolve($payload, persist: true);
            $proposal = new Proposal([
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
                'valid_until' => $validUntil?->toDateString(),
                'currency' => (string) ($payload['currency'] ?? $company->default_currency ?: 'IDR'),
                'activate_translation' => (bool) ($payload['activate_translation'] ?? false),
                'tax_rate' => $taxRate,
                'offer_name_1' => $payload['offer_name_1'] ?? null,
                'offer_1_price' => $payload['offer_1_price'] ?? null,
                'offer_1_original_price' => $payload['offer_1_original_price'] ?? null,
                'offer_1_renewal_price' => $payload['offer_1_renewal_price'] ?? null,
                'offer_1_original_renewal_price' => $payload['offer_1_original_renewal_price'] ?? null,
                'offer_name_2' => $payload['offer_name_2'] ?? null,
                'offer_2_price' => $payload['offer_2_price'] ?? null,
                'offer_2_original_price' => $payload['offer_2_original_price'] ?? null,
                'offer_2_renewal_price' => $payload['offer_2_renewal_price'] ?? null,
                'offer_2_original_renewal_price' => $payload['offer_2_original_renewal_price'] ?? null,
                'status' => DocumentStatus::PUBLISHED,
                'notes' => [],
            ]);

            $this->assignProposalContent($proposal, $resolvedContent);
            $proposal->save();
            $proposal->refresh();

            return DocumentApiResponse::document($proposal);
        });
    }

    /**
     * @param  array<string, mixed>  $resolved
     */
    private function assignProposalContent(Proposal $proposal, array $resolved): void
    {
        foreach (ProposalContentCatalog::SHARED_REPEATER_FIELDS as $field) {
            $proposal->{$field} = $resolved[$field] ?? [];
        }

        foreach ([
            ...ProposalContentCatalog::RICH_TEXT_FIELDS,
            ...ProposalContentCatalog::TRANSLATABLE_REPEATER_FIELDS,
        ] as $field) {
            $proposal->setTranslations($field, $resolved[$field] ?? $this->content->emptyProposalValue($field));
        }
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

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, string>
     */
    private function warnings(array $payload): array
    {
        $warnings = [];

        if (blank($payload['offer_name_1'] ?? null) || blank($payload['offer_1_price'] ?? null)) {
            $warnings[] = 'offer_name_1 and offer_1_price are empty. The published proposal will have no Offer 1 price.';
        }

        return $warnings;
    }
}
