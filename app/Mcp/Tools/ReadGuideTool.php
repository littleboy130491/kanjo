<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('read_guide')]
#[Description('Read the Kanjo operating manual: domain model, field names, content modes, client snapshot rules, and create/update recipes. Call this once per conversation before creating or updating anything.')]
#[IsReadOnly]
#[IsIdempotent]
#[IsOpenWorld(false)]
class ReadGuideTool extends Tool
{
    private const PREAMBLE = <<<'MD'
        # Using this manual through MCP

        The manual below was written for the Kanjo REST API. Through this MCP server, use these tools instead of HTTP calls:

        | REST endpoint | MCP tool |
        |---|---|
        | `GET /companies`, `/clients`, `/proposals`, `/invoices`, `/spks`, `/services` | `search_records` with `type` |
        | `GET /{type}/{id}` | `get_record` |
        | `GET /content-defaults/proposal`, `/content-defaults/spk` | `get_content_defaults` |
        | `GET /proposals/skeleton`, `/invoices/skeleton`, `/spks/skeleton` | `get_skeleton` |
        | `POST /proposals` | `create_proposal` |
        | `POST /invoices` | `create_invoice` |
        | `POST /spks` | `create_spk` |
        | `POST /proposals/{id}/invoices` | `create_invoice_from_proposal` |
        | `POST /proposals/{id}/spks` | `create_spk_from_proposal` |
        | `PATCH /{type}/{id}` | `update_record` |

        - Send the JSON request body as `payload`. Pass `dry_run` as its own tool argument.
        - Skip the manual's auth and HTTP sections: you are signed in as a Kanjo user, and documents you create or update are attributed to that user (not `DOCUMENT_API_USER_ID`).
        - A 4xx in the manual comes back here as a tool error with the same JSON body.

        ---

        MD;

    public function handle(Request $request): Response
    {
        $path = base_path('docs/api/agent-guide.md');

        if (! is_file($path)) {
            return Response::error('The agent guide is not installed on this server.');
        }

        return Response::text(self::PREAMBLE.file_get_contents($path));
    }
}
