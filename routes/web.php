<?php

use App\Http\Controllers\InvoiceViewController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\ProposalViewController;
use Illuminate\Http\Request;
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

// Proxy image from RunCloud. Use a query string to avoid encoded-slash path issues.
$proxyImageHandler = function (Request $request, ?string $encodedImageUrl = null) {
    $imageUrl = $request->query('url');

    if (! is_string($imageUrl) || $imageUrl === '') {
        $imageUrl = $encodedImageUrl ? rawurldecode($encodedImageUrl) : null;
    }

    abort_unless(is_string($imageUrl) && filter_var($imageUrl, FILTER_VALIDATE_URL), 404);

    $response = Http::withBasicAuth(config('app.runcloud_username'), config('app.runcloud_password'))
        ->get($imageUrl);

    abort_if($response->failed(), $response->status());

    return response($response->body(), $response->status())
        ->header('Content-Type', $response->header('Content-Type', 'application/octet-stream'))
        ->header('Cache-Control', 'public, max-age=86400');
};

Route::get('/proxy-image', $proxyImageHandler)->name('proxy-image');
Route::get('/proxy-image/{encodedImageUrl}', $proxyImageHandler)->where('encodedImageUrl', '.*');
