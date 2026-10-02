<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\DocumentApiBridge;
use App\Mcp\Support\RecordType;
use App\Mcp\Tools\Concerns\AcceptsApiPayload;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('update_record')]
#[Description('Partially update an existing company, client, service, proposal, invoice, or SPK. Send only the fields to change. Editing a client does not change client details already frozen on its documents. Nothing can be deleted. Dry-run first.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsIdempotent]
#[IsOpenWorld(false)]
class UpdateRecordTool extends Tool
{
    use AcceptsApiPayload;

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'type' => ['required', 'in:'.implode(',', RecordType::values())],
            'id' => ['required', 'integer', 'min:1'],
        ]);
        $type = RecordType::from($validated['type']);

        return DocumentApiBridge::run($request, function () use ($request, $type, $validated): mixed {
            $record = $type->find((int) $validated['id']);
            DocumentApiBridge::authorize($request, 'update', $record);

            return app()->call([app($type->controller()), 'update'], [
                'request' => DocumentApiBridge::form($type->updateRequest(), DocumentApiBridge::payload($request)),
                $type->value => $record,
            ]);
        });
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
                ->description('Record id from search_records or get_record.')
                ->required(),
            ...$this->payloadSchema($schema, 'Only the fields to change, e.g. {"payment_status": "paid"} on an invoice or {"renewal_date": "2027-02-15"} on a service. Document content keys still need a mode. See read_guide section 6D.'),
        ];
    }
}
