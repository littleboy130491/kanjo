<?php

use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\DiscoveryController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\ProposalController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\SpkController;
use Illuminate\Support\Facades\Route;

Route::middleware(['document.api', 'throttle:60,1'])
    ->prefix('v1')
    ->group(function (): void {
        Route::get('/', [DiscoveryController::class, 'index']);
        Route::get('/guide', [DiscoveryController::class, 'guide']);
        Route::get('/openapi.json', [DiscoveryController::class, 'openapi']);

        Route::get('/companies', [CompanyController::class, 'index']);
        Route::get('/companies/{company}', [CompanyController::class, 'show']);
        Route::get('/clients', [ClientController::class, 'index']);
        Route::get('/clients/{client}', [ClientController::class, 'show']);

        Route::get('/content-defaults/proposal', [DiscoveryController::class, 'proposalContentDefaults']);
        Route::get('/content-defaults/spk', [DiscoveryController::class, 'spkContentDefaults']);

        Route::get('/proposals/skeleton', [DiscoveryController::class, 'proposalSkeleton']);
        Route::get('/invoices/skeleton', [DiscoveryController::class, 'invoiceSkeleton']);
        Route::get('/spks/skeleton', [DiscoveryController::class, 'spkSkeleton']);

        Route::get('/proposals', [ProposalController::class, 'index']);
        Route::get('/proposals/{proposal}', [ProposalController::class, 'show']);
        Route::get('/invoices', [InvoiceController::class, 'index']);
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
        Route::get('/spks', [SpkController::class, 'index']);
        Route::get('/spks/{spk}', [SpkController::class, 'show']);
        Route::get('/services', [ServiceController::class, 'index']);
        Route::get('/services/{service}', [ServiceController::class, 'show']);

        Route::post('/proposals', [ProposalController::class, 'store']);
        Route::post('/invoices', [InvoiceController::class, 'store']);
        Route::post('/spks', [SpkController::class, 'store']);

        Route::post('/proposals/{proposal}/invoices', [ProposalController::class, 'storeInvoice']);
        Route::post('/proposals/{proposal}/spks', [ProposalController::class, 'storeSpk']);
    });
