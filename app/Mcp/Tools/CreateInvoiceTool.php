<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Requests\Api\V1\StoreInvoiceRequest;
use App\Mcp\Support\DocumentApiBridge;
use App\Mcp\Tools\Concerns\AcceptsApiPayload;
use App\Models\Invoice;
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

#[Name('create_invoice')]
#[Description('Create a published standalone invoice (not linked to a proposal). To bill an existing proposal use create_invoice_from_proposal instead. Get the template from get_skeleton type=invoice and dry-run first.')]
#[IsReadOnly(false)]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class CreateInvoiceTool extends Tool
{
    use AcceptsApiPayload;

    public function handle(Request $request): Response|ResponseFactory
    {
        return DocumentApiBridge::run($request, function () use ($request): mixed {
            DocumentApiBridge::authorize($request, 'create', Invoice::class);

            return app()->call([app(InvoiceController::class), 'store'], [
                'request' => DocumentApiBridge::form(StoreInvoiceRequest::class, DocumentApiBridge::payload($request)),
            ]);
        });
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return $this->payloadSchema($schema, 'Invoice body in the get_skeleton type=invoice shape: company_id, client_id or client, items [{title, price, description}], and content.additional_info with mode override or empty.');
    }
}
