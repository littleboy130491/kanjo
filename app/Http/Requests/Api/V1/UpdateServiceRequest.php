<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\ServiceStatus;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends DocumentApiRequest
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
            'domain' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'string', 'max:255'],
            'renewal_date' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:8'],
            'status' => ['sometimes', Rule::enum(ServiceStatus::class)],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
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
        return 'PATCH /api/v1/services/{id}. Services use renewal_date (not due_date). status: on-going, suspended, terminated.';
    }
}
