<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\DocumentApiBridge;
use App\Mcp\Support\RecordType;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('search_records')]
#[Description('List or search Kanjo records, newest first. Use type=company to get issuing companies and their PICs (needed for company_id). Document search (proposal, invoice, spk) matches document number and the frozen client name/company on the document.')]
#[IsReadOnly]
#[IsIdempotent]
#[IsOpenWorld(false)]
class SearchRecordsTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'type' => ['required', 'in:'.implode(',', RecordType::values())],
            'q' => ['nullable', 'string', 'max:255'],
            'client_id' => ['nullable', 'integer'],
            'company_id' => ['nullable', 'integer'],
            'proposal_id' => ['nullable', 'integer'],
            'service_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string'],
            'payment_status' => ['nullable', 'string'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $type = RecordType::from($validated['type']);

        return DocumentApiBridge::run($request, fn (): mixed => app()->call([app($type->controller()), 'index'], [
            'request' => DocumentApiBridge::query(collect($validated)->except('type')->all()),
        ]));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()
                ->enum(RecordType::values())
                ->description('Record type to list.')
                ->required(),
            'q' => $schema->string()
                ->description('Search text. client: name/company/email. service: name/domain. Documents: number and client snapshot. Ignored for company.'),
            'client_id' => $schema->integer()->description('Filter proposal, invoice, spk, or service by client.'),
            'company_id' => $schema->integer()->description('Filter proposal, invoice, or spk by issuing company.'),
            'proposal_id' => $schema->integer()->description('Filter invoice or spk by source proposal.'),
            'service_id' => $schema->integer()->description('Filter invoice by service.'),
            'status' => $schema->string()->description('Documents: draft or published. Services: on-going, suspended, or terminated.'),
            'payment_status' => $schema->string()->description('Invoices only: unpaid, partially_paid, paid, overdue, or cancelled.'),
            'limit' => $schema->integer()->description('Max rows, 1-100. Default 50.'),
        ];
    }
}
