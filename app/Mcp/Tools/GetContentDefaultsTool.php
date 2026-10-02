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

#[Name('get_content_defaults')]
#[Description('Get the stored default content used when a create payload sets a content field to {"mode": "default"}. proposal returns the content-default packs (pass a pack id as content_default_id); spk returns the defaults plus the placeholder names it fills in.')]
#[IsReadOnly]
#[IsIdempotent]
#[IsOpenWorld(false)]
class GetContentDefaultsTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'type' => ['required', 'in:proposal,spk'],
        ]);

        return DocumentApiBridge::run($request, fn (): mixed => match ($validated['type']) {
            'proposal' => app(DiscoveryController::class)->proposalContentDefaults(),
            'spk' => app(DiscoveryController::class)->spkContentDefaults(),
        });
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()
                ->enum(['proposal', 'spk'])
                ->required(),
        ];
    }
}
