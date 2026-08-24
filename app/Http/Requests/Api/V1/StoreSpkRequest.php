<?php

namespace App\Http\Requests\Api\V1;

use App\Services\DocumentApi\SpkContentCatalog;

class StoreSpkRequest extends DocumentApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'dry_run' => ['sometimes', 'boolean'],
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'spk_date' => ['nullable', 'date'],
            'company_pic_index' => ['nullable', 'integer', 'min:0'],
            'company_pic_name' => ['nullable', 'string', 'max:255'],
            'company_pic_role' => ['nullable', 'string', 'max:255'],
            ...$this->clientRules(),
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
        return 'Fetch GET /api/v1/spks/skeleton and include title, subject, and content each with mode default, override, or empty.';
    }
}
