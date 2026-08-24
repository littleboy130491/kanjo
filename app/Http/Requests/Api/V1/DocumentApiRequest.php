<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

abstract class DocumentApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<int, string>
     */
    abstract protected function contentFieldKeys(): array;

    abstract protected function validationHint(): string;

    protected bool $requireAllContentKeys = true;

    /**
     * @return array<int, string>
     */
    protected function allowedModesFor(string $field): array
    {
        return ['default', 'override', 'empty'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function contentRules(): array
    {
        $presence = $this->requireAllContentKeys ? 'required' : 'sometimes';
        $rules = [
            'content' => [$presence, 'array'],
        ];

        foreach ($this->contentFieldKeys() as $field) {
            $modePresence = $this->requireAllContentKeys
                ? 'required'
                : "required_with:content.{$field}";
            $rules["content.{$field}"] = [$presence, 'array'];
            $rules["content.{$field}.mode"] = [$modePresence, Rule::in($this->allowedModesFor($field))];
            $rules["content.{$field}.value"] = ["required_if:content.{$field}.mode,override"];
            $rules["content.{$field}.template"] = ['nullable', 'string'];
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    protected function clientRules(bool $required = true): array
    {
        $clientPresence = $required ? ['required_without:client_id', 'array'] : ['nullable', 'array'];
        $snapshotPresence = $required ? ['required_without:client_id'] : ['nullable'];

        return [
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'client' => $clientPresence,
            'client.company' => [...$snapshotPresence, 'string', 'max:255'],
            'client.name' => [...$snapshotPresence, 'string', 'max:255'],
            'client.email' => ['nullable', 'email', 'max:255'],
            'client.phone' => ['nullable', 'string', 'max:255'],
            'client.address' => ['nullable', 'string'],
            'client.pic_role' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateContentKeys($validator);
            $this->validateItems($validator);
        });
    }

    protected function validateItems(Validator $validator): void
    {
        if (! $this->has('items')) {
            return;
        }

        $items = $this->input('items');

        if (! is_array($items)) {
            return;
        }

        $rows = isset($items['en']) || isset($items['id'])
            ? array_merge($items['en'] ?? [], $items['id'] ?? [])
            : $items;

        if ($rows === []) {
            $validator->errors()->add('items', 'The items field must contain at least one item.');

            return;
        }

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                $validator->errors()->add("items.{$index}", 'Each item must be an object with title and price.');

                continue;
            }

            if (blank($row['title'] ?? null)) {
                $validator->errors()->add("items.{$index}.title", 'The item title is required.');
            }

            if (! isset($row['price']) || ! is_numeric($row['price'])) {
                $validator->errors()->add("items.{$index}.price", 'The item price is required and must be numeric.');
            }
        }
    }

    protected function validateContentKeys(Validator $validator): void
    {
        $content = $this->input('content');

        if (! is_array($content)) {
            return;
        }

        $allowed = $this->contentFieldKeys();
        $missing = $this->requireAllContentKeys
            ? array_values(array_diff($allowed, array_keys($content)))
            : [];
        $unknown = array_values(array_diff(array_keys($content), $allowed));

        foreach ($missing as $field) {
            $validator->errors()->add("content.{$field}", "The content.{$field} field is required.");
        }

        foreach ($unknown as $field) {
            $validator->errors()->add("content.{$field}", "Unknown content field [{$field}].");
        }
    }

    protected function failedValidation(Validator $validator): void
    {
        $missing = [];

        foreach ($validator->errors()->keys() as $key) {
            if (preg_match('/^content\.([^.]+)/', $key, $matches) === 1) {
                $missing[] = $matches[1];
            }
        }

        throw new HttpResponseException(response()->json([
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
            'missing_content_fields' => array_values(array_unique($missing)),
            'hint' => $this->validationHint(),
        ], 422));
    }
}
