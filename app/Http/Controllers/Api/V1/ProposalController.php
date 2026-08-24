<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProposalInvoiceRequest;
use App\Http\Requests\Api\V1\StoreProposalRequest;
use App\Http\Requests\Api\V1\StoreProposalSpkRequest;
use App\Http\Requests\Api\V1\UpdateProposalRequest;
use App\Models\Proposal;
use App\Services\DocumentApi\DocumentCatalog;
use App\Services\DocumentApi\DocumentUpdater;
use App\Services\DocumentApi\InvoiceCreator;
use App\Services\DocumentApi\ProposalCreator;
use App\Services\DocumentApi\SpkCreator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProposalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Proposal::query()->withCount('invoices')->orderByDesc('id');

        DocumentCatalog::applySearch($query, (string) $request->query('q', ''), [
            'document_number',
            'client_company',
            'client_name',
        ]);

        if (filled($request->query('client_id'))) {
            $query->where('client_id', (int) $request->query('client_id'));
        }

        if (filled($request->query('company_id'))) {
            $query->where('company_id', (int) $request->query('company_id'));
        }

        if (filled($request->query('status'))) {
            $query->where('status', (string) $request->query('status'));
        }

        $proposals = $query
            ->limit(DocumentCatalog::limit($request))
            ->get()
            ->map(fn (Proposal $proposal): array => DocumentCatalog::proposal($proposal));

        return response()->json(['data' => $proposals]);
    }

    public function show(Proposal $proposal): JsonResponse
    {
        $proposal->loadCount('invoices');
        $invoices = $proposal->invoices()->orderByDesc('id')->limit(50)->get();
        $spks = $proposal->spks()->orderByDesc('id')->limit(50)->get();
        $serviceIds = $invoices
            ->pluck('service_id')
            ->filter()
            ->unique()
            ->values();

        return response()->json([
            'data' => DocumentCatalog::proposal($proposal),
            'invoices' => $invoices->map(fn ($invoice): array => DocumentCatalog::invoice($invoice))->values(),
            'spks' => $spks->map(fn ($spk): array => DocumentCatalog::spk($spk))->values(),
            'service_ids' => $serviceIds,
        ]);
    }

    public function store(StoreProposalRequest $request, ProposalCreator $creator): JsonResponse
    {
        return response()->json($creator->handle($request->validated()));
    }

    public function update(UpdateProposalRequest $request, Proposal $proposal, DocumentUpdater $updater): JsonResponse
    {
        return response()->json($updater->proposal($proposal, $request->validated()));
    }

    public function storeInvoice(
        StoreProposalInvoiceRequest $request,
        Proposal $proposal,
        InvoiceCreator $creator,
    ): JsonResponse {
        return response()->json($creator->handleFromProposal($proposal, $request->validated()));
    }

    public function storeSpk(
        StoreProposalSpkRequest $request,
        Proposal $proposal,
        SpkCreator $creator,
    ): JsonResponse {
        return response()->json($creator->handleFromProposal($proposal, $request->validated()));
    }
}
