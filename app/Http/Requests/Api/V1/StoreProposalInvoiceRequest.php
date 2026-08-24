<?php

namespace App\Http\Requests\Api\V1;

class StoreProposalInvoiceRequest extends DocumentApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'dry_run' => ['sometimes', 'boolean'],
            'offer' => ['nullable', 'integer', 'in:1,2'],
            'renewal' => ['sometimes', 'boolean'],
            'items' => ['nullable', 'array'],
            'content' => ['nullable', 'array'],
            'content.additional_info' => ['nullable', 'array'],
            'content.additional_info.mode' => ['required_with:content.additional_info', 'in:default,override,empty'],
            'content.additional_info.value' => ['required_if:content.additional_info.mode,override'],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function contentFieldKeys(): array
    {
        return [];
    }

    protected function validateContentKeys(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $content = $this->input('content');

        if (! is_array($content) || $content === []) {
            return;
        }

        $unknown = array_values(array_diff(array_keys($content), ['additional_info']));

        foreach ($unknown as $field) {
            $validator->errors()->add("content.{$field}", "Unknown content field [{$field}].");
        }
    }

    protected function validationHint(): string
    {
        return 'POST /api/v1/proposals/{id}/invoices copies Offer 1 by default. Optional: offer (1|2), renewal, items, content.additional_info.';
    }
}
