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

#[Name('get_record')]
#[Description('Get one Kanjo record by id with its related records: a client includes its proposals, invoices, services, and SPKs; a proposal includes its invoices and SPKs; a service includes its invoices. Get ids from search_records first.')]
#[IsReadOnly]
#[IsIdempotent]
#[IsOpenWorld(false)]
class GetRecordTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'type' => ['required', 'in:'.implode(',', RecordType::values())],
            'id' => ['required', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $type = RecordType::from($validated['type']);

        return DocumentApiBridge::run($request, fn (): mixed => app()->call([app($type->controller()), 'show'], [
            'request' => DocumentApiBridge::query(['limit' => $validated['limit'] ?? null]),
            $type->value => $type->find((int) $validated['id']),
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
                ->required(),
            'id' => $schema->integer()
                ->description('Record id from search_records or another response.')
                ->required(),
            'limit' => $schema->integer()
                ->description('Client only: cap each related list, 1-100. Default 50.'),
        ];
    }
}
