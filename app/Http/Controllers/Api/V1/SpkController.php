<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreSpkRequest;
use App\Http\Requests\Api\V1\UpdateSpkRequest;
use App\Models\Spk;
use App\Services\DocumentApi\DocumentCatalog;
use App\Services\DocumentApi\DocumentUpdater;
use App\Services\DocumentApi\SpkCreator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpkController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Spk::query()->orderByDesc('id');

        DocumentCatalog::applySearch($query, (string) $request->query('q', ''), [
            'document_number',
            'client_company',
            'client_pic_name',
        ]);

        foreach (['client_id', 'company_id', 'proposal_id'] as $column) {
            if (filled($request->query($column))) {
                $query->where($column, (int) $request->query($column));
            }
        }

        if (filled($request->query('status'))) {
            $query->where('status', (string) $request->query('status'));
        }

        $spks = $query
            ->limit(DocumentCatalog::limit($request))
            ->get()
            ->map(fn (Spk $spk): array => DocumentCatalog::spk($spk));

        return response()->json(['data' => $spks]);
    }

    public function show(Spk $spk): JsonResponse
    {
        return response()->json([
            'data' => DocumentCatalog::spk($spk),
        ]);
    }

    public function store(StoreSpkRequest $request, SpkCreator $creator): JsonResponse
    {
        return response()->json($creator->handleStandalone($request->validated()));
    }

    public function update(UpdateSpkRequest $request, Spk $spk, DocumentUpdater $updater): JsonResponse
    {
        return response()->json($updater->spk($spk, $request->validated()));
    }
}
