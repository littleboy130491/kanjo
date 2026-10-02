<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\Api\V1\ProposalController;
use App\Http\Requests\Api\V1\StoreProposalSpkRequest;
use App\Mcp\Support\DocumentApiBridge;
use App\Mcp\Tools\Concerns\AcceptsApiPayload;
use App\Models\Proposal;
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

#[Name('create_spk_from_proposal')]
#[Description('Create a published SPK (work order) from an existing proposal. Client, company, and offer details are copied from the proposal. Dry-run first.')]
#[IsReadOnly(false)]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class CreateSpkFromProposalTool extends Tool
{
    use AcceptsApiPayload;

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'proposal_id' => ['required', 'integer', 'min:1'],
        ]);

        return DocumentApiBridge::run($request, function () use ($request, $validated): mixed {
            $proposal = Proposal::query()->findOrFail((int) $validated['proposal_id']);
            DocumentApiBridge::authorize($request, 'create', Spk::class);

            return app()->call([app(ProposalController::class), 'storeSpk'], [
                'request' => DocumentApiBridge::form(StoreProposalSpkRequest::class, DocumentApiBridge::payload($request)),
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
            ...$this->payloadSchema($schema, 'SPK body: content keys title, party_identification, subject, content, signature, each with a mode. Optional company_pic_index (0-based, from the company pic list) and offer (1 or 2).'),
        ];
    }
}
