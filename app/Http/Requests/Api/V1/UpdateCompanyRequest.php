<?php

namespace App\Http\Requests\Api\V1;

class UpdateCompanyRequest extends DocumentApiRequest
{
    protected bool $requireAllContentKeys = false;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'dry_run' => ['sometimes', 'boolean'],
            'company_name' => ['sometimes', 'string', 'max:255'],
            'brand_name' => ['sometimes', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'email_1' => ['sometimes', 'string', 'max:255'],
            'email_2' => ['nullable', 'string', 'max:255'],
            'phone_1' => ['sometimes', 'string', 'max:255'],
            'phone_2' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'google_maps_embed_url' => ['nullable', 'string'],
            'default_currency' => ['nullable', 'string', 'max:8'],
            'color_primary' => ['nullable', 'string', 'max:32'],
            'color_secondary' => ['nullable', 'string', 'max:32'],
            'footer_text' => ['nullable', 'array'],
            'bank' => ['nullable', 'array'],
            'pic' => ['nullable', 'array'],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function contentFieldKeys(): array
    {
        return [];
    }

    protected function validationHint(): string
    {
        return 'PATCH /api/v1/companies/{id}. Logo cannot be changed via API. Omitted fields stay unchanged.';
    }
}
