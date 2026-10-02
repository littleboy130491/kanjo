<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\Api\V1\ProposalController;
use App\Http\Requests\Api\V1\StoreProposalInvoiceRequest;
use App\Mcp\Support\DocumentApiBridge;
use App\Mcp\Tools\Concerns\AcceptsApiPayload;
use App\Models\Invoice;
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

#[Name('create_invoice_from_proposal')]
#[Description('Create a published invoice from an existing proposal. The client snapshot is copied and one item is generated from Offer 1, unless the payload sets offer 2, renewal=true, or its own items. Dry-run first.')]
#[IsReadOnly(false)]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class CreateInvoiceFromProposalTool extends Tool
{
    use AcceptsApiPayload;

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'proposal_id' => ['required', 'integer', 'min:1'],
        ]);

        return DocumentApiBridge::run($request, function () use ($request, $validated): mixed {
            $proposal = Proposal::query()->findOrFail((int) $validated['proposal_id']);
            DocumentApiBridge::authorize($request, 'create', Invoice::class);

            return app()->call([app(ProposalController::class), 'storeInvoice'], [
                'request' => DocumentApiBridge::form(StoreProposalInvoiceRequest::class, DocumentApiBridge::payload($request)),
                'proposal' => $proposal,
            ]);
        });
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'proposal_id' => $schema->integer()
                ->description('Source proposal id from search_records or get_record.')
                ->required(),
            ...$this->payloadSchema($schema, 'Optional overrides. Examples: {} for an Offer 1 down-payment invoice; {"offer": 2}; {"renewal": true}; items [{title, price, description}]; content.additional_info with mode default, override, or empty.'),
        ];
    }
}
