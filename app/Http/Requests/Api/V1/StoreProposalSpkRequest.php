<?php

namespace App\Http\Requests\Api\V1;

use App\Services\DocumentApi\SpkContentCatalog;

class StoreProposalSpkRequest extends DocumentApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'dry_run' => ['sometimes', 'boolean'],
            'offer' => ['nullable', 'integer', 'in:1,2'],
            'offer_index' => ['nullable', 'integer', 'in:1,2'],
            'company_pic_index' => ['nullable', 'integer', 'min:0'],
            'company_pic_name' => ['nullable', 'string', 'max:255'],
            'company_pic_role' => ['nullable', 'string', 'max:255'],
            'client' => ['nullable', 'array'],
            'client.pic_role' => ['nullable', 'string', 'max:255'],
            'activate_translation' => ['sometimes', 'boolean'],
            ...$this->contentRules(),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function contentFieldKeys(): array
    {
        return SpkContentCatalog::FIELD_KEYS;
    }

    protected function validationHint(): string
    {
        return 'Fetch GET /api/v1/spks/skeleton. From-proposal SPKs still require title, party_identification, subject, and content modes. Optional company_pic_index selects a company PIC.';
    }
}
