<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\Api\V1\DiscoveryController;
use App\Mcp\Support\DocumentApiBridge;
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

#[Name('get_skeleton')]
#[Description('Get the create payload template for a proposal, invoice, or SPK, with every required field and content key. Fill it in and send it as payload to the matching create tool.')]
#[IsReadOnly]
#[IsIdempotent]
#[IsOpenWorld(false)]
class GetSkeletonTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'type' => ['required', 'in:proposal,invoice,spk'],
        ]);

        return DocumentApiBridge::run($request, fn (): mixed => match ($validated['type']) {
            'proposal' => app(DiscoveryController::class)->proposalSkeleton(),
            'invoice' => app(DiscoveryController::class)->invoiceSkeleton(),
            'spk' => app(DiscoveryController::class)->spkSkeleton(),
        });
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()
                ->enum(['proposal', 'invoice', 'spk'])
                ->required(),
        ];
    }
}
