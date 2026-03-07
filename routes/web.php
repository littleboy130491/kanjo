<?php

use App\Http\Controllers\InvoiceViewController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\ProposalViewController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/proposal/{slug}', [ProposalViewController::class, 'show'])
    ->middleware('document.access:proposal')
    ->name('proposal.show');
Route::post('/proposal/{slug}/auth', [ProposalViewController::class, 'authenticate'])
    ->defaults('document_type', 'proposal')
    ->middleware('document.auth.throttle')
    ->name('proposal.auth');
Route::get('/proposal/{slug}/pdf', [PdfController::class, 'proposal'])
    ->middleware('document.access:proposal')
    ->name('pdf.proposal');

Route::get('/invoice/{slug}', [InvoiceViewController::class, 'show'])
    ->middleware('document.access:invoice')
    ->name('invoice.show');
Route::post('/invoice/{slug}/auth', [InvoiceViewController::class, 'authenticate'])
    ->defaults('document_type', 'invoice')
    ->middleware('document.auth.throttle')
    ->name('invoice.auth');
Route::get('/invoice/{slug}/pdf', [PdfController::class, 'invoice'])
    ->middleware('document.access:invoice')
    ->name('pdf.invoice');

// proxy image from runcloud
Route::get('/proxy-image/{encodedImageUrl}', function (string $encodedImageUrl) {
    $imageUrl = rawurldecode($encodedImageUrl);

    $response = Http::withBasicAuth(config('app.runcloud_username'), config('app.runcloud_password'))
        ->get($imageUrl);

    return response($response->body(), 200)
        ->header('Content-Type', $response->header('Content-Type'))
        ->header('Cache-Control', 'public, max-age=86400');
})->where('encodedImageUrl', '.*');
