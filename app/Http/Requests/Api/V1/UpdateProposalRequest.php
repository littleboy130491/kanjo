<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\DocumentStatus;
use App\Services\DocumentApi\ProposalContentCatalog;
use Illuminate\Validation\Rule;

class UpdateProposalRequest extends DocumentApiRequest
{
    protected bool $requireAllContentKeys = false;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'dry_run' => ['sometimes', 'boolean'],
            'company_id' => ['sometimes', 'integer', 'exists:companies,id'],
            'content_default_id' => ['nullable', 'integer', 'exists:proposal_content_defaults,id'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'status' => ['sometimes', Rule::enum(DocumentStatus::class)],
            'issue_date' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date'],
            'currency' => ['nullable', 'string', 'max:8'],
            'tax_rate' => ['nullable', 'numeric', 'min:0'],
            'activate_translation' => ['sometimes', 'boolean'],
            'timeline_template' => ['nullable', 'string', Rule::in(ProposalContentCatalog::TIMELINE_TEMPLATES)],
            'offer_name_1' => ['nullable', 'string', 'max:255'],
            'offer_1_price' => ['nullable', 'numeric'],
            'offer_1_original_price' => ['nullable', 'numeric'],
            'offer_1_renewal_price' => ['nullable', 'numeric'],
            'offer_1_original_renewal_price' => ['nullable', 'numeric'],
            'offer_name_2' => ['nullable', 'string', 'max:255'],
            'offer_2_price' => ['nullable', 'numeric'],
            'offer_2_original_price' => ['nullable', 'numeric'],
            'offer_2_renewal_price' => ['nullable', 'numeric'],
            'offer_2_original_renewal_price' => ['nullable', 'numeric'],
            'client_company' => ['nullable', 'string', 'max:255'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'client_email' => ['nullable', 'email', 'max:255'],
            'client_phone' => ['nullable', 'string', 'max:255'],
            'client_address' => ['nullable', 'string'],
            ...$this->clientRules(required: false),
            ...$this->contentRules(),
            'content.*.template' => ['nullable', 'string', Rule::in(ProposalContentCatalog::allowedTemplateKeys())],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function contentFieldKeys(): array
    {
        return ProposalContentCatalog::fieldKeys();
    }

    protected function validationHint(): string
    {
        return 'PATCH /api/v1/proposals/{id}. Send only fields to change. Content keys are optional; each sent key needs mode default, override, or empty. Changing client_id does not rewrite snapshot fields unless you also send them.';
    }
}
