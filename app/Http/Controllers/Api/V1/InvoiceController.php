<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreInvoiceRequest;
use App\Http\Requests\Api\V1\UpdateInvoiceRequest;
use App\Models\Invoice;
use App\Services\DocumentApi\DocumentCatalog;
use App\Services\DocumentApi\DocumentUpdater;
use App\Services\DocumentApi\InvoiceCreator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::query()->orderByDesc('id');

        DocumentCatalog::applySearch($query, (string) $request->query('q', ''), [
            'document_number',
            'client_company',
            'client_name',
        ]);

        foreach (['client_id', 'company_id', 'proposal_id', 'service_id'] as $column) {
            if (filled($request->query($column))) {
                $query->where($column, (int) $request->query($column));
            }
        }

        if (filled($request->query('status'))) {
            $query->where('status', (string) $request->query('status'));
        }

        if (filled($request->query('payment_status'))) {
            $query->where('payment_status', (string) $request->query('payment_status'));
        }

        $invoices = $query
            ->limit(DocumentCatalog::limit($request))
            ->get()
            ->map(fn (Invoice $invoice): array => DocumentCatalog::invoice($invoice));

        return response()->json(['data' => $invoices]);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        return response()->json([
            'data' => DocumentCatalog::invoice($invoice),
        ]);
    }

    public function store(StoreInvoiceRequest $request, InvoiceCreator $creator): JsonResponse
    {
        return response()->json($creator->handleStandalone($request->validated()));
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice, DocumentUpdater $updater): JsonResponse
    {
        return response()->json($updater->invoice($invoice, $request->validated()));
    }
}
