<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProposalContentDefault;
use App\Models\SpkContentDefault;
use App\Services\DocumentApi\OpenApiSpec;
use App\Services\DocumentApi\Skeletons;
use App\Services\DocumentApi\SpkContentCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class DiscoveryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'name' => 'Kanjo Document API',
            'version' => 'v1',
            'guide_url' => url('/api/v1/guide'),
            'openapi_url' => url('/api/v1/openapi.json'),
            'endpoints' => [
                'GET /api/v1',
                'GET /api/v1/guide',
                'GET /api/v1/openapi.json',
                'GET /api/v1/companies',
                'GET /api/v1/companies/{id}',
                'GET /api/v1/clients',
                'GET /api/v1/content-defaults/proposal',
                'GET /api/v1/content-defaults/spk',
                'GET /api/v1/proposals/skeleton',
                'GET /api/v1/invoices/skeleton',
                'GET /api/v1/spks/skeleton',
                'POST /api/v1/proposals',
                'POST /api/v1/invoices',
                'POST /api/v1/spks',
                'POST /api/v1/proposals/{id}/invoices',
                'POST /api/v1/proposals/{id}/spks',
            ],
        ]);
    }

    public function guide(): Response
    {
        $path = base_path('docs/api/agent-guide.md');

        abort_unless(is_file($path), 404, 'Agent guide is not installed.');

        return response((string) file_get_contents($path), 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
        ]);
    }

    public function openapi(): JsonResponse
    {
        return response()->json(OpenApiSpec::document());
    }

    public function proposalSkeleton(): JsonResponse
    {
        return response()->json(Skeletons::proposal());
    }

    public function invoiceSkeleton(): JsonResponse
    {
        return response()->json(Skeletons::invoice());
    }

    public function spkSkeleton(): JsonResponse
    {
        return response()->json(Skeletons::spk());
    }

    public function proposalContentDefaults(): JsonResponse
    {
        $record = ProposalContentDefault::query()
            ->where('field_key', ProposalContentDefault::GLOBAL_FIELD_KEY)
            ->first();

        return response()->json([
            'field_keys' => array_keys(ProposalContentDefault::FIELD_OPTIONS),
            'value' => $record?->getTranslations('value') ?? [],
        ]);
    }

    public function spkContentDefaults(): JsonResponse
    {
        $record = SpkContentDefault::query()
            ->where('field_key', SpkContentDefault::GLOBAL_FIELD_KEY)
            ->first();

        return response()->json([
            'field_keys' => array_keys(SpkContentDefault::FIELD_OPTIONS),
            'placeholders' => SpkContentCatalog::PLACEHOLDERS,
            'value' => $record?->getTranslations('value') ?? [],
        ]);
    }
}
