<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateServiceRequest;
use App\Models\Service;
use App\Services\DocumentApi\DocumentCatalog;
use App\Services\DocumentApi\DocumentUpdater;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Service::query()->withCount('invoices')->orderByDesc('id');

        DocumentCatalog::applySearch($query, (string) $request->query('q', ''), [
            'name',
            'domain',
        ]);

        if (filled($request->query('client_id'))) {
            $query->where('client_id', (int) $request->query('client_id'));
        }

        if (filled($request->query('status'))) {
            $query->where('status', (string) $request->query('status'));
        }

        $services = $query
            ->limit(DocumentCatalog::limit($request))
            ->get()
            ->map(fn (Service $service): array => DocumentCatalog::service($service));

        return response()->json(['data' => $services]);
    }

    public function show(Service $service): JsonResponse
    {
        $service->loadCount('invoices');
        $invoices = $service->invoices()->orderByDesc('id')->limit(50)->get();
        $proposalIds = $invoices
            ->pluck('proposal_id')
            ->filter()
            ->unique()
            ->values();

        return response()->json([
            'data' => DocumentCatalog::service($service),
            'invoices' => $invoices->map(fn ($invoice): array => DocumentCatalog::invoice($invoice))->values(),
            'proposal_ids' => $proposalIds,
        ]);
    }

    public function update(UpdateServiceRequest $request, Service $service, DocumentUpdater $updater): JsonResponse
    {
        return response()->json($updater->service($service, $request->validated()));
    }
}
