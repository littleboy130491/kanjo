<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\DocumentApi\DocumentCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Client::query()->orderByDesc('id');

        DocumentCatalog::applySearch($query, (string) $request->query('q', ''), [
            'name',
            'company',
            'email',
        ]);

        $clients = $query
            ->limit(DocumentCatalog::limit($request))
            ->get()
            ->map(fn (Client $client): array => DocumentCatalog::client($client));

        return response()->json(['data' => $clients]);
    }

    public function show(Request $request, Client $client): JsonResponse
    {
        $limit = DocumentCatalog::limit($request);

        $proposals = $client->proposals()->withCount('invoices')->orderByDesc('id')->limit($limit)->get();
        $invoices = $client->invoices()->orderByDesc('id')->limit($limit)->get();
        $services = $client->services()->withCount('invoices')->orderByDesc('id')->limit($limit)->get();
        $spks = $client->spks()->orderByDesc('id')->limit($limit)->get();

        return response()->json([
            'data' => DocumentCatalog::client($client),
            'counts' => [
                'proposals' => $client->proposals()->count(),
                'invoices' => $client->invoices()->count(),
                'services' => $client->services()->count(),
                'spks' => $client->spks()->count(),
            ],
            'proposals' => $proposals->map(fn ($proposal): array => DocumentCatalog::proposal($proposal))->values(),
            'invoices' => $invoices->map(fn ($invoice): array => DocumentCatalog::invoice($invoice))->values(),
            'services' => $services->map(fn ($service): array => DocumentCatalog::service($service))->values(),
            'spks' => $spks->map(fn ($spk): array => DocumentCatalog::spk($spk))->values(),
        ]);
    }
}
