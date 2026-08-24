<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\DocumentStatus;
use App\Services\DocumentApi\SpkContentCatalog;
use Illuminate\Validation\Rule;

class UpdateSpkRequest extends DocumentApiRequest
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
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'proposal_id' => ['nullable', 'integer', 'exists:proposals,id'],
            'status' => ['sometimes', Rule::enum(DocumentStatus::class)],
            'spk_date' => ['nullable', 'date'],
            'offer' => ['nullable', 'integer', 'in:1,2'],
            'offer_index' => ['nullable', 'integer', 'in:1,2'],
            'client_company' => ['nullable', 'string', 'max:255'],
            'client_pic_name' => ['nullable', 'string', 'max:255'],
            'client_pic_role' => ['nullable', 'string', 'max:255'],
            'client_address' => ['nullable', 'string'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'company_pic_name' => ['nullable', 'string', 'max:255'],
            'company_pic_role' => ['nullable', 'string', 'max:255'],
            'company_address' => ['nullable', 'string'],
            ...$this->clientRules(required: false),
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
        return 'PATCH /api/v1/spks/{id}. Send only fields to change. Content keys optional; each sent key needs mode default, override, or empty.';
    }
}
