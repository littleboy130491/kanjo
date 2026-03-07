<?php

namespace App\Http\Controllers;

use App\Http\Middleware\DocumentAccessMiddleware;
use App\Models\Invoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InvoiceViewController extends Controller
{
    public function show(Request $request, string $slug)
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);

        /** @var Invoice|null $invoice */
        $invoice = $request->attributes->get('document');

        if (! $invoice) {
            abort(404);
        }

        $invoice->loadMissing(['company']);

        return view('invoices.show', [
            'invoice' => $invoice,
            'locale' => $locale,
            'slug' => $slug,
        ]);
    }

    public function authenticate(Request $request, string $slug): RedirectResponse
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $documentNumber = str_replace('-', '/', $slug);

        $invoice = Invoice::query()
            ->where(fn ($query) => $query
                ->where('slug', $slug)
                ->orWhere('document_number', $documentNumber))
            ->first();

        if (! $invoice || $invoice->status->value !== 'published') {
            abort(404);
        }

        $isDocumentCredential = filled($invoice->access_username) && filled($invoice->access_password);
        $expectedUsername = $isDocumentCredential
            ? $invoice->access_username
            : config('app.global_access_username');

        $expectedPassword = $isDocumentCredential
            ? $invoice->access_password
            : config('app.global_access_password');

        if (! $expectedUsername || ! $expectedPassword) {
            abort(403, 'Document access credentials are not configured.');
        }

        $usernameMatches = hash_equals($expectedUsername, (string) $request->string('username'));
        $passwordMatches = DocumentAccessMiddleware::passwordsMatch(
            (string) $request->string('password'),
            $expectedPassword,
        );

        if (! $usernameMatches || ! $passwordMatches) {
            return back()->withErrors([
                'credentials' => 'Invalid credentials.',
            ])->withInput();
        }

        $request->session()->put([
            DocumentAccessMiddleware::sessionKey('invoice', $invoice->id) => true,
            DocumentAccessMiddleware::versionKey('invoice', $invoice->id) => DocumentAccessMiddleware::credentialVersion($invoice),
        ]);

        return redirect()->route('invoice.show', [
            'slug' => $slug,
            'lang' => $request->input('lang'),
        ]);
    }

    private function resolveLocale(Request $request): string
    {
        $supported = config('app.supported_locales', ['en', 'id']);
        $locale = (string) $request->query('lang', config('app.locale', 'en'));

        return in_array($locale, $supported, true) ? $locale : config('app.locale', 'en');
    }
}
