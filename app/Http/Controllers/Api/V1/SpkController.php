<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreSpkRequest;
use App\Services\DocumentApi\SpkCreator;
use Illuminate\Http\JsonResponse;

class SpkController extends Controller
{
    public function store(StoreSpkRequest $request, SpkCreator $creator): JsonResponse
    {
        return response()->json($creator->handleStandalone($request->validated()));
    }
}
