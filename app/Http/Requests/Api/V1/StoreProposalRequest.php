<?php

namespace App\Http\Requests\Api\V1;

use App\Services\DocumentApi\ProposalContentCatalog;
use Illuminate\Validation\Rule;

class StoreProposalRequest extends DocumentApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'dry_run' => ['sometimes', 'boolean'],
            'company_id' => ['required', 'integer', 'exists:companies,id'],
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
            ...$this->clientRules(),
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
        return 'Fetch GET /api/v1/proposals/skeleton and include every content key with mode default, override, or empty.';
    }
}
