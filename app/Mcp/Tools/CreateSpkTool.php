<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\Api\V1\SpkController;
use App\Http\Requests\Api\V1\StoreSpkRequest;
use App\Mcp\Support\DocumentApiBridge;
use App\Mcp\Tools\Concerns\AcceptsApiPayload;
use App\Models\Spk;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('create_spk')]
#[Description('Create a published standalone SPK (work order / letter of agreement). To create one from an existing proposal use create_spk_from_proposal instead. Get the template from get_skeleton type=spk and dry-run first.')]
#[IsReadOnly(false)]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class CreateSpkTool extends Tool
{
    use AcceptsApiPayload;

    public function handle(Request $request): Response|ResponseFactory
    {
        return DocumentApiBridge::run($request, function () use ($request): mixed {
            DocumentApiBridge::authorize($request, 'create', Spk::class);

            return app()->call([app(SpkController::class), 'store'], [
                'request' => DocumentApiBridge::form(StoreSpkRequest::class, DocumentApiBridge::payload($request)),
            ]);
        });
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return $this->payloadSchema($schema, 'SPK body in the get_skeleton type=spk shape: company_id, client_id or client, and content keys title, party_identification, subject, content, signature, each with a mode.');
    }
}
