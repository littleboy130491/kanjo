<?php

namespace App\Mcp\Tools\Concerns;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;

trait AcceptsApiPayload
{
    /**
     * @return array<string, Type>
     */
    protected function payloadSchema(JsonSchema $schema, string $description): array
    {
        return [
            'payload' => $schema->object()
                ->description($description)
                ->required(),
            'dry_run' => $schema->boolean()
                ->description('true = validate and preview only, nothing is saved. Always dry-run first, then repeat with false.')
                ->default(false),
        ];
    }
}
