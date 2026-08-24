<?php

namespace App\Http\Requests\Api\V1;

class UpdateClientRequest extends DocumentApiRequest
{
    protected bool $requireAllContentKeys = false;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'dry_run' => ['sometimes', 'boolean'],
            'name' => ['sometimes', 'string', 'max:255'],
            'company' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'array'],
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
        return 'PATCH /api/v1/clients/{id} with any of name, company, email, phone, address. Omitted fields stay unchanged. Existing documents are not auto-updated.';
    }
}
