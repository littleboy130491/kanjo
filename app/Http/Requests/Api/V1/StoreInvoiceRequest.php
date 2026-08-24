<?php

namespace App\Http\Requests\Api\V1;

class StoreInvoiceRequest extends DocumentApiRequest
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
            'due_date' => ['nullable', 'date'],
            'currency' => ['nullable', 'string', 'max:8'],
            'tax_rate' => ['nullable', 'numeric', 'min:0'],
            'activate_translation' => ['sometimes', 'boolean'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'items' => ['required', 'array', 'min:1'],
            ...$this->clientRules(),
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
        return 'Fetch GET /api/v1/invoices/skeleton. Standalone invoices require items and content.additional_info with mode override or empty.';
    }
}
