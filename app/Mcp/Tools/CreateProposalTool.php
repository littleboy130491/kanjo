<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\Api\V1\ProposalController;
use App\Http\Requests\Api\V1\StoreProposalRequest;
use App\Mcp\Support\DocumentApiBridge;
use App\Mcp\Tools\Concerns\AcceptsApiPayload;
use App\Models\Proposal;
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

#[Name('create_proposal')]
#[Description('Create a published proposal (quotation). Workflow: read_guide, search_records type=company for company_id, find or describe the client, get_skeleton type=proposal, fill it, call with dry_run=true, then call again with dry_run=false. Returns document_number, public_url, and pdf_url.')]
#[IsReadOnly(false)]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class CreateProposalTool extends Tool
{
    use AcceptsApiPayload;

    public function handle(Request $request): Response|ResponseFactory
    {
        return DocumentApiBridge::run($request, function () use ($request): mixed {
            DocumentApiBridge::authorize($request, 'create', Proposal::class);

            return app()->call([app(ProposalController::class), 'store'], [
                'request' => DocumentApiBridge::form(StoreProposalRequest::class, DocumentApiBridge::payload($request)),
            ]);
        });
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return $this->payloadSchema($schema, 'Proposal body in the get_skeleton type=proposal shape: company_id, client_id or client {company, name, email, phone, address}, offer fields, and every content key with a mode (default, override, or empty).');
    }
}
