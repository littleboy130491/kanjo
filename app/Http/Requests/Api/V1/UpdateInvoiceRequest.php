<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use Illuminate\Validation\Rule;

class UpdateInvoiceRequest extends DocumentApiRequest
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
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'status' => ['sometimes', Rule::enum(DocumentStatus::class)],
            'payment_status' => ['sometimes', Rule::enum(PaymentStatus::class)],
            'issue_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'currency' => ['nullable', 'string', 'max:8'],
            'tax_rate' => ['nullable', 'numeric', 'min:0'],
            'activate_translation' => ['sometimes', 'boolean'],
            'items' => ['sometimes', 'array', 'min:1'],
            'client_company' => ['nullable', 'string', 'max:255'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'client_email' => ['nullable', 'email', 'max:255'],
            'client_phone' => ['nullable', 'string', 'max:255'],
            'client_address' => ['nullable', 'string'],
            ...$this->clientRules(required: false),
            ...$this->contentRules(),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function contentFieldKeys(): array
    {
        return ['additional_info'];
    }

    /**
     * @return array<int, string>
     */
    protected function allowedModesFor(string $field): array
    {
        return ['override', 'empty'];
    }

    protected function validationHint(): string
    {
        return 'PATCH /api/v1/invoices/{id}. Send only fields to change. items optional. content.additional_info mode override or empty. Changing client_id does not rewrite snapshots unless sent.';
    }
}
