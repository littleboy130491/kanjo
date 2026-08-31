<?php

use App\Http\Controllers\InvoiceViewController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\ProposalViewController;
use App\Http\Controllers\SpkViewController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/proposal/{slug}', [ProposalViewController::class, 'show'])
    ->middleware('document.access:proposal')
    ->name('proposal.show');
Route::get('/proposal/{slug}/auth', [ProposalViewController::class, 'authenticateRedirect'])
    ->name('proposal.auth.redirect');
Route::post('/proposal/{slug}/auth', [ProposalViewController::class, 'authenticate'])
    ->defaults('document_type', 'proposal')
    ->middleware('document.auth.throttle')
    ->name('proposal.auth');

Route::get('/proposal-v2/{slug}', [ProposalViewController::class, 'showV2'])
    ->middleware('document.access:proposal,proposal-v2.auth')
    ->name('proposal-v2.show');
Route::get('/proposal-v2/{slug}/auth', [ProposalViewController::class, 'authenticateRedirectV2'])
    ->name('proposal-v2.auth.redirect');
Route::post('/proposal-v2/{slug}/auth', [ProposalViewController::class, 'authenticateV2'])
    ->defaults('document_type', 'proposal')
    ->middleware('document.auth.throttle')
    ->name('proposal-v2.auth');
Route::get('/proposal/{slug}/pdf', [PdfController::class, 'proposal'])
    ->middleware('document.access:proposal')
    ->name('pdf.proposal');

Route::get('/invoice/{slug}', [InvoiceViewController::class, 'show'])
    ->middleware('document.access:invoice')
    ->name('invoice.show');
Route::get('/invoice/{slug}/auth', [InvoiceViewController::class, 'authenticateRedirect'])
    ->name('invoice.auth.redirect');
Route::post('/invoice/{slug}/auth', [InvoiceViewController::class, 'authenticate'])
    ->defaults('document_type', 'invoice')
    ->middleware('document.auth.throttle')
    ->name('invoice.auth');
Route::get('/invoice/{slug}/pdf', [PdfController::class, 'invoice'])
    ->middleware('document.access:invoice')
    ->name('pdf.invoice');

Route::get('/spk/{slug}', [SpkViewController::class, 'show'])
    ->middleware('document.access:spk')
    ->name('spk.show');
Route::get('/spk/{slug}/auth', [SpkViewController::class, 'authenticateRedirect'])
    ->name('spk.auth.redirect');
Route::post('/spk/{slug}/auth', [SpkViewController::class, 'authenticate'])
    ->defaults('document_type', 'spk')
    ->middleware('document.auth.throttle')
    ->name('spk.auth');
Route::get('/spk/{slug}/pdf', [PdfController::class, 'spk'])
    ->middleware('document.access:spk')
    ->name('pdf.spk');

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
