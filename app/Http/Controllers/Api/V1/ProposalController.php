<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProposalInvoiceRequest;
use App\Http\Requests\Api\V1\StoreProposalRequest;
use App\Http\Requests\Api\V1\StoreProposalSpkRequest;
use App\Models\Proposal;
use App\Services\DocumentApi\InvoiceCreator;
use App\Services\DocumentApi\ProposalCreator;
use App\Services\DocumentApi\SpkCreator;
use Illuminate\Http\JsonResponse;

class ProposalController extends Controller
{
    public function store(StoreProposalRequest $request, ProposalCreator $creator): JsonResponse
    {
        return response()->json($creator->handle($request->validated()));
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
