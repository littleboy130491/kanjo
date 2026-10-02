<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\CreateInvoiceFromProposalTool;
use App\Mcp\Tools\CreateInvoiceTool;
use App\Mcp\Tools\CreateProposalTool;
use App\Mcp\Tools\CreateSpkFromProposalTool;
use App\Mcp\Tools\CreateSpkTool;
use App\Mcp\Tools\GetContentDefaultsTool;
use App\Mcp\Tools\GetRecordTool;
use App\Mcp\Tools\GetSkeletonTool;
use App\Mcp\Tools\ReadGuideTool;
use App\Mcp\Tools\SearchRecordsTool;
use App\Mcp\Tools\UpdateRecordTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Kanjo')]
#[Version('1.0.0')]
#[Instructions(<<<'TXT'
    Kanjo is a web design agency's quotation and invoicing system. It issues proposals (quotations, numbered QUO/...), invoices (INV/...), and SPKs (work orders, SPK/...) from several issuing companies (brands) to clients.

    Before creating or updating anything, call read_guide once and follow it.

    Rules:
    - Never invent ids. Get company, client, proposal, invoice, service, and SPK ids from search_records or get_record.
    - To create a document: get_skeleton, fill in every field, call the create tool with dry_run=true, fix any errors, then call it again with dry_run=false.
    - Created documents are published immediately and attributed to the signed-in Kanjo user. Give the user the public_url and pdf_url from the response.
    - Documents store a frozen copy of the client's details. Editing a client does not change existing documents.
    - Nothing can be deleted through this server.
    TXT)]
class KanjoServer extends Server
{
    /**
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        ReadGuideTool::class,
        SearchRecordsTool::class,
        GetRecordTool::class,
        GetContentDefaultsTool::class,
        GetSkeletonTool::class,
        CreateProposalTool::class,
        CreateInvoiceTool::class,
        CreateSpkTool::class,
        CreateInvoiceFromProposalTool::class,
        CreateSpkFromProposalTool::class,
        UpdateRecordTool::class,
    ];
}
