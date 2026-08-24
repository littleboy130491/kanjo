<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreInvoiceRequest;
use App\Services\DocumentApi\InvoiceCreator;
use Illuminate\Http\JsonResponse;

class InvoiceController extends Controller
{
    public function store(StoreInvoiceRequest $request, InvoiceCreator $creator): JsonResponse
    {
        return response()->json($creator->handleStandalone($request->validated()));
    }
}
